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
