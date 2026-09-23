
# Phase 15 — Inventory / Procurement / Supply Chain
## Module Specification & AI Implementation Prompt

**Architecture:** Laravel Modular Monolith  
**PHP:** 8.4+  
**Database:** MySQL / PostgreSQL  
**Frontend:** Blade + AdminLTE  
**API:** REST `/api/v1`  
**Queue/Cache:** Redis  
**Phase:** 15 of 21  
**Status:** Production-Ready Specification

---

# 1. Purpose

Phase 15 introduces the centralized **Inventory / Procurement / Supply Chain Management** module for the Hospital Management System.

The module manages the enterprise-wide lifecycle:

```text
Demand
 ↓
Requisition
 ↓
Approval
 ↓
Supplier Selection / RFQ
 ↓
Quotation
 ↓
Purchase Order
 ↓
Goods Receipt
 ↓
Quality / Quantity Verification
 ↓
Put-away
 ↓
Inventory
 ↓
Internal Transfer / Issue
 ↓
Consumption
 ↓
Reorder
```

It provides a common enterprise inventory and procurement foundation for:

- Medical supplies
- Surgical supplies
- Consumables
- General hospital supplies
- Office supplies
- Cleaning supplies
- Engineering/maintenance items
- IT consumables
- Pharmacy procurement
- Blood Bank consumables
- Laboratory consumables
- Radiology consumables
- OT supplies
- ICU supplies
- Emergency supplies
- Other configurable inventory categories

---

# 2. Core Architectural Principle

Phase 15 owns:

> **Enterprise procurement, purchasing, inventory, stock movement, suppliers, receiving, warehouses, and supply-chain processes.**

It does **not** own specialized clinical workflows.

The architecture is:

```text
Department / Clinical Module
        ↓
Material / Supply Request
        ↓
Procurement
        ↓
Purchase Order
        ↓
Goods Receipt
        ↓
Inventory
        ↓
Issue / Transfer
        ↓
Consuming Module
```

---

# 3. Critical Boundary Rules

The AI must inspect Phases 0–14 before coding.

Do not duplicate:

- Patient/MPI
- Encounter
- Appointment
- Clinical Orders
- Billing
- Invoice
- Payment
- Pharmacy medication master
- Pharmacy dispensing
- Blood Bank unit inventory
- Laboratory test/result system
- Radiology/PACS
- OT clinical workflow
- ICU clinical workflow
- Emergency clinical workflow
- Insurance claims
- Nursing/MAR
- Admission/Bed Management
- User/RBAC
- Organization/Hospital/Branch
- File Management
- Notifications
- Workflow/Approval
- Audit
- API infrastructure

---

# 4. Specialized Inventory Boundary

Phase 15 is the **enterprise supply-chain engine**.

Specialized modules remain responsible for domain-specific inventory.

## Pharmacy

Phase 7 owns:

- Medication
- Drug formulation
- Pharmacy dispensing
- Medication batches
- Pharmacy-specific stock logic

Phase 15 may provide:

- Supplier
- Purchase Order
- Procurement
- Goods Receipt
- Enterprise purchasing
- Procurement history

Do not replace pharmacy's clinical dispensing workflow.

## Blood Bank

Phase 13 owns:

- Blood units
- Blood components
- Blood storage
- Blood traceability

Phase 15 may procure:

- Blood bags
- Reagents
- PPE
- Consumables
- Other supplies

Never treat blood units as ordinary inventory items.

## Laboratory

Phase 5 owns laboratory testing.

Phase 15 may manage procurement of:

- Reagents
- Tubes
- Consumables
- PPE
- Laboratory supplies

## OT

Phase 10 owns surgical workflow.

Phase 15 manages:

- General supplies
- Surgical consumables
- Procurement
- Warehouse stock
- Implant procurement support

Clinical implant usage remains with OT.

## Radiology

Phase 6 owns radiology/PACS.

Phase 15 manages:

- Contrast procurement where applicable
- Film/consumables
- PPE
- General supplies

---

# 5. Enterprise Supply Chain Hierarchy

Recommended hierarchy:

```text
Organization
   ↓
Hospital
   ↓
Branch
   ↓
Warehouse / Store
   ↓
Storage Area
   ↓
Bin / Rack / Shelf
   ↓
Inventory Item
   ↓
Batch / Lot / Serial
```

Support multiple warehouses and stores.

Examples:

```text
Central Medical Store
Pharmacy Store
OT Store
ICU Store
Laboratory Store
Engineering Store
IT Store
General Store
Emergency Store
```

---

# 6. Item Master

Create a centralized non-clinical material/item master.

Item categories may include:

```text
Medical Consumable
Surgical Consumable
Laboratory Consumable
Radiology Consumable
Pharmacy Procurement Item
Blood Bank Consumable
PPE
Cleaning Material
Office Supply
IT Consumable
Engineering Spare
Furniture
Equipment
Other
```

Item fields:

- Item code
- Item name
- Category
- Subcategory
- Description
- Brand
- Manufacturer
- Model
- Specification
- Unit of measure
- Purchase unit
- Stock unit
- Issue unit
- Conversion factor
- Barcode
- SKU
- HSN/Tax code where applicable
- Reorder level
- Minimum stock
- Maximum stock
- Safety stock
- Lead time
- Preferred supplier
- Batch tracking required
- Serial tracking required
- Expiry tracking required
- QC required
- Active status

---

# 7. Item Classification

Support:

```text
Category
Subcategory
Item Group
Item Type
Criticality
ABC Class
VED Class
Storage Class
```

Criticality:

```text
Critical
Essential
Normal
Non-critical
```

Inventory analysis can support:

- ABC
- VED
- FSN
- HML

These classifications should be configurable.

---

# 8. Unit of Measure

Support:

```text
Piece
Box
Pack
Carton
Bottle
Vial
Tube
Kg
Gram
Liter
ml
Meter
Roll
Pair
Set
Kit
```

Support conversions:

```text
1 Carton = 10 Boxes
1 Box = 100 Pieces
```

Conversion rules must be explicit and auditable.

Do not silently convert units.

---

# 9. Batch / Lot Tracking

For applicable items support:

