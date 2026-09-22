Below is a **production-oriented Phase 5 — Laboratory Information System (LIS)** specification designed to sit on top of your existing **Phase 0–4 HMS**, with the assumption that **Modular Architecture is already implemented**.

The key architectural principle is:

> **Clinical Order → Lab Order → Specimen → Processing → Result → Validation → Report → Patient EMR + Billing**

The LIS should be independently usable by laboratory staff while integrating tightly with **Patient/MPI, Clinical Encounter, Billing, Notifications, Documents, Audit, and future FHIR/HL7 interfaces**.

# Phase 5 — Laboratory Information System (LIS)

## 1. Purpose

Phase 5 introduces the complete laboratory workflow:

```text
Patient
   ↓
Clinical Encounter
   ↓
Laboratory Order
   ↓
Billing / Charge
   ↓
Lab Registration
   ↓
Sample Collection
   ↓
Specimen Accession
   ↓
Sample Processing
   ↓
Testing
   ↓
Result Entry
   ↓
Technical Validation
   ↓
Pathologist / Authorized Validation
   ↓
Lab Report
   ↓
Patient EMR
```

The LIS must support both:

- **Routine laboratory operations**
- **Future analyzer/instrument integration**

---

# 2. Scope

Phase 5 should include:

1. Laboratory Master Data
2. Test Catalog
3. Test Categories
4. Test Panels/Profiles
5. Specimen Types
6. Containers
7. Sample Requirements
8. Reference Ranges
9. Critical Values
10. Laboratory Orders
11. Lab Registration
12. Sample Collection
13. Barcode Management
14. Specimen Accession
15. Sample Receiving
16. Sample Rejection
17. Sample Processing
18. Test Assignment
19. Worklists
20. Result Entry
21. Result Validation
22. Pathologist Approval
23. Critical Result Management
24. Lab Reports
25. Result History
26. Result Amendment
27. Result Cancellation
28. Quality Control Foundation
29. Analyzer Integration Foundation
30. HL7/FHIR Integration Readiness
31. Billing Integration
32. Notifications
33. Lab Dashboard
34. Laboratory Reporting
35. Audit
36. Role-based access
37. Multi-hospital/branch scope

---

# 3. LIS Architecture

The relationship with existing modules should be:

```text
                    Phase 1
                 Patient / MPI
                       │
                       ▼
                    Phase 3
              Clinical Encounter
                       │
                       ▼
                 Clinical Order
                       │
                       ▼
               ┌───────────────┐
               │      LIS      │
               └───────┬───────┘
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
      Specimen      Testing      Billing
          │            │            │
          ▼            ▼            ▼
      Collection     Result       Invoice
                       │
                       ▼
                   Validation
                       │
                       ▼
                  Lab Report
                       │
              ┌────────┴────────┐
              ▼                 ▼
          Patient EMR       Doctor Portal
```

---

# 4. Clinical Order vs Lab Order

This distinction is important.

A Phase 3 physician creates:

> Clinical Order — Laboratory — CBC

The LIS converts/receives this into:

> Lab Order — CBC

The LIS then controls:

- Sample
- Specimen
- Testing
- Result
- Validation
- Reporting

The clinical module should **not manage laboratory processing**.

---

# 5. Laboratory Master Data

Create configurable master data for:

### Laboratory

- Laboratory
- Branch
- Department
- Section

Example:

```text
Central Laboratory
├── Hematology
├── Biochemistry
├── Microbiology
├── Immunology
├── Serology
├── Clinical Pathology
└── Molecular Diagnostics
```

---

# 6. Test Catalog

Create:

`lab_tests`

Suggested fields:

```text
id
organization_id
hospital_id
code
name
short_name
category_id
section_id
description
test_type
method
unit
specimen_type_id
container_type_id
fasting_required
turnaround_time_minutes
is_panel
is_active
effective_from
effective_to
created_by
updated_by
created_at
updated_at
```

Test types may include:

- Quantitative
- Qualitative
- Semi-quantitative
- Text
- Categorical
- Calculated
- Microbiology
- Culture
- Pathology

Do not assume every laboratory result is numeric.

---

# 7. Test Categories

Examples:

- Hematology
- Biochemistry
- Clinical Pathology
- Microbiology
- Serology
- Immunology
- Hormone
- Molecular
- Histopathology
- Cytology
- Blood-related testing
- Other

Categories must be configurable.

---

# 8. Test Panels / Profiles

Support profiles such as:

```text
CBC
LFT
RFT
Lipid Profile
Thyroid Profile
Urine R/E
```

Example:

```text
CBC
 ├── Hemoglobin
 ├── RBC
 ├── WBC
 ├── Platelet
 ├── Hematocrit
 └── MCV
```

Create a structure such as:

`lab_panels`

`lab_panel_items`

A panel should reference individual tests rather than duplicating test definitions.

---

# 9. Specimen Management

Create specimen master data.

Examples:

- Blood
- Serum
- Plasma
- Urine
- Stool
- Sputum
- Swab
- CSF
- Tissue
- Semen
- Other

Store:

- Specimen code
- Name
- Description
- Collection requirements
- Stability
- Storage temperature
- Maximum processing time

---

# 10. Container Management

Examples:

- EDTA tube
- Plain tube
- Citrate tube
- Fluoride tube
- Urine container
- Sterile container
- Swab container

A test should specify its required specimen/container where applicable.

---

# 11. Sample Requirements

Support:

- Fasting required
- Patient preparation
- Minimum volume
- Preferred volume
- Collection instructions
- Transport instructions
- Storage requirements
- Stability duration

Example:

```text
Test: Fasting Blood Glucose

Fasting: Yes
Specimen: Plasma
Container: Fluoride tube
```

---

# 12. Reference Ranges

Reference ranges must be configurable.

A reference range may depend on:

- Test
- Age
- Gender
- Pregnancy status
- Specimen
- Method
- Laboratory
- Unit
- Effective date

Example:

```text
Hemoglobin

Adult Male
13.0–17.0 g/dL

Adult Female
12.0–15.0 g/dL
```

Do not hardcode reference ranges into PHP code.

---

# 13. Result Types

The system must support:

### Numeric

```text
Hemoglobin = 14.2
```

### Text

```text
Microscopy = No abnormal cells seen
```

### Qualitative

```text
Positive / Negative
```

### Categorical

```text
Trace / + / ++ / +++
```

### Calculated

Example:

```text
BMI
eGFR
LDL calculation
```

Calculated tests must use controlled formulas and versioning.

---

# 14. Critical Values

Each test can have configurable critical thresholds.

Example:

```text
Potassium < 2.5
Potassium > 6.5
```

When a critical result occurs:

```text
Result Entered
      ↓
Critical Value Detected
      ↓
Alert
      ↓
Technician / Pathologist
      ↓
Responsible Clinician Notification
      ↓
Acknowledgement
```

Record:

- Critical value
- Detected by
- Notification recipient
- Notification method
- Time
- Acknowledgement
- Acknowledged by
- Notes

---

# 15. Laboratory Order

Create:

`lab_orders`

Suggested:

```text
id
organization_id
hospital_id
branch_id
lab_id
patient_id
encounter_id
clinical_order_id
order_number
priority
status
ordered_by
ordered_at
requested_collection_at
clinical_notes
created_at
updated_at
```

Statuses may include:

```text
Ordered
Registered
Awaiting Collection
Collected
Received
Processing
Partial Result
Awaiting Validation
Validated
Reported
Cancelled
Rejected
```

Do not allow arbitrary status changes.

---

# 16. Lab Order Items

Create:

`lab_order_items`

Fields:

```text
id
lab_order_id
test_id
panel_id nullable
priority
status
requested_at
collected_at
result_status
completed_at
created_at
updated_at
```

A single laboratory order may contain multiple tests.

---

# 17. Lab Registration

When a patient arrives:

```text
Clinical Order
      ↓
Lab Registration
      ↓
Billing Validation
      ↓
Specimen Requirement
      ↓
Collection
```

Registration should show:

- Patient
- MRN
- Encounter
- Ordered tests
- Payment status where required
- Insurance/corporate status
- Collection requirements

---

# 18. Specimen Accession

Each collected specimen should receive a unique accession identifier.

Example:

```text
LAB-2026-00004521
```

Never use a simple row count.

Accession numbers must be:

- Unique
- Concurrency-safe
- Immutable

---

# 19. Specimen Model

Create:

`lab_specimens`

Suggested:

```text
id
organization_id
hospital_id
branch_id
lab_order_id
accession_number
specimen_type_id
container_type_id
barcode
status
collected_by
collected_at
received_by
received_at
rejected_by
rejected_at
rejection_reason
storage_location
notes
created_at
updated_at
```

---

# 20. Barcode

Support barcode generation for specimens.

