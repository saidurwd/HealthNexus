# D-P26 — Bangladesh pack: state and decisions

Owner instruction (2026-11-02): "Go for Bangladesh gaps" — the gaps listed in `docs/ux/navigation.md`. Each is a slice of its own, built the same way as every phase (tables, services, screens, API where useful, demo history, tests, Bangla, charter, gate report). Order chosen by what a hospital meets first.

| Slice | Gap | State |
|---|---|---|
| **B1** | Patient welfare fund and waivers | **built** (this document) |
| B2 | Birth and death notification and certification (P-25), medico-legal flag | next |
| B3 | Address master: division → district → upazila → union (P-04) | planned (needs an additive change in Patients) |
| B4 | Maternity and newborn, immunisation (EPI) (Phase 24 packs) | planned |
| B5 | Outbreak watch (dengue and others); Quality already tracks outbreaks inside infection control | planned |
| B6 | Cabin and bed-class price lists as a view over Billing's price lists (P-23) | planned |
| B7 | Government-hospital flows (OPD ticket fees, DGHS returns beyond the national pack); Bangla digits as a user setting (P-02) | planned |

## B1 decisions
1. **A separate module, not a payer.** Guideline P-17 puts charity and government/NGO schemes in Insurance *as payers* that reimburse a claim. Welfare is the hospital spending money it already holds, so it is its own module (`wf_`). Insurance payer types `charity`, `ngo`, `government` stay for schemes that pay the hospital later.
2. **Billing owns the bill.** Welfare never edits an invoice. It requests a `discount` adjustment through `AdjustmentService` (reason: the grant) and Billing's approver decides. Billing has no way to reject an adjustment, so a discount that is never approved simply stays "waiting for Billing", counts against the grant, and is raised by the daily check.
3. **The fund is charged when Billing approves** (listener on `AdjustmentApproved`; `welfare:check` settles any that were missed). One disbursement per application (unique index), so a second event does nothing.
4. **The ledger never changes.** Balance = sum of entries; a trigger refuses update and delete on SQLite and MySQL. Corrections are reversals with a reason, only for donations, and never below what approved grants may still use.
5. **Money is set aside on approval, spent on Billing's approval.** "Free to give" = balance − what approved, unexpired grants may still take. Approval locks the fund row and checks it. An expired or stopped grant gives its money back.
6. **Two people at each decision.** The assessor cannot decide their own assessment; the requester cannot approve their own grant. Billing's approval is a third check.
7. **The means test is a suggestion.** Income per person against two example thresholds, plus points for circumstances (two points move up one category). The committee decides and the thresholds are the hospital's settings.
8. **Privacy.** Income, household and circumstances need `welfare.assessment.view`; the reports and the API carry totals only. A donor can be anonymous (the name is not shown, but the receipt is kept).
9. **Receipts** are numbered per year (`WR-2026-000001`); assessments `WA-…`, grants `WG-…`.

## Open for the hospital
- Who sits on the welfare committee and who is the welfare officer; the real thresholds, categories and circumstances; whether zakat money is restricted to certain kinds of patient or care (the fund records its kind; restricting use is a rule to add once the hospital states it).
- Whether grants should be able to cover a share of a future bill (today a grant is applied to a bill that exists).
- Native review of the Bangla wording.

## Deferred / known limitations
- **Finance posts a welfare discount like any other discount** (Finance already listens to `AdjustmentApproved`). A separate "welfare expense" account needs a mapping in Finance's "Where things are posted" — a Finance slice.
- **Billing cannot reject an adjustment**, so a never-approved discount waits. A reject action is a Billing slice.
- Free (no-charge) services that never produce a bill, and in-kind gifts, are not recorded.
- No SMS or letter of thanks to donors, and no donor statements for tax (a rule the hospital must confirm with its adviser).
- No import of existing donor lists or opening balances beyond entering them as gifts with a note.