- Batch number
- Lot number
- Manufacture date
- Expiry date
- Supplier
- Purchase order
- Receipt
- Unit cost
- Quantity
- Remaining quantity
- Storage location
- Quality status

Expired items must not be issued.

---

# 10. Serial Number Tracking

For serialized assets/equipment:

- Serial number
- Manufacturer
- Model
- Warranty
- Purchase order
- Receipt
- Supplier
- Location
- Custodian
- Status

This should integrate with future/ existing Asset Management where applicable.

Do not create a duplicate asset lifecycle system if one already exists.

---

# 11. Supplier Management

Support:

- Supplier master
- Supplier code
- Legal name
- Trade name
- Address
- Contact person
- Phone
- Email
- Tax/VAT information
- Bank details
- Payment terms
- Credit terms
- Supplier category
- Product categories
- Registration documents
- Contract
- Status
- Risk classification

---

# 12. Supplier Qualification

Support supplier onboarding:

```text
Supplier Registration
 ↓
Document Verification
 ↓
Evaluation
 ↓
Approval
 ↓
Active Supplier
```

Supplier status:

```text
Draft
Pending Review
Approved
Suspended
Blocked
Expired
Inactive
```

---

# 13. Supplier Documents

Use existing File Management.

Documents may include:

- Trade license
- Tax certificate
- VAT certificate
- Bank information
- Product certificates
- Regulatory licenses
- Contract
- Manufacturer authorization
- Quality certificates
- Other required documents

Do not build another document storage engine.

---

# 14. Supplier Evaluation

Support configurable scoring:

```text
Price
Quality
Delivery
Service
Compliance
Product Availability
Warranty
Response Time
```

Do not hardcode scoring weights.

Store:

- Evaluation period
- Evaluator
- Criteria
- Score
- Comments
- Corrective actions

---

# 15. Purchase Requisition

Departments may request materials.

Workflow:

```text
Department
 ↓
Material Requisition
 ↓
Approval
 ↓
Procurement Review
 ↓
RFQ / Direct Purchase
 ↓
Purchase Order
```

Requisition fields:

- Requisition number
- Requesting department
- Hospital
- Branch
- Warehouse/store
- Requester
- Required date
- Priority
- Item
- Quantity
- UOM
- Justification
- Estimated cost
- Preferred supplier
- Budget reference
- Status

---

# 16. Requisition Status

```text
Draft
Submitted
Under Review
Approved
Partially Approved
Rejected
Converted to RFQ
Converted to PO
Cancelled
Closed
```

Never allow arbitrary status changes.

---

# 17. Approval Workflow

Reuse the existing Workflow/Approval module.

Example:

```text
Requester
 ↓
Department Head
 ↓
Procurement
 ↓
Finance
 ↓
Management
```

Approval thresholds must be configurable.

Example:

```text
< BDT 50,000 → Department Head
BDT 50,000–500,000 → Manager
> BDT 500,000 → Senior Approval
```

These are configuration examples, not hardcoded rules.

---

# 18. RFQ — Request for Quotation

Support:

- RFQ number
- Requisition reference
- Items
- Quantities
- Required delivery date
- Suppliers invited
- Response deadline
- Terms
- Attachments
- Status

Workflow:

```text
Requisition
 ↓
RFQ
 ↓
Supplier Invitations
 ↓
Quotation
 ↓
Comparison
 ↓
Selection
```

---

# 19. Supplier Quotation

Capture:

- Supplier
- Quotation number
- Quotation date
- Validity
- Item
- Quantity
- Unit price
- Discount
- Tax
- Delivery charge
- Other charges
- Lead time
- Warranty
- Payment terms
- Delivery terms
- Total amount

---

# 20. Quotation Comparison

Provide side-by-side comparison:

```text
Supplier A
Supplier B
Supplier C
```

Compare:

- Unit price
- Total price
- Tax
- Delivery
- Lead time
- Warranty
- Payment terms
- Quality score
- Supplier rating

Do not automatically select the cheapest supplier.

The system may highlight differences, but authorized procurement users make the final selection.

---

# 21. Purchase Order

Purchase Order lifecycle:

```text
Draft
 ↓
Approval
 ↓
Approved
 ↓
Sent to Supplier
 ↓
Acknowledged
 ↓
Partially Received
 ↓
Fully Received
 ↓
Closed
```

Alternative:

```text
Cancelled
Rejected
Expired
```

---

# 22. Purchase Order Fields

Conceptually:

```text
id
organization_id
hospital_id
branch_id
po_number
supplier_id
requisition_id
rfq_id
quotation_id
warehouse_id
order_date
expected_delivery_date
currency
payment_terms
delivery_terms
subtotal
discount
tax
shipping
other_charges
grand_total
status
approved_by
approved_at
created_by
created_at
updated_at
```

Actual schema must follow repository conventions.

---

# 23. Purchase Order Line

Each line should support:

- Item
- Description
- Specification
- Quantity
- UOM
- Unit price
- Discount
- Tax
- Expected delivery
- Received quantity
- Accepted quantity
- Rejected quantity
- Remaining quantity

---

# 24. Goods Receipt

Goods Receipt Note / GRN workflow:

```text
Purchase Order
 ↓
Delivery
 ↓
Goods Receipt
 ↓
Quantity Verification
 ↓
Quality Inspection
 ↓
Accepted / Rejected
 ↓
Inventory Update
```

Never increase inventory merely because a PO was created.

Inventory increases only after valid receipt processing.

---

# 25. GRN

Fields:

- GRN number
- PO
- Supplier
- Warehouse
- Delivery note
- Supplier invoice reference
- Receipt date
- Receiver
- Inspection status
- Quantity status
- Notes
- Attachments

---

# 26. Partial Receiving

Support:

```text
PO Quantity = 1,000
Received = 600
Remaining = 400
```

Do not automatically close the PO.

---

# 27. Quality Inspection

For QC-required items:

```text
Received
 ↓
Quarantine
 ↓
Inspection
 ↓
Accepted / Rejected / Partial
 ↓
Inventory
```

Support:

- Inspection checklist
- Sample quantity
- Pass/fail
- Defect
- Inspector
- Date
- Certificate
- Remarks

