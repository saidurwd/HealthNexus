# D-E07 — Stock Ledger: state (H-06)

Done: `stock_ledger` (append-only; MySQL/SQLite triggers refuse UPDATE and DELETE; model also throws), `StockLedger` contract
(`post`, `onHand`), `DatabaseStockLedger`, view `v_stock_on_hand_all_stores`, `StockLedgerReconciler::pharmacyDrift()`.
`PharmacyStockService` (the single place pharmacy_stock changes) posts one ledger row per movement in the same transaction.
Existing pharmacy history was copied into the ledger by the migration, plus an `opening_balance` row wherever history and
on-hand disagreed.

Corrections are new rows (adjustment_gain / adjustment_loss), never edits.

Not done:
- Reservations, in-transit transfer state, four-eyes approval for adjustments/write-offs (guideline 12.2 rules 5-7).
- Negative-stock setting: pharmacy still always refuses negative stock.
- Blood units never use this ledger (guideline D-11). Inventory (Phase 15) must post here from day one.
- No scheduled drift check yet; run `StockLedgerReconciler::pharmacyDrift()` from a command or the ops dashboard.
- The ledger has no foreign keys to users/branches on purpose (cascade actions would be UPDATE/DELETEs).
