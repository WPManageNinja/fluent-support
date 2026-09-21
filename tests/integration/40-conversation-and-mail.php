<?php
/**
 * Agent replies: what lands in fs_conversations, and what goes out by mail.
 *
 * Mail is intercepted by the runner through `pre_wp_mail`, so nothing leaves
 * the machine. The point of these cases is that the notification is attempted
 * with the right recipient and subject — and, just as importantly, that an
 * internal note does NOT notify the customer.
 */

use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;

return function () {

    $marker = FsFactory::marker();
    $agent = Helper::getAgentByUserId();

    /**
     * Mails captured since a recorded offset, as plain arrays.
     *
     * @param int $offset
     * @return array<int,array<string,mixed>>
     */
    $mailsSince = function ($offset) {
        $all = FsTest::sentMails();

        return array_values(array_slice($all, $offset));
    };

    /**
     * Recipients of one captured mail, normalised to a flat list.
     *
     * @param array<string,mixed> $mail
     * @return array<int,string>
     */
    $recipients = function ($mail) {
        $to = isset($mail['to']) ? $mail['to'] : [];

        return array_map('strval', is_array($to) ? $to : [$to]);
    };

    FsTest::case('agent_response_is_stored_as_a_conversation_by_that_agent', function () use ($marker, $agent) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Convo storage', 'status' => 'new']);

        $result = FsTest::rest('POST', '/tickets/' . $ticket->id . '/responses', [
            'content'           => '<p>Stored reply ' . $marker . '</p>',
            'conversation_type' => 'response',
        ]);
        FsTest::assertHealthy($result, 'POST /tickets/{id}/responses');

        $conversations = Conversation::where('ticket_id', $ticket->id)
            ->where('conversation_type', 'response')
            ->get();

        FsTest::assertSame(1, count($conversations), 'exactly one response conversation on the ticket');

        $conversation = $conversations[0];
        FsTest::assertSame((int) $agent->id, (int) $conversation->person_id, 'conversation person_id is the agent');
        // fs_conversations stores only person_id; the author's kind comes from
        // the shared fs_persons row it points at.
        FsTest::assertSame('agent', $conversation->person->person_type, 'conversation author is an agent person');
        FsTest::assert(strpos((string) $conversation->content, $marker) !== false,
            'the stored content must be what we posted');
    });

    FsTest::case('agent_response_emails_the_ticket_customer', function () use (
        $marker, $mailsSince, $recipients
    ) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Convo mail', 'status' => 'new']);

        $before = count(FsTest::sentMails());

        $result = FsTest::rest('POST', '/tickets/' . $ticket->id . '/responses', [
            'content'           => '<p>Notify reply ' . $marker . '</p>',
            'conversation_type' => 'response',
        ]);
        FsTest::assertHealthy($result, 'POST /tickets/{id}/responses');

        $mails = $mailsSince($before);
        if (!$mails) {
            FsTest::fail('an agent response must attempt a customer notification, none was intercepted');
            return;
        }

        $matching = [];
        foreach ($mails as $mail) {
            if (in_array($customer->email, $recipients($mail), true)) {
                $matching[] = $mail;
            }
        }

        FsTest::assertSame(1, count($matching),
            'exactly one intercepted mail addressed to the ticket customer (' . $customer->email . '), got '
            . count($mails) . ' mail(s) total');

        $subject = isset($matching[0]['subject']) ? (string) $matching[0]['subject'] : '';
        $serial = (string) Ticket::find($ticket->id)->serial_number;
        FsTest::assert(strpos($subject, $serial) !== false,
            'the notification subject must carry the ticket serial #' . $serial . ', got: ' . $subject);
    });

    FsTest::case('mail_is_intercepted_and_never_handed_to_the_mailer', function () use (
        $marker, $mailsSince
    ) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Convo intercept', 'status' => 'new']);

        $before = count(FsTest::sentMails());

        FsTest::rest('POST', '/tickets/' . $ticket->id . '/responses', [
            'content'           => '<p>Intercept reply ' . $marker . '</p>',
            'conversation_type' => 'response',
        ]);

        $mails = $mailsSince($before);
        FsTest::assert(count($mails) > 0, 'the interceptor must have captured the outbound mail');

        // If interception were broken the message would have gone to a real
        // MTA and nothing would be in the buffer to inspect.
        foreach ($mails as $mail) {
            FsTest::assert(isset($mail['subject']) && $mail['subject'] !== '',
                'captured mail must carry a subject: ' . wp_json_encode($mail));
        }
    });

    FsTest::case('internal_note_does_not_email_the_customer', function () use (
        $marker, $mailsSince, $recipients
    ) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Convo note', 'status' => 'new']);

        $before = count(FsTest::sentMails());

        $result = FsTest::rest('POST', '/tickets/' . $ticket->id . '/responses', [
            'content'           => '<p>Internal note ' . $marker . '</p>',
            'conversation_type' => 'note',
        ]);
        FsTest::assertHealthy($result, 'POST /tickets/{id}/responses (note)');

        $note = Conversation::where('ticket_id', $ticket->id)
            ->where('conversation_type', 'note')
            ->first();
        if (!$note) {
            FsTest::fail('an internal note must still be stored as a conversation');
            return;
        }

        foreach ($mailsSince($before) as $mail) {
            if (in_array($customer->email, $recipients($mail), true)) {
                FsTest::fail('an internal note must never be emailed to the customer (' . $customer->email . ')');
                return;
            }
        }

        // A note is internal, so it is not a customer-facing reply.
        FsTest::assertSame(0, (int) Ticket::find($ticket->id)->response_count,
            'an internal note must not increment response_count');
    });

    FsTest::case('customer_response_is_attributed_to_the_customer_not_the_agent', function () use ($marker) {
        $customer = FsFactory::customer();
        $ticket = FsFactory::ticket(['customer_id' => $customer->id, 'title' => 'Convo customer side']);

        $conversation = FsFactory::conversation($ticket, [
            'person_id' => $customer->id,
            'content'   => '<p>Customer says ' . $marker . '</p>',
        ]);

        $stored = Conversation::find($conversation->id);
        FsTest::assertSame((int) $customer->id, (int) $stored->person_id, 'conversation person_id');

        // The relation must resolve through the shared fs_persons table to the
        // customer, never to a same-id agent.
        $author = $stored->person;
        if (!$author) {
            FsTest::fail('conversation->person did not resolve to the customer who wrote it');
            return;
        }

        FsTest::assertSame($customer->email, $author->email, 'conversation author email');
        FsTest::assertSame('customer', $author->person_type, 'conversation author is a customer person');
    });
};
