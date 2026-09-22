# PHASE 6 — RADIOLOGY / RIS / PACS
## Module Specification & AI Implementation Prompt

## 1. MODULE OVERVIEW

Implement **Phase 6 — Radiology / RIS / PACS** as a production-grade radiology information and medical imaging module within the existing Hospital Management Software / Hospital ERP.

The module must support the complete radiology workflow:

```text
Patient
   ↓
Clinical Encounter
   ↓
Imaging / Radiology Order
   ↓
Billing / Charge
   ↓
Radiology Registration
   ↓
Scheduling
   ↓
Patient Preparation
   ↓
Modality / Examination
   ↓
DICOM Study
   ↓
Image Acquisition
   ↓
PACS Storage
   ↓
Radiologist Worklist
   ↓
Image Review
   ↓
Radiology Reporting
   ↓
Radiologist Approval
   ↓
Final Report
   ↓
Patient EMR / Patient 360
```

The implementation must combine:

- RIS — Radiology Information System
- DICOM workflow
- PACS integration foundation
- Modality management
- Radiology scheduling
- Examination workflow
- Radiologist worklist
- Reporting
- Critical findings
- Image/document integration
- Billing integration
- EMR integration
- FHIR/HL7 interoperability readiness

---

# 2. CRITICAL ARCHITECTURE RULE

The existing modular architecture is already implemented.

**Do not create a second architecture.**

Before implementing Phase 6:

1. Inspect the existing repository.
2. Inspect the implementation of Phases 0–5.
3. Identify existing:
   - Patient/MPI
   - Organization
   - Hospital
   - Branch
   - Department
   - User
   - Role
   - Permission
   - Appointment
   - Encounter
   - Clinical Order
   - Billing
   - Notification
   - Audit
   - File/document management
   - API
   - Queue
   - Scheduler
4. Reuse existing infrastructure.
5. Follow existing naming conventions.
6. Follow existing migration conventions.
7. Follow existing service/action conventions.
8. Follow existing authorization mechanisms.
9. Follow existing UI conventions.

Do not duplicate:

- patients
- encounters
- appointments
- invoices
- payments
- users
- authentication
- roles
- permissions
- audit logging
- notifications
- general file management

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
- Laravel Queue
- Scheduler
- Policies/Gates
- Form Requests
- Services/Actions
- Events/Listeners
- Notifications
- PHPUnit/Pest

Do not replace existing technology without a strong architectural reason.

---

# 4. MODULE RESPONSIBILITIES

Radiology/RIS/PACS owns:

- radiology master data
- imaging modality master
- radiology test/procedure catalog
- imaging protocols
- body parts
- contrast configuration
- radiology orders
- radiology registration
- scheduling
- examination workflow
- modality worklist foundation
- DICOM metadata
- study tracking
- image/PACS integration
- radiologist worklist
- reporting
- report templates
- critical findings
- report amendment
- report finalization
- radiology dashboard
- radiology operational reporting
- radiology audit
- radiology interoperability

It does not own:

- Patient/MPI
- Clinical Encounter
- General Billing
- Payment
- Pharmacy
- Inventory
- Procurement
- Accounting
- General document management
- General appointment engine

---

# 5. RADIOLOGY ARCHITECTURE

Use the following logical architecture:

```text
                    Hospital ERP
                         │
        ┌────────────────┼────────────────┐
        │                │                │
     Patient          Clinical          Billing
       MPI             Encounter        Revenue
        │                │                │
        └───────────────┬┴────────────────┘
                        │
                 Radiology Order
                        │
                        ▼
              ┌───────────────────┐
              │       RIS         │
              │                   │
              │ Registration      │
              │ Scheduling        │
              │ Examination       │
              │ Worklists         │
              │ Reporting         │
              └─────────┬─────────┘
                        │
                     DICOM
                        │
             ┌──────────┴──────────┐
             │                     │
         Modalities              PACS
             │                     │
      CT / MRI / XR / US          │
      Mammography / etc.          │
             │                     │
             └──────────┬──────────┘
                        │
                  Radiologist
                   Workstation
                        │
                        ▼
                 Final Report
                        │
                        ▼
                 Patient EMR
```

---

# 6. RIS VS PACS BOUNDARY

Keep responsibilities clear.

## RIS

Responsible for:

- orders
- scheduling
- registration
- examination workflow
- radiology worklists
- reporting
- radiologist workflow
- findings
- report lifecycle
- operational statistics

## PACS

Responsible for:

- medical image storage
- DICOM studies
- series
- instances
- image retrieval
- image metadata
- image lifecycle
- viewer integration

The HMS/RIS should **not attempt to become a full DICOM image storage engine unless explicitly required**.

Prefer integration with an existing PACS/DICOM server.

---

# 7. PACS STRATEGY

Design the module around a PACS integration model.

Possible architecture:

```text
Modality
   ↓
DICOM
   ↓
PACS
   ↓
RIS
   ↓
Study Metadata
   ↓
Radiologist Worklist
   ↓
DICOM Viewer
```

Possible PACS technologies may include:

- Orthanc
- dcm4chee
- commercial PACS
- cloud PACS
- vendor PACS

Do not hardcode one PACS vendor.

Use an adapter/interface architecture.

---

# 8. DICOM REQUIREMENTS

The system must be DICOM-ready.

Important DICOM concepts include:

- Patient
- Study
- Series
- Instance
- Study Instance UID
- Series Instance UID
- SOP Instance UID
- Accession Number
- Modality
- Study Date
- Study Time
- Referring Physician
- Institution
- Body Part Examined

