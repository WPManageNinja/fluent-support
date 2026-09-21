<?php
/**
 * Fluent Support local test harness.
 *
 * Shared runtime for every suite. Loaded by each runner via `require_once`.
 * Everything runs through WP-CLI (`wp eval-file`) against the Docker lab's
 * real WordPress + MariaDB install — never against a dev site.
 *
 * Design notes (do not "improve" these without reading tests/README.md):
 *
 *  - REST calls are dispatched IN-PROCESS via rest_do_request(), not over HTTP.
 *    Fast (no network, no second PHP boot), deterministic, and it keeps
 *    $wpdb->last_error observable in the same process.
 *  - A DB error is detected THREE ways because any one of them can be masked:
 *    the response status, $wpdb->last_error, and the plugin's own exception
 *    envelope. All three are checked.
 *  - PHP notices/warnings/deprecations are promoted to failures, filtered to
 *    the fluent-support plugin paths. "Silently broken" is the exact condition
 *    this whole effort exists to eliminate.
 */

if (!defined('WP_CLI') || !WP_CLI) {
    exit("Fluent Support tests must run via WP-CLI.\n");
}

// Refuse to run against anything that is not a throwaway database. The suite
// creates and deletes real plugin records; convention (the lab) is not
// protection the day someone points `wp eval-file` at a real site.
require_once __DIR__ . '/guard-production-db.php';
(static function () {
    global $wpdb;
    suite_guard_against_production_db((string) DB_NAME, (string) $wpdb->prefix);
})();

if (!class_exists('\FluentSupport\App\Models\Ticket')) {
    WP_CLI::error('Fluent Support is not active on this site.');
}

class FsTest
{
    /** @var array<int,array{name:string,detail:string}> */
    public static $failures = [];

    /** @var int */
    public static $passed = 0;

    /** @var int */
    public static $skipped = 0;

    /** @var array<int,string> PHP diagnostics captured during the current case. */
    private static $diagnostics = [];

    /** @var string|null Name of the case currently running. */
    private static $currentCase = null;

    /** @var bool Whether the current case called skip(). */
    private static $currentSkipped = false;

    /** @var float */
    private static $startedAt = 0.0;

    /** @var array<int,array<string,mixed>> Messages intercepted before transport. */
    private static $sentMails = [];

    /** @var \Closure|null Exact callback retained for idempotent registration. */
    private static $mailInterceptor = null;

    // -----------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------

