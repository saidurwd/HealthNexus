
# Phase 8 — IPD / Admission / Bed Management
## Module Specification & AI Implementation Prompt

## 1. Module Overview

Build **Phase 8 — IPD / Admission / Bed Management** as a production-grade module within the existing Hospital Management System.

The module manages the complete inpatient lifecycle:

> **Admission Request → Admission Approval → Patient Admission → Bed Allocation → Inpatient Stay → Transfers → Clinical Care → Procedures/Orders → Discharge Planning → Discharge → Bed Release → Billing Finalization**

The module must integrate with:

- Phase 0 — Foundation & Platform
- Phase 1 — Patient / MPI
- Phase 2 — Appointment & Scheduling
- Phase 3 — OPD / Clinical Encounter / EMR
- Phase 4 — Billing & Revenue
- Phase 5 — LIS
- Phase 6 — Radiology / PACS
- Phase 7 — Pharmacy & Medication Management
- Future Phase 9 — Nursing
- Future Phase 10 — OT / Surgery
- Future Phase 11 — ICU
- Future Phase 12 — Emergency
- Future Phase 14 — Insurance
- Future Phase 15 — Inventory / Procurement

### Critical architectural rule

The existing modular architecture is **already implemented**.

AI must:

- inspect the existing repository;
- understand the existing module structure;
- reuse existing infrastructure;
- extend existing services and abstractions;
- reuse existing Patient, Encounter, Billing, Prescription, User, Permission, Audit, Notification and Workflow functionality;
- **NOT create another modular framework**;
- **NOT duplicate existing business domains**.

This module must be implemented as an extension of the existing HMS architecture.

---

# 2. Primary Objectives

The IPD module must provide:

1. Inpatient admission management
2. Admission request and approval
3. Bed/room/ward management
4. Real-time bed availability
5. Bed reservation
6. Bed allocation
7. Bed transfer
8. Room transfer
9. Ward transfer
10. Isolation bed management
11. Patient movement tracking
12. Inpatient encounter management
13. Attending physician assignment
14. Admission diagnosis
15. Expected discharge date
16. Discharge planning
17. Discharge workflow
18. Bed release
19. Readmission handling
20. Leave/pass management
21. Death/disposition workflow foundation
22. IPD charge generation
23. Billing integration
24. Clinical/Lab/Radiology/Pharmacy integration
25. Nursing integration foundation
26. OT/ICU/Emergency integration foundation
27. Patient 360 integration
28. Inpatient dashboard
29. Bed occupancy reporting
30. Admission/discharge/transfer reporting
31. Audit and security
32. Multi-hospital/branch support
33. API support
34. FHIR readiness

---

# 3. IPD Domain Model

The basic hierarchy should be:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
Building
    ↓
Floor
    ↓
Ward
    ↓
Room
    ↓
Bed
```

Clinical relationship:

```text
Patient
   ↓
Admission
   ↓
Inpatient Encounter
   ↓
Ward / Room / Bed
   ↓
Bed Movements
   ↓
Clinical Care
   ↓
Discharge
```

Financial relationship:

```text
Admission
   ↓
Chargeable Events
   ↓
Billing Engine
   ↓
Invoice
   ↓
Payment
```

Do not create a separate IPD invoice/payment engine.

---

# 4. Admission Lifecycle

Recommended admission lifecycle:

```text
Requested
    ↓
Pending Approval
    ↓
Approved
    ↓
Reserved
    ↓
Admitted
    ↓
Active
    ↓
Transfer Requested
    ↓
Transferred
    ↓
Discharge Planned
    ↓
Discharge Pending
    ↓
Discharged
    ↓
Closed
```

Additional terminal states may include:

- Cancelled
- Rejected
- Deceased
- Left Against Medical Advice
- Absconded
- Transferred to Another Facility

The exact status model should follow existing project conventions.

---

# 5. Admission Types

Support configurable admission types such as:

- Elective
- Emergency
- Urgent
- Referral
- Day Care
- Observation
- Maternity
- Surgical
- Medical
- ICU
- Step-down
- Isolation
- Rehabilitation

Do not hardcode hospital-specific categories.

Use configurable master data where appropriate.

---

# 6. Admission Sources

Examples:

- OPD
- Emergency
- Referral
- External Hospital
- Direct
- Appointment
- Transfer
- Readmission
- Physician Referral

---

# 7. Admission Request

An admission request should capture:

```text
Patient
Encounter
Requesting Provider
Department
Specialty
Admission Type
Reason
Provisional Diagnosis
Priority
Expected Length of Stay
Expected Admission Date
Expected Discharge Date
Required Bed Type
Isolation Requirement
Special Requirements
Notes
Status
Requested By
Approved By
```

Admission requests may originate from:

- OPD encounter
- Emergency encounter
- Doctor
- Referral
- Direct registration

---

# 8. Admission Approval

Where hospital policy requires approval, support:

```text
Admission Request
        ↓
Approval Workflow
        ↓
Approved / Rejected
```

Approval should support:

- approver
- approval timestamp
- decision
- comments
- workflow step
- audit trail

Use the existing Phase 0 Workflow/Approval engine.

Do not build a second approval framework.

---

# 9. Admission Record

Core conceptual fields:

```text
id
organization_id
hospital_id
branch_id
admission_number
patient_id
source_encounter_id
admission_request_id
inpatient_encounter_id
admission_type
admission_source
admission_date
admission_time
attending_provider_id
admitting_provider_id
department_id
specialty_id
provisional_diagnosis
reason_for_admission
priority
expected_discharge_date
actual_discharge_date
status
discharge_disposition
created_by
approved_by
admitted_by
discharged_by
created_at
updated_at
```

Admission number must be:

- unique;
- immutable;
- concurrency safe;
- never generated using `COUNT()`.

Example:

```text
ADM-2026-00001234
```

---

# 10. Inpatient Encounter

An admission should create or link to an inpatient clinical encounter.

Do not create a competing clinical encounter system.

Reuse Phase 3.

Example:

```text
Patient
   ↓
Admission
   ↓
Encounter
   ↓
IPD Clinical Context
```

The inpatient encounter should provide:

- admission context
- provider context
- department
- specialty
- diagnoses
- orders
- prescriptions
- clinical notes
- procedures
- referrals
- clinical timeline

---

# 11. Bed Management

Bed management is a core component of Phase 8.

Bed hierarchy:

```text
Hospital
 → Building
   → Floor
     → Ward
       → Room
         → Bed
