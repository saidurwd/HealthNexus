# Phase 1 — Patient Core Implementation Prompt

## Role

Act as a senior:

- Laravel Architect
- Healthcare Software Architect
- Hospital ERP Developer
- Database Architect
- Security Engineer
- API Architect
- QA/Test Engineer

You are implementing **Phase 1 — Patient Core** of an enterprise-grade Hospital Management System.

The system must be designed for long-term production use and must be capable of supporting multiple hospitals, branches, departments, users, and future clinical/financial modules.

---

# 1. Existing Architecture

Phase 0 — Foundation has already been implemented or specified.

The platform architecture is:

- PHP 8.4+
- Laravel
- MySQL or PostgreSQL
- Redis
- Laravel Queue
- Laravel Scheduler
- Blade
- AdminLTE
- REST API
- Modular Monolith
- RBAC
- Multi-organization
- Multi-hospital
- Multi-branch
- Department-level access scope
- Audit logging
- Activity logging
- Security events
- File management
- Notifications
- Workflow/approval
- Master data
- Localization
- System settings

Before writing code:

1. Inspect the existing project.
2. Understand its architecture.
3. Identify existing Foundation services.
4. Reuse existing authentication.
5. Reuse existing RBAC.
6. Reuse existing policies.
7. Reuse existing audit system.
8. Reuse existing file management.
9. Reuse existing notifications.
10. Reuse existing master-data framework.
11. Reuse existing workflow framework.
12. Reuse existing API response structure.
13. Reuse existing organization/hospital/branch scope mechanisms.

Do not create duplicate Foundation functionality.

Do not overwrite unrelated code.

---

# 2. Objective

Implement:

# Phase 1 — Patient Core

The objective is to create the Hospital's:

**Master Patient Index (MPI)**

and the complete patient identity/demographic management layer.

All future HMS modules must be able to reference the patient created here.

---

# 3. Critical Architecture Principle

Implement:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
Patient
```

But do NOT make patient identity branch-specific.

A patient registered in one branch must be identifiable in another branch of the same hospital/organization.

The patient must have a globally unique internal ID and a unique MRN.

---

# 4. Scope

Implement:

1. Patient Registration
2. Master Patient Index
3. MRN generation
4. Patient demographics
5. Patient identifiers
6. Patient contacts
7. Patient addresses
8. Emergency contacts
9. Next of kin
10. Guardians
11. Patient photo
12. Patient status
13. Patient alerts
14. Basic patient allergies
15. Patient preferences
16. Communication preferences
17. Consent management
18. Patient documents
19. Duplicate detection
20. Duplicate review queue
21. Patient merge
22. Patient amendment/correction
23. Patient search
24. Advanced patient search
25. Patient 360 profile
26. Patient timeline foundation
27. Patient barcode/QR
28. Patient portal identity foundation
29. Patient reports
30. Patient API
31. Patient audit
32. Patient security
33. Patient access scope
34. Automated tests

---

# 5. Do NOT Implement

Do not implement:

- OPD
- Emergency treatment
- IPD
- Bed management
- Clinical encounters
- Doctor consultation
- Nursing
- Laboratory
- Radiology
- Pharmacy
- Billing
- Insurance claims
- OT
- ICU
- Blood Bank
- Clinical decision support

Only create integration points required for future modules.

---

# 6. Database

Create migrations for:

```text
patients
patient_identifiers
patient_contacts
patient_addresses
patient_emergency_contacts
patient_next_of_kins
patient_guardians
patient_alerts
patient_allergies
patient_preferences
patient_consents
patient_documents
patient_duplicate_candidates
patient_merge_requests
patient_amendment_requests
patient_timeline_events
patient_portal_accounts
```

Reuse Phase 0 master-data tables where possible.

Do not create unnecessary duplicate lookup tables.

---

# 7. Patients Table

Implement approximately:

```text
id
organization_id
hospital_id
primary_branch_id

mrn

patient_type_id

first_name
middle_name
last_name
preferred_name
display_name

date_of_birth
dob_unknown
estimated_age
estimated_age_unit

gender_id
sex_id
marital_status_id

blood_group_id
rh_factor

nationality_id
preferred_language_id

occupation

status

photo_file_id

