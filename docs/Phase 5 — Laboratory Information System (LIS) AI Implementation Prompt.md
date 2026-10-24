# Phase 5 — Laboratory Information System (LIS)
## AI Implementation Prompt

You are a senior healthcare software architect and Laravel/PHP engineer implementing **Phase 5 — Laboratory Information System (LIS)** in an existing Hospital Management Software / Hospital ERP project.

The project already has a **modular monolith architecture**, and Phases 0–4 have already been implemented:

- Phase 0 — Foundation & Platform
- Phase 1 — Patient / Master Patient Index
- Phase 2 — Appointment & Scheduling
- Phase 3 — OPD / Clinical Encounter / EMR
- Phase 4 — Billing & Revenue

Your responsibility is to implement the **Laboratory Information System (LIS)** as a production-grade clinical module.

---

# 1. CRITICAL ARCHITECTURE RULE

**Modular Architecture has already been implemented.**

DO NOT:

- create another modular framework
- introduce another module/package architecture
- duplicate authentication
- duplicate users/roles/permissions
- duplicate organization/hospital/branch/department management
- duplicate Patient/MPI
- duplicate Clinical Encounter
- duplicate Clinical Orders
- duplicate Billing
- duplicate Notifications
- duplicate Audit infrastructure
- duplicate File/Document management
- create a second API architecture
- create independent tenant logic

Before writing code:

1. Inspect the existing repository.
2. Identify the existing module structure.
3. Identify how Phases 0–4 were implemented.
4. Follow the existing conventions exactly.
5. Reuse existing services, models, traits, policies, middleware, events, jobs, components, layouts, API responses and utilities wherever applicable.
6. Only create new infrastructure when the existing system genuinely does not provide the required capability.

The implementation must look like a natural extension of the existing application.

---

# 2. TECHNOLOGY BASELINE

Use the project's existing technology stack.

Expected baseline:

- PHP 8.4+
- Laravel
- MySQL/PostgreSQL according to existing project configuration
- Redis where already used
- Blade
- AdminLTE or the project's existing UI framework
- REST API
- Laravel Queue
- Laravel Scheduler
- Policies/Gates
- Form Requests
- Services/Actions
- Events/Listeners
- Notifications
- Database Transactions
- PHPUnit/Pest according to existing project
- PSR-12 / Laravel coding conventions

Do not replace existing technologies without a compelling technical reason.

---

# 3. PRIMARY OBJECTIVE

Build a complete LIS workflow:

```text
Patient
   ↓
Clinical Encounter
   ↓
Clinical Laboratory Order
   ↓
Billing / Charge
   ↓
Laboratory Registration
   ↓
Specimen Collection
   ↓
Barcode / Accession
   ↓
Specimen Receiving
   ↓
Specimen Processing
   ↓
Test Assignment / Worklist
   ↓
Result Entry
   ↓
Technical Validation
   ↓
Pathologist / Authorized Validation
   ↓
Critical Result Management
   ↓
Laboratory Report
   ↓
Patient EMR / Patient 360
```

The system must support both:

1. Orders originating from the Clinical/EMR workflow.
2. Direct laboratory registration where permitted by organizational policy.

---

# 4. MODULE BOUNDARY

The LIS owns:

- laboratory configuration
- test catalog
- panels/profiles
- specimen configuration
- laboratory orders
- laboratory registration
- accession
- specimen collection
- barcode tracking
- specimen receiving
- specimen rejection
- specimen processing
- test worklists
- result entry
- result validation
- critical-result workflow
- laboratory reports
- result history
- result amendment
- laboratory QC foundation
- analyzer integration foundation
- laboratory dashboards
- laboratory reports
- LIS-specific audit events

The LIS does NOT own:

- Patient master data
- Appointment management
- Clinical encounters
- General billing/payment
- Pharmacy inventory
- General inventory
- Procurement
- Accounting
- Full insurance claims processing
- Radiology/PACS
- OT
- ICU
- Blood Bank

Use existing modules for these capabilities.

---

# 5. EXISTING MODULE INTEGRATION

Inspect and reuse existing implementations for:

## Patient

Use the existing Patient/MPI as the source of truth.

Do not create another patient table.

LIS must reference:

```text
patient_id
```

Patient information should be retrieved from the existing Patient module.

---

## Clinical Encounter

Use the existing Encounter module.

LIS orders should reference:

```text
encounter_id
```

Do not duplicate encounter information.

---

## Clinical Orders

Phase 3 provides the generic clinical order framework.

Example:

```text
Doctor
 ↓
Clinical Order
 ↓
Laboratory — CBC
 ↓
LIS Lab Order
```

The Clinical module records the physician's intent.

The LIS manages:

- registration
- specimen
- testing
- results
- validation
- reporting

Do not move laboratory workflow into the Clinical module.

---

## Billing

Phase 4 owns financial transactions.

Laboratory should publish chargeable events to Billing.

Correct architecture:

```text
Clinical Order
      ↓
Lab Order
      ↓
Chargeable Event
      ↓
Billing
      ↓
Invoice
      ↓
Payment
      ↓
Receipt
```

Do not create another invoice/payment system inside LIS.

---

## Notifications

Reuse the existing notification system for:

- sample rejection
- recollection request
- critical results
- result validation
- report availability
- report amendment

---

## Audit

Reuse the central audit system.

LIS must generate meaningful clinical audit events.

---

# 6. LABORATORY MASTER DATA

Implement configurable laboratory master data.

Support:

```text
Laboratory
 ├── Branch
 ├── Sections
 │    ├── Hematology
 │    ├── Biochemistry
 │    ├── Microbiology
 │    ├── Immunology
 │    ├── Serology
 │    ├── Clinical Pathology
 │    ├── Molecular Diagnostics
 │    ├── Histopathology
 │    └── Cytology
```

Do not hardcode these values.

They must be configurable.

---

# 7. TEST CATALOG

Create a laboratory test catalog.

Suggested table:

```text
lab_tests
```

Fields should include, where appropriate:

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

Support test types such as:

- quantitative
- qualitative
- semi-quantitative
- text
- categorical
- calculated
- microbiology
- culture
- pathology

The schema should be extensible.

---

# 8. TEST CATEGORIES

Create configurable categories.

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
- Blood-related
- Other

Do not hardcode categories in business logic.

---

# 9. LAB PANELS / PROFILES

Support test panels.

Examples:

```text
CBC
LFT
RFT
Lipid Profile
Thyroid Profile
Urine R/E
```

Suggested:

```text
lab_panels
lab_panel_items
```

A panel should reference existing tests rather than duplicate test definitions.

Example:

```text
CBC
 ├── Hemoglobin
 ├── RBC
 ├── WBC
 ├── Platelets
 ├── Hematocrit
 └── MCV
```

---

# 10. SPECIMEN MASTER

Create configurable specimen types.

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

Store appropriate configuration such as:

- collection requirements
- stability
- storage temperature
- maximum processing time
- preferred volume
- minimum volume

---

# 11. CONTAINER MASTER

Support specimen containers.

Examples:

- EDTA
- Plain
- Citrate
- Fluoride
- Sterile container
- Urine container
- Swab
- Other

A test should be able to specify the required specimen/container.

---

# 12. SAMPLE REQUIREMENTS

Support:

- fasting required
- patient preparation
- minimum volume
- preferred volume
- collection instructions
- transport requirements
- storage requirements
- maximum processing time

Do not hardcode these rules in controllers.

---

# 13. REFERENCE RANGES

Reference ranges must be configurable.

Do NOT hardcode reference ranges in PHP.

Support ranges based on:

- test
- age
- gender
- pregnancy status where applicable
- specimen type
- method
- laboratory
- unit
- effective date

Example:

```text
Test: Hemoglobin

Male:
13.0–17.0 g/dL

Female:
12.0–15.0 g/dL
```

The actual values must be configurable by authorized users.

---

# 14. RESULT TYPES

Support:

```text
Numeric
Text
Qualitative
Categorical
Calculated
```

For numeric tests support:

```text
numeric_value
unit
reference_range
abnormal_flag
critical_flag
```

For qualitative tests support:

```text
Positive
Negative
Reactive
Non-Reactive
Detected
Not Detected
```

Do not assume all laboratory results are numeric.

---

# 15. CALCULATED RESULTS

Support calculated laboratory results where required.

Examples:

- eGFR
- LDL calculation
- ratios

Calculated formulas must be:

- configurable where appropriate
- versioned
- auditable
- validated
- protected from unauthorized modification

Never execute arbitrary user-provided PHP code as a formula.

---

# 16. CRITICAL VALUES

Implement configurable critical-value rules.