Do not store DICOM identifiers as ordinary human-readable accession numbers.

Maintain separate application identifiers and DICOM identifiers.

---

# 9. RADIOLOGY MASTER DATA

Implement configurable:

- Radiology Department
- Radiology Section
- Imaging Modality
- Modality Type
- Body Part
- Procedure
- Protocol
- Contrast
- Contrast Route
- Preparation
- Position
- Report Template
- Finding Template

---

# 10. MODALITY MASTER

Create configurable modality records.

Examples:

```text
X-Ray
CT
MRI
Ultrasound
Mammography
Fluoroscopy
Angiography
PET
PET-CT
SPECT
Dental X-Ray
DEXA
```

Do not hardcode modality types.

Suggested conceptual fields:

```text
id
organization_id
hospital_id
branch_id
code
name
modality_type
manufacturer
model
serial_number
ae_title
ip_address
port
pacs_endpoint
department_id
location
status
is_active
```

---

# 11. DICOM AE TITLE

Support DICOM Application Entity configuration.

Example:

```text
Modality AE Title:
CT_SCANNER_01

PACS AE Title:
PACS_SERVER

RIS AE Title:
RIS_SERVER
```

AE titles must be configurable.

Do not embed them directly in source code.

---

# 12. MODALITY STATUS

Support:

```text
Online
Offline
Maintenance
Disabled
```

Potential future monitoring:

```text
Last DICOM communication
Last successful study
Connection status
Queue status
```

---

# 13. RADIOLOGY PROCEDURE CATALOG

Create a radiology procedure catalog.

Examples:

```text
Chest X-Ray PA
Chest X-Ray AP
CT Brain
CT Chest
CT Abdomen
MRI Brain
MRI Spine
Ultrasound Abdomen
USG Pelvis
Mammography
```

Suggested fields:

```text
id
organization_id
hospital_id
code
name
modality_type
body_part_id
description
duration_minutes
turnaround_time_minutes
contrast_required
preparation_required
sedation_required
is_active
```

---

# 14. BODY PART MASTER

Support configurable anatomical body parts.

Examples:

- Head
- Brain
- Neck
- Chest
- Abdomen
- Pelvis
- Spine
- Shoulder
- Elbow
- Wrist
- Hand
- Hip
- Knee
- Ankle
- Foot

Support laterality:

```text
Left
Right
Bilateral
Midline
Not Applicable
```

---

# 15. IMAGING PROTOCOLS

A procedure may have one or more protocols.

Example:

```text
CT Brain
 ├── Non-Contrast
 ├── Contrast
 └── Angiography
```

Store protocol-specific configuration.

Do not duplicate the procedure itself.

---

# 16. CONTRAST MANAGEMENT

Support configurable contrast agents.

Information may include:

- contrast name
- type
- concentration
- route
- default dose
- maximum dose
- manufacturer
- safety information
- active/inactive

Important:

**Do not treat this as pharmacy inventory.**

Inventory ownership remains with the future Inventory/Pharmacy modules.

RIS should record contrast administration relevant to the examination.

---

# 17. RADIOLOGY ORDER

Radiology orders originate primarily from the Clinical Order system.

Example:

```text
Doctor
 ↓
Clinical Order
 ↓
Imaging — CT Brain
 ↓
Radiology Order
```

The order should contain:

- patient
- encounter
- ordering provider
- department
- procedure
- priority
- clinical indication
- provisional diagnosis
- relevant history
- requested date
- contrast requirement
- special instructions

---

# 18. CLINICAL INDICATION

The radiologist needs clinical context.

Support:

- clinical indication
- symptoms
- relevant history
- provisional diagnosis
- previous imaging reference
- clinical question

Do not duplicate the patient's entire medical history.

---

# 19. RADIOLOGY ORDER STATUS

Use controlled status transitions.

Suggested:

```text
Ordered
Registered
Scheduled
Checked-In
Preparing
Ready
In-Progress
Completed
Images Available
Reporting
Reported
Cancelled
No-Show
Rejected
```

Not every installation needs every state.

Adapt to project requirements.

---

# 20. RADIOLOGY REGISTRATION

Workflow:

```text
Patient Verification
       ↓
Order Verification
       ↓
Billing Verification
       ↓
Radiology Registration
       ↓
Accession Number
       ↓
Scheduling
```

Generate a unique accession number.

Example:

```text
RAD-2026-00001245
```

Do not generate using:

```php
count() + 1
```

Use concurrency-safe numbering.

---

# 21. RADIOLOGY ACCESSION

The accession number is the primary radiology workflow identifier.

Requirements:

- unique
- immutable
- searchable
- hospital-aware
- audit-friendly

Do not confuse:

```text
Patient MRN
Radiology Accession Number
DICOM Study Instance UID
```

These are different identifiers.

---

# 22. RADIOLOGY SCHEDULING

Use the existing Appointment/Scheduling engine where possible.

Radiology should integrate with existing scheduling rather than creating an unrelated appointment framework.

Support:

- modality schedules
- radiologist schedules
- technician schedules
- blocked slots
- holidays
- maintenance
- emergency slots
- priority cases
- rescheduling
- cancellation
- no-show

---

# 23. MODALITY CAPACITY

Support modality-based capacity.

Example:

```text
CT-01
08:00–20:00
15-minute slots
```

MRI may require:

```text
30–60 minute slots
```

Configuration should be data-driven.

---

# 24. PATIENT PREPARATION

Support configurable preparation instructions.

