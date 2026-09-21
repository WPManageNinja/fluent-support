<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\App;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Blocks\BlockHelper;
use FluentSupport\App\Services\EmailClaimService;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\TranslationStrings;
use FluentSupport\App\Vite;
use FluentSupport\Framework\Support\Arr;
use FluentSupportPro\App\Services\ProHelper;

class CustomerPortalHandler
{
    public function renderPortal($args = [])
    {
        /**
         * This hook filter customer portal access permission error message.
         * If a customer has no access to the portal, then the message will be displayed.
         * @param string $invalidPermissionMessage
         * @return string
         * @since 1.6.0
         */
        $invalidPermissionMessage = apply_filters(
            'fluent_support/customer_portal_invalid_permission_message',
            esc_html__('You don\'t have permission to access customer support portal', 'fluent-support')
        );

        $person = Helper::getCurrentCustomer();

        if (!$person && PermissionManager::currentUserPermissions()) {
            $adminPortalUrl = Helper::getPortalAdminBaseUrl();

            /**
             * This hook filter is responsible for generating error message
             * when a support staff try to access customer portal
             * @param string $agentPermissionErrMessage
             * @return string
             * @since 1.6.0
             */
            $msg = __('Customer Portal is only accessible by Customers. Looks like you are a support staff', 'fluent-support');
            $agentPermissionErrMessage = apply_filters(
                'fluent_support/customer_portal_agent_permission_error_message',
                $msg
            );
            return '<div style="text-align: center;"><h3>' . esc_html($agentPermissionErrMessage) . '</h3><a href="' . esc_url($adminPortalUrl) . '">' . esc_html__('Go to Support Admin Page', 'fluent-support') . '</a></div>';
        } else if ($this->hasCustomerPortalAccess()) {

            /*
            * Filter customer portal access settings
            *
            * @since v1.0.0
            *
            * @param array $canAccess
            */
            $canAccess = apply_filters('fluent_support/user_portal_access_config', [
                'status' => true,
                'message' => $invalidPermissionMessage
            ]);

            if (empty($canAccess['status'])) {
                $invalidPermissionMessage = Arr::get($canAccess, 'message', $invalidPermissionMessage);
                return '<div id="fluent_support_client_app" style="text-align: center;"><h3 class="fs_customer_restriction">' . esc_html($invalidPermissionMessage) . '</h3></div>';
            }

            if (!$person) {
                $this->maybeCreateCustomer();
            }

            if (isset($args['attributes']) && !empty($args['attributes'])) {
                BlockHelper::processAttributesAndPrepareStyle($args['attributes']);
            }

            $this->enqueueScripts();

            return $this->renderEmailClaimNotice($person)
                . '<div id="fluent_support_client_app"><h3 class="fs_loading_text">' . __('Loading Customer Portal. Please wait...', 'fluent-support') . '</h3></div>';
        } else {

            $businessSettings = Helper::getBusinessSettings();
            $loggedInMessage = Arr::get($businessSettings, 'login_message', '');

            $loggedInMessage = str_replace('[fluent_support_portal]', '', $loggedInMessage);

            // Pass portal's show-signup / show-reset-password to auth/login shortcodes
            // by temporarily overriding defaults via the existing filter.
            $overrideDefaults = function ($defaults) use ($args) {
                $defaults['show-signup']         = Arr::get($args, 'show-signup', 'true');
                $defaults['show-reset-password'] = Arr::get($args, 'show-reset-password', 'true');
                return $defaults;
            };

            $loggedInMessage = wp_kses_post($loggedInMessage);

            add_filter('fluent_support/auth_shortcode_defaults', $overrideDefaults);
            $result = do_shortcode($loggedInMessage);

            remove_filter('fluent_support/auth_shortcode_defaults', $overrideDefaults);

            return $result;
        }
    }