Example:

```text
Result entered
     ↓
Critical rule evaluated
     ↓
Critical value detected
     ↓
Alert created
     ↓
Responsible staff notified
     ↓
Clinician notified
     ↓
Acknowledgement recorded
```

Store:

- result
- critical rule
- detected_at
- detected_by
- notification recipient
- notification method
- notified_at
- acknowledged_by
- acknowledged_at
- acknowledgement notes

Critical results must never silently disappear.

---

# 17. LAB ORDER

Create:

```text
lab_orders
```

Suggested fields:

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

Statuses:

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

Do not allow arbitrary status manipulation.

Implement valid state transitions.

---

# 18. LAB ORDER ITEMS

Create:

```text
lab_order_items
```

Suggested fields:

```text
id
lab_order_id
test_id
panel_id
priority
status
requested_at
collected_at
result_status
completed_at
created_at
updated_at
```

Support individual tests and panels.

---

# 19. LAB REGISTRATION

At laboratory reception:

```text
Verify patient
↓
Verify clinical order
↓
Verify billing/payment policy
↓
Register laboratory order
↓
Generate accession number
↓
Generate specimen/barcode requirements
```

Registration must be concurrency-safe.

Do not generate accession numbers using:

```php
Model::count() + 1
```

Use a proper sequence/number-generation mechanism.

Example:

```text
LAB-2026-00004521
```

The accession number must be:

- unique
- immutable
- searchable

---

# 20. SPECIMEN MANAGEMENT

Create:

```text
lab_specimens
```

Suggested fields:

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

# 21. SPECIMEN COLLECTION

Workflow:

```text
Verify Patient
↓
Verify Order
↓
Collect Sample
↓
Generate/Scan Barcode
↓
Record Collection
↓
Send to Laboratory
```

The system must prevent specimen collection against the wrong patient/order.

Record:

- collector
- date/time
- specimen type
- container
- barcode
- collection location
- remarks

---

# 22. BARCODE MANAGEMENT

Implement barcode support.

Barcode should uniquely identify a specimen/accession.

Support future integration with:

- barcode scanners
- label printers
- automated laboratory systems

Do not tightly couple barcode generation to a specific hardware vendor.

---

# 23. SPECIMEN REJECTION

Support configurable rejection reasons:

- insufficient quantity
- wrong container
- hemolysed
- clotted
- leaked
- contaminated
- delayed transport
- incorrect identification
- stability exceeded
- other

Rejected specimens must retain complete history.

Do not delete rejected specimens.

Support recollection where appropriate.

---

# 24. SPECIMEN RECEIVING

At laboratory receiving:

Verify:

- barcode
- patient
- accession
- specimen type
- container
- quantity
- condition
- collection time
- transport time

Then:

```text
Accept
OR
Reject
```

Record receiving staff and timestamp.

---

# 25. SPECIMEN PROCESSING

Support workflow statuses:

```text
Received
Centrifuged
Aliquoted
Prepared
Assigned
Processing
Completed
```

Design this so future analyzers can integrate with it.

---

# 26. TEST WORKLIST

Create laboratory worklists.

Filter by:

- section
- test
- priority
- status
- date
- technician
- analyzer
- accession number

Examples:

```text
Hematology Worklist
Biochemistry Worklist
Microbiology Worklist
Pending Validation
Critical Results
```

Use server-side pagination.

Never load thousands of records into memory unnecessarily.

---

# 27. LAB RESULT MODEL

Create:

```text
lab_results
```

Suggested fields:

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

Adapt the exact schema to the existing project's conventions.

---

# 28. RESULT ENTRY

Technician workflow:

```text
Open Worklist
↓
Open Test
↓
Verify Specimen
↓
Enter Result
↓
System Evaluates Reference Range
↓
System Evaluates Critical Rule
↓
Save
↓
Technical Validation
```

Prevent unauthorized result modification.

All important result changes must be auditable.

---

# 29. ABNORMAL FLAGS

Support configurable flags such as:

```text
Normal
Low
High
Critical Low
Critical High
Positive
Negative
Abnormal
Panic
N/A
```

Where possible, calculate flags from configured reference ranges rather than allowing arbitrary manual flags.

Manual override, if supported, must require a reason and audit event.

---

# 30. RESULT VALIDATION

Support:

```text
Result Entry
↓
Technical Review
↓
Technical Validation
↓
Pathologist / Authorized Validation
↓
Report
```

