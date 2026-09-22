# AI IMPLEMENTATION PROMPT
## Phase 3 — OPD / Clinical Encounter / EMR Foundation

You are a senior healthcare software architect and Laravel engineer responsible for implementing **Phase 3 — OPD / Clinical Encounter / EMR Foundation** of an enterprise-grade, international-standard Hospital Management System (HMS).

You are working on an existing Laravel modular-monolith HMS where **Phase 0 — Foundation**, **Phase 1 — Patient / MPI**, and **Phase 2 — Appointment & Scheduling** have already been implemented.

Your job is to inspect the existing codebase, understand the established architecture, and implement Phase 3 **without breaking existing functionality or duplicating existing platform capabilities**.

---

# 1. PROJECT OBJECTIVE

Implement the clinical workflow that connects:

**Patient → Appointment/Walk-in → Encounter → Clinical Assessment → Diagnosis → Orders → Prescription → Referral → Follow-up → Encounter Completion → Clinical Record Locking**

Phase 3 must establish the reusable **EMR/clinical foundation** that future modules such as:

- Laboratory
- Radiology
- Pharmacy
- Billing
- IPD
- Nursing
- OT
- ICU
- Blood Bank
- Insurance
- Patient Portal
- FHIR/HL7 integrations
- Clinical AI

can build upon.

The implementation must be production-oriented, secure, auditable, modular, maintainable, and suitable for multi-hospital deployment.

---

# 2. IMPORTANT ARCHITECTURAL RULES

## 2.1 Do not rebuild Phase 0–2

Before writing code:

1. Inspect the existing repository.
2. Identify existing:
   - Models
   - Migrations
   - Services
   - Repositories, if used
   - Policies
   - Permissions
   - Middleware
   - Routes
   - Components
   - Layouts
   - API response helpers
   - Audit mechanisms
   - File management
   - Notifications
   - Settings
   - Organization/hospital/branch/department scope
   - Patient/MPI functionality
   - Appointment/scheduling functionality
3. Reuse existing infrastructure wherever possible.
4. Do not create duplicate implementations of existing capabilities.

If the current codebase differs from this specification, preserve the project's established conventions where they are architecturally sound.

---

# 3. TECHNOLOGY REQUIREMENTS

Use the project's existing stack, which is expected to be:

- PHP 8.4+
- Laravel
- MySQL or PostgreSQL
- Redis
- Blade
- AdminLTE
- REST API
- Laravel Queue
- Laravel Scheduler

Follow:

- PSR-12
- Laravel conventions
- SOLID principles
- Dependency Injection
- Service-oriented business logic
- Form Requests
- Policies/Gates
- Events/Listeners
- Jobs
- Transactions
- Eloquent relationships
- Database constraints
- Proper indexing
- Secure authorization

Avoid:

- Fat controllers
- Business logic inside Blade
- Raw SQL unless justified
- Duplicate business rules
- Hardcoded hospital/department IDs
- Hardcoded specialty behavior
- Hardcoded diagnosis systems
- Hardcoded medicine inventory relationships
- Trusting client-side authorization
- Silent modification of locked clinical records

---

# 4. MULTI-TENANCY / ORGANIZATIONAL SCOPE

The existing hierarchy is:

Organization
    ↓
Hospital
    ↓
Branch
    ↓
Department

Every clinical record must respect the existing organizational scope.

At minimum, clinical records must support:

- organization_id
- hospital_id
- branch_id where applicable
- department_id where applicable

Server-side authorization must ensure:

> A user belonging to Hospital A must never access Hospital B clinical records unless an explicitly authorized cross-hospital role/scope allows it.

Never rely only on hidden form fields, route parameters, JavaScript, or UI filtering.

Implement authorization through the existing access-control architecture.

---

# 5. CORE CONCEPT

## APPOINTMENT ≠ ENCOUNTER

This distinction is mandatory.

### Appointment

An appointment represents a **planned visit**.

### Encounter

An encounter represents an **actual clinical interaction**.

An appointment can:

- Be scheduled
- Be confirmed
- Be checked in
- Become an encounter
- Be cancelled
- Be rescheduled
- Become a no-show

An encounter can originate from:

- Scheduled appointment
- Walk-in
- Referral
- Emergency
- Follow-up
- Internal transfer
- External referral

Therefore:

> Never force every encounter to have an appointment_id.

appointment_id must be nullable.

---

# 6. PHASE 3 MODULES

Implement the following:

1. Clinical Encounter
2. OPD Registration
3. Encounter Lifecycle
4. Provider Assignment
5. Doctor Queue Integration
6. Chief Complaint
7. HPI
8. Medical History
9. Surgical History
10. Family History
11. Social History
12. Medication History
13. Allergy Review
14. Vital Signs
15. Clinical Examination
16. Review of Systems
17. Clinical Assessment
18. Diagnosis
19. Problem List
20. Procedure Recording
21. Clinical Orders Framework
22. Prescription
23. Referral
24. Follow-up
25. Patient Instructions
26. Clinical Notes
27. Clinical Templates
28. Encounter Summary
29. Clinical Timeline
30. Clinical Documents
31. Encounter Locking
32. Clinical Amendment/Addendum
33. Clinical Audit
34. Break-Glass Access Foundation
35. Clinical API
36. Reporting
37. FHIR/HL7/DICOM readiness
38. AI integration extension points

---

# 7. ENCOUNTER DATA MODEL

Create or adapt:

## encounters

Suggested fields:

- id
- organization_id
- hospital_id
- branch_id
- encounter_number
- patient_id
- appointment_id nullable
- encounter_type_id
- provider_id
- department_id
- specialty_id nullable
- encounter_date
- start_at nullable
- end_at nullable
- status
- priority
- source
- reason_for_visit
- created_by
- completed_by nullable
- completed_at nullable
- locked_at nullable
- locked_by nullable
- created_at
- updated_at

Use foreign keys wherever appropriate.

---

# 8. ENCOUNTER TYPES

Support configurable encounter types such as:

- OPD
- Walk-in
- Follow-up
- Emergency
- Referral
- Consultation
- Internal Transfer
- External Referral
- Telemedicine-ready type if supported by the existing architecture

Do not hardcode these values throughout the application.

Use the existing master-data/configuration architecture where appropriate.

---

# 9. ENCOUNTER NUMBER

Generate a unique immutable encounter number.

Example:

ENC-2026-00000123

Requirements:

- Unique
- Concurrency-safe
- Never generated using COUNT()
- Never reused
- Never changed after creation

Use an appropriate sequence/generator mechanism.

---

# 10. ENCOUNTER STATUS

Implement controlled lifecycle states:

- Draft
- Registered
- Waiting
- In Progress
- Paused
- Completed
- Cancelled
- Transferred
- Amended
- Locked

Typical workflow:

Registered
→ Waiting
→ In Progress
→ Completed
→ Locked

Pause:

In Progress
→ Paused
→ In Progress

Cancellation:

Waiting
→ Cancelled

Transfer:

Waiting/In Progress
→ Transferred

Amendment:

Locked
→ Amendment/Addendum process

Do not allow arbitrary status modification.

Implement a dedicated service such as:

EncounterLifecycleService

with methods conceptually similar to:

- register()
- start()
- pause()
- resume()
- complete()
- cancel()
- transfer()
- lock()
- amend()

Every transition must validate whether the transition is allowed.

---

# 11. ENCOUNTER STATUS HISTORY

Create:

encounter_status_history

Suggested fields:

- id
- encounter_id
- from_status
- to_status
- reason
- changed_by
- changed_at
- metadata

Every important lifecycle transition must be recorded.

---

# 12. CLINICAL DATA TABLES

Use separate clinical tables rather than putting the entire medical record into the encounters table.

Recommended tables:

- encounter_complaints
- encounter_histories
- encounter_examinations
- encounter_review_of_systems
- encounter_vitals
- encounter_assessments
- encounter_diagnoses
- patient_problems
- encounter_procedures
- clinical_orders
- clinical_order_items
- prescriptions
- prescription_items
- encounter_referrals
- encounter_instructions
- encounter_notes
- encounter_documents
- encounter_amendments

Reuse existing Phase 1 patient history structures where appropriate instead of duplicating patient-level data.

---

# 13. CHIEF COMPLAINT

Create:

encounter_complaints

Fields:

- id
- encounter_id
- complaint
- duration
- duration_unit
- onset
- severity
- location
- notes
- sort_order
- created_at
- updated_at

Requirements:

- Multiple complaints
- Reordering
- Primary complaint indication if useful
- Audit changes
- Preserve history

---

# 14. HISTORY OF PRESENT ILLNESS

Support structured HPI fields:

- onset
- duration
- course
- severity
- associated symptoms
- aggravating factors
- relieving factors
- clinical notes

Also allow structured/free-text hybrid documentation.

Do not force every specialty into the same fixed HPI structure.

