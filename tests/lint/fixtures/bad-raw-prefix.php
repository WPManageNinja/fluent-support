<?php
/**
 * Lint self-test fixture. NEVER loaded — scanned as text by
 * tests/lint/raw-sql-prefix.php, which MUST report violations here.
 * If the fixture scan exits 0, the lint is broken.
 */

class FsLintFixture
{
    public function violations($query)
    {
        // VIOLATION: bare qualified identifier inside selectRaw.
        $query->selectRaw("SUM(CASE WHEN fs_tickets.status = 'open' THEN 1 ELSE 0 END) as open_count");

        // VIOLATION: bare qualified identifier inside whereRaw.
        $query->whereRaw("fs_conversations.ticket_id = fs_tickets.id");

        // VIOLATION: orderByRaw with a bare identifier.
        $query->orderByRaw("fs_persons.first_name ASC");

        return $query;
    }

    public function correct($query)
    {
        global $wpdb;
        $t = $wpdb->prefix . 'fs_tickets';

        // OK: prefix interpolated inside the literal.
        $query->selectRaw("SUM(CASE WHEN {$t}.status = 'open' THEN 1 ELSE 0 END) as open_count");

        // OK: prefix concatenated immediately before the literal.
        $query->whereRaw($wpdb->prefix . 'fs_tickets.id > 0');

        // OK: grammar-wrapped qualified column — the grammar adds the prefix.
        $query->where('fs_tickets.status', 'open')->select(['fs_tickets.*']);

        // OK: raw with no table-qualified identifier at all.
        $query->raw('COUNT(*)');

        return $query;
    }
}
