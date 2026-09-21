<?php

namespace FluentSupport\App\Hooks\Handlers;

class DeactivationHandler
{
    public function handle()
    {
        wp_clear_scheduled_hook('fluent_support_hourly_tasks');
        wp_clear_scheduled_hook('fluent_support_daily_tasks');
        wp_clear_scheduled_hook('fluent_support_weekly_tasks');

        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('fluent_support_half_hourly', [], 'fluent-support');
        }
    }
}
