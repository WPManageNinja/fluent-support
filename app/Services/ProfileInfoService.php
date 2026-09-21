<?php

namespace FluentSupport\App\Services;

use FluentSupport\App\Models\Activity;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Ticket;

class ProfileInfoService
{
    public static function getProfileExtraWidgets( $customer )
    {
        $widgets = [];
        /*
         * Filter customer profile widgets
         *
         * @since v1.0.0
         * @param array $widgets
         * @param object|array  $customer
         *
         * @return void
         */
        $widgets = apply_filters('fluent_support/customer_extra_widgets', $widgets, $customer);
        return $widgets;
    }

    // This method is linked with 'profile_update' action & it will trigger when user update profile
    public function onWPProfileUpdate($userId, $userOldData, $userUpdatedData)
    {
        if (!$userId || !is_array($userUpdatedData)) {
            return false;
        }

        // Matching on user_id only is what keeps this safe: a WordPress account
        // can never reach a customer row it is not already linked to, so this
        // can only ever rewrite the requester's own record.
        $customers = Customer::where('user_id', $userId)->get();

        if (!$customers || count($customers) === 0) {
            return false;
        }

        $keys = ['first_name', 'last_name', 'user_email'];

        if (array_diff_key(array_flip($keys), $userUpdatedData)) {
            return false;
        }

        // wp_insert_user() takes slashed data, and profile_update hands that same
        // array straight on, so a name like O'Brien arrives as O\'Brien. Written
        // through unchanged it reaches the customer record with a literal
        // backslash, which is what the customer then sees in the portal and on
        // every notification addressed to them.
        $userUpdatedData = wp_unslash($userUpdatedData);

        $email = $userUpdatedData['user_email'];

        // Any plugin calling wp_update_user() for any reason fires this hook, so
        // most of the time the account address has not moved at all. Reading the
        // pending-change meta and running a capability check on every one of
        // those is work for nothing.
        //
        // It also keeps the administrator path honest. Without this, an
        // administrator editing somebody's first name would move the support
        // address onto whatever unverified address the account happened to be
        // carrying, because the check below only compares the customer record to
        // the account. Authority is granted for the change the actor actually
        // made, not for a divergence somebody else created earlier.
        // Unslashed on both sides. wp_insert_user() slashes the previous address
        // on purpose -- "Slash current user email to compare it later with
        // slashed new user email" -- so comparing it against the unslashed new
        // one reads an address like o'brien@example.com as changed on every
        // update, and an administrator editing only a name would move the
        // support address onto it.
        $previousAccountEmail = is_object($userOldData) && isset($userOldData->user_email)
            ? wp_unslash($userOldData->user_email)
            : '';

        // An unknown previous address falls through to the full check rather
        // than silently skipping it.
        $accountEmailMoved = !$previousAccountEmail
            || !self::isSameEmail($previousAccountEmail, $email);

        // Only a proven change may move the address that support notifications
        // and signed ticket links are delivered to. Names are cosmetic and are
        // always synced.
        $changeReason = $accountEmailMoved ? self::provenEmailChangeReason($userId, $email) : '';
        $emailIsProven = (bool) $changeReason;

        foreach ($customers as $customer) {
            $data = [
                'first_name' => $userUpdatedData['first_name'],
                'last_name'  => $userUpdatedData['last_name'],
            ];

            $previousEmail = $customer->email;

            // Never move this row onto an address another customer already
            // holds. Duplicate emails make every later email-based match
            // ambiguous, and mail piping would then thread onto whichever row
            // happens to have the lower id.
            $emailMoved = $emailIsProven
                && !self::isSameEmail($previousEmail, $email)
                && !self::isEmailHeldByAnotherCustomer($email, $customer->id);

            if ($emailMoved) {
                $data['email'] = $email;
            }

            $customer->fill($data);
            $customer->save();

            if ($emailMoved) {
                self::onProvenEmailChange($customer, $previousEmail, $changeReason);
            }
        }

        return true;
    }

