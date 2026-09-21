# Coverage ledger

Every audited surface, classified honestly. Four states — nothing else:

- **DONE** — behaviourally tested: real boot, real request, values asserted.
- **THIN** — a detector or smoke test only; would miss a subtle regression.
- **PINNED** — a shipped fix is pinned by a source-contract detector, but no
  behavioural test exists.
- **LEFT** — known gap; the reason it's left is recorded, not implied.

Rules: every row carries file:line evidence. THIN and PINNED rows are the
next round's work-list. Moving a row to DONE requires a proof-of-catch note
(what was broken, what went red). Never delete a row — reclassify it.

| Surface | State | Evidence (test / detector) | Production ref | Notes |
|---|---|---|---|---|
| raw()/selectRaw()/whereRaw() table-prefix bug class | PINNED | tests/lint/raw-sql-prefix.php (proof: fixtures exit 1 with 3 planted hits) | app/, ../fluent-support-pro/app/ (304 files scanned) | founding bug class; runs on host, no WP |
| PHP parse/fatal lint, core + pro | THIN | tests/bin/run-all.sh `run_static` php -l loop | app/, boot/, database/ both plugins | syntax only |
| Route census vs manifests (124 GET + 127 mutating) | PINNED | tests/lint/route-coverage.php | app/Http/Routes/api.php + pro routes | new route without a manifest entry fails the gate bidirectionally |
| Every GET route, core + pro (124 routes, 226 cases) | THIN | tests/smoke/routes.manifest.php via tests/bin/run-smoke.php | app/Http/Routes/api.php | asserts status + no wpdb error + no exception under real DB w/ fstest_ prefix; caught FS-SMOKE-001..004 on first run |
| Every mutating route ×2 identities (254 cases) | DONE | tests/smoke/mutating.manifest.php via tests/bin/run-permissions.php | app/Http/Policies/ | 418 fuse proves gate outcome without mutation; proof: subscriber_allowed on an admin route went red |
| Portal any-logged-in-user contract (8 routes) | DONE | mutating.manifest.php `subscriber_allowed` entries | app/Http/Policies/PortalPolicy.php | subscriber leg asserts the gate PASSES (418) — locks the portal contract |
| Ticket lifecycle create→reply→close→reopen→property | DONE | tests/integration/10-ticket-lifecycle.php | app/Services/Tickets/TicketService.php, ResponseService.php | proof: removed resolved_at stamp (TicketService.php:28) → 2 cases red, restored → green |
| Ticket index filters/search/pagination | DONE | tests/integration/20-ticket-filters.php | app/Models/Ticket.php scopes | agent-written; tier verified twice, protected counts stable |
| fs_persons / fs_taggables / fs_meta discriminators | DONE | tests/integration/30-shared-table-discriminators.php | app/Models/{Customer,Agent,Tag,TicketTag,Meta}.php | global-scope cross-leak checks |
| Conversations + mail interception | DONE | tests/integration/40-conversation-and-mail.php | app/Services/Tickets/ResponseService.php | wp_mail intercepted process-wide |
| Literal-backslash preservation on the ticket/reply write path | DONE | tests/integration/45-content-slash-preservation.php | app/Services/Tickets/ResponseService.php:48, TicketService.php:184-185 | byte-for-byte assertion, not a substring probe — the boundary unslashes once, the services must sanitize only; proof: restored the `wp_unslash()` in both services → 6 assertions red (`C:\Users\test` → `C:Userstest`), removed again → green |
| Customer portal behavioural flow | DONE | tests/integration/50-customer-portal.php | app/Services/CustomerPortalService.php | includes portal close stamping (part of the proof-of-catch above) |
| Pro workflows via REST | DONE | ../fluent-support-pro/tests/integration/90-pro-workflows.php (symlinked) | pro app/Http/Controllers/WorkflowsController.php | skips by name when pro absent |
| SSE stream endpoint (fluent-bot resume) | LEFT | — | fluent-bot chat stream route | blocks the in-process runner on an open stream |
| OAuth redirect endpoints (dropbox/google) | LEFT | — | pro AuthorizeController.php:19 | wp_redirect terminates WP-CLI; browser flow only |
| External-plugin integrations (FluentCRM, FluentBoards) | THIN | smoke entries allow 400 "not installed" | app/Http/Controllers/ (integration routes) | lab has no external siblings; absence path is what's covered |
| Webhook listeners (telegram/slack/twilio/mail-piping) | LEFT | permission entries skip "public by design" | app/Http/Routes/api.php public group | need signed-payload fixtures to test bodies |
| Reporting response-growth without `type` | PINNED | KNOWN-FAILURE skips in routes.manifest.php | app/Modules/Reporting/Reporting.php:191 | FS-SMOKE-001; unskip on fix |
| fs_ai_activity_logs surface | PINNED | KNOWN-FAILURE skips in routes.manifest.php | database/DBMigrator.php (migrator unregistered) | FS-SMOKE-002 |
| preset-prompts without `type` (core + pro) | PINNED | KNOWN-FAILURE skips in routes.manifest.php | FluentBotService.php:11, pro AIService.php:16 | FS-SMOKE-003 |
| reset_avatar permission gates (agents + customers) | DONE | canonical denial entries in mutating.manifest.php | AgentController@resetAvatar, CustomerController@resetAvatar | FS-PERM-001 fixed: type-hinted binding dropped, findOrFail runs post-auth |
| WPFluent Person query possibly inheriting Customer global scope | LEFT | observed during integration mutation M25: `Person::where(...)->delete()` removed only the customer twin | vendor/wpfluent framework, app/Models/Person.php | unconfirmed framework oddity, not a plugin bug; investigate before relying on Person-level bulk queries in tests |
