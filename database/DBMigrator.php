<?php

namespace FluentSupport\Database;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

use FluentSupport\Database\Migrations\ActivityMigrator;
use FluentSupport\Database\Migrations\MailBoxMigrator;
use FluentSupport\Database\Migrations\MetaMigrator;
use FluentSupport\Database\Migrations\PersonsMigrator;
use FluentSupport\Database\Migrations\TicketsMigrator;
use FluentSupport\Database\Migrations\ConversationsMigrator;
use FluentSupport\Database\Migrations\AttachmentsMigrator;
use FluentSupport\Database\Migrations\TaggablesMigrator;
use FluentSupport\Database\Migrations\TagRelationsMigrator;
use FluentSupport\Database\Migrations\DataMetrixMigrator;
use FluentSupport\Database\Migrations\ProductsMigrator;

class DBMigrator
{
    protected static $migrators = [
        ProductsMigrator::class,
        PersonsMigrator::class,
        TicketsMigrator::class,
        ConversationsMigrator::class,
        AttachmentsMigrator::class,
        TaggablesMigrator::class,
        TagRelationsMigrator::class,
        DataMetrixMigrator::class,
        MetaMigrator::class,
        MailBoxMigrator::class,
        ActivityMigrator::class,
    ];

    public static function run($network_wide = false)
    {
        global $wpdb;

        if ($network_wide) {
            // Retrieve all site IDs from this network (WordPress >= 4.6 provides easy to use functions for that).
            if (function_exists('get_sites') && function_exists('get_current_network_id')) {
                $site_ids = get_sites(array(
                    'fields' => 'ids',
                    'network_id' => get_current_network_id()
                ));
            } else {
                $site_ids = $wpdb->get_col(
                    "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;"
                );
            }
            // Install the plugin for all these sites.
            foreach ($site_ids as $site_id) {
                switch_to_blog($site_id);
                self::migrate();
                restore_current_blog();
            }
        } else {
            self::migrate();
        }
    }

    public static function migrate()
    {
        foreach (static::$migrators as $class) {
            $class::migrate();
        }
    }

    // Version-gated re-run for in-place updates that never reactivate; locked so concurrent requests don't race.
    public static function maybeMigrateDBChanges()
    {
        $currentDbVersion = get_option('fluent_support_db_version');

        if ($currentDbVersion && !version_compare($currentDbVersion, FLUENT_SUPPORT_DB_VERSION, '<')) {
            return;
        }

        if (!self::acquireMigrationLock()) {
            return;
        }

        self::migrate();
        update_option('fluent_support_db_version', FLUENT_SUPPORT_DB_VERSION, 'no');

        self::releaseMigrationLock();
    }

    // MySQL advisory lock (zero-wait); fails open on NULL so a locking hiccup never blocks upgrades.
    private static function acquireMigrationLock()
    {
        global $wpdb;

        $acquired = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 0)", self::getMigrationLockName()));

        return $acquired === null || (string) $acquired === '1';
    }

    private static function releaseMigrationLock()
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", self::getMigrationLockName()));
    }

    private static function getMigrationLockName()
    {
        global $wpdb;

        // GET_LOCK names are server-wide; scope to this site's DB and prefix.
        $dbName = defined('DB_NAME') ? DB_NAME : '';

        return 'fs_db_migration_' . md5($dbName . '|' . $wpdb->prefix);
    }
}