    /**
     * Install the error handler that turns PHP diagnostics into failures and
     * log in as an administrator so permission-gated routes are reachable.
     */
    public static function boot()
    {
        self::$startedAt = microtime(true);
        self::configureSqlEnvironment();

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Respect @-suppression and error_reporting().
            if (!(error_reporting() & $errno)) {
                return false;
            }

            // Ignore diagnostics raised inside WP core / other plugins: we are
            // testing Fluent Support (free and pro — the path hint matches
            // both), and a noisy neighbour must not fail our suite.
            if (strpos($errfile, 'plugins/fluent-support') === false) {
                return false;
            }

            self::$diagnostics[] = self::errorLabel($errno) . ': ' . $errstr
                . ' (' . self::relPath($errfile) . ':' . $errline . ')';

            return true; // handled — do not print
        });

        $admin = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID']);
        if (!$admin) {
            WP_CLI::error('No administrator user found on this site.');
        }
        wp_set_current_user($admin[0]->ID);
    }

    /**
     * Enable production-default strict SQL modes for this connection when the
     * opt-in environment axis is active. The lab's MariaDB already runs
     * strict-by-default; this axis exists for any environment that does not.
     */
    public static function configureSqlEnvironment()
    {
        if (getenv('FS_STRICT_SQL') !== '1') {
            return;
        }

        global $wpdb;
        $current = (string) $wpdb->get_var('SELECT @@SESSION.sql_mode');
        $modes = array_values(array_filter(array_map('trim', explode(',', $current))));

        foreach (['ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES'] as $requiredMode) {
            if (!in_array($requiredMode, $modes, true)) {
                $modes[] = $requiredMode;
            }
        }

        $target = implode(',', $modes);
        $updated = $wpdb->query($wpdb->prepare('SET SESSION sql_mode = %s', $target));
        if ($updated === false) {
            WP_CLI::error('Could not enable the strict SQL environment axis: ' . $wpdb->last_error);
        }

        $verified = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $wpdb->get_var('SELECT @@SESSION.sql_mode'))
        )));
        foreach (['ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES'] as $requiredMode) {
            if (!in_array($requiredMode, $verified, true)) {
                WP_CLI::error('Strict SQL environment axis did not enable ' . $requiredMode . '.');
            }
        }
    }

    /**
     * Print the summary and exit with a non-zero code if anything failed.
     * Every runner MUST end with this — the exit code is what makes the suite
     * usable from a script or a git hook.
     */
    public static function finish($suiteName)
    {
        restore_error_handler();

        $elapsed = round(microtime(true) - self::$startedAt, 1);
        $total   = self::$passed + count(self::$failures) + self::$skipped;

        WP_CLI::log('');
        WP_CLI::log(str_repeat('=', 72));
        WP_CLI::log(sprintf(
            '%s: %d/%d passed, %d failed, %d skipped  (%ss)',
            $suiteName, self::$passed, $total, count(self::$failures), self::$skipped, $elapsed
        ));

        if (self::$failures) {
            WP_CLI::log('');
            foreach (self::$failures as $i => $f) {
                WP_CLI::log(sprintf('%d) %s', $i + 1, $f['name']));
                foreach (explode("\n", rtrim($f['detail'])) as $line) {
                    WP_CLI::log('   ' . $line);
                }
                WP_CLI::log('');
            }
            WP_CLI::log(str_repeat('=', 72));
            WP_CLI::halt(1);
        }

        WP_CLI::log(str_repeat('=', 72));
        WP_CLI::halt(0);
    }

    // -----------------------------------------------------------------
    // Assertions
    // -----------------------------------------------------------------

    /**
     * Run one test case. $fn receives no arguments and should call fail()/pass()
     * indirectly through the assert helpers below.
     */
    public static function case($name, callable $fn)
    {
        self::$currentCase = $name;
        self::$diagnostics = [];
        self::$currentSkipped = false;

        $failedBefore = count(self::$failures);

        try {
            $fn();
        } catch (\Throwable $e) {
            self::fail('threw ' . get_class($e) . ': ' . $e->getMessage()
                . ' (' . self::relPath($e->getFile()) . ':' . $e->getLine() . ')');
        }

        // Any PHP diagnostic raised inside Fluent Support during the case is a failure.
        if (self::$diagnostics) {
            self::fail("PHP diagnostics raised:\n  - " . implode("\n  - ", self::$diagnostics));
        }

        // A case is passed only if it neither failed nor skipped — a skip is
        // NOT executed coverage and must never inflate the passed count.
        if (count(self::$failures) === $failedBefore && !self::$currentSkipped) {
            self::$passed++;
        }

        self::$currentCase = null;
    }

    public static function fail($detail)
    {
        self::$failures[] = [
            'name'   => self::$currentCase ?: '(no case)',
            'detail' => $detail,
        ];
    }

    public static function skip($reason)
    {
        self::$currentSkipped = true;
        self::$skipped++;
        WP_CLI::log('  SKIP ' . (self::$currentCase ?: '') . ' — ' . $reason);
    }

    public static function assert($condition, $detail)
    {
        if (!$condition) {
            self::fail($detail);
        }
    }

    public static function assertSame($expected, $actual, $label)
    {
        if ($expected !== $actual) {
            self::fail($label . "\n  expected: " . var_export($expected, true)
                . "\n  actual:   " . var_export($actual, true));
        }
    }

    /**
     * Claim diagnostics that are being surfaced as an explicit KNOWN-FAILURE.
     *
     * Use this only immediately before skip(): matching production warnings are
     * removed from the automatic failure list so a known defect can remain
     * executable, while every unmatched diagnostic still fails the case.
     *
     * @param string $pattern PCRE matched against each formatted diagnostic.
     * @return array<int,string> Claimed diagnostics for inclusion in skip output.
     */
    public static function claimKnownDiagnostics($pattern)
    {
        $claimed = [];
        $remaining = [];

        foreach (self::$diagnostics as $diagnostic) {
            if (preg_match($pattern, $diagnostic)) {
                $claimed[] = $diagnostic;
            } else {
                $remaining[] = $diagnostic;
            }
        }

        self::$diagnostics = $remaining;

        return $claimed;
    }

    // -----------------------------------------------------------------
    // REST
    // -----------------------------------------------------------------

    /**
     * Dispatch a Fluent Support REST route in-process and return a rich result array.
     *
     * @param string $method  GET|POST|PUT|PATCH|DELETE
     * @param string $route   Route WITHOUT the namespace, e.g. '/tickets' or '/tickets/42'
     * @param array  $params  Query params (GET) or body params (everything else)
     * @return array{status:int,data:mixed,db_error:string,is_exception:bool,message:string}
     */
    public static function rest($method, $route, array $params = [])
    {
        global $wpdb;

        $wpdb->last_error = '';

        /*
         * WPFluent's Request merges inputs from the REST request into the
         * container's request singleton. That is correct for one HTTP request
         * per PHP process, but an in-process suite dispatches hundreds of REST
         * requests in one process. Rebind an empty request so query parameters
         * from one case cannot leak into the next.
         */
        $app = \FluentSupport\App\App::getInstance();
        $requestClass = 'FluentSupport\Framework\Http\Request\Request';
        $app->instance($requestClass, new $requestClass($app, [], []));

        $path = '/fluent-support/v2' . '/' . ltrim($route, '/');
        $path = rtrim($path, '/');

        $request = new WP_REST_Request(strtoupper($method), $path);

        if (strtoupper($method) === 'GET') {
            $request->set_query_params($params);
        } else {
            $request->set_body_params($params);
        }

        $response = rest_do_request($request);
        $data     = $response->get_data();

        // The plugin wraps runtime errors in its own envelope, which can carry a
        // 2xx status in some paths — check the payload shape, not just the code.
        $isException = is_array($data)
            && isset($data['code'])
            && in_array($data['code'], ['plugin_exception', 'internal_server_error', 'rest_error'], true);

        $message = '';
        if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
            $message = $data['message'];
        }

        return [
            'status'       => $response->get_status(),
            'data'         => $data,
            'db_error'     => (string) $wpdb->last_error,
            'is_exception' => $isException,
            'message'      => $message,
        ];
    }

    /**
     * The core smoke assertion: a route responded without any form of breakage.
     *
     * Keep all three checks — a DB error can be invisible in the HTTP status
     * on some routes, and the status alone can be invisible on others.
     */
    public static function assertHealthy(array $result, $label, array $okStatuses = [200, 201, 204])
    {
        if ($result['db_error'] !== '') {
            self::fail($label . "\n  DATABASE ERROR: " . $result['db_error']);
            return;
        }

        if ($result['is_exception']) {
            self::fail($label . "\n  PLUGIN EXCEPTION: " . $result['message']);
            return;
        }

        if (!in_array($result['status'], $okStatuses, true)) {
            self::fail($label . "\n  unexpected status " . $result['status']
                . ' (allowed: ' . implode(',', $okStatuses) . ')'
                . ($result['message'] !== '' ? "\n  message: " . $result['message'] : ''));
        }
    }

    /**
     * Drop Fluent Support's own caches so a suite tests live code paths.
     *
     * NOT OPTIONAL. A warm transient can return 200 over a completely broken
     * query, and a cached endpoint is exactly where a regression hides longest.
     * Deliberately targeted (LIKE on the plugin's transient rows) rather than
     * wp_cache_flush(), which would evict unrelated site data.
     */
    public static function clearCaches()
    {
        global $wpdb;

        $rows = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '\_transient\_fluent%'
                OR option_name LIKE '\_transient\_timeout\_fluent%'
                OR option_name LIKE '\_transient\_fs\_%'
                OR option_name LIKE '\_transient\_timeout\_fs\_%'"
        );

        foreach ($rows as $name) {
            $key = preg_replace('/^_transient_(timeout_)?/', '', $name);
            delete_transient($key);
        }

        return count($rows);
    }

    // -----------------------------------------------------------------
    // Mail safety
    // -----------------------------------------------------------------

    /**
     * Fail closed around wp_mail() and start a fresh capture buffer.
     *
     * The callback runs at the last possible priority and always returns true.
     * WordPress therefore exits before PHPMailer even when another plugin added
     * an earlier pre_wp_mail filter. Repeated calls reset captures without
     * registering duplicate callbacks.
     */
    public static function interceptMail()
    {
        self::$sentMails = [];

        if (self::$mailInterceptor === null) {
            self::$mailInterceptor = function ($preempt, $attributes) {
                self::$sentMails[] = is_array($attributes) ? $attributes : [];
                return true;
            };
        }

        if (has_filter('pre_wp_mail', self::$mailInterceptor) === false) {
            add_filter('pre_wp_mail', self::$mailInterceptor, PHP_INT_MAX, 2);
        }
    }

    /**
     * Return all messages captured since the last interceptMail() call.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function sentMails()
    {
        return self::$sentMails;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** Count queries run by $fn — for locking in N+1 fixes. */
    public static function countQueries(callable $fn)
    {
        global $wpdb;
        $before = $wpdb->num_queries;
        $fn();
        return $wpdb->num_queries - $before;
    }

    /** Unique suffix so fixtures from different runs never collide. */
    public static function uniq($prefix = 'fstest')
    {
        return $prefix . '-' . strtolower(wp_generate_password(8, false, false));
    }

    private static function relPath($file)
    {
        $pos = strpos($file, 'fluent-support');
        return $pos === false ? basename($file) : substr($file, $pos);
    }

    private static function errorLabel($errno)
    {
        $map = [
            E_WARNING           => 'Warning',
            E_NOTICE            => 'Notice',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
        ];
        return isset($map[$errno]) ? $map[$errno] : ('Error(' . $errno . ')');
    }
}
