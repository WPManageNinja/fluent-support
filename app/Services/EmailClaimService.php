<?php

namespace FluentSupport\App\Services;

use FluentSupport\App\Models\Activity;
use FluentSupport\App\Models\Attachment;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Notification;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Notifications\NotificationSettings;

/**
 * Reconciles a customer record with the WordPress account it belongs to after
 * that account's email address has moved.
 *
 * ProfileInfoService deliberately refuses to follow an unverified email change,
 * so an account whose address changes through REST, WP-CLI or a WooCommerce
 * account form leaves its customer record pointing at the previous address.
 * That is the safe outcome but not a finished one: support mail keeps going to
 * an inbox the customer may have stopped reading, and any ticket they open from
 * the new address lands on a second, unlinked record.
 *
 * This closes that gap without ever trusting the account address on its own.
 * The divergence is surfaced in the portal, the customer asks for a
 * confirmation link, and the link is mailed to the address being claimed.
 *
 * The security of the whole flow rests on which inbox that mail lands in. When
 * somebody points their WordPress account at an address they do not own, the
 * link goes to the real owner, who learns of the attempt, and the sender is
 * left with nothing. Confirming also requires being signed in as the claiming
 * account, so neither the inbox nor the account moves anything alone.
 */
class EmailClaimService
{
    /**
     * How long a claim link stays valid.
     *
     * WordPress's own email change confirmation never expires. Bounding it is
     * cheap here because a fresh link can always be requested from the portal,
     * and it limits how long a link left sitting in an inbox stays live.
     */
    const TTL_SECONDS = DAY_IN_SECONDS;

