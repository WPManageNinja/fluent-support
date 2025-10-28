<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Mailer;

/**
 * WatcherNotificationHandler - Handles all watcher notification logic
 *
 * This class manages notifications for ticket watchers when tickets are updated.
 * It hooks into various ticket events and sends email notifications to watchers.
 *
 * @package FluentSupport\App\Hooks\Handlers
 * @version 1.0.0
 */
class WatcherNotificationHandler
{
    /**
     * Register all watcher notification hooks
     */
    public static function init()
    {
        // Response added by agent
        add_action('fluent_support/response_added_by_agent', [self::class, 'handleResponseByAgent'], 20, 3);

        // Response added by customer
        add_action('fluent_support/response_added_by_customer', [self::class, 'handleResponseByCustomer'], 20, 3);

        // Ticket closed
        add_action('fluent_support/ticket_closed', [self::class, 'handleTicketClosed'], 20, 2);

        // Ticket reopened
        add_action('fluent_support/ticket_reopen', [self::class, 'handleTicketReopened'], 20, 2);

        // Agent assigned to ticket
        add_action('fluent_support/agent_assigned_to_ticket', [self::class, 'handleAgentAssigned'], 20, 3);
    }

    /**
     * Handle response added by agent
     *
     * @param object $response The response object
     * @param Ticket $ticket The ticket object
     * @param object $agent The agent who added the response
     */
    public static function handleResponseByAgent($response, $ticket, $agent)
    {
        self::notifyWatchers($ticket, 'response_added', [
            'response' => $response,
            'agent' => $agent
        ]);
    }

    /**
     * Handle response added by customer
     *
     * @param object $response The response object
     * @param Ticket $ticket The ticket object
     * @param object $customer The customer who added the response
     */
    public static function handleResponseByCustomer($response, $ticket, $customer)
    {
        self::notifyWatchers($ticket, 'response_added', [
            'response' => $response,
            'customer' => $customer
        ]);
    }

    /**
     * Handle ticket closed
     *
     * @param Ticket $ticket The ticket object
     * @param object $person The person who closed the ticket
     */
    public static function handleTicketClosed($ticket, $person)
    {
        self::notifyWatchers($ticket, 'ticket_closed', [
            'person' => $person
        ]);
    }

    /**
     * Handle ticket reopened
     *
     * @param Ticket $ticket The ticket object
     * @param object $person The person who reopened the ticket
     */
    public static function handleTicketReopened($ticket, $person)
    {
        self::notifyWatchers($ticket, 'ticket_reopened', [
            'person' => $person
        ]);
    }

    /**
     * Handle agent assigned to ticket
     *
     * @param object $agent The assigned agent
     * @param Ticket $ticket The ticket object
     * @param object $assigner The agent who made the assignment
     */
    public static function handleAgentAssigned($agent, $ticket, $assigner)
    {
        self::notifyWatchers($ticket, 'agent_assigned', [
            'agent' => $agent,
            'assigner' => $assigner
        ]);
    }

    /**
     * Notify all watchers about ticket updates
     *
     * @param Ticket $ticket The ticket object
     * @param string $updateType Type of update (response_added, status_changed, etc.)
     * @param array $additionalData Additional data for the notification
     * @return void
     */
    public static function notifyWatchers($ticket, $updateType, $additionalData = [])
    {
        $mailbox = $ticket->mailbox;

        if (!$mailbox) {
            return;
        }

        // Get watchers
        $watchers = Ticket::getWatchersWithDetails($ticket->id);

        if ($watchers->isEmpty()) {
            return;
        }

        // Get current agent to exclude from notifications
        $currentAgent = Helper::getAgentByUserId();

        $emailHandler = new EmailNotificationHandler();

        foreach ($watchers as $watcher) {
            // Don't notify the agent who made the change
            if ($currentAgent && $currentAgent->id == $watcher->id) {
                continue;
            }

            $subject = sprintf(
                __('[Ticket #%d] %s', 'fluent-support'),
                $ticket->id,
                $ticket->title
            );

            $emailBody = $emailHandler->parseEmailBody(self::getWatcherEmailBody($updateType, $additionalData), [
                'business'        => $mailbox,
                'ticket'          => $ticket,
                'watcher'         => $watcher,
                'email_type'      => 'watcher_notification',
                'update_type'     => $updateType,
                'additional_data' => $additionalData
            ]);

            $headers = $mailbox->getMailerHeader();

            Mailer::send($watcher->email, $subject, $emailBody, $headers);
        }
    }

    /**
     * Get email body template for watcher notifications
     *
     * @param string $updateType Type of update
     * @param array $additionalData Additional data
     * @return string Email template
     */
    private static function getWatcherEmailBody($updateType, $additionalData)
    {
        $templates = [
            'response_added'   => '<p>A new response has been added to ticket #{{ticket.id}}.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>',
            'ticket_closed'    => '<p>Ticket #{{ticket.id}} has been closed.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>',
            'ticket_reopened'  => '<p>Ticket #{{ticket.id}} has been reopened.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>',
            'agent_assigned'   => '<p>Ticket #{{ticket.id}} has been assigned to a new agent.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>',
            'priority_changed' => '<p>Ticket #{{ticket.id}} priority has been changed.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>',
            'default'          => '<p>Ticket #{{ticket.id}} has been updated.</p><p><a href="{{ticket.admin_url}}">View Ticket</a></p>'
        ];

        return isset($templates[$updateType]) ? $templates[$updateType] : $templates['default'];
    }
}

