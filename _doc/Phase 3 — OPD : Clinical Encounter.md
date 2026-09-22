Absolutely. **Phase 3 — OPD / Clinical Encounter** is the point where the HMS moves from administrative scheduling into the actual clinical workflow.

The most important architectural rule is:

> **Appointment ≠ Encounter.**  
> An appointment represents a planned visit. An encounter represents the actual clinical interaction.

Phase 3 should therefore create a reusable **Encounter + EMR foundation** that can later support OPD, Emergency, IPD, Telemedicine, ICU, and other clinical workflows.

# Phase 3 — OPD / Clinical Encounter

## 1. Purpose

Phase 3 manages the complete outpatient clinical lifecycle:

```text
Patient
   ↓
Appointment
   ↓
Check-In
   ↓
Queue
   ↓
Clinical Encounter
   ↓
Clinical Assessment
   ↓
Diagnosis
   ↓
Orders / Procedures
   ↓
Prescription
   ↓
Follow-Up
   ↓
Encounter Completion
```

The module becomes the foundation for the hospital's **Electronic Medical Record (EMR)**.

---

# 2. Phase 3 Scope

### Core Clinical

1. Clinical Encounter
2. OPD Registration
3. Encounter Types
4. Encounter Status
5. Provider Assignment
6. Chief Complaint
7. History of Present Illness
8. Medical History
9. Surgical History
10. Family History
11. Social History
12. Allergy Review
13. Medication History
14. Vital Signs
15. Clinical Examination
16. Review of Systems
17. Diagnosis
18. Clinical Assessment
19. Clinical Notes
20. Problem List
21. Procedures
22. Clinical Orders Framework
23. Prescription
24. Follow-Up
25. Referral
26. Clinical Documents
27. Patient Instructions
28. Encounter Summary
29. Clinical Timeline
30. EMR History

### OPD Operations

31. Doctor Queue
32. Nurse/Assistant Workflow
33. Encounter Start
34. Encounter Pause
35. Encounter Resume
36. Encounter Completion
37. Encounter Cancellation
38. Encounter Transfer
39. Consultation Notes
40. Follow-Up Booking

### Clinical Safety

41. Allergy Alerts
42. Critical Patient Alerts
43. Medication Allergy Warning
44. Duplicate Diagnosis Prevention
45. Clinical Record Locking
46. Amendment/Addendum
47. Clinical Audit
48. Break-Glass Foundation

### Integration Ready

49. Laboratory
50. Radiology
51. Pharmacy
52. Billing
53. Insurance
54. Referral
55. FHIR/HL7
56. Patient Portal

The downstream modules will be implemented in later phases.

---

# 3. Explicitly Out of Scope

Do not build the full:

- Laboratory system
- Radiology system
- PACS
- Pharmacy inventory
- Pharmacy dispensing
- Billing
- Insurance claims
- IPD
- Bed management
- OT
- ICU
- Blood Bank

However, Phase 3 must create clean **clinical orders, prescription, diagnosis, procedure and referral interfaces** so those modules can integrate later.

---

# 4. Core Clinical Architecture

The architecture should be:

```text
Patient
   │
   ├── Appointments
   │
   └── Encounters
          │
          ├── Chief Complaint
          ├── History
          ├── Vitals
          ├── Examination
          ├── Diagnoses
          ├── Problems
          ├── Procedures
          ├── Orders
          ├── Prescriptions
          ├── Referrals
          ├── Documents
          ├── Instructions
          └── Follow-Up
```

A patient can have:

```text
Patient A
 ├── Appointment 1
 │     └── Encounter 1
 ├── Appointment 2
 │     └── Encounter 2
 └── Emergency Encounter
```

Never put clinical data directly into the `patients` table.

---

# 5. Encounter vs Appointment

This distinction is critical.

### Appointment

```text
Patient intends to see Dr. X
on 22 September at 10:00.
```

### Encounter

```text
Patient actually saw Dr. X
and clinical assessment was performed.
```

An appointment may therefore:

- be cancelled
- become a no-show
- be rescheduled
- occur without an appointment
- result in an encounter

An encounter may originate from:

```text
Scheduled Appointment
Walk-in
Referral
Emergency
Follow-up
Internal Transfer
External Referral
```

