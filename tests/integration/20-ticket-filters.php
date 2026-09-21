<?php
/**
 * GET /tickets filtering.
 *
 * Fixtures span two mailboxes, three statuses and two priorities. Every
 * assertion narrows the response to this suite's own four ticket IDs before
 * counting, so unrelated site data (seed rows, real tickets, fixtures from
 * the other integration files) can never make a filter look correct or
 * incorrect.
 *
 * A filter that is silently ignored returns the *other* fixtures too, which
 * is exactly what these scoped counts catch.
 */

return function () {

    $boxA = FsFactory::mailbox();
    $boxB = FsFactory::mailbox();
    $customer = FsFactory::customer();

    $needle = FsTest::uniq('needle');

    $fixtures = [
        'active_a_normal'   => FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $boxA->id,
            'status'      => 'active',
            'priority'    => 'normal',
            'title'       => 'Filter active A',
        ]),
        'closed_a_critical' => FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $boxA->id,
            'status'      => 'closed',
            'priority'    => 'critical',
            'title'       => 'Filter closed A',
        ]),
        'new_b_normal'      => FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $boxB->id,
            'status'      => 'new',
            'priority'    => 'normal',
            'title'       => 'Filter new B',
        ]),
        'active_b_critical' => FsFactory::ticket([
            'customer_id' => $customer->id,
            'mailbox_id'  => $boxB->id,
            'status'      => 'active',
            'priority'    => 'critical',
            'title'       => 'Filter searchable ' . $needle,
        ]),
    ];

    $ownedIds = [];
    foreach ($fixtures as $key => $ticket) {
        $ownedIds[$key] = (int) $ticket->id;
    }

    /**
     * Return the IDs from a GET /tickets payload that are this suite's fixtures.
     *
     * Scoped to the four fixture IDs, not merely to the run marker: every file
     * in one run shares a marker, so marker-only scoping would pick up tickets
     * created by the other suites.
     *
     * @param array<string,mixed> $result
     * @return array<int>|null null when the response was not usable
     */
    $ownedIdsIn = function ($result) use (&$ownedIds) {
        // The controller returns a LengthAwarePaginator instance, not an array.
        $payload = isset($result['data']['tickets']) ? $result['data']['tickets'] : null;
        if (is_object($payload)) {
            $payload = json_decode(json_encode($payload), true);
        }
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            return null;
        }

        $ids = [];
        foreach ($payload['data'] as $row) {
            $row = (array) $row;
            if (in_array((int) $row['id'], $ownedIds, true)) {
                $ids[] = (int) $row['id'];
            }
        }

        sort($ids);

        return $ids;
    };

    $expect = function (array $keys) use ($ownedIds) {
        $ids = [];
        foreach ($keys as $key) {
            $ids[] = $ownedIds[$key];
        }
        sort($ids);

        return $ids;
    };

    $query = ['per_page' => 100];

    FsTest::case('unfiltered_ticket_list_contains_every_fixture', function () use (
        $query, $ownedIdsIn, $expect
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + ['filters' => ['status_type' => 'all']]);
        FsTest::assertHealthy($result, 'GET /tickets');

        $found = $ownedIdsIn($result);
        if ($found === null) {
            FsTest::fail('GET /tickets did not return a paginated tickets payload: '
                . wp_json_encode($result['data']));
            return;
        }

        FsTest::assertSame(
            $expect(['active_a_normal', 'closed_a_critical', 'new_b_normal', 'active_b_critical']),
            $found,
            'all four fixtures are listed without filters'
        );
    });

    FsTest::case('status_filter_returns_only_tickets_in_that_status', function () use (
        $query, $ownedIdsIn, $expect
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + ['filters' => ['status_type' => 'closed']]);
        FsTest::assertHealthy($result, 'GET /tickets?status_type=closed');

        FsTest::assertSame(
            $expect(['closed_a_critical']),
            $ownedIdsIn($result),
            'only the closed fixture is returned for status_type=closed'
        );
    });

    FsTest::case('new_status_filter_excludes_active_and_closed_fixtures', function () use (
        $query, $ownedIdsIn, $expect
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + ['filters' => ['status_type' => 'new']]);
        FsTest::assertHealthy($result, 'GET /tickets?status_type=new');

        FsTest::assertSame(
            $expect(['new_b_normal']),
            $ownedIdsIn($result),
            'only the new fixture is returned for status_type=new'
        );
    });

    FsTest::case('mailbox_filter_returns_only_that_mailboxes_tickets', function () use (
        $query, $ownedIdsIn, $expect, $boxA
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + [
            'filters' => [
                'status_type' => 'all',
                'mailbox_id'  => $boxA->id,
            ],
        ]);
        FsTest::assertHealthy($result, 'GET /tickets?mailbox_id=');

        FsTest::assertSame(
            $expect(['active_a_normal', 'closed_a_critical']),
            $ownedIdsIn($result),
            'mailbox A returns exactly its two fixtures'
        );
    });

    FsTest::case('priority_filter_returns_only_matching_priority', function () use (
        $query, $ownedIdsIn, $expect
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + [
            'filters' => [
                'status_type' => 'all',
                'priority'    => 'critical',
            ],
        ]);
        FsTest::assertHealthy($result, 'GET /tickets?priority=critical');

        FsTest::assertSame(
            $expect(['closed_a_critical', 'active_b_critical']),
            $ownedIdsIn($result),
            'both critical fixtures and nothing else'
        );
    });

    FsTest::case('mailbox_and_priority_filters_combine_with_and', function () use (
        $query, $ownedIdsIn, $expect, $boxB
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + [
            'filters' => [
                'status_type' => 'all',
                'mailbox_id'  => $boxB->id,
                'priority'    => 'critical',
            ],
        ]);
        FsTest::assertHealthy($result, 'GET /tickets?mailbox_id=&priority=');

        // Narrowing on two axes must intersect, not union.
        FsTest::assertSame(
            $expect(['active_b_critical']),
            $ownedIdsIn($result),
            'mailbox B + critical yields one fixture'
        );
    });

    FsTest::case('search_matches_ticket_title', function () use (
        $query, $ownedIdsIn, $expect, $needle
    ) {
        $result = FsTest::rest('GET', '/tickets', $query + [
            'filters' => ['status_type' => 'all'],
            'search'  => $needle,
        ]);
        FsTest::assertHealthy($result, 'GET /tickets?search=');

        FsTest::assertSame(
            $expect(['active_b_critical']),
            $ownedIdsIn($result),
            'search by title needle returns only the matching fixture'
        );
    });

    FsTest::case('search_for_an_absent_term_returns_no_fixtures', function () use ($query, $ownedIdsIn) {
        $result = FsTest::rest('GET', '/tickets', $query + [
            'filters' => ['status_type' => 'all'],
            'search'  => FsTest::uniq('nomatch'),
        ]);
        FsTest::assertHealthy($result, 'GET /tickets?search=<absent>');

        FsTest::assertSame([], $ownedIdsIn($result), 'a non-matching search returns none of our fixtures');
    });
};
