# Phase 15 gate report (Gate F-2) — Inventory, Procurement and Supply Chain

Date: 2026-11-10. Status: **built and automatically tested; NOT signed off.**

| Check | Result |
|---|---|
| Slices S01–S13 | built |
| Item with unit conversions; batch / expiry rules; duplicate code refused; expiry needs batches | tested |
| Balance equals the ledger sum; a hand-edited projection is detected and rebuilt; the ledger refuses UPDATE / DELETE | tested |
| Same posting key twice moves stock once | tested |
| No negative stock unless the store allows it; reservations reduce available, not on-hand | tested |
| Oldest expiry issued first; expired, held and recalled batches skipped or refused | tested |
| Supplier: documents required and valid to qualify; expiry stops ordering; disqualified supplier blocked from PO (also from an already drafted order); poor scorecard suspends | tested |
| Approval levels follow the amount; levels in order; each by a different person; nobody approves what they raised | tested |
| Quotation comparison; winning quote becomes a PO at the quoted prices; requisition marked ordered | tested |
| Department budget stops an order over budget | tested |
| Partial deliveries close PO lines correctly; over-receipt refused; rejects go to quarantine and cannot be issued; short shelf life refused; last cost updated | tested |
| Amendment keeps the old version; a bigger total goes back for approval and blocks receiving; cannot order below what arrived; cancel refused after receipt | tested |
| Three-way match inside / outside tolerance; billing for goods that did not arrive; mismatch needs a second person and never reaches Finance before | tested |
| Emergency order: ceiling, issued at once, ratified afterwards, overdue list, refusal stops the rest | tested |
| Supplier return takes stock out of quarantine once; credit note closes it | tested |
| Transfer atomic (two ledger rows), in-transit state, shortfall returns to sender, cannot overdraw or target itself | tested |
| Department request issued oldest-expiry first, partial issue, return of unused stock | tested |
| Count variance posts only when a different person approves; uncounted sheet and large unexplained difference refused | tested |
| Adjustment needs a second person and cannot overdraw | tested |
| Expiry sweep blocks the batch and proposes the write-off once; recall moves every batch to quarantine and traces where it went; hold and release return stock to the origin | tested |
| Point-of-care use: one ChargeEvent per use, no charge without patient or code, whole units only | tested |
| Reorder suggestions from real use, ignoring what is on order; dashboard and valuation agree with the ledger | tested |
| Cross-organization access (404), no-permission refusal (403), every screen draws | tested |
| Demo history (6 months) and per-module users | tested (InventoryHistoryTest, DemoHistoryTest, DemoAccessTest) |
| Bangla parity | enforced by BanglaParityTest; wording needs native review |
| MySQL | migrations and the six-month history are run on a scratch MySQL database before release (see the Phase 14 note about column widths) |

## Not done / needs people
See D-P15 (manager sign-off of every example threshold and tolerance, pharmacy receiving adapter, consignment settlement, scanner / label hardware, real-user tests, pen test).