Clinical templates must be able to extend this.

---

# 15. PATIENT HISTORY

Support:

## Medical History

- Condition
- Diagnosis/code where applicable
- Onset
- Status
- Notes

## Surgical History

- Procedure
- Date
- Hospital
- Surgeon
- Notes

## Family History

- Condition
- Relationship
- Age
- Status
- Notes

## Social History

Examples:

- Smoking
- Alcohol
- Occupation
- Diet
- Exercise
- Lifestyle
- Other

Make these configurable.

---

# 16. MEDICATION HISTORY

Support:

- Medicine name
- Dose
- Frequency
- Route
- Duration
- Source
- Status
- Notes

Possible status values:

- Current
- Previous
- Discontinued
- Unknown

Do not couple this to pharmacy inventory in Phase 3.

---

# 17. ALLERGY REVIEW

Allergy information must be highly visible in the clinical workspace.

Show:

- Allergen
- Reaction
- Severity
- Status
- Notes

Example:

Penicillin
Reaction: Rash
Severity: Severe

The system must display an obvious allergy warning before prescription/order actions where appropriate.

Reuse the patient allergy source of truth established in Phase 1.

Do not silently duplicate allergy data.

---

# 18. VITAL SIGNS

Create:

encounter_vitals

Suggested fields:

- id
- encounter_id
- patient_id
- temperature
- temperature_unit
- pulse
- respiratory_rate
- systolic_bp
- diastolic_bp
- spo2
- weight
- weight_unit
- height
- height_unit
- bmi
- pain_score
- recorded_by
- recorded_at

Requirements:

- Multiple vital measurements per encounter
- Never overwrite previous clinical observations
- Show measurement history
- Store who recorded the observation
- Store timestamp
- Support future trend graphs

BMI may be calculated but should not replace the underlying height/weight measurements.

---

# 19. CLINICAL EXAMINATION

Implement configurable examination sections.

Examples:

- General Examination
- HEENT
- Cardiovascular
- Respiratory
- Abdomen
- Neurological
- Musculoskeletal
- Skin
- Other

Each section should support:

- Normal
- Abnormal
- Not Assessed
- Notes

Do not hardcode specialty-specific examination workflows.

---

# 20. REVIEW OF SYSTEMS

Provide configurable categories such as:

- Constitutional
- Respiratory
- Cardiovascular
- Gastrointestinal
- Genitourinary
- Neurological
- Musculoskeletal
- Skin
- Psychiatric
- Other

Each can support:

- Normal
- Abnormal
- Not Assessed
- Notes

---

# 21. CLINICAL ASSESSMENT

Assessment must be separate from diagnosis.

Support:

- Clinical assessment text
- Differential considerations
- Clinical reasoning
- Plan
- Risk/priority notes where appropriate

Do not automatically convert assessment text into diagnosis records.

---

# 22. DIAGNOSIS

Create:

encounter_diagnoses

Support:

- diagnosis_code
- diagnosis_name
- coding_system
- diagnosis_type
- is_primary
- onset_date
- status
- notes
- recorded_by
- recorded_at

Diagnosis types:

- Primary
- Secondary
- Differential
- Historical

The architecture must support:

- ICD-10
- ICD-11
- SNOMED CT
- Other coding systems

Do not hardcode the application around one coding system.

---

# 23. PATIENT PROBLEM LIST

Create or reuse:

patient_problems

Suggested fields:

- id
- patient_id
- problem_code
- problem_name
- coding_system
- status
- onset_date
- resolved_date
- source_encounter_id
- notes
- created_by
- updated_by

Important:

Do not automatically make every encounter diagnosis a permanent patient problem unless the application's business rules explicitly require it.

---

# 24. PROCEDURES

Support encounter-level procedures:

- Procedure code
- Procedure name
- Date/time
- Provider
- Status
- Notes

Prepare the architecture for future procedure coding standards.

---

# 25. CLINICAL ORDERS FRAMEWORK

Phase 3 should create the generic order abstraction.

Create:

clinical_orders

Suggested fields:

- id
- organization_id
- hospital_id
- branch_id
- order_number
- patient_id
- encounter_id
- provider_id
- order_type
- priority
- status
- ordered_at
- ordered_by
- cancelled_at
- cancelled_by
- notes
- created_at
- updated_at

Possible future order types:

- Laboratory
- Radiology
- Procedure
- Referral
- Medication
- Other

Do NOT implement complete Laboratory or Radiology modules in Phase 3.

Phase 3 establishes the interface those future modules will consume.

