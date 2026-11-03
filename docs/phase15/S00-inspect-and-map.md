# P15-S00 — Inspect & Map: Inventory, Procurement and Supply Chain

Read-only report (guideline 26.4). Built on the owner's standing instruction to continue phase by phase: the 91-section `Phase 15 — Inventory - Procurement - Supply Chain` spec is not in the repository, so approval levels, tolerances, shelf-life and emergency rules below are **configurable examples** in `config/inventory.php` (overridable by `inventory.*` settings) for the stores and procurement managers to confirm (OD-15).

## What already exists (reused, not copied)
| Need | Owner | Note |
|---|---|---|
| Stock ledger and its contract | `App\Contracts\Stock\StockLedger`, `stock_ledger` (append-only, trigger-protected), `v_stock_on_hand_all_stores` | Inventory posts with store kind `inventory`; no second ledger |
| Pharmacy stock | Phase 7 `pharmacy_*` | Untouched. Drugs stay in the Phase 7 drug master; an item only carries `medication_id` |
| Blood units | Phase 13 `blood_*` | Untouched |
| Charges for patient-linked use | `ChargeEventPublisher` / `ChargeEventData` (module `INV`) | Billing prices; whole units only (the charge quantity is an integer) |
| Payables | `FinanceGateway::postPayable` (null adapter) | Called only for a matched or approved invoice |
| Numbers, public ids, alerts, settings, audit, module flag | `NumberSeriesService`, `HasPublicId`, `AlertService`, `SettingsService`, `AuditLogger`, `config/modules.php` key `inventory` (tables `inventory_*` / `procurement_*`, permissions `inventory.*` / `procurement.*`) | The module was registered earlier as unbuilt, with 9 legacy two-part permissions (`inventory.view` …) that stay for compatibility |
| Departments, branches, users | core | Referenced by id, never copied |

## Decisions (see D-P15)
- Enterprise stock lives in `inventory_stock` (a projection) kept equal to the ledger inside the same transaction; reconciliation and rebuild exist.
- Rejected, recalled and held stock is moved to one **quarantine store** per organization instead of being flagged in place, so "on hand" in a working store is always usable stock.
- Approval by amount is one generic chain (`procurement_approvals`) used by requisitions and purchase orders.
- Quantities are DECIMAL(18,4) in **base units**; purchase documents are in **purchase units** and converted on receipt.

## Questions for the owner (not blocking)
1. Approval thresholds (example: 50,000 and 500,000) and who holds levels 1–3.
2. The emergency-order ceiling (example 200,000) and the time to ratify (example 48 hours).
3. Price and quantity tolerance for the invoice match (examples: 2% price, 0% quantity).
4. Minimum remaining shelf life on delivery (example 30 days) and expiry warning windows (90 and 30 days).
5. Whether pharmacy purchasing should go through Phase 15 now (the receiving adapter is deferred, see D-P15).
