# PHASE 7 — PHARMACY & MEDICATION MANAGEMENT
## Module Specification & AI Implementation Prompt

---

# 1. MODULE OVERVIEW

Implement **Phase 7 — Pharmacy & Medication Management** as a production-grade pharmacy and medication-management module within the existing Hospital Management Software / Hospital ERP.

The module must support:

- Medication master data
- Drug catalog
- Generic/brand management
- Formulation and strength
- Units and packaging
- Pharmacy branches
- Pharmacy stores
- Stock and batch tracking
- Expiry management
- Purchase/receiving integration
- Prescription processing
- Dispensing
- Partial dispensing
- Returns
- Stock adjustments
- Transfers
- Medication substitution
- Patient medication history
- Medication administration foundation
- Drug interaction/allergy safety foundation
- Controlled/high-risk medication foundation
- Pharmacy billing integration
- Clinical EMR integration
- Notifications
- Audit
- Pharmacy reporting
- API
- Future inventory/procurement integration

Core workflow:

```text
Patient
   ↓
Clinical Encounter
   ↓
Medication Prescription
   ↓
Medication Review
   ↓
Pharmacy Order
   ↓
Billing / Charge
   ↓
Pharmacy Verification
   ↓
Stock / Batch Selection
   ↓
Dispensing
   ↓
Patient Medication History
   ↓
EMR / Patient 360
```

For inpatient care later:

```text
Physician Order
   ↓
Medication Order
   ↓
Pharmacy Verification
   ↓
Dispensing
   ↓
Medication Administration
   ↓
MAR
   ↓
Clinical Record
```

---

# 2. CRITICAL ARCHITECTURE RULE

The project's modular architecture is already implemented.

**Do not create another modular framework.**

Before writing code:

1. Inspect the repository.
2. Inspect Phases 0–6.
3. Identify the existing architecture.
4. Reuse existing infrastructure.
5. Follow established naming conventions.
6. Follow existing authorization patterns.
7. Follow existing API conventions.
8. Follow existing audit/notification infrastructure.
9. Follow existing UI conventions.

Do NOT duplicate:

- Patient/MPI
- Encounter
- Appointment
- User
- Authentication
- Roles
- Permissions
- Organization
- Hospital
- Branch
- Department
- Billing
- Invoice
- Payment
- Notification
- Audit
- File Management
- Clinical Order
- existing Prescription infrastructure if Phase 3 already contains it

The pharmacy module must extend existing clinical prescription functionality rather than create a competing prescription system.

---

# 3. TECHNOLOGY BASELINE

Use the project's existing stack.

Expected:

- PHP 8.4+
- Laravel
- MySQL/PostgreSQL
- Redis
- Blade
- AdminLTE or existing UI framework
- REST API
- Queue
- Scheduler
- Policies/Gates
- Form Requests
- Services/Actions
- Events/Listeners
- Notifications
- PHPUnit/Pest

Do not introduce a new technology stack.

---

# 4. CORE ARCHITECTURAL BOUNDARY

The Pharmacy module must distinguish between:

## Clinical Medication Order

Created by a physician/authorized clinician.

Example:

```text
Amoxicillin 500 mg
1 capsule
3 times daily
5 days
```

## Pharmacy Dispensing

Pharmacy determines:

- available stock
- batch
- expiry
- quantity
- dispensing unit
- substitution policy
- actual quantity dispensed

## Inventory

General inventory/procurement ownership will be expanded in Phase 15.

Therefore Phase 7 should provide a pharmacy-specific inventory/stock foundation without creating a second enterprise inventory system.

---

# 5. RESPONSIBILITIES

Pharmacy owns:

- medication master
- generic drugs
- brands
- formulations
- strengths
- routes
- dosage forms
- pharmacy locations
- pharmacy stock foundation
- batches
- expiry
- prescriptions
- dispensing
- returns
- stock transfers
- stock adjustments
- medication substitution
- medication history
- medication safety foundation
- pharmacy billing integration
- pharmacy reporting
- pharmacy audit

It does NOT own:

- general procurement
- enterprise inventory
- accounting
- supplier management as a complete procurement system
- patient registration
- clinical encounter
- general billing
- inpatient bed management
- nursing
- MAR as a complete inpatient workflow until Phase 9

---

# 6. MEDICATION DOMAIN MODEL

Use the following conceptual hierarchy:

```text
Medication
   ↓
Generic / Active Ingredient
   ↓
Brand
   ↓
Formulation
   ↓
Strength
   ↓
Dosage Form
   ↓
Package / Unit
```

Example:

```text
Generic:
Paracetamol

Brand:
Napa

Strength:
500 mg

Dosage Form:
Tablet

Route:
Oral

Package:
100 tablets
```

Do not treat every brand/strength/package as a completely unrelated medication.

---

# 7. GENERIC / ACTIVE INGREDIENT MASTER

Create configurable active ingredients.

Examples:

```text
Paracetamol
Amoxicillin
Azithromycin
Omeprazole
Metformin
Atorvastatin
```

Suggested fields:

```text
id
organization_id
code
generic_name
chemical_name
description
therapeutic_class
pharmacological_class
is_active
created_at
updated_at
```

Support combination medicines.

Example:

```text
Amoxicillin + Clavulanic Acid
```

---

# 8. MEDICATION MASTER

Create a medication/product master.

Conceptually:

```text
medications
```

Suggested fields:

```text
id
organization_id
hospital_id
generic_id
brand_id
medication_code
name
strength
strength_unit
dosage_form_id
route_id
pack_size
dispensing_unit
prescription_unit
manufacturer
is_prescription_required
is_controlled
is_high_alert
is_active
created_at
updated_at
```

