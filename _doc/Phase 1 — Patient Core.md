# Phase 1 — Patient Core

## 1. Purpose

Phase 1 establishes the **single source of truth for patient identity and demographic information** across the entire Hospital ERP.

Every future module—Appointments, OPD, Emergency, IPD, Laboratory, Radiology, Pharmacy, Billing, Insurance, OT, ICU, etc.—must reference the patient created by this module.

### Core principle

> **One patient = one Master Patient Index (MPI) record.**

A patient may have many encounters, appointments, prescriptions, investigations, admissions, invoices, insurance claims, and documents, but these must all reference the same patient identity.

---

# 2. Phase 1 Scope

### Included

1. Patient Registration
2. Master Patient Index (MPI)
3. Patient Number / MRN
4. Patient Demographics
5. Patient Identifiers
6. Contact Information
7. Address Management
8. Emergency Contacts
9. Next of Kin
10. Guardian Information
11. Patient Photo
12. Patient Status
13. Patient Alerts
14. Allergies — basic identity-level record only
15. Patient Documents
16. Consent Management
17. Patient Preferences
18. Communication Preferences
19. Language Preferences
20. Patient Flags
21. Duplicate Patient Detection
22. Duplicate Review Queue
23. Patient Merge
24. Patient Correction / Amendment
25. Patient Deactivation
26. Patient Reactivation
27. Patient Search
28. Advanced Patient Search
29. Patient 360° Profile
30. Patient Timeline foundation
31. Patient Audit History
32. Patient Data Export
33. Patient Printouts
34. Patient Barcode / QR Code
35. Patient Portal identity foundation
36. API endpoints
37. Notifications
38. Access control
39. Data privacy
40. Reporting

---

# 3. Explicitly Out of Scope

Do **not** implement these as full modules in Phase 1:

- OPD consultation
- Emergency treatment
- Inpatient admission
- Bed management
- Clinical encounters
- Doctor notes
- Nursing notes
- Laboratory orders
- Laboratory results
- Radiology orders
- Radiology reports
- Pharmacy dispensing
- Billing
- Insurance claims
- Surgery
- ICU
- Blood Bank
- Diet
- Clinical decision support

However, Phase 1 should provide the data structures required for these future modules.

---

# 4. Patient Identity Architecture

The central entity is:

```text
Organization
    │
    └── Hospital
          │
          └── Branch
                │
                └── Patient
                       ├── Identifiers
                       ├── Contacts
                       ├── Addresses
                       ├── Emergency Contacts
                       ├── Documents
                       ├── Consents
                       ├── Alerts
                       ├── Preferences
                       └── Audit History
```

The patient should belong to the organization and have a hospital/branch context.

Recommended:

```text
organization
    ↓
hospital
    ↓
branch
    ↓
patient
```

However, do **not** make the patient identity itself branch-specific.

A patient registered at:

```text
Hospital A / Branch 1
```

must be recognizable when they later visit:

```text
Hospital A / Branch 2
```

This is particularly important for the MPI.

---

# 5. Patient Number / MRN

Every patient receives a unique **Medical Record Number (MRN)**.

Example:

```text
DHC-00000001
DHC-00000002
DHC-00000003
```

or:

```text
PAT-2026-000001
```

Recommended architecture:

```text
patients.id
```

is the immutable internal primary key.

```text
patients.mrn
```

is the human-facing identifier.

### Rules

MRN must:

- be unique
- never be reused
- never change
- not contain personally sensitive information
- be searchable
- be printable as barcode/QR
- be usable by all future modules

Do not use:

```text
patient_name
phone
NID
date_of_birth
```

as the primary patient identifier.

---

# 6. Patient Registration

## Registration workflow

```text
Search Existing Patient
        │
        ├── Found → Open Patient
        │
        └── Not Found
                │
                ↓
        Start Registration
                │
                ↓
        Enter Demographics
                │
                ↓
        Duplicate Detection
                │
          ┌─────┴─────┐
          │           │
       Possible      No Match
       Duplicate        │
          │             ↓
          ↓         Create Patient
     Review Queue
```

---

# 7. Registration Types

Support:

### New Patient

First registration.

### Existing Patient

Search and retrieve existing MPI record.

### Temporary Patient

Useful for unidentified emergency patients.

Example:

```text
TEMP-2026-000123
```

Later:

```text
Temporary Patient
        ↓
Identity Confirmed
        ↓
Convert / Merge
        ↓
Permanent Patient
```

### Unknown Patient

For patients whose identity is initially unknown.

Example:

```text
Unknown Male
Unknown Female
Unknown Child
```

The record must support later identification.

---