    /**
     * Domain separator mixed into the signing key so a token minted here can
     * never be replayed against another feature that signs with the same salt.
     */
    const SIGNING_CONTEXT = 'fluent_support_email_claim_v1';

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        /*
         * Filter whether customers may reconcile their support address with
         * their WordPress account address from the portal. Turning this off
         * leaves a diverged record pointing at its previous address until an
         * agent moves it by hand.
         *
         * @since v2.4.1
         * @param bool $enabled
         */
        return (bool) apply_filters('fluent_support/enable_email_claim', true);
    }

    /**
     * Describe the gap between the signed-in account's address and the address
     * on its customer record, or null when there is nothing to reconcile.
     *
     * Returning null is the common case and covers rather more than "the
     * addresses match": no signed-in account, no record linked to it, a record
     * linked to somebody else, or an address already held by another linked
     * record. That last one is a genuine conflict between two accounts and is
     * left for an agent rather than resolved by whoever asks first.
     *
     * Pass the customer when the caller has already resolved it. The portal
     * renders this on every page load, and looking the same record up twice per
     * render is the one cost this check has in the common case where the two
     * addresses agree.
     *
     * @param \FluentSupport\App\Models\Customer|null $customer
     * @return array|null
     */
    public static function getDivergence($customer = null)
    {
        if (!self::isEnabled()) {
            return null;
        }

        $userId = (int) get_current_user_id();

        if (!$userId) {
            return null;
        }

        $user = get_user_by('ID', $userId);

        if (!$user || !$user->user_email) {
            return null;
        }

        $customer = $customer ?: Helper::getCurrentCustomer();

        if (!$customer || (int) $customer->user_id !== $userId) {
            return null;
        }

        if (self::isSame($customer->email, $user->user_email)) {
            return null;
        }

        if (self::heldByLinkedCustomer($user->user_email, $customer->id)) {
            return null;
        }

        // Deliberately does not count or load the stray records holding the new
        // address. Nothing on the read path needs them -- the notice reports no
        // count on purpose -- and apply() re-reads them at the moment it acts,
        // where a list assembled pages earlier would be stale anyway.
        return [
            'customer' => $customer,
            'from'     => $customer->email,
            'to'       => $user->user_email
        ];
    }

    /**
     * Mail a confirmation link to the address being claimed.
     *
     * @param array $divergence As returned by getDivergence()
     * @return string '' on success, otherwise an error slug
     */
    public static function issue($divergence)
    {
        $customer = $divergence['customer'];
        $target   = $divergence['to'];

        // Two buckets, because the two abuses are different. The per-account
        // bucket stops somebody cycling their own account address to mail-bomb
        // a series of victims; the per-address bucket stops the same inbox
        // being targeted repeatedly from several accounts.
        $accountKey = 'fs_email_claim_user_' . (int) $customer->user_id;
        $targetKey  = 'fs_email_claim_to_' . wp_hash(self::normalize($target));

        if (Helper::hitRateLimit($accountKey, 5, HOUR_IN_SECONDS)
            || Helper::hitRateLimit($targetKey, 5, HOUR_IN_SECONDS)
        ) {
            return 'throttled';
        }

        $link = self::buildConfirmUrl($customer, $target);

        if (!$link) {
            return 'no_portal';
        }

        $siteName = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

        $subject = apply_filters(
            'fluent_support/email_claim_mail_subject',
            // translators: %s is the site name
            sprintf(__('[%s] Confirm your support email address', 'fluent-support'), $siteName),
            $customer
        );

        $pStart = '<p style="font-family: Arial, sans-serif; font-size: 16px; font-weight: normal; margin: 0; margin-bottom: 16px;">';

        $body = $pStart . sprintf(
                // translators: %s is the customer's first name
                __('Hello %s,', 'fluent-support'),
                // WordPress does not sanitize first_name on the way in, so this
                // is arbitrary text landing in an HTML mail body.
                esc_html($customer->first_name)
            ) . '</p>' .
            $pStart . sprintf(
                // translators: 1: site name, 2: the email address being confirmed
                __('Someone asked to use this address for support messages on %1$s. Confirming will send future support notifications to %2$s and bring any tickets opened from it into your account.', 'fluent-support'),
                $siteName,
                $target
            ) . '</p>' .
            $pStart . '<a style="display: inline-block; background: #2271b1; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 3px;" href="' . esc_url($link) . '">' .
            esc_html__('Confirm this address', 'fluent-support') . '</a></p>' .
            $pStart . __('You will be asked to sign in first, so this link only works for the account that requested it.', 'fluent-support') . '</p>' .
            $pStart . __('If you did not ask for this, no action is needed and nothing has changed. Your support messages will keep going to the address they go to now.', 'fluent-support') . '</p>';

        /*
         * Filter the body of the support address confirmation email.
         *
         * @since v2.4.1
         * @param string $body
         * @param \FluentSupport\App\Models\Customer $customer
         * @param string $target The address being confirmed
         * @param string $link
         */
        $body = apply_filters('fluent_support/email_claim_mail_body', $body, $customer, $target, $link);

        $message = Helper::loadView('notification', [
            'body'        => $body,
            'pre_header'  => __('Confirm your support email address', 'fluent-support'),
            'show_footer' => false
        ]);

        $sent = wp_mail($target, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);

        if (!$sent) {
            // Telling the customer to go and read a mail that was never accepted
            // for delivery wastes their time and both rate limit allowances.
            return 'send_failed';
        }

        return '';
    }

    /**
     * Validate a confirmation token against current state.
     *
     * Every field in the token is re-checked rather than trusted, because the
     * world moves between issuing a link and clicking it. Binding the record's
     * address at issue time is what retires a used link: applying a claim moves
     * that address, so replaying the same link finds a record that no longer
     * matches.
     *
     * That is a condition, not a stored single use. Nothing is written down, so
     * a link works again if the record is put back on the address it was issued
     * against before the link expires -- an agent correcting a mistake, say.
     * The link is still in the inbox that proved the address in the first place,
     * so this reconfirms what was already confirmed; it is recorded here because
     * calling it single-use would overstate what the token does.
     *
     * @param string $token
     * @return array ['status' => slug, 'customer' => Customer|null, 'email' => string]
     */
    public static function resolveClaim($token)
    {
        if (!self::isEnabled()) {
            return ['status' => 'disabled'];
        }

        $claim = self::parseToken($token);

        if (!$claim) {
            return ['status' => 'invalid'];
        }

        if ($claim['expires'] < time()) {
            return ['status' => 'expired'];
        }

        // Requiring the claiming account to be signed in is the second half of
        // the proof. Reading the inbox is not enough on its own, and a link
        // forwarded to somebody else does nothing in their hands.
        if ((int) get_current_user_id() !== $claim['user_id']) {
            return ['status' => 'wrong_account'];
        }

        $customer = Customer::where('id', $claim['customer_id'])->first();

        if (!$customer || (int) $customer->user_id !== $claim['user_id']) {
            return ['status' => 'invalid'];
        }

        if (!self::isSame($customer->email, $claim['from'])) {
            return ['status' => 'stale'];
        }

        $user = get_user_by('ID', $claim['user_id']);

        if (!$user || !self::isSame($user->user_email, $claim['to'])) {
            return ['status' => 'stale'];
        }

        if (self::heldByLinkedCustomer($claim['to'], $customer->id)) {
            return ['status' => 'conflict'];
        }

        return [
            'status'   => 'ok',
            'customer' => $customer,
            'email'    => $claim['to']
        ];
    }

    /**
     * Move the record onto the confirmed address and absorb any unlinked record
     * that was already collecting tickets there.
     *
     * @param \FluentSupport\App\Models\Customer $customer
     * @param string $email
     * @return array ['merged' => int, 'tickets' => int]
     */
    public static function apply($customer, $email)
    {
        $previousEmail = $customer->email;

        $merged = 0;
        $moved  = 0;

        foreach (self::unlinkedRecordsHolding($email, $customer->id) as $duplicate) {
            $moved += self::absorb($duplicate, $customer);
            $merged++;
        }

        $customer->email = $email;
        $customer->save();

        // Rotates every ticket hash and writes the activity entry. Shared with
        // the profile_update paths so a proven address move has exactly one
        // set of consequences however it was proven.
        ProfileInfoService::onProvenEmailChange($customer, $previousEmail, 'claimed');

        return ['merged' => $merged, 'tickets' => $moved];
    }

    /**
     * Reparent everything hanging off one customer record onto another, then
     * remove the record it came from.
     *
     * The record is only deleted once it is provably empty. Reparenting is
     * several statements over tables that may well be MyISAM, where a
     * transaction would silently do nothing, so emptiness is re-read from the
     * database rather than assumed from the writes having been attempted. A
     * record that is not empty is left in place for an agent to look at.
     *
     * @param \FluentSupport\App\Models\Customer $source
     * @param \FluentSupport\App\Models\Customer $target
     * @return int Number of tickets moved
     */
    protected static function absorb($source, $target)
    {
        $tickets = Ticket::where('customer_id', $source->id)->get();

        foreach ($tickets as $ticket) {
            $ticket->customer_id = $target->id;
            // Saved one at a time on purpose: Ticket's updating event rotates
            // the hash when customer_id moves, and a mass update on the query
            // builder would skip it, leaving every link already mailed for
            // these tickets working under their new owner.
            $ticket->save();
        }

        Conversation::where('person_id', $source->id)->update(['person_id' => $target->id]);
        Attachment::where('person_id', $source->id)->update(['person_id' => $target->id]);

        Activity::where('person_type', 'customer')
            ->where('person_id', $source->id)
            ->update(['person_id' => $target->id]);

        Activity::where('object_type', 'customer')
            ->where('object_id', $source->id)
            ->update(['object_id' => $target->id]);

        // Internal notifications name the person who caused them, and a customer
        // reply is one of the things that raises one. Left behind, they point at
        // a person row that is about to be deleted.
        //
        // The tables are opt-in: NotificationSettings creates them the first
        // time internal notifications are switched on, and the default is off.
        // Querying them unguarded is a fatal on every site that never enabled
        // the feature.
        $hasNotifications = (new NotificationSettings())->notificationTablesExist();

        if ($hasNotifications) {
            Notification::where('actor_id', $source->id)
                ->update(['actor_id' => $target->id]);
        }

        self::mergeMeta($source, $target);

        /*
         * Fires while one customer record is being folded into another, after
         * core has reparented tickets, conversations, attachments, activity and
         * person meta, and before the emptied record is removed.
         *
         * Anything holding its own rows against a person id must move them here
         * -- Fluent Support Pro moves time tracking on this hook.
         *
         * @since v2.4.1
         * @param \FluentSupport\App\Models\Customer $source Record being absorbed
         * @param \FluentSupport\App\Models\Customer $target Record it is folded into
         */
        do_action('fluent_support/merging_customer_records', $source, $target);

        $sourceId      = $source->id;
        $sourceEmail   = $source->email;
        $ticketsMoved  = count($tickets);

        $remaining = Ticket::where('customer_id', $sourceId)->count()
            + Conversation::where('person_id', $sourceId)->count()
            + Attachment::where('person_id', $sourceId)->count();

        if ($hasNotifications) {
            $remaining += Notification::where('actor_id', $sourceId)->count();
        }

        if ($remaining === 0) {
            $source->deleteAllMeta();
            $source->delete();
        }

        Activity::create([
            'event_type'  => 'fluent_support/customer_records_merged',
            'person_id'   => $target->id,
            'person_type' => 'customer',
            'object_id'   => $target->id,
            'object_type' => 'customer',
            'description' => $remaining === 0
                ? sprintf(
                    // translators: 1: merged customer record id, 2: that record's email address, 3: number of tickets moved
                    __('Merged customer record #%1$s (%2$s) into this one after the address was confirmed. %3$s ticket(s) moved and their shared links were reissued.', 'fluent-support'),
                    $sourceId,
                    $sourceEmail,
                    $ticketsMoved
                )
                : sprintf(
                    // translators: 1: customer record id, 2: that record's email address, 3: number of tickets moved
                    __('Moved %3$s ticket(s) from customer record #%1$s (%2$s) into this one after the address was confirmed. That record still holds other data and was kept.', 'fluent-support'),
                    $sourceId,
                    $sourceEmail,
                    $ticketsMoved
                )
        ]);

        return $ticketsMoved;
    }

    /**
     * Carry across person meta the surviving record does not already have.
     *
     * Keys the target already holds are left alone: the record being kept is
     * the one the customer has been using, and a stray record assembled from an
     * inbound email is not a better source of truth than it. Whatever is left
     * goes with the row, rather than sitting in fs_meta pointing at a person id
     * that no longer exists.
     *
     * @param \FluentSupport\App\Models\Customer $source
     * @param \FluentSupport\App\Models\Customer $target
     * @return void
     */
    protected static function mergeMeta($source, $target)
    {
        $sourceMeta = Meta::where('object_type', 'person_meta')
            ->where('object_id', $source->id)
            ->get();

        if (!$sourceMeta || count($sourceMeta) === 0) {
            return;
        }

        $existing = Meta::where('object_type', 'person_meta')
            ->where('object_id', $target->id)
            ->get()
            ->pluck('key')
            ->toArray();

        foreach ($sourceMeta as $meta) {
            if (in_array($meta->key, $existing, true)) {
                continue;
            }

            $meta->object_id = $target->id;
            $meta->save();
        }
    }

    /**
     * @param \FluentSupport\App\Models\Customer $customer
     * @param string $target
     * @return string
     */
    public static function buildConfirmUrl($customer, $target)
    {
        $baseUrl = Helper::getPortalBaseUrl();

        if (!$baseUrl) {
            return '';
        }

        $token = self::buildToken(
            $customer->id,
            $customer->user_id,
            $customer->email,
            $target,
            time() + self::TTL_SECONDS
        );

        return add_query_arg([
            'fs_view'  => 'email_claim',
            'fs_claim' => $token
        ], $baseUrl);
    }

    /**
     * URL the portal notice points at to request a confirmation email.
     *
     * Nonced because following it sends mail, and a GET that has an effect is
     * otherwise a link an attacker can put in front of a signed-in customer.
     *
     * @return string
     */
    public static function buildRequestUrl()
    {
        $baseUrl = Helper::getPortalBaseUrl();

        if (!$baseUrl) {
            return '';
        }

        return add_query_arg([
            'fs_view'         => 'email_claim',
            'fs_claim_action' => 'send',
            '_wpnonce'        => wp_create_nonce('fs_email_claim_send')
        ], $baseUrl);
    }

    /**
     * @param int $customerId
     * @param int $userId
     * @param string $from Address the record holds now
     * @param string $to Address being claimed
     * @param int $expires
     * @return string
     */
    public static function buildToken($customerId, $userId, $from, $to, $expires)
    {
        // The addresses are percent-encoded before they are joined, because the
        // separator is legal inside one: WordPress's is_email() accepts '|' in
        // the local part, so a@b|c@example.com is a real address somebody can
        // hold. Unencoded it splits the payload into seven fields and the exact
        // field count below rejects the token, which fails closed but leaves
        // that customer unable to use the flow at all.
        $payload = implode('|', [
            (int) $customerId,
            (int) $userId,
            rawurlencode(self::normalize($from)),
            rawurlencode(self::normalize($to)),
            (int) $expires
        ]);

        $raw = $payload . '|' . self::sign($payload);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * @param string $token
     * @return array|null
     */
    protected static function parseToken($token)
    {
        $token = (string) $token;

        if (!$token || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        $raw = base64_decode(strtr($token, '-_', '+/'), true);

        if (!$raw) {
            return null;
        }

        $parts = explode('|', $raw);

        // Exact: the addresses were percent-encoded before joining, so no field
        // can carry the separator.
        if (count($parts) !== 6) {
            return null;
        }

        list($customerId, $userId, $from, $to, $expires, $signature) = $parts;

        $payload = implode('|', [$customerId, $userId, $from, $to, $expires]);

        if (!hash_equals(self::sign($payload), $signature)) {
            return null;
        }

        // Decoded only after the signature has been checked, so what is verified
        // is exactly the string that was signed.
        return [
            'customer_id' => (int) $customerId,
            'user_id'     => (int) $userId,
            'from'        => rawurldecode($from),
            'to'          => rawurldecode($to),
            'expires'     => (int) $expires
        ];
    }

    /**
     * @param string $payload
     * @return string
     */
    protected static function sign($payload)
    {
        return hash_hmac('sha256', self::SIGNING_CONTEXT . '|' . $payload, wp_salt('auth'));
    }

    /**
     * Customer records holding this address that no WordPress account has
     * claimed. These are the rows an address change stranded, and they are the
     * only ones a confirmed claim is allowed to absorb.
     *
     * @param string $email
     * @param int $excludeId
     * @return \FluentSupport\Framework\Support\Collection
     */
    protected static function unlinkedRecordsHolding($email, $excludeId)
    {
        return Customer::where('email', $email)
            ->where('id', '!=', $excludeId)
            ->unclaimed()
            ->orderBy('id', 'ASC')
            ->get();
    }

    /**
     * Whether another WordPress account's customer record already holds this
     * address. That is a conflict between two accounts, not something a
     * confirmation can settle, so nothing is offered and nothing is moved.
     *
     * @param string $email
     * @param int $excludeId
     * @return bool
     */
    protected static function heldByLinkedCustomer($email, $excludeId)
    {
        if (!$email) {
            return true;
        }

        return (bool) Customer::where('email', $email)
            ->where('id', '!=', $excludeId)
            ->claimed()
            ->first();
    }

    /**
     * @param string $email
     * @return string
     */
    protected static function normalize($email)
    {
        return strtolower(trim((string) $email));
    }

    /**
     * @param string $left
     * @param string $right
     * @return bool
     */
    protected static function isSame($left, $right)
    {
        return self::normalize($left) === self::normalize($right);
    }
}