deceased_at

is_temporary
is_unknown

merged_into_patient_id

registered_at
registered_by

created_at
updated_at
deleted_at
```

Adjust field types and naming according to the existing project conventions.

Use foreign keys where appropriate.

Use indexes intelligently.

Do not create indexes blindly on every column.

---

# 8. MRN

Implement a configurable MRN generation service.

Requirements:

- unique
- immutable
- never reused
- concurrency-safe
- transaction-safe
- configurable format
- organization/hospital-aware if required
- suitable for barcode/QR

Example:

```text
PAT-2026-000001
```

Do not generate MRN using a simple `COUNT()` query.

Use a safe sequence/counter strategy.

---

# 9. Patient Registration

Implement:

```text
Search Existing Patient
        ↓
If found → Open Existing Patient
        ↓
If not found → Registration
        ↓
Duplicate Detection
        ↓
Review possible duplicate
        ↓
Create patient
        ↓
Generate MRN
```

Registration must be transactional.

If any critical operation fails, rollback the patient creation.

---

# 10. Patient Types

Support:

```text
Regular
Temporary
Unknown
VIP
Corporate
Other
```

Prefer configurable master data where appropriate.

---

# 11. Demographics

Support:

```text
First Name
Middle Name
Last Name
Preferred Name
Display Name

Date of Birth
Unknown DOB
Estimated Age
Age Unit

Gender
Sex
Marital Status

Blood Group
Rh Factor