Examples:

- fasting
- hydration
- bladder preparation
- metal screening
- pregnancy screening
- contrast preparation
- creatinine requirement where applicable
- sedation preparation

Preparation requirements must be configurable by procedure/protocol.

---

# 25. MRI SAFETY FOUNDATION

For MRI procedures, support safety screening.

Potential fields:

- pacemaker
- implant
- metallic foreign body
- aneurysm clip
- pregnancy status where applicable
- prior MRI safety clearance
- implant documentation
- screening completed by
- screening date/time

Do not invent clinical safety rules.

Configuration should allow the hospital to define its approved checklist.

---

# 26. CT / CONTRAST SAFETY FOUNDATION

Where contrast is used, support recording:

- contrast required
- contrast agent
- route
- dose
- administration time
- administrator
- relevant safety verification
- reaction
- reaction severity
- action taken

Do not create clinical decision rules unless explicitly specified by the hospital.

---

# 27. EXAMINATION WORKFLOW

Core workflow:

```text
Scheduled
   ↓
Patient Checked-In
   ↓
Preparation
   ↓
Ready
   ↓
Examination Started
   ↓
Image Acquisition
   ↓
Completed
   ↓
DICOM Study Available
   ↓
Radiologist Worklist
```

Record timestamps for major transitions.

These timestamps will support TAT reporting.

---

# 28. RADIOLOGY EXAMINATION

Create a radiology examination record.

Suggested fields:

```text
id
organization_id
hospital_id
branch_id
radiology_order_id
accession_number
procedure_id
modality_id
patient_id
encounter_id
scheduled_at
check_in_at
started_at
completed_at
status
technologist_id
performing_provider_id
clinical_notes
technical_notes
created_at
updated_at
```

Do not duplicate the entire patient/encounter.

---

# 29. DICOM STUDY

Create a metadata record representing the DICOM study.

Suggested:

```text
radiology_studies
```

Fields may include:

```text
id
radiology_examination_id
patient_id
accession_number
study_instance_uid
study_id
study_date
study_time
modality
study_description
body_part
referring_physician
institution_name
pacs_server_id
pacs_status
study_status
created_at
updated_at
```

Do not store the actual image binary data in the relational database unless explicitly required.

---

# 30. DICOM SERIES

Support:

```text
radiology_series
```

Fields:

```text
id
study_id
series_instance_uid
series_number
series_description
modality
body_part
number_of_instances
created_at
updated_at
```

---

# 31. DICOM INSTANCES

Where metadata tracking is required, support:

```text
radiology_instances
```

Potential fields:

```text
id
series_id
sop_instance_uid
sop_class_uid
instance_number
file_reference
created_at
updated_at
```

Do not unnecessarily replicate the complete DICOM header into SQL.

Store only metadata needed by RIS/PACS integration.

---

# 32. PACS ADAPTER

Create an abstraction such as:

```text
PacsClientInterface
```

Potential operations:

```text
findStudy()
getStudy()
getSeries()
getInstance()
getStudyViewerUrl()
sendStudy()
queryStudies()
```

Actual implementation should be adapter-based.

Example conceptual structure:

```text
PACS Interface
      │
 ┌────┼───────────────┐
 │    │               │
Orthanc dcm4chee Commercial PACS
```

Do not hardcode Orthanc or any other PACS vendor into the domain layer.

---

# 33. DICOMWEB READINESS

Prepare for modern DICOMweb APIs where applicable:

- QIDO-RS
- WADO-RS
- STOW-RS

Potential workflow:

```text
RIS
 ↓
DICOMweb
 ↓
PACS
 ↓
Web Viewer
```

Do not implement a full DICOMweb server unless required.

---

# 34. IMAGE VIEWER INTEGRATION

The RIS should provide an image-viewer launch capability.

Example:

```text
Open Study
    ↓
Get authorized viewer URL
    ↓
Launch DICOM Viewer
```

Potential viewer approaches:

- embedded viewer
- external viewer
- OHIF
- vendor viewer

The actual viewer must be configurable.

Do not expose unrestricted PACS URLs.

---

# 35. SECURE IMAGE ACCESS

Image access must be authorized.

Do not expose:

```text
http://pacs-server/study/123
```

directly to arbitrary users.

Use:

- authorization
- signed/temporary URLs where appropriate
- authenticated proxy
- PACS access controls
- audit logging

---

# 36. RADIOLOGIST WORKLIST

Create a dedicated radiologist worklist.

Filters:

- date
- priority
- modality
- body part
- procedure
- accession number
- referring department
- referring physician
- reporting status
- assigned radiologist
- unreported studies

Example:

```text
Pending Studies
Urgent Studies
Assigned to Me
Unassigned
In Reporting
Awaiting Approval
Critical Findings
```

---

# 37. RADIOLOGIST ASSIGNMENT

Support assignment of studies to radiologists.

Possible states:

```text
Unassigned
Assigned
In Reporting
Submitted
Approved
```

Record:

- assigned radiologist
- assignment time
- assigned by

---

# 38. REPORTING WORKFLOW

Core reporting flow:

```text
Study Available
      ↓
Radiologist Opens Study
      ↓
Image Review
      ↓
Findings
      ↓
Impression
      ↓
Report Draft
      ↓
Submit
      ↓
Approval
      ↓
Final Report
```

---

# 39. RADIOLOGY REPORT

Report should support:

### Clinical information

- indication
- history
- referring physician

### Examination

- procedure
- modality
- body part
- protocol
- contrast

### Findings

Free-text and structured findings.

