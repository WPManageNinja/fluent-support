<?php
/**
 * Reconciling a customer record with an account whose address has moved.
 *
 * Fluent Support refuses to follow an unverified email change, which is the
 * safe answer but leaves the record pointing at the previous address: support
 * mail keeps going to an inbox the customer may have abandoned, and tickets
 * they open from the new address land on a second, unlinked record.
 *
 * EmailClaimService closes that by mailing a confirmation link to the address
 * being claimed. The whole design rests on which inbox that mail lands in --
 * somebody pointing their account at an address they do not own sends the link
 * to the real owner -- so these cases pin down both halves: that a confirmed
 * link moves and merges, and that every unconfirmed or replayed variation of
 * one does nothing at all.
 */

use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\EmailClaimService;

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

    $oldEmail       = $marker . '-claim-old@example.invalid';
    $newEmail       = $marker . '-claim-new@example.invalid';
    $bystanderEmail = $marker . '-claim-bystander@example.invalid';

    // The account starts on its original address. Creating it before the
    // unlinked record matters: user_register adopts unlinked records carrying
    // the account's address, and adopting the stray one would remove the very
    // thing these cases are about.
    $claimantUserId  = $makeWpUser('claim-owner', $oldEmail);
    $bystanderUserId = $makeWpUser('claim-bystander', $bystanderEmail);

    $serialBase = 930000 + wp_rand(1, 60000);
    $usedToken  = '';
    $renameUserId = 0;
    $slashUserId  = 0;
    $aposUserId   = 0;

    try {
        $claimant = FsFactory::customer([
            'email'   => $oldEmail,
            'user_id' => $claimantUserId,
        ]);

        $ownTicket = FsFactory::ticket([
            'customer_id'   => $claimant->id,
            'title'         => 'Email claim own ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase,
        ]);

        // The record an inbound mail from the new address would have created:
        // no account has ever claimed it, and it is collecting tickets the
        // customer cannot see from the portal.
        $stray = FsFactory::customer([
            'email'   => $newEmail,
            'user_id' => null,
        ]);

        $strayTicket = FsFactory::ticket([
            'customer_id'   => $stray->id,
            'title'         => 'Email claim stray ticket',
            'privacy'       => 'private',
            'serial_number' => $serialBase + 1,
        ]);

        // Custom-field data on both records. One key exists only on the stray
        // record and has to survive the merge; the other exists on both, and the
        // record being kept must win.
        $stray->updateMeta('fs_claim_carried', 'from-stray');
        $stray->updateMeta('fs_claim_kept', 'from-stray');
        Customer::find($claimant->id)->updateMeta('fs_claim_kept', 'from-claimant');

        // The account holder moves their own address with no confirmation, the
        // way the REST users endpoint and WooCommerce's account form both do.
        $becomeUser($claimantUserId);
        wp_update_user(['ID' => $claimantUserId, 'user_email' => $newEmail]);
        $becomeUser($adminId);

        FsTest::case('an_unverified_change_is_offered_for_confirmation_but_moves_nothing', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $oldEmail, $newEmail
        ) {
            $becomeUser($claimantUserId);

            $stored = Customer::find($claimant->id);
            FsTest::assertSame($oldEmail, (string) $stored->email,
                'an unverified account email change must leave the support address where it is');

            $divergence = EmailClaimService::getDivergence();

            FsTest::assert(is_array($divergence),
                'the gap between the account address and the support address must be offered for confirmation');
            FsTest::assertSame($oldEmail, (string) $divergence['from'],
                'the notice must name the address support mail goes to now');
            FsTest::assertSame($newEmail, (string) $divergence['to'],
                'the notice must name the address the account now holds');

            $becomeUser($adminId);
        });

        FsTest::case('merging_carries_internal_notification_actors_across', function () use ($serialBase, $marker) {
            // The notification tables are opt-in: NotificationSettings creates
            // them the first time internal notifications are switched on, and
            // the default is off. Skip rather than create them here, so this
            // reflects whatever the site under test actually has.
            if (!(new \FluentSupport\App\Services\Notifications\NotificationSettings())->notificationTablesExist()) {
                FsTest::skip('internal notification tables are not installed');
                return;
            }

            $email = $marker . '-notify-merge@example.invalid';

            $keep    = FsFactory::customer(['user_id' => null]);
            $absorbed = FsFactory::customer(['email' => $email, 'user_id' => null]);

            $ticket = FsFactory::ticket([
                'customer_id'   => $absorbed->id,
                'title'         => 'Notification actor merge ticket',
                'privacy'       => 'private',
                'serial_number' => $serialBase + 4,
            ]);

            $notification = \FluentSupport\App\Models\Notification::create([
                'actor_id'    => $absorbed->id,
                'object_id'   => $ticket->id,
                'object_type' => 'ticket',
                'action'      => 'fluent_support/test_actor_merge',
            ]);

            try {
                EmailClaimService::apply(Customer::find($keep->id), $email);

                FsTest::assert(!Customer::find($absorbed->id),
                    'the absorbed record must be gone, or this case is not testing a merge');

                $moved = \FluentSupport\App\Models\Notification::find($notification->id);

                FsTest::assert($moved !== null, 'the notification must survive the merge');

                if ($moved) {
                    FsTest::assertSame((int) $keep->id, (int) $moved->actor_id,
                        'a notification must not keep naming a person row that has been deleted');
                }
            } finally {
                \FluentSupport\App\Models\Notification::where('id', $notification->id)->delete();
            }
        });

        FsTest::case('an_agent_correcting_the_address_reissues_the_ticket_links', function () use (
            $becomeUser, $adminId, $marker, $serialBase
        ) {
            // Correcting a customer's address is exactly when the previous inbox
            // must stop working: a signed ticket link authorises read, reply,
            // close and reopen on its own, so whoever holds the old address
            // keeps full access until the hash changes.
            $becomeUser($adminId);

            $customer = FsFactory::customer(['user_id' => null]);

            $ticket = FsFactory::ticket([
                'customer_id'   => $customer->id,
                'title'         => 'Agent edit reissue ticket',
                'privacy'       => 'private',
                'serial_number' => $serialBase + 3,
            ]);

            $before = (string) Ticket::find($ticket->id)->hash;

            (new Customer())->updateCustomer($customer->id, [
                'email'      => $marker . '-agent-corrected@example.invalid',
                'first_name' => 'Corrected',
                'last_name'  => 'Customer',
            ]);

            $after = (string) Ticket::find($ticket->id)->hash;

            FsTest::assert($before !== '', 'the ticket must start with a hash for this case to mean anything');
            FsTest::assert($after !== $before,
                'an agent moving the contact address must reissue the links already sent to the previous inbox');
        });

        FsTest::case('a_name_only_edit_holds_the_address_even_when_the_account_email_needs_slashing', function () use (
            $becomeUser, $adminId, $marker, &$aposUserId
        ) {
            // wp_insert_user() slashes the previous address before firing
            // profile_update -- core says so in a comment, "Slash current user
            // email to compare it later with slashed new user email". Comparing
            // it against the unslashed new address reads an apostrophe address
            // as changed on every update, and an administrator editing only a
            // name would move the support address onto it.
            $account = "o'" . $marker . '@example.invalid';
            $held    = $marker . '-apos-held@example.invalid';

            $aposUserId = wp_insert_user([
                'user_login' => substr($marker . '-apos', 0, 60),
                'user_email' => $account,
                'user_pass'  => wp_generate_password(20),
                'role'       => 'subscriber',
            ]);

            if (is_wp_error($aposUserId)) {
                FsTest::fail('could not create an account with an apostrophe address: ' . $aposUserId->get_error_message());
                $aposUserId = 0;
                return;
            }

            $aposUserId = (int) $aposUserId;

            FsTest::assertSame($account, (string) get_user_by('ID', $aposUserId)->user_email,
                'the account must actually hold the apostrophe address, or this case proves nothing');

            $customer = FsFactory::customer(['email' => $held, 'user_id' => $aposUserId]);

            $becomeUser($adminId);
            wp_update_user(['ID' => $aposUserId, 'first_name' => 'RenamedOnly']);

            FsTest::assertSame($held, (string) Customer::find($customer->id)->email,
                'a name-only edit must hold the support address whatever characters the account address contains');
        });

        FsTest::case('a_name_reaches_the_customer_record_as_the_account_holds_it', function () use (
            $becomeUser, $adminId, $makeWpUser, $marker, &$slashUserId
        ) {
            // wp_insert_user() takes slashed data and profile_update passes that
            // same array on, so anything written straight through lands with a
            // literal backslash the customer then sees in the portal.
            $email = $marker . '-slash@example.invalid';
            $slashUserId = $makeWpUser('slash-owner', $email);

            $customer = FsFactory::customer(['email' => $email, 'user_id' => $slashUserId]);

            $becomeUser($slashUserId);
            wp_update_user([
                'ID'         => $slashUserId,
                'first_name' => wp_slash("O'Brien"),
                'last_name'  => wp_slash('D\'Angelo'),
            ]);

            $stored = Customer::find($customer->id);

            FsTest::assertSame("O'Brien", (string) $stored->first_name,
                'the name on the customer record must read exactly as WordPress stores it');
            FsTest::assertSame("D'Angelo", (string) $stored->last_name,
                'the surname on the customer record must read exactly as WordPress stores it');
            FsTest::assertSame(get_user_meta($slashUserId, 'first_name', true), (string) $stored->first_name,
                'the customer record and the WordPress account must agree on the name');

            $becomeUser($adminId);
        });

        FsTest::case('a_confirmation_link_survives_an_address_containing_the_field_separator', function () {
            // is_email() accepts a pipe in the local part, and the token joins
            // its fields on a pipe. Unencoded, such an address splits the
            // payload into an extra field and the token is rejected -- closed,
            // but that customer can never use the flow.
            $odd = 'first|last@example.invalid';

            $token = EmailClaimService::buildToken(12, 34, 'plain@example.invalid', $odd, time() + 3600);

            $parse = new ReflectionMethod('FluentSupport\\App\\Services\\EmailClaimService', 'parseToken');
            $parse->setAccessible(true);
            $claim = $parse->invoke(null, $token);

            FsTest::assert(is_array($claim),
                'a token for an address containing the field separator must still parse');

            if (is_array($claim)) {
                FsTest::assertSame($odd, (string) $claim['to'],
                    'the address must come back exactly as it went in');
                FsTest::assertSame(12, (int) $claim['customer_id'], 'the record id must survive the round trip');
                FsTest::assertSame(34, (int) $claim['user_id'], 'the account id must survive the round trip');
            }
        });

        FsTest::case('creating_a_portal_ticket_does_not_move_the_held_address', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $oldEmail
        ) {
            // Holding the address on profile_update is worth nothing if any
            // other path writes it. This is the one that used to: the portal's
            // ticket-create route resolves the customer through
            // Customer::maybeCreateCustomer() with a payload built from the
            // signed-in account's own email, which WordPress lets that account
            // change without confirming anything.
            $becomeUser($claimantUserId);

            (new \FluentSupport\App\Services\CustomerPortalService())
                ->resolveCustomer(null, '127.0.0.1', true);

            $stored = Customer::find($claimant->id);

            FsTest::assertSame($oldEmail, (string) $stored->email,
                'intake must not move the contact address of a record that belongs to an account');

            $becomeUser($adminId);
        });

        FsTest::case('an_administrator_editing_only_a_name_does_not_move_the_address', function () use (
            $becomeUser, $adminId, $makeWpUser, $marker, &$renameUserId
        ) {
            // Its own fixtures on purpose. Run against the shared ones, the
            // collision guard would refuse the move anyway -- the stray record
            // holds that address -- and the case would pass without ever
            // exercising the thing it is named after.
            $before = $marker . '-rename-old@example.invalid';
            $after  = $marker . '-rename-new@example.invalid';

            $renameUserId = $makeWpUser('rename-owner', $before);

            $customer = FsFactory::customer([
                'email'      => $before,
                'user_id'    => $renameUserId,
                'first_name' => 'Original',
            ]);

            // The account moves its own address with no confirmation, so the
            // support address is held where it is.
            $becomeUser($renameUserId);
            wp_update_user(['ID' => $renameUserId, 'user_email' => $after]);

            FsTest::assertSame($before, (string) Customer::find($customer->id)->email,
                'the divergence must exist for this case to mean anything');

            // An administrator has authority over the account, but that is
            // authority for the change they made. Renaming somebody must not
            // quietly bless an unverified address the account is already
            // carrying, which is what happens when the check compares the
            // record to the account rather than to the update.
            $becomeUser($adminId);
            wp_update_user(['ID' => $renameUserId, 'first_name' => 'Renamed']);

            $stored = Customer::find($customer->id);

            FsTest::assertSame($before, (string) $stored->email,
                'an administrator who did not touch the email must not move the support address');
            FsTest::assertSame('Renamed', (string) $stored->first_name,
                'names must still sync, or this case would pass for the wrong reason');
        });

        FsTest::case('the_confirmation_email_is_sent_to_the_new_address_only', function () use (
            $becomeUser, $adminId, $claimantUserId, $oldEmail, $newEmail
        ) {
            $becomeUser($claimantUserId);

            $sent = [];

            // Short-circuits delivery and records what would have gone out.
            $capture = function ($short, $atts) use (&$sent) {
                $sent[] = $atts;
                return true;
            };

            // The lab has no portal page assigned, and the link has to point
            // somewhere for the mail to be worth sending.
            $portalUrl = function () {
                return home_url('/support');
            };

            add_filter('pre_wp_mail', $capture, 10, 2);
            add_filter('fluent_support/portal_base_url', $portalUrl);

            $error = EmailClaimService::issue(EmailClaimService::getDivergence());

            remove_filter('fluent_support/portal_base_url', $portalUrl);
            remove_filter('pre_wp_mail', $capture, 10);

            FsTest::assertSame('', (string) $error, 'issuing the confirmation must not error');
            FsTest::assertSame(1, count($sent), 'exactly one confirmation email must be sent');

            if (!$sent) {
                return;
            }

            $recipients = (array) $sent[0]['to'];

            FsTest::assert(in_array($newEmail, $recipients, true),
                'the confirmation must go to the address being claimed, which is the inbox that proves control of it');
            FsTest::assert(!in_array($oldEmail, $recipients, true),
                'the confirmation must not go to the previous address; WordPress already notifies it of the account change');

            $becomeUser($adminId);
        });

        FsTest::case('a_ticket_changing_owner_is_reissued', function () use ($serialBase) {
            // The mechanism absorb() leans on, exercised on its own. Merging a
            // record also moves the customer's address, and that rotates every
            // hash on the record, so a merge alone cannot tell whether the
            // reparenting itself reissues. This can: absorb() saves each ticket
            // one at a time purely so this event fires, and a mass update on the
            // query builder would silently skip it and carry live links across
            // to the new owner.
            $from = FsFactory::customer(['user_id' => null]);
            $to   = FsFactory::customer(['user_id' => null]);

            $ticket = FsFactory::ticket([
                'customer_id'   => $from->id,
                'title'         => 'Email claim reparent ticket',
                'privacy'       => 'private',
                'serial_number' => $serialBase + 2,
            ]);

            $before = (string) Ticket::find($ticket->id)->hash;

            $row = Ticket::find($ticket->id);
            $row->customer_id = $to->id;
            $row->save();

            $after = (string) Ticket::find($ticket->id)->hash;

            FsTest::assert($before !== '', 'the ticket must start with a hash for this case to mean anything');
            FsTest::assert($after !== $before,
                'a ticket moving to another customer must be reissued, or every link already mailed for it keeps working under its new owner');
        });

        FsTest::case('a_tampered_confirmation_link_is_refused', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $oldEmail, $newEmail
        ) {
            $becomeUser($claimantUserId);

            $token = EmailClaimService::buildToken(
                $claimant->id, $claimantUserId, $oldEmail, $newEmail, time() + 3600
            );

            // Flip one character of the signature. Done on the decoded bytes, not
            // on the base64 text: the padding is stripped from the encoded form,
            // so the final character there can carry bits that decode to nothing
            // and flipping it sometimes leaves the payload identical. The
            // signature is hex, so changing its last character always changes it.
            $raw  = base64_decode(strtr($token, '-_', '+/'), true);
            $last = substr($raw, -1);
            $raw  = substr($raw, 0, -1) . ($last === 'a' ? 'b' : 'a');

            $tampered = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

            FsTest::assert($tampered !== $token, 'the tampered token must actually differ from the original');

            $resolved = EmailClaimService::resolveClaim($tampered);

            FsTest::assertSame('invalid', $resolved['status'],
                'a token whose signature does not verify must be refused');

            $becomeUser($adminId);
        });

        FsTest::case('an_expired_confirmation_link_is_refused', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $oldEmail, $newEmail
        ) {
            $becomeUser($claimantUserId);

            $token = EmailClaimService::buildToken(
                $claimant->id, $claimantUserId, $oldEmail, $newEmail, time() - 10
            );

            $resolved = EmailClaimService::resolveClaim($token);

            FsTest::assertSame('expired', $resolved['status'],
                'a link past its expiry must be refused even though its signature is sound');

            $becomeUser($adminId);
        });

        FsTest::case('a_confirmation_link_does_nothing_for_another_signed_in_account', function () use (
            $becomeUser, $adminId, $claimantUserId, $bystanderUserId, $claimant, $oldEmail, $newEmail
        ) {
            // Reading the inbox is only half the proof. A link forwarded to
            // somebody else, or opened by whoever is signed in on a shared
            // machine, must not move anything.
            $becomeUser($bystanderUserId);

            $token = EmailClaimService::buildToken(
                $claimant->id, $claimantUserId, $oldEmail, $newEmail, time() + 3600
            );

            $resolved = EmailClaimService::resolveClaim($token);

            FsTest::assertSame('wrong_account', $resolved['status'],
                'confirming must require being signed in as the account the link was issued to');

            $stored = Customer::find($claimant->id);
            FsTest::assertSame($oldEmail, (string) $stored->email,
                'a link opened by the wrong account must leave the support address alone');

            $becomeUser($adminId);
        });

        FsTest::case('no_claim_is_offered_while_another_account_holds_the_address', function () use (
            $becomeUser, $adminId, $claimantUserId, $bystanderUserId, $newEmail, $stray
        ) {
            // Two accounts disputing one address is not something a confirmation
            // settles, so nothing is offered and an agent has to look at it.
            $strayRow = Customer::find($stray->id);
            $strayRow->user_id = $bystanderUserId;
            $strayRow->save();

            $becomeUser($claimantUserId);

            FsTest::assert(EmailClaimService::getDivergence() === null,
                'an address already held by another accounts record must not be offered for claiming');

            $token = EmailClaimService::buildToken(
                Customer::where('user_id', $claimantUserId)->first()->id,
                $claimantUserId,
                Customer::where('user_id', $claimantUserId)->first()->email,
                $newEmail,
                time() + 3600
            );

            FsTest::assertSame('conflict', EmailClaimService::resolveClaim($token)['status'],
                'a link into a contested address must be refused rather than creating a duplicate');

            $becomeUser($adminId);

            $strayRow = Customer::find($stray->id);
            $strayRow->user_id = null;
            $strayRow->save();
        });

        FsTest::case('confirming_moves_the_address_absorbs_the_stray_record_and_rotates_hashes', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $stray, $ownTicket, $strayTicket, $oldEmail, $newEmail, &$usedToken
        ) {
            $becomeUser($claimantUserId);

            $ownHashBefore   = (string) Ticket::find($ownTicket->id)->hash;
            $strayHashBefore = (string) Ticket::find($strayTicket->id)->hash;

            $usedToken = EmailClaimService::buildToken(
                $claimant->id, $claimantUserId, $oldEmail, $newEmail, time() + 3600
            );

            $resolved = EmailClaimService::resolveClaim($usedToken);

            FsTest::assertSame('ok', $resolved['status'], 'a sound link from the claiming account must resolve');

            $result = EmailClaimService::apply($resolved['customer'], $resolved['email']);

            $stored = Customer::find($claimant->id);
            FsTest::assertSame($newEmail, (string) $stored->email,
                'a confirmed address must become the address support writes to');

            FsTest::assertSame(1, (int) $result['merged'], 'the stray record must be absorbed');
            FsTest::assertSame(1, (int) $result['tickets'], 'the stray records ticket must move across');

            FsTest::assertSame((int) $claimant->id, (int) Ticket::find($strayTicket->id)->customer_id,
                'a ticket opened from the new address must end up on the customers own record');

            FsTest::assert(!Customer::find($stray->id),
                'an emptied stray record must not be left behind to match future inbound mail');

            // Both sets of links have to die: the ones mailed for the old
            // address, and the ones mailed while the ticket sat on a record
            // that has now changed hands.
            FsTest::assert($ownHashBefore !== '' && (string) Ticket::find($ownTicket->id)->hash !== $ownHashBefore,
                'moving the contact address must rotate the hashes that authorise the signed public ticket view');
            FsTest::assert($strayHashBefore !== '' && (string) Ticket::find($strayTicket->id)->hash !== $strayHashBefore,
                'a ticket changing owner must be reissued, or links already mailed for it keep working under the new owner');

            $becomeUser($adminId);
        });

        FsTest::case('merging_carries_over_custom_data_the_surviving_record_lacks', function () use ($claimant) {
            $kept = Customer::find($claimant->id);

            FsTest::assertSame('from-stray', (string) $kept->getMeta('fs_claim_carried'),
                'data held only by the absorbed record must move across rather than disappear with it');
        });

        FsTest::case('merging_does_not_overwrite_custom_data_the_surviving_record_has', function () use ($claimant) {
            $kept = Customer::find($claimant->id);

            // The surviving record is the one the customer has been using. A
            // stray record assembled from an inbound email is not a better
            // source of truth than it.
            FsTest::assertSame('from-claimant', (string) $kept->getMeta('fs_claim_kept'),
                'a key the surviving record already holds must not be replaced by the absorbed one');
        });

        FsTest::case('merging_leaves_no_custom_data_pointing_at_the_removed_record', function () use ($stray) {
            $orphans = Meta::where('object_type', 'person_meta')
                ->where('object_id', $stray->id)
                ->count();

            FsTest::assertSame(0, (int) $orphans,
                'meta must not be left in fs_meta pointing at a person id that no longer exists');
        });

        FsTest::case('a_confirmation_link_is_inert_while_the_address_it_moved_stays_moved', function () use (
            $becomeUser, $adminId, $claimantUserId, $newEmail, &$usedToken
        ) {
            $becomeUser($claimantUserId);

            FsTest::assert(!empty($usedToken), 'the previous case must have produced a token for this one to mean anything');

            // Retired by binding the record's address into the link: confirming
            // moves that address, so the link no longer describes any record
            // that exists. Nothing is stored, so this is a condition rather
            // than a stored single use -- putting the record back on the old
            // address before the link expires makes it work again. It is still
            // the inbox that proved the address, so that reconfirms what was
            // already confirmed.
            $resolved = EmailClaimService::resolveClaim($usedToken);

            FsTest::assertSame('stale', $resolved['status'],
                'a link that has already been confirmed must not confirm anything a second time');

            $becomeUser($adminId);
        });

        FsTest::case('a_confirmation_link_dies_when_the_records_address_moves_again', function () use (
            $becomeUser, $adminId, $claimantUserId, $claimant, $newEmail, $marker
        ) {
            $becomeUser($claimantUserId);

            $laterEmail = $marker . '-claim-later@example.invalid';
            wp_update_user(['ID' => $claimantUserId, 'user_email' => $laterEmail]);

            // Issued against the address the record holds right now.
            $token = EmailClaimService::buildToken(
                $claimant->id, $claimantUserId, $newEmail, $laterEmail, time() + 3600
            );

            // An agent corrects the record before the customer gets to the link.
            $becomeUser($adminId);
            $row = Customer::find($claimant->id);
            $row->email = $marker . '-claim-corrected@example.invalid';
            $row->save();

            $becomeUser($claimantUserId);

            FsTest::assertSame('stale', EmailClaimService::resolveClaim($token)['status'],
                'a link must stop working once the record it describes no longer holds the address it was issued against');

            $becomeUser($adminId);
        });
    } finally {
        $becomeUser($adminId);

        // fs_persons rows do not cascade their meta, and the factory does not
        // track fs_meta, so these come out by hand.
        if (!empty($claimant) && !empty($stray)) {
            Meta::where('object_type', 'person_meta')
                ->whereIn('object_id', [$claimant->id, $stray->id])
                ->delete();
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ([$claimantUserId, $bystanderUserId, $renameUserId, $slashUserId, $aposUserId] as $userId) {
            if ($userId) {
                wp_delete_user($userId);
            }
        }
    }
};
