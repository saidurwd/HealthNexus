# Phase 17 — Patient Portal, Mobile and CRM: Gate F-2 report

| Check | Result |
|---|---|
| Slices S00–S13 | built (S13 = interfaces and fakes only, by design) |
| Whole suite | 1,169 tests before the final fixes: 1,167 passed; the 2 failures (route-security allowlist for the public config endpoint and sign-in pages) are fixed. Portal tests: about 195 in `tests/Feature/Portal/*` |
| Pint · phpstan (level 5, no new baseline entries) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; about 520 new strings — **wording needs native review** |
| MySQL | all three migrations apply on a fresh MySQL database; the six-month history ran there. Two defects were found only on MySQL / at scale and fixed: the demo picked deceased or merged patients (the portal rightly refused them), and the payment service was built before the sandbox provider was switched on |

## Scratch MySQL build (six months)
159 patient logins (54 through the front-desk invitation), 1,015 messages (32 urgent), 117 refill requests, 378 online payments, 124 visit ratings, 47 forms, 23 complaints worked through the steps (two left late on purpose), about 250 logged contacts, 18 campaigns, 6 settlement files (366 lines matched, 6 differences kept for the screen), 1,083 results released, 8 critical results still held, 45 kept back.

Integrity queries on that database, all **0**: released result without a time; critical result released without an acknowledged alert; marketing message to someone who had not opted in; online payment posted to Billing twice; online payment whose amount differs from the Billing payment; campaign approved by its author; invoice where paid + due ≠ total; active login without a password.

## Behaviour that is tested
| Rule | Status |
|---|---|
| Registration matches an existing patient only on patient number / national id + date of birth + the phone on file, then a code to that phone; a miss, a wrong date and a wrong phone look the same and send nothing; limits counted per number and per address | tested |
| Codes: hashed, ten minutes, five tries burn the code, used once, resend limit | tested |
| Front-desk invitation code works once, needs the date of birth | tested |
| New device needs a code; "always ask" setting; five wrong passwords lock the number; unknown number and wrong password give the same answer | tested |
| Sanctum token carries only the ability `portal`; patient token refused on staff routes and the reverse; staff session cannot open patient pages; hospital taken from the account, not a header; suspended or closed login loses its tokens at once; expired token refused | tested |
| Every patient API route refuses a signed-out caller; every id the API takes returns 404 for another patient's record (appointments, results, bills, payments, threads, documents, refills, complaints); `?patient=` for a non-relative is 403 | tested (`PortalSecurityTest`) |
| Mass assignment cannot move a record to another patient or hospital or set status; amount and outcome of a payment cannot be dictated | tested |
| No secrets in responses (hashes, tokens, keys, push token) | tested |
| Booking uses the staff slot lock (second booking of the same slot refused); reschedule keeps the old appointment if the new one fails; cutoff hours; tampered or foreign slot token refused | tested |
| Digital queue shows people ahead, never who | tested |
| Withheld results are invisible and their content never leaks; sensitive words force clinician release; delayed release; "in person only" needs a reason; critical never auto-released and needs an acknowledged alert; withdrawing a release | tested |
| Refill: request only, duplicate and old prescriptions refused, decided once | tested |
| Payment: nothing posted until the provider confirms; same key once; one in progress per bill; part payment; over-amount refused; declined / pending / expired; bill settled at the counter meanwhile → "to refund"; callback re-verified; settlement matching | tested |
| Messages: urgent text flagged and told to call; staff reply notifies; closed thread; HTML stripped; no order created | tested |
| Consent per channel and purpose, STOP, quiet hours, held texts re-checked at send time, weekly marketing cap, consent versions kept | tested |
| Relatives: approval by staff only, scopes, adolescent privacy, ends at 18, expiry, revocation by either side, logged as proxy access | tested |
| Documents: expiring, limited opens, revocable, owner only, only released items | tested |
| CRM: groups (unknown condition refused; diagnosis group needs basis + second approver + consent), campaigns (second approver, consent, quiet hours, once per patient), ratings (once, window, own visit; poor rating opens a follow-up), complaints (numbers, steps, deadlines, escalation, satisfaction once), forms (validation, encrypted answers, versions) | tested |
| Export of own data only, closing a login keeps the clinical record, sync lists only what the caller may see, paging capped, ETag 304, language by header | tested |
| Accessibility basics on the patient pages (labels on every field, language declared, one heading, button text) | tested; a real assistive-technology audit is still needed |
| Staff screens: every screen draws with data; 403 without permission; 404 for another hospital | tested |
| Demo history and per-module users (portal and crm managers and viewers, patient-relations roles, call-centre agent) | tested (`DemoHistoryTest`, `FactoriesTest`) |

## Not done / needs people
See D-P17 (SMS provider, payment aggregator and webhook signature, push, video; confirmation of every number and the release policy per test; native Bangla review; accessibility audit with patients on low-cost phones; penetration test including response-time measurements on registration; load test of booking; the mobile app itself).