Adapt the exact schema to existing project conventions.

---

# 9. BRAND MASTER

Support:

- brand name
- manufacturer
- generic
- active/inactive
- registration/reference information where required

Do not hardcode brands.

---

# 10. DOSAGE FORM MASTER

Support:

```text
Tablet
Capsule
Syrup
Suspension
Injection
Cream
Ointment
Gel
Drops
Inhaler
Suppository
Patch
Powder
Solution
Infusion
Other
```

Make configurable.

---

# 11. ROUTE MASTER

Examples:

```text
Oral
IV
IM
SC
Topical
Sublingual
Rectal
Vaginal
Ophthalmic
Otic
Nasal
Inhalation
Other
```

Routes must be configurable.

---

# 12. STRENGTH AND UNITS

Support standardized units:

```text
mg
g
mcg
mL
L
IU
%
mg/mL
mg/5mL
```

Do not store strength only as arbitrary text where structured values are required.

---

# 13. COMBINATION MEDICATIONS

Support medications containing multiple active ingredients.

Example:

```text
Generic Combination
-------------------
Amoxicillin
+
Clavulanic Acid
```

Create an association such as:

```text
medication_ingredients
```

with:

```text
medication_id
generic_id
strength
unit
```

This will be important for:

- allergy checking
- interaction checking
- reporting
- substitution

---

# 14. PHARMACY LOCATIONS

Support:

```text
Hospital
   ↓
Pharmacy
   ├── Main Pharmacy
   ├── Emergency Pharmacy
   ├── OPD Pharmacy
   ├── Inpatient Pharmacy
   └── Satellite Pharmacy
```

Reuse existing organization/hospital/branch structures.

---

# 15. PHARMACY STORE / LOCATION

Each pharmacy can have one or more stock locations.

Examples:

```text
Main Store
Cold Storage
Controlled Drug Cabinet
Emergency Cabinet
Ward Store
```

Support configurable location types.

---

# 16. STOCK FOUNDATION

Phase 7 should provide pharmacy-specific stock management.

Core concepts:

```text
Medication
   ↓
Batch
   ↓
Expiry
   ↓
Location
   ↓
Quantity
```

Do not implement a simplistic:

```text
medication.stock_quantity
```

because pharmacy stock must be batch-aware.

---

# 17. BATCH MANAGEMENT

Every stock receipt should support batch tracking.

Store:

```text
batch_number
medication_id
manufacturer
manufacturing_date
expiry_date
unit_cost
selling_price
received_quantity
available_quantity
location_id
supplier_reference
created_at
updated_at
```

Actual fields should follow the existing architecture.

---

# 18. EXPIRY MANAGEMENT

Support:

- expiry date
- expired
- near expiry
- valid

Do not allow expired medication to be dispensed.

Implement configurable near-expiry thresholds.

Example:

```text
Expired
≤ 30 days
≤ 60 days
≤ 90 days
```

Thresholds must be configurable.

---

# 19. FEFO

Use:

**First Expiry, First Out**

for batch selection where appropriate.

Example:

```text
Batch A → expires Jan 2027
Batch B → expires Jun 2027

Dispense Batch A first.
```

Do not automatically use FIFO where FEFO is clinically/operationally more appropriate.

---

# 20. STOCK TRANSACTIONS

Never update stock by simply changing a quantity without recording why.

Create stock transaction records.

Types:

```text
Opening
Purchase Receipt
Transfer In
Transfer Out
Dispensing
Return
Adjustment
Damage
Expired
Wastage
Correction
```

Each transaction should record:

- medication
- batch
- location
- quantity
- direction
- reference
- user
- timestamp
- reason where applicable

---

# 21. STOCK LEDGER

The stock ledger should provide a complete history.

Example:

```text
Date        Transaction       Qty
-----------------------------------
01 Jan      Opening           +100
03 Jan      Purchase          +500
04 Jan      Dispensing         -20
05 Jan      Transfer Out       -50
06 Jan      Return              +5
```

Current stock should be derivable and/or maintained safely using transactional logic.

---

# 22. STOCK ADJUSTMENTS

Adjustments require:

- reason
- quantity
- batch
- location
- user
- timestamp
- authorization where required

Examples:

- physical count difference
- damaged stock
- expired stock
- system correction

Never permit arbitrary silent stock changes.

---

# 23. STOCK TRANSFER

Support:

```text
Pharmacy A
    ↓
Transfer Request
    ↓
Approval
    ↓
Dispatch
    ↓
Receipt
    ↓
Pharmacy B
```

Statuses:

```text
Requested
Approved
Dispatched
Received
Cancelled
Rejected
```

For internal transfers, maintain complete audit trail.

---

# 24. STOCK COUNT

Provide a foundation for physical stock counting.

Workflow:

```text
Stock Count Session
      ↓
Expected Quantity
      ↓
Physical Quantity
      ↓
Variance
      ↓
Review
      ↓
Adjustment
```

Do not automatically adjust stock without an authorized workflow.

---

# 25. PRESCRIPTION INTEGRATION

Phase 3 Clinical Encounter already supports prescriptions.

The Pharmacy module must consume the existing prescription.

Correct flow:

```text
Clinical Encounter
      ↓
Prescription
      ↓
Pharmacy Queue
      ↓
Pharmacist Review
      ↓
Dispensing
```

Do not create a second physician prescription system.

---

# 26. PRESCRIPTION STATUS

Support integration with existing prescription lifecycle.

Potential pharmacy-related states:

```text
Issued
Sent to Pharmacy
Under Review
Partially Dispensed
Fully Dispensed
Cancelled
Expired
Rejected
```

If the existing prescription module has different states, adapt to it.

---

# 27. PHARMACY ORDER

Create a pharmacy fulfillment concept if required.

Example:

```text
pharmacy_orders
```

References:

```text
prescription_id
patient_id
encounter_id
pharmacy_id
```

Do not duplicate the prescription itself.

---

# 28. PHARMACY DISPENSING

Workflow:

```text
Prescription
   ↓
Verify Patient
   ↓
Verify Prescription
   ↓
Medication Availability
   ↓
Batch Selection
   ↓
Pharmacist Verification
   ↓
Dispense
   ↓
Stock Deduction
   ↓
Billing
   ↓
Dispensing Record
```

---

# 29. PATIENT IDENTIFICATION

Before dispensing:

Verify:

- patient
- MRN
- prescription
- medication
- dosage
- quantity

The system should make wrong-patient dispensing difficult.

---

# 30. PHARMACIST REVIEW

Pharmacist should be able to review:

- medication
- dose
- route
- frequency
- duration
- allergies
- interactions
- duplicate therapy
- contraindication alerts where configured
- previous medication history

Do not automatically reject prescriptions solely based on an unverified software rule unless explicitly configured by the hospital.

---

# 31. DISPENSING RECORD

Create:

```text
pharmacy_dispensings
```

Potential fields:

```text
id
pharmacy_order_id
prescription_id
patient_id
pharmacy_id
dispensed_by
dispensed_at
status
remarks
```

Each dispensing should contain line items.

---

# 32. DISPENSING ITEMS

Suggested:

```text
pharmacy_dispensing_items
```

Fields:

```text
id
dispensing_id
prescription_item_id
medication_id
batch_id
quantity_prescribed
quantity_dispensed
unit
substitution_flag
substitution_reason
created_at
updated_at
```

---

# 33. PARTIAL DISPENSING

Support partial dispensing.

Example:

```text
Prescription:
30 tablets

Available:
20 tablets

Dispensed:
20

Remaining:
10
```

The system must maintain remaining quantity.

Do not mark the prescription fully dispensed.

---

# 34. SUBSTITUTION

Support controlled substitution.

Example:

```text
Prescribed:
Brand A

Available:
Brand B

Same:
Generic
Strength
Dosage form
Route
```

Substitution must follow configured hospital/pharmacy policy.

Record:

- original medication
- dispensed medication
- reason
- pharmacist
- approval where required
- timestamp

Never silently substitute.

---

# 35. GENERIC SUBSTITUTION

Support substitution by generic where permitted.

Example:

```text
Prescribed Brand A
        ↓
Same generic
        ↓
Brand B
```

Require configured authorization.

---

# 36. DISPENSING UNIT

The system must distinguish:

```text
Box
Strip
Tablet
Capsule
Bottle
mL
Ampoule
Vial
```

Do not assume prescription quantity and stock quantity use the same unit.

Example:

```text
Stock:
1 box = 10 strips
1 strip = 10 tablets

Prescription:
15 tablets
```

Unit conversion must be controlled and auditable.

---

# 37. UNIT CONVERSION

Support configurable medication-specific conversions.

Example:

```text
1 Box = 10 Strips
1 Strip = 10 Tablets
```

Do not use arbitrary mathematical conversions for medications where packaging rules differ.

---

# 38. RETURNED MEDICATION

Support returns where permitted.

Workflow:

```text
Dispensed
   ↓
Return Request
   ↓
Verification
   ↓
Accepted / Rejected
   ↓
Stock Update
```

Return rules must account for:

- opened packaging
- storage condition
- temperature control
- controlled drugs
- hospital policy

Do not automatically return all medicines to usable stock.

---

# 39. DAMAGED / EXPIRED STOCK

Support controlled workflows:

```text
Available
 ↓
Damaged / Expired
 ↓
Quarantine
 ↓
Review
 ↓
Disposal / Adjustment
```

Do not simply delete expired stock.

---

# 40. QUARANTINE STOCK

Provide a quarantine concept.

Examples:

- damaged
- expired
- recalled
- suspected counterfeit
- quality issue
- investigation

Quarantined medication must not be available for dispensing.

---

# 41. DRUG RECALL FOUNDATION

Provide a foundation for medication batch recalls.

Example:

```text
Manufacturer Recall
      ↓
Batch Search
      ↓
Affected Stock
      ↓
Quarantine
      ↓
Affected Dispensing History
      ↓
Notification / Action
```

Do not delete affected batch records.

---

# 42. MEDICATION HISTORY

Patient 360 should show:

- prescribed medication
- dispensed medication
- quantity
- date
- prescribing provider
- dispensing pharmacy
- status

Distinguish:

```text
Prescribed
vs
Dispensed
vs
Administered
```

These are not the same clinical event.

---

# 43. MEDICATION RECONCILIATION FOUNDATION

Support medication reconciliation.

Compare:

```text
Home Medications
+
Current Prescriptions
+
Recently Dispensed Medications
+
Inpatient Medication Orders
```

Future IPD/ICU modules can extend this.

---

# 44. ALLERGY MANAGEMENT

Use the existing patient allergy system.

Do not create a second allergy master.

Before dispensing, retrieve patient allergies and compare with medication ingredients.

Potential alert:

```text
Allergy Alert:
Penicillin allergy recorded.
Medication contains Amoxicillin.
```

The system should alert rather than silently block unless configured otherwise.

---