    /**
     * How the email change being applied right now was authorised, or an empty
     * string when it was not.
     *
     * Two paths count. The account holder can prove the new address by opening
     * WordPress's confirmation link, and somebody with authority over the
     * account can change it on their behalf. Everything else, which is most
     * things, leaves the customer's contact address where it is.
     *
     * @param int $userId
     * @param string $newEmail
     * @return string 'verified', 'administrator', or ''
     */
    public static function provenEmailChangeReason($userId, $newEmail)
    {
        if (self::isWpConfirmedEmailChange($userId, $newEmail)) {
            return 'verified';
        }

        if (self::isAdminAuthorizedEmailChange($userId)) {
            return 'administrator';
        }

        return '';
    }

    /**
     * Whether an administrator is changing this account's address on its
     * owner's behalf.
     *
     * Authority over the WordPress account is the test. Someone who may edit
     * that account may also move the address Fluent Support writes to, on the
     * basis that the customer record's address is derived from the account in
     * the first place.
     *
     * Worth knowing what that admits on a WooCommerce store. WooCommerce grants
     * edit_users to shop_manager through a user_has_cap filter and then narrows
     * it, in wc_modify_map_meta_cap(), to accounts holding the 'customer' role,
     * which is the population Fluent Support serves. A shop manager therefore
     * passes this check for any customer, whether or not they hold a single
     * Fluent Support permission, and can redirect that customer's support
     * notifications. Narrowing this to holders of fst_sensitive_data, the
     * permission CustomerPolicy uses to gate editing a customer record, would
     * close that; it is a one-line change here.
     *
     * The address is authorised but still unproven, which is why this path
     * rotates ticket hashes exactly as the verified one does, so links already
     * delivered to the previous inbox stop working.
     *
     * Excluding the actor's own account matters: an administrator changing their
     * own address goes through the same unverified endpoints as anybody else and
     * gets no special treatment.
     *
     * @param int $userId
     * @return bool
     */
    public static function isAdminAuthorizedEmailChange($userId)
    {
        $actorId = get_current_user_id();

        if (!$actorId || $actorId === (int) $userId) {
            return false;
        }

        return current_user_can('edit_user', (int) $userId);
    }

    /**
     * Whether the email change being applied to this user right now went through
     * WordPress's own click-through confirmation.
     *
     * wp-admin/user-edit.php calls wp_update_user() and only deletes the
     * _new_email meta afterwards, so the pending record is still readable while
     * profile_update fires. The record on its own proves nothing: submitting the
     * profile form writes it without any click, so an unverified change made
     * through REST could arrive with a matching address already sitting in meta.
     * Only the hash coming back in the request shows the link was opened from
     * the inbox the mail was delivered to.
     *
     * @param int $userId
     * @param string $newEmail
     * @return bool
     */
    public static function isWpConfirmedEmailChange($userId, $newEmail)
    {
        // Mirrors the condition core applies the confirmed change under.
        // wp-admin/user-edit.php runs the confirmation branch only when
        // IS_PROFILE_PAGE is truthy, and that constant is defined nowhere but
        // profile.php and user-edit.php, where it means "the user is editing
        // their own account". Requiring it here means a replayed hash cannot be
        // honoured from REST, WP-CLI, or an administrator editing somebody else.
        if (!defined('IS_PROFILE_PAGE') || !IS_PROFILE_PAGE) {
            return false;
        }

        $pending = get_user_meta($userId, '_new_email', true);

        if (!is_array($pending) || empty($pending['hash']) || empty($pending['newemail'])) {
            return false;
        }

        if (!self::isSameEmail($pending['newemail'], $newEmail)) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this compares a secret delivered to the user's own inbox; it does not act on request state
        $submitted = isset($_GET['newuseremail'])
            ? sanitize_text_field(wp_unslash($_GET['newuseremail']))
            : '';

        return $submitted && hash_equals($pending['hash'], $submitted);
    }