---

# 6. Encounter Types

Support:

```text
New Consultation
Follow-up
Review
Second Opinion
Procedure
Health Checkup
Referral
Telemedicine
Walk-in
Other
```

Future modules may add:

```text
Emergency
Inpatient
ICU
Post-operative
```

---

# 7. Encounter Status

Recommended:

```text
Draft
Registered
Waiting
In Progress
Paused
Completed
Cancelled
Transferred
Amended
Locked
```

Lifecycle:

```text
Registered
    ↓
Waiting
    ↓
In Progress
    ↓
Completed
```

Alternative:

```text
Waiting → Cancelled
Waiting → Transferred
In Progress → Paused → In Progress
Completed → Amendment
```

Do not allow arbitrary status changes.

---

# 8. Encounter Number

Generate a unique clinical encounter number.

Example:

```text
ENC-2026-00000123
```

Requirements:

- unique
- immutable
- concurrency-safe
- searchable
- printable

Never generate using `COUNT()`.

---

# 9. Encounter Entity

Recommended:

```text
encounters
```

Fields:

```text
id
organization_id
hospital_id
branch_id

encounter_number

patient_id
appointment_id nullable

encounter_type_id

provider_id
department_id
specialty_id

encounter_date
start_at
end_at

status
priority
source

chief_complaint_summary

reason_for_visit

referred_by

created_by
completed_by

completed_at

created_at
updated_at
```

Keep the main encounter table relatively lean.

Clinical details should reside in related tables.

---

# 10. OPD Workflow

Recommended workflow:

```text
Appointment
     ↓
Patient Check-In
     ↓
Queue
     ↓
Doctor Opens Queue
     ↓
Start Encounter
     ↓
Patient Context
     ↓
Chief Complaint
     ↓
History
     ↓
Vitals
     ↓
Examination
     ↓
Assessment
     ↓
Diagnosis
     ↓
Orders
     ↓
Prescription
     ↓
Instructions
     ↓
Follow-Up
     ↓
Complete Encounter
```

---

# 11. Doctor Workspace

The doctor should see a focused clinical workspace.

Example:

```text
┌──────────────────────────────────────────────────┐
│ Patient: Abdullah Rahman                        │
│ MRN: PAT-2026-000123                            │
│ 46 Y | Male | O+                                │
│ ⚠ Penicillin Allergy                            │
├──────────────────────────────────────────────────┤
│ Chief Complaint │ History │ Vitals │ Examination│
│ Diagnosis       │ Orders  │ Rx     │ Follow-up  │
└──────────────────────────────────────────────────┘
```

---

# 12. Patient Context Header

Every clinical screen should display:

```text
Patient Name
MRN
Age
Gender
Blood Group
Allergy Alerts
Important Patient Alerts
Current Encounter
```

Never make clinicians navigate away just to confirm patient identity.

For sensitive operations, consider a patient identity confirmation step.

---

# 13. Chief Complaint

Implement:

```text
encounter_complaints
```

Support:

```text
Complaint
Duration
Onset
Severity
Location
Notes
```

Example:

```text
Fever
Duration: 3 days
Severity: Moderate
```

Allow multiple complaints.

---

# 14. History of Present Illness

Implement structured + free-text HPI.

Possible fields:

```text
onset
duration
course
severity
associated_symptoms
aggravating_factors
relieving_factors
clinical_notes
```

Do not force every clinical specialty into one rigid form.

Support flexible clinical sections.

---

# 15. Medical History

Patient-level medical history should be accessible during an encounter.

Examples:

```text
Diabetes
Hypertension
Asthma
Heart Disease
Previous Surgery
```

Important architectural distinction:

### Patient history

Long-term patient information.

### Encounter assessment

What the clinician assesses during this visit.

Do not duplicate the same data unnecessarily.

---

# 16. Surgical History

Support:

```text
Procedure
Date
Hospital
Surgeon
Notes
```

Future surgical module can extend this.

---

# 17. Family History

Support:

```text
Condition
Relationship
Age
Status
Notes
```

Example:

```text
Father
Diabetes
```

---

# 18. Social History

Support configurable sections:

```text
Smoking
Alcohol
Occupation
Diet
Exercise
Lifestyle
Other
```

Keep these configurable.

