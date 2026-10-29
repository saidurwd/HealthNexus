# Phase 21 — S00 Inspect and map (Finance and Accounting)

Decision OD-05: **build** the accounting core (owner confirmed "go ahead" on the build recommendation, 2026-10-29). The `FinanceGateway` stays the single hand-off, now with a real adapter.

## What already produces money events (reused, not copied)
| Source | Where | How Finance consumes it |
|---|---|---|
| Invoice finalised / cancelled | `BillingInvoice` (+items with `tax_amount`, `billing_items.category_id`), events `InvoiceFinalized`, `InvoiceCancelled` | listener → posting engine (`billing.invoice.finalized`) |
| Payment, refund, adjustment | `BillingPayment` (method, cashier session), `BillingRefund`, `BillingAdjustment`; events `PaymentCompleted`, `RefundProcessed`, `AdjustmentApproved`; seam `RevenuePostingInterface` (null) | listeners post; the seam is bound to a recorder that queues nothing extra |
| Advance deposits | `billing_advance_transactions` | liability (patient advances) |
| Cashier session close | `BillingCashierSession` (+ `CashierSessionClosed`) | bank deposit slip (S06) |
| Insurance | `InsuranceClaim` (payer_share), `InsuranceRemittance(+lines)`, `InsuranceWriteoff`; `FinanceGateway::postReceivable` | insurer receivable, cleared by remittance lines |
| Online payments | `portal_payments`, `portal_settlements(+lines)` | gateway clearing account, reconciled at settlement (S06) |
| Purchasing | `ProcurementGoodsReceipt(+grn lines unit_cost, accepted_quantity)`, `ProcurementSupplierInvoice` (tax, total, `finance_reference`); `FinanceGateway::postPayable` | GRN → inventory / goods-received-not-invoiced; invoice → payable |
| Stock | `stock_ledger` (quantity, unit_cost, txn_type, append-only), `InventoryAdjustment` | daily valuation journal per movement type (S08) |
| Payroll | `HrPayrollRun.journal` (balanced lines with role names), `PayrollJournalService::post` → `FinanceGateway::postJournal` | adapter maps the role names to accounts |
| Budgets | `ProcurementBudget` (Phase 15) | read for commitments; Finance owns the annual budget (S09) |

## Gaps found (nothing to fix in other modules)
- Billing items have a category but **no revenue account** → account roles map `revenue:<category code>` → account (Finance setting).
- There is **no supplier payment** record anywhere → Finance owns payment vouchers (S05).
- There is **no bank account / deposit / statement** → Finance owns them (S06).
- Billing tax is per item (`tax_rate`, `tax_amount`); VAT rates are a hospital/accountant matter (OD-03). Tax codes are Finance settings; nothing is hard-coded.
- No fixed-asset register exists; physical tracking is Phase 22.

## Design (summary)
- Tables `fin_*`: fiscal years/periods, cost centres, accounts, account roles, journals + lines, postings (idempotency and failure queue), open items for receivables and payables, supplier payments, bank accounts/deposits/statements, tax codes/entries, budgets, assets/depreciation, fee-share rules.
- A journal can only be saved **balanced**; once posted it is never changed or deleted — corrected by a **reversal** that points back to it. Manual journals need a second person (maker-checker). A **locked or closed period refuses postings**.
- **Posting engine:** one builder per event; accounts come from role mappings; each journal has a unique key (`source_type`, `source_id`, `event_key`) so a repeated event posts once. A failed posting is kept in a queue with its reason and retried (`finance:post`); the **Billing vs GL reconciliation** report must show 0.
- Finance never writes to other modules' tables; other modules never write to the ledger except through `FinanceGateway`/the listeners.
- Module key `finance` (exists, depends on `billing`); permissions `finance.*`; UI `/admin/finance/*`; API `/api/v1/finance/*` (reports, read-only).

## Slice plan
S01 accounts, cost centres, years/periods, settings · S02 journals, reversal, maker-checker, trial balance · S03 posting engine and billing events · S04 receivables · S05 payables and supplier payments · S06 cash, bank, wallet reconciliation · S07 tax · S08 payroll and stock postings · S09 budgets · S10 fixed assets · S11 statements, doctor revenue and fee share, department profitability · S12 hardening, demo history, gate.
