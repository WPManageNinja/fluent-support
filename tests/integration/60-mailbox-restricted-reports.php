<?php
/**
 * Mailbox restrictions in stats and reports.
 *
 * A viewer whose agent record carries `agent_restrictions` for a mailbox must
 * not see that mailbox's tickets or replies in any aggregate. Fixtures put one
 * active ticket (with one agent reply) in an allowed mailbox and one in a
 * restricted mailbox, both worked by the same fixture agent, and every
 * assertion is anchored on that agent's own rows so unrelated site data can
 * never make a scope look applied.
 *
 * Restriction is resolved from the CURRENT user: Helper::getAgentByUserId()
 * -> Agent.user_id -> person_meta. The viewer therefore needs a real WP user
 * linked to a fixture agent. The user and the meta row are created here and
 * removed in this file's own finally — neither is factory-tracked.
 *
 * Pro cases skip by name when fluent-support-pro is not active.
 */

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Modules\Reporting\Reporting;
use FluentSupport\App\Modules\StatModule;

return function () {

    $adminId = get_current_user_id();
    $proActive = defined('FLUENTSUPPORTPRO')
        && class_exists('\FluentSupportPro\App\Modules\Reporting\AdvancedReportService')
        && class_exists('\FluentSupportPro\App\Modules\Reporting\SnapshotService');

    // ---- fixtures ---------------------------------------------------------

    $allowedBox = FsFactory::mailbox();
    $restrictedBox = FsFactory::mailbox();
    $customer = FsFactory::customer();

    // The agent whose activity is measured. Its rows are the only ones the
    // assertions look at, so every expected value below is exact.
    $worker = FsFactory::agent();

    // The agent who views the reports, restricted from $restrictedBox.
    $viewer = FsFactory::agent();

    $login = FsTest::uniq('fs-restricted-viewer');
    $viewerUserId = wp_insert_user([
        'user_login' => $login,
        'user_pass'  => wp_generate_password(24, true, true),
        'user_email' => $login . '@example.test',
        'role'       => 'subscriber',
    ]);

    if (is_wp_error($viewerUserId)) {
        throw new RuntimeException('Could not create viewer user: ' . $viewerUserId->get_error_message());
    }

    $asViewer = function () use ($viewerUserId) {
        wp_set_current_user($viewerUserId);
        PermissionManager::currentUserPermissions(false);
    };

    $asNobody = function () {
        wp_set_current_user(0);
        PermissionManager::currentUserPermissions(false);
    };

    $asAdmin = function () use ($adminId) {
        wp_set_current_user($adminId);
        PermissionManager::currentUserPermissions(false);
    };

    try {
        $viewer->user_id = $viewerUserId;
        $viewer->save();
        $viewer->updateMeta('agent_restrictions', [
            'businessBoxRestrictions' => true,
            'restrictedBusinessBoxes' => [(int) $restrictedBox->id],
        ]);

        $allowedTicket = FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $allowedBox->id,
            'agent_id'    => $worker->id,
            'status'      => 'active',
            'title'       => 'Restricted-report allowed',
        ]);
        $restrictedTicket = FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $restrictedBox->id,
            'agent_id'    => $worker->id,
            'status'      => 'active',
            'title'       => 'Restricted-report restricted',
        ]);

        foreach ([$allowedTicket, $restrictedTicket] as $ticket) {
            FsFactory::conversation($ticket, [
                'person_id'         => $worker->id,
                'conversation_type' => 'response',
            ]);
        }

        $today = current_time('Y-m-d');

        // Sanity: the viewer really is restricted, otherwise every case below
        // would pass vacuously against an unscoped query.
        FsTest::case('restricted viewer: fixture agent resolves its mailbox restriction', function () use ($asViewer, $asAdmin, $restrictedBox) {
            $asViewer();
            try {
                FsTest::assertSame(
                    [(int) $restrictedBox->id],
                    PermissionManager::getRestrictedMailboxIds(),
                    'viewer restriction resolves from the current user'
                );
            } finally {
                $asAdmin();
            }
        });

        // ---- core: StatModule::getOverAllStats() ---------------------------

        FsTest::case('restricted viewer: StatModule::getOverAllStats excludes the restricted mailbox', function () use ($asNobody, $asViewer, $asAdmin, $restrictedBox) {
            // Baseline with no user: no agent, no restriction, site-wide.
            $asNobody();
            $unscoped = StatModule::getOverAllStats();

            // Exactly what the restricted mailbox contributes to each figure.
            $activeInBox = Ticket::where('mailbox_id', $restrictedBox->id)->where('status', 'active')->count();
            $responsesInBox = \FluentSupport\App\Models\Conversation::where('conversation_type', 'response')
                ->whereHas('ticket', function ($q) use ($restrictedBox) {
                    $q->where('mailbox_id', $restrictedBox->id);
                })->count();

            $asViewer();
            try {
                $scoped = StatModule::getOverAllStats();
            } finally {
                $asAdmin();
            }

            FsTest::assertSame(1, $activeInBox, 'fixture: one active ticket in the restricted mailbox');
            FsTest::assertSame(1, $responsesInBox, 'fixture: one response in the restricted mailbox');

            FsTest::assertSame(
                $unscoped['active_tickets']['count'] - $activeInBox,
                $scoped['active_tickets']['count'],
                'active_tickets drops the restricted mailbox'
            );
            FsTest::assertSame(
                $unscoped['responses']['count'] - $responsesInBox,
                $scoped['responses']['count'],
                'responses drops replies on restricted-mailbox tickets'
            );
        });

        // ---- core: Reporting::agentSummary() -------------------------------

        FsTest::case('restricted viewer: Reporting::agentSummary excludes the restricted mailbox', function () use ($asViewer, $asAdmin, $worker) {
            $asViewer();
            try {
                $agents = (new Reporting())->agentSummary();
            } finally {
                $asAdmin();
            }

            $row = null;
            foreach ($agents as $agent) {
                if ((int) $agent->id === (int) $worker->id) {
                    $row = $agent;
                    break;
                }
            }

            FsTest::assert($row !== null, 'worker agent is listed in the summary');
            if (!$row) {
                return;
            }

            // The worker has one open ticket + one reply per mailbox; only the
            // allowed mailbox's pair may be counted.
            FsTest::assertSame(1, (int) $row->stats['opens'], 'opens counts only the allowed-mailbox ticket');
            FsTest::assertSame(1, (int) $row->stats['responses'], 'responses counts only the allowed-mailbox reply');
            FsTest::assertSame(1, (int) $row->stats['interactions'], 'interactions counts only the allowed-mailbox ticket');
        });

        // ---- pro: AdvancedReportService::getPerformanceOverview() ----------

        FsTest::case('pro restricted viewer: getPerformanceOverview excludes the restricted mailbox', function () use ($proActive, $asViewer, $asAdmin, $worker, $today) {
            if (!$proActive) {
                FsTest::skip('fluent-support-pro is not active');
                return;
            }

            $asViewer();
            try {
                $overview = \FluentSupportPro\App\Modules\Reporting\AdvancedReportService::getPerformanceOverview(
                    $today, $today, [(int) $worker->id]
                );
            } finally {
                $asAdmin();
            }

            $agentRow = null;
            foreach ($overview['agents'] as $candidate) {
                if ((int) $candidate['id'] === (int) $worker->id) {
                    $agentRow = $candidate;
                    break;
                }
            }

            FsTest::assert($agentRow !== null, 'worker agent is listed in the performance overview');
            if ($agentRow) {
                FsTest::assertSame(1, (int) $agentRow['responses'], 'agent responses counts only the allowed-mailbox reply');
            }

            FsTest::assertSame(1, (int) $overview['totals']['open'], 'totals.open counts only the allowed-mailbox ticket');
            FsTest::assertSame(1, (int) $overview['tickets']['total'], 'ticket rows list only the allowed-mailbox ticket');
        });

        // ---- pro: SnapshotService::takeSnapshot() under a restricted user --

        FsTest::case('pro: takeSnapshot stores site-wide figures even when a restricted user runs cron', function () use ($proActive, $asNobody, $asViewer, $asAdmin, $viewerUserId) {
            if (!$proActive) {
                FsTest::skip('fluent-support-pro is not active');
                return;
            }

            $snapshotService = '\FluentSupportPro\App\Modules\Reporting\SnapshotService';
            $snapshotModel = '\FluentSupportPro\App\Models\ReportSnapshot';

            // What an unscoped capture must contain, over the same cumulative
            // range the snapshot uses.
            $asNobody();
            $expected = \FluentSupportPro\App\Modules\Reporting\AdvancedReportService::getTicketOverview(
                $snapshotService::CUMULATIVE_START_DATE,
                current_time('Y-m-d')
            );

            $maxIdBefore = (int) $snapshotModel::max('id');

            // ALTERNATE_WP_CRON, a logged-in hit on wp-cron.php and
            // `wp cron event run --user` all run the hook with a user set.
            $asViewer();
            try {
                $snapshotService::takeSnapshot();
                $userAfter = get_current_user_id();
            } finally {
                $asAdmin();
            }

            try {
                $stored = $snapshotModel::ofType('ticket_overview')
                    ->where('id', '>', $maxIdBefore)
                    ->orderBy('id', 'desc')
                    ->first();

                FsTest::assert($stored !== null, 'a ticket_overview snapshot row was written');
                if (!$stored) {
                    return;
                }

                FsTest::assertSame($expected['tickets'], $stored->data['tickets'], 'snapshot ticket counts are site-wide, not the viewer\'s scope');
                FsTest::assertSame($expected['workload'], $stored->data['workload'], 'snapshot workload is site-wide, not the viewer\'s scope');
                FsTest::assertSame($viewerUserId, $userAfter, 'the caller\'s user is restored after capture');
            } finally {
                // Snapshot rows are not factory-tracked.
                $snapshotModel::where('id', '>', $maxIdBefore)->delete();
            }
        });
    } finally {
        $asAdmin();

        Meta::where('object_id', $viewer->id)
            ->where('object_type', 'person_meta')
            ->where('key', 'agent_restrictions')
            ->delete();

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        if (get_user_by('ID', $viewerUserId)) {
            wp_delete_user($viewerUserId);
        }
    }
};
