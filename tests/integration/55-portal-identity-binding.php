<?php
/**
 * Portal identity binding: a WordPress account reaches a customer record through
 * its user id and through nothing else.
 *
 * WordPress lets any user change their own address with no verification through
 * the REST users endpoint, WP-CLI and most account pages, because the
 * confirmation flow runs only on wp-admin/profile.php. Resolving a customer by
 * that address therefore let a plain subscriber read, answer and close another
 * customer's private tickets, and writing their user id onto the matched row
 * made the takeover survive changing the address back.
 *
 * These cases reproduce that sequence with the routes the original report used:
 * an unlinked customer holding a private ticket, and a subscriber who takes
 * over the address.
 */

use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;

return function () {

    $marker  = FsFactory::marker();
    $adminId = get_current_user_id();

    $becomeUser = function ($userId) {
        wp_set_current_user($userId);
        PermissionManager::currentUserPermissions(false);
    };

    $makeWpUser = function ($slug, $email) use ($marker) {
        $userId = wp_insert_user([
            'user_login' => substr($marker . '-' . $slug, 0, 60),
            'user_email' => $email,
            'user_pass'  => wp_generate_password(20),
            'role'       => 'subscriber',
        ]);

        if (is_wp_error($userId)) {
            throw new RuntimeException('Could not create WP user: ' . $userId->get_error_message());
        }

        return (int) $userId;
    };

    $victimEmail   = $marker . '-bind-victim@example.invalid';
    $attackerEmail = $marker . '-bind-attacker@example.invalid';
    $ownerEmail    = $marker . '-bind-owner@example.invalid';
    $adopteeEmail  = $marker . '-bind-adoptee@example.invalid';

    $attackerUserId = $makeWpUser('bind-attacker', $attackerEmail);
    $ownerUserId    = $makeWpUser('bind-owner', $ownerEmail);
    $adopteeUserId  = 0;
    $managerUserId  = 0;

    try {
        // The victim exists only inside Fluent Support. No WordPress account
        // holds this address, which is exactly what leaves it free to claim.
        $victim = FsFactory::customer([
            'email'   => $victimEmail,
            'user_id' => null,
        ]);

        $owner = FsFactory::customer([
            'email'   => $ownerEmail,
            'user_id' => $ownerUserId,
        ]);

        $serialBase = 910000 + wp_rand(1, 70000);

        $victimTicket = FsFactory::ticket([
            'customer_id'   => $victim->id,
            'title'         => 'Identity binding victim ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase,
        ]);

        $ownerTicket = FsFactory::ticket([
            'customer_id'   => $owner->id,
            'title'         => 'Identity binding owner ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase + 1,
        ]);

        $victimSerial = (int) Ticket::find($victimTicket->id)->serial_number;

        // Step 5 of the report: change only the subscriber's own address. No
        // confirmation takes place, which is how the REST users endpoint, WP-CLI
        // and account pages such as WooCommerce's all apply an email change.
        wp_update_user(['ID' => $attackerUserId, 'user_email' => $victimEmail]);

        FsTest::case('an_unverified_self_service_email_change_does_not_move_the_customers_address', function () use (
            $becomeUser, $adminId, $owner, $ownerUserId, $marker
        ) {
            // The account holder changing their own address, which is what the
            // REST users endpoint and account pages such as WooCommerce's do.
            // No confirmation takes place anywhere in that path.
            $becomeUser($ownerUserId);

            $movedEmail = $marker . '-bind-owner-moved@example.invalid';
            wp_update_user(['ID' => $ownerUserId, 'user_email' => $movedEmail]);

            $stored = Customer::find($owner->id);

            FsTest::assertSame($owner->email, (string) $stored->email,
                'an email change WordPress never confirmed must not move the address support writes to');

            wp_update_user(['ID' => $ownerUserId, 'user_email' => $owner->email]);
            $becomeUser($adminId);
        });

        FsTest::case('taking_over_an_address_does_not_list_the_unlinked_customers_tickets', function () use (
            $becomeUser, $attackerUserId, $victimTicket
        ) {
            $becomeUser($attackerUserId);

            $result = FsTest::rest('GET', '/customer-portal/tickets', [
                'per_page'    => 100,
                'filter_type' => 'all',
            ]);
            FsTest::assertHealthy($result, 'GET /customer-portal/tickets');

            $payload = json_decode(json_encode($result['data']['tickets']), true);
            $rows    = isset($payload['data']) ? $payload['data'] : [];

            $ids = [];
            foreach ($rows as $row) {
                $ids[] = (int) $row['id'];
            }

            FsTest::assert(!in_array((int) $victimTicket->id, $ids, true),
                'holding the address must not surface a customer record that is not linked to this account');
        });

        FsTest::case('taking_over_an_address_cannot_read_the_unlinked_customers_ticket', function () use (
            $becomeUser, $attackerUserId, $victimSerial, $victimTicket
        ) {
            $becomeUser($attackerUserId);

            $result   = FsTest::rest('GET', '/customer-portal/tickets/' . $victimSerial);
            $returned = isset($result['data']['ticket']['id']) ? (int) $result['data']['ticket']['id'] : 0;

            FsTest::assert($returned !== (int) $victimTicket->id,
                'holding the address must not read another customer private ticket (status '
                . $result['status'] . ')');
        });

        FsTest::case('portal_use_does_not_bind_the_unlinked_customer_to_the_requester', function () use (
            $becomeUser, $attackerUserId, $victim, $victimEmail
        ) {
            $becomeUser($attackerUserId);

            // Reading alone never binds. The report made the takeover permanent
            // by loading the portal page, which resolves the current user into a
            // customer through exactly this payload, and the REST ticket-create
            // route reaches the same call with forceCreate set.
            $resolved = Customer::maybeCreateCustomer([
                'user_id'         => $attackerUserId,
                'email'           => $victimEmail,
                'last_ip_address' => '127.0.0.1',
            ]);

            // Refused the victim's row, the call falls through to creating a
            // fresh empty one for this account. That row is a real fixture and
            // has to be cleaned up like any other.
            if ($resolved && (int) $resolved->id !== (int) $victim->id) {
                FsFactory::adoptRestCustomer($resolved);
            }

            $stored = Customer::find($victim->id);

            FsTest::assert(empty($stored->user_id),
                'an existing customer row must never be bound to an account that merely holds its address (user_id is '
                . var_export($stored->user_id, true) . ')');
        });

        FsTest::case('portal_responses_do_not_carry_the_ticket_hash', function () use (
            $becomeUser, $ownerUserId, $ownerTicket
        ) {
            $becomeUser($ownerUserId);

            $result = FsTest::rest('GET', '/customer-portal/tickets', [
                'per_page'    => 100,
                'filter_type' => 'all',
            ]);
            FsTest::assertHealthy($result, 'GET /customer-portal/tickets');

            $payload = json_decode(json_encode($result['data']['tickets']), true);
            $rows    = isset($payload['data']) ? $payload['data'] : [];

            FsTest::assert(count($rows) > 0, 'the owner must see their own ticket for this case to mean anything');

            foreach ($rows as $row) {
                FsTest::assert(!array_key_exists('hash', $row),
                    'the ticket hash authorises the signed public view and must not be serialised to the portal');
                FsTest::assert(!array_key_exists('content_hash', $row),
                    'content_hash must not be serialised to the portal');
            }
        });

        FsTest::case('an_administrator_editing_the_account_moves_the_customers_address', function () use (
            $becomeUser, $adminId, $owner, $ownerUserId, $ownerTicket, $marker
        ) {
            $becomeUser($adminId);

            $hashBefore = (string) Ticket::find($ownerTicket->id)->hash;
            $adminEmail = $marker . '-bind-owner-byadmin@example.invalid';

            wp_update_user(['ID' => $ownerUserId, 'user_email' => $adminEmail]);

            $stored = Customer::find($owner->id);

            FsTest::assertSame($adminEmail, (string) $stored->email,
                'an administrator editing the account may move the address, since they can already edit the customer directly');

            // The new address is authorised but still unproven, so links already
            // sitting in the previous inbox have to stop working.
            $hashAfter = (string) Ticket::find($ownerTicket->id)->hash;

            FsTest::assert($hashBefore !== '' && $hashAfter !== $hashBefore,
                'moving the contact address must rotate the ticket hashes that authorise the signed public view');

            wp_update_user(['ID' => $ownerUserId, 'user_email' => $owner->email]);
        });

        FsTest::case('an_actor_who_may_edit_the_account_moves_the_customers_address', function () use (
            $becomeUser, $adminId, $owner, $ownerUserId, $marker, &$managerUserId
        ) {
            // Anyone who may edit the WordPress account may move the address
            // Fluent Support writes to. This role holds edit_users and nothing
            // else: no administrator rights and no Fluent Support permission,
            // which is the shape WooCommerce's shop_manager has on a store.
            add_role('fs_test_user_manager', 'FS Test User Manager', [
                'read'       => true,
                'edit_users' => true,
            ]);

            $managerUserId = wp_insert_user([
                'user_login' => substr($marker . '-bind-manager', 0, 60),
                'user_email' => $marker . '-bind-manager@example.invalid',
                'user_pass'  => wp_generate_password(20),
                'role'       => 'fs_test_user_manager',
            ]);

            if (is_wp_error($managerUserId)) {
                FsTest::fail('could not create the user-manager account: ' . $managerUserId->get_error_message());
                return;
            }

            $managerUserId = (int) $managerUserId;
            $becomeUser($managerUserId);

            FsTest::assert(current_user_can('edit_user', $ownerUserId),
                'the stand-in role must actually hold edit_user, or this case proves nothing');
            FsTest::assert(!current_user_can('manage_options'),
                'the stand-in role must not be a full administrator, or this case proves nothing');

            $movedEmail = $marker . '-bind-bymanager@example.invalid';
            wp_update_user(['ID' => $ownerUserId, 'user_email' => $movedEmail]);

            $stored = Customer::find($owner->id);

            FsTest::assertSame($movedEmail, (string) $stored->email,
                'an actor who may edit the WordPress account may move the support address');

            $becomeUser($adminId);
            wp_update_user(['ID' => $ownerUserId, 'user_email' => $owner->email]);
        });

        FsTest::case('an_actor_without_user_edit_rights_cannot_move_the_customers_address', function () use (
            $becomeUser, $adminId, $owner, $ownerUserId, $attackerUserId, $marker
        ) {
            // wp_update_user() performs no capability check of its own, so this
            // is the boundary: a plain subscriber reaching a user update through
            // any route must not be able to redirect a support address.
            $becomeUser($attackerUserId);

            FsTest::assert(!current_user_can('edit_user', $ownerUserId),
                'the subscriber must not hold edit_user, or this case proves nothing');

            $grabbedEmail = $marker . '-bind-grabbed@example.invalid';
            wp_update_user(['ID' => $ownerUserId, 'user_email' => $grabbedEmail]);

            $stored = Customer::find($owner->id);

            FsTest::assertSame($owner->email, (string) $stored->email,
                'an actor with no rights over the account must not redirect a support address');

            $becomeUser($adminId);
            wp_update_user(['ID' => $ownerUserId, 'user_email' => $owner->email]);
        });

        FsTest::case('registering_an_account_adopts_an_unlinked_customer_row', function () use (
            $makeWpUser, $adopteeEmail, &$adopteeUserId
        ) {
            $adoptee = FsFactory::customer([
                'email'   => $adopteeEmail,
                'user_id' => null,
            ]);

            // Registration is the one moment an address may be treated as
            // belonging to the account: WordPress refuses an address another
            // user already holds, and it mails the credentials to that inbox.
            $adopteeUserId = $makeWpUser('bind-adoptee', $adopteeEmail);

            $stored = Customer::find($adoptee->id);

            FsTest::assertSame($adopteeUserId, (int) $stored->user_id,
                'a new account must adopt the unlinked customer row carrying its address');
        });
    } finally {
        $becomeUser($adminId);

        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ([$attackerUserId, $ownerUserId, $adopteeUserId, $managerUserId] as $userId) {
            if ($userId) {
                wp_delete_user($userId);
            }
        }

        remove_role('fs_test_user_manager');
    }
};