---

# 28. Rejected Goods

Rejected goods must not become available stock.

Workflow:

```text
Receipt
 ↓
Rejected
 ↓
Quarantine
 ↓
Supplier Return / Replacement / Disposal
```

Track reason and authorization.

---

# 29. Inventory Ledger

Inventory must use a complete transaction ledger.

Transactions:

```text
Opening
Purchase Receipt
Purchase Return
Transfer In
Transfer Out
Issue
Return
Adjustment
Damage
Expired
Wastage
Consumption
Correction
Stock Count Adjustment
```

Never silently update stock quantities.

---

# 30. Inventory Balance

Maintain reliable inventory balance.

Conceptually:

```text
Opening
+ Receipts
+ Transfers In
+ Returns
- Issues
- Transfers Out
- Consumption
- Damage
- Expiry
- Wastage
± Adjustments
=
Current Balance
```

The ledger remains the authoritative movement history.

---

# 31. Inventory Locations

Support:

```text
Warehouse
 ↓
Zone
 ↓
Rack
 ↓
Shelf
 ↓
Bin
```

Each stock quantity should have a precise location where required.

---

# 32. Multi-Warehouse Inventory

Example:

```text
Central Store
   ↓
Hospital A Store
Hospital B Store
Hospital C Store
   ↓
Department Stores
```

Support stock visibility by:

- Organization
- Hospital
- Branch
- Warehouse
- Location

---

# 33. Stock Transfer

Workflow:

```text
Source Store
 ↓
Transfer Request
 ↓
Approval
 ↓
Dispatch
 ↓
In Transit
 ↓
Receiving Store
 ↓
Receipt
```

Statuses:

```text
Requested
Approved
Picked
Dispatched
In Transit
Received
Rejected
Cancelled
```

---

# 34. Stock Transfer Integrity

Do not increase destination stock when transfer is merely requested.

Stock movement occurs according to configured dispatch/receipt rules.

Use transactions and row locking.

Two users must not transfer the same available quantity simultaneously.

---

# 35. Internal Stock Requisition

Departments can request stock:

```text
Department
 ↓
Stock Requisition
 ↓
Approval
 ↓
Picking
 ↓
Issue
 ↓
Consumption
```

Support:

- Requester
- Department
- Item
- Quantity
- Priority
- Purpose
- Cost center
- Required date

---

# 36. Stock Issue

Issue workflow:

```text
Approved Request
 ↓
Availability Check
 ↓
Batch Selection
 ↓
Picking
 ↓
Verification
 ↓
Issue
 ↓
Inventory Ledger
```

For expiry-controlled goods use FEFO where appropriate.

---

# 37. Return to Store

Support:

```text
Issued
 ↓
Returned
 ↓
Inspection
 ↓
Accepted / Quarantine / Reject
```

Returned stock must not automatically become available.

---

# 38. Expiry Management

Support:

- Expiry tracking
- Near-expiry alerts
- Expired stock
- Quarantine
- Disposal
- Supplier return
- Wastage reporting

Example alert:

```text
90 days
60 days
30 days
7 days
```

Thresholds configurable.

---

# 39. FEFO / FIFO

Support configurable inventory allocation:

- FEFO — First Expiry, First Out
- FIFO — First In, First Out
- Manual selection for controlled situations

For expiry-sensitive items, FEFO should be the default where appropriate.

---

# 40. Stock Count

Support:

```text
Stock Count
 ↓
Freeze / Snapshot
 ↓
Physical Count
 ↓
Variance
 ↓
Review
 ↓
Approval
 ↓
Adjustment
```

Do not silently overwrite system stock.

---

# 41. Cycle Counting

Support scheduled cycle counts based on:

- ABC class
- Criticality
- Risk
- Value
- Historical variance

---

# 42. Stock Adjustment

Adjustment requires:

- Item
- Location
- Batch/serial
- System quantity
- Physical quantity
- Variance
- Reason
- Evidence
- Requested by
- Approved by
- Timestamp

High-value adjustments may require workflow approval.

---

# 43. Inventory Valuation

Support configurable valuation methods:

- Moving average
- FIFO
- Standard cost
- Other organization-approved methods

The implementation must use the organization's accounting policy.

Do not assume one valuation method universally.

---

# 44. Procurement Cost

Track:

- Purchase price
- Discount
- Tax
- Freight
- Other landed costs

Where supported, calculate landed cost:

```text
Landed Cost
=
Purchase Cost
+ Freight
+ Customs
+ Other Allocated Charges
```

Allocation rules must be configurable.

---

# 45. Supplier Returns

Workflow:

```text
Stock
 ↓
Return Request
 ↓
Approval
 ↓
Supplier Return
 ↓
Credit / Replacement
```

Track:

- Item
- Batch
- Quantity
- Reason
- Supplier
- PO/GRN
- Credit note
- Replacement
- Status

---

# 46. Procurement Contracts

Support:

- Framework agreements
- Annual contracts
- Rate contracts
- Preferred supplier agreements
- Contract validity
- Price lists
- Minimum order quantity
- Delivery terms
- Payment terms

---

# 47. Budget Integration

Procurement should be able to reference:

- Budget
- Cost center
- Department
- Project
- Funding source

Do not create a second accounting/budget system if one exists.

Use integration interfaces.

---

# 48. Three-Way Matching

Support:

```text
Purchase Order
       ↓
Goods Receipt
       ↓
Supplier Invoice
```

Match:

- Item
- Quantity
- Price
- Tax
- Total

Exceptions:

```text
Price mismatch
Quantity mismatch
Missing receipt
Duplicate invoice
Tax mismatch
```

Financial posting remains integrated with the existing financial/accounting system.

---

# 49. Supplier Invoice Integration

Phase 15 may capture supplier invoice references for matching.

It should not replace the organization's Accounts Payable module if one exists.

---

# 50. Procurement Lifecycle

Complete lifecycle:

```text
Demand
 ↓
Requisition
 ↓
Approval
 ↓
RFQ
 ↓
Quotation
 ↓
Comparison
 ↓
Supplier Selection
 ↓
PO
 ↓
Approval
 ↓
Supplier Acknowledgement
 ↓
Delivery
 ↓
GRN
 ↓
QC
 ↓
Inventory
 ↓
Invoice Matching
 ↓
Payment
```

