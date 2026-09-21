# FIX-PLAN.md — open bug/gap backlog

> Findings recorded by test runs. Nothing here is scheduled until a maintainer
> picks it up. Test-writing runs must IGNORE this file — the parked
> `KNOWN-FAILURE` skips stay parked.

| # | Item | Kind | Risk |
|---|---|---|---|
| FS-SMOKE-001 | response-growth reports die with SQL error `Unknown column '_id'` when `type` param is omitted | runtime / SQL | Medium |
| FS-SMOKE-002 | `fs_ai_activity_logs` table is never created — AIActivityLogsMigrator not registered in DBMigrator | runtime / migration | Medium |
| FS-SMOKE-003 | preset-prompts endpoints (core fluent-bot + pro ai) throw an uncaught TypeError when `type` param is omitted | runtime | Medium |
| FS-SMOKE-004 | MailBoxService::getTickets reads filter keys without isset — PHP warnings on every unfiltered request | code quality | Low |

## FS-PERM-001 — FIXED: pre-auth model binding leaked record existence

`POST /agents/reset_avatar/{agent}` and `POST /customers/reset_avatar/{customer}`
type-hinted a model, so `substituteParameters()` ran `findOrFail()` inside the
permission callback before any policy — unauthenticated callers could
distinguish existing from missing IDs via the model-not-found response. Fixed
by dropping the type-hints in `AgentController@resetAvatar` /
`CustomerController@resetAvatar` and resolving with `findOrFail()` inside the
authorized controller. The former `KNOWN-FAILURE FS-PERM-001` skips in
`tests/smoke/mutating.manifest.php` are now canonical denial assertions
(anonymous 401/`rest_forbidden`, subscriber 403/`rest_forbidden`) and guard
against regression. Any future route that type-hints a model reintroduces the
same shape — the permission tier will catch it only if the route is in the
manifest.

## FS-PERM-002 — FIXED: AgentPolicy throws; anonymous denial was 403, not 401

`app/Http/Policies/AgentPolicy.php` — `guardManageOptions()` throws
(deliberately, per the FS-SEC-003 comment) for add/update/delete of agents.
`Route::permissionCallback()` (Route.php:1138) catches the exception and uses
`$e->getCode() ?: 403` as the response status — the original throw carried no
code, so both anonymous and logged-in-but-forbidden callers got 403. Fixed by
having `guardManageOptions()` branch on `is_user_logged_in()`: anonymous
callers now throw with code 401, logged-in-but-non-admin callers throw with
code 403 (explicit, no longer relying on the `?: 403` fallback). The error
`code` in the response body stays `Permission Callback Error` rather than
`rest_forbidden` — that's inherent to throwing instead of returning `false`
from the policy (the deliberate FS-SEC-003 choice, so a specific message
replaces WordPress core's generic denial) and remains accepted cosmetic
drift. `tests/bin/run-permissions.php`'s `throws_denial` branch now asserts
the per-identity `$expectedStatus` (401 anonymous, 403 subscriber) instead of
a hardcoded 403; the three `agents:` manifest entries keep
`'throws_denial' => true` since the policy still throws.

## FS-SMOKE-001 — response-growth reports: `Unknown column '_id'` without `type`

**Where**: `app/Modules/Reporting/Reporting.php:191` —
`$filterColumn = $type."_id";` in `getResponseGrowthChart()`, used at line 197
as `->where($filterColumn, '>', 0)`. Reached from
`ReportingController@getResponseGrowthChart` (ReportingController.php:212),
routed as `GET /product-reports/response-growth` and
`GET /mailbox-reports/response-growth` (api.php:194, 201).

**Impact**: any request without `?type=` produces
`WordPress database error: Unknown column '_id' in 'WHERE'` — the endpoint
returns garbage instead of data or a validation error. (Compare
`getTicketResolveGrowth()` at Reporting.php:100, which guards with
`!empty($type)`.)

**Fix direction**: apply the same guard as line 100, or validate `type` in the
controller (`product|mailbox`) and 422 otherwise. Gate: the two
`KNOWN-FAILURE FS-SMOKE-001` skips in `tests/smoke/routes.manifest.php` become
passes.

## FS-SMOKE-002 — `fs_ai_activity_logs` table never created

**Where**: `database/Migrations/AIActivityLogsMigrator.php` defines the table,
but `database/DBMigrator.php` never calls it — a fresh activation creates all
other `fs_` tables and skips this one. `GET /ai-activity-logger`
(`AIActivityLoggerController@getAIActivities` → `Helper::getAIActivities` →
`paginate()`) then dies with `Table '..._fs_ai_activity_logs' doesn't exist`,
and every AI-activity write presumably fails silently too.

**Fix direction**: register `AIActivityLogsMigrator::migrate()` in
`DBMigrator.php` alongside the other migrators. Gate: the two
`KNOWN-FAILURE FS-SMOKE-002` skips in `tests/smoke/routes.manifest.php` become
passes on a freshly reset lab.

## FS-SMOKE-003 — preset-prompts endpoints TypeError without `type`

**Where**: core —
`app/Services/Integrations/FluentBot/FluentBotService.php:11` passes
`$request->get('type')` (null when absent) into
`FluentBotHelper::getPresetPrompts(string $type)`; pro — same shape at
`fluent-support-pro/app/Services/Integrations/AI/AIService.php:16` into
`AIHelper::getPresetPrompts(string $type)`. Routes:
`GET /fluent-bot/preset-prompts` (core), `GET /ai/preset-prompts` (pro).

**Impact**: uncaught `TypeError` (500) whenever the `type` query param is
omitted.

**Fix direction**: default the param (`$request->get('type', '')`) or validate
and 422. Gate: the two `KNOWN-FAILURE FS-SMOKE-003` skips in
`tests/smoke/routes.manifest.php` become passes.

## FS-SMOKE-004 — MailBoxService::getTickets undefined array keys

**Where**: `app/Services/MailerInbox/MailBoxService.php:216, 220, 224` —
`if ($filters['customer_id'])` etc. without `isset()`/`!empty()`. Route:
`GET /mailboxes/{id}/tickets`.

**Impact**: three PHP warnings on every request that omits the filters
(polluting logs; fatal under a warnings-as-errors handler).

**Fix direction**: use `!empty($filters['customer_id'])` per key. Gate: the
`KNOWN-FAILURE FS-SMOKE-004` skip on `mailboxes: tickets of a mailbox` in
`tests/smoke/routes.manifest.php` becomes a pass.