# 8. Patient Demographics

Core fields:

```text
MRN
Patient Type
First Name
Middle Name
Last Name
Preferred Name
Full Name
Date of Birth
Age
Age Precision
Gender
Sex
Marital Status
Blood Group
Rh Factor
Nationality
Religion
Occupation
Education
Preferred Language
Communication Language
Birth Country
Birth Place
```

### Important design rule

Do not store age as the authoritative value.

Store:

```text
date_of_birth
```

and calculate age dynamically.

For patients with unknown DOB:

```text
dob_unknown = true
estimated_age
age_unit
```

Example:

```text
estimated_age = 5
age_unit = years
```

---

# 9. Name Management

Support structured names:

```text
first_name
middle_name
last_name
preferred_name
```

Also maintain:

```text
display_name
```

Display name should be generated according to localization rules.

Example:

```text
Md. Abdullah Rahman
```

The system should support:

- English
- Bangla
- Arabic-ready names
- Unicode
- local naming conventions

Avoid assuming:

```text
First Name + Last Name
```

is universally correct.

---

# 10. Gender and Sex

Do not hardcode gender values in PHP.

Use master data.

Example:

```text
Male
Female
Other
Unknown
Not stated
```

If clinical requirements later distinguish:

```text
Administrative Gender
Biological Sex
```

the architecture should allow it.

---

# 11. Patient Identifiers

Patients may have multiple identifiers.

Examples:

```text
National ID
Passport
Birth Registration
Driving License
Insurance ID
Hospital Card
Previous Hospital MRN
Employer ID
Student ID
Other
```

Table:

```text
patient_identifiers
```

Suggested fields:

```text
id
patient_id
identifier_type_id
identifier_value
issuing_authority
country_id
issue_date
expiry_date
is_primary
is_verified
verified_at
verified_by
status
created_at
updated_at
```

### Security

Sensitive identifiers should not unnecessarily appear in:

- lists
- reports
- logs
- notifications
- URLs

Where appropriate, mask:

```text
********1234
```

---

# 12. Contact Information

A patient may have multiple:

- mobile numbers
- landlines
- email addresses
- WhatsApp numbers
- other communication channels

Table:

```text
patient_contacts
```

Example:

```text
type = mobile
value = +8801XXXXXXXXX
is_primary = true
is_verified = true
```

Support:

```text
Primary
Secondary
Work
Home
Emergency
```

---

# 13. Address Management

A patient can have multiple addresses.

Examples:

```text
Permanent
Present
Work
Mailing
Guardian
Other
```

Address structure:

```text
country
division/state
district
city
upazila
postal_code
address_line_1
address_line_2
```

For Bangladesh, design the model so local administrative divisions can be represented without hardcoding Bangladesh-specific logic into the core.

---

# 14. Emergency Contact

Emergency contact should be independent from ordinary contacts.

Fields:

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

Example:

```text
Name: Abdullah Rahman
Relationship: Son
Mobile: +880...
Priority: 1
```

---

# 15. Next of Kin

Support:

```text
next_of_kin
```

with:

```text
name
relationship
contact
address
legal_authority
is_primary
```

This will become important later for:

- inpatient
- emergency
- consent
- billing
- insurance
- discharge

---

# 16. Guardian

Important for:

- children
- dependent patients
- incapacitated patients

Fields:

```text
guardian_type
name
relationship
patient_id
contact_id
legal_authority
document_id
valid_from
valid_to
```

---

# 17. Patient Photo

Support a patient photograph.

Requirements:

- private storage
- configurable maximum size
- MIME validation
- image dimension validation
- randomized filename
- authorization-controlled access
- audit access where required
- thumbnail generation

Example:

```text
patient-photo/{patient_id}/profile.webp
```

Do not expose storage paths directly.

---

# 18. Patient Status

Suggested states:

```text
Active
Inactive
Deceased
Unknown
Merged
Archived
```

Do not physically delete patients.

A patient record may have legal, clinical, financial, or audit relationships.

---

# 19. Patient Alerts

Create a generic patient alert framework.

Examples:

```text
Allergy Alert
VIP
Fall Risk
Infection Precaution
Communication Difficulty
Special Assistance
Privacy Restriction
Legal Alert
Clinical Alert
Other
```

Phase 1 should provide the framework.

Clinical modules can later add specialized alerts.

Suggested:

```text
patient_alerts
```

Fields:

```text
id
patient_id
alert_type
title
description
severity
status
start_at
expires_at
created_by
resolved_by
resolved_at
```

---

# 20. Allergies

Phase 1 can provide **basic patient-level allergy information**, but detailed clinical allergy management belongs to Clinical/EMR.