---

# 51. Emergency Procurement

Support emergency procurement.

Workflow may allow controlled bypass of normal RFQ steps:

```text
Emergency Request
 ↓
Emergency Approval
 ↓
Direct Procurement
 ↓
Receipt
 ↓
Inventory
```

Every bypass must record:

- Reason
- Requester
- Approver
- Timestamp
- Supplier
- Amount
- Supporting evidence

Never silently bypass procurement controls.

---

# 52. Consignment Inventory

Provide optional foundation for:

- Consignment stock
- Supplier-owned stock
- Hospital-owned stock

Track ownership explicitly.

Supplier-owned inventory must not be treated as hospital-owned inventory.

---

# 53. Vendor Managed Inventory

Optional integration foundation:

```text
Hospital Demand
 ↓
Supplier Visibility
 ↓
Supplier Replenishment
```

No external supplier portal is required unless specifically scoped.

---

# 54. Minimum / Maximum Stock

Configure:

- Minimum stock
- Maximum stock
- Reorder point
- Safety stock
- Economic order quantity
- Lead time

Example:

```text
Current = 50
Reorder Point = 100
Max = 500

→ Replenishment recommendation
```

---

# 55. Reorder Suggestions

System may calculate:

```text
Recommended Order
=
Forecast Demand
+
Safety Stock
-
Available Stock
-
Open PO
```

Actual formula should be configurable.

---

# 56. Demand Forecasting

AI/analytics may use:

- Historical consumption
- Seasonality
- Lead time
- Supplier reliability
- Current stock
- Open POs
- Expiry
- Department demand
- Clinical activity

Forecasts remain advisory.

---

# 57. Procurement Analytics

Dashboard:

```text
Total Procurement
Open POs
Pending Requisitions
Pending RFQs
Supplier Performance
Inventory Value
Low Stock
Critical Stock
Expired Stock
Near Expiry
Stock Variance
Purchase Price Variance
```

---

# 58. Supplier Performance

Metrics:

- On-time delivery
- Fill rate
- Rejection rate
- Price variance
- Quality score
- Lead time
- Response time
- Return rate

Do not automatically blacklist suppliers based solely on AI predictions.

---

# 59. Inventory Dashboard

Display:

```text
Total Inventory Value
Available Stock
Reserved Stock
Quarantine Stock
Low Stock
Critical Stock
Near Expiry
Expired
Stock Transfers
Pending Requisitions
Pending POs
```

---

# 60. Reports

Required reports:

### Procurement

- Purchase requisitions
- RFQs
- Quotations
- Purchase orders
- Purchase analysis
- Supplier comparison
- Procurement by department
- Procurement by category

### Inventory

- Stock balance
- Stock ledger
- Stock valuation
- Batch report
- Expiry report
- Stock movement
- Stock adjustment
- Stock variance
- Slow-moving stock
- Non-moving stock
- Dead stock

### Supplier

- Supplier performance
- Supplier purchase history
- Supplier returns
- Price comparison
- Contract expiry

### Supply Chain

- Reorder report
- Demand forecast
- Lead-time analysis
- Stock-out report
- Service-level report

---

# 61. Suggested Database Tables

Actual implementation must first inspect the repository and consolidate equivalent tables.

Potential tables:

```text
inventory_item_categories
inventory_item_subcategories
inventory_item_groups
inventory_items
inventory_item_units
inventory_item_unit_conversions
inventory_item_barcodes
inventory_item_attributes

inventory_warehouses
inventory_zones
inventory_locations
inventory_bins

inventory_batches
inventory_serial_numbers
inventory_stock
inventory_stock_reservations
inventory_stock_transactions
inventory_stock_transfers
inventory_stock_transfer_items

inventory_stock_requisitions
inventory_stock_requisition_items
inventory_stock_issues
inventory_stock_issue_items
inventory_stock_returns
inventory_stock_return_items

inventory_stock_counts
inventory_stock_count_items
inventory_stock_adjustments

inventory_expiry_records
inventory_quarantine_records

procurement_suppliers
procurement_supplier_contacts
procurement_supplier_documents
procurement_supplier_evaluations

procurement_contracts
procurement_contract_items
procurement_supplier_price_lists

procurement_requisitions
procurement_requisition_items
procurement_rfqs
procurement_rfq_suppliers
procurement_quotations
procurement_quotation_items
procurement_quotation_comparisons

procurement_purchase_orders
procurement_purchase_order_items
procurement_purchase_order_events

procurement_goods_receipts
procurement_goods_receipt_items
procurement_quality_inspections
procurement_quality_inspection_items

procurement_supplier_returns
procurement_supplier_return_items

procurement_invoice_matches
procurement_landed_costs
procurement_landed_cost_allocations

procurement_budgets
procurement_chargeable_events
```

Do not blindly create all tables.

Inspect existing modules and consolidate.

---

# 62. Inventory Stock Model

A stock record should conceptually identify:

```text
organization_id
hospital_id
branch_id
warehouse_id
location_id
item_id
batch_id nullable
serial_id nullable
quantity
reserved_quantity
available_quantity
unit_cost
status
```

For tracked inventory:

```text
Available
Reserved
Quarantine
Damaged
Expired
```

must be clearly distinguished.

---

# 63. Inventory Ledger

Conceptual transaction:

```text
inventory_stock_transactions

id
organization_id
hospital_id
branch_id
warehouse_id
location_id
item_id
batch_id
serial_id
transaction_type
reference_type
reference_id
quantity_in
quantity_out
unit_cost
balance_after
reason
performed_by
created_at
```

Do not allow users to manually rewrite historical ledger entries.

Corrections should create compensating transactions.

---

# 64. Procurement Requisition Number

Example:

```text
PR-2026-00001234
```

Never use:

```php
Requisition::count() + 1
```

Use concurrency-safe sequence generation.

---

# 65. Purchase Order Number

Example:

```text
PO-2026-00004567
```

Must be unique within the appropriate organizational scope.