```

A bed should support configurable attributes:

```text
Bed Number
Bed Type
Ward
Room
Gender
Capacity
Status
Isolation
ICU Capability
Ventilator Capability
Oxygen Availability
Monitor Availability
VIP
Pediatric
Maternity
Bariatric
Accessible
```

Do not hardcode these categories.

---

# 12. Bed Status

Recommended bed states:

```text
Available
Reserved
Occupied
Cleaning
Blocked
Maintenance
Isolation
Out of Service
Pending Transfer
Pending Discharge
```

Bed status changes must be auditable.

Never directly modify a bed's status without recording the corresponding movement/state transition.

---

# 13. Bed Allocation

Allocation workflow:

```text
Admission
   ↓
Find Available Bed
   ↓
Reserve Bed
   ↓
Verify Patient
   ↓
Allocate Bed
   ↓
Occupy Bed
```

Allocation must validate:

- bed availability;
- patient eligibility;
- gender restrictions;
- isolation requirements;
- ward restrictions;
- bed type;
- hospital/branch scope;
- active admission status.

Concurrency must prevent two users from allocating the same bed simultaneously.

---

# 14. Bed Reservation

Support temporary reservations.

Example:

```text
Reserved
  ↓
Admission Completed
  ↓
Occupied
```

Reservations must have:

- reservation date/time;
- expiry;
- patient;
- admission;
- requested bed;
- requester;
- reason;
- status.

Expired reservations should not continue blocking beds.

---

# 15. Bed Transfer

Support:

- bed-to-bed transfer;
- room-to-room transfer;
- ward-to-ward transfer;
- department transfer;
- branch/hospital transfer foundation.

Transfer workflow:

```text
Transfer Request
      ↓
Approval if required
      ↓
Destination Bed Validation
      ↓
Destination Bed Reservation
      ↓
Patient Movement
      ↓
Source Bed Release
      ↓
Destination Bed Occupied
```

A transfer must be atomic.

Never leave the patient simultaneously occupying two active beds.

---

# 16. Bed Movement Ledger

Maintain an immutable movement history.

Example:

```text
Patient
Admission
Source Bed
Destination Bed
Movement Type
Requested At
Moved At
Reason
Requested By
Approved By
Completed By
```

Movement types:

- Admission
- Transfer
- Temporary Leave
- Return
- Discharge
- Bed Change
- Ward Change
- Room Change
- ICU Transfer
- Isolation Transfer

---

# 17. Patient Location

The system must always be able to determine:

> **Where is this inpatient currently located?**

Example:

```text
Hospital A
 → Main Building
   → 3rd Floor
     → Medical Ward
       → Room 305
         → Bed B
```

Current location should be derived from the active movement/allocation record rather than duplicated uncontrolled fields.

---

# 18. Ward Management

Ward configuration should include:

```text
Ward Name
Code
Hospital
Branch
Floor
Department
Specialty
Gender Policy
Capacity
Isolation Capability
Active Status
```

Ward dashboard:

- total beds
- occupied
- available
- reserved
- cleaning
- maintenance
- isolation
- occupancy %
- pending admissions
- pending transfers
- pending discharges

---

# 19. Room Management

Room attributes:

```text
Room Number
Room Type
Ward
Floor
Capacity
Gender
Isolation
VIP
Rate Category
Status
```

Room types may include:

- General
- Semi-private
- Private
- Deluxe
- VIP
- Isolation
- ICU
- HDU

These must remain configurable.

---

# 20. Isolation Management

Support isolation requirements such as:

- Contact
- Droplet
- Airborne
- Protective
- Infection-control isolation
- Custom

Isolation requirements should be configurable and should not be interpreted as clinical advice by the software.

When an admission requires isolation:

```text
Admission Requirement
        ↓
Eligible Isolation Bed
        ↓
Bed Allocation
        ↓
Isolation Flag
```

The system should prevent inappropriate bed allocation according to configured hospital rules.

---

# 21. Attending Physician

Support:

- admitting physician
- attending physician
- primary consultant
- consulting specialist
- on-call provider

Do not duplicate Practitioner/User models.

Reuse existing provider identity and authorization infrastructure.

Provider assignment must support history.

Do not overwrite previous provider assignments without recording history.

---

# 22. Admission Diagnosis

Admission may capture:

- provisional diagnosis
- admission diagnosis
- principal diagnosis
- secondary diagnosis

Use existing Phase 3 diagnosis structures and coding infrastructure.

Do not create another diagnosis master.

Final coding may be updated during discharge according to hospital policy.

---

# 23. Inpatient Clinical Integration

Phase 8 should integrate with Phase 3 Clinical Encounter.

Examples:

```text
IPD Admission
     ↓
Clinical Encounter
     ↓
Diagnosis
     ↓
Clinical Orders
     ↓
Lab / Radiology
     ↓
Prescription
     ↓
Pharmacy
```

The IPD module must not implement:

- laboratory testing;
- radiology reporting;
- pharmacy dispensing;
- clinical prescription authoring;
- nursing MAR.

Those belong to their respective modules.

---

# 24. Laboratory Integration

A doctor may create a clinical order during an inpatient encounter:

```text
IPD Encounter
   ↓
Clinical Order
   ↓
Lab Order
   ↓
LIS
   ↓
Result
   ↓
EMR
```

Phase 8 only needs to consume/display relevant status and results through existing interfaces.

Do not duplicate LIS functionality.

---

# 25. Radiology Integration

Workflow:

```text
IPD Encounter
   ↓
Clinical/Radiology Order
   ↓
RIS
   ↓
PACS
   ↓
Radiology Report
   ↓
EMR
```

IPD should display relevant examination/report status.

Do not implement DICOM/PACS logic inside IPD.

---

# 26. Pharmacy Integration

Workflow:

```text
IPD Clinical Encounter
       ↓
Prescription
       ↓
Pharmacy
       ↓
Dispensing
       ↓
Medication Administration
```

Phase 8 must integrate with Phase 7.

Do not create another medication or dispensing engine.

---

# 27. Nursing Integration Foundation

Phase 9 will own full nursing workflows.

Phase 8 should expose:

- current admission
- current location
- patient status
- diagnosis
- care context
- attending provider
- orders
- medication information
- transfer information
- discharge plan

Future relationship:

```text
IPD Admission
      ↓
