# AGENT.md — rules for writing Fluent Support tests

**Read this completely before writing a single test.** `tests/README.md` covers
how to run things; this file covers how to write them.

You are extending a **working, proven** test harness. Do not redesign it. Do not
introduce PHPUnit, Docker, wp-env, or GitHub Actions — those were considered and
deliberately rejected (see `docs/TESTING_STRATEGY.md` §5). Everything runs
locally through WP-CLI.

---

## 0. Ground truth: how to run things

All commands run from the **plugin root**:

```bash
bash tests/bin/run-all.sh           # everything
bash tests/bin/run-all.sh static    # S0 lint only (fast)
bash tests/bin/run-all.sh smoke     # S1 REST smoke only

# a single suite, directly:
wp eval-file tests/bin/run-smoke.php
wp eval-file tests/bin/run-smoke.php -- --filter=lists
```

Paths are auto-detected (see `tests/bin/lib/resolve-wp-root.sh`) — never
hardcode a WordPress root, a site URL, or a table prefix in a test. Read the
prefix from `$wpdb->prefix` and the URL from `home_url()`.

This dev site uses a **non-default** table prefix on purpose: that is what makes
prefix bugs detectable at all.

**Every runner must exit non-zero on failure.** That is what makes the suite
usable. `FsTest::finish()` already does this; do not bypass it.

---

## 1. The rule that matters most: proof-of-catch

**A test you have never seen fail is worthless.** The dominant failure mode of
AI-written tests is passing whether the code works or not.

For every test you write, you MUST:

1. Break the thing the test covers (revert a fix, flip a comparison, delete a
   `where`, remove a prefix).
2. Run the suite. **Observe it go red**, and confirm the failure message actually
   describes the problem.
3. Restore the code.
4. Run again. Observe green.
5. Record the red output in your commit message or PR description.

If a test cannot be made to fail, delete it — it is asserting nothing.

Two worked examples already exist, both proven:

- `tests/lint/fixtures/bad-raw-prefix.php` — the linter's proof-of-catch. Run
  `php tests/lint/raw-sql-prefix.php tests/lint/fixtures` → exits 1 with 3 hits.
- The two `REGRESSION` entries in `tests/smoke/routes.manifest.php` — proven red
  against commit `403534031`.

---

## 2. Absolute prohibitions

Violating any of these makes the test worse than nothing:

- **Never mock `$wpdb`, the query builder, or models.** Every bug that has
  actually shipped from this repo was a runtime/SQL bug. A mocked-DB test passes
  happily on all of them. Mock only outbound third parties (SMTP, remote APIs).
- **Never `sleep()`**, never make real loopback HTTP calls, never run real
  Action Scheduler, never send real mail. Intercept `wp_mail` via the
  `pre_wp_mail` filter.
- **Never depend on pre-existing site data** for anything you assert on. Read-only
  smoke may *skip* when no row exists; an assertion must create its own fixture.
- **Never assert on log text or exact SQL strings.** Assert behaviour and results.
- **Never suppress a warning to make a test pass.** The warning is the finding.
- **Never fix production code inside a test PR.** If you find a bug, write a
  failing test, tag it `KNOWN-FAILURE`, and report it in `FIX-PLAN.md`. Leave the
  production code alone.
- **Never weaken or delete a `REGRESSION` case** in the smoke manifest.

---

## 3. Requirements for every test

- One behaviour per case. The name states the behaviour, not the method:
  `unsubscribed_contact_is_not_resurrected_by_import`, not `testUpdateOrCreate`.
- Arrange → Act → Assert, in that order, visibly.
- Fixtures use `FsTest::uniq()` so parallel/repeat runs never collide.
- **Clean up everything you create.** The dev site has 12,867 real contacts and
  69 real campaigns on it. Deleting or mutating real data is unacceptable. Create
  your own rows, delete them in a `finally`.
- When an assertion encodes a maintainer decision, comment the *business rule*
  and link the doc that decided it (e.g. `docs/COMPREHENSIVE_CODE_REVIEW_2026-07.md`).

### Caches

`FsTest::clearCaches()` runs before each suite and **must stay there**. The
2026-07-20 automation-reports bug was invisible behind a 5-minute transient: the
endpoint returned 200 while the code beneath it was entirely broken. If you add
a suite, clear caches first.

---

## 4. The harness API

`tests/lib/harness.php` — read it, it is short and commented.

```php
FsTest::boot();                       // error handler + admin login. Call once.
FsTest::case('name', function () {}); // run one case
FsTest::finish('SUITE NAME');         // summary + exit code. Call last.

FsTest::rest('GET', '/tickets', ['per_page' => 5]);
// => ['status'=>int, 'data'=>mixed, 'db_error'=>string, 'is_exception'=>bool, 'message'=>string]

FsTest::assertHealthy($result, 'label');       // no DB error, no exception, ok status
FsTest::assert($cond, 'detail');
FsTest::assertSame($expected, $actual, 'label');
FsTest::fail('detail');
FsTest::skip('reason');

FsTest::countQueries(fn);              // for locking in N+1 fixes
FsTest::uniq('prefix');                // collision-free fixture names
FsTest::clearCaches();
```

Extend the harness when you genuinely need a new primitive — but add it to
`harness.php` with a doc-block explaining *why*, never inline in a suite.

---

## 5. Output you must produce

Two destinations, and it matters which:

**Findings — a production bug you discovered — go in `tests/FIX-PLAN.md`.** That
file is the durable backlog the maintainer works from. Add an entry with the
symptom, `file:line` root cause, and which test is parked as `KNOWN-FAILURE`
against it. Findings recorded anywhere else get lost.

**Work you did — proof-of-catch, what you skipped, what you could not do — goes
in the commit message or PR description.** Include:

```
Cases added: <count>   Suite runtime: <seconds>

Proof-of-catch:
  <test name> — broke <what>, observed: <the red failure message>

Deliberately skipped:
  <route/behaviour> — <reason>

Needs a human decision:
  <anything surprising or ambiguous>
```

Be honest. Work reported "complete" that is actually partial destroys the value
of the whole exercise — it will not get a second look. If you get blocked, say
so, say why, and move on rather than stopping.

---

## 6. Working style

- Commit in coherent chunks with a clear message. **Never commit a red suite.**
  Before committing, run the tiers your change touched and put the real numbers
  in the message.
- If something would take much longer than expected, do the highest-value subset,
  say plainly that it is partial, and move on. Breadth beats depth here.
- Prefer many small, obvious, mechanical tests over few clever ones.
- When unsure whether behaviour is a bug or intentional: assert the *current*
  behaviour, tag the case `ASSUMPTION`, and flag it in `FIX-PLAN.md` for review.
- A tier that is flaky must be skipped with a documented reason, not reported
  green. A permanently red tier gets muted, and a muted tier catches nothing.