Role requirements must be configurable.

Possible roles:

- Laboratory Technician
- Senior Technician
- Pathologist
- Laboratory Manager

A technician must not automatically be allowed to approve results unless the organization's permissions explicitly allow it.

---

# 31. RESULT STATUS

Use controlled states:

```text
Pending
Entered
Technically Validated
Pathologist Validated
Reported
Amended
Cancelled
```

Prevent invalid transitions.

---

# 32. RESULT IMMUTABILITY

Once a result has been reported:

**Never silently overwrite it.**

If a correction is required:

```text
Original Result
↓
Amendment
↓
Reason
↓
New Version
↓
Authorized Approval
↓
Amended Report
```

Retain:

- original value
- amended value
- reason
- user
- timestamp
- previous version
- new version

---

# 33. LAB REPORT

Generate professional laboratory reports.

Report should contain:

### Hospital/Laboratory

- hospital
- branch
- laboratory
- section

### Patient

- patient name
- MRN
- DOB/age
- gender
- relevant identifiers

### Clinical

- encounter
- ordering provider
- accession number

### Test

- test name
- result
- unit
- reference range
- abnormal flag

### Timing

- collection time
- receiving time
- validation time
- report time

### Authorization

- technician
- validator
- pathologist/authorized approver

### Status

```text
Preliminary
Final
Amended
Cancelled
```

Use the existing document/PDF infrastructure if available.

---

# 34. PATIENT EMR INTEGRATION

Laboratory results must become available in:

- Patient 360
- Patient Timeline
- Encounter
- Doctor Workspace
- Laboratory History

Do not copy the same result into multiple unrelated tables.

Use references to the LIS result/report.

Support historical result viewing.

---

# 35. RESULT TRENDING

Provide historical result retrieval.

Example:

```text
Hemoglobin

Date       Result
------------------
Jan 2026   12.5
Mar 2026   13.1
Jun 2026   12.8
Sep 2026   13.4
```

This should be query-based and optimized.

Do not load the patient's complete medical history unnecessarily.

---

# 36. BILLING INTEGRATION

Use Phase 4 Billing.

Flow:

```text
Lab Order
↓
Chargeable Event
↓
Billing Engine
```

Billing remains responsible for:

- price
- discount
- tax
- invoice
- payment
- receipt
- refund
- outstanding balance

LIS may determine:

- test
- panel
- billable event
- configured billing requirement

But it must not own financial transactions.

---

# 37. QUALITY CONTROL FOUNDATION

Implement a foundation for future laboratory QC.

Support data structures for:

- QC materials
- QC runs
- control values
- expected ranges
- instrument
- lot number
- expiry
- QC status

Future enhancements may include:

- Levey-Jennings charts
- Westgard rules
- QC trend analysis

Do not overbuild advanced QC unless required by the existing project scope.

---

# 38. ANALYZER INTEGRATION FOUNDATION

Design for future analyzer integration.

Potential protocols:

- ASTM
- HL7
- vendor APIs
- file exchange
- database exchange

Use an adapter pattern:

```text
Analyzer
   ↓
Analyzer Adapter
   ↓
Normalized Result
   ↓
LIS Result
```

Do not hardcode vendor-specific logic directly into the core result model.

The architecture should allow multiple analyzer vendors.

---

# 39. HL7 / FHIR READINESS

The LIS must be designed for interoperability.

FHIR resources potentially include:

```text
Patient
Encounter
Practitioner
Organization
ServiceRequest
Specimen
Observation
DiagnosticReport
DocumentReference
```

HL7 should be considered for:

- laboratory orders
- laboratory results
- patient demographics
- acknowledgements

A full HL7 engine is not required unless already part of the project.

Do not implement fake interoperability merely for appearance.

---

# 40. NOTIFICATIONS

Use existing notification infrastructure.

Potential events:

```text
Sample Rejected
Recollection Required
Critical Result
Result Validated
Report Ready
Report Amended
```

Notification channels should remain configurable.

---

# 41. LABORATORY DASHBOARD

Create a laboratory dashboard.

Include:

- today's orders
- registered orders
- awaiting collection
- collected samples
- received samples
- rejected samples
- processing
- pending results
- validation pending
- critical results
- completed reports

KPIs:

- test volume
- average turnaround time
- pending tests
- specimen rejection rate
- critical results
- validation turnaround time
- analyzer workload