Nursing
      ↓
Vitals
      ↓
Nursing Assessment
      ↓
Care Plan
      ↓
MAR
      ↓
Nursing Notes
```

Do not implement the full Nursing module in Phase 8.

---

# 28. Admission Charge Integration

Admission may create chargeable events such as:

- admission fee;
- registration fee;
- bed charge;
- room charge;
- service charge;
- transfer charge where applicable.

However:

> **Billing owns all financial transactions.**

IPD should publish:

```text
Chargeable Event
```

to Phase 4.

Example:

```text
IPD Bed Occupancy
       ↓
Chargeable Event
       ↓
Billing Engine
       ↓
Invoice
```

Never create:

- `ipd_invoices`
- `ipd_payments`
- `ipd_receipts`

if those duplicate the central Billing module.

---

# 29. Bed/Room Charges

Support configurable billing rules:

- per day;
- per night;
- calendar day;
- admission/discharge day;
- hourly;
- room category;
- bed category.

Billing configuration must determine the actual financial calculation.

IPD should provide occupancy events and service references.

---

# 30. Daily Bed Charge Generation

A scheduler may identify active occupancy and publish chargeable events.

Example:

```text
Active Bed Occupancy
       ↓
Daily Charge Job
       ↓
Chargeable Event
       ↓
Billing
```

Important:

- prevent duplicate charges;
- use idempotency;
- handle transfers;
- handle same-day admissions;
- handle same-day discharge;
- handle temporary leave;
- support corrections through proper billing mechanisms.

Do not directly insert duplicate invoice lines.

---

# 31. Discharge Management

Discharge lifecycle:

```text
Active
 ↓
Discharge Planning
 ↓
Discharge Requested
 ↓
Clinical Clearance
 ↓
Billing Clearance
 ↓
Pharmacy/Other Clearance
 ↓
Discharge Approved
 ↓
Discharged
 ↓
Bed Released
```

The exact approval requirements must be configurable.

---

# 32. Discharge Request

Capture:

```text
Admission
Patient
Discharge Type
Planned Date
Reason
Discharge Diagnosis
Disposition
Instructions
Follow-up Required
Follow-up Provider
Follow-up Date
Requested By
```

Discharge types:

- Routine
- Transfer
- Referral
- LAMA
- Absconded
- Deceased
- Other configured types

---

# 33. Discharge Disposition

Examples:

- Home
- Another Hospital
- Rehabilitation
- Nursing Facility
- Transfer to ICU
- Transfer to Ward
- Deceased
- Other

Use configurable master data.

---

# 34. Discharge Summary Integration

The discharge summary should be part of the clinical record.

Phase 8 may provide the admission/discharge context, while clinical documentation remains under the EMR/clinical architecture.

Possible summary sections:

- admission details
- discharge details
- principal diagnosis
- secondary diagnoses
- procedures
- significant investigations
- hospital course
- medication information
- follow-up
- instructions

Do not create a competing document management framework.

Use existing File/Document infrastructure.

---

# 35. Bed Release

When discharge is completed:

```text
Patient Discharged
       ↓
Active Bed Allocation Closed
       ↓
Bed → Cleaning / Available
```

Hospital policy may require:

```text
Occupied
   ↓
Cleaning
   ↓
Available
```

Do not immediately make the bed available if cleaning is mandatory.

---

# 36. Readmission

A patient may be admitted multiple times.

Never overwrite previous admissions.

Example:

```text
Patient
 ├── Admission #1
 ├── Admission #2
 ├── Admission #3
 └── Admission #4
```

Each admission must retain independent:

- admission number;
- encounter;
- bed history;
- diagnosis history;
- discharge;
- billing references.

---

# 37. Temporary Leave / Pass

Support configurable patient leave workflows.

Example:

```text
Active
 ↓
Leave Requested
 ↓
Approved
 ↓
On Leave
 ↓
Returned
```

Track:

- leave start;
- expected return;
- actual return;
- reason;
- approval;
- responsible staff.

Bed handling during leave must be configurable because hospitals differ in whether a bed remains reserved/occupied for billing and operational purposes.

---

# 38. Patient Movement

Maintain a complete patient movement timeline:

```text
Admission
→ Ward A / Room 101 / Bed 1
→ Ward A / Room 103 / Bed 2
→ ICU / Bed ICU-04
→ Ward B / Room 210 / Bed 1
→ Discharge
```

This is essential for:

- clinical history;
- infection control;
- billing;
- audit;
- reporting;
- operational management.

---

# 39. Bed Occupancy Calculation

Dashboard metrics:

```text
Total Beds
Occupied Beds
Available Beds
Reserved Beds
Blocked Beds
Maintenance Beds
Isolation Beds
Cleaning Beds
Occupancy %
```

Example:

```text
Occupancy % =
Occupied Beds / Operational Beds × 100
```

Exclude non-operational beds according to configured rules.

Do not calculate occupancy from raw historical rows without considering current active state.

---

# 40. Bed Availability Search

Provide filters:

- hospital
- branch
- building
- floor
- ward
- room
- bed type
- gender
- isolation
- specialty
- status

Example:

```text
Find:
Hospital = Main Hospital
Ward = Medicine
Gender = Male
Bed Type = General
Status = Available
```

Search must use server-side authorization scope.

---

# 41. Bed Board

Create a real-time bed board.

Example:

```text
Medical Ward

Room 301
 ├ Bed A — Occupied
 └ Bed B — Available

Room 302
 ├ Bed A — Cleaning
 └ Bed B — Occupied

Room 303
 ├ Bed A — Reserved
 └ Bed B — Maintenance
