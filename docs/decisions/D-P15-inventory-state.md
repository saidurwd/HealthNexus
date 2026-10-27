# D-P15 — Inventory, Procurement and Supply Chain: state at the end of the Phase 15 build

Date: 2026-11-10. Slices S01–S13 of guideline §26.4 are built and tested. This is not a sign-off by the stores / procurement managers.

## What is built
Item master (categories, units of measure with conversions, batch / expiry / serial tracking, FEFO or FIFO, reorder levels, link to a Phase 7 medication, billing code for patient-charged items); stores with shelves; suppliers with qualification, documents and expiry alerts, scorecards (a poor score suspends) and agreed prices; the stock model (ledger, projection, reservations, reconciliation and rebuild); requisitions and approval by amount; requests for quotation with comparison and award; purchase orders with budget check, versioned amendments, re-approval when an amendment raises the value, and emergency orders approved afterwards; goods receipt (counted, then inspected: accepted stocked, rejected quarantined, short shelf life refused, partial deliveries close lines correctly); supplier invoices with a three-way match and a second-person approval for mismatches, hand-over to Finance only when matched or approved; goods returned to suppliers and credited; transfers (in transit, shortfall returns to the sender), department requests and issues (partial issue, returns); point-of-care use with idempotent ChargeEvents; counts (variance posts only when a different person approves), adjustments, expiry sweep with proposed write-offs, holds and recalls with a trace; reorder suggestions from real use; dashboard and reports; screens, menu, Bangla; factories; six months of demo history and test users; the daily `inventory:check`.

## Decisions made here
- **Quarantine is a store, not a flag.** Rejected deliveries, held and recalled batches are moved (two ledger rows) to the organization's `QRN` store. A working store's on-hand therefore never contains unusable stock, and release puts stock back where it came from.
- **One approval chain** (`procurement_approvals`) for requisitions and purchase orders: the levels an amount needs, in order, each by a different person, never the requester.
- **An amendment is a new version**: the previous version is stored whole; if the new total reaches a level the order has not had, the order goes back to `pending_approval` and goods cannot be received until it is approved.
- **Transfers never lose stock**: dispatch takes it out of the sender, receive puts it in; whatever the receiver did not get is received back at the sender in the same step.
- **Expired stock is written off only through an adjustment** that a second person approves; the sweep blocks the batch and proposes the write-off, it never removes stock by itself.
- **Charged use is in whole units.** Billing stores charge quantity as an integer; a part-unit use of a charged item is refused rather than rounded.
- **Dates are compared with `whereDate`** (SQLite stores a time part, MySQL does not).

## Needs the stores / procurement managers before use (OD-15)
Item list, categories and reorder levels; approval thresholds and who holds each level; emergency ceiling and time to approve; invoice match tolerance; shelf-life rule; budgets per department; supplier qualification documents; who may approve adjustments and counts.

## Deferred
- **Pharmacy receiving adapter** (guideline 12.3): Phase 7 keeps its own receiving screen and may be supplied by a transfer later. Changing Phase 7 is outside this phase (AI-RULES 15).
- Consignment and vendor-managed inventory (the order type exists; consignment settlement against consumption does not); serial-number issue and return flows (the table and the item flag exist); stock reservation from requests (the reservation mechanism exists and is tested).
- Supplier tender / e-bidding portal, supplier self-service, barcode label printing and scanner receiving (hardware), import of an opening stock file through the Data import tool.
- Landed cost, weighted-average costing (valuation uses the batch cost, else the standard cost), VAT withholding at payment (Phase 21).
- Inventory API endpoints (`/api/v1/inventory`, `/api/v1/procurement`); real-user and load testing; native Bangla review.
- OT, IPD and ED screens do not yet call `InventoryConsumptionService` themselves; use is recorded from the Stores screen (the service and the ChargeEvent path are ready for them).
