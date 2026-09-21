<?php

namespace FluentSupport\App\Services\ThirdParty;

use Exception;
use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;
use FluentSupport\App\Services\Tickets\ResponseService;
use FluentSupport\App\Services\Tickets\TicketService;

class HandleTelegramEvent
{


    /**
     * handleEvent method is responsible for handling telegram events
     * @param array $payload
     * @param string $token
     * @return array
     * @throws Exception
     */
    public function handleEvent ($payload, $token)
    {

        // Check if user have the pro version installed or not
        $this->verifyProVersion();

        // Check if the token is valid or not
        $this->validateToken($token);

        try {
            // Build the response or throw error
            $responseData = $this->parseResponseData($payload);

        } catch (\Exception $exception) {
            return false;
        }

        $ticket = Ticket::find($responseData['ticket_id']);

        if (!$ticket) {
            return false;
        }

        $agent = Agent::find($responseData['agent_id']);

        if (!$agent) {
            return false;
        }

        // The agent comes from the Telegram sender id, not a logged-in user, so check
        // access explicitly: every mailbox notifies the same chat, and an agent excluded
        // from a box must not reply to or close that box's tickets from here.
        if (!(new AgentTicketAccess())->canAccess($agent, $ticket)) {
            return false;
        }

        $data = [
            'conversation_type' => 'response',
            'content'           => $responseData['response_text'],
            'source'            => 'telegram'
        ];

        if(!empty($data['content'])) {
            // Create the response
            (new ResponseService)->createResponse($data, $agent, $ticket);
        }

        if(!empty($responseData['command']) && $responseData['command'] == 'close_ticket') {
            (new TicketService())->close($ticket, $agent);
        }

        return $data;
    }


    /**
     * verifyProVersion method will check if the pro version is installed or not
     * @throws Exception
     * @return boolean | Exception
     */
    private function verifyProVersion ()
    {
        if (!defined('FLUENTSUPPORTPRO')) {
            throw new \Exception('Telegram Integration requires pro version of Fluent Support', 400);
        }

        return true;
    }

    /**
     * validateToken method will check if the token is valid or not
     * @param string $token
     * @throws Exception
     * @return boolean | Exception
     */
    private function validateToken ($token)
    {
        if (!hash_equals(\FluentSupportPro\App\Services\Integrations\Telegram\TelegramHelper::getWebhookToken(), $token)) {
            throw new \Exception('Bot Token could not be verified', 403);
        }
        return true;
    }

    /**
     * parseResponseData method will parse the response data and return an array with
     * response text and ticket id and agent id
     * @param array $payload
     * @throws Exception
     * @return array
     */
    private function parseResponseData ($payload)
    {
        $responseData = \FluentSupportPro\App\Services\Integrations\Telegram\TelegramHelper::parseTelegramBotPayload($payload);

        if ( is_wp_error( $responseData ) ) {
            /*
             * Action on telegram payload error when replying ticket from telegram
             *
             * @since v1.0.0
             * @param array $responseData
             * @param array $payload
             */
            do_action('fluent_support/telegram_payload_error', $responseData, $payload);

            throw new \Exception(
                esc_html($responseData->get_error_message()),
                (int) $responseData->get_error_code()
            );

        } else {
            return $responseData;
        }
    }
}