---

# 19. Allergy Review

Phase 1 contains basic allergies.

Phase 3 must make them clinically visible.

Example:

```text
⚠ ALLERGY ALERT

Penicillin
Reaction: Rash
Severity: Moderate
```

The clinician must explicitly acknowledge critical allergy information where appropriate.

---

# 20. Medication History

Display current/recent medications.

Phase 3 can maintain medication history references, while the full pharmacy system comes later.

Support:

```text
Medication
Dose
Frequency
Route
Duration
Source
Status
```

Do not build pharmacy inventory here.

---

# 21. Vital Signs

Implement structured vitals.

Examples:

```text
Temperature
Pulse
Respiratory Rate
Blood Pressure
SpO2
Weight
Height
BMI
Pain Score
```

Potential fields:

```text
systolic_bp
diastolic_bp
pulse
respiratory_rate
temperature
temperature_unit
spo2
weight
weight_unit
height
height_unit
bmi
pain_score
recorded_at
recorded_by
```

Support future device integration.

---

# 22. Vital Sign History

The clinician should be able to see:

```text
Today
Previous Visit
Previous Month
Historical Trend
```

Do not overwrite previous measurements.

Every measurement is a clinical observation.

---

# 23. Clinical Examination

Implement a flexible examination framework.

Example:

```text
General Examination
HEENT
Cardiovascular
Respiratory
Abdomen
Neurological
Musculoskeletal
Skin
Other
```

But do not hardcode only these specialties.

Use configurable examination sections/templates.

---

# 24. Clinical Templates

Allow providers to use templates.

Example:

```text
General Medicine Examination
Pediatric Examination
Cardiology Examination
Dermatology Examination
Orthopedic Examination
Gynecology Examination
```

Templates should be configurable.

Avoid creating separate hardcoded forms for every specialty.

---

# 25. Review of Systems

Support configurable:

```text
Constitutional
Respiratory
Cardiovascular
GI
GU
Neurological
Musculoskeletal
Skin
Psychiatric
Other
```

Allow:

```text
Normal
Abnormal
Not Assessed
```

plus notes.

---

# 26. Assessment

The encounter needs an explicit clinical assessment section.

Example:

```text
Assessment:
Likely viral upper respiratory infection.
```

This should be distinct from diagnosis coding.

---

# 27. Diagnosis

Implement diagnosis management.

Support:

```text
Primary Diagnosis
Secondary Diagnosis
Differential Diagnosis
Historical Diagnosis
```

Each diagnosis should support:

```text
Diagnosis Code
Diagnosis Name
Coding System
Status
Onset
Notes
Primary
```

Prepare for:

```text
ICD-10
ICD-11
SNOMED CT
```

depending on future licensing/integration decisions.

Do not hardcode only one coding system.

---

# 28. Problem List

Create a patient problem-list foundation.

Example:

```text
Active Problems
────────────────
Hypertension
Diabetes Mellitus

Resolved Problems
────────────────
Pneumonia
```

A problem should be reusable across encounters.

Architecture:

```text
Patient
   ↓
Problem List
   ↑
Encounter Diagnoses
```

Do not automatically convert every encounter diagnosis into a permanent problem without defined rules.

---

# 29. Procedures

Support encounter procedures.

Example:

```text
Procedure
Code
Date
Provider
Notes
Status
```

Future OT/procedure modules can extend this.

---

# 30. Clinical Orders Framework

Create an order abstraction.

Example:

```text
Clinical Order
    ├── Laboratory
    ├── Radiology
    ├── Procedure
    └── Other
```

Phase 3 should establish:

```text
clinical_orders
```

but **must not implement the full Laboratory or Radiology systems**.

Example:

```text
Order:
CBC

Status:
Requested
```

Later:

```text
Laboratory Module
       ↓
Receives Order
       ↓
Sample
       ↓
Result
```

---

# 31. Prescription

Phase 3 should introduce the clinical prescription layer.

Example:

```text
Prescription
    ├── Medicine A
    ├── Medicine B
    └── Medicine C
```

Each item:

```text
Medicine
Dose
Unit
Route
Frequency
Duration
Quantity
Instructions
PRN
Start Date
End Date
```

The full Pharmacy module will later handle:

- drug inventory
- dispensing
- stock
- batch
- expiry
- sales
- returns

---

# 32. Prescription Status

Support:

```text
Draft
Issued
Cancelled
Expired
Dispensed
Partially Dispensed
```

The Pharmacy module later controls actual dispensing status.

---

# 33. Medication Safety

Prepare the architecture for:

```text
Allergy checking
Drug interaction
Duplicate therapy
Dose validation
Contraindications
```

But do not implement an advanced drug-interaction engine in Phase 3 unless a verified clinical data source is available.

---

# 34. Follow-Up

Support:

```text
Follow-Up Required
Follow-Up Date
Follow-Up Interval
Instructions
```

Provide:

```text
[Book Follow-Up]
```

which calls Phase 2 appointment scheduling.

The clinical module should not directly manipulate appointment slots.

---

# 35. Referral

Support referral:

```text
Internal Referral
External Referral
Department Referral
Provider Referral
```

Example:

```text
Refer to:
Cardiology

Reason:
Persistent chest discomfort
```

Future referral module can expand this.

---

# 36. Patient Instructions

Support structured and free-text instructions.

Examples:

```text
Medication instructions
Diet advice
Lifestyle advice
Warning signs
Follow-up instructions
```

These are clinical instructions and must be audited.

---

# 37. Encounter Summary

At completion, generate:

```text
Encounter Summary
```

Including:

```text
Chief Complaint
Relevant History
Vitals
Examination
Assessment
Diagnosis
Procedures
Orders
Prescription
Follow-Up
Instructions
```

Do not create a second source of truth.

Generate the summary from the underlying clinical records.

---

# 38. Clinical Document

Support:

```text
Consultation Summary
Referral Letter
Prescription
Clinical Certificate
Medical Note
```

Use Phase 0 file management.

---

# 39. Clinical Note

Implement structured note sections rather than one giant text field.

Possible:

```text
Chief Complaint
HPI
History
Examination
Assessment
Plan
```

Allow specialty-specific templates.

---

# 40. Clinical Record Locking

This is critical.

Once an encounter is completed:

```text
Completed
   ↓
Locked
```

Normal users should not modify locked clinical records.

If correction is necessary:

```text
Locked Record
      ↓
Amendment/Addendum
      ↓
New Audit Entry
```

Never silently rewrite historical clinical documentation.

---

# 41. Addendum

Support:

```text
Clinical Addendum
```

Fields:

```text
Original Encounter
Addendum Type
Reason
Content
Created By
Created At
Approved By
```

The original record remains intact.

---

# 42. Break-Glass

Prepare emergency access.

Example:

```text
Normal Access
     ↓
Access Denied
     ↓
Break Glass
     ↓
Reason Required
     ↓
Temporary Access
     ↓
Security Audit
```

Break-glass must be highly auditable.

---

# 43. Clinical Audit

Audit:

```text
Encounter Created
Encounter Viewed
Encounter Started
Clinical Note Created
Clinical Note Updated
Vital Recorded
Diagnosis Added
Diagnosis Changed
Procedure Added
Order Created
Prescription Created
Prescription Issued
Referral Created
Encounter Completed
Encounter Locked
Addendum Created
Clinical Record Exported
Clinical Record Printed
Break-Glass Access
```

Do not put complete clinical content into generic application logs.

---

# 44. Clinical Timeline

The Patient 360 timeline should now show:

```text
Appointment
      ↓
Encounter
      ↓
Diagnosis
      ↓
Prescription
      ↓
Order
      ↓
Follow-Up
```

Use references to actual records rather than duplicating clinical data.

---

# 45. Doctor Queue

Phase 2 provides the waiting queue.

Phase 3 consumes it.

Example:

```text
Doctor Dashboard

Waiting
────────────────────
Token C021  Abdullah Rahman
Token C022  Karim Ahmed
Token C023  Rahim Uddin

[Call Next]
```

Selecting a patient should open the clinical workspace.

---

# 46. Clinical Workspace

Recommended layout:

```text
┌───────────────────────────────────────────────────────┐
│ PATIENT HEADER                                        │
├───────────────────────────────────────────────────────┤
│ Chief Complaint │ History │ Vitals │ Examination      │
│ Diagnosis       │ Orders  │ Prescription │ Follow-up │
├───────────────────────────────────────────────────────┤
│ Clinical Workspace                                   │
│                                                       │
│                                                       │
└───────────────────────────────────────────────────────┘
```

