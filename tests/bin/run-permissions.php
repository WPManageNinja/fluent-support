<?php
/**
 * Permission smoke for every mutating route (POST, PUT, PATCH, DELETE) of
 * Fluent Support core + pro.
 *
 * Each executable route is dispatched once anonymously and once as a WordPress
 * subscriber. A post-permission/pre-controller safety fuse turns any permission
 * bypass into a 418 response, so a failed gate is visible without allowing the
 * handler to mutate the lab.
 */

require_once dirname(__DIR__) . '/lib/harness.php';

FsTest::boot();
FsTest::clearCaches();

$manifestFile = dirname(__DIR__) . '/smoke/mutating.manifest.php';
$manifestData = require $manifestFile;

if (!is_array($manifestData) || !isset($manifestData['expected'], $manifestData['routes'])) {
    WP_CLI::error("Permission manifest must return ['expected' => [...], 'routes' => [...]].");
}

$expectedMethods = $manifestData['expected'];
$manifest = $manifestData['routes'];

$methodCounts = array_fill_keys(array_keys($expectedMethods), 0);
$seen = [];
foreach ($manifest as $index => $entry) {
    if (
        !is_array($entry) ||
        empty($entry['method']) ||
        empty($entry['route']) ||
        !isset($entry['payload']) ||
        !is_array($entry['payload'])
    ) {
        WP_CLI::error('Invalid permission-manifest entry at index ' . $index . '.');
    }

    $method = strtoupper($entry['method']);
    if (!isset($methodCounts[$method])) {
        WP_CLI::error('Unsupported permission-manifest method: ' . $method . '.');
    }

    $key = $method . ' ' . $entry['route'];
    if (isset($seen[$key])) {
        WP_CLI::error('Duplicate permission-manifest entry: ' . $key . '.');
    }

    $seen[$key] = true;
    $methodCounts[$method]++;
}

if ($methodCounts !== $expectedMethods) {
    WP_CLI::error(sprintf(
        "Permission manifest drift: expected %s; got %s. Re-generate the manifest\n" .
        "from the route files and update 'expected' deliberately in the same commit.",
        json_encode($expectedMethods),
        json_encode($methodCounts)
    ));
}

$adminId = get_current_user_id();
$username = FsTest::uniq('fs-permission');
$testUserId = wp_insert_user([
    'user_login' => $username,
    'user_pass'  => wp_generate_password(24, true, true),
    'user_email' => $username . '@example.test',
    'role'       => 'subscriber',
]);

if (is_wp_error($testUserId)) {
    WP_CLI::error('Could not create permission-smoke subscriber: ' . $testUserId->get_error_message());
}

$deleted = false;
$cleanup = function () use ($adminId, $testUserId, &$deleted) {
    if ($deleted) {
        return;
    }

    wp_set_current_user($adminId);
    \FluentSupport\App\Modules\PermissionManager::currentUserPermissions(false);

    if (!function_exists('wp_delete_user')) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    if (get_user_by('ID', $testUserId)) {
        wp_delete_user($testUserId);
    }

    $deleted = true;
};

register_shutdown_function($cleanup);

/*
 * WordPress applies rest_dispatch_request only after permission_callback has
 * allowed a request and immediately before the controller callback. Returning
 * an error here makes a permission bypass fail without executing any handler.
 */
$controllerFuse = function ($dispatchResult, $request) {
    if (strpos($request->get_route(), '/fluent-support/v2/') === 0) {
        return new WP_Error(
            'fs_permission_gate_bypassed',
            'Permission callback allowed the controller dispatch.',
            ['status' => 418]
        );
    }

    return $dispatchResult;
};

add_filter('rest_dispatch_request', $controllerFuse, 10, 4);

/**
 * Replace manifest placeholders without ever selecting a real record.
 *
 * @param string $route
 * @return string
 */
function fsPermissionResolveRoute($route)
{
    $route = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', function ($match) {
        return '2147483647';
    }, $route);

    if (strpos($route, '{') !== false || strpos($route, '}') !== false) {
        throw new RuntimeException('Unresolved permission-smoke route token: ' . $route);
    }

    return $route;
}

/**
 * Dispatch one identity and require WordPress's canonical denial response.
 *
 * @param array<string,mixed> $entry
 * @param string              $route
 * @param int                 $userId
 * @param int                 $expectedStatus
 * @param string              $identity
 * @return void
 */