Clearly define KPI calculations.

---

# 42. REPORTING

Provide operational reports.

Examples:

### Daily Test Volume

By:

- date
- laboratory
- section
- test

### Pending Tests

By:

- test
- section
- priority
- age

### Specimen Collection

By:

- date
- collector
- section

### Specimen Rejection

By:

- reason
- section
- date

### Turnaround Time

Measure:

```text
Collection → Receiving
Receiving → Result
Result → Validation
Collection → Final Report
```

### Critical Results

Include:

- test
- result
- detected time
- notified time
- acknowledgement time

### Management

- tests by department
- tests by provider
- tests by laboratory
- test volume
- laboratory revenue reference

Do not duplicate billing calculations; obtain financial figures from Billing.

---

# 43. RBAC

Use the existing permission system.

Potential roles:

```text
Lab Receptionist
Phlebotomist / Sample Collector
Lab Technician
Senior Technician
Pathologist
Lab Manager
```

Example permissions:

```text
lab.dashboard.view

lab.test.view
lab.test.create
lab.test.update

lab.panel.view
lab.panel.create
lab.panel.update

lab.order.view
lab.order.create
lab.order.cancel

lab.specimen.view
lab.specimen.collect
lab.specimen.receive
lab.specimen.reject
lab.specimen.process

lab.result.view
lab.result.create
lab.result.update
lab.result.validate
lab.result.amend

lab.report.view
lab.report.generate
lab.report.print
lab.report.export
lab.report.approve

lab.critical_result.view
lab.critical_result.notify
lab.critical_result.acknowledge

lab.qc.view
lab.qc.create

lab.analyzer.view
lab.analyzer.configure

lab.settings.manage

lab.audit.view
```

Follow the existing permission naming conventions if they differ.

---

# 44. MULTI-HOSPITAL / MULTI-BRANCH SECURITY

The system supports:

```text
Organization
   ↓
Hospital
   ↓
Branch
   ↓
Department / Laboratory
```

Enforce access scope server-side.

A laboratory user assigned to Hospital A must not be able to access Hospital B laboratory data.

This must be tested explicitly.

Do not rely only on UI filtering.

---

# 45. SECURITY REQUIREMENTS

Implement:

- RBAC
- organization scope
- hospital scope
- branch scope
- laboratory scope where appropriate
- policy authorization
- secure API authorization
- audit logging
- sensitive clinical data protection
- break-glass compatibility
- secure document access
- request validation
- mass-assignment protection
- CSRF protection
- rate limiting where appropriate
- secure exports
- no sensitive information in logs

Never expose:

- SQL queries
- stack traces
- filesystem paths
- credentials
- API secrets
- internal exception details

---

# 46. BREAK-GLASS COMPATIBILITY

If the existing system supports break-glass access:

```text
Normal Access Denied
↓
Break-Glass Reason Required
↓
Temporary Access
↓
Security Event
↓
Audit
```

Do not create a second break-glass system.

Reuse the existing implementation.

---

# 47. AUDIT EVENTS

Generate meaningful audit records for:

- lab order created
- lab order cancelled
- sample collected
- sample received
- sample rejected
- specimen processed
- result entered
- result modified
- result technically validated
- result pathologist validated
- critical result detected
- critical result notified
- critical result acknowledged
- report generated
- report printed
- report exported
- result amended
- report amended
- result cancelled

For clinical data changes capture appropriate before/after information according to the existing audit framework.

---

# 48. API

Use the existing API architecture.

Base:

```text
/api/v1/lab
```

Potential endpoints:

```http
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

Do not blindly implement every endpoint. Follow the existing API design.

Use the project's existing response format.

Expected successful response:

```json
{
    "success": true,
    "message": "Operation successful.",
    "data": {},
    "meta": {}
}
```

Expected validation response:

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "field": [
            "The field is required."
        ]
    }
}
```

---

# 49. DATABASE INTEGRITY

Use:

- foreign keys
- unique constraints
- appropriate indexes
- nullable relationships where required
- decimal types for numeric results
- UTC timestamps
- soft deletes only where appropriate
- immutable identifiers

Important indexes should include appropriate combinations involving:

```text
patient_id
encounter_id
clinical_order_id
lab_order_id
specimen_id
test_id
status
accession_number
barcode
collected_at
reported_at
```