Nationality
Preferred Language
Occupation
```

Do not store calculated age as the authoritative demographic value.

---

# 12. Names

Support Unicode.

The application must support:

- English
- Bangla
- Arabic-ready Unicode

Do not hardcode Western naming assumptions.

---

# 13. Patient Identifiers

Implement:

```text
patient_identifiers
```

Support:

- National ID
- Passport
- Birth Registration
- Insurance ID
- Previous Hospital MRN
- Employer ID
- Student ID
- Other

Fields should include:

```text
identifier_type
identifier_value
issuing_authority
country
issue_date
expiry_date
is_primary
is_verified
verified_at
verified_by
status
```

Protect sensitive identifiers.

Do not expose them unnecessarily in lists or URLs.

---

# 14. Contacts

Implement multiple patient contacts.

Support:

```text
Mobile
Phone
Email
WhatsApp
Other
```

Include:

```text
type
value
is_primary
is_verified
verified_at
```

---

# 15. Addresses

Support multiple addresses:

```text
Permanent
Present
Work
Mailing
Other
```

Use structured geographic/master data where available.

Do not hardcode Bangladesh-only assumptions.

---

# 16. Emergency Contacts

Implement:

```text
patient_emergency_contacts
```

Support:

```text
name
relationship
mobile
phone
email
address
priority
is_primary
```

---

# 17. Next of Kin

Implement:

```text
patient_next_of_kins
```

Support relationship and contact details.

---

# 18. Guardians

Implement guardians for:

- children
- dependent patients
- patients requiring legal representatives

Support legal authority/document references.

---

# 19. Patient Photo

Reuse Phase 0 file management.

Requirements:

- private storage
- authorization
- MIME validation
- size validation
- randomized filenames
- thumbnail support
- secure retrieval

Do not expose direct storage paths.

---

# 20. Patient Alerts

Implement a generic alert framework.

Support:

```text
Alert Type
Title
Description
Severity
Status
Start Date
Expiry Date
Created By
Resolved By
Resolved At
```

All alert changes must be audited.

---

# 21. Allergies

Implement basic patient-level allergies:

```text
allergen
reaction
severity
status
reported_by
```

Do not implement clinical decision support.

---

# 22. Patient Preferences

Implement:

```text
preferred_language
preferred_contact_method
preferred_phone
preferred_email
preferred_notification_channel
accessibility_requirements
```

Allow future expansion.

---

# 23. Consent

Implement versioned patient consent.

Examples:

```text
Treatment
Communication
Data Processing
Research
Photography
Portal
Information Sharing
```

Never overwrite historical consent.

Support:

```text
consented_at
withdrawn_at
method
version
document_id
captured_by
witness_id
```

---

# 24. Documents

Reuse Phase 0 file-management functionality.

Patient documents may include:

```text
National ID
Passport
Birth Certificate
Insurance Card
Referral Letter
Consent
Previous Medical Record
Other
```

Every document access must respect authorization.

Sensitive document access should be auditable.

---

# 25. Duplicate Detection

Implement a duplicate detection service.

Potential matching attributes:

```text
National ID
Passport
Phone
Email
Date of Birth
Name
Father's Name
Mother's Name
Address
```

Implement configurable weighted matching.

Do not automatically merge based on fuzzy matching.

Return:

```text
No Match
Possible Match
Strong Match
```

Create duplicate candidates for manual review.

---

# 26. Duplicate Review

Implement a review queue.

Actions:

```text
Confirm Duplicate
Not Duplicate
Needs Investigation
Merge
Reject
```

Every decision must be audited.

---

# 27. Patient Merge

Implement secure patient merge.

Requirements:

- explicit permission
- confirmation screen
- transaction
- complete audit
- survivor selection
- relationship migration
- identifier conflict handling
- document handling
- timeline preservation
- future module reference safety

Never physically delete the duplicate patient.

Set:

```text
status = merged
merged_into_patient_id = survivor_id
```

Where existing foreign-key relationships exist, migrate them safely.

For relationships that cannot safely be migrated automatically, create a review/error workflow rather than silently losing data.

---

# 28. Amendment Workflow

Implement correction requests for sensitive patient information.

Workflow:

```text
Draft
↓
Submitted
↓
Pending Approval
↓
Approved
↓
Applied
```

or:

```text
Rejected
```

Sensitive changes must be fully audited.

---

# 29. Search

Implement fast patient search.

Search:

```text
MRN
Name
Phone
National ID
Passport
Birth Registration
Email
DOB
Barcode
QR
```

Implement:

- pagination
- sorting
- filtering
- authorization scope
- optimized queries

Avoid N+1 queries.

---

# 30. Advanced Search

Support:

```text
Hospital
Branch
Gender
Age
DOB
Blood Group
Nationality
Status
Registration Date
City
District
Language
Patient Type
```

---

# 31. Patient 360

Create:

```text
/patients/{patient}
```

with reusable patient header.

Header should display:

```text
Photo
MRN
Name
Age
Gender
Blood Group
Allergy alerts
Patient status
```

Tabs:

```text
Overview
Demographics
Identifiers
Contacts
Addresses
Emergency Contacts
Next of Kin
Guardians
Alerts
Allergies
Documents
Consents
Timeline
Audit History
```

Prepare placeholders for future:

```text
Encounters
Appointments
Diagnoses
Prescriptions
Laboratory
Radiology
Admissions
Procedures
Billing
Insurance
```

Do not implement those modules now.

---

# 32. Patient Timeline

Create the timeline infrastructure.

Implement:

```text
patient_timeline_events
```

Future modules should be able to publish timeline events.

Support:

```text
event_type
event_title
event_description
event_date
source_module
source_type
source_id
created_by
metadata
```

Do not duplicate business records unnecessarily.

The timeline should reference source records where possible.

---

# 33. Barcode / QR

Implement patient barcode/QR generation.

Payload should contain only a safe identifier, such as:

```text
MRN
```

Do not encode sensitive demographic information.

Scanning must require authentication before patient information is displayed.

---

# 34. Portal Foundation

Prepare patient portal identity.

Support:

```text
patient_portal_accounts
```

Do not implement the complete patient portal.

---

# 35. Authorization

Implement permissions such as:

```text
patient.view
patient.create
patient.update
patient.merge
patient.export
patient.print
patient.delete
patient.documents.view
patient.documents.manage
patient.consent.view
patient.consent.manage
patient.alert.view
patient.alert.manage
patient.amend
```

Do not rely on hidden UI menus.

Authorization must be enforced server-side.

---

# 36. Access Scope

A user authorized for:

```text
Hospital A
```

must not access:

```text
Hospital B
```

unless explicitly authorized.

A branch-scoped user must only see authorized branches.

Use the existing Phase 0 scope mechanism.

Do not implement ad-hoc authorization in individual controllers.

---

# 37. API

Implement:

```http
GET    /api/v1/patients
POST   /api/v1/patients
GET    /api/v1/patients/{patient}
PUT    /api/v1/patients/{patient}
PATCH  /api/v1/patients/{patient}

