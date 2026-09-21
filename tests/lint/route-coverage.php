<?php
/**
 * Route-coverage lint.
 *
 * Re-parses the WPFluent route files (core api.php + each sibling's routes
 * file) with its own tiny parser and diffs the result against the committed
 * smoke manifests:
 *
 *   - every GET route must appear in tests/smoke/routes.manifest.php
 *   - every POST/PUT/PATCH/DELETE route must appear in
 *     tests/smoke/mutating.manifest.php
 *
 * A route added to production without a manifest entry fails the gate, so the
 * manifests can be trusted as the covered-surface map. Runs on the host; no
 * WordPress needed.
 *
 * Usage:
 *   php tests/lint/route-coverage.php            # diff (exit 1 on drift)
 *   php tests/lint/route-coverage.php --list     # print the parsed census
 */

$testsDir = dirname(__DIR__);
$config = require $testsDir . '/suite.config.php';
$root = dirname($testsDir);

$listOnly = in_array('--list', $argv, true);

/**
 * Parse one WPFluent route file into [METHOD => [path, ...]].
 *
 * Tracks `$router->prefix('x')->...->group(function ($router) {` nesting via
 * brace depth so nested prefixes compose. Route calls are
 * `$router->get|post|put|patch|delete('path', ...)`.
 *
 * @param string $file
 * @return array<string,array<int,string>>
 */
function fsParseRoutes($file)
{
    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "Cannot read {$file}\n");
        exit(2);
    }

    // Strip comments so commented-out routes are not counted.
    $clean = '';
    foreach (token_get_all($source) as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $clean .= $token[1];
        } else {
            $clean .= $token;
        }
    }

    $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => [], 'ANY' => []];
    $prefixStack = []; // each: ['prefix' => string, 'depth' => int]
    $depth = 0;
    $length = strlen($clean);
    $offset = 0;

    while ($offset < $length) {
        $char = $clean[$offset];

        // Skip string literals so braces inside them don't affect depth.
        if ($char === "'" || $char === '"') {
            $quote = $char;
            $offset++;
            while ($offset < $length) {
                if ($clean[$offset] === '\\') {
                    $offset += 2;
                    continue;
                }
                if ($clean[$offset] === $quote) {
                    break;
                }
                $offset++;
            }
            $offset++;
            continue;
        }

        if ($char === '{') {
            $depth++;
            $offset++;
            continue;
        }

        if ($char === '}') {
            $depth--;
            while ($prefixStack && end($prefixStack)['depth'] > $depth) {
                array_pop($prefixStack);
            }
            $offset++;
            continue;
        }

        if ($char === '$' && preg_match(
            '/\G\$router\s*->\s*prefix\s*\(\s*([\'"])([^\'"]*)\1\s*\)/A',
            $clean,
            $m,
            0,
            $offset
        )) {
            // The group's { follows later in this statement; record the depth
            // the group body will open AT (current depth), popped when its
            // closing } returns below it.
            $prefixStack[] = ['prefix' => $m[2], 'depth' => $depth + 1];
            $offset += strlen($m[0]);
            continue;
        }

        if ($char === '$' && preg_match(
            '/\G\$router\s*->\s*(get|post|put|patch|delete|any)\s*\(\s*([\'"])([^\'"]*)\2/A',
            $clean,
            $m,
            0,
            $offset
        )) {
            $method = strtoupper($m[1]);
            $segments = [];
            foreach ($prefixStack as $entry) {
                if ($entry['prefix'] !== '') {
                    $segments[] = trim($entry['prefix'], '/');
                }
            }
            $path = trim($m[3], '/');
            if ($path !== '') {
                $segments[] = $path;
            }
            $routes[$method][] = '/' . implode('/', $segments);
            $offset += strlen($m[0]);
            continue;
        }

        $offset++;
    }

    return $routes;
}

/**
 * Normalize a route path: collapse duplicate slashes, strip trailing slash,
 * and canonicalize `{placeholder}` names to `{}` so a manifest may name a
 * token differently from the route file.
 *
 * @param string $path
 * @return string
 */