Barcode should identify the specimen/accession.

Workflow:

```text
Registration
     ↓
Barcode Generated
     ↓
Printed
     ↓
Attached to Container
     ↓
Scanned During Processing
```

Design the barcode service so future barcode scanners can integrate easily.

---

# 21. Sample Collection

Collection workflow:

```text
Awaiting Collection
        ↓
Patient Verified
        ↓
Specimen Collected
        ↓
Barcode Applied
        ↓
Collection Recorded
        ↓
Sample Sent to Laboratory
```

Record:

- Collector
- Date/time
- Specimen
- Container
- Collection location
- Notes

---

# 22. Sample Rejection

Support specimen rejection.

Reasons:

- Insufficient quantity
- Wrong container
- Hemolysed
- Clotted
- Leaked
- Contaminated
- Delayed transport
- Incorrect identification
- Expired/stability exceeded
- Other

Record:

- Rejection reason
- Rejected by
- Date/time
- Comments

The doctor/patient should be notified when recollection is required.

---

# 23. Sample Receiving

Laboratory receiving workflow:

```text
Collected
   ↓
Transport
   ↓
Received
   ↓
Verify
   ├── Accept
   └── Reject
```

Receiving staff should verify:

- Barcode
- Patient
- Specimen
- Quantity
- Container
- Condition
- Collection time

---

# 24. Sample Processing

Support laboratory processing states:

- Received
- Centrifuged
- Aliquoted
- Prepared
- Assigned
- Processing
- Completed

For initial implementation, keep processing generic.

Specialized workflows can be expanded later.

---

# 25. Test Worklists

Each lab section should have a worklist.

Example:

```text
Hematology Worklist
-------------------
CBC
CBC
ESR
CBC
```

Filters:

- Section
- Test
- Priority
- Status
- Date
- Analyzer
- Technician

---

# 26. Result Entry

Create:

`lab_results`

Suggested:

```text
id
lab_order_item_id
test_id
specimen_id
result_type
numeric_value
text_value
qualitative_value
unit
reference_range
abnormal_flag
critical_flag
result_status
entered_by
entered_at
validated_by
validated_at
reported_at
created_at
updated_at
```

Do not force all results into a single numeric column.

---

# 27. Abnormal Flags

Support:

- Normal
- Low
- High
- Critical Low
- Critical High
- Positive
- Negative
- Abnormal
- Panic
- Not Applicable

The flag should be derived from configured reference/critical ranges where possible.

Do not rely solely on manual flagging.

---

# 28. Result Validation

Laboratory workflow:

```text
Result Entry
    ↓
Technical Review
    ↓
Validation
    ↓
Pathologist/Authorized Approval
    ↓
Report
```

Roles should determine who can validate.

---

# 29. Result Status

Recommended:

```text
Pending
Entered
Technically Validated
Pathologist Validated
Reported
Amended
Cancelled
```

Not every test necessarily requires the same validation level; make workflow configurable.

---

# 30. Result Amendment

Once a result is reported:

> Never silently overwrite it.

If correction is required:

```text
Original Result
      ↓
Amendment
      ↓
Reason
      ↓
Authorized User
      ↓
New Result Version
```

Keep:

- Original result
- Amended result
- Reason
- User
- Timestamp

---

# 31. Result History

For every result show:

```text
Result Version 1
Result Version 2
Result Version 3
```

with:

- Author
- Validator
- Timestamp
- Amendment reason

This is essential for medical/legal auditability.

---

# 32. Laboratory Report

Generate reports containing:

### Header

- Hospital
- Laboratory
- Address/contact
- Patient
- MRN
- Age
- Gender
- Encounter
- Lab accession

### Results

| Test | Result | Unit | Reference Range | Flag |
|---|---:|---|---|---|

### Footer

- Collected date/time
- Report date/time
- Technician
- Validator
- Pathologist
- Report status

---

# 33. Report Status

Reports should clearly indicate:

- Preliminary
- Final
- Amended
- Cancelled

Do not allow a preliminary report to appear identical to a final report.

---

# 34. Patient EMR Integration

After validation:

```text
Lab Result
     ↓
Patient Clinical Timeline
     ↓
Patient 360
     ↓
Doctor Workspace
```

Phase 3 should be able to display:

- Lab orders
- Results
- Reports
- Historical trends

Do not duplicate results into the Patient table.

---

# 35. Result Trends

For numeric tests, provide historical trend data.

Example:

```text
Hemoglobin
14.2
13.8
12.9
12.4
```

Future UI can display a graph.

This should query actual historical results rather than maintaining a separate trend table unless performance requires one.

---

# 36. Billing Integration

When a clinical order is created:

```text
Clinical Order
      ↓
Lab Order
      ↓
Billing Charge
```

Use Phase 4.

The LIS should NOT implement its own invoice/payment engine.

Billing should remain responsible for:

- Price
- Invoice
- Payment
- Receipt
- Refund

---

# 37. Payment Rules

The laboratory configuration may define whether testing requires:

- Full payment
- Partial payment
- Credit
- Corporate authorization
- Insurance authorization

But the actual financial transaction must remain in Phase 4.

---

# 38. Analyzer Integration

Prepare an integration layer.

Potential future interfaces:

- ASTM
- HL7
- Vendor APIs
- File-based exchange
- Database integration

Do not hardcode analyzer-specific logic into test/result models.

Use an adapter pattern.

Example:

```text
Analyzer
   ↓
Analyzer Adapter
   ↓
Normalized Result
   ↓
LIS Result
```

---

# 39. Analyzer Worklist

Future analyzer workflow:

```text
Lab Order
   ↓
Analyzer Worklist
   ↓
Analyzer
   ↓
Result
   ↓
Result Validation
```

Prepare:

`lab_analyzers`

`lab_analyzer_tests`

`lab_analyzer_results`

where appropriate.

---

# 40. Quality Control Foundation

Provide the foundation for:

- QC materials
- QC runs
- Control values
- Expected ranges
- QC status
- Instrument
- Lot
- Expiry

Future expansion can implement:

- Levey-Jennings charts
- Westgard rules
- QC trend analysis

Do not overbuild the initial QC engine if not required for deployment.

---

# 41. Laboratory Inventory Foundation

The LIS may reference:

- Reagents
- Consumables
- QC materials

But full inventory should remain with the future **Inventory / Procurement module**.

Use integration interfaces rather than duplicate stock management.

---

# 42. Notifications

Support notifications for:

- Sample rejected
- Recollection required
- Critical result
- Result validated
- Report ready
- Report amended

Use the existing Phase 0 notification system.

---

# 43. Laboratory Dashboard

### Today's workload

- Orders
- Samples collected
- Samples pending
- Samples rejected
- Tests pending
- Results pending validation
- Reports completed
- Critical results

### Operational KPIs

- Average turnaround time
- Pending samples
- Rejection rate
- Test volume
- Critical results
- Analyzer workload

Keep KPI definitions explicit.

---

# 44. Laboratory Reports

Implement:

### Operational

- Daily test volume
- Pending tests
- Sample collection report
- Sample rejection report
- Turnaround time

### Clinical

- Result report
- Critical result report
- Abnormal result report
- Patient result history

### Management

- Tests by department
- Tests by provider
- Tests by laboratory
- Revenue by laboratory service

### Quality

- Sample rejection
- Result amendments
- Critical result notification
- Validation turnaround

---

# 45. Roles

Potential LIS roles:

### Lab Receptionist

- Registration
- Billing verification
- Appointment/order verification

### Phlebotomist / Sample Collector

- Collection
- Barcode
- Specimen handling

### Lab Technician

- Sample processing
- Result entry

### Senior Technician

- Technical validation

### Pathologist

- Validation
- Report approval
- Amendments

### Lab Manager

- Configuration
- Workload
- Quality
- Reporting

Use the existing RBAC system.

---

# 46. Permissions

Suggested:

```text
lab.dashboard.view

lab.test.view
lab.test.create
lab.test.update

lab.panel.view
lab.panel.create
lab.panel.update

lab.specimen.view
lab.specimen.collect
lab.specimen.receive
lab.specimen.reject
lab.specimen.process

lab.order.view
lab.order.create
lab.order.cancel

lab.result.view
lab.result.create
lab.result.update
lab.result.validate
lab.result.amend

lab.report.view
lab.report.generate
lab.report.print
lab.report.export

lab.critical_result.view
lab.critical_result.notify
lab.critical_result.acknowledge

lab.qc.view
lab.qc.create

lab.analyzer.view
lab.analyzer.configure

lab.report.approve
lab.settings.manage
lab.audit.view
```

Adapt to the existing permission naming scheme.

---

# 47. Audit

Audit at least:

- Lab order created
- Order cancelled
- Sample collected
- Sample received
- Sample rejected
- Sample processed
- Result entered
- Result modified
- Result validated
- Result reported
- Critical result detected
- Critical result notified
- Critical result acknowledged
- Report generated
- Report printed
- Report exported
- Result amended

Do not store entire result payload unnecessarily in generic audit logs.

---

# 48. FHIR / HL7 Readiness

Prepare mapping for:

### FHIR

- ServiceRequest
- Specimen
- Observation
- DiagnosticReport
- Patient
- Encounter
- Practitioner
- Organization

### HL7

Prepare for:

- Order messages
- Result messages
- Patient demographics
- Acknowledgement messages

Do not implement a full HL7 engine unless specifically required.

---

# 49. API

Base:

```text
/api/v1/lab
```

Potential endpoints:

```text
GET    /tests
POST   /tests

GET    /panels
POST   /panels

GET    /orders
POST   /orders
GET    /orders/{order}

POST   /orders/{order}/register
POST   /orders/{order}/collect
POST   /orders/{order}/receive

GET    /specimens
GET    /specimens/{specimen}
POST   /specimens/{specimen}/reject

GET    /worklists
GET    /results
POST   /results
POST   /results/{result}/validate
POST   /results/{result}/amend

GET    /reports
GET    /reports/{report}
POST   /reports/{report}/finalize

GET    /critical-results
POST   /critical-results/{result}/acknowledge
```

Follow the existing Phase 0 API response format.

---

# 50. Clinical Data Security

Laboratory results are clinical data.

Enforce:

- Hospital scope
- Branch scope
- Department/lab scope
- Role permissions
- Patient access rules
- Audit
- Break-glass where applicable

Never expose laboratory results through an endpoint without authorization.

---

# 51. Database Integrity

Use:

- Foreign keys
- Unique accession numbers
- Unique order numbers
- Unique barcode where applicable
- Appropriate indexes
- Decimal numeric result storage
- UTC timestamps

Important indexes:

- patient_id
- encounter_id
- clinical_order_id
- lab_order_id
- specimen_id
- test_id
- status
- accession_number
- barcode
- collected_at
- reported_at

---

# 52. Transactions

Use database transactions for:

### Lab registration

```text
Validate order
→ Create lab order
→ Create items
→ Billing integration
→ Audit
```

### Specimen collection

```text
Validate patient
→ Create accession
→ Create specimen
→ Generate barcode
→ Update order status
→ Audit
```

### Result validation

```text
Validate result
→ Check reference range
→ Check critical value
→ Validate
→ Audit
→ Trigger notification
```

### Report finalization

```text
Validate all required results
→ Generate report
→ Mark final
→ Lock results
→ Audit
→ Notify
```

---

# 53. Concurrency

Protect against:

- Duplicate accession numbers
- Duplicate barcode
- Double result validation
- Double report finalization
- Duplicate lab registration
- Duplicate analyzer result import

Use:

- Unique constraints
- Transactions
- Row locks where appropriate
- Idempotency for integrations

---

# 54. Performance

Avoid N+1 queries.

Use:

- Eager loading
- Pagination
- Proper indexes
- Worklist-specific queries
- Date filters
- Status filters

Do not load every historical lab result when opening a patient's current encounter.

---

# 55. Testing

Implement:

## Unit tests

- Reference range calculation
- Critical value detection
- Result flag calculation
- Turnaround calculation
- Panel expansion
- Billing mapping

## Feature tests

- Create lab order
- Register patient
- Collect sample
- Barcode
- Receive sample
- Reject sample
- Enter result
- Validate result
- Critical result
- Generate report
- Amend result

## Integration tests

- Clinical Order → Lab Order
- Lab Order → Billing Charge
- Result → EMR
- Critical Result → Notification
- Final Report → Patient Timeline

## Security tests

Most important:

> Hospital A laboratory user cannot access Hospital B laboratory data.

Also test:

- Unauthorized result validation
- Unauthorized report approval
- Unauthorized amendment
- Unauthorized export
- API scope bypass

---

# 56. Non-Goals

Do NOT implement full:

- Radiology/PACS
- Pharmacy
- IPD
- OT
- ICU
- Blood Bank
- General Inventory
- Procurement
- Full Accounting
- Full Insurance Claims

Only create clean integration interfaces.

---

# 57. Recommended Module Structure

Since modular architecture already exists, adapt to the actual project.

Conceptually:

```text
Laboratory Module
│
├── Models
├── Services
├── Actions
├── Policies
├── Requests
├── Controllers
├── Events
├── Listeners
├── Jobs
├── Notifications
├── Reports
├── API
├── Views
├── Routes
├── Database
└── Tests
```

Do not create a new module framework.

---

# 58. Implementation Sequence

The AI coding agent should implement in this order:

### Step 1
Inspect existing Phase 0–4 architecture.

### Step 2
Map existing:

- Patient
- Encounter
- Clinical Order
- Billing
- Organization
- Hospital
- Branch
- Department
- User
- Permissions
- Audit
- Notifications
- Files

### Step 3
Create LIS database migrations.

### Step 4
Implement master data.

### Step 5
Implement test catalog.

### Step 6
Implement panels.

### Step 7
Implement specimen configuration.

### Step 8
Implement laboratory orders.

### Step 9
Implement registration.

### Step 10
Implement specimen collection.

### Step 11
Implement barcode/accession.

### Step 12
Implement sample receiving/rejection.

### Step 13
Implement worklists.

### Step 14
Implement result entry.

### Step 15
Implement validation.

### Step 16
Implement critical result workflow.

### Step 17
Implement report generation.

### Step 18
Implement result amendment.

### Step 19
Implement Phase 4 billing integration.

### Step 20
Implement notifications.

### Step 21
Implement API.

### Step 22
Implement reports.

### Step 23
Implement audit/security.

### Step 24
Implement analyzer integration interfaces.

### Step 25
Implement FHIR/HL7 mapping foundations.

### Step 26
Run tests.

### Step 27
Document deployment and operation.

---

# 59. Definition of Done

Phase 5 is complete only when:

- Laboratory master data works.
- Test catalog works.
- Panels work.
- Specimen configuration works.
- Reference ranges work.
- Critical values work.
- Clinical orders become lab orders.
- Lab registration works.
- Accession numbers are unique.
- Barcode workflow works.
- Sample collection works.
- Sample receiving works.
- Sample rejection works.
- Worklists work.
- Result entry works.
- Result validation works.
- Critical result workflow works.
- Reports work.
- Result amendment works.
- EMR integration works.
- Billing integration works.
- Notifications work.
- Audit works.
- RBAC works.
- Hospital/branch scope works.
- API works.
- Analyzer integration foundation exists.
- FHIR/HL7 readiness exists.
- Automated tests execute successfully.
- Phase 0–4 functionality remains intact.

Do not declare completion merely because the UI exists.

---

# 60. Final AI Implementation Instruction

Treat LIS as a **clinical laboratory information system**, not merely a test-result CRUD module.

Clinical data integrity, patient identification, specimen traceability, result immutability, validation, critical-result communication, auditability, and hospital-level access control are mandatory.

Do not duplicate:

- Patient
- Encounter
- Clinical Order
- Billing
- Authentication
- Authorization
- Audit
- Notification
- File management

from existing modules.

Use the existing modular architecture.

The laboratory must be able to operate its complete workflow:

```text
Order
 ↓
Registration
 ↓
Collection
 ↓
Accession
 ↓
Receiving
 ↓
Processing
 ↓
Testing
 ↓
Result Entry
 ↓
Validation
 ↓
Critical Result Handling
 ↓
Report
 ↓
EMR
```

while the financial workflow remains:

```text
Lab Service
 ↓
Billing Charge
 ↓
Invoice
 ↓
Payment
 ↓
Receipt
```

Before coding:

**Inspect the existing repository and Phase 0–4 implementations. Map the LIS requirements to the existing architecture and reuse existing infrastructure.**

Do not introduce a second modular architecture.

Do not claim tests passed unless they were actually executed.

At the end, provide an implementation report containing:

1. Implemented features
2. Database changes
3. Models/services/actions
4. Routes/API
5. Permissions
6. Events/listeners/jobs
7. Phase 3 integration
8. Phase 4 billing integration
9. Tests executed
10. Security tests
11. Deployment instructions
12. Remaining/deferred work

**Begin with repository analysis and architecture mapping.**
:::

### Recommended LIS boundary

One particularly important decision is to **keep the LIS responsible for laboratory workflow, not general inventory**.

```text
                 LIS
                  │
       ┌──────────┼──────────┐
       ▼          ▼          ▼
    Orders     Samples     Results
       │          │          │
       │          │          ▼
       │          │       Reports
       │          │
       ▼          ▼
    Billing    Specimen
       │       Workflow
       ▼
   Phase 4
```