### Impression

Clinical summary.

### Recommendation

Optional.

### Authorization

- radiologist
- reviewer
- approval timestamp

---

# 40. REPORT TEMPLATES

Create configurable report templates.

Examples:

```text
CT Brain
Chest X-Ray
MRI Brain
Ultrasound Abdomen
Mammography
```

Template sections may include:

```text
Clinical History
Technique
Findings
Measurements
Impression
Recommendation
```

Do not hardcode report templates into PHP.

---

# 41. STRUCTURED FINDINGS

Support optional structured findings.

Example:

```text
Organ: Liver
Size: ...
Echotexture: ...
Lesion: Yes/No
Lesion Size: ...
```

Structured reporting should remain extensible.

Do not force all radiologists to use structured reporting unless configured.

---

# 42. MEASUREMENTS

Support optional imaging measurements:

- lesion size
- organ size
- distance
- volume
- density
- laterality

Store appropriate numeric values and units.

Do not use free text for data that requires structured analytics when a structured field is appropriate.

---

# 43. REPORT STATUS

Use controlled states:

```text
Draft
Submitted
Under Review
Approved
Final
Amended
Cancelled
```

Do not allow arbitrary status changes.

---

# 44. REPORT IMMUTABILITY

Once a report becomes Final:

**Never silently overwrite it.**

Correction workflow:

```text
Final Report
    ↓
Amendment
    ↓
Reason
    ↓
Authorized Review
    ↓
Amended Report
```

Retain original report versions.

---

# 45. REPORT VERSIONING

Create report versions.

Track:

- version
- report text
- author
- reviewer
- timestamp
- amendment reason
- approval status

The user should be able to view report history according to authorization.

---

# 46. CRITICAL FINDINGS

Implement critical-result/critical-finding workflow.

Example:

```text
Radiologist identifies critical finding
        ↓
Critical Finding Created
        ↓
Responsible Clinician Identified
        ↓
Notification
        ↓
Acknowledgement
        ↓
Audit
```

Store:

- finding
- radiologist
- detected time
- recipient
- notification method
- notification time
- acknowledgement
- acknowledgement time
- notes

Critical findings must not disappear inside free-text reports.

---

# 47. CRITICAL FINDING STATUS

Support:

```text
Detected
Pending Notification
Notified
Acknowledged
Escalated
Closed
```

Escalation rules should be configurable.

---

# 48. BILLING INTEGRATION

Use Phase 4 Billing.

Correct architecture:

```text
Radiology Order
      ↓
Chargeable Event
      ↓
Billing
      ↓
Invoice
      ↓
Payment
```

Radiology does not own:

- invoice
- payment
- receipt
- refund
- accounting

Radiology only publishes the billable service event.

---

# 49. PATIENT EMR INTEGRATION

Final radiology results should appear in:

- Patient 360
- Patient Timeline
- Encounter
- Doctor Workspace
- Radiology History

The EMR should show:

```text
Procedure
Accession
Date
Radiologist
Impression
Report
Image Availability
```

Provide secure image viewer access where authorized.

---

# 50. PRIOR IMAGING

Radiologists often need previous studies.

Support:

```text
Current Study
      ↓
Previous Studies
      ↓
Compare
```

Provide authorized search by:

- patient
- modality
- body part
- date
- accession

Do not automatically load every historical study.

---

# 51. IMAGE COMPARISON

The RIS should support launching multiple studies in the viewer where PACS/viewer supports comparison.

Example:

```text
Current CT
+
Previous CT
```

The HMS does not need to implement its own medical image comparison algorithm.

Delegate image manipulation/comparison to the DICOM viewer.

---

# 52. RADIOLOGY DASHBOARD

Dashboard should include:

- today's orders
- scheduled examinations
- waiting patients
- examinations in progress
- completed studies
- studies awaiting reporting
- urgent studies
- critical findings
- reports pending approval
- completed reports

KPIs:

- examination volume
- modality utilization
- report turnaround time
- patient waiting time
- report backlog
- critical finding acknowledgement time
- cancellation/no-show rate

---

# 53. TURNAROUND TIME

Track:

```text
Order → Registration
Registration → Examination
Check-in → Start
Start → Completion
Completion → Images Available
Images Available → Report Draft
Report Draft → Final
Examination → Final Report
```

Define KPI formulas explicitly.

---

# 54. RADIOLOGY REPORTING

Provide reports for:

### Daily workload

- examinations
- modalities
- procedures
- radiologists

### Modality utilization

```text
CT
MRI
X-Ray
USG
etc.
```

### Report TAT

By:

- modality
- radiologist
- priority
- department

### Patient waiting time

### Critical findings

### Cancellation/no-show

### Study backlog

### Revenue reference

Financial values should come from Billing.

---

# 55. RBAC

Use existing permission infrastructure.

Suggested permissions:

```text
radiology.dashboard.view

radiology.procedure.view
radiology.procedure.create
radiology.procedure.update

radiology.modality.view
radiology.modality.create
radiology.modality.update

radiology.order.view
radiology.order.create
radiology.order.cancel

radiology.registration.view
radiology.registration.create

radiology.schedule.view
radiology.schedule.create
radiology.schedule.update
radiology.schedule.cancel

radiology.examination.view
radiology.examination.start
radiology.examination.complete

radiology.study.view
radiology.study.export

radiology.worklist.view
radiology.worklist.assign

radiology.report.view
radiology.report.create
radiology.report.update
radiology.report.submit
radiology.report.approve
radiology.report.amend

radiology.critical_finding.view
radiology.critical_finding.create
radiology.critical_finding.notify
radiology.critical_finding.acknowledge

radiology.pacs.view
radiology.pacs.manage

radiology.dicom.view
radiology.dicom.manage

radiology.settings.manage
radiology.audit.view
```