# 45. DRUG INTERACTION FOUNDATION

Create an integration-ready medication safety framework.

Potential checks:

- drug-drug interaction
- drug-allergy interaction
- duplicate therapy
- therapeutic duplication
- contraindication
- dose warning
- age-related warning
- pregnancy-related warning where applicable

Do not invent clinical interaction data.

Use an approved drug database/provider if implemented.

---

# 46. MEDICATION SAFETY ALERTS

Alerts should contain:

- severity
- source
- medication
- interacting medication
- explanation
- recommended action
- override option if permitted
- override reason
- user
- timestamp

Do not silently suppress alerts.

---

# 47. ALERT SEVERITY

Potential levels:

```text
Informational
Low
Moderate
High
Critical
```

Severity definitions must be configurable or based on the integrated drug database.

---

# 48. ALERT OVERRIDE

Where clinically appropriate, authorized users may override an alert.

Require:

```text
Override reason
User
Timestamp
Alert
Prescription/dispensing reference
```

High-risk alerts should have stronger authorization requirements.

---

# 49. CONTROLLED MEDICATION FOUNDATION

Support configurable controlled/high-risk medication flags.

Examples may include:

- narcotics
- psychotropic medicines
- controlled substances
- high-alert medications

Do not hardcode legal classifications.

Each jurisdiction/hospital may have different requirements.

---

# 50. CONTROLLED DRUG REGISTER

For controlled medications, support a dedicated register where required.

Record:

- medication
- batch
- quantity
- transaction
- patient/prescription
- user
- witness where required
- balance
- reason

The exact workflow must be configurable to local legal/hospital requirements.

---

# 51. HIGH-ALERT MEDICATIONS

Support high-alert flags.

Examples may include:

- insulin
- anticoagulants
- concentrated electrolytes
- chemotherapy agents

Do not assume a universal list.

Allow hospital-configured classification.

---

# 52. COLD-CHAIN MEDICATION FOUNDATION

Support storage requirements.

Potential fields:

```text
storage_temperature_min
storage_temperature_max
temperature_sensitive
```

Future integration may connect to temperature monitoring.

Do not implement a full IoT temperature system in Phase 7.

---

# 53. PHARMACY BILLING

Use Phase 4 Billing.

Correct architecture:

```text
Prescription
    ↓
Dispensing
    ↓
Chargeable Event
    ↓
Billing
```

Billing owns:

- price
- discount
- tax
- invoice
- payment
- receipt
- refund

Pharmacy owns:

- medication
- quantity
- batch
- dispensing

Do not duplicate financial logic.

---

# 54. PRICING

Medication pricing may depend on:

- pharmacy
- hospital
- medication
- batch
- effective date
- patient category
- insurance/corporate policy

Pricing should be obtained through the Billing/price-list infrastructure where possible.

Do not create an isolated invoice price engine.

---

# 55. INSURANCE FOUNDATION

Prepare for future insurance integration.

Support references such as:

```text
insurance_plan_id
coverage_status
copay
authorization_required
```

Do not implement full claims processing in Phase 7.

---

# 56. PHARMACY PROCUREMENT BOUNDARY

Phase 15 will provide enterprise:

- procurement
- suppliers
- purchase orders
- receiving
- inventory

Phase 7 should define integration interfaces.

Possible future flow:

```text
Procurement
   ↓
Purchase Order
   ↓
Goods Receipt
   ↓
Pharmacy Stock
```

Do not build a competing procurement system.

---

# 57. SUPPLIER FOUNDATION

If supplier reference is required for batch records, use an existing supplier/procurement master if available.

Do not create a duplicate supplier system.

---

# 58. PHARMACY DASHBOARD

Dashboard should show:

- prescriptions pending
- dispensing queue
- partially dispensed
- today's dispensing
- stock alerts
- low stock
- near expiry
- expired stock
- quarantined stock
- controlled medication activity
- returns
- stock variance

---

# 59. PHARMACY REPORTING

Provide:

### Dispensing Report

- date
- pharmacy
- medication
- quantity
- pharmacist

### Stock Report

- medication
- batch
- expiry
- location
- quantity

### Expiry Report

- expired
- near expiry

### Low Stock Report

- minimum level
- current level
- shortage

### Consumption Report

- medication
- department
- date
- quantity

### Patient Medication History

- prescribed
- dispensed

### Controlled Drug Register

- transaction
- balance
- patient
- user

### Returns

- medication
- quantity
- reason

### Stock Variance

- expected
- actual
- difference
- adjustment

### Revenue Reference

Financial figures must come from Billing.

---

# 60. STOCK LEVELS

Support configurable:

```text
minimum_stock
reorder_level
maximum_stock
```

These are pharmacy operational values.

Actual procurement should be handled by Phase 15.

---

# 61. REORDER ALERT

When stock reaches the configured reorder level:

```text
Stock
 ↓
Reorder Threshold
 ↓
Alert
 ↓
Procurement
```

Do not automatically create a purchase order unless explicitly integrated later.

---

# 62. MULTI-HOSPITAL / MULTI-BRANCH

Respect:

```text
Organization
 ↓
Hospital
 ↓
Branch
 ↓
Pharmacy
 ↓
Store
```

A pharmacy user should only see authorized pharmacy locations.

Enforce server-side.

---

# 63. RBAC

Use existing permission infrastructure.

Suggested permissions:

```text
pharmacy.dashboard.view

pharmacy.medication.view
pharmacy.medication.create
pharmacy.medication.update

pharmacy.generic.view
pharmacy.generic.create
pharmacy.generic.update

pharmacy.brand.view
pharmacy.brand.create
pharmacy.brand.update

pharmacy.prescription.view
pharmacy.prescription.review

pharmacy.dispensing.view
pharmacy.dispensing.create
pharmacy.dispensing.verify
pharmacy.dispensing.cancel
pharmacy.dispensing.return

pharmacy.stock.view
pharmacy.stock.receive
pharmacy.stock.transfer
pharmacy.stock.adjust
pharmacy.stock.count

pharmacy.batch.view
pharmacy.batch.create
pharmacy.batch.update

pharmacy.expiry.view
pharmacy.quarantine.manage

pharmacy.substitution.view
pharmacy.substitution.approve

pharmacy.safety_alert.view
pharmacy.safety_alert.override

pharmacy.controlled_drug.view
pharmacy.controlled_drug.manage

pharmacy.reports.view
pharmacy.reports.export

pharmacy.settings.manage
pharmacy.audit.view
```

Adapt names to the existing permission conventions.

---

# 64. USER ROLES

Potential roles:

```text
Pharmacy Receptionist
Pharmacy Technician
Pharmacist
Senior Pharmacist
Pharmacy Manager
Storekeeper
Pharmacy Administrator
```

Use existing role infrastructure.

---

# 65. DATABASE DESIGN

Potential core tables:

```text
pharmacy_generics
pharmacy_brands
pharmacy_medications
pharmacy_medication_ingredients
pharmacy_dosage_forms
pharmacy_routes
pharmacy_locations
pharmacy_stores
pharmacy_batches
pharmacy_stock
pharmacy_stock_transactions
pharmacy_stock_counts
pharmacy_stock_count_items
pharmacy_transfers
pharmacy_transfer_items
pharmacy_orders
pharmacy_order_items
pharmacy_dispensings
pharmacy_dispensing_items
pharmacy_returns
pharmacy_return_items
pharmacy_quarantine
pharmacy_substitutions
pharmacy_safety_alerts
pharmacy_controlled_drug_transactions
pharmacy_recall_batches
```

The exact database structure must follow the actual project architecture.

Do not duplicate existing prescription tables if Phase 3 already provides them.

---

# 66. STOCK MODEL

Do not represent pharmacy stock using a single medication-level quantity.

Conceptually:

```text
Medication
   ↓
Batch
   ↓
Store
   ↓
Stock
```

Stock uniqueness should account for the appropriate combination of:

```text
organization
hospital
branch
store
medication
batch
```

---

# 67. STOCK TRANSACTION INTEGRITY

Every stock movement must be traceable.

Example:

```text
Dispensing
   ↓
Stock Transaction
   ↓
Batch Quantity Change
   ↓
Dispensing Record
```

Use database transactions.

Never:

```php
$stock->quantity -= $qty;
```

without an auditable stock transaction.

---

# 68. CONCURRENCY

Protect against:

- two pharmacists dispensing the same stock
- negative stock
- duplicate dispensing
- duplicate return
- duplicate transfer
- duplicate stock adjustment

Use:

- database transactions
- row locking
- atomic updates
- constraints
- idempotency where appropriate

---

# 69. NEGATIVE STOCK

By default, do not permit negative stock.

If emergency dispensing without stock is a valid hospital policy:

- make it explicitly configurable
- require authorization
- audit the event
- create an outstanding/reconciliation state

Do not silently allow negative quantities.

---

# 70. DISPENSING STATUS

Use controlled statuses:

```text
Pending
Under Review
Approved
Partially Dispensed
Fully Dispensed
Cancelled
Rejected
Returned
```

Do not allow arbitrary status changes.

---

# 71. PRESCRIPTION EXPIRY

Support prescription expiration.

Possible factors:

- prescription date
- validity period
- medication category
- controlled medication rules

Do not hardcode universal legal rules.

---

# 72. MEDICATION RECALL

Support identifying patients who received an affected batch.

Workflow:

```text
Recall Batch
   ↓
Search Dispensing History
   ↓
Affected Patients
   ↓
Review
   ↓
Notification / Action
```

Patient notifications must follow existing privacy and notification policies.

---

# 73. API

Use existing `/api/v1`.

Potential endpoints:

```http
GET    /api/v1/pharmacy/medications
POST   /api/v1/pharmacy/medications

GET    /api/v1/pharmacy/generics
POST   /api/v1/pharmacy/generics

GET    /api/v1/pharmacy/brands
POST   /api/v1/pharmacy/brands

GET    /api/v1/pharmacy/prescriptions
GET    /api/v1/pharmacy/prescriptions/{prescription}

POST   /api/v1/pharmacy/orders
GET    /api/v1/pharmacy/orders

GET    /api/v1/pharmacy/dispensing
POST   /api/v1/pharmacy/dispensing
POST   /api/v1/pharmacy/dispensing/{dispensing}/verify
POST   /api/v1/pharmacy/dispensing/{dispensing}/return

GET    /api/v1/pharmacy/stock
GET    /api/v1/pharmacy/batches

POST   /api/v1/pharmacy/transfers
POST   /api/v1/pharmacy/transfers/{transfer}/approve
POST   /api/v1/pharmacy/transfers/{transfer}/dispatch
POST   /api/v1/pharmacy/transfers/{transfer}/receive

POST   /api/v1/pharmacy/stock/adjustments
POST   /api/v1/pharmacy/stock/counts

GET    /api/v1/pharmacy/alerts
POST   /api/v1/pharmacy/alerts/{alert}/override

GET    /api/v1/pharmacy/reports
```

Adapt endpoints to actual project conventions.

---

