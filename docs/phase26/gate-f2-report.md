# Phase 26 · B1 — Patient welfare: Gate F-2 report

| Check | Result |
|---|---|
| Slice | built: funds and donations, means-test assessment, grants, discounts through Billing, daily check, screens, read-only API, demo history |
| Whole suite | last complete run 1,551 tests: 1,549 passed. The two failures were `HrHistoryTest` (the old date-dependent payroll test, HR code, not this work) and one demo assertion of mine, fixed since (see below). After the fixes the demo test passes (2/2) and the affected suites pass together: Finance, Welfare, Billing, Insurance, Ux, Localization, Phase0, Rbac, Security — 358 tests |
| Welfare tests | 38 (funds, ledger immutability, means test at every boundary, grants, Billing integration, the missed-event safety net, screens, journey with a second person at each decision, API) |
| Pint · phpstan (level 5; two baseline entries for `ModuleMenuFilter` removed, none added) · `registry:export --check` | clean · 0 errors · clean |
| Bangla | 156 new strings plus 22 for the navigation; **wording needs native review** |
| MySQL | the migration applies on MySQL; the six-month history ran there (70 patients assessed, 47 grants, 43 discounts approved by Billing, 0 steps skipped) |

## Defects found by running on real-shaped data
- **Finance's reconciliation counted every approved discount as a difference.** It compared Billing's invoice total (which a discount lowers) with the ledger's invoice entry (the discount is a separate journal). Welfare is the first thing to produce approved discounts in the demo, so it surfaced. Fixed in `ReconciliationService` (the bill is compared as it was finalised) and covered in `PostingTest`; the test failed before the fix.
- **Order of the demo history.** Finance posts a bill from its totals at the time it posts, so a bill already cut to nothing by a 100% waiver had no entry. The welfare history now runs after Finance, and where a month is already closed the finance manager reopens it to post the discount and closes it again, as an accountant would.
- **Billing has no way to reject an adjustment.** A discount Billing never approves stays "waiting for Billing", counts against the grant and is raised by the daily check. Recorded as a Billing follow-up.

## Scratch MySQL build (six months, on top of the full history)
3 funds; 41 gifts; 70 assessments (57 approved, 8 turned down, 5 left waiting); 47 grants; 43 discounts approved by Billing, 1 left waiting. Integrity queries, all **0**: fund overdrawn at any time · a fund with less than approved grants may take · grant's spent amount differing from its applied discounts · grant above its limit · applied discount without exactly one fund charge · fund charge differing from its discount · fund charged before Billing approved · assessment decided by its own assessor · grant approved by its own requester · grant for another patient than its assessment · discount on another patient's bill · grant valid beyond its assessment · gift without a receipt.

## Behaviour that is tested
| Rule | Status |
|---|---|
| A fund's balance is the sum of its entries; entries cannot be changed or deleted (database trigger); a mistake needs a reversal with a reason, once, never below what approved grants may use | tested |
| Income per person decides the suggested category at every boundary; circumstances move up one category, never two; the assessor cannot decide their own assessment; evidence is needed to submit | tested |
| A grant needs an approved, in-force assessment that found a need; a fund can cap one patient; the requester cannot approve; approval needs free money; one open grant per patient; expiry and stopping give the money back | tested |
| Applying a grant asks Billing for a discount and changes neither the bill nor the fund; Billing's approval lowers the bill and charges the fund and grant once; the amount is limited by the grant, its share of the bill and what is still to pay; another patient's bill and a bill already waiting are refused; a missed event is put right by the daily check | tested |
| Screens open for the super user and are refused without rights; income is only for the assessment right; another hospital's records do not exist; the API needs a login, the right and the header and carries no patient data | tested |

## Open for the hospital
Welfare committee and officer; the real thresholds and circumstances; rules for restricted money (zakat). See `docs/decisions/D-P26-bangladesh-pack.md`.