---

# 66. GRN Number

Example:

```text
GRN-2026-00007890
```

Must be concurrency-safe.

---

# 67. Supplier Return Number

Example:

```text
SR-2026-00000456
```

---

# 68. API

Base:

```text
/api/v1/inventory
/api/v1/procurement
```

Suggested inventory endpoints:

```text
GET    /inventory/items
POST   /inventory/items

GET    /inventory/warehouses
GET    /inventory/stock
GET    /inventory/stock/{item}

POST   /inventory/requisitions
GET    /inventory/requisitions

POST   /inventory/issues
POST   /inventory/returns

POST   /inventory/transfers
POST   /inventory/transfers/{id}/approve
POST   /inventory/transfers/{id}/dispatch
POST   /inventory/transfers/{id}/receive

POST   /inventory/counts
POST   /inventory/adjustments
```

Procurement:

```text
GET    /procurement/suppliers
POST   /procurement/suppliers

POST   /procurement/requisitions
GET    /procurement/requisitions

POST   /procurement/rfqs
POST   /procurement/quotations

POST   /procurement/purchase-orders
POST   /procurement/purchase-orders/{id}/approve

POST   /procurement/goods-receipts

POST   /procurement/returns

GET    /procurement/reports
```

Use the existing API response format.

---

# 69. Permissions

Suggested permissions:

```text
inventory.dashboard.view

inventory.item.view
inventory.item.create
inventory.item.update
inventory.item.manage

inventory.warehouse.view
inventory.warehouse.manage
inventory.location.manage

inventory.stock.view
inventory.stock.issue
inventory.stock.receive
inventory.stock.transfer
inventory.stock.adjust
inventory.stock.count
inventory.stock.reserve
inventory.stock.export

inventory.batch.view
inventory.batch.manage
inventory.expiry.view
inventory.quarantine.manage

inventory.requisition.view
inventory.requisition.create
inventory.requisition.approve
inventory.requisition.cancel

procurement.supplier.view
procurement.supplier.create
procurement.supplier.update
procurement.supplier.approve
procurement.supplier.suspend

procurement.contract.view
procurement.contract.manage

procurement.rfq.view
procurement.rfq.create
procurement.rfq.manage

procurement.quotation.view
procurement.quotation.create
procurement.quotation.compare
procurement.quotation.approve

procurement.po.view
procurement.po.create
procurement.po.approve
procurement.po.send
procurement.po.cancel

procurement.grn.view
procurement.grn.create
procurement.grn.inspect
procurement.grn.approve

procurement.return.view
procurement.return.create
procurement.return.approve

procurement.report.view
procurement.report.export

procurement.audit.view
procurement.settings.manage
```

---

# 70. Suggested Roles

```text
Storekeeper
Inventory Officer
Warehouse Manager
Procurement Officer
Senior Procurement Officer
Purchase Manager
Procurement Manager
Receiving Officer
Quality Inspector
Supply Chain Manager
Inventory Controller
Store Supervisor
Finance Reviewer
Department Requester
Hospital Administrator
Procurement Administrator
```

---

# 71. Workflow Integration

Use the existing Workflow/Approval engine.

Potential approval objects:

- Purchase Requisition
- Supplier
- RFQ selection
- Purchase Order
- Stock Adjustment
- Emergency Purchase
- Supplier Return
- Write-off
- High-value transfer

---

# 72. Notifications

Notify:

- Requisition submitted
- Approval required
- Requisition approved/rejected
- RFQ deadline
- Quotation received
- PO approved
- PO overdue
- Delivery received
- QC failed
- Low stock
- Critical stock
- Near expiry
- Expired stock
- Transfer dispatched
- Transfer received
- Stock count variance
- Supplier contract expiry

Reuse the existing Notification system.

---

# 73. Audit

Audit:

- Item master changes
- Supplier changes
- Contract changes
- Requisition
- Approval
- RFQ
- Quotation
- Supplier selection
- PO
- PO approval
- GRN
- QC
- Stock movement
- Stock adjustment
- Stock count
- Transfer
- Issue
- Return
- Expiry
- Disposal
- Supplier return
- Emergency procurement
- Export

---

# 74. Security

Enforce organizational scope at the server side.

Example:

```text
Hospital A Storekeeper
        ↓
Hospital B warehouse
        ↓
Access denied
```

Also protect:

- Supplier bank details
- Contract pricing
- Procurement reports
- Cost information
- High-value purchase approvals
- Stock adjustment
- Write-off
- Export

---

# 75. Concurrency

Protect:

- Stock issue
- Stock transfer
- Stock receipt
- Stock adjustment
- Stock count
- Reservation
- PO receiving
- Partial receipt
- Batch allocation

Use:

- Transactions
- Row locks
- Unique constraints
- State validation
- Idempotency

Two users must not issue the same stock simultaneously.

---

# 76. Idempotency

Important operations must be idempotent:

```text
Goods Receipt
Stock Issue
Stock Transfer
Purchase Order Approval
Supplier Return
Inventory Adjustment
```

Repeated API requests must not create duplicate stock transactions.

---

# 77. Inventory Reservation

Some stock may be reserved for:

- OT
- ICU
- Emergency
- Surgery
- Patient-specific requirements
- Department requests

Reserved stock should not be available for unrelated issue.

---

# 78. Quarantine

Quarantine stock may result from:

- Failed QC
- Temperature excursion
- Damaged packaging
- Suspected contamination
- Recall
- Return
- Investigation

Quarantine stock cannot be issued until authorized release.

---

# 79. Recall Management

Provide an enterprise recall foundation:

```text
Recall
 ↓
Affected Item
 ↓
Batch / Lot
 ↓
Warehouse
 ↓
Departments
 ↓
Issued Stock
 ↓
Return / Quarantine / Disposal
```

Specialized clinical recall workflows remain with their owning modules.

---

# 80. Consumption Integration

Consumption may originate from:

- Pharmacy
- Laboratory
- OT
- ICU
- Emergency
- Nursing
- General departments
- Maintenance
- IT

Phase 15 records enterprise stock movement.

The consuming module remains responsible for its domain-specific clinical event.

---