Keep the interface fast for doctors.

---

# 47. Encounter Navigation

Recommended tabs:

```text
Overview
Chief Complaint
History
Vitals
Examination
Assessment
Diagnosis
Problems
Orders
Prescription
Procedures
Referral
Instructions
Follow-Up
Documents
Summary
Audit
```

---

# 48. Database Architecture

Recommended core tables:

```text
encounters
encounter_status_history
encounter_complaints
encounter_histories
encounter_examinations
encounter_review_of_systems
encounter_vitals
encounter_assessments
encounter_diagnoses
patient_problems
encounter_procedures
clinical_orders
clinical_order_items
prescriptions
prescription_items
encounter_referrals
encounter_instructions
encounter_notes
encounter_documents
encounter_amendments
encounter_templates
encounter_template_sections
```

Depending on implementation, some can be consolidated.

---

# 49. `encounters` Table

Recommended:

```text
id
organization_id
hospital_id
branch_id

encounter_number

patient_id
appointment_id nullable

encounter_type_id

provider_id
department_id
specialty_id

encounter_date
start_at
end_at

status
priority
source

reason_for_visit

created_by
completed_by
completed_at

locked_at
locked_by

created_at
updated_at
```

---

# 50. Encounter Complaints

```text
encounter_complaints

id
encounter_id
complaint
duration
duration_unit
onset
severity
location
notes
sort_order
created_at
updated_at
```

---

# 51. Encounter Vitals

```text
encounter_vitals

id
encounter_id
patient_id

temperature
temperature_unit

pulse
respiratory_rate

systolic_bp
diastolic_bp

spo2

weight
weight_unit

height
height_unit

bmi

pain_score

recorded_by
recorded_at
```

Do not overwrite previous vitals.

---

# 52. Diagnoses

```text
encounter_diagnoses

id
encounter_id
patient_id

diagnosis_code
diagnosis_name
coding_system

diagnosis_type
is_primary

onset_date
status
notes

recorded_by
recorded_at
```

---

# 53. Patient Problems

```text
patient_problems

id
patient_id

problem_code
problem_name
coding_system

status
onset_date
resolved_date

source_encounter_id

notes

created_by
updated_by
```

---

# 54. Clinical Orders

```text
clinical_orders

id
organization_id
hospital_id
branch_id

order_number

patient_id
encounter_id
provider_id

order_type
priority

status

ordered_at
ordered_by

cancelled_at
cancelled_by

notes

created_at
updated_at
```

Later modules can consume:

```text
order_type = laboratory
order_type = radiology
order_type = procedure
```

---

# 55. Prescription

```text
prescriptions

id
prescription_number

patient_id
encounter_id
provider_id

status

issued_at
valid_until

instructions

created_at
updated_at
```

Items:

```text
prescription_items

id
prescription_id

medicine_code
medicine_name

dose
dose_unit

route
frequency

duration
duration_unit

quantity

is_prn

instructions

start_date
end_date

status
```

Do not tie medicine to inventory IDs yet.

---

# 56. Clinical Templates

Create reusable templates.

Example:

```text
Template:
General Medicine OPD

Sections:
Chief Complaint
HPI
ROS
General Examination
Cardiovascular
Respiratory
Abdomen
Assessment
Plan
```

Providers should be able to select a template.

---

# 57. Specialty Templates

Prepare:

```text
General Medicine
Cardiology
Pediatrics
Gynecology
Orthopedics
Dermatology
ENT
Ophthalmology
Dentistry
Psychiatry
```

Do not hardcode clinical logic into each specialty.

Templates should be data/configuration driven.

---

# 58. Clinical Data Versioning

Clinical records are legally and operationally sensitive.

Where appropriate:

```text
Original Version
      ↓
Amendment
      ↓
Version 2
```

Never silently alter completed clinical records.

---

# 59. API

Base:

```text
/api/v1/encounters
```

Endpoints:

```http
GET    /api/v1/encounters
POST   /api/v1/encounters
GET    /api/v1/encounters/{encounter}

POST   /api/v1/encounters/{encounter}/start
POST   /api/v1/encounters/{encounter}/pause
POST   /api/v1/encounters/{encounter}/resume
POST   /api/v1/encounters/{encounter}/complete

GET    /api/v1/encounters/{encounter}/vitals
POST   /api/v1/encounters/{encounter}/vitals

GET    /api/v1/encounters/{encounter}/diagnoses
POST   /api/v1/encounters/{encounter}/diagnoses

GET    /api/v1/encounters/{encounter}/orders
POST   /api/v1/encounters/{encounter}/orders

GET    /api/v1/encounters/{encounter}/prescription
POST   /api/v1/encounters/{encounter}/prescription

GET    /api/v1/encounters/{encounter}/summary
POST   /api/v1/encounters/{encounter}/amendment
```

Use the Phase 0 API response format.

---

# 60. Authorization

Recommended permissions:

```text
encounter.view
encounter.create
encounter.update
encounter.start
encounter.complete
encounter.cancel
encounter.amend
encounter.lock
encounter.export
encounter.print

clinical.note.view
clinical.note.create
clinical.note.update

clinical.vitals.view
clinical.vitals.create

clinical.diagnosis.view
clinical.diagnosis.create
clinical.diagnosis.update

clinical.order.view
clinical.order.create
clinical.order.cancel

prescription.view
prescription.create
prescription.issue
prescription.cancel

clinical.referral.view
clinical.referral.create

clinical.break_glass
```

Use policies and scope enforcement.

---

# 61. Clinical Access Scope

A doctor should normally see:

```text
Patients
    ↓
Encounters
    ↓
within authorized Hospital / Branch / Department / Provider scope
```

Do not rely on route parameters or UI visibility.

All database queries must respect authorization.

---

# 62. Clinical Privacy

Patient clinical information must receive stronger protection than ordinary operational records.

Consider:

- minimum necessary access
- role-based access
- department/provider scope
- sensitive record audit
- break-glass
- export controls
- document access logging
- session controls
- automatic timeout
- secure APIs

---

# 63. Reports

Implement:

### Daily OPD Report

```text
Date
Department
Provider
Total Patients
New Patients
Follow-Ups
Completed
Cancelled
No Show
```

### Provider Workload

### Department OPD Statistics

### Diagnosis Statistics

### Prescription Statistics

### Follow-Up Statistics

### Waiting Time

### Consultation Time

### Encounter Completion

### No-Show

All reports must respect authorization.

---

# 64. Clinical KPIs

Prepare:

```text
Average Waiting Time
Average Consultation Time
Patients per Provider
New vs Follow-Up
Encounter Completion Rate
No-Show Rate
Diagnosis Distribution
Prescription Rate
Referral Rate
Order Rate
```

Do not use these metrics to make unsupported clinical quality judgments.

---

# 65. FHIR Readiness

Prepare mapping for:

```text
Encounter
Patient
Practitioner
PractitionerRole
Condition
Observation
Procedure
MedicationRequest
ServiceRequest
DiagnosticReport
DocumentReference
CarePlan
Appointment
```

Do not implement a complete FHIR server in Phase 3.

---

# 66. HL7/DICOM Readiness

Clinical orders should be designed so future systems can map:

```text
Clinical Order
   ↓
HL7 / FHIR ServiceRequest
```

Radiology integration can later map to:

```text
DICOM / PACS
```

Laboratory can later map to:

```text
LIS / HL7 / FHIR
```

---

# 67. AI Readiness

Do not make AI a required part of clinical decision-making.

Prepare an extension point for future:

```text
Clinical Summary
Note Assistance
Medical Coding Assistance
Documentation Assistance
Patient Summary
```

Any future AI functionality must:

- be clearly identified as AI-assisted
- preserve clinician control
- maintain auditability
- never silently alter clinical records
- never automatically finalize diagnosis/prescription
- require clinician review before committing clinical information

---

# 68. Events

Implement events such as:

```text
EncounterCreated
EncounterStarted
EncounterCompleted
EncounterLocked
DiagnosisAdded
VitalRecorded
ClinicalOrderCreated
PrescriptionCreated
PrescriptionIssued
ReferralCreated
ClinicalAmendmentCreated
```

Future modules should consume these events.

---

