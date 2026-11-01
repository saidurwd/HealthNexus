# Phase 23 — Quality, Safety and Compliance: Gate F-2 report

| Check | Result |
|---|---|
| Slices S00–S09 | built |
| Whole suite | 1,402 tests: 1,400 passed on the full run. Of the two failures, one was this phase's own test helper (it cleared the command output; fixed, the demo-history test then passed: 2 tests, 225 assertions). The other, `HrHistoryTest` ("a correction run was paid"), is **not from this phase**: it fails the same way on the committed baseline (`1121166`) and began when the calendar rolled into November — it depends on the date. It is in HR (rule 15) and is reported separately. Quality tests: 55 in `tests/Feature/Quality/*` (incidents, CAPA, infection, programme, governance, screens and API) |
| Pint · phpstan (level 5, no baseline entries) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; 493 new strings — **wording needs native review** |
| MySQL | both migrations apply on MySQL and the quality history ran there, on top of the full six-month history, in about 40 seconds |

## Scratch MySQL build (six months)
175 incidents (48 reported without a name, 1 sentinel, 4 swept from the blood bank, 9 corrected by amendment), 10 investigations accepted by a second person, 134 actions (85 verified, 10 judged not effective, 37 past due), 122 incidents closed and 53 open; 3 deaths reviewed once each (1 waiting); 23 infections with 1 outbreak opened by the threshold and later closed, 36 isolations, 26 weeks of hand-hygiene rounds; 4 clinical audits; 8 documents (2 in a second version, 1 waiting) and 214 readings; 18 privileges granted by a second person, 3 waiting, 1 suspended; 7 licence items; 2 checklist assessments; 18 meetings minuted and 3 planned; 4 complaints raised as incidents; 13 indicators for 7 months.

Integrity queries on that database, all **0**: anonymous report with a reporter row · named report without one · first event naming a user · closed incident without closer or note · action checked by its owner · investigation accepted by its lead · document approved by its author · privilege decided by its requester · closed incident with an unaccepted investigation · closed incident with an open action · serious incident closed without an accepted investigation · document with two approved versions · approved version that is not current · emergency deaths without exactly one review · source event swept twice · meeting item without outcome in a minuted meeting · incident dated in the future.

## Behaviour that is tested
| Rule | Status |
|---|---|
| What was reported cannot be changed or deleted — by the application **or by raw SQL** (triggers); amendment, event and reporter rows only grow | tested |
| An anonymous report leaves no reporter row, no audit entry naming a user, no user on its first event; a named reporter is shown only to `quality.incident.reveal`, with a reason, recorded on the trail and in the audit log; the timeline and API never name a reporter | tested |
| A correction is an amendment; the original stays; only listed fields can be amended; a closed incident takes no new action | tested |
| A serious incident needs an investigation a second person accepts before closing; closing needs a review and no open action; reopening needs a reason | tested |
| Actions: owner does, a second person checks; one that did not work returns to its owner; overdue warns once; past dates, strangers as owners and unknown sources refused | tested |
| Infection: suspected → confirmed/ruled out once; hospital-acquired from the set day; isolation flag asked through a service; hand-hygiene percentage; an outbreak opens at the threshold on one ward and type and joins later cases | tested |
| Indicators are numerator over denominator; no denominator gives no value; readmission, repeat operation, deaths reviewed in time, hand hygiene and infection rates | tested |
| Each death gets exactly one review; classification validated; a clinical audit counts criteria and lists gaps | tested |
| Documents: author cannot approve; approved text frozen (model and SQL); a new version supersedes and asks everyone again; acknowledgements once | tested |
| Privileges: needs a valid licence, a second person, never the clinician; lapses with date and licence. Calendar warns weekly. Assessments need evidence and an action for every gap | tested |
| Meetings go agenda → held → outcomes → minutes; a death review becomes "discussed"; complaints raised as incidents carry only the number | tested |
| Screens open for the super user and are refused without rights; any staff member can report; a reporter sees only their own; a branch-limited user sees only their branches; another hospital's record is 404; the read-only API needs a login, the right and the header | tested |

## Open for the hospital
The quality owner, the harm level that starts an investigation, anonymity, the real checklist and privilege list, native review of Bangla. See `docs/decisions/D-P23-quality-state.md` (including isolation flags on other screens, a separate slice).
