# Phase 21 — Finance and Accounting: Gate F-2 report

Decision OD-05: **build** the accounting core. No tax rate is built in (OD-03).

| Check | Result |
|---|---|
| Slices S00–S12 | built |
| Whole suite | 1,307 tests on the first full run: 1,306 passed; the one failure (the stub-registry test expected the Finance gateway to be a null adapter) is fixed. Finance tests: 51 in `tests/Feature/Finance/*` (ledger core, posting, payables, cash/bank/tax/stock, statements, screens, commands and API) |
| Pint · phpstan (level 5, no baseline entries added) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; about 340 new strings — **wording needs native review by an accountant who reads Bangla** |
| MySQL | both migrations apply on a fresh MySQL database and the six-month history ran there. Two defects were found only on MySQL and fixed: `written_off` and `fully_depreciated` did not fit their status columns |

## Scratch MySQL build (six months)
8,411 journals posted (18 by hand), 3,879 receivable items, 47 supplier payables, 26 supplier payments (a second person approved each), 178 tills banked, 6 monthly bank statements (5 reconciled, the latest left open), 3 write-offs approved by a second person, 6 assets depreciated monthly, 14 tax-register entries, 0 postings left failed.

Integrity queries on that database, all **0**: journal that does not balance; journal whose total differs from its lines; total debits minus total credits; posting left failed; posted journal without a period; manual journal approved by its writer; finalised invoice with no journal. **Billing against the books: difference 0.00** on invoices billed (21,309,750.00), payments received (17,158,392.24), refunds (39,105.70) and the receivable items against the control accounts.

## Behaviour that is tested
| Rule | Status |
|---|---|
| An unbalanced journal cannot be saved for approval; a line is one-sided; a heading or a switched-off account takes no postings; a receivable/payable line needs a party; amounts are validated | tested |
| A posted journal cannot be changed or deleted — by the application **or by raw SQL** (database triggers) | tested |
| Reversal swaps the sides once, keeps the original, and a reversal cannot be reversed | tested |
| Maker-checker: manual journals, supplier payments, write-offs and budgets cannot be approved by their writer; sent back journals return to draft with the reason | tested |
| Locked, closed or missing periods refuse postings; reopening needs a reason; closing a year sweeps the result to retained earnings, locks everything, lists what blocks it | tested |
| The same source and event posts once (called twice, called from the scan, called from the gateway) | tested |
| Invoice, payment (cash, wallet, card, bank, insurer, corporate), refund, cancellation, discount/credit/debit/write-off adjustments, advances, insurer share, remittance disallowance, insurance write-off post correctly and reconcile with Billing to 0 | tested |
| A posting that fails (locked period, unmapped account) waits in the failed list with its reason, never breaks Billing, and posts after the cause is fixed; nothing is recorded before the books are started | tested |
| PO → goods received → supplier invoice → payment chain; tax withheld; payment reversal; ageing | tested |
| Stock movements valued once per day and kind; today is left for tomorrow; the scan resumes where it stopped | tested |
| Tills banked once; a shortage goes to cash over/short; statements imported (identical lines kept, duplicates skipped), matched, booked, reconciled; gateway settlement with fee | tested |
| VAT worksheet ties the register to the ledger; tax codes validated | tested |
| Budget variance (expense and revenue), purchase check (warn/block/off), approval | tested |
| Depreciation monthly, exact last month, never twice; disposal | tested |
| Income statement, balance sheet (balances), cash flow (explains the whole change), profit by cost centre; doctor revenue and the most specific fee-share rule | tested |
| Screens: all open with data; 403 without the right; another hospital's records are 404; CSV needs the export right and is spreadsheet-safe | tested (`DeskTest`) |
| API: needs a login, each endpoint its own permission, the hospital is the `X-Organization-Id` header and only if the user belongs to it | tested |
| Demo history and per-module users (finance manager, accountant, accounts officer, viewer) | tested (`DemoHistoryTest`, `FactoriesTest`) |

## Not done / needs people
See D-P21: the accountant's confirmation of the chart, every account role, tax codes and rates, approval rules and opening balances; opening stock value and assets; multi-currency, bank feeds, cost allocation and the tax return file are not built; the Procurement budget hook is a one-line change in Phase 15 code left for a separate slice; and a Billing/Portal rule to decide (an insured patient paying the insurer's share, which shows as an overpayment in the books).