Example:

```text
Allergen
Reaction
Severity
Status
Reported By
```

Example:

```text
Penicillin
Rash
Moderate
Active
Patient reported
```

Do not implement complex clinical decision support here.

---

# 21. Patient Preferences

Support:

```text
preferred_language
preferred_contact_method
preferred_phone
preferred_email
preferred_notification_channel
communication_preferences
accessibility_requirements
```

Example:

```text
Preferred language: Bangla
Preferred contact: SMS
Appointment reminder: SMS + WhatsApp
```

---

# 22. Consent Management

Foundation already provides generic workflow/audit.

Phase 1 should introduce patient-related consent.

Examples:

```text
General Treatment Consent
Data Processing Consent
Communication Consent
Research Consent
Photography Consent
Portal Consent
Information Sharing Consent
```

Suggested table:

```text
patient_consents
```

Fields:

```text
id
patient_id
consent_type
version
status
consented_at
withdrawn_at
method
document_id
captured_by
witness_id
remarks
```

Consent must be versioned.

Never overwrite historical consent.

---

# 23. Patient Documents

Patient documents may include:

```text
National ID
Passport
Birth Certificate
Insurance Card
Referral Letter
Previous Medical Record
Consent Form
Other Identity Document
```

Use the Phase 0 File Management system.

Architecture:

```text
Patient
   ↓
Patient Document
   ↓
File Management
```

Do not build a separate storage mechanism.

---

# 24. Duplicate Patient Detection

This is one of the most important components of Phase 1.

The system must detect potential duplicate patients during registration.

Possible matching fields:

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

Use weighted matching.

Example conceptual score:

```text
NID exact match          100
Passport exact match    100
Phone exact match        80
DOB exact match          50
Name similarity          40
Address similarity       20
```

These values should be configurable.

Do **not** automatically merge patients based solely on fuzzy matching.

---

# 25. Duplicate Review Queue

Possible duplicates should go into:

```text
Patient Duplicate Review
```

Example:

```text
New Patient
   │
   ↓
Duplicate Engine
   │
   ├── No Match
   │      ↓
   │   Create
   │
   └── Possible Match
          ↓
      Review Queue
          │
       ┌──┴──┐
       ↓     ↓
    Same    Different
       │
       ↓
     Merge
```

Reviewer actions:

```text
Confirm Duplicate
Not Duplicate
Needs Investigation
Merge
Reject
```

All actions must be audited.

---

# 26. Patient Merge

Patient merge is a highly sensitive operation.

Example:

```text
Patient A
MRN: PAT-000001

Patient B
MRN: PAT-000742
```

If confirmed as duplicate:

```text
Master Patient
PAT-000001
      ↑
      │
PAT-000742
```

The surviving patient must retain:

- MRN
- demographic data
- identifiers
- contacts
- documents
- future references

The merged patient should become:

```text
status = merged
merged_into_patient_id = PAT-000001
```

### Never physically delete the duplicate.

---

# 27. Merge Rules

Before merge:

```text
Review demographic differences
Review identifiers
Review documents
Review related records
Review financial references
Review future clinical references
```

Require explicit confirmation.

For high-risk merge:

```text
Require special permission
```

Example:

```text
patient.merge
```

Only authorized roles should perform it.

---

# 28. Patient Correction / Amendment

Users should be able to request correction of patient data.

Example:

```text
Current:
DOB = 10-01-1980

Requested:
DOB = 10-01-1981
```

Do not silently overwrite critical identity information.

For sensitive fields:

```text
Change Request
     ↓
Approval
     ↓
Update
     ↓
Audit
```

Critical fields may include:

- Name
- DOB
- Gender
- NID
- Passport
- MRN-linked identifiers

---

# 29. Patient Search

Search must be extremely fast.

Support:

```text
MRN
Name
Phone
NID
Passport
Birth Registration
Email
Date of Birth
Barcode
QR
```

Example:

```text
Search Patient
[ PAT-2026-000123          ]

Results
────────────────────────────────
MRN         Name              DOB
PAT-000123  Abdullah Rahman   10/02/1980
PAT-000981  Abdullah Karim    12/08/1982
```

---

# 30. Advanced Search

Filters:

```text
Hospital
Branch
Gender
Age
Date of Birth
Blood Group
Nationality
Patient Status
Registration Date
City
District
Language
Patient Type
```

Use server-side filtering and pagination.

---

# 31. Patient 360° Profile

The Patient Profile should become the central UI used by all future modules.

### Header