# 69. Queue Jobs

Use asynchronous processing for:

```text
GenerateClinicalDocument
GeneratePrescriptionPDF
GenerateEncounterSummary
SendFollowUpNotification
SendReferralNotification
ExternalClinicalIntegration
```

Do not block the doctor UI unnecessarily.

---

# 70. UI Components

Create reusable:

```text
PatientClinicalHeader
AllergyAlert
PatientAlert
EncounterHeader
ClinicalTabs
VitalSignsForm
VitalHistory
ComplaintForm
HistoryForm
ExaminationForm
DiagnosisSelector
ProblemList
ClinicalOrderForm
PrescriptionBuilder
ReferralForm
FollowUpForm
ClinicalTimeline
EncounterSummary
ClinicalLockIndicator
AmendmentDialog
```

---

# 71. Doctor Workflow

Implement:

```text
Doctor Dashboard
        ↓
Waiting Queue
        ↓
Select Patient
        ↓
Start Encounter
        ↓
Review Patient History
        ↓
Record Chief Complaint
        ↓
Record History
        ↓
Review Vitals
        ↓
Perform Examination
        ↓
Assessment
        ↓
Diagnosis
        ↓
Orders
        ↓
Prescription
        ↓
Instructions
        ↓
Follow-Up
        ↓
Complete Encounter
        ↓
Lock Record
```

---

# 72. Nurse/Assistant Workflow

Support limited clinical preparation:

```text
Patient
   ↓
Queue
   ↓
Open Encounter
   ↓
Record Vitals
   ↓
Initial Assessment
   ↓
Mark Ready for Doctor
```

Do not allow nurses/assistants to modify doctor-only clinical records unless explicitly authorized.

---

# 73. Clinical Record Locking

When doctor completes the encounter:

```text
status = completed
locked_at = now()
locked_by = doctor
```

Subsequent corrections must use:

```text
Amendment
```

not direct editing.

---

# 74. Amendment Workflow

Implement:

```text
Request Amendment
       ↓
Reason
       ↓
Review
       ↓
Approved
       ↓
Addendum / Version
```

The original record remains unchanged.

Audit:

```text
Who
When
What
Why
Original reference
New content
```

---

# 75. Testing

## Unit Tests

Test:

- encounter-number generation
- status transitions
- diagnosis rules
- prescription calculations
- BMI calculation
- clinical template loading
- follow-up linkage

## Feature Tests

Test:

- encounter creation
- appointment-to-encounter conversion
- walk-in encounter
- vital entry
- diagnosis
- problem list
- order creation
- prescription
- referral
- follow-up
- encounter completion
- locking
- amendment

## Security Tests

Test:

- unauthorized clinical access
- cross-hospital access
- cross-branch access
- provider scope
- unauthorized prescription
- unauthorized diagnosis modification
- unauthorized record amendment
- unauthorized export
- break-glass audit

---

# 76. Critical Clinical Security Tests

At minimum:

```text
Doctor A cannot access Doctor B's restricted patient records
unless authorized.

Hospital A user cannot access Hospital B clinical records.

A completed/locked encounter cannot be silently modified.

A user without prescription permission cannot issue prescriptions.

A user without amendment permission cannot modify completed clinical records.

Break-glass access always creates a security audit event.
```

---

# 77. Performance

Optimize for:

- fast doctor workspace
- fast patient history
- fast encounter loading
- minimal page reloads
- efficient clinical timeline
- efficient diagnosis search
- efficient medicine search
- no N+1 queries

Do not load the entire patient medical history automatically if it is very large.

Use:

```text
Pagination
Lazy loading
Tabs
API requests
Caching where appropriate
```

---

# 78. Search

Clinical search should support:

### Diagnosis

```text
Code
Name
Coding System
```

### Medication

```text
Generic Name
Brand Name
Code
```

### Patient

Reuse Phase 1 search.

Do not load thousands of diagnosis or medication records into the browser.

---

# 79. Menu

Recommended:

```text
Clinical
│
├── Dashboard
├── Doctor Queue
├── My Encounters
├── New Encounter
├── Encounter List
├── Patient History
├── Clinical Templates
├── Diagnoses
├── Problem List
├── Clinical Orders
├── Prescriptions
├── Referrals
├── Follow-Ups
├── Clinical Reports
└── Settings
```