---

# 26. PRESCRIPTION

Create:

prescriptions

Fields:

- id
- prescription_number
- patient_id
- encounter_id
- provider_id
- status
- issued_at
- valid_until
- instructions
- created_at
- updated_at

Create:

prescription_items

Fields:

- id
- prescription_id
- medicine_code
- medicine_name
- dose
- dose_unit
- route
- frequency
- duration
- duration_unit
- quantity
- is_prn
- instructions
- start_date
- end_date
- status

Prescription statuses:

- Draft
- Issued
- Cancelled
- Expired
- Dispensed
- Partially Dispensed

Do not implement pharmacy stock/inventory logic in Phase 3.

Do not couple medicine selection directly to inventory IDs.

---

# 27. REFERRAL

Support:

- Internal referral
- External referral
- Department referral
- Provider referral

Fields should include:

- patient
- encounter
- referring provider
- destination department/provider
- referral reason
- urgency
- notes
- status
- created_at

Prepare for a future dedicated Referral Management module.

---

# 28. FOLLOW-UP

Support:

- Follow-up required
- Follow-up date
- Follow-up interval
- Instructions

Provide:

> Book Follow-Up

This action should integrate with Phase 2 scheduling.

Do NOT directly manipulate appointment slots from the clinical controller.

Use a service/interface between clinical and scheduling domains.

---

# 29. PATIENT INSTRUCTIONS

Support configurable instructions such as:

- Medication instructions
- Diet
- Lifestyle
- Warning signs
- Follow-up
- Other clinical instructions

Instructions should be associated with the encounter.

---

# 30. CLINICAL NOTES

Support structured note sections:

- Chief Complaint
- HPI
- History
- Examination
- Assessment
- Plan

Allow specialty-specific templates.

Clinical notes must retain author and timestamps.

---

# 31. CLINICAL TEMPLATES

Implement configurable clinical templates.

Example templates:

- General Medicine
- Pediatrics
- Cardiology
- Dermatology
- Orthopedics
- Gynecology

Template sections may include:

- Chief Complaint
- HPI
- ROS
- General Examination
- Cardiovascular
- Respiratory
- Abdomen
- Assessment
- Plan

Store templates as configuration/data.

Do NOT hardcode specialty-specific business logic into controllers.

---

# 32. DOCTOR WORKSPACE

Create a clinical workspace optimized for physicians.

Patient header should display:

- Patient name
- MRN
- Age
- Gender
- Blood group if available
- Allergy alerts
- Important alerts
- Current encounter

Tabs:

1. Overview
2. Chief Complaint
3. History
4. Vitals
5. Examination
6. Assessment
7. Diagnosis
8. Problems
9. Orders
10. Prescription
11. Procedures
12. Referral
13. Instructions
14. Follow-Up
15. Documents
16. Summary
17. Audit

Use reusable Blade components where practical.

---

# 33. NURSE / ASSISTANT WORKFLOW

Support:

Patient
→ Queue
→ Open Encounter
→ Record Vitals
→ Initial Assessment
→ Mark Ready for Doctor

Nurses/assistants must not automatically receive physician-level permissions.

Authorization must be enforced server-side.

---

# 34. DOCTOR QUEUE

Reuse Phase 2 queue infrastructure.

Doctor workspace should show:

- Waiting patients
- Current patient
- Priority
- Waiting time
- Appointment information
- Encounter type
- Status

Do not create a second independent queue system if Phase 2 already contains one.

---

# 35. ENCOUNTER SUMMARY

Generate a summary from underlying records.

Summary should include:

- Patient
- Encounter
- Chief Complaint
- Relevant History
- Vitals
- Examination
- Assessment
- Diagnosis
- Procedures
- Orders
- Prescription
- Follow-Up
- Instructions

Important:

> The summary must NOT become a second source of truth.

It should be generated from the actual clinical records.

---

# 36. CLINICAL TIMELINE

Patient clinical timeline should show:

Appointment
↓
Encounter
↓
Diagnosis
↓
Prescription
↓
Orders
↓
Follow-up

Use references to actual records.

Do not duplicate clinical data unnecessarily.

---

# 37. DOCUMENTS

Support clinical documents such as:

- Consultation Summary
- Referral Letter
- Prescription
- Clinical Certificate
- Medical Note

Use the existing Phase 0 file management system.

Do not create a completely separate file storage architecture.

---

# 38. ENCOUNTER LOCKING

This is a critical clinical safety requirement.

When an encounter is completed:

1. It may be marked completed.
2. It becomes locked according to the configured workflow.
3. Normal users cannot silently modify historical clinical records.

Locked records must remain immutable.

If correction is required:

Use an amendment/addendum workflow.

---

# 39. CLINICAL AMENDMENT

Create:

encounter_amendments

Suggested fields:

- id
- encounter_id
- original_record_type
- original_record_id
- amendment_type
- reason
- content
- created_by
- created_at
- approved_by nullable
- approved_at nullable

Never silently rewrite the original clinical documentation.

The UI must clearly show:

> Original Record

and

> Amendment/Addendum

with author and timestamp.

---

# 40. BREAK-GLASS ACCESS

Create the foundation for emergency access to restricted clinical records.

Normal flow:

Access denied
→ Break Glass
→ Reason required
→ Temporary access
→ Security audit

Requirements:

- Mandatory reason
- User identity
- Patient/record
- Timestamp
- IP
- User agent
- Scope
- Expiration where applicable
- Security event

Break-glass must never bypass auditing.

Create a dedicated permission such as:

clinical.break_glass

---

# 41. CLINICAL AUDIT

Use the existing audit framework where available.

Record events such as:

- EncounterCreated
- EncounterViewed
- EncounterStarted
- EncounterPaused
- EncounterResumed
- EncounterCompleted
- EncounterLocked
- ClinicalNoteCreated
- ClinicalNoteUpdated
- VitalRecorded
- DiagnosisAdded
- DiagnosisChanged
- ProcedureAdded
- ClinicalOrderCreated
- ClinicalOrderCancelled
- PrescriptionCreated
- PrescriptionIssued
- PrescriptionCancelled
- ReferralCreated
- FollowUpCreated
- ClinicalDocumentGenerated
- ClinicalRecordExported
- ClinicalRecordPrinted
- BreakGlassAccess
- ClinicalAmendmentCreated

Do not place excessive clinical content into generic audit records.

Store references to the affected entity where possible.

---

# 42. PERMISSIONS

Implement or reuse permissions such as:

## Encounter

- encounter.view
- encounter.create
- encounter.update
- encounter.start
- encounter.complete
- encounter.cancel
- encounter.amend
- encounter.lock
- encounter.export
- encounter.print

## Clinical

- clinical.note.view
- clinical.note.create
- clinical.note.update
- clinical.vitals.view
- clinical.vitals.create
- clinical.diagnosis.view
- clinical.diagnosis.create
- clinical.diagnosis.update
- clinical.order.view
- clinical.order.create
- clinical.order.cancel
- clinical.referral.view
- clinical.referral.create
- clinical.break_glass

## Prescription

- prescription.view
- prescription.create
- prescription.issue
- prescription.cancel

Use the existing permission naming architecture if it differs.

---

# 43. API

Base URL:

/api/v1/encounters

Implement appropriate endpoints.

Examples:

GET    /encounters
POST   /encounters
GET    /encounters/{encounter}
POST   /encounters/{encounter}/start
POST   /encounters/{encounter}/pause
POST   /encounters/{encounter}/resume
POST   /encounters/{encounter}/complete
POST   /encounters/{encounter}/cancel
POST   /encounters/{encounter}/transfer

GET/POST /encounters/{encounter}/vitals
GET/POST /encounters/{encounter}/diagnoses
GET/POST /encounters/{encounter}/orders
GET/POST /encounters/{encounter}/prescription
GET     /encounters/{encounter}/summary
POST    /encounters/{encounter}/amendment

Follow the existing Phase 0 API response structure.

Success:

{
    "success": true,
    "message": "Operation successful.",
    "data": {},
    "meta": {}
}

Validation:

{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}

Never expose:

- SQL errors
- Stack traces
- File paths
- Secrets
- Internal infrastructure details

---

# 44. EVENTS

Implement domain events where appropriate:

- EncounterCreated
- EncounterStarted
- EncounterCompleted
- EncounterLocked
- DiagnosisAdded
- VitalRecorded
- ClinicalOrderCreated
- PrescriptionCreated
- PrescriptionIssued
- ReferralCreated
- ClinicalAmendmentCreated

Reuse the project's existing event architecture.

---

# 45. QUEUE JOBS

Where appropriate, create jobs such as:

- GenerateClinicalDocument
- GeneratePrescriptionPDF
- GenerateEncounterSummary
- SendFollowUpNotification
- SendReferralNotification
- ExternalClinicalIntegration

Do not execute expensive document generation synchronously if the existing architecture supports asynchronous processing.

