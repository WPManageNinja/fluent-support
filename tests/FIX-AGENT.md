# FIX-AGENT.md — rules for a production-fix run

**Read this INSTEAD of `tests/AGENT.md` §2's "never fix production code" rule.**

`AGENT.md` governs *test-writing* runs. This is a **fix run**, and the rule is
deliberately inverted: your job here is to change production code. Every other
rule in `AGENT.md` still applies unchanged — especially proof-of-catch, the data
safety rules, and the prohibition on mocking.

Work the backlog in `tests/FIX-PLAN.md`, in order.

---

## 0. What makes this run safe

You are not fixing blind. Every item in the backlog already has a **test that
fails on it today**, written in an earlier run and parked as an executable
`KNOWN-FAILURE` skip. The suite is large (`SMOKE 258 · PERMISSIONS 292 ·
INTEGRATION 108 · JS 17`) and green, so it doubles as a regression net.

That gives you an objective definition of done for each item:

> the parked skip becomes a **pass**, and nothing else changes state.

---

## 1. The rule that replaces proof-of-catch

In a test run you break the code to prove the test works. Here it is reversed —
the test is already proven, so:

1. Run the suite. **Observe the specific skip**, and copy its exact text.
2. Make the fix.
3. Run again. **Observe that skip become a pass.**
4. Confirm the pass/fail/skip counts moved exactly as expected and nothing else
   changed.
5. Record before/after skip text in `tests/FIX-PLAN.md`.

If a fix does not flip its skip, it is not done. If it flips a skip you did not
intend to touch, stop and investigate before committing.

---

## 2. Absolute prohibitions

- **NEVER weaken, delete, narrow, or "adjust" a test to make it pass.** This is
  the single most damaging thing you could do tonight and it is unrecoverable
  without a careful diff review. The test is the specification. If a test seems
  wrong, do not touch it — record the disagreement in `FIX-PLAN.md` under
  `NEEDS DECISION` and move to the next item.
- **Never edit anything under `vendor/`** (CLAUDE.md rule 13), including the
  bundled League CSV tree. If a fix appears to require it, that is a
  `NEEDS DECISION`, not a task.
- **Never change a route's HTTP method or a public hook/filter signature.**
  Those are contracts with the Vue app and with third-party add-ons. Flag them.
- **Never touch real data.** 12,867 contacts / 69 campaigns. Verify counts
  before and after every suite run, as the existing suites do.
- **Never commit a red suite**, and never commit with `config/app.php` set to
  `env => 'dev'`.
- **Never bundle two backlog items into one commit.**

---

## 3. Definition of done, per item

Before committing any item, all of these must hold:

```bash
bash tests/bin/run-all.sh          # exits 0
```

- The item's parked skip is now a pass.
- Skip count dropped by exactly the number of items you fixed.
- `SMOKE 258/258 · PERMISSIONS 292/292 · JS 17/17` unchanged.
- Subscriber/campaign counts still `12,867 / 69`.
- `git diff` touches only files named in that backlog item, plus a
  `FIX-PLAN.md` entry.

Commit message: `fix: <short imperative summary>` — matching repo style
(`Fix XSS...`, `Refactor the Commerce Reports UI`).

Run the browser suite (`bash tests/bin/run-all.sh browser`, ~140s) only for
items marked **browser** in the backlog; it is too slow for every commit.

---

## 4. When to stop and flag instead of fixing

Write the item to `FIX-PLAN.md` under `NEEDS DECISION` and move on when:

- The correct behaviour is genuinely ambiguous and you would be guessing at
  product intent.
- The fix would change behaviour that existing callers, add-ons, or the Vue app
  may depend on.
- The fix requires editing `vendor/`.
- The fix requires a database migration or a schema change.
- You cannot make the fix without modifying a test.

**Flagging is a success, not a failure.** A clear question with the evidence
attached is worth more than a confident wrong guess made at 4am with nobody
awake to catch it. The maintainer reads `FIX-PLAN.md` first thing.

---

## 5. Output, per item

Append to `tests/FIX-PLAN.md`:

```markdown
## FIX N — <title> — <date/time>

**Status:** fixed | partial | flagged (needs decision)
**Files changed:** <paths>
**Commit:** <sha>

### Skip flipped
- before: `<exact skip text>`
- after:  `<pass line, or the remaining skip text if partial>`

### What was wrong
<root cause in 2-3 sentences — the mechanism, not a restatement of the symptom>

### What changed
<the approach, and why this approach over the alternatives>

### Regression risk
<what could this break; what you checked; what you could not check locally>

### Follow-ups / decisions needed
<anything left for the maintainer>
```

Be honest about partials. An item marked "fixed" that is actually half-done is
worse than one marked "partial", because it will not get a second look.