```

Color coding may be implemented in UI, but status must remain represented by semantic values rather than color alone.

---

# 42. Admission Dashboard

Dashboard should include:

### Today's statistics

- admissions
- discharges
- transfers
- current inpatients
- pending admissions
- pending transfers
- pending discharges

### Bed statistics

- total
- available
- occupied
- reserved
- cleaning
- maintenance
- isolation
- occupancy rate

### Operational alerts

- pending bed allocation
- delayed discharge
- expired reservation
- bed conflict
- missing discharge completion
- high occupancy
- isolation bed shortage

---

# 43. Delayed Discharge

Support configurable indicators for patients whose expected discharge date has passed.

Example:

```text
Expected Discharge < Today
AND
Admission Status = Active
```

Display:

- patient;
- MRN;
- admission number;
- ward;
- bed;
- attending physician;
- expected discharge date;
- days overdue.

This is an operational indicator, not an automatic clinical judgment.

---

# 44. Admission Cancellation

An admission request/reservation can be cancelled before actual admission.

Once a patient is formally admitted:

> Do not delete the admission.

Use cancellation/void/amendment workflows according to lifecycle stage.

---

# 45. Bed Blocking

Beds may be blocked for:

- maintenance;
- cleaning;
- infection control;
- renovation;
- equipment issue;
- administrative reasons.

Track:

```text
Reason
Start
Expected End
Actual End
Requested By
Approved By
```

---

# 46. Bed Maintenance

Do not implement a full asset maintenance module.

Only provide bed availability blocking.

Future integration with enterprise asset management may reference the bed/equipment asset.

---

# 47. Database Design

Follow the existing project's database conventions.

Potential tables include:

```text
ipd_admission_requests
ipd_admissions
ipd_admission_status_history

ipd_buildings
ipd_floors
ipd_wards
ipd_rooms
ipd_beds

ipd_bed_reservations
ipd_bed_allocations
ipd_bed_movements
ipd_bed_status_history

ipd_provider_assignments

ipd_discharge_requests
ipd_discharge_plans
ipd_discharge_records

ipd_patient_leaves

ipd_bed_blocks

ipd_chargeable_events
```

The exact table structure must be adapted to the existing architecture.

Do not blindly create all tables if equivalent abstractions already exist.

---

# 48. Suggested `ipd_beds`

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
building_id
floor_id
ward_id
room_id
bed_code
bed_name
bed_type_id
gender_type
isolation_capable
status
is_active
created_at
updated_at
```

Use foreign keys wherever appropriate.

---

# 49. Suggested `ipd_admissions`

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
admission_number
patient_id
admission_request_id
encounter_id
admission_type_id
admission_source_id
department_id
specialty_id
admitting_provider_id
attending_provider_id
admission_date
expected_discharge_date
actual_discharge_date
priority
status
discharge_disposition_id
created_by
approved_by
admitted_by
discharged_by
created_at
updated_at
```

---

# 50. Suggested `ipd_bed_allocations`

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
admission_id
patient_id
bed_id
allocated_at
released_at
status
allocation_type
reason
allocated_by
released_by
created_at
updated_at
```

Enforce one active allocation per admission/patient according to business rules.

---

# 51. Suggested `ipd_bed_movements`

```text
id
organization_id
hospital_id
branch_id
admission_id
patient_id
from_bed_id
to_bed_id
movement_type
requested_at
approved_at
moved_at
reason
status
requested_by
approved_by
completed_by
created_at
updated_at
```

Never delete completed movement history.

---

# 52. Database Integrity

Use:

- foreign keys;
- indexes;
- unique constraints;
- check constraints where supported;
- transaction boundaries;
- optimistic/pessimistic locking where required.

Important uniqueness examples:

```text
hospital + bed_code
hospital + admission_number
organization + admission_number
```

Adapt uniqueness to the project's actual multi-tenant model.

---

# 53. Concurrency Safety

Bed allocation is a high-risk concurrency operation.

Example:

```text
User A → selects Bed 101
User B → selects Bed 101
```

Only one should successfully allocate it.

Use database transactions and appropriate locking.

Example conceptual flow:

```text
BEGIN TRANSACTION

Lock bed row

Verify status = Available

Create allocation

Update bed state

Create movement record

Commit
```

Never rely only on frontend availability checks.

---

# 54. Double Allocation Prevention

Tests must prove:

```text
Two simultaneous admission requests
        ↓
Same bed
        ↓
Exactly one succeeds
        ↓
Other request receives controlled conflict
```

---

# 55. Billing Integration

Integration contract:

```text
IPD
 ↓
Chargeable Event
 ↓
Billing
```

Examples:

```text
Admission Charge
Bed Charge
Room Charge
Service Charge
Transfer Charge
Other Configured IPD Charge
```

Billing remains responsible for:

- price;
- discount;
- tax;
- invoice;
- payment;
- receipt;
- refund;
- credit;
- insurance/corporate billing.

---

# 56. Insurance Integration Foundation

Prepare references for future Phase 14:

```text
Admission
Patient
Payer
Insurance Policy
Authorization
Coverage
Chargeable Events
Invoice
Claim
```

Do not implement the complete claims engine in Phase 8.

---

# 57. Pharmacy Integration

IPD should be able to identify medications associated with an inpatient.

Display through existing Phase 7 APIs/services:

```text
Current Prescriptions
Dispensed Medications
Medication History
Allergies
Medication Alerts
```

Do not duplicate pharmacy records.

---

# 58. Patient 360 Integration

Patient 360 should show:

```text
Current Admission
Admission History
Current Ward/Room/Bed
Previous Admissions
Transfers
Discharges
Clinical Encounters
Lab
Radiology
Medications
Billing
Insurance
Documents
```

Patient 360 remains a shared platform feature, not an IPD-owned duplicate.

---

# 59. Notifications

Use the existing notification framework.

Potential notifications:

### Admission

- admission approved;
- bed allocated;
- admission cancelled.

### Transfer

- transfer requested;
- transfer approved;
- transfer completed.

### Discharge

- discharge requested;
- discharge pending;
- discharge approved;
- discharge completed.

### Bed management

- bed reservation expiring;
- bed blocked;
- high occupancy;
- pending allocation.

---

# 60. Events

Suggested domain events:

```text
AdmissionRequested
AdmissionApproved
AdmissionRejected
AdmissionCancelled

PatientAdmitted
BedReserved
BedAllocated
BedReleased

BedTransferRequested
BedTransferApproved
PatientTransferred

PatientLeaveStarted
PatientReturnedFromLeave

DischargeRequested
DischargeApproved
PatientDischarged

BedBlocked
BedUnblocked

AdmissionChargeableEventCreated
BedChargeableEventCreated
```

Events must follow the project's existing event conventions.

---

# 61. Jobs

Potential queued/scheduled jobs:

```text
ExpireBedReservations
GenerateBedOccupancyCharges
DetectDelayedDischarges
GenerateIPDOccupancyReport
SendAdmissionNotifications
SendDischargeNotifications
ReconcileBedStates
```

