<?php
/**
 * Closed-state transitions through the generic ticket property endpoint.
 *
 * PUT /tickets/{id}/property accepted `closed` as a status value and assigned it
 * straight onto the model. That skipped TicketService::close(), so the ticket was
 * saved closed with resolved_at, closed_by and total_close_time left NULL and
 * without firing fluent_support/ticket_closed — which is what drives the customer
 * email, the Pro chat notifications and workflow trigger, the activity log and the
 * del_files_on_close attachment cleanup. A closed ticket could also be moved back
 * to active or new the same way, leaving resolved_at set on a live ticket.
 *
 * Both directions must now be refused and sent to closeTicket()/reOpenTicket().
 *
 * @package Fluent_Support
 */

use FluentSupport\App\Http\Controllers\TicketController;
use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Tickets\TicketService;
use FluentSupport\Framework\Validator\ValidationException;

class TicketStatusTransitionTest extends WP_UnitTestCase
{
    /**
     * Route.php binds the dispatched WP_REST_Request into the container as
     * 'wprestrequest', and Request::inputs() merges that binding into every
     * framework Request built afterwards. Left in place it leaks another test's
     * prop_name/prop_value into these requests, so the binding is dropped on both
     * ends of every test.
     */
    private function forgetRestRequestBinding()
    {
        $app = isset($GLOBALS['fluent_support_test_app']) ? $GLOBALS['fluent_support_test_app'] : null;

        if ($app && $app->bound('wprestrequest')) {
            $app->forgetInstance('wprestrequest');
        }
    }

    public function set_up()
    {
        parent::set_up();

        $this->forgetRestRequestBinding();
    }

    public function tear_down()
    {
        $this->forgetRestRequestBinding();

        parent::tear_down();
    }

    /**
     * Build a WP user plus its fs_agents row and attach exactly the permissions given.
     *
     * The user must not be an administrator: attachPermissions() short-circuits for
     * manage_options, and userCan() lets admins through unconditionally.
     */
    private function makeAgent(array $permissions)
    {
        $email = 'agent-' . wp_generate_password(8, false) . '@example.test';

        $userId = self::factory()->user->create([
            'role'       => 'subscriber',
            'user_email' => $email,
        ]);

        $agent = Agent::create([
            'first_name' => 'Test',
            'last_name'  => 'Agent',
            'email'      => $email,
            'user_id'    => $userId,
            'status'     => 'active',
        ]);

        PermissionManager::attachPermissions($userId, $permissions);

        return $agent;
    }

    /**
     * currentUserPermissions() memoizes into a static that is not keyed by user id,
     * so the cache has to be refilled whenever the acting user changes.
     */
    private function actAs(Agent $agent)
    {
        wp_set_current_user($agent->user_id);
        PermissionManager::currentUserPermissions(false);
    }