# 81. Chargeable Events

Some supplies may be billable.

Phase 15 may publish:

```text
Inventory Item
 ↓
Chargeable Event
 ↓
Billing Phase 4
```

Billing remains responsible for:

- Price
- Tax
- Discount
- Invoice
- Payment

Never implement a second billing engine.

---

# 82. AI Readiness

AI may assist with:

### Demand Forecasting

Predict future demand from:

- Historical consumption
- Seasonality
- Hospital activity
- Department demand
- Lead times
- Supplier performance

### Reorder Recommendations

Recommend:

- What to purchase
- Quantity
- Timing
- Preferred supplier

### Stock Optimization

Identify:

- Overstock
- Understock
- Slow-moving
- Non-moving
- Dead stock
- Near-expiry stock

### Supplier Analytics

Identify:

- Delivery delays
- Price changes
- Quality trends
- Supplier reliability

### Procurement Anomaly Detection

Identify:

- Unusual price
- Duplicate orders
- Unusual quantity
- Split purchases
- Repeated emergency purchases
- Unusual supplier concentration

These are alerts, not accusations.

---

# 83. AI Procurement Safety

AI MUST NOT autonomously:

- Select a supplier as final decision
- Approve purchase orders
- Approve high-value procurement
- Modify supplier bank details
- Adjust inventory
- Dispose of stock
- Approve write-offs
- Override quality inspection
- Release quarantined stock
- Change contract terms

Human authorization remains mandatory.

---

# 84. AI Explainability

AI recommendations should show:

```text
Recommendation
Confidence
Historical basis
Input factors
Expected impact
Model/version
Timestamp
Reviewer
Final decision
```

---

# 85. AI Audit

Store:

- AI model/version
- Request
- Recommendation
- Confidence
- User
- Timestamp
- Final human action

Do not store unnecessary sensitive information.

---

# 86. AI Privacy

Do not send:

- Patient-identifying information
- Sensitive clinical information
- Supplier confidential data

to external AI systems unless explicitly authorized.

Use anonymized/aggregated data wherever possible.

---

# 87. Supply Chain Analytics

Recommended KPIs:

```text
Inventory Turnover
Days of Inventory
Stock-out Rate
Fill Rate
Service Level
Purchase Price Variance
Supplier On-Time Delivery
Supplier Rejection Rate
Order Cycle Time
Procurement Cycle Time
Average Lead Time
Emergency Purchase Rate
Expiry/Wastage Rate
Inventory Accuracy
Dead Stock Value
Near-Expiry Value
```

---

# 88. Implementation Order

Implement in this sequence:

```text
1. Inspect existing architecture
2. Inspect Phases 0–14
3. Identify existing item/asset/procurement concepts
4. Define ownership boundaries
5. Item master
6. UOM
7. Categories
8. Supplier master
9. Supplier qualification
10. Warehouses
11. Locations
12. Inventory stock
13. Inventory ledger
14. Batch/lot
15. Serial tracking
16. Purchase requisition
17. Approval
18. RFQ
19. Quotation
20. Comparison
21. Purchase Order
22. PO approval
23. Goods Receipt
24. Quality inspection
25. Inventory update
26. Stock requisition
27. Stock issue
28. Stock transfer
29. Stock return
30. Stock count
31. Adjustments
32. Expiry
33. Quarantine
34. Supplier return
35. Contract procurement
36. Three-way matching
37. Budget integration
38. Reports
39. Dashboard
40. Notifications
41. API
42. External integrations
43. AI assistance
44. Security testing
45. Concurrency testing
46. Performance testing
47. Documentation
```

---

# 89. Non-Goals

Phase 15 does not own:

- Patient registration
- Clinical encounters
- Diagnosis
- Prescription
- Medication dispensing
- Blood unit lifecycle
- Laboratory testing
- Radiology reporting
- Nursing
- ICU clinical care
- Surgery workflow
- Emergency clinical workflow
- Insurance adjudication
- Patient billing
- General ledger
- Accounts payable unless specifically integrated
- Full fixed-asset lifecycle unless an existing asset module is explicitly integrated

---

# 90. Definition of Done

Phase 15 is complete when:

## Item Management

- Item master works
- Categories work
- UOM works
- Conversion works
- Batch/lot works
- Serial tracking works

## Supplier

- Supplier management works
- Qualification works
- Evaluation works
- Contracts work
- Documents work

## Procurement

- Requisition works
- Approval works
- RFQ works
- Quotation works
- Comparison works
- Purchase order works
- PO approval works

## Receiving

- GRN works
- Partial receipt works
- QC works
- Rejection works
- Quarantine works

## Inventory

- Stock works
- Ledger works
- Batch works
- Expiry works
- FEFO/FIFO works
- Transfers work
- Issues work
- Returns work
- Counts work
- Adjustments work

## Supply Chain

- Reorder works
- Demand analytics work
- Supplier performance works
- Stock-out reporting works

## Integration

- Pharmacy integration works
- Laboratory integration works
- Radiology integration works
- OT integration works
- ICU integration works
- Emergency integration works
- Blood Bank integration works
- Billing integration works
- File Management works
- Workflow works
- Notifications work
- Audit works

## Security

- Multi-hospital isolation works
- RBAC works
- Warehouse scope works
- Adjustment authorization works
- Procurement approval works

## Reliability

- Concurrency protection works
- Idempotency works
- Duplicate prevention works
- Ledger integrity works

## Interoperability

- API works
- Integration adapters exist
- Future interoperability is supported

## AI

- Forecasting extension points exist
- Recommendation architecture exists
- Human approval is mandatory
- AI actions are audited

## Testing

- Unit tests
- Feature tests
- API tests
- Integration tests
- Security tests
- Concurrency tests
- Inventory integrity tests
- Procurement workflow tests
- Negative tests

---

# 91. Final AI Implementation Prompt

Copy the following prompt into an AI coding agent:

