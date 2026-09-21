<?php
/**
 * Literal backslashes must survive the write path byte for byte.
 *
 * Request input is unslashed exactly once at the boundary — the WPFluent
 * Request cleaner for web requests, WP core for REST params — and API, MCP and
 * webhook callers hand over decoded JSON that was never slashed. The write
 * services must therefore sanitize only. A second wp_unslash() silently eats
 * Windows paths, regexes, escaped quotes and UNC paths out of ticket bodies and
 * agent replies: the row is still written, the request still returns 200, and
 * the loss is visible only to the person reading the ticket afterwards.
 *
 * That is precisely the invariant a framework or WP-core bump can break without
 * anything erroring, so it is asserted byte for byte instead of with a
 * substring probe. If the boundary ever starts handing these services slashed
 * input again, this goes red here rather than in a support inbox.
 */

use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;

return function () {

    $marker = FsFactory::marker();

    /*
     * Every escape sequence stripslashes() would eat, in one payload:
     *   C:\Users\test        Windows path      — \U and \t
     *   /\d+\.\d+/           regex             — \d and \.
     *   it's a "quote"       quoting           — \' and \" after a slash pass
     *   \\fileserver\share   UNC / literal \\  — collapses to \fileserver\share
     */
    $slashy = 'C:\Users\test | /\d+\.\d+/ | it\'s a "quote" | \\\\fileserver\\share';
    $body = '<p>' . $slashy . '</p>';

    FsTest::case('agent_reply_stores_literal_backslashes_byte_for_byte', function () use ($marker, $slashy, $body) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Slash reply', 'status' => 'new']);

        $content = '<p>Slash reply ' . $marker . '</p>' . $body;

        $result = FsTest::rest('POST', '/tickets/' . $ticket->id . '/responses', [
            'content'           => $content,
            'conversation_type' => 'response',
        ]);
        FsTest::assertHealthy($result, 'POST /tickets/{id}/responses');

        $stored = Conversation::where('ticket_id', $ticket->id)
            ->where('conversation_type', 'response')
            ->first();

        if (!$stored) {
            FsTest::fail('the reply was not stored as a conversation, nothing to assert on');
            return;
        }

        // Byte for byte: ResponseService::createResponse() sanitizes, it does
        // not unslash. Anything else here is content loss.
        FsTest::assertSame($content, (string) $stored->content,
            'fs_conversations.content must be exactly what was posted');

        // content_hash is derived from the same string, so a mangled body would
        // also break reply de-duplication on the email-piping path.
        FsTest::assertSame(md5($content), (string) $stored->content_hash,
            'fs_conversations.content_hash must be md5 of the stored content');

        FsTest::assert(strpos((string) $stored->content, $slashy) !== false,
            'the backslash payload must be present verbatim, got: ' . $stored->content);
    });

    FsTest::case('created_ticket_stores_literal_backslashes_byte_for_byte', function () use ($marker, $slashy, $body) {
        $customer = FsFactory::customer();
        $mailbox = FsFactory::mailbox();

        $title = 'Slash ticket ' . $marker . ' C:\Users\test';
        $content = '<p>Slash body ' . $marker . '</p>' . $body;

        $result = FsTest::rest('POST', '/tickets', [
            'ticket' => [
                'customer_id' => $customer->id,
                'mailbox_id'  => $mailbox->id,
                'title'       => $title,
                'content'     => $content,
                'priority'    => 'normal',
            ],
        ]);

        // Adopt before asserting — a row written by a request that then errors
        // must still be registered for exact-ID cleanup.
        foreach (Ticket::where('title', 'LIKE', '%' . $marker . '%')->get() as $persisted) {
            FsFactory::adoptRestTicket($persisted);
        }

        FsTest::assertHealthy($result, 'POST /tickets');

        $created = isset($result['data']['ticket']['id']) ? (int) $result['data']['ticket']['id'] : 0;
        if (!$created) {
            FsTest::fail('POST /tickets did not return a ticket id. payload: ' . wp_json_encode($result['data']));
            return;
        }

        $stored = Ticket::find($created);
        if (!$stored) {
            FsTest::fail('POST /tickets returned id ' . $created . ' but no fs_tickets row exists.');
            return;
        }

        // TicketService::createTicket() sanitizes title and content; neither
        // may unslash.
        FsTest::assertSame($title, (string) $stored->title,
            'fs_tickets.title must be exactly what was posted');
        FsTest::assertSame($content, (string) $stored->content,
            'fs_tickets.content must be exactly what was posted');

        FsTest::assert(strpos((string) $stored->content, $slashy) !== false,
            'the backslash payload must be present verbatim, got: ' . $stored->content);
    });
};
