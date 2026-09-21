<?php
/**
 * Customer portal: a logged-in customer sees their own tickets and nothing else.
 *
 * The portal resolves the customer from the current WP user (id/email), so
 * these cases create real WP users, link them to factory customers, and switch
 * identity with wp_set_current_user() — dropping the permission cache each
 * time, exactly as the runner does between files.
 *
 * Portal ticket routes address tickets by their PUBLIC identifier
 * (serial_number), not the primary key. Asserting with the primary key here
 * would silently test the wrong row.
 */

use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;

return function () {

    $marker = FsFactory::marker();
    $adminId = get_current_user_id();

    /**
     * Become a WP user and drop the cached permission set.
     *
     * @param int $userId
     * @return void
     */
    $becomeUser = function ($userId) {
        wp_set_current_user($userId);
        PermissionManager::currentUserPermissions(false);
    };

    /**
     * Create a WP user whose email carries the fixture marker.
     *
     * @param string $slug
     * @return int
     */
    $makeWpUser = function ($slug) use ($marker) {
        $login = substr($marker . '-' . $slug, 0, 60);
        $userId = wp_insert_user([
            'user_login' => $login,
            'user_email' => $marker . '-' . $slug . '@example.invalid',
            'user_pass'  => wp_generate_password(20),
            'role'       => 'subscriber',
        ]);

        if (is_wp_error($userId)) {
            throw new RuntimeException('Could not create portal WP user: ' . $userId->get_error_message());
        }

        return (int) $userId;
    };

    $ownerUserId = $makeWpUser('portal-owner');
    $intruderUserId = $makeWpUser('portal-intruder');

    try {
        $owner = FsFactory::customer([
            'email'   => $marker . '-portal-owner@example.invalid',
            'user_id' => $ownerUserId,
        ]);
        $intruder = FsFactory::customer([
            'email'   => $marker . '-portal-intruder@example.invalid',
            'user_id' => $intruderUserId,
        ]);

        // Serial numbers default to the primary key, which would make a
        // lookup by id indistinguishable from a lookup by public identifier.
        // Forcing them apart is what gives these cases their meaning.
        $serialBase = 900000 + wp_rand(1, 80000);

        $ownerTicket = FsFactory::ticket([
            'customer_id'   => $owner->id,
            'title'         => 'Portal owner ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase,
        ]);
        $intruderTicket = FsFactory::ticket([
            'customer_id'   => $intruder->id,
            'title'         => 'Portal intruder ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase + 1,
        ]);

        $ownerSerial = (int) Ticket::find($ownerTicket->id)->serial_number;

        FsTest::case('portal_fixtures_have_a_serial_number_distinct_from_the_row_id', function () use (
            $ownerTicket, $ownerSerial
        ) {
            FsTest::assert($ownerSerial > 0 && $ownerSerial !== (int) $ownerTicket->id,
                'the portal fixture must be addressable by a serial that is not its id (id '
                . $ownerTicket->id . ', serial ' . $ownerSerial . ')');
        });

        FsTest::case('portal_lists_only_the_logged_in_customers_tickets', function () use (
            $becomeUser, $ownerUserId, $ownerTicket, $intruderTicket
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('GET', '/customer-portal/tickets', [
                'per_page'    => 100,
                'filter_type' => 'all',
            ]);
            FsTest::assertHealthy($result, 'GET /customer-portal/tickets');

            $payload = $result['data']['tickets'];
            if (is_object($payload)) {
                $payload = json_decode(json_encode($payload), true);
            }
            if (!isset($payload['data'])) {
                FsTest::fail('portal ticket list has no data array: ' . wp_json_encode($result['data']));
                return;
            }

            $ids = [];
            foreach ($payload['data'] as $row) {
                $ids[] = (int) ((array) $row)['id'];
            }

            FsTest::assert(in_array((int) $ownerTicket->id, $ids, true),
                'the customer must see their own ticket');
            FsTest::assert(!in_array((int) $intruderTicket->id, $ids, true),
                'the customer must NOT see another customer ticket');
        });

        FsTest::case('portal_customer_can_read_their_own_ticket', function () use (
            $becomeUser, $ownerUserId, $ownerSerial, $ownerTicket
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('GET', '/customer-portal/tickets/' . $ownerSerial);
            FsTest::assertHealthy($result, 'GET /customer-portal/tickets/{serial}');

            $returned = isset($result['data']['ticket']['id']) ? (int) $result['data']['ticket']['id'] : 0;
            FsTest::assertSame((int) $ownerTicket->id, $returned, 'the portal returns the ticket we asked for');
        });

        FsTest::case('portal_customer_cannot_read_another_customers_ticket', function () use (
            $becomeUser, $intruderUserId, $ownerSerial, $ownerTicket
        ) {
            $becomeUser($intruderUserId);

            $result = FsTest::rest('GET', '/customer-portal/tickets/' . $ownerSerial);

            // A private ticket belongs to one customer only; anyone else must be
            // refused rather than served the row.
            $returned = isset($result['data']['ticket']['id']) ? (int) $result['data']['ticket']['id'] : 0;
            FsTest::assert($returned !== (int) $ownerTicket->id,
                'another customer must never receive ticket ' . $ownerTicket->id
                . ' (status ' . $result['status'] . ')');
            FsTest::assert($result['status'] !== 200,
                'reading another customer ticket must not return 200, got ' . $result['status']);
        });

        FsTest::case('portal_customer_cannot_reply_to_another_customers_ticket', function () use (
            $becomeUser, $intruderUserId, $ownerSerial, $ownerTicket, $marker
        ) {
            $becomeUser($intruderUserId);

            $before = Conversation::where('ticket_id', $ownerTicket->id)->count();

            $result = FsTest::rest('POST', '/customer-portal/tickets/' . $ownerSerial . '/responses', [
                'content' => '<p>Intruder reply ' . $marker . '</p>',
            ]);

            FsTest::assert($result['status'] !== 200,
                'replying to another customer ticket must be refused, got ' . $result['status']);

            $after = Conversation::where('ticket_id', $ownerTicket->id)->count();
            FsTest::assertSame((int) $before, (int) $after,
                'a refused reply must not write a conversation row');
        });

        FsTest::case('portal_customer_reply_is_stored_against_their_own_ticket', function () use (
            $becomeUser, $ownerUserId, $ownerSerial, $ownerTicket, $owner, $marker
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('POST', '/customer-portal/tickets/' . $ownerSerial . '/responses', [
                'content' => '<p>Owner reply ' . $marker . '</p>',
            ]);
            FsTest::assertHealthy($result, 'POST /customer-portal/tickets/{serial}/responses');

            $conversation = Conversation::where('ticket_id', $ownerTicket->id)
                ->where('conversation_type', 'response')
                ->orderBy('id', 'DESC')
                ->first();

            if (!$conversation) {
                FsTest::fail('the portal reply was not stored as a conversation');
                return;
            }

            FsTest::assert(strpos((string) $conversation->content, $marker) !== false,
                'the stored reply must be the content we posted');
            FsTest::assertSame((int) $owner->id, (int) $conversation->person_id,
                'the reply is attributed to the logged-in customer');
        });

        FsTest::case('portal_customer_cannot_close_another_customers_ticket', function () use (
            $becomeUser, $intruderUserId, $ownerSerial, $ownerTicket
        ) {
            $becomeUser($intruderUserId);

            $statusBefore = Ticket::find($ownerTicket->id)->status;

            $result = FsTest::rest('POST', '/customer-portal/tickets/' . $ownerSerial . '/close');

            FsTest::assert($result['status'] !== 200,
                'closing another customer ticket must be refused, got ' . $result['status']);
            FsTest::assertSame($statusBefore, Ticket::find($ownerTicket->id)->status,
                'a refused close must leave the ticket status untouched');
        });

        FsTest::case('portal_customer_can_close_their_own_ticket', function () use (
            $becomeUser, $ownerUserId, $ownerSerial, $ownerTicket
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('POST', '/customer-portal/tickets/' . $ownerSerial . '/close');
            FsTest::assertHealthy($result, 'POST /customer-portal/tickets/{serial}/close');

            $ticket = Ticket::find($ownerTicket->id);
            FsTest::assertSame('closed', $ticket->status, 'the customer closed their own ticket');
            FsTest::assert(!empty($ticket->resolved_at), 'a portal close must stamp resolved_at');
        });

        FsTest::case('portal_me_endpoint_reports_the_logged_in_user', function () use (
            $becomeUser, $ownerUserId, $owner
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('GET', '/customer-portal/me');
            FsTest::assertHealthy($result, 'GET /customer-portal/me');

            FsTest::assertSame((int) $ownerUserId, (int) $result['data']['user_id'], 'me.user_id');
            FsTest::assertSame($owner->email, (string) $result['data']['email'], 'me.email');
        });
    } finally {
        // Identity and permission cache must be handed back to the runner.
        $becomeUser($adminId);

        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ([$ownerUserId, $intruderUserId] as $userId) {
            if ($userId) {
                wp_delete_user($userId);
            }
        }
    }
};