    /**
     * Notice shown above the portal when the customer's support address no
     * longer matches the address on their WordPress account.
     *
     * Rendered server side, outside the element the portal app mounts into, so
     * it survives the Vue app taking over and needs no asset build.
     *
     * @param \FluentSupport\App\Models\Customer|null $customer Already resolved by the caller
     *
     * The notice deliberately says nothing about what is waiting on the other
     * address. Somebody who points their account at an address they do not own
     * sees this same notice, and a ticket count would tell them whether that
     * person is a customer here.
     *
     * @return string
     */
    protected function renderEmailClaimNotice($customer = null)
    {
        $html = $this->renderEmailClaimResult();

        // The caller already resolved this record; handing it over keeps the
        // check free of an extra query on every portal render.
        $divergence = EmailClaimService::getDivergence($customer);

        if (!$divergence) {
            return $html;
        }

        $requestUrl = EmailClaimService::buildRequestUrl();

        if (!$requestUrl) {
            return $html;
        }

        $message = sprintf(
            // translators: 1: address support mail currently goes to, 2: the account's current address
            __('Support messages are being sent to %1$s, but your account email is now %2$s.', 'fluent-support'),
            '<strong>' . esc_html($divergence['from']) . '</strong>',
            '<strong>' . esc_html($divergence['to']) . '</strong>'
        );

        return $html
            . '<div class="fs_email_claim_notice" style="border: 1px solid #dcdcde; border-left: 4px solid #2271b1; background: #fff; padding: 12px 16px; margin-bottom: 16px;">'
            . '<p style="margin: 0 0 8px;">' . wp_kses($message, ['strong' => []]) . '</p>'
            . '<p style="margin: 0 0 12px; opacity: .8;">' . esc_html__('Confirm the new address to move your support messages and bring across any tickets you opened from it. We will email a link to that address.', 'fluent-support') . '</p>'
            . '<a class="fs_email_claim_button" style="display: inline-block; background: #2271b1; color: #fff; text-decoration: none; padding: 8px 20px; border-radius: 3px;" href="' . esc_url($requestUrl) . '">'
            . esc_html__('Send confirmation email', 'fluent-support')
            . '</a></div>';
    }

    /**
     * @return string
     */
    protected function renderEmailClaimResult()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- selects a fixed message to display; performs no action
        $result = isset($_GET['fs_claim_result']) ? sanitize_key(wp_unslash($_GET['fs_claim_result'])) : '';

        if (!$result) {
            return '';
        }

        $user = wp_get_current_user();
        $accountEmail = $user ? $user->user_email : '';

        $messages = [
            'sent'         => sprintf(
                // translators: %s is the email address the confirmation was sent to
                __('Confirmation email sent to %s. Open the link in that inbox to finish.', 'fluent-support'),
                $accountEmail
            ),
            'confirmed'    => __('Your support email address has been updated.', 'fluent-support'),
            'throttled'    => __('Too many confirmation emails have been requested for this address. Please try again later.', 'fluent-support'),
            'expired'      => __('That confirmation link has expired. You can request a new one below.', 'fluent-support'),
            'stale'        => __('That confirmation link is no longer valid, usually because it was already used or the address changed again. You can request a new one below.', 'fluent-support'),
            'wrong_account' => __('That confirmation link belongs to a different account. Sign in as that account and open the link again.', 'fluent-support'),
            'conflict'     => __('Another support profile already uses that address, so it cannot be moved automatically. Please contact support.', 'fluent-support'),
            'invalid'      => __('That confirmation link could not be read. You can request a new one below.', 'fluent-support'),
            'send_failed'  => __('We could not send the confirmation email just now. Please try again in a few minutes.', 'fluent-support')
        ];

        if (empty($messages[$result])) {
            return '';
        }

        $isGood = in_array($result, ['sent', 'confirmed'], true);

