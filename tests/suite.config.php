<?php
/**
 * Per-plugin test suite configuration for the Fluent Support family.
 *
 * This is the ONLY file that should contain plugin-specific names. Everything
 * else in tests/ reads from here, so porting the suite to another WPFluent
 * plugin means editing this file and one regex in the lint.
 */

return [
    // Plugin identity ------------------------------------------------------
    'plugin_slug'      => 'fluent-support',

    // Substring used to decide whether a PHP notice/warning belongs to US.
    // Deliberately matches fluent-support-pro too — pro diagnostics are ours.
    'plugin_dir_hint'  => 'plugins/fluent-support',

    // REST -----------------------------------------------------------------
    'rest_namespace'   => 'fluent-support/v2',
    'routes_file'      => 'app/Http/Routes/api.php',

    // Database -------------------------------------------------------------
    // The plugin's own table prefix, WITHOUT the WordPress prefix.
    // Also the regex in lint/raw-sql-prefix.php.
    'table_prefix'     => 'fs_',

    // Counted before and after every run; a change is a hard failure.
    'protected_tables' => ['fs_tickets', 'fs_conversations', 'fs_persons'],

    // Framework wiring -----------------------------------------------------
    // No global app function exists (unlike FluentCrm()); the facade is the
    // supported entry point: \FluentSupport\App\App::getInstance().
    'app_bootstrap'    => ['\\FluentSupport\\App\\App', 'getInstance'],
    'request_class'    => 'FluentSupport\\Framework\\Http\\Request\\Request',

    // Any class that only exists when the plugin is loaded — proves the
    // harness booted against the right install before anything else runs.
    'sentinel_class'   => 'FluentSupport\\App\\Models\\Ticket',

    // Caches ---------------------------------------------------------------
    // Transient LIKE patterns flushed before every suite. A warm transient
    // can return 200 over completely broken SQL. No object-cache group —
    // fluent-support has none (verified 2026-08-08).
    'cache_groups'     => [],

    // Optional seams -------------------------------------------------------
    // fluent-support has no loopback-intercept filter (verified 2026-08-08).
    'loopback_filter'  => '',

    // Product family -------------------------------------------------------
    // The suite's unit of operation. 'unrelated' classifications are recorded
    // so the boundary is never re-litigated: fluent-connect is a FluentCRM/
    // ThriveCart product, NOT part of this family (verified 2026-08-08).
    'siblings'         => [
        'fluent-support-pro' => [
            'kind'        => 'pro',
            'constant'    => 'FLUENTSUPPORTPRO',
            'namespace'   => 'FluentSupportPro',
            'routes_file' => 'app/Http/routes.php', // non-standard path
            'path_hint'   => '../fluent-support-pro',
        ],
    ],
];