    private function makeTicket($status = 'active')
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'customer-' . wp_generate_password(8, false) . '@example.test',
        ]);

        return Ticket::create([
            'customer_id' => $customer->id,
            'title'       => 'Status transition ticket',
            'content'     => 'body',
            'status'      => $status,
        ]);
    }

    private function makeRequest(array $post = [])
    {
        return new \FluentSupport\Framework\Http\Request\Request(
            $GLOBALS['fluent_support_test_app'], [], $post
        );
    }

    /**
     * updateTicketProperty() funnels every failure through Helper::getSafeErrorMessage(),
     * which throws a ValidationException carrying the message rather than returning a
     * response, so a refusal has to be caught here.
     *
     * @return string The refusal message, or '' when the call was allowed through.
     */
    private function setPropertyExpectingRefusal($ticketId, $propName, $propValue)
    {
        $request = $this->makeRequest([
            'prop_name'  => $propName,
            'prop_value' => $propValue,
        ]);

        try {
            (new TicketController())->updateTicketProperty($request, $ticketId);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            return isset($errors['message']) ? $errors['message'] : $e->getMessage();
        }

        return '';
    }

    private function setProperty($ticketId, $propName, $propValue)
    {
        $request = $this->makeRequest([
            'prop_name'  => $propName,
            'prop_value' => $propValue,
        ]);

        return (new TicketController())->updateTicketProperty($request, $ticketId);
    }

    private function conversationCount($ticketId)
    {
        return Conversation::where('ticket_id', $ticketId)->count();
    }

    /* ---------------------------------------------------------------------
     * The finding: closing and reopening through the generic property route.
     * ------------------------------------------------------------------ */

    public function test_property_route_cannot_close_a_ticket()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = $this->makeTicket('active');
        $notesBefore = $this->conversationCount($ticket->id);
        $closedHooksBefore = did_action('fluent_support/ticket_closed');

        $message = $this->setPropertyExpectingRefusal($ticket->id, 'status', 'closed');

        $this->assertStringContainsString(
            'dedicated close and re-open actions',
            $message,
            'Closing through the property route must be refused and point at closeTicket().'
        );

        $ticket = Ticket::find($ticket->id);

        // The durable state is the real assertion: without the guard the row is
        // already saved as closed by the time the response is built.
        $this->assertSame('active', $ticket->status, 'The ticket must not have been closed.');
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_by);
        $this->assertNull($ticket->total_close_time);
        $this->assertSame($notesBefore, $this->conversationCount($ticket->id), 'No internal note may be written for a refused transition.');
        $this->assertSame($closedHooksBefore, did_action('fluent_support/ticket_closed'));
    }

    public function test_property_route_cannot_reopen_a_closed_ticket()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = (new TicketService())->close($this->makeTicket('active'), $agent);
        $resolvedAt = $ticket->resolved_at;
        $reopenHooksBefore = did_action('fluent_support/ticket_reopen');

        $message = $this->setPropertyExpectingRefusal($ticket->id, 'status', 'active');

        $this->assertStringContainsString('dedicated close and re-open actions', $message);

        $ticket = Ticket::find($ticket->id);

        $this->assertSame('closed', $ticket->status, 'The ticket must not have been reopened.');
        $this->assertSame($resolvedAt, $ticket->resolved_at, 'resolved_at must not be left behind on a reopened ticket.');
        $this->assertSame($reopenHooksBefore, did_action('fluent_support/ticket_reopen'));
    }

    /**
     * 'new' is a changeable status too, so the closed-ticket half of the guard has
     * to cover it — not just closed -> active.
     */
    public function test_property_route_cannot_move_a_closed_ticket_to_new()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = (new TicketService())->close($this->makeTicket('active'), $agent);

        $message = $this->setPropertyExpectingRefusal($ticket->id, 'status', 'new');

        $this->assertStringContainsString('dedicated close and re-open actions', $message);
        $this->assertSame('closed', Ticket::find($ticket->id)->status);
    }

    /* ---------------------------------------------------------------------
     * Control leg: the transitions the admin app actually sends here still work.
     * handleStatusChange() in ViewTicket.vue only uses this route for new <-> active.
     * ------------------------------------------------------------------ */

    public function test_property_route_still_allows_new_to_active()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = $this->makeTicket('new');
        $notesBefore = $this->conversationCount($ticket->id);

        $this->setProperty($ticket->id, 'status', 'active');

        $this->assertSame('active', Ticket::find($ticket->id)->status);
        $this->assertSame(
            $notesBefore + 1,
            $this->conversationCount($ticket->id),
            'The status change still records its internal note.'
        );
    }

    public function test_property_route_still_allows_active_to_new()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = $this->makeTicket('active');

        $this->setProperty($ticket->id, 'status', 'new');

        $this->assertSame('new', Ticket::find($ticket->id)->status);
    }

    public function test_property_route_still_allows_non_status_properties()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = $this->makeTicket('active');

        $this->setProperty($ticket->id, 'title', 'Renamed ticket');

        $this->assertSame('Renamed ticket', Ticket::find($ticket->id)->title);
    }

    /* ---------------------------------------------------------------------
     * The dedicated routes remain the only way in and out of the closed state,
     * and still carry the closure data and hooks the property route skipped.
     * ------------------------------------------------------------------ */

    public function test_dedicated_close_records_closure_data_and_fires_the_hook()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = $this->makeTicket('active');
        $hooksBefore = did_action('fluent_support/ticket_closed');

        // 'no' is what the Close Ticket button sends (close_ticket_silently in
        // ViewTicket.vue). An absent value would take the silent path and skip
        // the very hooks this test is asserting on.
        $request = $this->makeRequest(['close_ticket_silently' => 'no']);

        (new TicketController())->closeTicket($request, $ticket->id);

        $ticket = Ticket::find($ticket->id);

        $this->assertSame('closed', $ticket->status);
        $this->assertNotEmpty($ticket->resolved_at, 'Reporting filters closed tickets by resolved_at.');
        $this->assertSame((int) $agent->id, (int) $ticket->closed_by);
        $this->assertNotNull($ticket->total_close_time);
        $this->assertSame($hooksBefore + 1, did_action('fluent_support/ticket_closed'));
    }

    public function test_dedicated_reopen_clears_resolved_at_and_fires_the_hook()
    {
        $agent = $this->makeAgent(['fst_manage_other_tickets']);
        $this->actAs($agent);

        $ticket = (new TicketService())->close($this->makeTicket('active'), $agent);
        $hooksBefore = did_action('fluent_support/ticket_reopen');

        (new TicketController())->reOpenTicket($ticket->id);

        $ticket = Ticket::find($ticket->id);

        $this->assertSame('active', $ticket->status);
        $this->assertNull($ticket->resolved_at, 'A reopened ticket must not stay marked as resolved.');
        $this->assertSame($hooksBefore + 1, did_action('fluent_support/ticket_reopen'));
    }
}
