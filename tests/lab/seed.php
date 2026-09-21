<?php
/**
 * Lab seed — a minimal, deterministic dataset so read-only smoke resolvers
 * always find a row. Idempotent: re-running setup does not duplicate rows.
 *
 * Runs via `wp eval-file` inside the lab only. Seeds are recognisable by the
 * fs-lab-seed marker in emails/titles.
 */

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\Tag;
use FluentSupport\App\Models\Ticket;

if (!defined('WP_CLI') || !WP_CLI) {
    exit("Seed must run via WP-CLI.\n");
}

if (!class_exists(Ticket::class)) {
    WP_CLI::error('Fluent Support is not active in the lab.');
}

// Activation does not create a mailbox (the onboarding wizard does); the lab
// seeds one so ticket routes have a working inbox.
$mailbox = MailBox::first();
if (!$mailbox) {
    $mailbox = MailBox::create([
        'name'       => 'fs-lab-seed-mailbox',
        'email'      => 'support@fs-lab.example.test',
        'box_type'   => 'web',
        'is_default' => 'yes',
        'settings'   => ['admin_email_address' => 'support@fs-lab.example.test'],
    ]);
}

// The lab admin as a Fluent Support agent (admins pass every policy, but an
// agent Person row makes assignment/report fixtures meaningful).
$adminUser = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID'])[0];
$agent = Agent::firstOrCreate(
    ['email' => $adminUser->user_email],
    [
        'first_name' => 'Lab',
        'last_name'  => 'Agent',
        'user_id'    => $adminUser->ID,
        'status'     => 'active',
    ]
);

$customer = Customer::firstOrCreate(
    ['email' => 'fs-lab-seed-customer@example.test'],
    [
        'first_name' => 'Seed',
        'last_name'  => 'Customer',
        'status'     => 'active',
    ]
);

$product = Product::firstOrCreate(['title' => 'fs-lab-seed-product']);
$tag = Tag::firstOrCreate(['title' => 'fs-lab-seed-tag'], ['slug' => 'fs-lab-seed-tag']);

// Pro's /ticket-tags surface reads fs_taggables scoped to tag_type 'ticket_tag'.
if (class_exists(\FluentSupport\App\Models\TicketTag::class)) {
    \FluentSupport\App\Models\TicketTag::firstOrCreate(['title' => 'fs-lab-seed-ticket-tag']);
}

$ticket = Ticket::where('title', 'fs-lab-seed-ticket')->first();
if (!$ticket) {
    $ticket = Ticket::create([
        'customer_id' => $customer->id,
        'agent_id'    => $agent->id,
        'mailbox_id'  => $mailbox->id,
        'product_id'  => $product->id,
        'priority'    => 'normal',
        'status'      => 'active',
        'title'       => 'fs-lab-seed-ticket',
        'slug'        => 'fs-lab-seed-ticket',
        'hash'        => md5('fs-lab-seed-ticket'),
        'source'      => 'web',
        'content'     => '<p>Seed ticket body for smoke resolvers.</p>',
    ]);

    Conversation::create([
        'ticket_id'         => $ticket->id,
        'person_id'         => $customer->id,
        'conversation_type' => 'response',
        'content'           => '<p>Seed customer response.</p>',
        'source'            => 'web',
    ]);

    Conversation::create([
        'ticket_id'         => $ticket->id,
        'person_id'         => $agent->id,
        'conversation_type' => 'response',
        'content'           => '<p>Seed agent response.</p>',
        'source'            => 'web',
    ]);
}

WP_CLI::success(sprintf(
    'Seeded: mailbox #%d, agent #%d, customer #%d, product #%d, tag #%d, ticket #%d',
    $mailbox->id, $agent->id, $customer->id, $product->id, $tag->id, $ticket->id
));
