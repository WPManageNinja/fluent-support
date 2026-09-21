<?php
/**
 * Permission-smoke manifest: every mutating (POST/PUT/PATCH/DELETE) REST route
 * of the Fluent Support plugin family, under namespace `fluent-support/v2`.
 *
 * Generated: 2026-08-08
 * Sources of truth:
 *   - Core: app/Http/Routes/api.php
 *   - Pro:  ../fluent-support-pro/app/Http/routes.php
 *
 * The runner dispatches each route anonymously (expects 401) and as a subscriber
 * (expects 403). Controllers never execute — a `rest_dispatch_request` fuse blocks
 * them — so payloads only need to be syntactically plausible.
 *
 * `{placeholder}` tokens are kept literally; the runner substitutes 2147483647.
 * Entries carrying `skip` are public by design and are excluded visibly rather
 * than silently dropped.
 *
 * Do not hand-edit without re-reconciling the `expected` counts below.
 */

return [
    'expected' => [
        'POST'   => 96,
        'PUT'    => 15,
        'DELETE' => 18,
    ],

    'routes' => [

        // ---- mailboxes (core, AdminSettingsPolicy) ----
        [
            'label'   => 'mailboxes: save',
            'method'  => 'POST',
            'route'   => '/mailboxes',
            'payload' => [],
        ],
        [
            'label'   => 'mailboxes: update',
            'method'  => 'PUT',
            'route'   => '/mailboxes/{id}',
            'payload' => [],
        ],
        [
            'label'   => 'mailboxes: delete',
            'method'  => 'DELETE',
            'route'   => '/mailboxes/{id}',
            'payload' => [],
        ],
        [
            'label'   => 'mailboxes: move tickets',
            'method'  => 'PUT',
            'route'   => '/mailboxes/{id}/move_tickets',
            'payload' => [],
        ],
        [
            'label'   => 'mailboxes: save email settings',
            'method'  => 'PUT',
            'route'   => '/mailboxes/{id}/email_settings',
            'payload' => [],
        ],
        [
            'label'   => 'mailboxes: set default',
            'method'  => 'PUT',
            'route'   => '/mailboxes/{id}/set_default',
            'payload' => [],
        ],

        // ---- tickets (core, AgentTicketPolicy) ----
        [
            'label'   => 'tickets: create',
            'method'  => 'POST',
            'route'   => '/tickets',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: store or update label search',
            'method'  => 'POST',
            'route'   => '/tickets/label-search',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: delete label search',
            'method'  => 'DELETE',
            'route'   => '/tickets/{label_search_id}/label-search',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: create response',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/responses',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: create or update draft',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/draft',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: delete draft',
            'method'  => 'DELETE',
            'route'   => '/tickets/{draft_id}/draft',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: remove live activity',
            'method'  => 'DELETE',
            'route'   => '/tickets/{ticket_id}/live_activity',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: update response',
            'method'  => 'PUT',
            'route'   => '/tickets/{ticket_id}/responses/{response_id}',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: approve draft response',
            'method'  => 'PUT',
            'route'   => '/tickets/{ticket_id}/approve_draft_response/{response_id}',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: delete response',
            'method'  => 'DELETE',
            'route'   => '/tickets/{ticket_id}/responses/{response_id}',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: update property',
            'method'  => 'PUT',
            'route'   => '/tickets/{ticket_id}/property',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: add tag',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/tags',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: detach tag',
            'method'  => 'DELETE',
            'route'   => '/tickets/{ticket_id}/tags/{tag_id}',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: close',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/close',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: delete',
            'method'  => 'DELETE',
            'route'   => '/tickets/{ticket_id}/delete',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: re-open',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/re-open',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: change customer',
            'method'  => 'PUT',
            'route'   => '/tickets/{ticket_id}/change-customer',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: create fluent-booking link',
            'method'  => 'POST',
            'route'   => '/tickets/{ticket_id}/fluent-booking/booking-link',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: bulk actions',
            'method'  => 'POST',
            'route'   => '/tickets/bulk-actions',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: bulk reply',
            'method'  => 'POST',
            'route'   => '/tickets/bulk-reply',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: sync fluentcrm tags',
            'method'  => 'POST',
            'route'   => '/tickets/sync-fluentcrm-tags',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: sync fluentcrm lists',
            'method'  => 'POST',
            'route'   => '/tickets/sync-fluentcrm-lists',
            'payload' => [],
        ],
        [
            'label'   => 'tickets: create fluent-boards task',
            'method'  => 'POST',
            'route'   => '/tickets/fluent-boards/stages',
            'payload' => [],
        ],

        // ---- notifications (core, AgentTicketPolicy) ----
        [
            'label'   => 'notifications: mark all read',
            'method'  => 'POST',
            'route'   => '/notifications/mark-all-read',
            'payload' => [],
        ],
        [
            'label'   => 'notifications: mark read',
            'method'  => 'POST',
            'route'   => '/notifications/{notification_id}/mark-read',
            'payload' => [],
        ],

        // ---- products (core, AdminSettingsPolicy) ----
        [
            'label'   => 'products: create',
            'method'  => 'POST',
            'route'   => '/products',
            'payload' => [],
        ],
        [
            'label'   => 'products: create with id',
            'method'  => 'POST',
            'route'   => '/products/{product_id}',
            'payload' => [],
        ],
        [
            'label'   => 'products: update',
            'method'  => 'PUT',
            'route'   => '/products/{product_id}',
            'payload' => [],
        ],
        [
            'label'   => 'products: delete',
            'method'  => 'DELETE',
            'route'   => '/products/{product_id}',
            'payload' => [],
        ],

        // ---- ticket_file_upload (core, PortalPolicy) ----
        [
            'label'              => 'uploader: ticket file upload',
            'method'             => 'POST',
            'route'              => '/ticket_file_upload',
            'payload'            => [],
            // PortalPolicy::verifyRequest allows any logged-in user by design.
            'subscriber_allowed' => true,
        ],

        // ---- settings (core, AdminSettingsPolicy) ----
        [
            'label'   => 'settings: save',
            'method'  => 'POST',
            'route'   => '/settings',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save integration',
            'method'  => 'POST',
            'route'   => '/settings/integration',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save slack integration',
            'method'  => 'POST',
            'route'   => '/settings/slack-integration',
            'payload' => [],
        ],
        [
            'label'   => 'settings: setup portal',
            'method'  => 'POST',
            'route'   => '/settings/setup',
            'payload' => [],
        ],
        [
            'label'   => 'settings: setup installation',
            'method'  => 'POST',
            'route'   => '/settings/setup-installation',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save recaptcha',
            'method'  => 'POST',
            'route'   => '/settings/recaptcha-settings',
            'payload' => [],
        ],
        [
            'label'   => 'settings: install fluentcrm',
            'method'  => 'POST',
            'route'   => '/settings/install-fluentcrm',
            'payload' => [],
        ],
        [
            'label'   => 'settings: update remote upload driver',
            'method'  => 'POST',
            'route'   => '/settings/update-remote-upload-driver',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save ai provider',
            'method'  => 'POST',
            'route'   => '/settings/ai-integration',
            'payload' => [],
        ],
        [
            'label'   => 'settings: disconnect ai provider',
            'method'  => 'POST',
            'route'   => '/settings/ai-integration/disconnect',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save fluent bot integration',
            'method'  => 'POST',
            'route'   => '/settings/fluent-bot-integration',
            'payload' => [],
        ],
        [
            'label'   => 'settings: save fluent bot presets',
            'method'  => 'POST',
            'route'   => '/settings/fluent-bot-presets',
            'payload' => [],
        ],
        [
            'label'   => 'settings: mcp toggle',
            'method'  => 'POST',
            'route'   => '/settings/mcp/toggle',
            'payload' => [],
        ],
        [
            'label'   => 'settings: mcp install adapter',
            'method'  => 'POST',
            'route'   => '/settings/mcp/install-adapter',
            'payload' => [],
        ],

        // ---- agents (core, AgentPolicy) ----
        // AgentPolicy::guardManageOptions deliberately THROWS for non-admins
        // (FS-SEC-003), so both identities get 403/"Permission Callback Error".
        [
            'label'         => 'agents: add',
            'method'        => 'POST',
            'route'         => '/agents',
            'payload'       => [],
            'throws_denial' => true,
        ],
        [
            'label'         => 'agents: update',
            'method'        => 'PUT',
            'route'         => '/agents/{agent_id}',
            'payload'       => [],
            'throws_denial' => true,
        ],
        [
            'label'         => 'agents: delete',
            'method'        => 'DELETE',
            'route'         => '/agents/{agent_id}',
            'payload'       => [],
            'throws_denial' => true,
        ],
        [
            'label'   => 'agents: update avatar',
            'method'  => 'POST',
            'route'   => '/agents/avatar/{agent_id}',
            'payload' => [],
        ],
        [
            'label'   => 'agents: reset avatar',
            'method'  => 'POST',
            'route'   => '/agents/reset_avatar/{agent}',
            'payload' => [],
        ],

        // ---- agent-groups (core, AdminSensitivePolicy) ----
        [
            'label'   => 'agent-groups: create',
            'method'  => 'POST',
            'route'   => '/agent-groups',
            'payload' => [],
        ],
        [
            'label'   => 'agent-groups: update',
            'method'  => 'PUT',
            'route'   => '/agent-groups/{group_id}',
            'payload' => [],
        ],
        [
            'label'   => 'agent-groups: delete',
            'method'  => 'DELETE',
            'route'   => '/agent-groups/{group_id}',
            'payload' => [],
        ],

        // ---- customers (core, AdminSensitivePolicy) ----
        [
            'label'   => 'customers: create',
            'method'  => 'POST',
            'route'   => '/customers',
            'payload' => [],
        ],
        [
            'label'   => 'customers: update',
            'method'  => 'PUT',
            'route'   => '/customers/{customer_id}',
            'payload' => [],
        ],
        // CustomerPolicy::guardCustomerDeletion deliberately THROWS rather than
        // returning false, so deletions surface as "Permission Callback Error"
        // with a specific message. The status still follows the exception code
        // per identity (401 anonymous, 403 subscriber).
        [
            'label'         => 'customers: delete',
            'method'        => 'DELETE',
            'route'         => '/customers/{customer_id}',
            'payload'       => [],
            'throws_denial' => true,
        ],
        [
            'label'         => 'customers: bulk delete',
            'method'        => 'DELETE',
            'route'         => '/customers/bulk-delete',
            'payload'       => [],
            'throws_denial' => true,
        ],
        [
            'label'   => 'customers: update profile image',
            'method'  => 'POST',
            'route'   => '/customers/profile_image/{customer_id}',
            'payload' => [],
        ],
        [
            'label'   => 'customers: reset avatar',
            'method'  => 'POST',
            'route'   => '/customers/reset_avatar/{customer}',
            'payload' => [],
        ],

        // ---- customer-portal (core, PortalPolicy) ----
        // PortalPolicy::verifyRequest allows ANY logged-in user by design (the
        // portal is customer-facing); subscriber legs assert the gate passes.
        [
            'label'              => 'customer-portal: create ticket',
            'method'             => 'POST',
            'route'              => '/customer-portal/tickets',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: create response',
            'method'             => 'POST',
            'route'              => '/customer-portal/tickets/{ticket_id}/responses',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: close ticket',
            'method'             => 'POST',
            'route'              => '/customer-portal/tickets/{ticket_id}/close',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: re-open ticket',
            'method'             => 'POST',
            'route'              => '/customer-portal/tickets/{ticket_id}/re-open',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: ticket file upload',
            'method'             => 'POST',
            'route'              => '/customer-portal/ticket_file_upload',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: agent feedback rating',
            'method'             => 'POST',
            'route'              => '/customer-portal/tickets/{ticket_id}/agent-feedback',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],
        [
            'label'              => 'customer-portal: logout',
            'method'             => 'POST',
            'route'              => '/customer-portal/logout',
            'payload'            => [],
            'subscriber_allowed' => true,
        ],

        // ---- public (core, PublicPolicy) ----
        [
            'label'   => 'public: telegram bot webhook',
            'method'  => 'POST',
            'route'   => '/public/telegram_bot_response/{token}',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],
        [
            'label'   => 'public: slack event webhook',
            'method'  => 'POST',
            'route'   => '/public/slack_response/{token}',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],

        // ---- fluent-bot (core, AgentTicketPolicy) ----
        [
            'label'   => 'fluent-bot: create feedback',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/feedback',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: delete feedback',
            'method'  => 'DELETE',
            'route'   => '/fluent-bot/{id}/feedback/{feedback_id}',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: generate response',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/generate-response',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: generate stream response',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/generate-stream-response',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: get ticket summary',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/get-ticket-summary',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: get ticket tone',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/get-ticket-tone',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: save chat id',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/chat-id',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: delete chat id',
            'method'  => 'DELETE',
            'route'   => '/fluent-bot/{id}/chat-id',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: switch conversation',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/conversations/switch',
            'payload' => [],
        ],
        [
            'label'   => 'fluent-bot: save context selection',
            'method'  => 'POST',
            'route'   => '/fluent-bot/{id}/context-selection',
            'payload' => [],
        ],

        // ---- activity-logger (core, ActivityLoggerPolicy) ----
        [
            'label'   => 'activity-logger: update settings',
            'method'  => 'POST',
            'route'   => '/activity-logger/settings',
            'payload' => [],
        ],

        // ---- ai-activity-logger (core, ActivityLoggerPolicy) ----
        [
            'label'   => 'ai-activity-logger: update settings',
            'method'  => 'POST',
            'route'   => '/ai-activity-logger/settings',
            'payload' => [],
        ],

        // ---- auth (core, PublicPolicy) ----
        [
            'label'   => 'auth: signup',
            'method'  => 'POST',
            'route'   => '/signup',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],
        [
            'label'   => 'auth: login',
            'method'  => 'POST',
            'route'   => '/login',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],
        [
            'label'   => 'auth: verify 2fa',
            'method'  => 'POST',
            'route'   => '/two_fa',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],
        [
            'label'   => 'auth: reset password',
            'method'  => 'POST',
            'route'   => '/reset_pass',
            'payload' => [],
            'skip'    => 'public by design: PublicPolicy',
        ],

        // ---- ticket_importer (core, AdminSettingsPolicy) ----
        [
            'label'   => 'ticket_importer: import',
            'method'  => 'POST',
            'route'   => '/ticket_importer/import',
            'payload' => [],
        ],
        [
            'label'   => 'ticket_importer: delete',
            'method'  => 'DELETE',
            'route'   => '/ticket_importer/delete',
            'payload' => [],
        ],

        // ---- ticket_image_upload (core, AgentTicketPolicy) ----
        [
            'label'   => 'uploader: ticket image upload',
            'method'  => 'POST',
            'route'   => '/ticket_image_upload',
            'payload' => [],
        ],

        // ---- pro: ticket-tags (AdminSettingsPolicy) ----
        [
            'label'             => 'pro ticket-tags: create',
            'method'            => 'POST',
            'route'             => '/ticket-tags',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro ticket-tags: update',
            'method'            => 'PUT',
            'route'             => '/ticket-tags/{tag_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro ticket-tags: delete',
            'method'            => 'DELETE',
            'route'             => '/ticket-tags/{tag_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: saved-replies (AgentTicketPolicy) ----
        [
            'label'             => 'pro saved-replies: create',
            'method'            => 'POST',
            'route'             => '/saved-replies',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro saved-replies: update',
            'method'            => 'PUT',
            'route'             => '/saved-replies/{id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro saved-replies: delete',
            'method'            => 'DELETE',
            'route'             => '/saved-replies/{id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: ticket-custom-fields (AdminSettingsPolicy) ----
        [
            'label'             => 'pro ticket-custom-fields: store',
            'method'            => 'POST',
            'route'             => '/ticket-custom-fields',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro ticket-custom-fields: sync ticket data',
            'method'            => 'POST',
            'route'             => '/ticket-custom-fields/{ticket_id}/sync',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: workflows (WorkflowPolicy) ----
        [
            'label'             => 'pro workflows: create',
            'method'            => 'POST',
            'route'             => '/workflows',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro workflows: update action sequence',
            'method'            => 'POST',
            'route'             => '/workflows/action-sequence',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro workflows: duplicate',
            'method'            => 'POST',
            'route'             => '/workflows/duplicate/{workflow_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro workflows: update',
            'method'            => 'POST',
            'route'             => '/workflows/{workflow_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro workflows: delete',
            'method'            => 'DELETE',
            'route'             => '/workflows/{workflow_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro workflows: run',
            'method'            => 'POST',
            'route'             => '/workflows/{workflow_id}/run',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: email-box (AdminSettingsPolicy) ----
        [
            'label'             => 'pro email-box: issue mapped email',
            'method'            => 'POST',
            'route'             => '/email-box/{box_id}/issue-email',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: mail-piping (PublicPolicy) ----
        // Registered with ->any(), so it answers POST among all other methods.
        [
            'label'             => 'pro mail-piping: pipe payload',
            'method'            => 'POST',
            'route'             => '/mail-piping/{box_id}/push/{token}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
            'skip'              => 'public by design: PublicPolicy (registered via ->any())',
        ],

        // ---- pro: pro (AdminSettingsPolicy) ----
        [
            'label'             => 'pro license: save',
            'method'            => 'POST',
            'route'             => '/pro/license',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro license: deactivate',
            'method'            => 'POST',
            'route'             => '/pro/remove-license',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro form-settings: save',
            'method'            => 'POST',
            'route'             => '/pro/form-settings',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: settings (AdminSettingsPolicy) ----
        [
            'label'             => 'pro settings: save discord integration',
            'method'            => 'POST',
            'route'             => '/settings/discord-integration',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro settings: update incoming webhook',
            'method'            => 'PUT',
            'route'             => '/settings/incoming-webhook',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro settings: save twilio integration',
            'method'            => 'POST',
            'route'             => '/settings/twilio-integration',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro settings: save auto-close',
            'method'            => 'POST',
            'route'             => '/settings/auto-close',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro settings: save upload integration',
            'method'            => 'POST',
            'route'             => '/settings/upload_integration',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: public (PublicPolicy) ----
        [
            'label'             => 'pro public: incoming webhook',
            'method'            => 'POST',
            'route'             => '/public/incoming_webhook/{token}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
            'skip'              => 'public by design: PublicPolicy',
        ],
        [
            'label'             => 'pro public: twilio response',
            'method'            => 'POST',
            'route'             => '/public/twilio-response/{token}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
            'skip'              => 'public by design: PublicPolicy',
        ],

        // ---- pro: tickets (AgentTicketPolicy) ----
        [
            'label'             => 'pro tickets: sync watchers',
            'method'            => 'POST',
            'route'             => '/tickets/{ticket_id}/sync-watchers',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro tickets: add watchers',
            'method'            => 'POST',
            'route'             => '/tickets/{ticket_id}/add_watchers',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro tickets: merge tickets',
            'method'            => 'POST',
            'route'             => '/tickets/{ticket_id}/merge_tickets',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro tickets: split ticket',
            'method'            => 'POST',
            'route'             => '/tickets/{ticket_id}/split_ticket',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: time-tracks (AgentTicketPolicy) ----
        [
            'label'             => 'pro time-tracks: update estimated time',
            'method'            => 'POST',
            'route'             => '/time-tracks/{ticket_id}/estimated-time',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro time-tracks: manual commit track',
            'method'            => 'POST',
            'route'             => '/time-tracks/{ticket_id}',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: ai (AgentTicketPolicy) ----
        [
            'label'             => 'pro ai: generate response',
            'method'            => 'POST',
            'route'             => '/ai/{id}/generate-response',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro ai: get ticket summary',
            'method'            => 'POST',
            'route'             => '/ai/{id}/get-ticket-summary',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro ai: get ticket tone',
            'method'            => 'POST',
            'route'             => '/ai/{id}/get-ticket-tone',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],

        // ---- pro: advanced-reports audit (AdvancedReportPolicy) ----
        [
            'label'             => 'pro advanced-reports: run audit',
            'method'            => 'POST',
            'route'             => '/advanced-reports/audit/run',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
        [
            'label'             => 'pro advanced-reports: process audit batch',
            'method'            => 'POST',
            'route'             => '/advanced-reports/audit/process-batch',
            'payload'           => [],
            'requires_constant' => 'FLUENTSUPPORTPRO',
        ],
    ],
];