# 74. API SECURITY

Every API request must enforce:

- authentication
- authorization
- organization scope
- hospital scope
- branch scope
- pharmacy/store scope

Do not trust client-supplied:

```text
hospital_id
pharmacy_id
store_id
patient_id
```

without server-side verification.

---

# 75. EVENTS

Potential events:

```text
PrescriptionSentToPharmacy
PharmacyOrderCreated
DispensingStarted
MedicationDispensed
PartialDispensingCompleted
DispensingCompleted
MedicationReturned
StockReceived
StockAdjusted
StockTransferred
MedicationQuarantined
MedicationRecalled
CriticalMedicationAlert
```

Use existing event conventions.

---

# 76. JOBS

Potential jobs:

```text
CheckMedicationExpiry
GenerateExpiryAlerts
GenerateLowStockAlerts
ProcessMedicationRecall
SendPharmacyNotification
GeneratePharmacyReport
SynchronizeExternalDrugDatabase
```

Use queues for expensive operations.

---

# 77. NOTIFICATIONS

Support:

- prescription ready
- prescription partially fulfilled
- prescription unavailable
- critical medication alert
- stock expiry
- low stock
- recall
- refill reminder where configured

Use existing notification infrastructure.

---

# 78. AUDIT

Audit:

- medication master changes
- batch creation
- stock receipt
- stock adjustment
- transfer
- dispensing
- return
- substitution
- safety alert override
- controlled drug transaction
- quarantine
- recall
- report export

Clinical/financial events must use the appropriate existing audit mechanism.

---

# 79. CLINICAL SAFETY

Pharmacy must prioritize:

1. Correct patient
2. Correct prescription
3. Correct medication
4. Correct strength
5. Correct dosage form
6. Correct route
7. Correct quantity
8. Correct batch
9. Correct expiry
10. Correct dispensing
11. Complete audit trail

---

# 80. DISPENSING SAFETY CHECK

Before final dispensing, verify:

```text
Patient
Prescription
Medication
Strength
Form
Route
Dose
Frequency
Duration
Quantity
Allergy
Interaction Alerts
Batch
Expiry
```

The exact checklist should be configurable.

---

# 81. MEDICATION ADMINISTRATION FOUNDATION

Phase 9 Nursing will eventually provide full Medication Administration Record (MAR).

Phase 7 should preserve enough data to support:

```text
Prescription
 ↓
Dispensing
 ↓
Medication Administration
```

Do not build the complete nursing MAR in Phase 7.

---

# 82. FHIR READINESS

Prepare for:

```text
Medication
MedicationRequest
MedicationDispense
MedicationStatement
MedicationAdministration
AllergyIntolerance
Patient
Encounter
Practitioner
Organization
```

Keep FHIR mapping separate from pharmacy domain logic.

---

# 83. EXTERNAL DRUG DATABASE INTEGRATION

Design an adapter for future drug-information sources.

Potential information:

- generic
- brand
- strength
- ingredients
- interactions
- contraindications
- allergy information

Do not hardcode third-party provider logic into medication models.

Example:

```text
DrugInformationProviderInterface
```

---

# 84. AI READINESS

Future AI capabilities may include:

- medication reconciliation assistance
- interaction summarization
- adherence analysis
- stock forecasting
- expiry optimization
- dispensing anomaly detection
- medication-use analytics

AI must never silently:

- change a prescription
- substitute medication
- override a safety alert
- authorize controlled medication
- dispense medication

Any future AI suggestion must be:

- clearly identified
- reviewable
- clinician/pharmacist controlled
- auditable

---

# 85. REPORTING

Provide:

### Prescription Report

- provider
- patient
- medication
- date

### Dispensing Report

- pharmacy
- pharmacist
- medication
- quantity

### Stock Ledger

- medication
- batch
- location
- transaction

### Expiry Report

- expired
- near expiry

### Low Stock

- current
- minimum
- reorder level

### Consumption

- medication
- department
- period

### Controlled Medication

- transaction
- balance
- patient
- user

### Returns

- medication
- quantity
- reason

### Recall

- affected batches
- affected patients
- dispensing history

### Revenue

Reference Phase 4 Billing.

---

# 86. PERFORMANCE

Optimize:

- pharmacy queue
- medication search
- stock search
- batch selection
- dispensing
- patient medication history
- expiry reports

Use:

- indexes
- pagination
- eager loading
- caching for medication masters
- optimized stock queries

Medication search must remain responsive even with a large catalog.

---

# 87. DATABASE INDEXES

Consider indexes on:

```text
generic_id
brand_id
medication_id
batch_number
expiry_date
store_id
pharmacy_id
patient_id
prescription_id
dispensing_id
status
created_at
```

Use composite indexes based on actual queries.

Do not blindly index every column.

---

# 88. TRANSACTIONS

Use transactions for:

### Dispensing

```text
Verify prescription
Lock stock
Select batch
Deduct stock
Create stock transaction
Create dispensing
Publish billing event
Create audit
```

### Return

```text
Validate dispensing
Validate return policy
Update stock if accepted
Create return
Create stock transaction
Audit
```

### Transfer

```text
Lock source stock
Deduct source
Create transfer
Receive destination
Add destination stock
```

---

# 89. TESTING

## Unit Tests

Test:

- FEFO batch selection
- expiry validation
- unit conversion
- stock calculation
- interaction severity
- prescription status
- dispensing quantity
- partial dispensing
- reorder calculation

## Feature Tests

Test:

- prescription queue
- dispensing
- partial dispensing
- return
- stock adjustment
- transfer
- stock count
- expiry
- quarantine
- substitution