Jobs must be:

- idempotent;
- retry-safe;
- observable;
- logged;
- failure-tolerant.

---

# 62. Scheduled Bed Reconciliation

A reconciliation process may identify inconsistencies such as:

```text
Bed marked Available
BUT
Active Allocation exists
```

or:

```text
Bed marked Occupied
BUT
No active allocation exists
```

The system should report discrepancies rather than silently modifying clinical/operational records.

Automatic repair should require explicit policy and audit.

---

# 63. Permissions

Suggested permissions:

```text
ipd.dashboard.view

ipd.admission.view
ipd.admission.create
ipd.admission.update
ipd.admission.approve
ipd.admission.cancel

ipd.bed.view
ipd.bed.create
ipd.bed.update
ipd.bed.block
ipd.bed.unblock

ipd.bed.reserve
ipd.bed.allocate
ipd.bed.release

ipd.transfer.view
ipd.transfer.create
ipd.transfer.approve
ipd.transfer.complete
ipd.transfer.cancel

ipd.discharge.view
ipd.discharge.create
ipd.discharge.approve
ipd.discharge.complete

ipd.leave.view
ipd.leave.create
ipd.leave.approve
ipd.leave.complete

ipd.reports.view
ipd.reports.export

ipd.settings.manage
ipd.audit.view
```

All authorization must be enforced server-side.

---

# 64. Suggested Roles

Examples:

### Admission Officer

- admission registration
- admission request
- bed search
- reservation

### IPD Coordinator

- admission
- transfer
- discharge coordination
- bed management

### Ward Manager

- ward/bed management
- transfers
- occupancy

### Nurse

- current inpatient location
- transfer workflow
- patient status

### Doctor

- admission request
- clinical admission information
- discharge planning
- transfer request

### Hospital Administrator

- configuration
- reporting
- operational oversight

### Billing Officer

- billing clearance
- admission/discharge financial status

Role definitions must map to the existing permission framework.

---

# 65. API

Base:

```text
/api/v1/ipd
```

Suggested endpoints:

```http
GET    /admissions
POST   /admissions
GET    /admissions/{id}
PUT    /admissions/{id}

POST   /admissions/{id}/approve
POST   /admissions/{id}/cancel

GET    /beds
GET    /beds/availability
POST   /beds/{id}/reserve
POST   /beds/{id}/allocate
POST   /beds/{id}/release

GET    /admissions/{id}/movements
POST   /admissions/{id}/transfers
POST   /transfers/{id}/approve
POST   /transfers/{id}/complete

POST   /admissions/{id}/discharge-request
POST   /admissions/{id}/discharge-approve
POST   /admissions/{id}/discharge

POST   /admissions/{id}/leave
POST   /leaves/{id}/return

GET    /wards
GET    /rooms
GET    /bed-board

GET    /dashboard
GET    /reports/occupancy
GET    /reports/admissions
GET    /reports/discharges
GET    /reports/transfers
```

All APIs must use the existing standard response structure:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {},
  "meta": {}
}
```

Validation failure:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

Never expose:

- SQL errors;
- stack traces;
- filesystem paths;
- secrets;
- internal exception details.

---

# 66. API Security

Every endpoint must verify:

1. authentication;
2. permission;
3. organization;
4. hospital;
5. branch;
6. department where applicable;
7. patient access;
8. admission access;
9. bed scope.

Never trust:

```text
hospital_id
organization_id
patient_id
bed_id
admission_id
```

sent by the client.

Verify relationships server-side.

---

# 67. UI Menu

Recommended menu:

```text
IPD / Inpatient
├── Dashboard
├── Admission
│   ├── Admission Requests
│   ├── Admissions
│   └── Current Inpatients
├── Bed Management
│   ├── Bed Board
│   ├── Bed Availability
│   ├── Wards
│   ├── Rooms
│   ├── Beds
│   ├── Reservations
│   └── Blocked Beds
├── Transfers
├── Patient Leave
├── Discharge
│   ├── Pending Discharge
│   ├── Discharged Patients
│   └── Discharge History
├── Reports
└── Settings
```

---

# 68. Bed Board UX

The bed board should support:

- hospital filter;
- branch;
- building;
- floor;
- ward;
- room;
- bed type;
- status;
- gender;
- isolation.

Each bed card should display:

```text
Bed Number
Status
Patient Name / MRN if occupied
Admission Number
Admission Date
Attending Physician
Expected Discharge
Isolation Indicator
```

Privacy-sensitive information should be restricted by permission.

---

# 69. Admission Workspace

Admission workspace:

```text
Patient Search
        ↓
Patient Summary
        ↓
Admission Request
        ↓
Admission Details
        ↓
Diagnosis
        ↓
Provider
        ↓
Bed Requirements
        ↓
Bed Search
        ↓
Reservation
        ↓
Admission Confirmation
```

Do not duplicate Patient registration.

---

# 70. Inpatient Workspace

Display:

```text
Patient Header
Admission Number
MRN
Current Location
Attending Provider
Admission Date
Expected Discharge

Tabs:

Overview
Clinical Encounter
Diagnoses
Orders
Laboratory
Radiology
Medications
Nursing
Procedures
Transfers
Billing
Documents
Discharge
Timeline
Audit
```

Most tabs should link to existing modules rather than duplicate data.

---

# 71. Discharge Workspace

Show:

```text
Admission Summary
Current Bed
Clinical Status
Diagnosis
Procedures
Investigations
Medication Status
Pending Orders
Billing Status
Insurance Status
Discharge Plan
Follow-up
Disposition
```

Use configured clearance rules.

---

# 72. Audit Requirements

Audit at minimum:

### Admission

- create;
- approve;
- reject;
- cancel;
- update.

### Bed

- create;
- update;
- reserve;
- allocate;
- release;
- block/unblock.

### Transfer

- request;
- approve;
- complete;
- cancel.

### Discharge

- request;
- approve;
- complete;
- amend.

### Patient movement

Every movement.

### Sensitive access

- patient location;
- admission details;
- discharge records;
- exports;
- break-glass access.

Use existing Audit/Security Event infrastructure.

---

# 73. Clinical Record Integrity

Never silently overwrite:

- admission diagnosis;
- provider assignment;
- bed movement;
- discharge information;
- patient location;
- completed admission;
- completed transfer;
- completed discharge.

Corrections should use:

- amendment;
- correction;
- addendum;
- reversal;
- new version;

according to the applicable record type.

---

# 74. Break-Glass Access

Use the existing Phase 0/clinical security foundation.

When a restricted inpatient record requires emergency access:

```text
Normal Access Denied
        ↓