Do not add indexes blindly. Review actual query patterns.

---

# 50. TRANSACTION REQUIREMENTS

Use database transactions for critical operations.

At minimum:

### Laboratory Registration

```text
Create registration
Generate accession
Create specimens
Create order items
Publish chargeable event
```

### Specimen Collection

```text
Verify order
Create/update specimen
Generate barcode
Update order/item status
Create audit
```

### Result Validation

```text
Validate result
Update status
Create audit
Generate critical event if applicable
```

### Report Finalization

```text
Validate report state
Finalize report
Lock relevant result state
Create audit
Publish notification event
```

---

# 51. CONCURRENCY CONTROL

Protect against:

- duplicate accession numbers
- duplicate barcodes
- double registration
- double validation
- double report finalization
- duplicate analyzer imports
- duplicate charge creation

Use:

- database unique constraints
- transactions
- row locks where necessary
- idempotency mechanisms where appropriate

Do not rely solely on application-level checks.

---

# 52. PERFORMANCE

Follow production performance practices:

- eager loading
- pagination
- indexed filters
- server-side searching
- efficient worklist queries
- query scopes
- caching for appropriate master data
- queues for expensive tasks
- optimized report generation

Do not retrieve the entire laboratory history when only the current encounter is required.

---

# 53. EVENTS AND JOBS

Use events/listeners/jobs where appropriate.

Potential events:

```text
LabOrderCreated
LabOrderRegistered
SpecimenCollected
SpecimenReceived
SpecimenRejected
LabResultEntered
LabResultValidated
CriticalResultDetected
CriticalResultAcknowledged
LabReportFinalized
LabReportAmended
```

Potential queued jobs:

```text
GenerateLabReport
SendCriticalResultNotification
SendReportReadyNotification
ProcessAnalyzerResult
GenerateLabStatistics
```

Do not create unnecessary asynchronous complexity.

Follow existing project conventions.

---

# 54. UI REQUIREMENTS

Use the existing UI framework.

Suggested screens:

### Laboratory

- Dashboard
- Laboratory List
- Laboratory Sections
- Test Catalog
- Test Categories
- Panels
- Specimen Types
- Containers
- Reference Ranges
- Critical Value Rules

### Orders

- Lab Orders
- Order Details
- Registration
- Collection Queue
- Receiving Queue

### Specimens

- Specimen List
- Specimen Details
- Rejection
- Processing

### Worklists

- Section Worklists
- Technician Worklist
- Pending Validation
- Critical Results

### Results

- Result Entry
- Result Review
- Result Validation
- Result History
- Result Amendment

### Reports

- Report Preview
- Final Report
- Amended Report
- Report History

### Administration

- Analyzer Configuration
- QC
- Lab Settings
- Audit

Use reusable components rather than duplicating Blade markup.

---

# 55. PATIENT IDENTIFICATION SAFETY

Laboratory workflow is highly sensitive to patient identification.

At collection and receiving, display enough patient information to safely verify identity while following privacy requirements.

Use multiple identifiers where the existing patient policy supports it.

Never rely solely on an internal database ID being invisible to the user.

---

# 56. CLINICAL SAFETY

The implementation must prioritize:

1. Correct patient
2. Correct order
3. Correct specimen
4. Correct test
5. Correct result
6. Correct validation
7. Correct report
8. Complete audit trail

The system must make it difficult to accidentally associate a specimen/result with the wrong patient.

---

# 57. MODULE STRUCTURE

Follow the existing project module architecture.

Conceptually, LIS may contain:

```text
Laboratory/
├── Models/
├── Services/
├── Actions/
├── Policies/
├── Requests/
├── Controllers/
├── Events/
├── Listeners/
├── Jobs/
├── Notifications/
├── Reports/
├── API/
├── Views/
├── Routes/
├── Database/
└── Tests/
```

Do not create this structure if the project already uses a different established structure. Adapt to the existing architecture.

---

# 58. TESTING REQUIREMENTS

Implement:

## Unit Tests

Test:

- reference-range evaluation
- abnormal flag calculation
- critical-value detection
- state transitions
- accession generation
- barcode generation
- calculated result logic
- TAT calculation

## Feature Tests

Test:

- create laboratory order
- register order
- collect specimen
- receive specimen
- reject specimen
- process specimen
- enter result
- validate result
- finalize report
- amend result
- acknowledge critical result

## Integration Tests

Test:

```text
Clinical Order → LIS
LIS → Billing
LIS → Patient EMR
LIS → Notification
LIS → Audit
```

## Security Tests

Explicitly test:

```text
Hospital A Lab User
        ↓
Cannot access
        ↓
Hospital B Lab Data
```

Also test:

- unauthorized result validation
- unauthorized report approval
- unauthorized result amendment
- unauthorized export
- API scope bypass
- direct URL access
- broken object-level authorization
- permission escalation

---

# 59. NEGATIVE TESTING

Test invalid workflows.

Examples:

- collect specimen for cancelled order
- receive already rejected specimen
- validate already cancelled result
- amend unauthorized result
- finalize incomplete report
- duplicate accession
- duplicate barcode
- wrong hospital
- wrong branch
- wrong laboratory
- missing specimen
- missing result
- invalid state transition

The system should reject these safely.

---

# 60. DATA INTEGRITY RULES

Never:

- silently overwrite reported results
- delete clinical results without controlled policy
- bypass validation
- bypass audit
- bypass hospital scope
- create duplicate patient records
- duplicate invoice logic
- duplicate encounter logic

Clinical records must remain traceable.

---

# 61. IMPLEMENTATION ORDER

Implement in this order unless repository dependencies require adjustment:

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
- Users
- Roles
- Permissions
- Audit
- Notifications
- Files
- API

### Step 3

Create LIS migrations.

### Step 4

Implement laboratory master data.

### Step 5

Implement test catalog.

### Step 6

Implement panels.

### Step 7

Implement specimen/container configuration.

### Step 8

Implement reference ranges.

### Step 9

Implement critical-value rules.

### Step 10

Implement laboratory orders.

### Step 11

Implement registration.

### Step 12

Implement accession generation.

### Step 13

Implement specimen collection.

### Step 14

Implement barcode management.

### Step 15

Implement receiving/rejection.

### Step 16

Implement processing.

### Step 17

Implement worklists.

### Step 18

Implement result entry.

### Step 19

Implement technical validation.

### Step 20

Implement pathologist/authorized validation.

### Step 21

Implement critical-result workflow.

### Step 22

Implement report generation/finalization.

### Step 23

Implement result amendment.

### Step 24

Implement Patient/EMR integration.

### Step 25

Implement Billing integration.

### Step 26

Implement Notifications.

### Step 27

Implement Audit.

### Step 28

Implement dashboard/reporting.

### Step 29

Implement API.

### Step 30

Implement analyzer integration foundation.

### Step 31

Implement FHIR/HL7 mapping foundation.

### Step 32

Implement tests.

### Step 33

Perform security review.

### Step 34

Perform performance review.

### Step 35

Update documentation.

---

# 62. DEVELOPMENT PRINCIPLES

Follow these principles:

### Thin Controllers

Controllers should orchestrate requests, not contain business logic.

### Services/Actions

Put meaningful business operations into Services/Actions following the existing architecture.

### Form Requests

Use Form Requests for validation.

### Policies

Authorization must be implemented through Policies/Gates and existing scope mechanisms.

### Transactions

Use transactions around critical workflows.

### Events

Use events where they provide meaningful decoupling.

### Jobs

Use queued jobs for expensive/asynchronous work.

### DTOs

Use existing DTO conventions if the project already has them.

### Enums

Use enums or existing status/value-object conventions where appropriate.

### Repository Pattern

Do not introduce repositories merely because they are fashionable. Follow the project's established architecture.

---

# 63. DO NOT OVERENGINEER

Do not implement every future LIS feature immediately.

The following should remain foundations unless explicitly required:

- advanced analyzer integrations
- full HL7 engine
- advanced FHIR server
- advanced QC statistics
- AI diagnosis
- automated clinical interpretation
- machine-learning models

Build clean extension points instead.

---

# 64. AI / CLINICAL INTELLIGENCE READINESS

Prepare the architecture for future AI capabilities.

Possible future features:

- abnormal result summarization
- trend detection
- critical-result prioritization
- laboratory workload prediction
- TAT prediction
- quality anomaly detection

However:

**Do not implement AI clinical decision-making in Phase 5.**

AI must never silently:

- diagnose
- alter results
- alter reference ranges
- change physician orders
- prescribe medication
- finalize laboratory reports

Any future AI capability must remain clinician-controlled and auditable.

---

# 65. FHIR RESOURCE MAPPING