        return '<div class="fs_email_claim_result" style="border: 1px solid #dcdcde; border-left: 4px solid ' . ($isGood ? '#00a32a' : '#d63638') . '; background: #fff; padding: 12px 16px; margin-bottom: 16px;">'
            . '<p style="margin: 0;">' . esc_html($messages[$result]) . '</p></div>';
    }

    public function hasCustomerPortalAccess()
    {
        $userId = get_current_user_id();

        if ($userId) {
            return true;
        }

        return $this->isSignedTicketView();
    }

    protected function isSignedTicketView()
    {
        if (!Helper::isPublicSignedTicketEnabled()) {
            return false;
        }

        return isset($_REQUEST['fs_view']) && $_REQUEST['fs_view'] == 'ticket' && isset($_REQUEST['support_hash']) && isset($_REQUEST['ticket_id']);
    }

    private function maybeCreateCustomer()
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return false;
        }

        $person = Helper::getCurrentPerson();
        if ($person) {
            return $person;
        }

        $user = get_user_by('ID', $userId);

        $request = App::request();

        $onBehalf = [
            'user_id' => $user->ID,
            'email' => $user->user_email,
            'last_ip_address' => $request->getIp()
        ];

        $customFields = Helper::getBusinessSettings('custom_registration_form_field');

        if (!empty($customFields)) {
            $onBehalf = $this->processCustomFields($customFields, $onBehalf);
        }

        return Customer::maybeCreateCustomer($onBehalf);
    }

    private function processCustomFields($customFields, $onBehalf)
    {
        $userMeta = get_user_meta(get_current_user_id());
        $customData = [];

        foreach ($customFields as $field) {
            if (isset($userMeta[$field])) {
                $customData[$field] = is_array($userMeta[$field]) ? $userMeta[$field][0] : $userMeta[$field];
            }
        }

        if ($customData) {
            $onBehalf = array_merge($onBehalf, $customData);
        }

        return $onBehalf;
    }

    public function enqueueScripts()
    {
        $app = App::getInstance();

        $ns = $app->config->get('app.rest_namespace');
        $v = $app->config->get('app.rest_version');
        $slug = $app->config->get('app.slug');

        $restInfo = [
            'base_url' => esc_url_raw(rest_url()),
            'url' => rest_url($ns . '/' . $v . '/customer-portal'),
            'nonce' => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version' => $v,
        ];

        $assets = $app['url.assets'];


        $i18ns = TranslationStrings::getPortalStrings();

        $i18ns['allowed_files_and_size'] = Helper::getFileUploadMessage();

        $data = [
            'rest' => $restInfo,
            'nonce' => wp_create_nonce($slug),
            'ticket_statuses' => Helper::ticketStatuses(),
            'support_products' => Product::select(['id', 'title'])->orderedByTitle()->get(),
            'product_field_required' => Helper::isProductRequired(),
            'customer_ticket_priorities' => Helper::customerTicketPriorities(),
            'view_tickets_url' => '#/',
            'i18n' => $i18ns,
            'fallback_image' => $assets . 'images/icons/file.svg',
            'has_file_upload' => !!Helper::ticketAcceptedFileMiles(),
            'has_rich_text_editor' => true,
            'customer_status' => static::customerStatus()->status ?? static::customerStatus(),
            'max_file_upload' => Helper::getBusinessSettings('max_file_upload', 3),
            'agent_feedback_rating' => Helper::getBusinessSettings('agent_feedback_rating', 'no'),
            'can_view_private_ticket_number' => current_user_can('manage_options') || PermissionManager::currentUserCan([
                    'fst_view_tickets',
                    'fst_manage_own_tickets',
                    'fst_manage_unassigned_tickets',
                    'fst_manage_other_tickets'
                ]),
        ];

        if ($this->isSignedTicketView()) {
            $data['intended_ticket_hash'] = sanitize_text_field($_REQUEST['support_hash']);
            $data['view_tickets_url'] = Helper::getPortalBaseUrl() . '/#';
        } else {
            add_filter('user_can_richedit', '__return_true');
        }

        $reCaptchaSettings = ReCaptchaHandler::getSettings();
        $data['recaptcha'] = ['enabled' => false];

        if (ReCaptchaHandler::isRecaptchaApplicable('ticket_form', $reCaptchaSettings)) {
            $recaptchaVersion = $reCaptchaSettings['reCaptcha_version'] ?? 'recaptcha_v2';
            $siteKey = $reCaptchaSettings['siteKey'] ?? '';

            $data['recaptcha'] = [
                'enabled' => true,
                'version' => $recaptchaVersion,
                'site_key' => $siteKey,
            ];
        }

        /*
         * Filter customer portal localize javascript data
         *
         *  @since v1.0.0
         *
         * @param array $data
         */
        $data = apply_filters('fluent_support/customer_portal_vars', $data);

        if (!empty($data['has_rich_text_editor'])) {
            wp_tinymce_inline_scripts();
            wp_enqueue_editor();
        }

        // Inject Vite HMR client for dev mode
        add_action('wp_head', function () {
            Vite::injectViteClient();
        }, 1);

        wp_enqueue_script('dompurify', $assets . 'libs/purify/purify.min.js', [], '3.4.13');
        wp_enqueue_script('fs_tk_customer_portal', Vite::getEnqueuePath('portal/js/app.js'), ['jquery'], FLUENT_SUPPORT_VERSION, true);

        if (is_rtl()) {
            wp_enqueue_style('fs_tk_customer_portal_rtl', $assets . 'portal/css/app-rtl.css', [], FLUENT_SUPPORT_VERSION);
        } else {
            wp_enqueue_style('fs_tk_customer_portal', Vite::getEnqueuePath('portal/css/app.css'), [], FLUENT_SUPPORT_VERSION);
        }

        wp_localize_script('fs_tk_customer_portal', 'fs_customer_portal', $data);
    }

    protected static function customerStatus()
    {
        $user = get_current_user_id();

        if (!$user && isset($_REQUEST['support_hash']) && isset($_REQUEST['ticket_id']) && isset($_REQUEST['fs_view']) && $_REQUEST['fs_view'] == 'ticket') {
            return true;
        }

        return Customer::where('user_id', $user)->select(['status'])->first();
    }
}