function fsNormalizeRoute($path)
{
    $path = '/' . trim(preg_replace('#/+#', '/', $path), '/');
    return preg_replace('/\{[^}]*\}/', '{}', $path);
}

// ---- census from route files -------------------------------------------

$sources = [
    ['label' => $config['plugin_slug'], 'file' => $root . '/' . 'app/Http/Routes/api.php'],
];
foreach ($config['siblings'] as $slug => $sibling) {
    $file = $root . '/' . $sibling['path_hint'] . '/' . $sibling['routes_file'];
    if (!is_file($file)) {
        fwrite(STDOUT, "SKIP — sibling '{$slug}' is not present at {$sibling['path_hint']}\n");
        continue;
    }
    $sources[] = ['label' => $slug, 'file' => $file];
}

$census = ['GET' => [], 'MUTATING' => []];
foreach ($sources as $sourceEntry) {
    $parsed = fsParseRoutes($sourceEntry['file']);
    foreach ($parsed as $method => $paths) {
        foreach ($paths as $path) {
            // ->any() routes (webhook listeners) can mutate, so they belong in
            // the mutating manifest — usually as a documented public skip. The
            // manifest exercises them once, as POST, so census them as POST.
            $censusMethod = $method === 'ANY' ? 'POST' : $method;
            $key = ($censusMethod === 'GET' ? '' : $censusMethod . ' ') . fsNormalizeRoute($path);
            $bucket = $censusMethod === 'GET' ? 'GET' : 'MUTATING';
            $census[$bucket][$key] = $sourceEntry['label'];
        }
    }
}

if ($listOnly) {
    foreach ($census as $bucket => $entries) {
        ksort($entries);
        fwrite(STDOUT, "== {$bucket} (" . count($entries) . ")\n");
        foreach ($entries as $key => $label) {
            fwrite(STDOUT, "  {$key}  [{$label}]\n");
        }
    }
    exit(0);
}

// ---- census from manifests ---------------------------------------------

$smokeManifestFile = $testsDir . '/smoke/routes.manifest.php';
$mutatingManifestFile = $testsDir . '/smoke/mutating.manifest.php';

foreach ([$smokeManifestFile, $mutatingManifestFile] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing manifest: {$file}\n");
        exit(1);
    }
}

$covered = ['GET' => [], 'MUTATING' => []];
foreach (require $smokeManifestFile as $entry) {
    $covered['GET'][fsNormalizeRoute($entry['route'])] = true;
}
$mutatingData = require $mutatingManifestFile;
foreach ($mutatingData['routes'] as $entry) {
    $covered['MUTATING'][strtoupper($entry['method']) . ' ' . fsNormalizeRoute($entry['route'])] = true;
}

// ---- diff ---------------------------------------------------------------

$failures = 0;
foreach ($census as $bucket => $entries) {
    $manifestName = $bucket === 'GET' ? 'routes.manifest.php' : 'mutating.manifest.php';
    foreach ($entries as $key => $label) {
        if (!isset($covered[$bucket][$key])) {
            fwrite(STDERR, "UNCOVERED {$bucket} route ({$label}): {$key} — add it to {$manifestName}\n");
            $failures++;
        }
    }
}

// Reverse direction: manifest entries whose route no longer exists.
foreach ($covered['GET'] as $key => $unused) {
    if (!isset($census['GET'][$key])) {
        fwrite(STDERR, "STALE manifest entry (routes.manifest.php): {$key} — route no longer in source\n");
        $failures++;
    }
}
foreach ($covered['MUTATING'] as $key => $unused) {
    if (!isset($census['MUTATING'][$key])) {
        fwrite(STDERR, "STALE manifest entry (mutating.manifest.php): {$key} — route no longer in source\n");
        $failures++;
    }
}

if ($failures) {
    fwrite(STDERR, "\nroute-coverage: {$failures} drift issue(s).\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "route-coverage: clean — %d GET and %d mutating routes all covered.\n",
    count($census['GET']),
    count($census['MUTATING'])
));
exit(0);