Adapt permission naming to the existing project.

---

# 56. USER ROLES

Potential roles:

```text
Radiology Receptionist
Radiology Scheduler
Radiology Technician
Radiographer
Radiologist
Senior Radiologist
Radiology Manager
PACS Administrator
Radiology Administrator
```

Do not create duplicate users or roles if the existing system already supports role configuration.

---

# 57. MULTI-HOSPITAL SECURITY

Enforce:

```text
Organization
   ↓
Hospital
   ↓
Branch
   ↓
Radiology Department
   ↓
Modality
```

A user assigned to Hospital A must not access Hospital B studies.

This restriction must be enforced at:

- UI
- controller
- service/action
- policy
- API
- database query scope where appropriate

Do not rely on UI filtering.

---

# 58. DICOM SECURITY

DICOM/PACS integration requires additional security considerations.

Protect:

- PACS credentials
- AE titles
- DICOM endpoints
- viewer URLs
- image retrieval
- image export
- study sharing

Do not expose PACS credentials to browsers.

Use server-side integration where appropriate.

---

# 59. IMAGE EXPORT

If exporting images is supported:

Require:

- permission
- audit
- purpose where appropriate
- secure download
- controlled format

Record:

- user
- study
- date/time
- export reason if configured

---

# 60. AUDIT

Record clinical/security events such as:

- radiology order created
- order cancelled
- registration created
- appointment scheduled
- appointment cancelled
- examination started
- examination completed
- study received
- study viewed
- study assigned
- report created
- report submitted
- report approved
- report finalized
- report amended
- report printed
- report exported
- image exported
- critical finding created
- critical finding notified
- critical finding acknowledged
- PACS study accessed

Use the existing audit infrastructure.

---

# 61. FILE MANAGEMENT

Do not automatically copy every DICOM image into Laravel storage.

Preferred architecture:

```text
RIS
 ↓
PACS
 ↓
DICOM Viewer
```

The application should store:

- study metadata
- identifiers
- PACS reference
- viewer reference
- authorized access mechanism

Use the existing File module for:

- reports
- PDFs
- non-DICOM documents
- referral documents
- supporting attachments

---

# 62. API

Use the existing `/api/v1` architecture.

Potential endpoints:

```http
GET    /api/v1/radiology/procedures
POST   /api/v1/radiology/procedures

GET    /api/v1/radiology/modalities
POST   /api/v1/radiology/modalities

GET    /api/v1/radiology/orders
POST   /api/v1/radiology/orders

GET    /api/v1/radiology/orders/{order}
POST   /api/v1/radiology/orders/{order}/register

GET    /api/v1/radiology/schedules
POST   /api/v1/radiology/schedules

GET    /api/v1/radiology/examinations
POST   /api/v1/radiology/examinations/{exam}/start
POST   /api/v1/radiology/examinations/{exam}/complete

GET    /api/v1/radiology/studies
GET    /api/v1/radiology/studies/{study}
GET    /api/v1/radiology/studies/{study}/viewer

GET    /api/v1/radiology/worklist

GET    /api/v1/radiology/reports
POST   /api/v1/radiology/reports
POST   /api/v1/radiology/reports/{report}/submit
POST   /api/v1/radiology/reports/{report}/approve
POST   /api/v1/radiology/reports/{report}/amend

GET    /api/v1/radiology/critical-findings
POST   /api/v1/radiology/critical-findings
POST   /api/v1/radiology/critical-findings/{finding}/acknowledge
```

Follow existing API patterns instead of blindly implementing every endpoint.

---

# 63. API RESPONSE

Reuse the project's standard API response.

Example:

```json
{
    "success": true,
    "message": "Operation successful.",
    "data": {},
    "meta": {}
}
```

Validation:

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

Never expose internal exceptions.

---

# 64. INTEROPERABILITY

Prepare for:

### FHIR

Potential resources:

```text
Patient
Encounter
Practitioner
PractitionerRole
Organization
ServiceRequest
ImagingStudy
DiagnosticReport
Observation
DocumentReference
Appointment
```

### HL7

Potential workflows:

- order messages
- scheduling
- patient demographics
- results
- acknowledgements

### DICOM

Support architecture for:

- C-FIND
- C-MOVE / C-GET where applicable
- C-STORE
- Modality Worklist
- MPPS
- DICOMweb

Do not implement every protocol immediately.

Create clean extension points.

---

# 65. DICOM MODALITY WORKLIST

Design for future DICOM Modality Worklist.

Workflow:

```text
Radiology Order
      ↓
RIS
      ↓
DICOM Modality Worklist
      ↓
CT/MRI/X-Ray
      ↓
DICOM Study
      ↓
PACS
```

The modality should receive standardized patient/order information.

This reduces manual data entry and patient identification errors.

---

# 66. MPPS FOUNDATION

Prepare for Modality Performed Procedure Step where supported.

Capture:

- procedure start
- procedure completion
- modality
- accession
- study UID
- performed status

This can improve examination tracking.

---

# 67. DICOM STUDY RECONCILIATION

Implement a foundation for reconciliation when:

- patient information differs
- accession differs
- study is unmatched
- duplicate study is detected

Do not automatically alter patient identity based solely on incoming DICOM data.