Break-Glass
        ↓
Reason Required
        ↓
Temporary Access
        ↓
Security Audit
```

Never bypass authorization silently.

---

# 75. Multi-Hospital Security

Mandatory security test:

```text
Hospital A
    ↓
User A
    ↓
IPD Data A
```

must never access:

```text
Hospital B
    ↓
IPD Data B
```

Test:

- direct URL;
- API;
- manipulated IDs;
- browser requests;
- exports;
- reports;
- bed board;
- admission search;
- patient location;
- transfers.

---

# 76. FHIR Readiness

Prepare for:

```text
Patient
Encounter
Location
Organization
Practitioner
PractitionerRole
Condition
CarePlan
ServiceRequest
Procedure
MedicationRequest
MedicationAdministration
Observation
DiagnosticReport
DocumentReference
```

Particularly important:

### Encounter

Represent inpatient encounter.

### Location

Represent:

```text
Hospital
Ward
Room
Bed
```

### Patient

Reference Phase 1 MPI.

Do not implement full FHIR server functionality unless the existing interoperability phase requires it.

---

# 77. HL7 / Integration Readiness

Prepare interfaces for future:

- ADT messages;
- patient movement;
- admission;
- transfer;
- discharge;
- bed status;
- lab;
- radiology;
- pharmacy.

Potential ADT concepts:

```text
A01 — Admit
A02 — Transfer
A03 — Discharge
A08 — Patient/visit information update
```

Use an adapter/integration architecture rather than hardcoding external systems.

---

# 78. Reporting

Reports should include:

### Admission

- daily admissions;
- admission by department;
- admission by physician;
- admission by type;
- admission by source.

### Bed

- bed census;
- occupancy;
- available beds;
- bed utilization;
- ward occupancy;
- room utilization;
- blocked beds;
- isolation occupancy.

### Transfer

- transfers by ward;
- transfers by reason;
- transfer history.

### Discharge

- daily discharge;
- discharge by disposition;
- delayed discharge;
- average length of stay foundation.

### Patient movement

- complete movement history.

---

# 79. Average Length of Stay

Provide reporting foundation for:

```text
Length of Stay =
Discharge Date/Time - Admission Date/Time
```

The exact hospital reporting convention should be configurable.

Do not assume that calendar-day and 24-hour calculations are identical.

---

# 80. Data Export

Exports may include:

- admission reports;
- bed census;
- occupancy;
- discharge reports;
- movement reports.

Export must require permission and generate an audit event.

Sensitive patient data must not be exported to unauthorized users.

---

# 81. Performance Requirements

Optimize for:

- bed board;
- bed availability search;
- current inpatient search;
- admission queue;
- transfer queue;
- discharge queue;
- occupancy dashboard.

Use:

- appropriate indexes;
- pagination;
- eager loading;
- query optimization;
- caching for relatively static masters;
- efficient current-state queries.

Do not load complete patient histories into the bed board.

---

# 82. Suggested Indexes

Potential indexes:

```text
admission_number
patient_id
encounter_id
status
admission_date
expected_discharge_date
department_id
provider_id

hospital_id + status
ward_id + status
room_id + status
bed_id + status

patient_id + status
admission_id + status
from_bed_id
to_bed_id
moved_at
```

Adapt based on actual query patterns.

---

# 83. Transaction Boundaries

Use database transactions for:

### Admission

```text
Create admission
Create encounter
Reserve/allocate bed
Create movement
Publish events
```

### Transfer

```text
Lock source
Lock destination
Validate
Close source allocation
Create destination allocation
Create movement
Update state
```

### Discharge

```text
Finalize discharge
Close active allocation
Release/clean bed
Create movement
Publish chargeable event
```

Do not partially complete these operations.

---

# 84. Idempotency

Important operations should be idempotent:

- admission approval;
- bed allocation;
- transfer completion;
- discharge completion;
- chargeable event generation;
- notification jobs.

Duplicate API requests must not create:

- duplicate admissions;
- duplicate allocations;
- duplicate transfers;
- duplicate discharge;
- duplicate billing events.

---

# 85. Testing Strategy

## Unit Tests

Test:

- admission number generation;
- bed availability;
- bed status transitions;
- reservation expiry;
- bed allocation;
- transfer rules;
- gender restrictions;
- isolation rules;
- occupancy calculation;
- discharge rules;
- leave/return;
- chargeable event generation;
- duplicate prevention.

## Feature Tests

Test:

- admission workflow;
- approval;
- bed reservation;
- allocation;
- transfer;
- discharge;
- bed blocking;
- leave;
- dashboard;
- reports;
- API permissions.

## Integration Tests

Test:

```text
Patient → Admission
Admission → Encounter
Admission → Billing
Admission → LIS
Admission → Radiology
Admission → Pharmacy
Admission → Notifications
Admission → Audit
```

---

# 86. Security Tests

Mandatory tests:

```text
Hospital A user → Hospital B admission
Hospital A user → Hospital B bed
Hospital A user → Hospital B patient
Hospital A user → Hospital B report
```

must all fail.

Also test:

- unauthorized admission approval;
- unauthorized bed allocation;
- unauthorized transfer;
- unauthorized discharge;
- unauthorized export;
- direct ID manipulation;
- API scope bypass;
- privilege escalation;
- break-glass misuse.

---

# 87. Negative Tests

Verify that:

- unavailable bed cannot be allocated;
- blocked bed cannot be allocated;
- maintenance bed cannot be allocated;
- occupied bed cannot be allocated;
- expired reservation cannot be used;
- cancelled admission cannot be admitted;
- discharged patient cannot be transferred from active allocation;
- transfer cannot create two active beds;
- duplicate discharge cannot occur;
- duplicate chargeable event cannot occur;
- unauthorized user cannot bypass approval;
- invalid hospital/branch/ward relationships are rejected.

---

# 88. AI Integration Readiness

Future AI capabilities may include:

- bed demand forecasting;
- occupancy forecasting;
- discharge prediction support;
- admission volume forecasting;
- transfer optimization;
- length-of-stay analytics;
- delayed discharge identification;
- bed utilization anomaly detection;
- operational capacity planning.

AI must remain advisory.

AI must never:

- autonomously admit a patient;
- autonomously discharge a patient;
- autonomously transfer a patient;
- autonomously allocate a clinically restricted bed;
- alter clinical records;
- override isolation rules;
- bypass authorization.

All AI recommendations should be:

- explainable;
- auditable;
- clinician/administrator controlled;
- clearly marked as AI-generated.

---

# 89. Notifications and Escalation

Potential escalation:

```text
Pending Admission
        ↓