    /**
     * Apply the consequences of a customer's contact address moving to one that
     * has been proven.
     *
     * Shared by every path that is allowed to move an address -- WordPress's own
     * confirmation, an administrator with rights over the account, and the
     * portal claim flow in EmailClaimService -- so a proven move has exactly one
     * set of consequences however it was proven.
     *
     * @param \FluentSupport\App\Models\Customer $customer
     * @param string $previousEmail
     * @param string $reason 'verified', 'administrator', 'claimed' or 'agent'
     * @return void
     */
    public static function onProvenEmailChange($customer, $previousEmail, $reason = 'verified')
    {
        // Signed ticket links already delivered to the previous inbox keep
        // authorizing read, reply, close and reopen on these tickets until the
        // hash they carry stops matching.
        $tickets = Ticket::where('customer_id', $customer->id)->get();

        foreach ($tickets as $ticket) {
            $ticket->hash = bin2hex(random_bytes(16));
            $ticket->save();
        }

        Activity::create([
            'event_type'  => 'fluent_support/customer_email_changed',
            'person_id'   => $customer->id,
            'person_type' => 'customer',
            'object_id'   => $customer->id,
            'object_type' => 'customer',
            'description' => sprintf(
                self::emailChangeDescription($reason),
                $previousEmail,
                $customer->email
            )
        ]);

        /*
         * Fires after a customer's contact address moves to a newly proven one.
         *
         * @since v2.4.1
         * @param \FluentSupport\App\Models\Customer $customer
         * @param string $previousEmail
         * @param string $reason
         */
        do_action('fluent_support/customer_email_verified_change', $customer, $previousEmail, $reason);
    }

    /**
     * Activity log wording for each way an address can be proven.
     *
     * @param string $reason
     * @return string A sprintf format taking the previous and new addresses
     */
    protected static function emailChangeDescription($reason)
    {
        if ($reason === 'administrator') {
            // translators: 1: previous email address, 2: new email address
            return __('Contact email changed from %1$s to %2$s by an administrator editing the WordPress account.', 'fluent-support');
        }

        if ($reason === 'agent') {
            // translators: 1: previous email address, 2: new email address
            return __('Contact email changed from %1$s to %2$s by a support agent editing the customer.', 'fluent-support');
        }

        if ($reason === 'claimed') {
            // translators: 1: previous email address, 2: new email address
            return __('Contact email changed from %1$s to %2$s, confirmed by the account holder from the support portal.', 'fluent-support');
        }

        // translators: 1: previous email address, 2: new email address
        return __('Contact email changed from %1$s to %2$s, confirmed through WordPress email verification.', 'fluent-support');
    }

    /**
     * @param string $left
     * @param string $right
     * @return bool
     */
    protected static function isSameEmail($left, $right)
    {
        return strtolower(trim((string) $left)) === strtolower(trim((string) $right));
    }

    /**
     * Adopt customer rows that already carry this address but are not linked to
     * a WordPress account yet.
     *
     * Registration is one of the few moments where an address may be treated as
     * belonging to the account: WordPress refuses to register an address that
     * another user already holds, and the credentials it sends go to that inbox.
     * A later email change carries no such proof, which is why binding is done
     * here rather than on every profile update.
     *
     * @param int $userId
     * @return void
     */
    public function onWPUserRegister($userId)
    {
        $user = $userId ? get_user_by('ID', $userId) : false;

        if (!$user || !$user->user_email) {
            return;
        }

        /*
         * Filter whether a newly registered WordPress account adopts unlinked
         * customer rows that already carry its email address. Sites that let
         * visitors register with a self-chosen password may want this off.
         *
         * @since v2.4.1
         * @param bool $shouldLink
         * @param \WP_User $user
         */
        if (!apply_filters('fluent_support/link_customer_on_user_register', true, $user)) {
            return;
        }

        Customer::where('email', $user->user_email)
            ->unclaimed()
            ->update(['user_id' => $user->ID]);
    }

    /**
     * @param string $email
     * @param int $customerId
     * @return bool
     */
    protected static function isEmailHeldByAnotherCustomer($email, $customerId)
    {
        if (!$email) {
            return true;
        }

        return (bool) Customer::where('email', $email)
            ->where('id', '!=', $customerId)
            ->first();
    }
}