Prepare mapping for:

```text
Patient
Encounter
Practitioner
Organization
ServiceRequest
Specimen
Observation
DiagnosticReport
DocumentReference
```

Keep mapping isolated so it can evolve without contaminating LIS core business logic.

---

# 66. API SECURITY

Every API endpoint must enforce:

- authentication
- authorization
- organization scope
- hospital scope
- branch scope where applicable
- resource-level authorization

Do not trust:

```text
hospital_id
organization_id
branch_id
patient_id
```

supplied by the client.

Resolve and verify them server-side.

---

# 67. FILE AND REPORT SECURITY

Laboratory reports may contain sensitive clinical information.

Use the existing private file/document infrastructure.

Do not expose reports through publicly guessable URLs.

Authorization must be checked before:

- viewing
- downloading
- printing
- exporting

---

# 68. AUDIT VS ACTIVITY LOG

Use the existing distinction.

Clinical/security audit:

```text
Result modified
Result validated
Report finalized
Critical result acknowledged
```

Activity log:

```text
User opened laboratory dashboard
User searched orders
```

Do not use a generic activity log as a replacement for clinical audit.

---

# 69. DEFINITION OF DONE

Phase 5 is complete only when:

- Laboratory master data works
- Test catalog works
- Panels work
- Specimen configuration works
- Reference ranges work
- Critical-value rules work
- Lab orders work
- Registration works
- Accession generation works
- Specimen collection works
- Barcode management works
- Specimen receiving works
- Rejection works
- Processing works
- Worklists work
- Result entry works
- Technical validation works
- Authorized validation works
- Critical-result workflow works
- Laboratory reports work
- Result history works
- Result amendment works
- Patient EMR integration works
- Billing integration works
- Notifications work
- Audit works
- RBAC works
- Hospital/branch access scope works
- API works
- Dashboard works
- Operational reporting works
- Analyzer integration foundation exists
- FHIR/HL7 mapping foundation exists
- Automated tests exist
- Security tests exist
- Documentation exists
- Existing Phase 0–4 functionality has no regression

---

# 70. IMPLEMENTATION REPORT

At the end of implementation, provide a structured report containing:

## 1. Summary

What was implemented.

## 2. Database

List:

- migrations
- tables
- relationships
- indexes
- constraints

## 3. Models

List all new/updated models.

## 4. Services / Actions

List business operations.

## 5. Controllers

List web/API controllers.

## 6. Routes

List web and API routes.

## 7. Permissions

List all permissions.

## 8. Events / Listeners

List all events and listeners.

## 9. Jobs

List queued jobs.

## 10. Notifications

List notifications.

## 11. Phase 3 Integration

Explain Clinical Order and EMR integration.

## 12. Phase 4 Integration

Explain charge/billing integration.

## 13. Security

Explain:

- RBAC
- scope enforcement
- authorization
- audit
- break-glass compatibility

## 14. Testing

Report:

- tests created
- tests actually executed
- results actually observed

**Never claim tests passed unless you actually ran them.**

## 15. Deployment

List:

- migrations
- environment requirements
- queue requirements
- scheduler requirements
- storage requirements
- configuration changes

## 16. Deferred Features

Clearly identify features intentionally left for future phases.

---

# 71. FINAL IMPLEMENTATION RULES

Before modifying code:

1. Inspect the repository.
2. Understand the existing architecture.
3. Inspect Phase 0–4 implementations.
4. Reuse existing infrastructure.
5. Identify dependencies.
6. Plan migrations.
7. Implement incrementally.
8. Run relevant tests.
9. Fix failures.
10. Review security.
11. Review database integrity.
12. Review performance.
13. Document the implementation.

Do not make assumptions about existing classes or tables without inspecting the repository.

Do not create duplicate infrastructure.

Do not silently change existing Phase 0–4 behavior.

If an existing implementation differs from the assumptions in this prompt, **adapt the LIS implementation to the actual project architecture**.

The final result must be a production-oriented LIS module suitable for integration into a multi-hospital Hospital ERP.

The LIS must be treated as a **clinical laboratory information system**, not as a simple CRUD module.

The highest priorities are:

1. Patient identification
2. Specimen traceability
3. Result accuracy
4. Validation
5. Critical-result communication
6. Result immutability
7. Auditability
8. Security
9. Interoperability
10. Maintainability

Implement accordingly.