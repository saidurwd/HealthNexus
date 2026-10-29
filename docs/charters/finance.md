# Finance and Accounting — Module Charter

_Updated in every slice (guideline 7.4). Phase 21 slices S00–S12 are built (decision OD-05: **build**). Every setting (period rules, write-off and payment approval, budget mode, ageing buckets) is an example awaiting the hospital's accountant. **No tax rate is built in** (OD-03): tax codes are the accountant's data._

- Phase / owner: 21 / **owner to be named by the hospital** (the finance manager and the accountant)
- Status: built · switchable per organization / hospital (`finance`, depends on `billing`); nothing is posted for a hospital until it has started its books (a fiscal year exists)
- Purpose: keep the hospital's books of account — double-entry, periods, receivables, payables, cash and bank, tax registers, budgets, fixed assets and the statements — fed automatically by what Billing, Insurance, Stores, Pharmacy, Payroll and the patient portal already do.
- Permission prefix: `finance` (31 permissions). Table prefix: `fin_`. Staff screens `/admin/finance/*`; read-only API `/api/v1/finance/*`.
- Owns: chart of accounts, account roles, cost centres, fiscal years and periods, journals and lines, the posting queue, receivable and payable open items, supplier payments, bank accounts/deposits/statements, tax codes and the tax register, budgets, fixed assets and depreciation, fee-share rules and runs.
- Does NOT own: invoices, payments, refunds, adjustments (Billing), claims and remittances (Insurance), goods receipts and supplier invoices (Inventory/Procurement), the stock ledger, payroll runs (HR), online-payment settlement files (Portal). Finance reads them and posts; it never changes them.
- Rules: a journal is saved only when balanced; once posted it is never changed or deleted (a database trigger refuses it) — a mistake is reversed by a second journal that points back; manual journals, supplier payments, write-offs and budgets need a second person; a locked or closed period refuses postings; the same source and event posts once (unique key); a posting that cannot be made waits in "Posting problems" with its reason and never holds up Billing.
- Posting engine: events `invoice.finalized`, `invoice.cancelled`, `payment.completed`, `refund.processed`, `adjustment.approved`, `advance.transaction`, `claim.accepted`, `remittance.line`, `insurance.writeoff`, plus `grn.posted` and the day's stock movements; a catch-up scan (`finance:post`, every ten minutes) finds anything an event missed. Accounts come from the account roles (data).
- Gateway: `FinanceGateway` is bound to the real adapter (`LedgerFinanceGateway`): payroll journals, insurer receivables and supplier invoices handed over by other modules. With Finance off it answers "not enabled" exactly like the old null adapter.
- Hooks: `finance:post` every ten minutes, `finance:check` daily.
- Settings keys: `finance.*` override `config/finance.php`.
- Reports: Billing-vs-books check, trial balance, account ledger, income statement, balance sheet, cash flow, profit by department, doctor revenue and fee share, receivable and payable ageing, VAT worksheet and tax register, budget variance, asset register; every table can be downloaded (separate export right).
- Known limitations & deferred items: see docs/decisions/D-P21-finance-state.md.

## Tables


