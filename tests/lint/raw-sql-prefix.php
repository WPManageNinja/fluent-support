<?php
/**
 * S0 lint — raw SQL must not contain unprefixed table-qualified identifiers.
 *
 * WHY THIS EXISTS
 * ---------------
 * In WPFluent, `where('fs_tickets.status', ...)`, `select('fs_x.col')`,
 * `join()` and `groupBy()` all pass through BaseGrammar::wrap(), which prepends
 * $wpdb->prefix. `raw()` / `selectRaw()` / `whereRaw()` DO NOT — the string is
 * handed to the driver verbatim. So this:
 *
 *     ->selectRaw("SUM(CASE WHEN fs_tickets.status = 'open' ...)")
 *
 * produces `fs_tickets.status` while every other identifier in the same query
 * is correctly `wp_fs_tickets`, and MySQL throws
 * "Unknown column 'fs_tickets.status' in 'field list'".
 *
 * That bug class shipped on FluentCRM (2026-07-20) and killed two endpoints
 * for a week. php -l, PHPCS and code review all passed it. This rule is the
 * cheap guard: it fails at author time, in milliseconds, and explains the fix.
 *
 * THE FIX IT WANTS
 *     global $wpdb;
 *     $t = $wpdb->prefix . 'fs_tickets';
 *     ->selectRaw("SUM(CASE WHEN `{$t}`.`status` = 'open' ...)")
 *
 * or better — keep plain qualified columns in select() and put only the
 * aggregate in selectRaw().
 *
 * Usage:  php tests/lint/raw-sql-prefix.php            # core + pro sibling
 *         php tests/lint/raw-sql-prefix.php <dir>      # explicit target (self-test)
 * Exit:   0 clean, 1 violations found
 */

$root = is_dir(__DIR__ . '/../../app') ? dirname(__DIR__, 2) : getcwd();

// Default scan targets: the core plugin's app tree plus every registered
// sibling's. A registered-but-absent sibling reports SKIP with its name —
// silence would read as covered. An explicit path argument overrides all of
// this, which is what the self-test uses to prove the rule still fires:
//   php tests/lint/raw-sql-prefix.php tests/lint/fixtures   ->  must exit 1
$scanDirs = ['app'];
$config = is_file(__DIR__ . '/../suite.config.php') ? require __DIR__ . '/../suite.config.php' : [];
$skips = [];
if (!isset($argv[1]) || $argv[1] === '') {
    foreach (isset($config['siblings']) ? $config['siblings'] : [] as $slug => $sibling) {
        $siblingApp = dirname($root) . '/' . $slug . '/app';
        if (is_dir($siblingApp)) {
            $scanDirs[] = $siblingApp;
        } else {
            $skips[] = $slug;
        }
    }
} else {
    $scanDirs = [rtrim($argv[1], '/')];
}

$rawCallPattern = '/\b(?:selectRaw|whereRaw|havingRaw|orderByRaw|groupByRaw|orWhereRaw|raw)\s*\(/i';

// A table-qualified Fluent Support identifier: fs_something.
$qualifiedPattern = '/\bfs_[a-z0-9_]+\s*\./i';

// Evidence that the prefix was resolved: an interpolated variable or a call.
$prefixEvidence = '/\$wpdb->prefix|getTablePrefix\s*\(|\{\$[a-zA-Z_][a-zA-Z0-9_]*\}|\$[a-zA-Z_][a-zA-Z0-9_]*\s*\./';

$violations = [];
$scanned = 0;

$iterate = function ($dir) {
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $path = $file->getPathname();
            // Never lint vendored or scoped-vendor trees.
            if (strpos($path, '/vendor/') !== false || strpos($path, 'scoped-vendor') !== false) {
                continue;
            }
            $out[] = $path;
        }
    }
    return $out;
};

foreach ($scanDirs as $dir) {
    $target = ($dir !== '' && $dir[0] === '/') ? $dir : $root . '/' . $dir;
    foreach ($iterate($target) as $path) {
        $scanned++;
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        foreach ($lines as $i => $line) {
            if (!preg_match_all($rawCallPattern, $line, $rawCalls, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            // Only literals INSIDE each raw call's parentheses count. A
            // grammar-wrapped select('fs_x.col') can share a line with an
            // innocent ->raw('COUNT(*)') — correct code that line-granularity
            // scanning would report as a violation.
            $rawSpans = '';
            foreach ($rawCalls[0] as $rawCall) {
                $openParen = strpos($line, '(', $rawCall[1]);
                if ($openParen === false) {
                    continue;
                }
                $depth = 0;
                $closeParen = strlen($line);
                for ($p = $openParen, $len = strlen($line); $p < $len; $p++) {
                    if ($line[$p] === '(') {
                        $depth++;
                    } elseif ($line[$p] === ')' && --$depth === 0) {
                        $closeParen = $p;
                        break;
                    }
                }
                $rawSpans .= substr($line, $openParen, $closeParen - $openParen) . ' ';
            }

            // Isolate the quoted string literals inside those spans.
            if (!preg_match_all(
                '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/',
                $rawSpans,
                $m,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            )) {
                continue;
            }

            foreach ($m as $match) {
                $doubleQuoted = isset($match[1]) ? $match[1][0] : '';
                $singleQuoted = isset($match[2]) ? $match[2][0] : '';
                $literal = $singleQuoted !== '' ? $singleQuoted : $doubleQuoted;
                $literalOffset = $match[0][1];

                if (!preg_match($qualifiedPattern, $literal, $hit)) {
                    continue;
                }

                // Prefix interpolated inside the literal: "{$t}.col".
                if (preg_match($prefixEvidence, $literal)) {
                    continue;
                }

                // Prefix concatenated immediately before the literal:
                //   $wpdb->prefix . 'fs_x.col'   or   $t . 'fs_x.col'
                // The variable sits OUTSIDE the quotes, so the literal alone
                // looks bare. Checking only the literal would report these as
                // violations when they are correct.
                $before = substr($rawSpans, 0, $literalOffset);
                if (preg_match(
                    '/(?:\$wpdb->prefix|getTablePrefix\s*\(\s*\)|\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*$/',
                    $before
                )) {
                    continue;
                }

                $violations[] = [
                    'file' => str_replace(dirname($root) . '/', '', $path),
                    'line' => $i + 1,
                    'ident' => trim($hit[0]),
                    'code' => trim($line),
                ];
            }
        }
    }
}

echo "raw-sql-prefix: scanned {$scanned} PHP files\n";
foreach ($skips as $slug) {
    echo "SKIP — sibling '{$slug}' is not present on this machine; its tree was NOT scanned.\n";
}

if (!$violations) {
    echo "OK — no unprefixed table-qualified identifiers inside raw SQL.\n";
    exit(0);
}

echo "\nFAIL — " . count($violations) . " violation(s):\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    unprefixed identifier: {$v['ident']}\n";
    echo "    " . (strlen($v['code']) > 140 ? substr($v['code'], 0, 137) . '...' : $v['code']) . "\n\n";
}
echo "Raw SQL bypasses the query grammar. Resolve the prefix explicitly:\n";
echo "  global \$wpdb; \$t = \$wpdb->prefix . 'fs_tickets';\n";
echo "  ->selectRaw(\"... `{\$t}`.`status` ...\")\n";
exit(1);