Use a controlled reconciliation process.

---

# 68. DUPLICATE STUDY DETECTION

Detect potential duplicates using combinations of:

- patient
- accession
- study UID
- modality
- study date/time

Do not silently delete duplicates.

Flag them for authorized review.

---

# 69. RADIOLOGY REPORT AMENDMENT

Amendment must preserve history.

Example:

```text
Final Report v1
       ↓
Amendment Requested
       ↓
Reason
       ↓
Radiologist Review
       ↓
Final Report v2
```

The original report remains accessible according to authorization.

---

# 70. CLINICAL SAFETY PRINCIPLES

Radiology implementation must prioritize:

1. Correct patient
2. Correct order
3. Correct examination
4. Correct modality
5. Correct study
6. Correct image association
7. Correct radiologist
8. Correct report
9. Correct report approval
10. Complete audit trail

---

# 71. DATABASE DESIGN

Use appropriate relational tables.

Potential core tables:

```text
radiology_departments
radiology_modalities
radiology_procedures
radiology_body_parts
radiology_protocols
radiology_contrast_agents
radiology_orders
radiology_order_items
radiology_registrations
radiology_examinations
radiology_studies
radiology_series
radiology_instances
radiology_report_templates
radiology_reports
radiology_report_versions
radiology_report_findings
radiology_critical_findings
radiology_assignments
radiology_pacs_servers
radiology_dicom_events
radiology_audit_events
```

Do not create tables that duplicate existing platform concepts.

The exact schema must follow the project's established conventions.

---

# 72. DATABASE RELATIONSHIPS

Conceptual relationships:

```text
Patient
  │
  └── Radiology Order
         │
         ├── Order Items
         │
         ├── Registration
         │
         └── Examination
                │
                └── Study
                     │
                     ├── Series
                     │    └── Instances
                     │
                     └── Report
```

A study should not exist without appropriate examination/order association unless it is explicitly an external/imported study.

---

# 73. TRANSACTIONS

Use transactions for:

### Radiology Registration

```text
Validate order
Create registration
Generate accession
Create examination
Publish chargeable event
```

### Examination Completion

```text
Complete examination
Update timestamps
Associate study
Create audit
Publish study-available event
```

### Report Finalization

```text
Validate report
Finalize report
Lock report version
Create audit
Notify EMR/clinician
```

---

# 74. CONCURRENCY

Protect against:

- duplicate accession
- duplicate registration
- duplicate study association
- duplicate DICOM import
- double report approval
- double report finalization
- duplicate critical-finding notification

Use:

- unique constraints
- transactions
- row locking
- idempotency
- DICOM UID uniqueness

---

# 75. PERFORMANCE

Optimize:

- radiologist worklists
- study searches
- report queues
- modality schedules
- patient imaging history

Use:

- pagination
- indexes
- eager loading
- query scopes
- caching for master data
- asynchronous PACS synchronization where appropriate

Never retrieve all patient studies into memory.

---

# 76. EVENTS

Potential events:

```text
RadiologyOrderCreated
RadiologyOrderRegistered
RadiologyScheduled
RadiologyExamStarted
RadiologyExamCompleted
RadiologyStudyReceived
RadiologyStudyAssigned
RadiologyReportCreated
RadiologyReportSubmitted
RadiologyReportApproved
RadiologyReportFinalized
RadiologyReportAmended
CriticalFindingDetected
CriticalFindingNotified
CriticalFindingAcknowledged
```

Use the existing event conventions.

---

# 77. QUEUED JOBS

Potential jobs:

```text
SyncPacsStudyMetadata
ProcessDicomEvent
GenerateRadiologyReport
GenerateReportPdf
SendCriticalFindingNotification
SendReportReadyNotification
ReconcileDicomStudy
UpdateRadiologyStatistics
```

Do not put heavy PACS communication directly inside HTTP requests when asynchronous processing is appropriate.

---

# 78. NOTIFICATIONS

Use existing notifications.

Possible notifications:

- appointment reminder
- examination reminder
- preparation reminder
- report ready
- critical finding
- amended report

Follow existing patient notification/privacy rules.

---

# 79. TESTING

Implement comprehensive testing.

## Unit Tests

Test:

- accession generation
- status transitions
- report lifecycle
- critical finding logic
- TAT calculation
- DICOM identifier validation
- duplicate study detection

## Feature Tests

Test:

- order
- registration
- scheduling
- check-in
- examination
- study association
- worklist
- report
- approval
- amendment
- critical finding

## Integration Tests

Test:

```text
Clinical Order → Radiology
Radiology → Billing
Radiology → Patient EMR
Radiology → Notification
Radiology → Audit
Radiology → PACS Adapter
```

---

# 80. DICOM/PACS TESTING

Create integration tests around the PACS abstraction.

Test:

- study lookup
- study metadata retrieval
- viewer URL generation
- duplicate study detection
- DICOM UID validation
- unavailable PACS handling
- timeout handling
- retry behavior
- invalid response handling

Use mocks/fakes for automated tests where real PACS is unavailable.

Do not claim real PACS integration testing unless it was actually performed.

---

# 81. SECURITY TESTING

Explicitly test:

```text
Hospital A Radiology User
        ↓
Cannot access
        ↓
Hospital B Study
```

Also test:

- unauthorized image viewing
- unauthorized report approval
- unauthorized amendment
- unauthorized study export
- unauthorized DICOM access
- API object-level authorization
- direct URL access
- permission escalation
- PACS credential exposure

---

# 82. NEGATIVE TESTING

Test:

- cancelled order cannot be performed
- unauthorized user cannot approve
- finalized report cannot be silently edited
- duplicate accession rejected
- duplicate Study Instance UID rejected
- wrong patient study rejected/reconciled
- invalid modality rejected
- invalid status transition rejected
- unauthorized PACS access rejected
- missing required clinical indication rejected where configured

---

# 83. UI STRUCTURE

Suggested menu:

```text
Radiology
├── Dashboard
├── Orders
├── Registration
├── Schedule
├── Examination Queue
├── Modality Worklist
├── Studies
├── Radiologist Worklist
├── Reports
├── Critical Findings
├── Report Templates
├── Procedures
├── Modalities
├── Body Parts
├── Protocols
├── PACS
├── DICOM
├── Analytics
└── Settings
```

Use the existing menu/permission system.

---

# 84. RADIOLOGIST WORKSPACE

Create a focused radiologist workspace.

Header:

```text
Patient
MRN
Age/Gender
Accession
Procedure
Modality
Study Date
Priority
```

Main area:

```text
Image Viewer
```

Side/bottom panel:

```text
Clinical History
Previous Studies
Findings
Impression
Recommendation
Critical Finding
Report Actions
```

Do not build a full medical image viewer inside the Laravel application unless explicitly required.

Integrate a DICOM viewer.

---

# 85. TECHNICIAN WORKSPACE

Provide:

```text
Today's Schedule
Patient Queue
Preparation
Start Examination
Contrast
Technical Notes
Complete Examination
```

---

# 86. RECEPTION WORKSPACE

Provide:

```text
Order Search
Patient Verification
Registration
Payment Status
Scheduling
Check-In
Print/Send Instructions
```

---

# 87. REPORT PRINTING

Reports should be printable/exportable according to existing document infrastructure.

Include:

- hospital branding
- patient information
- procedure
- accession
- findings
- impression
- radiologist
- approval
- report date
- report version/status

---

# 88. PRIVACY

Radiology data is sensitive clinical information.

Apply:

- least privilege
- role-based access
- hospital/branch scope
- audit
- secure viewer access
- secure report downloads
- export auditing

Do not expose images or reports through public URLs.

---

# 89. FUTURE EXTENSIBILITY

Design extension points for:

- AI radiology
- structured reporting
- voice dictation
- speech-to-text
- automated measurements
- AI image analysis
- mammography workflows
- cardiac imaging
- oncology imaging
- teleradiology
- external radiologist reporting
- cloud PACS
- multi-site PACS

Do not implement these unless explicitly included in the current scope.

---

# 90. AI READINESS

The architecture should support future AI features such as:

- report drafting assistance
- finding extraction
- structured-report suggestions
- study prioritization
- TAT prediction
- image-analysis integration
- comparison assistance

However:

**AI must never independently finalize a radiology report.**

Any AI-generated content must be:

- clearly identified
- reviewable
- editable
- clinician-controlled
- auditable

---

# 91. FHIR READINESS

Prepare mappings for:

```text
Patient
Encounter
ServiceRequest
ImagingStudy
DiagnosticReport
Observation
Practitioner
PractitionerRole
Organization
Appointment
DocumentReference
```

Keep interoperability mappings separate from core business logic.

---

# 92. DEPLOYMENT CONSIDERATIONS

Document:

- migrations
- queue workers
- scheduler
- PACS connectivity
- DICOM network configuration
- AE titles
- PACS endpoints
- DICOMweb endpoints
- viewer configuration
- environment variables
- secrets
- report storage
- backup considerations
- monitoring

Never place PACS credentials directly in source code.

---

# 93. OBSERVABILITY

Provide appropriate logging/monitoring for:

- PACS connection
- DICOM requests
- failed study imports
- failed viewer URL generation
- report processing
- critical notifications

Do not log sensitive patient information unnecessarily.

Use correlation/request IDs where the existing application supports them.

---

# 94. BACKUP / DISASTER RECOVERY CONSIDERATION

RIS database backups must be covered by the existing application/database backup strategy.

PACS images must be handled by the organization's PACS backup/replication strategy.

Do not assume backing up the Laravel database backs up DICOM images.

Document the distinction clearly.

---

# 95. IMPLEMENTATION ORDER

Implement in this order:

### Step 1
Inspect Phases 0–5.

### Step 2
Map existing Patient, Encounter, Clinical Order and Billing integration.

### Step 3
Implement radiology master data.

### Step 4
Implement modalities.

### Step 5
Implement procedure catalog.

### Step 6
Implement body parts and protocols.

### Step 7
Implement contrast configuration.

### Step 8
Implement radiology orders.

### Step 9
Implement registration/accession.

### Step 10
Implement scheduling integration.

### Step 11
Implement examination workflow.

### Step 12
Implement DICOM study metadata.

### Step 13
Implement PACS abstraction.

### Step 14
Implement PACS/DICOM adapter.

### Step 15
Implement radiologist worklist.

### Step 16
Implement report templates.

### Step 17
Implement report creation.

### Step 18
Implement report approval/finalization.

### Step 19
Implement report versioning/amendment.

### Step 20
Implement critical findings.

### Step 21
Implement EMR integration.

### Step 22
Implement Billing integration.

### Step 23
Implement notifications.

### Step 24
Implement dashboards and reports.

### Step 25
Implement API.

### Step 26
Implement FHIR/HL7 readiness.

### Step 27
Implement DICOM Modality Worklist foundation.

### Step 28
Implement MPPS foundation if appropriate.

