# Phase 20 — AI and Clinical Intelligence: Gate F-2 report

| Check | Result |
|---|---|
| Framework and five low-risk functions | built (summary, note draft, policy answers, figures questions, stock forecast); eight more registered, not built, cannot be switched on |
| Whole suite | 1,477 tests: 1,476 passed. The one failure is `HrHistoryTest` (payroll history depends on today's date); it fails the same way on the commit before this phase and is HR code, left alone (rule 15) |
| AI tests | 40 tests, all passed (gateway order, output guard, injection, de-identification, wrong patient, evaluation, approvals, kill switch, API, red team) |
| Pint · phpstan (level 5, no baseline entries) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by the localization tests (25 passed); 276 new strings — **wording needs native review** |
| MySQL | both migrations and the six-month AI history ran on scratch MySQL (about 2.7 minutes on top of the other modules) |

## Defects found on the six-month history (not by the small fixtures)
- Store roles lacked `ai.use`, so all 25 stock forecasts were refused. Granted; now 25 completed, none blocked.
- Policy search missed questions that used a different word ending or the document title. Added plain-ending removal and title search; all 7 sample questions now find their policy.

## Scratch MySQL build
2 providers, 2 models, 13 functions (5 on, 8 planned), 504 requests all completed, 504 drafts (264 accepted, 193 lapsed, 44 rejected, 3 open), 95 feedback entries, 6 incidents (5 injection attempts, 1 other), 1 passing evaluation run. 29 answers said "no supported answer" (questions the policies do not cover, on purpose).

Integrity queries, all **0**: output without the AI label · accepted draft without a reviewer · planned function switched on · function on with an unapproved model · approved model without a passing run · outside provider enabled · output with facts but no sources · blocked requests · drafts older than the window still open.

## Behaviour that is tested
| Rule | Status |
|---|---|
| Every request passes the gateway checks in order and is logged, including refusals; the log cannot be changed | tested |
| Unsupported, unsourced, foreign-sourced or action-telling lines are removed; URLs stripped | tested |
| Instruction-like text is scored, quoted as data, and a high score blocks the request | tested |
| Outside providers get de-identified data only unless a legal approval is recorded | tested |
| A model is approved only by a passing evaluation covering English and Bangla | tested |
| Planned functions cannot be switched on; a second person approves above low risk; pause and the master switch stop use | tested |
| Accepting needs the typed patient number; a mismatch is refused and opens an incident; nothing is written to a record | tested |
| Drift opens an incident; red-team pack passes | tested |
| Screens open for the super user and are refused without rights; the read-only API needs a login, the right and the header | tested |

## Open for the hospital
AI governance owner and committee, any outside provider and its legal approval, native review of Bangla. See `docs/decisions/D-P20-ai-state.md`.

---

# Phase 20b — four functions above low risk: Gate F-2 addendum

| Check | Result |
|---|---|
| Functions | medication review facts, radiology reading order, claim pre-check, early-warning screen (NEWS2) — built, **off until the owner sets them up and a second person switches them on, and only after the model passes that function's own evaluation**; four still planned (lab interpretation, ICU early warning, ED triage support, portal assistant) |
| Whole suite | 1,506 tests: 1,504 passed on the full run. Two failures: `HrHistoryTest` (the old date-dependent payroll test, not this work) and one assertion of mine in `DemoHistoryTest` (the SQLite demo had no finished, unreported radiology study to rank); the assertion now only requires use when a study is waiting, and the demo test was re-run (see below) |
| AI tests | 69 passed (29 new: review functions, claim check, screens; governance and red-team extended) |
| Pint · phpstan (level 5, no baseline entries) · `registry:export --check` | clean · 0 errors · clean |
| Localization, Ux, Rbac, Phase0, Security suites | 142 passed |
| Bangla | 80 new strings in this slice (no missing; **wording needs native review**) |

## Defects found by running on real-shaped data (not by the small fixtures)
- **MySQL only:** the draft `kind` column is 14 characters; `medication_review` is 17. Every medicine sheet was refused on save while SQLite accepted it. The kind is now `medicines`.
- **A forbidden word matched medicine names:** `inject` matched "Ceftriaxone Injection", so injectable medicines were silently dropped from the sheet (54 lines in 26 sheets). The rule is now the imperative `inject `; a test keeps injectable medicines listed.
- **Setting up the local engine would have tried to switch on every built function**, including the new ones, and failed for a single user. It now switches on low-risk functions only.
- **A refused request with hidden instructions opened no incident** (only completed ones did). It now does.
- **Phase 20's note draft and record read-back showed no vital signs** on real data: they read only `encounter_vitals`, which current flows no longer write. They now read `vital_signs` too.

## Scratch MySQL build (six months, on top of the full history)
40 medicine sheets, 45 early-warning screens, 40 claim checks, 7 reading orders — all completed, none refused; 0 lines removed from any draft; incidents back to the original 6 (5 hidden-instruction questions, 1 reported problem).

Integrity queries, all **0**: unlabelled output · accepted draft without a reviewer · function above low risk switched on by the person who set it up · function above low risk on without its own passing evaluation · outside provider enabled · blocked requests · output with facts but no sources.

## Behaviour that is tested
| Rule | Status |
|---|---|
| Setting up the local engine switches on only the five low-risk functions; the four others are built but planned/off | tested |
| Each higher-risk function has its own passing evaluation; without one it cannot be switched on; the person who set it up cannot approve it; a hospital set up before they were built gets them registered, still off | tested |
| NEWS2 points are right at every boundary; each input, its points and the total are shown; missing or old vitals are said to be unscored; fewer than three recent ones is refused; one parameter scoring 3 is marked | tested |
| The medicine sheet lists allergies, medicines and counts, restates Pharmacy's alerts as raised, leaves the decision blank, refuses a medicine name carrying three instruction signs, and drops (never repeats) a milder instruction | tested |
| The reading order is stat → urgent → routine, then longest wait; reported studies are left out; nothing in Radiology changes; no right or no studies is refused | tested |
| The claim check restates failed scrub rules, documents, deadline and queries; changes nothing; does not repeat an instruction in a payer's query; needs `insurance.claim.view`; cannot see another hospital's claim | tested |
| Accepting needs the typed patient number; a wrong one opens an incident; nothing is written to any record | tested |
| The four screens open for the super user, are refused without rights, run only once on; tiles appear only for functions that are on | tested |

## Open for the hospital
Whether each function is wanted and who owns it (pharmacy lead, radiology lead, claims manager, the clinical lead for the screen); clinician-written evaluation cases (the starter sets prove the guards, not clinical value); native review of Bangla. See `docs/decisions/D-P20-ai-state.md`.