---

# 46. REPORTING

Implement reporting foundations for:

## OPD

- Daily OPD Patients
- New vs Follow-up
- Department OPD Statistics
- Provider Workload
- Encounter Status
- Waiting Time
- Consultation Time
- Encounter Completion

## Clinical

- Diagnosis Statistics
- Prescription Statistics
- Follow-up Statistics
- Referral Statistics
- Clinical Order Statistics

KPIs may include:

- Average Waiting Time
- Average Consultation Time
- Patients per Provider
- New vs Follow-up
- Encounter Completion Rate
- No-show Rate
- Diagnosis Distribution
- Prescription Rate
- Referral Rate
- Order Rate

Keep reporting definitions transparent and configurable.

Do not make unsupported clinical-quality judgments from these metrics.

---

# 47. FHIR / HL7 / DICOM READINESS

Do not implement a complete FHIR server in Phase 3.

However, structure the data so that future mappings are possible.

Prepare mappings for:

- Patient
- Encounter
- Practitioner
- PractitionerRole
- Condition
- Observation
- Procedure
- MedicationRequest
- ServiceRequest
- DiagnosticReport
- DocumentReference
- CarePlan
- Appointment

Prepare clinical orders for future:

- HL7 integration
- Laboratory integration
- Radiology integration
- DICOM/PACS integration

Avoid vendor-specific coupling.

---

# 48. AI READINESS

Future AI functionality may include:

- Clinical summary assistance
- Note drafting assistance
- Coding assistance
- Patient history summarization
- Clinical documentation assistance

However:

AI must never silently:

- Finalize diagnosis
- Issue prescriptions
- Alter clinical records
- Override clinician decisions
- Change patient history

AI-generated content must be:

- Clearly identified
- Reviewable
- Editable by authorized clinicians
- Auditable
- Explicitly approved before becoming part of the official clinical record

Design extension points but do not implement unsafe autonomous clinical decision-making.

---

# 49. USER INTERFACE

Use the existing AdminLTE layout.

Add menu:

## Clinical

- Dashboard
- Doctor Queue
- My Encounters
- New Encounter
- Encounter List
- Patient History
- Clinical Templates
- Diagnoses
- Problem List
- Clinical Orders
- Prescriptions
- Referrals
- Follow-Ups
- Clinical Reports
- Settings

Respect role/permission-based menu visibility.

---

# 50. BLADE COMPONENTS

Where appropriate create reusable components such as:

- PatientClinicalHeader
- AllergyAlert
- PatientAlert
- EncounterHeader
- ClinicalTabs
- VitalSignsForm
- VitalHistory
- ComplaintForm
- HistoryForm
- ExaminationForm
- DiagnosisSelector
- ProblemList
- ClinicalOrderForm
- PrescriptionBuilder
- ReferralForm
- FollowUpForm
- ClinicalTimeline
- EncounterSummary
- ClinicalLockIndicator
- AmendmentDialog

Follow the existing component architecture.

---

# 51. DATABASE DESIGN REQUIREMENTS

Use:

- Foreign keys
- Appropriate indexes
- Unique constraints
- Composite indexes for common searches
- Soft deletes only where appropriate
- UTC timestamps
- Database transactions

Important indexes should include combinations involving:

- organization_id
- hospital_id
- branch_id
- patient_id
- encounter_id
- provider_id
- department_id
- encounter_date
- status

Do not add indexes blindly.

Review actual query patterns.

---

# 52. TRANSACTIONAL OPERATIONS

Use database transactions for operations such as:

### Encounter creation

Patient validation
→ Appointment validation
→ Encounter creation
→ Queue update
→ Audit/event

### Encounter completion

Validate state
→ Validate required clinical information
→ Complete encounter
→ Status history
→ Audit/event

### Prescription issue

Validate encounter
→ Validate provider permission
→ Validate prescription
→ Issue prescription
→ Audit/event

### Clinical amendment

Validate locked record
→ Create amendment
→ Audit
→ Notification if required

---

# 53. CONCURRENCY

Protect against:

- Duplicate encounter creation
- Duplicate encounter numbers
- Double completion
- Double prescription issuance
- Race conditions during appointment-to-encounter conversion

Use:

- Unique database constraints
- Transactions
- Appropriate row locking
- Idempotency where appropriate

Never rely only on frontend validation.

---

# 54. SECURITY

Clinical information is sensitive.

Implement:

- RBAC
- Hospital/branch/department scope
- Provider scope
- Minimum necessary access
- Audit logging
- Break-glass
- Secure document access
- Secure API authorization
- Session security
- CSRF protection
- Input validation
- Output escaping
- Rate limiting for APIs
- Export controls
- Print/download auditing where appropriate

Never expose patient clinical data through unauthorized endpoints.

---

# 55. TESTING REQUIREMENTS

Create automated tests.

At minimum:

## Unit tests

- Encounter number generation
- Lifecycle transitions
- Diagnosis rules
- Prescription rules
- Follow-up rules
- Locking
- Amendment rules

## Feature tests

- Create encounter from appointment
- Create walk-in encounter
- Start encounter
- Pause/resume
- Complete encounter
- Lock encounter
- Create diagnosis
- Record vitals
- Create prescription
- Issue prescription
- Create referral
- Create follow-up
- Create amendment
- Break-glass access

## Security tests

Most important:

> Hospital A user cannot access Hospital B clinical records.

Also test:

- Unauthorized doctor access
- Unauthorized prescription issuance
- Unauthorized amendment
- Unauthorized export
- Unauthorized break-glass
- Direct API authorization bypass

## Immutability tests

Verify:

- Locked clinical records cannot be silently modified.
- Amendments preserve original data.
- Audit records are created.

## API tests

Test:

- Authentication
- Authorization
- Validation
- Success responses
- Error responses
- Pagination
- Scope enforcement

---

# 56. PERFORMANCE

Avoid N+1 queries.

Use appropriate:

- eager loading
- pagination
- indexes
- query scopes
- caching where appropriate

Doctor queue and patient clinical timeline must remain performant with large datasets.

Do not load the patient's entire medical history unnecessarily.

Load data by tab/section when practical.

---

# 57. DATA INTEGRITY

Clinical records should not be physically deleted casually.

For important clinical data:

Prefer:

- status changes
- amendment
- cancellation
- archival
- audit

over destructive deletion.

If deletion is required for non-clinical temporary records, enforce authorization and audit it.

---

# 58. IMPLEMENTATION PROCESS

Follow this sequence.

## Step 1 — Repository Analysis

Inspect the entire project structure.

Identify:

- Laravel version
- PHP version
- database
- module structure
- authentication
- authorization
- organization hierarchy
- patient module
- appointment module
- audit
- file management
- notifications
- API architecture
- testing architecture

Before changing code, produce a concise implementation plan based on the actual repository.

## Step 2 — Architecture Mapping

Map Phase 3 requirements to existing classes.

Example:

Patient → existing Patient model
Appointment → existing Appointment model
Department → existing Department model
Provider → existing provider/user architecture
Audit → existing AuditService
File → existing FileService

Do not duplicate them.

## Step 3 — Database

Create migrations.

Add:

- encounters
- encounter_status_history
- clinical tables
- diagnosis structures
- orders
- prescriptions
- referrals
- instructions
- notes
- amendments
- any required configuration tables

Add indexes and constraints.

## Step 4 — Models

Create Eloquent models and relationships.

## Step 5 — Services

Create services such as:

- EncounterService
- EncounterLifecycleService
- ClinicalNoteService
- VitalService
- DiagnosisService
- ClinicalOrderService
- PrescriptionService
- ReferralService
- FollowUpService
- ClinicalAmendmentService
- ClinicalAccessService

Reuse existing services where available.

## Step 6 — Authorization

Create/update:

- Policies
- Gates
- Permission checks
- Scope checks

## Step 7 — Form Requests

Implement validation using Laravel Form Requests.

## Step 8 — Controllers

Keep controllers thin.

Controllers should primarily:

1. Receive request
2. Authorize
3. Validate
4. Call service
5. Return response/view

## Step 9 — Events/Jobs

Implement required events and asynchronous jobs.

## Step 10 — Blade UI

Implement the clinical workspace and related screens.

## Step 11 — API

Implement REST API endpoints.

## Step 12 — Reports

Implement initial OPD/clinical reports.

## Step 13 — Tests

Run the full relevant test suite.

## Step 14 — Documentation

Document:

- Installation
- Migrations
- Configuration
- Permissions
- Workflows
- API
- Clinical lifecycle
- Security
- Testing

---

# 59. REQUIRED SERVICES

Where appropriate, target a structure similar to:

app/
├── Domain/
│   └── Clinical/
│       ├── Models/
│       ├── Services/
│       ├── Actions/
│       ├── Policies/
│       ├── Events/
│       ├── Listeners/
│       ├── Jobs/
│       ├── DTOs/
│       └── Exceptions/