```text
┌─────────────────────────────────────────────┐
│ Photo  Abdullah Rahman                      │
│       MRN: PAT-2026-000123                  │
│       Male | 46 Years | O+                  │
│       ⚠ Allergy: Penicillin                 │
└─────────────────────────────────────────────┘
```

### Tabs

```text
Overview
Demographics
Identifiers
Contacts
Addresses
Emergency Contacts
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
Documents
Consents
Audit History
```

Initially, future-module tabs can display:

```text
Module not yet enabled
```

or be hidden based on module availability.

---

# 32. Patient Timeline

Phase 1 should create the timeline framework.

Example:

```text
2026-09-22  Patient Registered
2026-09-22  Mobile Number Updated
2026-09-23  Appointment Created
2026-09-24  OPD Consultation
2026-09-24  Prescription Created
2026-09-25  Lab Result Released
```

Future modules will publish events into this timeline.

Architecture:

```text
Patient Timeline
       ↑
Domain Events
       ↑
Appointments
Clinical
Lab
Radiology
Pharmacy
Billing
IPD
```

---

# 33. Patient Barcode / QR

Generate a barcode/QR containing a safe identifier.

Prefer:

```text
MRN
```

or an opaque patient reference.

Do not put sensitive patient information directly into the QR payload.

Example:

```text
PAT-2026-000123
```

Scanning should require authentication and authorization before revealing patient information.

---

# 34. Patient Portal Foundation

Phase 1 should prepare the patient identity for future portal access.

Do not build the full portal yet.

Support:

```text
patient_portal_enabled
portal_user_id
portal_registered_at
portal_status
```

Future portal:

```text
Patient
   ↓
Portal Account
   ↓
Appointments
Reports
Prescriptions
Bills
Documents
Messages
```

---

# 35. Database Design

Recommended tables:

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

Potential supporting tables:

```text
patient_types
identifier_types
relationship_types
alert_types
consent_types
```

Master data should preferably use the Phase 0 master-data framework where appropriate.

---

# 36. Core `patients` Table

Recommended structure:

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

---

# 37. Database Constraints

Important constraints:

```text
patients.mrn UNIQUE
```

and appropriate composite indexes.

Examples:

```text
organization_id
hospital_id
primary_branch_id
status
date_of_birth
first_name
last_name
created_at
```

Identifiers:

```text
identifier_type_id
identifier_value
```

should have carefully designed indexes.

Do not assume every identifier can be globally unique because some identifier types may be local.

---

# 38. Security

Patient data is sensitive.

Implement:

### Authorization

```text
patient.view
patient.create
patient.update
patient.delete
patient.merge
patient.export
patient.print
patient.documents.view
patient.documents.manage
patient.consent.manage
patient.alert.manage
patient.amend
```

### Scope

A user assigned to:

```text
Hospital A
```

must not access:

```text
Hospital B
```

unless explicitly authorized.

---

# 39. Break-Glass Foundation

The system should prepare for emergency access.

Example:

```text
Normal Access
      ↓
Permission denied
      ↓
Emergency Access
      ↓
Reason Required
      ↓
Temporary Access
      ↓
Audit Event
```

Phase 1 can implement the framework without implementing the full clinical break-glass workflow.

---

# 40. Audit Requirements

Audit:

```text
Patient Created
Patient Viewed
Patient Updated
Patient Identifier Added
Patient Identifier Changed
Patient Contact Changed
Patient Address Changed
Patient Document Uploaded
Patient Document Viewed
Patient Document Downloaded
Consent Created
Consent Withdrawn
Alert Created
Alert Resolved
Duplicate Detected
Duplicate Reviewed
Patient Merged
Patient Amendment Requested
Patient Amendment Approved
Patient Exported
Patient Printed
```

Particularly sensitive:

```text
Patient Merge
Patient Export
Patient Document Access
Patient Identifier Changes
```

---

# 41. API Design

Base:

```text
/api/v1/patients
```

Endpoints:

```http
GET    /api/v1/patients
POST   /api/v1/patients
GET    /api/v1/patients/{patient}
PUT    /api/v1/patients/{patient}
PATCH  /api/v1/patients/{patient}
```

Identifiers:

```http
GET    /api/v1/patients/{patient}/identifiers
POST   /api/v1/patients/{patient}/identifiers
PUT    /api/v1/patients/{patient}/identifiers/{identifier}
DELETE /api/v1/patients/{patient}/identifiers/{identifier}
```

Contacts:

```http
GET    /api/v1/patients/{patient}/contacts
POST   /api/v1/patients/{patient}/contacts
```

Addresses:

```http
GET    /api/v1/patients/{patient}/addresses
POST   /api/v1/patients/{patient}/addresses
```

Search:

```http
GET /api/v1/patients/search
```

Duplicates:

```http
GET  /api/v1/patients/duplicates
POST /api/v1/patients/{patient}/duplicate-check
```

Merge:

```http
POST /api/v1/patients/merge
```

Timeline:

```http
GET /api/v1/patients/{patient}/timeline
```

Documents:

```http
GET  /api/v1/patients/{patient}/documents
POST /api/v1/patients/{patient}/documents
```

---

# 42. Patient Registration UI

Recommended workflow:

### Step 1

```text
Search Existing Patient
```

### Step 2

```text
Patient Identity
```

### Step 3

```text
Demographics
```

### Step 4

```text
Contacts
```

### Step 5

```text
Address
```

### Step 6

```text
Emergency Contact
```

### Step 7

```text
Identifiers
```

### Step 8

```text
Duplicate Check
```

### Step 9

```text
Confirmation
```

### Step 10

```text
Generate MRN
```

### Step 11

```text
Registration Complete
```

---

# 43. Registration Completion

Display:

```text
Patient Registration Successful

MRN:
PAT-2026-000123

Name:
Abdullah Rahman

Date of Birth:
10 February 1980

Hospital:
Main Hospital

Branch:
Dhaka Branch

[Print Patient Card]
[Print Barcode]
[View Patient]
[Register Another Patient]
```

---

# 44. Reports

Phase 1 reports:

### Patient Registration Report

```text
Date
MRN
Patient Name
DOB
Gender
Mobile
Hospital
Branch
Registration Date
```

### Patient Demographic Report

### Patient Status Report

### Duplicate Patient Report

### Patient Merge Report

### Patient Registration Trend

### Patient Population by Gender

### Patient Population by Age Group

### Patient Population by Branch

### Patient Population by Nationality

All exports must respect permissions and data scope.

---

# 45. Non-Functional Requirements

### Performance

Patient search should target:

```text
< 1 second
```

under normal production conditions for common indexed searches.

### Scalability

Design for:

```text
1 million+
```

patient records.

### Availability

Patient registration should remain available during normal operational load.

### Security

Follow:

- least privilege
- secure authentication
- server-side authorization
- encryption in transit
- encrypted sensitive data where appropriate
- auditability
- secure file storage

---

# 46. International Standards Readiness

The data model should be prepared for interoperability.

### FHIR

Prepare mappings for:

```text
Patient
RelatedPerson
Organization
Practitioner
Consent
DocumentReference
Communication
```

### HL7

Prepare identifiers and demographic structures for future HL7 integration.

### Privacy

Architecture should support:

```text
Consent
Access control
Audit
Data minimization
Data export
Data correction
Data retention
```

Do not claim regulatory compliance merely because these features exist; compliance depends on deployment, policies, controls, and applicable jurisdiction.

---

# 47. Phase 1 Menu

```text
Patients
│
├── Dashboard
├── Register Patient
├── Patient List
├── Search Patient
├── Master Patient Index
├── Duplicate Patients
├── Merge Requests
├── Amendment Requests
├── Patient Alerts
├── Patient Documents
├── Consents
├── Patient Reports
└── Patient Settings
```

Patient profile:

```text
Patient
│
├── Overview
├── Demographics
├── Identifiers
├── Contacts
├── Addresses
├── Emergency Contacts
├── Next of Kin
├── Guardians
├── Alerts
├── Allergies
├── Documents
├── Consents
├── Timeline
└── Audit History
```

---

# 48. Phase 1 Acceptance Criteria

Phase 1 is complete only when:

- Patient can be registered.
- Unique MRN is generated.
- Existing patient can be searched.
- Duplicate detection works.
- Duplicate review workflow works.
- Patient merge works with authorization.
- Patient amendment workflow works.
- Demographics are maintained.
- Multiple identifiers are supported.
- Multiple contacts are supported.
- Multiple addresses are supported.
- Emergency contacts are supported.
- Guardians are supported.
- Patient photo is supported.
- Patient documents are supported.
- Patient consent is versioned.
- Patient alerts work.
- Patient search is indexed.
- Patient 360 profile works.
- Patient timeline framework works.
- Barcode/QR works.
- Patient data export is permission-controlled.
- Audit logging works.
- Hospital/branch access scope works.
- API works.
- API authorization works.
- Unit tests exist.
- Feature tests exist.
- Duplicate tests exist.
- Merge tests exist.
- Cross-hospital access tests exist.
- Security tests exist.
- Documentation exists.
- Deployment instructions exist.
- Backup/restore considerations are documented.

---

# AI Implementation Prompt — Phase 1

Use the following prompt directly with your coding AI:

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