No Bed Allocated
        ↓
IPD Coordinator
        ↓
Ward Manager
        ↓
Hospital Administrator
```

For delayed discharge:

```text
Expected Discharge Passed
        ↓
Care Team Notification
        ↓
IPD Coordinator
```

Escalation timing should be configurable.

---

# 90. Non-Goals

Do NOT implement in Phase 8:

- full Nursing module;
- full MAR;
- full ICU;
- full OT;
- full Emergency module;
- full Pharmacy;
- full LIS;
- full Radiology/PACS;
- enterprise procurement;
- warehouse inventory;
- accounting system;
- complete insurance claims;
- independent billing engine;
- duplicate patient registration;
- duplicate clinical encounter;
- duplicate prescription;
- autonomous AI admission/discharge.

---

# 91. Implementation Order

Implement in this sequence:

```text
1. Inspect existing architecture
2. Inspect Phases 0–7
3. Inspect Patient/MPI
4. Inspect Encounter/EMR
5. Inspect Billing
6. Inspect Pharmacy
7. Inspect Workflow/Approval
8. Inspect Audit/Notification

9. IPD master configuration
10. Buildings
11. Floors
12. Wards
13. Rooms
14. Beds

15. Bed status engine
16. Bed reservations
17. Bed availability
18. Admission requests
19. Admission approval
20. Admission creation
21. Inpatient encounter integration
22. Initial bed allocation
23. Bed movement ledger
24. Transfers
25. Provider assignment
26. Patient location

27. Discharge planning
28. Discharge workflow
29. Bed release
30. Cleaning workflow
31. Patient leave
32. Bed blocking

33. Billing integration
34. Daily bed charge integration
35. Pharmacy integration
36. LIS integration
37. Radiology integration
38. Nursing integration foundation

39. Dashboard
40. Bed board
41. Reports
42. Notifications
43. Audit
44. API
45. FHIR readiness
46. HL7/ADT readiness

47. Unit tests
48. Feature tests
49. Integration tests
50. Security tests
51. Concurrency tests
52. Performance tests
53. Documentation
54. Deployment verification
```

---

# 92. Definition of Done

Phase 8 is complete only when:

### Admission

- admission request implemented;
- approval implemented;
- admission lifecycle implemented;
- unique admission numbers implemented;
- inpatient encounter integrated.

### Bed Management

- building/floor/ward/room/bed hierarchy;
- bed statuses;
- availability;
- reservation;
- allocation;
- release;
- transfer;
- movement history;
- blocking;
- cleaning state.

### IPD

- current inpatient list;
- provider assignment;
- patient location;
- leave/pass;
- discharge planning;
- discharge workflow;
- readmission.

### Integration

- Patient/MPI;
- Clinical Encounter;
- Billing;
- Pharmacy;
- LIS;
- Radiology;
- Nursing foundation;
- Notification;
- Audit.

### Security

- RBAC;
- multi-hospital scope;
- patient access control;
- API security;
- audit;
- break-glass compatibility.

### Reliability

- transaction safety;
- concurrency protection;
- idempotency;
- duplicate prevention;
- reconciliation.

### Reporting

- census;
- occupancy;
- admission;
- transfer;
- discharge;
- movement;
- delayed discharge.

### API

- REST API;
- validation;
- authorization;
- pagination;
- filtering;
- standardized responses.

### Quality

- automated tests;
- security tests;
- concurrency tests;
- documentation;
- deployment instructions.

---

# 93. FINAL AI IMPLEMENTATION PROMPT

You are a senior Laravel architect, hospital information-system architect, database engineer, security engineer, and healthcare interoperability engineer.

Implement **Phase 8 — IPD / Admission / Bed Management** in the existing Hospital Management System repository.

## First: Inspect Before Coding

Before changing anything:

1. Inspect the entire existing project structure.
2. Identify the existing modular architecture.
3. Inspect Phase 0 Foundation.
4. Inspect Phase 1 Patient/MPI.
5. Inspect Phase 2 Appointment.
6. Inspect Phase 3 Clinical Encounter/EMR.
7. Inspect Phase 4 Billing.
8. Inspect Phase 5 LIS.
9. Inspect Phase 6 Radiology/PACS.
10. Inspect Phase 7 Pharmacy.
11. Inspect existing:
    - models;
    - migrations;
    - services;
    - actions;
    - repositories if used;
    - controllers;
    - policies;
    - gates;
    - Form Requests;
    - events;
    - listeners;
    - jobs;
    - notifications;
    - audit;
    - workflow;
    - API;
    - routes;
    - menus;
    - dashboard components;
    - tests.

Create a dependency map before implementation.

## Mandatory Architecture Rules

The existing modular architecture is already implemented.

**DO NOT create another modular architecture.**

**DO NOT create duplicate implementations of:**

- Patient;
- MPI;
- Appointment;
- Clinical Encounter;
- Diagnosis;
- Prescription;
- User;
- Role;
- Permission;
- Organization;
- Hospital;
- Branch;
- Department;
- Billing;
- Invoice;
- Payment;
- Notification;
- Audit;
- File Management;
- Workflow;
- Clinical Orders.

Reuse existing functionality.

## Implement

Build:

```text
IPD / Admission / Bed Management
```

with:

- admission requests;
- approval;
- admission;
- inpatient encounter integration;
- building;
- floor;
- ward;
- room;
- bed;
- bed status;
- reservation;
- allocation;
- transfer;
- patient movement;
- current location;
- provider assignment;
- isolation;
- leave;
- bed blocking;
- discharge planning;
- discharge;
- bed release;
- readmission;
- occupancy;
- bed board;
- dashboards;
- reports;
- notifications;
- audit;
- APIs;
- FHIR readiness;
- HL7 ADT readiness.

## Critical Business Rules

1. Never duplicate existing Patient/MPI.
2. Never duplicate Clinical Encounter.
3. Never duplicate Prescription.
4. Never duplicate Billing.
5. Never create IPD-specific invoice/payment systems.
6. Never silently overwrite patient movement.
7. Never silently overwrite completed admission/transfer/discharge records.
8. Never allocate an unavailable bed.
9. Never allow double allocation.
10. Protect bed allocation with database transactions and locking.
11. Never trust hospital/branch/patient/bed IDs supplied by the client.
12. Enforce access scope server-side.
13. Never delete completed clinical/operational history.
14. Use amendment/correction workflows.
15. Keep bed movement history immutable.
16. Make scheduled billing events idempotent.
17. Prevent duplicate admission/transfer/discharge operations.
18. Do not implement full Nursing, ICU, OT, Emergency or Insurance in this phase.
19. Do not implement autonomous AI decisions.
20. Never claim tests passed unless they were actually executed.

## Financial Boundary

Use:

```text
IPD Event
   ↓