GET    /api/v1/patients/search

GET    /api/v1/patients/{patient}/identifiers
POST   /api/v1/patients/{patient}/identifiers

GET    /api/v1/patients/{patient}/contacts
POST   /api/v1/patients/{patient}/contacts

GET    /api/v1/patients/{patient}/addresses
POST   /api/v1/patients/{patient}/addresses

GET    /api/v1/patients/{patient}/documents
POST   /api/v1/patients/{patient}/documents

GET    /api/v1/patients/{patient}/timeline

POST   /api/v1/patients/{patient}/duplicate-check

GET    /api/v1/patients/duplicates

POST   /api/v1/patients/merge
```

Use the existing Phase 0 API response format.

---

# 38. Audit

Audit at minimum:

```text
Patient Created
Patient Viewed
Patient Updated
Identifier Added
Identifier Updated
Contact Updated
Address Updated
Document Uploaded
Document Viewed
Document Downloaded
Consent Created
Consent Withdrawn
Alert Created
Alert Resolved
Duplicate Detected
Duplicate Reviewed
Patient Merged
Amendment Requested
Amendment Approved
Patient Exported
Patient Printed
```

Do not place sensitive patient information into ordinary application logs.

---

# 39. UI Requirements

Use the existing:

```text
Blade
AdminLTE
Reusable Components
```

Build reusable components for:

```text
Patient Search
Patient Header
Patient Status Badge
Patient Alert
Patient Demographic Form
Contact Form
Address Form
Identifier Form
Document Upload
Consent Form
Duplicate Comparison
Merge Confirmation
Timeline
```

---

# 40. Validation

Use Laravel Form Requests.

Validate:

- names
- DOB
- age
- identifiers
- phone numbers
- email
- dates
- file types
- file sizes
- relationships
- hospital/branch scope

Never trust client-side validation.

---

# 41. Services

Prefer services such as:

```text
PatientRegistrationService
PatientSearchService
PatientDuplicateDetectionService
PatientMergeService
PatientAmendmentService
PatientIdentifierService
PatientContactService
PatientAddressService
PatientConsentService
PatientDocumentService
PatientTimelineService
MrnGeneratorService
PatientQrService
```

Use dependency injection.

Avoid putting business logic into controllers.

---

# 42. Events

Consider:

```text
PatientRegistered
PatientUpdated
PatientMerged
PatientAmendmentApproved
PatientConsentCreated
PatientConsentWithdrawn
PatientDocumentUploaded
PatientAlertCreated
```

Use events for future module integration.

---

# 43. Notifications

Use the Phase 0 notification framework.

Potential notifications:

```text
Patient registration confirmation
Duplicate review notification
Amendment approval notification
Amendment rejection notification
Portal registration notification
```

Do not send sensitive patient data unnecessarily through notifications.

---

# 44. Database Performance

Ensure:

- proper indexes
- eager loading
- pagination
- optimized search
- no N+1 queries
- transaction boundaries
- foreign key integrity
- safe concurrent MRN generation

Design for at least:

```text
1,000,000+ patients
```

---

# 45. Testing

Implement:

## Unit Tests

Test:

- MRN generation
- duplicate scoring
- patient merge rules
- age calculation
- identifier validation

## Feature Tests

Test:

- patient registration
- patient update
- patient search
- contact management
- address management
- document access
- consent
- duplicate workflow
- amendment workflow
- merge workflow

## Security Tests

Test:

- unauthorized patient access
- cross-hospital access
- cross-branch access
- unauthorized merge
- unauthorized export
- unauthorized document access
- unauthorized patient modification

Critical test:

```text
Hospital A user MUST NOT access Hospital B patient records.
```

---

# 46. Security Requirements

Implement:

- server-side authorization
- scope enforcement
- CSRF protection
- secure file upload
- MIME validation
- mass-assignment protection
- secure API authentication
- rate limiting where appropriate
- audit logging
- secure error messages
- no SQL errors exposed to users
- no stack traces in production
- no sensitive information in URLs
- no sensitive information in ordinary logs

---

# 47. FHIR Readiness

Prepare the patient architecture for future interoperability.

Potential mapping:

```text
patients → FHIR Patient
patient_contacts → Patient.telecom
patient_addresses → Patient.address
patient_identifiers → Patient.identifier
patient_documents → DocumentReference
patient_consents → Consent
```

Do not build the full FHIR server in Phase 1.

Keep the internal model clean and interoperable.

---

# 48. Coding Standards

Follow:

- PSR-12
- Laravel conventions
- SOLID
- DRY
- dependency injection
- service/repository patterns only where justified
- Form Requests
- Policies
- API Resources
- Events
- Jobs
- Notifications
- database transactions

Avoid unnecessary abstraction.

Do not create repositories simply for the sake of repositories.

---

# 49. UI/UX

The interface must be suitable for hospital staff.

Prioritize:

- fast patient search
- keyboard-friendly registration
- minimal unnecessary clicks
- clear warnings
- duplicate detection before creation
- responsive forms
- accessible controls
- clear patient identity
- prominent allergy/alert indicators
- confirmation for destructive operations

Never rely solely on color to communicate alerts.

---

# 50. Internationalization

All user-facing labels must be translation-ready.

Support:

```text
English
Bangla
Arabic-ready
```

Do not hardcode labels inside Blade templates where translation keys should be used.

---

# 51. Deliverables

After implementation, provide a concise implementation report containing:

## Architecture

- files created
- files modified
- modules created
- services created
- events created

## Database

- migrations
- tables
- indexes
- foreign keys

## Security

- permissions
- policies
- scope rules
- audit events

## API

- endpoints
- request classes
- resources
- authentication

## UI

- routes
- controllers
- Blade views
- reusable components

## Testing

- tests created
- test commands executed
- actual test results

Do NOT claim tests passed unless they were actually executed.

## Deployment

Provide:

```bash
php artisan migrate
php artisan db:seed
php artisan optimize
php artisan queue:restart
```

only where appropriate to the actual implementation.

Document required environment variables.

---

# 52. Documentation

Create/update:

```text
docs/PHASE-1-PATIENT-CORE.md
docs/PATIENT-MPI.md
docs/PATIENT-SECURITY.md
docs/PATIENT-API.md
docs/PATIENT-DUPLICATE-MERGE.md
```

If the project already has an equivalent documentation structure, follow it instead.

---

# 53. Final Validation Checklist

Before declaring Phase 1 complete, verify:

[ ] Patient registration works

[ ] MRN generation works

[ ] Existing patient search works

[ ] Duplicate detection works

[ ] Duplicate review works

[ ] Patient merge works

[ ] Patient amendment works

[ ] Demographics work

[ ] Identifiers work

[ ] Contacts work

[ ] Addresses work

[ ] Emergency contacts work

[ ] Next of kin works

[ ] Guardians work

[ ] Photo works

[ ] Alerts work

[ ] Allergies work

[ ] Preferences work

[ ] Consent works

[ ] Documents work

[ ] Patient timeline works

[ ] Barcode/QR works

[ ] Patient 360 works

[ ] API works

[ ] RBAC works

[ ] Hospital scope works

[ ] Branch scope works

[ ] Audit works

[ ] Security tests work

[ ] Duplicate merge is transaction-safe

[ ] No patient data is lost during merge

[ ] No unauthorized cross-hospital access exists

[ ] No N+1 query problems exist

[ ] Migrations work on a clean database

[ ] Tests are executed

[ ] Documentation is updated

[ ] Deployment instructions are documented

---

# Final Rule

Do not move into OPD, Appointment, Emergency, IPD, Billing, Pharmacy, Laboratory, Radiology, or other clinical modules.

Complete **Phase 1 — Patient Core** as a stable, secure, reusable Master Patient Index platform first.

The final result must provide a strong foundation for:

```text
Phase 0 — Foundation
        ↓
Phase 1 — Patient Core
        ↓
Phase 2 — Appointment
        ↓
Phase 3 — OPD / Clinical
        ↓
Phase 4 — Billing
        ↓
Phase 5 — IPD / Bed Management
        ↓
Future HMS Modules
```

The Patient entity created in Phase 1 must remain the central identity referenced by every future module.