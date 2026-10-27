# Phase 14 gate report (Gate F-2) — Insurance and Corporate Claims

Date: 2026-11-05. Status: **built and automatically tested; NOT signed off.**

| Check | Result |
|---|---|
| Slices S01–S13 | built |
| Golden-file split tests (deductible, co-pay, exclusion, limit, contract rate, visit / annual limit, rounding, rule co-pay, pre-authorization cap) | 11 cases pass; invariant payer + patient + adjustment = bill on every case and line |
| Payer with effective-dated rates; rates never overlap | tested |
| Policy for holder and dependents, valid dates, member numbers | tested |
| Eligibility records who verified; the null adapter never answers "eligible"; an expired policy cannot be eligible | tested |
| Pre-authorization caps and is consumed, released on cancel, cannot be overspent or decided twice | tested |
| Claim built from finalised invoice lines, lines tie to invoice, no second claim for the same invoice and payer | tested |
| Illegal transitions rejected; submitted claim locked; rejected / denied corrected as a new version; timeline append-only | tested |
| Scrub lists errors with fix hints; ready needs a clean scrub | tested |
| Batch export then mark submitted; null gateway never submits | tested |
| Queries answered; denial needs a reason code; worklist by code; appeals | tested |
| Remittance allocated: payment posted on the invoice, claim paid / part-paid, benefits used; no over-allocation, wrong payer | tested |
| Write-off needs a different approver | tested |
| Coordination of benefits (second policy) | tested |
| Corporate credit limit blocks finalising; statements once per invoice, issued with due date, paid down oldest first | tested |
| Invoice hook drafts exactly one claim; nothing when uninsured or module off | tested |
| Aging buckets and dashboard | tested |
| Cross-organization access (404/403), no-permission refusal on every route, viewer read-only | tested |
| Demo history (6 months) and per-module users | tested (DemoHistoryTest, DemoAccessTest) |
| Bangla parity | enforced by BanglaParityTest; wording needs native review |

## Not done / needs people
See D-P14 (officer sign-off of every example rule, payer-specific forms and APIs, real-user tests, pen test).
