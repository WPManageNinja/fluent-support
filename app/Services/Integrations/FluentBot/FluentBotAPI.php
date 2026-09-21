<?php

namespace FluentSupport\App\Services\Integrations\FluentBot;

use WP_Error;

class FluentBotAPI
{
    protected $apiUrl;

    protected $apiKey;

    public function __construct(string $apiUrl, string $apiKey = '')
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function makeRequest(int $ticketId, $prompt, array $args = [])
    {
        $response = $this->sendRequest($args);

        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            $code = $response->get_error_code();
            return new \WP_Error($code, $message);
        }

        $rawBody = wp_remote_retrieve_body($response);
        $responseBody = json_decode($rawBody, true) ?? [];

        if (!$responseBody || !is_array($responseBody)) {
            return new \WP_Error('fluent_bot_error', __('Invalid or empty response from API', 'fluent-support'));
        }

        if (!empty($responseBody['error'])) {
            $message = $responseBody['error']['message'] ?? __('Unknown error occurred', 'fluent-support');
            return new \WP_Error('fluent_bot_error', $message);
        }

        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode !== 200) {
            $error = $responseBody['message'] ?? __('Something went wrong.', 'fluent-support');
            return new \WP_Error($statusCode, $error);
        }

        $content = $responseBody['response'] ?? '';

        if (empty($content)) {
            return new \WP_Error('fluent_bot_error', __('No AI response found in the API response.', 'fluent-support'));
        }

        $tokenUsage = $responseBody['token_usage'] ?? [];
        $totalTokens = ($tokenUsage['input_tokens'] ?? 0) + ($tokenUsage['output_tokens'] ?? 0);
        do_action('fluent_support/ai_response_success', $ticketId, $prompt, $totalTokens, "FluentBot");

        // Return both content and chat_id if available
        return [
            'content' => $content,
            'chat_id' => $responseBody['chat_id'] ?? null
        ];
    }

    public function makeStreamRequest(int $ticketId, $prompt, array $args = [])
    {
        $timeout = apply_filters('fs_ai_request_timeout', 120);

        // Use cURL for streaming
        // Note: Using cURL here because WordPress HTTP API doesn't support streaming SSE responses
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_error
        // PluginCheck:ignoreFile
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($args));
        $streamHeaders = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $streamHeaders[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $streamHeaders);

        $buffer = '';
        $conversationId = null;
        $streamTokens = 0;

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer, &$conversationId, &$streamTokens) {
            foreach ($this->drainSseChunk($data, $buffer) as $parsed) {
                // Store chat_id for later use
                if ($parsed['event'] === 'chat_id' && !empty($parsed['data'])) {
                    $conversationId = $parsed['data'][0];
                } elseif ($parsed['event'] === 'token_usage' && !empty($parsed['data'])) {
                    $usage = json_decode($parsed['data'][0], true);
                    if (is_array($usage)) {
                        $streamTokens = ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0);
                    }
                }
            }

            return strlen($data);
        });

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // Smaller buffer for faster streaming

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            echo "event: error\n";
            echo "data: " . json_encode(['error' => curl_error($ch)]) . "\n\n";
            flush();
        }

        curl_close($ch);

        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_error

        if ($httpCode === 200) {
            do_action('fluent_support/ai_response_success', $ticketId, $prompt, $streamTokens, "FluentBot");
        }
    }

    /**
     * Forward every complete SSE frame in $data to the browser, return the parsed frames.
     * $buffer holds the partial trailing frame across cURL writes, so pass it by reference.
     *
     * @param string $data   raw bytes from cURL
     * @param string $buffer incomplete frame carried over from the previous write
     * @return array<int, array{event: string, data: array<int, string>}>
     */
    private function drainSseChunk(string $data, string &$buffer): array
    {
        $buffer .= $data;

        // Process complete SSE events from the AI API
        $events = explode("\n\n", $buffer);
        $buffer = array_pop($events); // Keep incomplete event in buffer

        $parsed = [];

        foreach ($events as $event) {
            if (!trim($event)) {
                continue;
            }

            // SSE comment/keepalive (starts ":"): forward raw so proxies keep seeing bytes, no idle-timeout.
            if (strpos(ltrim($event), ':') === 0) {
                echo $event . "\n\n";
                flush();
                continue;
            }

            $lines = explode("\n", $event);
            $eventType = '';
            $eventId = '';
            $eventDataLines = [];

            foreach ($lines as $line) {
                if (strpos($line, 'event: ') === 0) {
                    $eventType = trim((string)substr($line, 7));
                } elseif (strpos($line, 'id: ') === 0) {
                    $eventId = trim((string)substr($line, 4));
                } elseif (strpos($line, 'data: ') === 0) {
                    $eventDataLines[] = (string)substr($line, 6);
                }
            }

            // Forward the event to the browser with proper formatting
            if (!$eventType) {
                continue;
            }

            echo "event: ".esc_html($eventType)."\n";

            // Include ID if present
            if ($eventId !== '') {
                echo "id: ".esc_html($eventId)."\n";
            }

            // Handle multiple data lines properly
            if (!empty($eventDataLines)) {
                foreach ($eventDataLines as $dataLine) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw SSE payload from trusted bot endpoint; rendered output sanitized client-side
                    echo "data: ".$dataLine."\n";
                }
            } else {
                echo "data: \n";
            }

            echo "\n";
            flush();

            $parsed[] = ['event' => $eventType, 'data' => $eventDataLines];
        }

        return $parsed;
    }

    /**
     * Reconnect to an in-flight turn: replay its buffered SSE and tail it live. Same wire
     * format as makeStreamRequest. Upstream ends on `__done__` or an `idle` frame; a total
     * cap still applies — see the timeout block below.
     */
    public function makeResumeStreamRequest()
    {
        // Use cURL for streaming
        // Note: Using cURL here because WordPress HTTP API doesn't support streaming SSE responses
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_error
        // PluginCheck:ignoreFile
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        $streamHeaders = ['Accept: text/event-stream'];
        if ($this->apiKey !== '') {
            $streamHeaders[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $streamHeaders);

        $buffer = '';

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer) {
            // Stop as soon as the agent navigates away — nothing here needs to
            // outlive the client, the turn itself is persisted upstream.
            if (connection_aborted()) {
                return 0;
            }

            $this->drainSseChunk($data, $buffer);

            return strlen($data);
        });

        // Bounded on purpose: connection_aborted() can lag (abort travels browser ->
        // proxy -> fluent-bot, Apache buffers writes), so a refresh mid-turn can leave
        // the old tail pinning a worker. The cap makes that self-limiting — the client
        // treats a cut tail as a body ended without __done__ and refetches.
        curl_setopt($ch, CURLOPT_TIMEOUT, apply_filters('fs_ai_resume_timeout', 120));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        // Abort sooner when genuinely stalled: the upstream sends ': keepalive'
        // during quiet gaps, so a healthy tail always beats this window.
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, apply_filters('fs_ai_resume_stall_timeout', 60));
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // Smaller buffer for faster streaming

        curl_exec($ch);

        if (curl_error($ch)) {
            echo "event: error\n";
            echo "data: " . wp_json_encode(['error' => curl_error($ch)]) . "\n\n";
            flush();
        }

        curl_close($ch);
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_error
    }

    protected function sendRequest(array $payload)
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $timeout = apply_filters('fs_ai_request_timeout', 60);

        return wp_remote_post($this->apiUrl, [
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => $timeout,
        ]);
    }
}