function fsPermissionAssertGatePassed(array $entry, $route, $userId, $identity)
{
    wp_set_current_user($userId);
    \FluentSupport\App\Modules\PermissionManager::currentUserPermissions(false);

    $result = FsTest::rest($entry['method'], $route, $entry['payload']);
    $code = is_array($result['data']) && isset($result['data']['code'])
        ? (string) $result['data']['code']
        : '';

    if ($result['status'] !== 418 || $code !== 'fs_permission_gate_bypassed') {
        FsTest::fail(sprintf(
            'expected %s to PASS the gate (418 fuse) for %s %s; got %d/%s%s',
            $identity,
            $entry['method'],
            $route,
            $result['status'],
            $code !== '' ? $code : '(no code)',
            $result['message'] !== '' ? "\n  message: " . $result['message'] : ''
        ));
    }
}

function fsPermissionAssertDenied(array $entry, $route, $userId, $expectedStatus, $identity)
{
    wp_set_current_user($userId);

    /*
     * PermissionManager caches one process-wide value without a user key. Force
     * a refresh after every identity switch or an earlier admin/user result can
     * leak into the next in-process REST dispatch.
     */
    \FluentSupport\App\Modules\PermissionManager::currentUserPermissions(false);

    if (get_current_user_id() !== $userId) {
        FsTest::fail(sprintf(
            '%s identity setup failed: expected user %d, got %d.',
            $identity,
            $userId,
            get_current_user_id()
        ));
        return;
    }

    $result = FsTest::rest($entry['method'], $route, $entry['payload']);
    $code = is_array($result['data']) && isset($result['data']['code'])
        ? (string) $result['data']['code']
        : '';

    if ($result['db_error'] !== '') {
        FsTest::fail('DATABASE ERROR during permission check: ' . $result['db_error']);
        return;
    }

    if ($result['is_exception']) {
        FsTest::fail('PLUGIN EXCEPTION during permission check: ' . $result['message']);
        return;
    }

    /*
     * Policies that deliberately throw (e.g. AgentPolicy::guardManageOptions,
     * FS-SEC-003) surface as "Permission Callback Error" with a specific
     * message rather than the canonical 'rest_forbidden' code. The status
     * still follows the exception code per identity (401 anonymous, 403
     * subscriber; FS-PERM-002). The manifest flags them with 'throws_denial';
     * the gate still refused, so this is a valid denial.
     */
    if (!empty($entry['throws_denial'])) {
        if ($result['status'] === $expectedStatus && $code === 'Permission Callback Error') {
            return;
        }
        FsTest::fail(sprintf(
            'expected throwing-policy denial %d/"Permission Callback Error" for %s %s %s; got %d/%s%s',
            $expectedStatus,
            $identity,
            $entry['method'],
            $route,
            $result['status'],
            $code !== '' ? $code : '(no code)',
            $result['message'] !== '' ? "\n  message: " . $result['message'] : ''
        ));
        return;
    }

    if ($result['status'] !== $expectedStatus || $code !== 'rest_forbidden') {
        $security = $result['status'] >= 200 && $result['status'] < 300
            ? 'SECURITY: '
            : '';
        FsTest::fail(sprintf(
            '%sexpected %s denial %d/rest_forbidden for %s %s; got %d/%s%s',
            $security,
            $identity,
            $expectedStatus,
            $entry['method'],
            $route,
            $result['status'],
            $code !== '' ? $code : '(no code)',
            $result['message'] !== '' ? "\n  message: " . $result['message'] : ''
        ));
    }

    if (get_current_user_id() !== $userId) {
        FsTest::fail(sprintf(
            '%s identity changed during dispatch: expected user %d, got %d.',
            $identity,
            $userId,
            get_current_user_id()
        ));
    }
}

try {
    foreach ($manifest as $entry) {
        $route = fsPermissionResolveRoute($entry['route']);

        foreach ([
            ['anonymous', 0, 401],
            ['subscriber', $testUserId, 403],
        ] as $identity) {
            FsTest::case($entry['label'] . ': ' . $identity[0], function () use ($entry, $route, $identity) {
                if (!empty($entry['skip'])) {
                    FsTest::skip($entry['skip']);
                    return;
                }

                if (isset($entry['requires_constant']) && !defined($entry['requires_constant'])) {
                    FsTest::skip('sibling providing ' . $entry['requires_constant'] . ' is not active');
                    return;
                }

                /*
                 * Routes whose policy allows any logged-in user by design
                 * (PortalPolicy): the subscriber leg must PASS the gate and be
                 * stopped by the 418 fuse — locking in the portal contract —
                 * while the anonymous leg still asserts a canonical denial.
                 */
                if (!empty($entry['subscriber_allowed']) && $identity[0] === 'subscriber') {
                    fsPermissionAssertGatePassed($entry, $route, $identity[1], $identity[0]);
                    return;
                }

                fsPermissionAssertDenied(
                    $entry,
                    $route,
                    $identity[1],
                    $identity[2],
                    $identity[0]
                );
            });
        }
    }
} finally {
    remove_filter('rest_dispatch_request', $controllerFuse, 10);
    $cleanup();
}

FsTest::finish('PERMISSIONS');