```text
You are a senior Laravel enterprise architect, healthcare supply-chain architect, procurement specialist, inventory-management engineer, database engineer, security engineer, and QA engineer.

Implement Phase 15 — Inventory / Procurement / Supply Chain in the existing Hospital Management System.

==================================================
CRITICAL ARCHITECTURAL INSTRUCTION
==================================================

The existing Laravel modular-monolith architecture is ALREADY IMPLEMENTED.

DO NOT create a second modular framework.

DO NOT replace the existing architecture.

FIRST inspect the repository.

Then inspect Phases 0–14.

Understand and reuse:

- Organization
- Hospital
- Branch
- Department
- User
- RBAC
- Workflow
- Audit
- Notifications
- File Management
- Patient/MPI
- Billing
- Pharmacy
- Laboratory
- Radiology
- IPD
- Nursing
- OT
- ICU
- Emergency
- Blood Bank
- Insurance
- API infrastructure

Do not create duplicate implementations.

==================================================
PRIMARY OBJECTIVE
==================================================

Implement a production-grade enterprise Inventory / Procurement / Supply Chain module supporting:

Item Master
→ Supplier
→ Demand
→ Purchase Requisition
→ Approval
→ RFQ
→ Quotation
→ Comparison
→ Purchase Order
→ Approval
→ Goods Receipt
→ Quality Inspection
→ Inventory
→ Warehouse
→ Stock Transfer
→ Stock Issue
→ Stock Return
→ Stock Count
→ Adjustment
→ Expiry
→ Quarantine
→ Supplier Return
→ Reorder
→ Analytics

==================================================
DOMAIN OWNERSHIP
==================================================

Phase 15 owns:

- Enterprise procurement
- Supplier management
- Purchase requisitions
- RFQ
- Quotations
- Purchase orders
- Goods receiving
- Quality inspection
- Warehouses
- General inventory
- Stock ledger
- Stock transfers
- Stock issues
- Stock returns
- Stock counts
- Stock adjustments
- Reorder
- Supply-chain analytics

Do NOT replace specialized inventory systems.

Pharmacy owns medication dispensing and pharmacy clinical inventory.

Blood Bank owns blood units and blood component inventory.

Laboratory owns laboratory testing.

Radiology owns radiology workflow.

OT owns surgical workflow.

ICU owns critical-care workflow.

Emergency owns emergency workflow.

Billing owns financial transactions.

Insurance owns payer claims.

==================================================
ITEM MASTER
==================================================

Implement:

- Categories
- Subcategories
- Groups
- Items
- UOM
- UOM conversion
- Barcode
- SKU
- Batch tracking
- Serial tracking
- Expiry tracking
- Reorder levels
- Safety stock
- Criticality
- ABC/VED/FSN/HML classification

Do not duplicate existing medication/asset masters.

==================================================
SUPPLIER
==================================================

Implement:

- Supplier master
- Contacts
- Documents
- Qualification
- Evaluation
- Contracts
- Price lists
- Payment terms
- Delivery terms
- Supplier status

Reuse existing File Management.

==================================================
PROCUREMENT
==================================================

Implement:

Purchase Requisition
→ Approval
→ RFQ
→ Supplier Quotation
→ Comparison
→ Supplier Selection
→ Purchase Order
→ Approval
→ Supplier Acknowledgement

Use existing Workflow/Approval.

Do not hardcode approval thresholds.

==================================================
GOODS RECEIPT
==================================================

Implement:

PO
→ Delivery
→ GRN
→ Quantity Verification
→ Quality Inspection
→ Accept / Reject / Partial
→ Inventory

Inventory must NOT increase merely because a PO exists.

Inventory increases only through valid receiving transactions.

==================================================
INVENTORY
==================================================

Implement:

- Warehouses
- Zones
- Racks
- Shelves
- Bins
- Stock
- Batch
- Serial
- Reservations
- Stock ledger
- Transfers
- Issues
- Returns
- Counts
- Adjustments
- Quarantine
- Expiry
- Disposal

Maintain a complete immutable movement history.

Never silently update historical stock transactions.

Corrections must use compensating transactions.

==================================================
STOCK INTEGRITY
==================================================

Use transactions and row locking.

Prevent:

- Negative stock where prohibited
- Double issue
- Double transfer
- Duplicate GRN
- Duplicate stock transaction
- Concurrent over-allocation
- Duplicate adjustment

Use unique constraints and idempotency.

==================================================
BATCH / EXPIRY
==================================================

For expiry-controlled items:

- Track batch
- Manufacture date
- Expiry
- Quantity
- Location
- Cost

Expired inventory must not be issued.

Implement configurable expiry alerts.

Support FEFO where appropriate.

==================================================
STOCK TRANSFER
==================================================

Implement:

Requested
→ Approved
→ Picked
→ Dispatched
→ In Transit
→ Received

Prevent source stock from being transferred twice.

Do not add destination stock before the configured receipt event.

==================================================
STOCK ISSUE
==================================================

Implement:

Request
→ Approval
→ Availability
→ Reservation/Picking
→ Verification
→ Issue
→ Ledger

Support department and cost-center references.

==================================================
STOCK COUNT
==================================================

Implement:

Count
→ Snapshot
→ Physical Count
→ Variance
→ Review
→ Approval
→ Adjustment

Never overwrite stock silently.

==================================================
PROCUREMENT FINANCE
==================================================

Support:

- Purchase price
- Discount
- Tax
- Freight
- Other landed costs
- Currency
- Payment terms
- Budget reference

If an accounting/AP module exists, integrate with it.

Do NOT create a second accounting system.

==================================================
THREE-WAY MATCH
==================================================

Support:

PO
+
GRN
+
Supplier Invoice

Detect:

- Quantity mismatch
- Price mismatch
- Tax mismatch
- Duplicate invoice
- Missing receipt

==================================================
SPECIALIZED MODULE INTEGRATION
==================================================

Integrate with:

Pharmacy
Laboratory
Radiology
OT
ICU
Emergency
Blood Bank
Billing
Insurance where procurement/charge references are needed
File Management
Workflow
Notifications
Audit

Do not duplicate those modules.

==================================================
SECURITY
==================================================

Enforce server-side organization/hospital/branch/warehouse authorization.

Hospital A users MUST NOT access Hospital B:

- Warehouses
- Stock
- Purchase Orders
- Supplier contracts
- Procurement reports
- Stock adjustments

Test direct URL and API ID manipulation.

==================================================
AUDIT
==================================================

Audit:

- Supplier changes
- Contract changes
- Requisitions
- Approvals
- RFQs
- Quotations
- Supplier selection
- POs
- GRNs
- QC
- Stock movements
- Transfers
- Issues
- Returns
- Counts
- Adjustments
- Quarantine
- Disposal
- Emergency procurement
- Exports

==================================================
CONCURRENCY
==================================================

Test:

Two users issuing same stock.

Two users transferring same stock.

Two users receiving same PO.

Two users adjusting same stock.

Two users counting same stock.

Two workers processing same GRN.

Two API requests creating same stock transaction.

Use:

- Database transactions
- Row locks
- Unique constraints
- Idempotency keys
- State validation

==================================================
IDENTIFIERS
==================================================

Use concurrency-safe identifiers.

Examples:

PR-2026-00001234
RFQ-2026-00000123
PO-2026-00004567
GRN-2026-00007890
SR-2026-00000456

NEVER use:

Model::count() + 1

==================================================
API
==================================================

Implement:

/api/v1/inventory
/api/v1/procurement

Follow existing API conventions.

Never expose:

- SQL
- stack traces
- credentials
- internal server paths
- secrets

==================================================
EVENTS
==================================================

Implement appropriate domain events for:

- RequisitionCreated
- RequisitionApproved
- RFQCreated
- QuotationReceived
- QuotationSelected
- PurchaseOrderCreated
- PurchaseOrderApproved
- PurchaseOrderSent
- GoodsReceiptCreated
- GoodsReceiptAccepted
- GoodsReceiptRejected
- StockReceived
- StockIssued
- StockTransferred
- StockReceivedAtDestination
- StockReturned
- StockCountCompleted
- StockAdjusted
- StockQuarantined
- StockReleased
- StockExpired
- SupplierReturnCreated

Use existing event conventions.

==================================================
JOBS
==================================================

Implement background jobs for:

- Expiry alerts
- Reorder suggestions
- Low-stock alerts
- Critical-stock alerts
- PO overdue detection
- Supplier contract expiry
- Stock reconciliation
- Demand forecasting
- Supplier performance calculations
- Slow-moving stock detection

Jobs must be idempotent.

==================================================
NOTIFICATIONS
==================================================

Use existing notification infrastructure.

Notify relevant users for:

- Approval required
- Requisition approved/rejected
- RFQ deadlines
- PO approval
- PO overdue
- Goods receipt
- QC failure
- Low stock
- Critical stock
- Near expiry
- Expiry
- Transfer
- Count variance
- Supplier contract expiry

==================================================
AI
==================================================

Implement AI extension points for:

1. Demand forecasting
2. Reorder recommendation
3. Overstock detection
4. Understock detection
5. Slow-moving stock
6. Dead stock
7. Supplier performance analytics
8. Procurement anomaly detection
9. Price anomaly detection
10. Emergency procurement pattern analysis

AI MUST NOT autonomously:

- Approve procurement
- Select supplier as final authority
- Modify supplier bank details
- Adjust stock
- Dispose stock
- Release quarantined stock
- Approve write-offs
- Override QC
- Change purchase orders

AI is advisory.

==================================================
AI EXPLAINABILITY
==================================================

Show:

- Recommendation
- Confidence
- Supporting factors
- Historical data
- Expected impact
- Model/version
- Timestamp

Record final human decision.

==================================================
TESTING
==================================================

Implement:

- Unit tests
- Feature tests
- API tests
- Integration tests
- Inventory ledger tests
- Procurement workflow tests
- Security tests
- Multi-hospital tests
- Concurrency tests
- Idempotency tests
- Expiry tests
- Batch tests
- Serial tests
- Transfer tests
- Stock count tests
- Three-way matching tests

Mandatory negative tests:

1. Expired item cannot be issued.
2. Quarantined item cannot be issued.
3. Duplicate GRN rejected.
4. Duplicate stock transaction rejected.
5. Duplicate PO processing prevented.
6. Same stock cannot be issued twice concurrently.
7. Same stock cannot be transferred twice.
8. Unauthorized adjustment blocked.
9. Unauthorized PO approval blocked.
10. Hospital A cannot access Hospital B stock.
11. Invalid UOM conversion rejected.
12. Invalid batch rejected.
13. Negative stock prevented where configured.
14. Duplicate supplier invoice detected.
15. QC-rejected stock cannot become available.

==================================================
QUALITY
==================================================

Use:

- Thin controllers
- Form Requests
- Services/Actions
- Policies
- Gates
- Events
- Listeners
- Jobs
- Transactions
- Dependency injection
- Repository conventions
- PSR-12
- Laravel best practices

Do not put business logic in controllers.

Do not put business logic in Blade.

==================================================
IMPLEMENTATION PROCESS
==================================================

Before coding:

1. Inspect repository.
2. Inspect module structure.
3. Inspect Phases 0–14.
4. Inspect existing inventory-like modules.
5. Inspect Pharmacy.
6. Inspect Blood Bank.
7. Inspect Asset Management if present.
8. Inspect Billing.
9. Inspect Workflow.
10. Inspect File Management.
11. Inspect RBAC.
12. Inspect Audit.
13. Inspect API conventions.

Create an architecture/integration map before implementation.

Then implement incrementally.

After every major feature:

- Run tests.
- Fix failures.
- Verify migrations.
- Verify authorization.
- Verify organizational scope.
- Verify inventory ledger.
- Verify concurrency.

Never claim tests passed unless they were actually executed.

==================================================
FINAL REPORT
==================================================

After implementation provide:

1. Architecture summary
2. Integration map
3. Files created
4. Files modified
5. Migrations
6. Models
7. Services/Actions
8. Controllers
9. Policies
10. Permissions
11. Events
12. Jobs
13. Notifications
14. API endpoints
15. UI pages
16. Database indexes
17. Integration points
18. Tests executed
19. Tests passed
20. Tests failed
21. Security verification
22. Concurrency verification
23. Known limitations
24. Recommended next steps

Never claim a test passed unless it was actually executed.
```

# End of Phase 15 Specification