Patient 360 should expose:

```text
Encounters
Clinical History
Diagnoses
Prescriptions
Orders
Referrals
```

according to authorization.

---

# 80. Documentation

Create/update:

```text
docs/PHASE-3-OPD-CLINICAL-ENCOUNTER.md
docs/CLINICAL-ENCOUNTER-ARCHITECTURE.md
docs/EMR-DATA-MODEL.md
docs/CLINICAL-SECURITY.md
docs/CLINICAL-API.md
docs/ENCOUNTER-LIFECYCLE.md
```

Use the project's existing documentation conventions where applicable.

---

# 81. Final Acceptance Criteria

Phase 3 is complete only when:

[ ] Appointment can create an encounter

[ ] Walk-in can create an encounter

[ ] Encounter number is generated safely

[ ] Encounter lifecycle works

[ ] Doctor queue integration works

[ ] Doctor workspace works

[ ] Patient clinical context is visible

[ ] Allergy alerts are visible

[ ] Chief complaint works

[ ] HPI works

[ ] Medical history works

[ ] Surgical history works

[ ] Family history works

[ ] Social history works

[ ] Medication history works

[ ] Vitals work

[ ] Vital history works

[ ] Clinical examination works

[ ] Review of systems works

[ ] Assessment works

[ ] Diagnosis works

[ ] Problem list works

[ ] Procedures work

[ ] Clinical order framework works

[ ] Prescription works

[ ] Referral works

[ ] Follow-up works

[ ] Instructions work

[ ] Clinical documents work

[ ] Encounter summary works

[ ] Clinical timeline works

[ ] Encounter locking works

[ ] Amendment/addendum works

[ ] Audit works

[ ] Break-glass framework works

[ ] API works

[ ] RBAC works

[ ] Hospital scope works

[ ] Branch scope works

[ ] Provider scope works

[ ] Security tests pass

[ ] Clinical record immutability tests pass

[ ] No N+1 problems exist

[ ] Clean migration works

[ ] Documentation is updated

[ ] Deployment instructions are documented

Do not claim tests passed unless they were actually executed.

---

# 82. Phase Boundary

When Phase 3 is complete, the HMS should support:

```text
PHASE 1
Patient / MPI
      ↓
PHASE 2
Appointment
      ↓
Check-In
      ↓
Queue
      ↓
PHASE 3
Clinical Encounter
      ↓
History
Vitals
Examination
Assessment
Diagnosis
Orders
Prescription
Referral
Follow-Up
      ↓
Completed + Locked Clinical Record
```

The next modules can then consume the clinical orders and financial/service information:

```text
                     Clinical Encounter
                            │
          ┌─────────────────┼─────────────────┐
          ↓                 ↓                 ↓
      Laboratory         Radiology         Pharmacy
          │                 │                 │
          └─────────────────┼─────────────────┘
                            ↓
                         Billing
                            ↓
                         Insurance
```

Do not implement those downstream modules in Phase 3.
:::

### Recommended Phase 3 architectural decisions

There are **three decisions I would treat as non-negotiable** for this phase:

1. **Separate `encounters` from `appointments`.** This gives you a clean foundation for OPD, Emergency, IPD and Telemedicine later.
2. **Make completed clinical records immutable.** Corrections should use amendments/addenda with a complete audit trail rather than overwriting the original record.
3. **Keep clinical data modular.** Vitals, diagnoses, problems, orders, prescriptions, procedures and referrals should be separate domain records linked to the encounter—not one massive `clinical_records` table.

With those principles, your HMS architecture becomes:

```text
Phase 0  Foundation
    ↓
Phase 1  Patient / MPI
    ↓
Phase 2  Appointment / Scheduling
    ↓
Phase 3  OPD / Clinical Encounter / EMR Foundation
    ↓
 ┌───────────┬────────────┬─────────────┬─────────────┐
 ↓           ↓            ↓             ↓
Lab       Radiology    Pharmacy       Billing
 ↓           ↓            ↓             ↓
 └───────────┴────────────┴─────────────┘
                     ↓
               Phase 5 — IPD
                     ↓
          Admission / Bed / Nursing
                     ↓
          OT / ICU / Discharge
```