## Integration Tests

Test:

```text
Clinical Prescription → Pharmacy
Pharmacy → Billing
Pharmacy → Patient EMR
Pharmacy → Notification
Pharmacy → Audit
Pharmacy → Future Inventory Interface
```

---

# 90. SECURITY TESTING

Explicitly test:

```text
Hospital A Pharmacy User
        X
Hospital B Pharmacy Stock
```

Also test:

- unauthorized dispensing
- unauthorized stock adjustment
- unauthorized controlled-drug access
- unauthorized substitution
- unauthorized alert override
- unauthorized patient medication access
- API scope bypass
- direct URL access
- privilege escalation

---

# 91. NEGATIVE TESTING

Test:

- expired medication cannot be dispensed
- quarantined medication cannot be dispensed
- insufficient stock cannot be dispensed
- cancelled prescription cannot be dispensed
- expired prescription cannot be dispensed
- duplicate dispensing prevented
- duplicate return prevented
- negative stock prevented
- unauthorized substitution prevented
- finalized dispensing cannot be silently changed
- invalid unit conversion rejected

---

# 92. UI STRUCTURE

Suggested menu:

```text
Pharmacy
├── Dashboard
├── Prescription Queue
├── Dispensing
├── Returns
├── Medication
├── Generics
├── Brands
├── Batches
├── Stock
├── Stock Transfers
├── Stock Count
├── Expiry
├── Quarantine
├── Controlled Medicines
├── Medication Safety
├── Recalls
├── Reports
└── Settings
```

Use existing role/permission-aware menu generation.

---

# 93. PHARMACIST WORKSPACE

Provide:

```text
Patient
MRN
Prescription
Allergies
Medication History
Interaction Alerts
Prescription Details
Available Stock
Batch
Expiry
Quantity
Substitution
Dispensing
```

The pharmacist should be able to understand the prescription and safety context without navigating through unrelated screens.

---

# 94. STOCKKEEPER WORKSPACE

Provide:

- stock receipt
- batch entry
- stock transfer
- stock count
- expiry
- quarantine
- adjustments
- stock ledger

Do not expose clinical information unnecessarily.

---

# 95. PATIENT MEDICATION VIEW

Patient 360 should provide:

```text
Current Medications
Past Medications
Prescriptions
Dispensed Medications
Medication Allergies
Medication Safety Alerts
```

Keep prescribed/dispensed/administered distinctions clear.

---

# 96. FILES / DOCUMENTS

Use existing File Management for:

- prescription attachments
- supplier documents where appropriate
- medication registration documents
- recall documents

Do not build another document management system.

---

# 97. DEPLOYMENT

Document:

- migrations
- queue workers
- scheduler
- storage
- environment variables
- external drug database configuration if applicable
- notification configuration
- backup requirements

Do not store secrets in source control.

---

# 98. IMPLEMENTATION ORDER

Implement in this sequence:

### Step 1
Inspect Phases 0–6.

### Step 2
Inspect existing Phase 3 prescription implementation.

### Step 3
Map Billing integration.

### Step 4
Map Patient/Encounter integration.

### Step 5
Implement medication masters.

### Step 6
Implement generics and brands.

### Step 7
Implement dosage forms/routes/units.

### Step 8
Implement pharmacy locations/stores.

### Step 9
Implement batch management.

### Step 10
Implement pharmacy stock foundation.

### Step 11
Implement stock ledger/transactions.

### Step 12
Implement prescription integration.

### Step 13
Implement pharmacy queue.

### Step 14
Implement dispensing.

### Step 15
Implement partial dispensing.

### Step 16
Implement FEFO.

### Step 17
Implement returns.

### Step 18
Implement stock transfers.

### Step 19
Implement stock counting.

### Step 20
Implement expiry/quarantine.

### Step 21
Implement substitution.

### Step 22
Implement medication safety foundation.

### Step 23
Implement controlled medication foundation.

### Step 24
Implement recall foundation.

### Step 25
Implement Billing integration.

### Step 26
Implement EMR integration.

### Step 27
Implement notifications.

### Step 28
Implement reports/dashboard.

### Step 29
Implement API.

### Step 30
Implement FHIR mapping foundation.

### Step 31
Implement external drug-information adapter.

### Step 32
Implement automated tests.

### Step 33
Perform security review.

### Step 34
Perform concurrency review.

### Step 35
Perform performance review.

### Step 36
Update documentation.

---

# 99. NON-GOALS

Do not implement as part of Phase 7 unless explicitly required:

- full procurement system
- enterprise inventory management
- accounting
- insurance claims engine
- complete inpatient MAR
- nursing medication administration
- chemotherapy management
- automated prescribing
- autonomous medication substitution
- AI prescribing
- AI diagnosis

Build clean integration points for future phases.

---

# 100. DEFINITION OF DONE

Phase 7 is complete when:

- medication master works
- generic master works
- brand master works
- formulation works
- dosage forms work
- routes work
- medication ingredients work
- pharmacy locations work
- pharmacy stores work
- batch tracking works
- expiry tracking works
- FEFO works
- stock ledger works
- stock transactions work
- stock adjustments work
- stock transfers work
- stock counting works
- prescription integration works
- pharmacy queue works
- pharmacist review works
- dispensing works
- partial dispensing works
- returns work
- substitution works
- quarantine works
- recall foundation works
- medication safety foundation works
- controlled medication foundation works
- Billing integration works
- Patient/EMR integration works
- notifications work
- audit works
- RBAC works
- multi-hospital scope works
- API works
- dashboard works
- reporting works
- FHIR readiness exists
- external drug-information integration foundation exists
- automated tests exist
- security tests exist
- concurrency tests exist
- documentation exists
- Phases 0–6 continue to work without regression

