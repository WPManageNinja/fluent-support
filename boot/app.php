<?php

defined('ABSPATH') or die;

use FluentSupport\Framework\Foundation\Application;
use FluentSupport\App\Hooks\Handlers\ActivationHandler;
use FluentSupport\App\Hooks\Handlers\DeactivationHandler;

return function ($file) {

    require_once FLUENT_SUPPORT_PLUGIN_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

    register_activation_hook($file, function () {
        (new ActivationHandler)->handle();
    });

    register_deactivation_hook($file, function () {
        (new DeactivationHandler)->handle();
    });

    add_action('plugins_loaded', function () use ($file) {
        $application = new Application($file);

        \FluentSupport\Database\DBMigrator::maybeMigrateDBChanges();

        do_action('fluent_support_loaded', $application);
        do_action('fluent_support_addons_loaded', $application);

        // add_action('init', function () {
        //     load_plugin_textdomain('fluent-support', false, 'fluent-support/language/');
        // });

        add_action('fluent_support/admin_app_loaded', function () {
            if (!wp_next_scheduled('fluent_support_hourly_tasks')) {
                wp_schedule_event(time(), 'hourly', 'fluent_support_hourly_tasks');
            }

            if (!wp_next_scheduled('fluent_support_daily_tasks')) {
                wp_schedule_event(time(), 'daily', 'fluent_support_daily_tasks');
            }

            if (!wp_next_scheduled('fluent_support_weekly_tasks')) {
                wp_schedule_event(time(), 'weekly', 'fluent_support_weekly_tasks');
            }

            global $wpdb;
            $table = esc_sql($wpdb->prefix . 'fs_tickets');
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); no user input.
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM `{$table}` LIKE 'serial_number'");
            if ($column_exists) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $has_null = $wpdb->get_var("SELECT id FROM `{$table}` WHERE `serial_number` IS NULL LIMIT 1");
                if ($has_null) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $wpdb->query("UPDATE `{$table}` SET `serial_number` = `id` WHERE `serial_number` IS NULL");
                }
            }

            /*
             * The below schedule is powered by Action Scheduler by WooCommerce
             * It will run every 30 minutes. Only schedule it while something is
             * actually listening (currently only Fluent Support Pro's storage
             * token refresh / email piping retry) — otherwise it fires with no
             * callback to run. Any future free-tier listener added via
             * add_action('fluent_support_half_hourly', ...) makes this schedule
             * itself automatically, with no change needed here.
             */
            $hasListener = has_action('fluent_support_half_hourly');
            $nextRun     = as_next_scheduled_action('fluent_support_half_hourly', [], 'fluent-support');

            if ($hasListener) {
                if (false === $nextRun) {
                    as_schedule_recurring_action(time(), 1800, 'fluent_support_half_hourly', [], 'fluent-support', true);
                }
            } elseif (false !== $nextRun) {
                as_unschedule_all_actions('fluent_support_half_hourly', [], 'fluent-support');
            }
        });

    });
};