Chargeable Event
   ↓
Existing Billing Engine
   ↓
Invoice
   ↓
Payment
```

Billing owns:

- price;
- discount;
- tax;
- invoice;
- payment;
- receipt;
- refund;
- insurance/corporate billing.

## Clinical Boundary

Use:

```text
Admission
   ↓
Existing Clinical Encounter
   ↓
Existing Clinical Orders
   ↓
Existing LIS / Radiology / Pharmacy
```

Do not duplicate clinical workflows.

## Bed Safety

Implement transactional locking for:

- reservation;
- allocation;
- transfer;
- release.

Test simultaneous allocation requests.

## Security

Implement and test:

```text
Hospital A → Hospital B
```

access denial for:

- admissions;
- patients;
- beds;
- transfers;
- reports;
- APIs;
- exports.

Also test:

- direct URL manipulation;
- IDOR;
- API scope bypass;
- privilege escalation;
- unauthorized discharge;
- unauthorized transfer;
- unauthorized bed allocation.

## Database

Create only the tables genuinely required after inspecting the existing schema.

Use:

- foreign keys;
- indexes;
- unique constraints;
- transactions;
- timestamps;
- organization/hospital/branch scope;
- appropriate soft-delete policy only where safe.

Do not introduce redundant master tables.

## API

Implement:

```text
/api/v1/ipd
```

with secure REST endpoints for:

- admissions;
- beds;
- reservations;
- allocation;
- transfers;
- discharge;
- leave;
- wards;
- rooms;
- bed board;
- dashboard;
- reports.

Use the existing API response format.

## UI

Implement:

```text
IPD
├── Dashboard
├── Admission
├── Current Inpatients
├── Bed Board
├── Bed Availability
├── Wards
├── Rooms
├── Beds
├── Reservations
├── Transfers
├── Patient Leave
├── Discharge
├── Reports
└── Settings
```

Reuse existing Blade/AdminLTE components.

## Events

Implement appropriate events such as:

```text
PatientAdmitted
BedAllocated
PatientTransferred
PatientDischarged
BedReleased
AdmissionChargeableEventCreated
```

Use existing event conventions.

## Jobs

Implement where required:

```text
ExpireBedReservations
GenerateBedOccupancyCharges
DetectDelayedDischarges
ReconcileBedStates
```

Jobs must be idempotent and retry-safe.

## Testing

Write and execute:

### Unit

- bed availability;
- status transitions;
- allocation;
- transfer;
- occupancy;
- discharge;
- reservation expiry.

### Feature

- admission;
- approval;
- reservation;
- allocation;
- transfer;
- discharge;
- leave;
- reports;
- API.

### Integration

- Patient → IPD;
- IPD → Encounter;
- IPD → Billing;
- IPD → Pharmacy;
- IPD → LIS;
- IPD → Radiology;
- IPD → Notification;
- IPD → Audit.

### Security

- multi-hospital isolation;
- RBAC;
- IDOR;
- API authorization;
- privilege escalation.

### Concurrency

Prove that two simultaneous requests cannot allocate the same bed.

### Negative

Test:

- unavailable bed;
- blocked bed;
- expired reservation;
- duplicate allocation;
- duplicate transfer;
- duplicate discharge;
- invalid hospital/branch relationships;
- unauthorized actions.

Only report tests as passed if they were actually executed.

## Performance

Optimize:

- bed board;
- availability search;
- admission queue;
- transfer queue;
- discharge queue;
- occupancy dashboard.

Use indexes, pagination, eager loading, caching and optimized current-state queries where appropriate.

## Interoperability

Prepare mappings/interfaces for:

```text
FHIR:
Patient
Encounter
Location
Organization
Practitioner
PractitionerRole
Condition
CarePlan
ServiceRequest
Procedure
MedicationRequest
Observation
DiagnosticReport
DocumentReference
```

Prepare future HL7 ADT support:

```text
A01
A02
A03
A08
```

Do not build unnecessary external integration infrastructure if the existing project already provides it.

## AI Safety

Create extension points for future:

- occupancy forecasting;
- bed demand forecasting;
- length-of-stay analytics;
- delayed discharge identification;
- capacity planning.

AI must never autonomously:

- admit;
- discharge;
- transfer;
- allocate restricted beds;
- override isolation;
- modify clinical records;
- bypass authorization.

## Final Deliverable

After implementation, provide a concise implementation report containing:

1. architecture changes;
2. modules/files created;
3. database changes;
4. models;
5. services/actions;
6. controllers;
7. Form Requests;
8. policies;
9. routes;
10. permissions;
11. events/listeners;
12. jobs;
13. notifications;
14. admission workflow;
15. bed management workflow;
16. transfer workflow;
17. discharge workflow;
18. billing integration;
19. LIS integration;
20. radiology integration;
21. pharmacy integration;
22. audit/security implementation;
23. API implementation;
24. FHIR/HL7 readiness;
25. tests actually executed and results;
26. deployment/migration instructions;
27. known limitations;
28. deferred features.

The implementation must be **production-grade, modular, secure, transaction-safe, concurrency-safe, auditable, multi-hospital capable, integration-ready, and consistent with the existing HMS architecture**.

Do not stop at CRUD screens. Implement the complete business workflow.