### Step 29
Implement security tests.

### Step 30
Implement integration tests.

### Step 31
Performance review.

### Step 32
Documentation.

---

# 96. NON-GOALS

Do not implement as part of the core Phase 6 unless explicitly requested:

- full PACS replacement
- complete DICOM viewer
- AI image diagnosis
- automated diagnosis
- autonomous reporting
- pharmacy inventory
- contrast inventory
- accounting
- insurance claims engine
- general hospital inventory
- advanced oncology imaging
- advanced cardiac imaging
- advanced mammography CAD

Build integration points instead.

---

# 97. DEFINITION OF DONE

Phase 6 is complete when:

- Radiology master data works
- Modality management works
- Procedure catalog works
- Body-part configuration works
- Protocol configuration works
- Contrast configuration works
- Radiology orders work
- Registration works
- Accession generation works
- Scheduling integration works
- Patient check-in works
- Examination workflow works
- DICOM study metadata works
- PACS adapter architecture works
- Radiologist worklist works
- Study assignment works
- Image viewer integration works
- Report templates work
- Report creation works
- Report validation/approval works
- Final report works
- Report versioning works
- Report amendment works
- Critical finding workflow works
- Patient EMR integration works
- Billing integration works
- Notification integration works
- Audit works
- RBAC works
- Multi-hospital scope works
- API works
- Dashboard works
- Reporting works
- DICOM interoperability foundation works
- FHIR/HL7 readiness exists
- Automated tests exist
- Security tests exist
- Documentation exists
- No regression exists in Phases 0–5

---

# 98. FINAL AI IMPLEMENTATION PROMPT

You are now authorized to implement **Phase 6 — Radiology / RIS / PACS** in the existing Hospital ERP repository.

Before coding:

1. Inspect the repository.
2. Inspect the existing modular architecture.
3. Inspect Phases 0–5.
4. Identify reusable infrastructure.
5. Identify actual model/table/class names.
6. Identify existing API conventions.
7. Identify existing authorization/scope mechanisms.
8. Identify existing audit/notification/document systems.
9. Identify existing billing and clinical-order integration mechanisms.
10. Create an implementation plan based on the actual repository.

Then implement the module incrementally.

## Mandatory rules

- Do not create a second modular architecture.
- Do not duplicate Patient.
- Do not duplicate Encounter.
- Do not duplicate Clinical Order.
- Do not duplicate Billing.
- Do not duplicate Authentication/RBAC.
- Do not duplicate Notifications.
- Do not duplicate Audit.
- Do not duplicate File Management.
- Do not hardcode radiology master data.
- Do not hardcode DICOM AE titles.
- Do not hardcode PACS endpoints.
- Do not store DICOM images unnecessarily inside MySQL/PostgreSQL.
- Do not silently overwrite finalized reports.
- Do not bypass authorization.
- Do not bypass hospital/branch scope.
- Do not expose PACS credentials.
- Do not expose unrestricted image URLs.
- Do not claim tests passed unless they were actually executed.

## Clinical safety priorities

Always prioritize:

1. Correct patient
2. Correct order
3. Correct examination
4. Correct modality
5. Correct study
6. Correct image association
7. Correct radiologist
8. Correct report
9. Correct approval
10. Complete audit trail

## PACS architecture

Use an adapter/interface architecture.

The core RIS domain must not depend directly on a specific PACS vendor.

Conceptually:

```text
RIS
 │
 └── PacsClientInterface
       │
       ├── Orthanc Adapter
       ├── dcm4chee Adapter
       └── Commercial PACS Adapter
```

Implement only the adapter(s) required by the current project.

## DICOM

Use proper DICOM identifiers and concepts.

Do not confuse:

```text
MRN
Radiology Accession Number
Study Instance UID
Series Instance UID
SOP Instance UID
```

Use appropriate unique constraints.

## Reporting

Final reports are immutable.

Corrections require:

```text
Amendment
+
Reason
+
Authorization
+
Version
+
Audit
```

## Critical Findings

Critical findings require:

```text
Detection
→ Notification
→ Acknowledgement
→ Audit
```

## Security

Test:

```text
Hospital A Radiology User
       X
Hospital B Radiology Study
```

Also test direct API access, direct URL access, report approval, amendment, image viewing and export.

## Integration

Implement:

```text
Clinical Order → Radiology
Radiology → Billing
Radiology → Patient EMR
Radiology → Notification
Radiology → Audit
Radiology → PACS
```

## Testing

Create and execute appropriate:

- unit tests
- feature tests
- integration tests
- API tests
- security tests
- PACS adapter tests
- concurrency tests

Only report tests as passed if they were actually executed.

## Final report

After implementation provide:

### Implementation Summary
What was implemented.

### Database
Tables, migrations, indexes and relationships.

### Models
New/modified models.

### Services/Actions
Business operations.

### Controllers
Web/API controllers.

### Routes
Web/API routes.

### Permissions
New permissions.

### Events/Listeners
Events and listeners.

### Jobs
Queued jobs.

### Notifications
Notification workflows.

### PACS/DICOM
Integration architecture and configuration.

### Clinical Integration
Clinical Order and EMR integration.

### Billing Integration
Chargeable-event integration.

### Security
Authorization and scope controls.

### Testing
Tests created and actually executed.

### Deployment
Migrations, queues, scheduler, PACS configuration and environment requirements.

### Deferred Features
Features intentionally left for future phases.

The final implementation must behave as a **production-grade Radiology Information System with PACS/DICOM integration capability**, not as a simple CRUD module.