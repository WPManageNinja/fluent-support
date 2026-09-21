# Fluent Support test suite

Local-only test suite for the Fluent Support family (core `fluent-support` +
`fluent-support-pro`). The core plugin hosts the harness; pro's tests live in
`../fluent-support-pro/tests/` and are symlinked in. Everything
WordPress-dependent runs inside the Docker lab — never against a dev site.

## Quick start

```bash
# one-time: stand up the lab (Docker required)
bash tests/bin/lab setup

# run everything
bash tests/bin/run-all.sh

# one tier
bash tests/bin/run-all.sh static        # host-only: php -l + lints
bash tests/bin/run-all.sh smoke        # every GET route
bash tests/bin/run-all.sh permissions  # every mutating route ×2 identities
bash tests/bin/run-all.sh integration  # behavioural tests
```

## The lab

`tests/lab/` — MariaDB 11.4 (strict modes, server-level) + WordPress + WP-CLI
on loopback port 8901. DB `fluent_support_test`, table prefix `fstest_`
(non-default on purpose: it catches raw SQL that bypasses the WPFluent
grammar's prefixing), `gmt_offset` 6 (non-UTC on purpose). Both plugins are
mounted read-only. `bash tests/bin/lab` gives `setup|reset|status|wp|logs`.

Seed fixtures (`tests/lab/seed.php`, idempotent, marker titles
`fs-lab-seed-*`): one mailbox, agent, customer, product, tag, ticket-tag,
ticket with two conversations. Note: plugin activation does NOT create a
default mailbox (the onboarding wizard does) — the seed creates its own.

## Tiers

| Tier | Runner | What it proves |
|---|---|---|
| static | `run-all.sh run_static` | php -l over core+pro; `lint/raw-sql-prefix.php` (the founding bug class); `lint/route-coverage.php` (route census vs manifests — a new route without a manifest entry fails the gate) |
| smoke | `bin/run-smoke.php` + `smoke/routes.manifest.php` | every GET route dispatched in-process as admin; asserts expected status, no `$wpdb->last_error`, no plugin exception |
| permissions | `bin/run-permissions.php` + `smoke/mutating.manifest.php` | every POST/PUT/DELETE route dispatched anonymously and as a subscriber; a post-permission/pre-controller fuse (418) makes any gate bypass visible without mutating the lab |
| integration | `bin/run-integration.php` + `integration/*.php` | behavioural flows with DB-backed assertions and exact-ID fixture cleanup (`lib/factory.php`) |

## Manifest conventions

- `routes.manifest.php`: `ok` (allowed statuses, default 2xx), `needs`
  (seed-backed resolver), `params`, `skip`, `requires_constant`
  (`FLUENTSUPPORTPRO` for pro routes — skip by name when absent).
- `mutating.manifest.php`: `expected` method counts (drift check), plus
  `subscriber_allowed` (PortalPolicy routes — subscriber must PASS the gate),
  `throws_denial` (AgentPolicy throws deliberately → canonical status per
  identity — 401 anonymous, 403 subscriber — with "Permission Callback Error"
  accepted in place of `rest_forbidden`), `skip`, `requires_constant`.

## Rules (see AGENT.md for the full set)

1. **Proof-of-catch** — never keep a test you haven't watched fail.
2. **Never mock the database.**
3. **Find bugs, don't fix them** — park as `KNOWN-FAILURE` skips, record in
   `FIX-PLAN.md` with file:line, keep the suite green. Fix passes are a
   separate task (`FIX-AGENT.md`).

Current parked findings: `FIX-PLAN.md`. Audited-surface map:
`COVERAGE-LEDGER.md`.
