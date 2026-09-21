<?php
/**
 * Shared tables must never leak between their discriminated types.
 *
 * fs_persons holds customers and agents side by side, separated only by the
 * person_type global scope on the Customer/Agent models. fs_taggables holds
 * every tag type, separated by tag_type. fs_tag_pivot is polymorphic on
 * (source_id, source_type).
 *
 * These are the bugs that a "SELECT ... WHERE id = ?" written without the
 * scope produces: an agent readable as a customer, a delete that takes the
 * wrong row with it, a tag list that shows another feature's tags.
 */

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Person;
use FluentSupport\App\Models\Tag;
use FluentSupport\App\Models\TagPivot;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\TicketTag;

return function () {

    $marker = FsFactory::marker();

    /**
     * Attached tag IDs for a ticket, read through the real relation.
     *
     * The columns are selected off the model side because the relation joins
     * fs_taggables to fs_tag_pivot and a bare `id` is ambiguous across them.
     *
     * @param Ticket $ticket
     * @return array<int>
     */
    $tagIdsOn = function (Ticket $ticket) {
        $ids = [];
        foreach ($ticket->tags()->get() as $tag) {
            $ids[] = (int) $tag->id;
        }

        return $ids;
    };

    // Same local part, same domain, different person_type: nothing but the
    // discriminator can tell these two rows apart.
    // The marker has to stay in the address: exact-ID cleanup verifies it.
    $sharedBase = $marker . '-twin';
    $customer = FsFactory::customer(['email' => $sharedBase . '+customer@example.invalid']);
    $agent = FsFactory::agent(['email' => $sharedBase . '+agent@example.invalid']);

    FsTest::case('customer_lookup_never_returns_an_agent_row', function () use ($customer, $agent) {
        FsTest::assert((int) $customer->id !== (int) $agent->id, 'fixtures must be two distinct person rows');

        FsTest::assert(Customer::find($agent->id) === null,
            'Customer::find() must not return agent row ' . $agent->id);
        FsTest::assert(Agent::find($customer->id) === null,
            'Agent::find() must not return customer row ' . $customer->id);

        // Both rows do exist — the scope is what hides them, not a missing row.
        FsTest::assert(Person::find($agent->id) !== null, 'the agent row exists in fs_persons');
        FsTest::assert(Person::find($customer->id) !== null, 'the customer row exists in fs_persons');
    });

    FsTest::case('customer_listing_excludes_agents_with_the_same_email_base', function () use (
        $sharedBase, $customer, $agent
    ) {
        $ids = Customer::where('email', 'LIKE', $sharedBase . '%')->pluck('id')->toArray();
        $ids = array_map('intval', (array) $ids);

        FsTest::assert(in_array((int) $customer->id, $ids, true),
            'the customer must be listed by a customer query');
        FsTest::assert(!in_array((int) $agent->id, $ids, true),
            'the agent must NOT be listed by a customer query (person_type leak)');
    });

    FsTest::case('deleting_a_customer_leaves_the_agent_row_intact', function () use ($sharedBase) {
        // This case is about deletion, so it owns its own throwaway pair.
        $doomed = FsFactory::customer(['email' => $sharedBase . '+doomed-customer@example.invalid']);
        $survivor = FsFactory::agent(['email' => $sharedBase . '+doomed-agent@example.invalid']);

        // Both twins share a first_name, so a delete written against the shared
        // fs_persons table without the person_type discriminator would take the
        // agent with it.
        $result = FsTest::rest('DELETE', '/customers/' . $doomed->id);
        FsTest::assertHealthy($result, 'DELETE /customers/{id}');

        FsTest::assert(Customer::find($doomed->id) === null, 'the customer row is gone');
        FsTest::assert(Agent::find($survivor->id) !== null,
            'deleting a customer must not remove an agent row from the shared table');
        FsTest::assert(Person::find($doomed->id) === null, 'the underlying fs_persons row is gone');
    });

    FsTest::case('ticket_tag_scope_hides_tags_of_other_types', function () {
        $ticketTag = FsFactory::tag(['tag_type' => 'ticket_tag']);
        $otherTag = FsFactory::tag(['tag_type' => 'agent_group']);

        FsTest::assert(TicketTag::find($ticketTag->id) !== null,
            'a ticket_tag row is visible through TicketTag');
        FsTest::assert(TicketTag::find($otherTag->id) === null,
            'TicketTag::find() must not return agent_group row ' . $otherTag->id);
        FsTest::assert(Tag::find($otherTag->id) !== null,
            'the agent_group row does exist in fs_taggables');
    });

    FsTest::case('attached_ticket_tags_are_readable_through_the_relation', function () use ($tagIdsOn) {
        $ticket = FsFactory::ticket(['title' => 'Tagged ticket']);
        $tagOne = FsFactory::tag(['tag_type' => 'ticket_tag']);
        $tagTwo = FsFactory::tag(['tag_type' => 'ticket_tag']);
        $unrelated = FsFactory::tag(['tag_type' => 'ticket_tag']);

        FsFactory::attachTag($ticket, $tagOne);
        FsFactory::attachTag($ticket, $tagTwo);

        $attached = $tagIdsOn($ticket);
        sort($attached);

        $expected = [(int) $tagOne->id, (int) $tagTwo->id];
        sort($expected);

        FsTest::assertSame($expected, $attached, 'ticket->tags() returns exactly the attached tags');
        FsTest::assert(!in_array((int) $unrelated->id, $attached, true),
            'an unattached tag must not appear on the ticket');
    });

    FsTest::case('tag_pivot_rows_are_scoped_to_their_source_type', function () {
        $ticket = FsFactory::ticket(['title' => 'Pivot scoped ticket']);
        $tag = FsFactory::tag(['tag_type' => 'ticket_tag']);

        // Attach through the route so the discriminator written by production
        // code is what gets asserted.
        $result = FsTest::rest('POST', '/tickets/' . $ticket->id . '/tags', ['tag_id' => $tag->id]);
        FsTest::assertHealthy($result, 'POST /tickets/{id}/tags');

        $ticketScoped = TagPivot::where('source_type', 'ticket_tag')
            ->where('source_id', $ticket->id)
            ->where('tag_id', $tag->id)
            ->count();
        FsTest::assertSame(1, (int) $ticketScoped, 'exactly one ticket_tag pivot row for this pair');

        // The same numeric source_id under a different source_type is a
        // different object entirely; it must not be picked up.
        $otherScoped = TagPivot::where('source_type', 'agent_group')
            ->where('source_id', $ticket->id)
            ->count();
        FsTest::assertSame(0, (int) $otherScoped, 'the pivot row must not be visible under another source_type');
    });

    FsTest::case('detaching_a_ticket_tag_removes_only_that_pivot_row', function () use ($tagIdsOn) {
        // Detachment is the behaviour under test, so this case owns its rows.
        $ticket = FsFactory::ticket(['title' => 'Detach ticket']);
        $keep = FsFactory::tag(['tag_type' => 'ticket_tag']);
        $drop = FsFactory::tag(['tag_type' => 'ticket_tag']);
        FsFactory::attachTag($ticket, $keep);
        FsFactory::attachTag($ticket, $drop);

        $result = FsTest::rest('DELETE', '/tickets/' . $ticket->id . '/tags/' . $drop->id);
        FsTest::assertHealthy($result, 'DELETE /tickets/{id}/tags/{tag_id}');

        $remaining = $tagIdsOn($ticket);

        FsTest::assert(in_array((int) $keep->id, $remaining, true),
            'the other tag must survive the detach');
        FsTest::assert(!in_array((int) $drop->id, $remaining, true),
            'the detached tag must be gone');
        // The tag itself is a shared record — detaching must not delete it.
        FsTest::assert(Tag::find($drop->id) !== null,
            'detaching from one ticket must not delete the tag row itself');
    });
};