---

# 101. FINAL AI IMPLEMENTATION PROMPT

You are now authorized to implement **Phase 7 — Pharmacy & Medication Management** in the existing Hospital ERP repository.

Before modifying code:

1. Inspect the repository.
2. Inspect the existing modular architecture.
3. Inspect Phases 0–6.
4. Inspect Phase 3 Prescription implementation carefully.
5. Identify the actual existing Patient, Encounter, Clinical Order, Billing, User, Permission, Audit and Notification implementations.
6. Reuse existing infrastructure.
7. Do not create duplicate systems.
8. Create an implementation plan based on the actual repository.

Then implement Phase 7 incrementally.

## Mandatory rules

- Do not create a second modular architecture.
- Do not create a second prescription system.
- Do not duplicate Patient.
- Do not duplicate Encounter.
- Do not duplicate Billing.
- Do not duplicate authentication/RBAC.
- Do not duplicate notifications.
- Do not duplicate audit.
- Do not create a competing enterprise procurement system.
- Do not create a competing enterprise inventory system if one already exists.
- Do not allow silent stock changes.
- Do not allow negative stock by default.
- Do not dispense expired or quarantined medication.
- Do not silently substitute medication.
- Do not silently overwrite dispensing records.
- Do not silently modify finalized clinical/dispensing records.
- Do not hardcode drug classifications.
- Do not invent drug interaction data.
- Do not expose sensitive patient information unnecessarily.
- Do not claim tests passed unless actually executed.

## Clinical safety priorities

Always prioritize:

1. Correct patient
2. Correct prescription
3. Correct medication
4. Correct strength
5. Correct dosage form
6. Correct route
7. Correct quantity
8. Correct batch
9. Correct expiry
10. Correct dispensing
11. Complete audit trail

## Stock safety

Every stock movement must create an auditable transaction.

Use:

```text
Transaction
→ Batch
→ Location
→ Quantity
→ User
→ Reference
→ Timestamp
```

Use database transactions and row locking where necessary.

## Prescription integration

The existing Clinical module owns the clinical prescription.

Pharmacy consumes and fulfills it.

Architecture:

```text
Clinical Encounter
       ↓
Prescription
       ↓
Pharmacy
       ↓
Dispensing
       ↓
Billing
       ↓
Patient Medication History
```

Do not create a competing prescription lifecycle.

## Billing integration

Pharmacy publishes chargeable events.

Billing owns:

- pricing
- invoice
- payment
- receipt
- refund

Pharmacy owns:

- medication
- batch
- quantity
- dispensing

## Medication safety

Use the existing allergy information.

Build an extension point for drug interaction checking.

Do not invent medical rules.

Any safety alert override must be:

- authorized
- reasoned
- audited

## Inventory boundary

Phase 7 provides the pharmacy stock foundation.

Phase 15 will provide enterprise inventory/procurement.

Do not create duplicate procurement functionality.

## Future nursing integration

Phase 9 will provide complete inpatient medication administration/MAR.

Phase 7 must preserve the relationship:

```text
Prescription
    ↓
Dispensing
    ↓
Medication Administration
```

Do not implement the full MAR in Phase 7.

## API

Follow the existing `/api/v1` conventions.

Every API endpoint must enforce:

- authentication
- authorization
- organization scope
- hospital scope
- branch scope
- pharmacy/store scope
- patient/resource authorization

## Security

Explicitly test:

```text
Hospital A Pharmacy User
        X
Hospital B Pharmacy Data
```

Also test:

- unauthorized dispensing
- unauthorized stock adjustment
- unauthorized controlled medication access
- unauthorized substitution
- unauthorized safety-alert override
- unauthorized patient medication access
- direct URL access
- API object-level authorization
- privilege escalation

## Testing

Create and execute:

- unit tests
- feature tests
- integration tests
- API tests
- security tests
- concurrency tests
- stock integrity tests

Especially test:

```text
Two pharmacists
      ↓
Same batch
      ↓
Same medication
      ↓
Concurrent dispensing
```

The system must prevent stock corruption or negative stock.

## Final implementation report

After implementation provide:

### 1. Implementation Summary

### 2. Database

Tables, migrations, indexes, constraints and relationships.

### 3. Models

New/modified models.

### 4. Services / Actions

Business operations.

### 5. Controllers

Web/API controllers.

### 6. Routes

Web/API routes.

### 7. Permissions

New permissions.

### 8. Events / Listeners

New events and listeners.

### 9. Jobs

Queued jobs.

### 10. Notifications

Notification workflows.

### 11. Prescription Integration

Explain how Phase 3 Prescription integrates with Pharmacy.

### 12. Billing Integration

Explain how Pharmacy publishes chargeable events.

### 13. Inventory Boundary

Explain what is handled now and what is deferred to Phase 15.

### 14. Medication Safety

Explain allergy/interactions/alerts.

### 15. Security

Explain RBAC and hospital/branch/pharmacy scope.

### 16. Testing

List tests created and tests actually executed.

Never claim tests passed unless they were actually executed.

### 17. Deployment

Migrations, queues, scheduler, configuration and environment requirements.

### 18. Deferred Features

Clearly identify future functionality.

The final result must be a **production-grade Pharmacy & Medication Management module**, not a simple CRUD application.

Clinical safety, medication traceability, stock integrity, dispensing accuracy, auditability, security and interoperability are mandatory.