If the existing project uses another module architecture, follow the established project structure rather than forcing this exact directory layout.

---

# 60. CLINICAL EXCEPTIONS

Create domain-specific exceptions where useful, for example:

- InvalidEncounterTransitionException
- EncounterLockedException
- UnauthorizedClinicalAccessException
- ClinicalRecordAmendmentRequiredException
- InvalidPrescriptionStateException
- BreakGlassReasonRequiredException

Map exceptions to safe API/UI responses.

---

# 61. ACCEPTANCE CRITERIA

Phase 3 is complete only when all of the following work.

### Encounter

- Create encounter from appointment
- Create walk-in encounter
- Generate unique encounter number
- Assign provider
- Assign department
- Set priority
- Track lifecycle

### Clinical Documentation

- Chief complaint
- HPI
- Medical history
- Surgical history
- Family history
- Social history
- Medication history
- Allergy review
- Vitals
- Examination
- ROS
- Assessment
- Diagnosis
- Problem list
- Procedures

### Clinical Actions

- Orders
- Prescription
- Referral
- Follow-up
- Instructions
- Documents

### Workflow

- Doctor queue
- Nurse workflow
- Encounter start
- Pause/resume
- Completion
- Locking
- Amendment

### Security

- RBAC
- Hospital scope
- Branch scope
- Department scope
- Provider scope
- Break-glass
- Audit

### API

- REST endpoints
- Validation
- Authorization
- Pagination
- Standard responses

### Quality

- Automated tests
- No major N+1 problems
- Database constraints
- Proper indexes
- Clean migrations
- Documentation
- No duplicate Phase 0–2 functionality

---

# 62. IMPORTANT NON-GOALS

Do NOT implement full:

- Laboratory Management
- Radiology/PACS
- Pharmacy Inventory
- Pharmacy Dispensing
- Billing
- Insurance
- IPD
- Bed Management
- Nursing Management
- OT
- ICU
- Blood Bank

Only establish the interfaces/framework required for future modules.

---

# 63. DEFINITION OF DONE

Do not declare Phase 3 complete merely because the code compiles.

Phase 3 is complete only after:

1. Existing Phase 0–2 functionality is verified.
2. Database migrations run successfully.
3. Seeders/configuration work.
4. Encounter lifecycle works.
5. Clinical workspace works.
6. Doctor/nurse authorization works.
7. Hospital/branch/department scope works.
8. Clinical records are audited.
9. Locked records are protected.
10. Amendments preserve originals.
11. Break-glass is audited.
12. API endpoints work.
13. Automated tests pass.
14. Security tests pass.
15. No major N+1 queries are identified.
16. Documentation is updated.

Do not claim tests passed unless they were actually executed.

---

# 64. FINAL IMPLEMENTATION REPORT

After implementation, provide a concise report containing:

## 1. Implemented Features

List all completed Phase 3 capabilities.

## 2. Database Changes

List:

- New tables
- Modified tables
- Important indexes
- Foreign keys

## 3. Application Changes

List:

- Models
- Services
- Policies
- Controllers
- Requests
- Events
- Jobs
- Blade components
- API routes

## 4. Permissions

List all added/modified permissions.

## 5. Testing

Report:

- Tests executed
- Tests passed
- Tests failed
- Security tests
- Any known limitations

Do not claim success for tests that were not actually run.

## 6. Migration Instructions

Provide exact commands required.

## 7. Deployment Notes

Include:

- Environment changes
- Queue requirements
- Scheduler requirements
- Cache requirements
- Storage requirements

## 8. Remaining Work

Clearly identify anything intentionally deferred to later phases.

---

# 65. FINAL INSTRUCTION TO THE AI CODING AGENT

Treat this as a production healthcare system.

Do not take shortcuts that compromise:

- Patient safety
- Clinical data integrity
- Privacy
- Authorization
- Auditability
- Historical record integrity
- Multi-hospital isolation
- Maintainability

Before coding, inspect the existing Phase 0–2 implementation.

Reuse existing architecture.

Implement Phase 3 incrementally.

Do not rewrite unrelated modules.

Do not introduce unnecessary dependencies.

Do not silently modify existing database behavior.

Do not make assumptions about existing classes when they can be inspected.

When an architectural conflict is discovered, document the conflict and choose the least disruptive solution consistent with the existing architecture.

At the end, provide the implementation report described above.

**Begin with repository analysis and architecture mapping before modifying any code.**