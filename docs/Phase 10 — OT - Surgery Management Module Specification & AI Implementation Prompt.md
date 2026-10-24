
# Phase 10 — OT / Surgery Management
## Module Specification & AI Implementation Prompt

## 1. Module Overview

Build **Phase 10 — OT / Surgery Management** as a production-grade operating-theatre and surgical-management module within the existing Hospital Management System.

The module manages the complete perioperative operational workflow:

```text
Surgical Decision / Order
        ↓
Surgery Request
        ↓
Preoperative Assessment
        ↓
Authorization / Scheduling
        ↓
OT Booking
        ↓
Preoperative Checklist
        ↓
Patient Preparation
        ↓
OT Admission / Reception
        ↓
WHO-Style Surgical Safety Checklist
        ↓
Anesthesia / Surgical Team
        ↓
Procedure
        ↓
Specimen / Implant / Blood Integration
        ↓
Postoperative Recovery
        ↓
PACU / ICU / Ward Transfer
        ↓
Operative Documentation
        ↓
Charges / Billing
        ↓
Follow-up
```

The exact clinical protocols must remain configurable according to hospital policy.

---

# 2. Critical Architecture Rule

The existing modular architecture is **already implemented**.

Before coding:

- inspect the repository;
- inspect Phases 0–9;
- understand the actual module boundaries;
- reuse existing infrastructure;
- extend existing abstractions.

**DO NOT create another modular architecture.**

Do not duplicate:

- Patient/MPI;
- Appointment;
- Clinical Encounter;
- Diagnosis;
- Clinical Orders;
- Prescription;
- Pharmacy;
- Billing;
- Admission;
- Bed Management;
- Nursing;
- User/RBAC;
- Workflow;
- Audit;
- Notifications;
- File Management.

OT/Surgery should orchestrate these systems rather than replace them.

---

# 3. Primary Objectives

Phase 10 should provide:

1. Surgery request management
2. Surgical case registration
3. Procedure catalog
4. Surgical specialties
5. Surgical team management
6. OT/theatre master management
7. OT equipment/capability configuration
8. OT scheduling
9. Operating-list management
10. Surgeon assignment
11. Assistant assignment
12. Anesthetist assignment
13. Nursing assignment
14. Preoperative assessment
15. Surgical consent tracking
16. Anesthesia assessment integration
17. Preoperative checklist
18. Surgical safety checklist
19. Patient verification
20. Site/side verification
21. Procedure verification
22. Implant integration
23. Blood bank integration
24. Laboratory integration
25. Radiology/PACS integration
26. Pharmacy integration
27. Intraoperative documentation
28. Operative note
29. Specimen handling
30. Surgical complications
31. Postoperative orders integration
32. PACU/recovery workflow
33. OT-to-ward/ICU transfer
34. Surgical cancellation
35. Emergency surgery workflow
36. OT utilization reporting
37. Surgery reporting
38. Billing integration
39. Audit/security
40. API
41. FHIR/HL7 readiness
42. AI integration points

---

# 4. Domain Boundary

The relationship should be:

```text
Patient
   ↓
Clinical Encounter
   ↓
Surgical Decision / Clinical Order
   ↓
OT Surgery Request
   ↓
OT Scheduling
   ↓
Preoperative Workflow
   ↓
Surgical Procedure
   ↓
Postoperative Recovery
   ↓
IPD / ICU / Discharge
```

OT owns:

- surgical case;
- theatre;
- surgical scheduling;
- surgical team;
- perioperative workflow;
- operative documentation;
- surgical checklist;
- specimen workflow;
- surgical complications;
- OT utilization.

OT does NOT own:

- patient registration;
- physician diagnosis master;
- prescription;
- pharmacy inventory;
- hospital admission;
- bed allocation;
- laboratory testing;
- radiology/PACS;
- blood inventory;
- enterprise inventory;
- accounting.

---

# 5. Surgical Case Lifecycle

Recommended lifecycle:

```text
Requested
   ↓
Under Review
   ↓
Approved
   ↓
Scheduled
   ↓
Preoperative Preparation
   ↓
Ready for OT
   ↓
Patient Received
   ↓
In Procedure
   ↓
Procedure Completed
   ↓
Recovery
   ↓
Transferred
   ↓
Closed
```

Additional states:

```text
Cancelled
Postponed
Rejected
No Show
Emergency
Aborted
```

Status names must follow existing project conventions.

---

# 6. Surgery Request

A surgery request may originate from:

- OPD;
- IPD;
- Emergency;
- specialist consultation;
- referral;
- clinical order.

Conceptual fields:

```text id="0a5s5h"
Patient
Encounter
Admission
Requesting Provider
Surgical Specialty
Requested Procedure
Indication
Diagnosis
Priority
Elective/Emergency
Preferred Date
Estimated Duration
Required OT Capability
Anesthesia Requirement
Special Equipment
Implant Requirement
Blood Requirement
Specimen Requirement
Notes
Requested By
Requested At
Status
```

Do not create another clinical diagnosis system.

---

# 7. Procedure Master

Create/reuse a configurable surgical procedure master.

Potential fields:

```text id="yq54oh"
Procedure Code
Procedure Name
Description
Specialty
Procedure Category
Default Duration
Anesthesia Category
Laterality Applicable
Site Applicable
Day Care Eligible
Emergency Eligible
Requires OT
Requires Implant
Requires Blood
Requires Specimen
Active
```

Procedure coding should be configurable.

Do not assume a single international procedure coding system.

---

# 8. Procedure Components

A procedure may contain:

- primary procedure;
- secondary procedure;
- additional procedure;
- staged procedure;
- bilateral procedure.

Support:

```text id="vbrb5a"
Primary
Secondary
Additional
Unplanned
Aborted
```

Do not silently replace the originally scheduled procedure.

---

# 9. Surgical Specialty

Support configurable specialties:

- General Surgery;
- Orthopedics;
- Neurosurgery;
- ENT;
- Ophthalmology;
- Urology;
- Gynecology;
- Obstetrics;
- Cardiothoracic;
- Plastic Surgery;
- Pediatric Surgery;
- Dental/Maxillofacial;
- Other configured specialties.

Do not hardcode these as the only available specialties.

---

# 10. OT / Theatre Management

OT configuration hierarchy:

```text id="wm79a9"
Hospital
  ↓
Branch
  ↓
OT Complex
  ↓
Theatre / Operating Room
```

Theatre attributes:

```text
Theatre Code
Name
Location
Capacity
Specialty Capability
Emergency Capability
Active
Status
```

---

# 11. OT Capabilities

Configure theatre capabilities:

- general surgery;
- orthopedic;
- laparoscopic;
- endoscopic;
- ophthalmic;
- ENT;
- pediatric;
- neurosurgical;
- cardiac;
- obstetric;
- other.

Do not hardcode clinical capability rules.

---

# 12. OT Operating Table / Room Resources

Theatre resources may include:

- operating table;
- anesthesia machine;
- surgical lights;
- electrosurgical equipment;
- imaging equipment;
- specialty equipment.

Do not create a competing enterprise Asset Management system.

OT should reference existing assets/resources where available.

---

# 13. OT Scheduling

Scheduling must support:

- date;
- theatre;
- procedure;
- surgeon;
- team;
- estimated duration;
- setup time;
- cleanup time;
- anesthesia requirement;
- emergency priority.

Example:

```text id="ty2wqy"
08:00–10:00
OT-01
General Surgery
Appendectomy
Dr. A
```

Prevent overlapping bookings.

---

# 14. OT Schedule Conflict Detection

The system must detect conflicts involving:

- theatre;
- surgeon;
- assistant;
- anesthetist;
- required equipment;
- patient;
- scheduled time.

A conflict must be identified server-side.

Do not rely only on the calendar UI.

---

# 15. Emergency Surgery

Emergency cases may bypass some scheduling steps while still enforcing mandatory safety checks.

Workflow:

```text id="0pq15p"
Emergency Surgical Decision
       ↓
Emergency OT Request
       ↓
Availability Check
       ↓
Safety/Consent Rules
       ↓
OT
```

The system must clearly distinguish:

- workflow bypass;
- safety requirement bypass.

Emergency status must never automatically mean that safety checks can be skipped.

---

# 16. Surgical Team

Support configurable roles:

- primary surgeon;
- assistant surgeon;
- anesthetist;
- anesthesia assistant;
- scrub nurse;
- circulating nurse;
- technician;
- other support staff.

Conceptual structure:

```text id="0awqib"
Surgical Case
   ↓
Team Assignment
   ├ Surgeon
   ├ Assistant
   ├ Anesthetist
   ├ Scrub Nurse
   └ Circulating Nurse
```

Use existing users/providers/staff.

Do not create duplicate user accounts.

---

# 17. Team Assignment History

Do not simply overwrite the team.

Track:

```text id="g7py4r"
Role
Provider/User
Assigned At
Removed At
Reason
Assigned By
```

This is important for clinical audit and medico-legal traceability.

---

# 18. Preoperative Assessment

Provide structured preoperative assessment workflow.

Potential sections:

### Patient

- identity;
- MRN;
- admission;
- allergies;
- diagnosis.

### Clinical

- relevant history;
- medications;
- comorbidities;
- prior surgery;
- relevant investigations.

### Safety

- identity;
- procedure;
- site;
- laterality;
- consent;
- allergies;
- fasting status;
- blood availability;
- implant availability.

Clinical content should be configurable.

Do not duplicate the physician EMR.

---

# 19. Preoperative Checklist

Configurable checklist categories:

```text id="1qj2yd"
Patient Identity
Procedure
Site/Side
Consent
Allergies
Fasting
Medication Review
Relevant Investigations
Imaging
Blood
Implants
Equipment
Anesthesia Review
Pregnancy Status Where Applicable
Infection Control
Documentation
```

Hospital-specific requirements must be configurable.

---

# 20. Surgical Consent

Track consent status.

Potential states:

```text id="8c2e2n"
Not Required
Pending
Obtained
Refused
Withdrawn
Expired
```

Capture:

- procedure;
- patient;
- consenting clinician;
- date/time;
- consent document;
- witness where required;
- language;
- interpreter where applicable;
- version.

Use existing File Management for scanned/uploaded documents.

---

# 21. Consent Integrity

A finalized consent record must not be silently modified.

Corrections require:

- new version;
- amendment;
- replacement according to policy;
- audit.

The original must remain traceable.

---

# 22. Site / Side Verification

Where applicable, support:

- right;
- left;
- bilateral;
- midline;
- not applicable.

The system should require consistency between:

```text
Clinical Order
Surgery Request
Scheduled Procedure
Consent
Preoperative Checklist
```

when laterality/site is applicable.

Conflicts must block or escalate the workflow according to configured policy.

---

# 23. Patient Verification

Before OT entry, require configured verification:

```text id="ux8q2c"
Patient Identity
MRN
Procedure
Site/Side
Consent
Allergies
```

Do not rely solely on the patient's name.

---

# 24. Surgical Safety Checklist

Implement a configurable surgical safety checklist with phases such as:

### Sign In

Before anesthesia/procedure.

### Time Out

Immediately before procedure.

### Sign Out

Before leaving theatre.

Each section should support:

```text id="7rvv4n"
Checklist Item
Response
Required
Completed By
Completed At
Exception
Comment
```

The exact checklist content must be configurable by hospital policy.

---

# 25. Checklist Rules

The system should distinguish:

```text
Completed
Not Applicable
Exception
Pending
```

Do not allow users to mark mandatory items as completed without the required response.

Emergency workflow may have special rules, but those rules must be explicit and auditable.

---

# 26. Anesthesia Integration

Prepare integration with anesthesia workflows.

OT should consume:

- anesthesia assessment status;
- anesthesia provider;
- anesthesia type;
- clearance status.

Future anesthesia functionality may include:

- pre-anesthesia assessment;
- intraoperative anesthesia chart;
- airway management;
- medications;
- fluids;
- monitoring;
- anesthesia complications.

Do not build a full independent anesthesia module unless specifically included in the project scope.

---

# 27. Anesthesia Types

Support configurable values:

- General;
- Regional;
- Spinal;
- Epidural;
- Local;
- Sedation;
- MAC;
- Other.

These are configurable classifications, not hardcoded clinical rules.

---

# 28. Operative Procedure

When the patient enters OT:

```text id="f74g9p"
Scheduled
    ↓
Patient Received
    ↓
In OT
    ↓
Sign In
    ↓
Time Out
    ↓
Procedure Started
    ↓
Procedure Completed
    ↓
Sign Out
```

Capture timestamps for each stage.

---

# 29. Intraoperative Documentation

Record:

```text id="wlm9jp"
Procedure Start
Procedure End
Actual Procedure
Surgeon
Assistants
Anesthesia
Anesthetist
OT
Position
Site
Findings
Technique Summary
Complications
Estimated Blood Loss
Specimens
Implants
Drains
Counts
Notes
```

Do not replace the detailed operative note with generic text fields if structured documentation is appropriate.

---

# 30. Operative Note

The operative note should support configurable templates.

Potential sections:

- preoperative diagnosis;
- postoperative diagnosis;
- procedure;
- indication;
- surgeon;
- assistants;
- anesthesia;
- findings;
- procedure description;
- specimen;
- implants;
- drains;
- blood loss;
- complications;
- disposition.

The note must integrate with the existing clinical documentation framework where available.

---

# 31. Operative Diagnosis

Capture:

- preoperative diagnosis;
- postoperative diagnosis;
- primary;
- secondary.

Use existing clinical diagnosis terminology and coding.

Do not create another diagnosis master.

---

# 32. Actual vs Scheduled Procedure

Maintain distinction:

```text id="o7k1ef"
Scheduled Procedure
        ≠
Actual Procedure
```

The surgeon may document:

- same;
- modified;
- additional;
- aborted.

Changes must be auditable.

---

# 33. Unplanned Additional Procedure

If an additional procedure occurs:

```text id="r9n2ry"
Scheduled Procedure
       ↓
Additional Procedure
       ↓
Reason
       ↓
Authorization/Consent Status
       ↓
Operative Record
```

Do not silently modify the scheduled procedure.

---

# 34. Surgical Complications

Provide structured complication recording:

```text id="f9u1c0"
Complication
Severity
Time
Phase
Description
Action Taken
Outcome
Reported By
```

Complication severity and terminology should be configurable.

---

# 35. Surgical Counts

Support documentation of:

- instruments;
- swabs;
- needles;
- other configured countable items.

Workflow:

```text id="w6oc8b"
Initial Count
      ↓
Additional Count
      ↓
Final Count
      ↓
Reconciliation
```

Any discrepancy must be recorded and escalated according to hospital policy.

Do not allow silent correction.

---

# 36. Implant Management

Support implant documentation.

Fields:

```text id="uv2thb"
Implant
Manufacturer
Model
Serial Number
Lot/Batch
Expiry
Quantity
Procedure
Patient
Implanted At
Implanted By
```

The enterprise inventory source remains Phase 15.

OT records clinical usage/reference.

---

# 37. Implant Integration

Future Phase 15 may provide:

- inventory availability;
- stock;
- supplier;
- purchase;
- batch.

OT should consume the required implant information through an integration boundary.

Do not create an independent OT inventory system.

---

# 38. Surgical Specimen Management

Support:

```text id="hjz2jo"
Specimen Collected
Specimen Type
Description
Site
Laterality
Collection Time
Collected By
Container
Label
Destination
```

Workflow:

```text id="pg7mne"
Surgery
 ↓
Specimen
 ↓
Label
 ↓
Pathology/Lab
 ↓
Result
 ↓
EMR
```

Do not implement a second laboratory/pathology system.

---

# 39. Specimen Traceability

Specimen should be traceable to:

- patient;
- admission;
- encounter;
- surgery;
- procedure;
- collection time;
- collector;
- destination.

Prevent duplicate or ambiguous specimen identifiers.

---

# 40. Blood Integration

If blood is required:

```text id="d2v5al"
Surgery
   ↓
Blood Requirement
   ↓
Blood Bank
   ↓
Blood Product
   ↓
OT
   ↓
Transfusion
```

Phase 13 owns:

- blood inventory;
- compatibility;
- crossmatch;
- blood product lifecycle.

OT only consumes the necessary status and documents relevant surgical use.

---

# 41. Surgical Medication Integration

OT may require medications.

Use Phase 7 Pharmacy.

Do not create:

- OT medication master;
- OT pharmacy;
- OT stock ledger.

OT should reference medications through existing clinical/pharmacy interfaces.

---

# 42. Surgical Inventory Boundary

OT may maintain a **case-level required item list**:

- implant;
- special equipment;
- consumable;
- instrument requirement.

But Phase 15 remains responsible for enterprise inventory/procurement.

Example:

```text id="25n9oh"
Surgery Case
   ↓
Required Item
   ↓
Inventory Availability Check
```

No independent stock quantities in OT.

---

# 43. PACU / Recovery

Postoperative recovery workflow:

```text id="8r6t0m"
Procedure Complete
      ↓
Recovery Required
      ↓
PACU
      ↓
Recovery Assessment
      ↓
Ready for Transfer
      ↓
Ward / ICU / Other
```

Support:

- arrival time;
- recovery status;
- vital observations;
- pain;
- consciousness;
- airway/respiratory status;
- nausea/vomiting;
- complications;
- discharge criteria;
- destination.

Use Phase 3/9 observation infrastructure where appropriate.

---

# 44. PACU Integration

Phase 9 Nursing may provide nursing observations.

Future Phase 11 ICU may receive patients requiring critical care.

Phase 10 owns the **OT/PACU transition workflow**, not the complete ICU system.

---

# 45. Postoperative Transfer

Possible destinations:

- ward;
- ICU;
- HDU;
- emergency;
- another facility;
- discharge/day-care.

Integration:

```text id="6w3b8j"
OT
 ↓
Transfer Request
 ↓
Phase 8 / ICU / Emergency
 ↓
Destination
```

The actual inpatient bed allocation remains Phase 8's responsibility.

---

# 46. Day Surgery

Support procedures where admission is not required.

Workflow:

```text id="yl8y4d"
Patient
 ↓
Surgery
 ↓
Recovery
 ↓
Discharge
```

Do not create a separate patient lifecycle.

---

# 47. Surgery Cancellation

Cancellation reasons should be configurable:

- patient unavailable;
- medical reason;
- no bed;
- no blood;
- no implant;
- no equipment;
- surgeon unavailable;
- anesthetic issue;
- patient refusal;
- administrative;
- emergency priority displacement;
- other.

Record:

```text id="xwd6r8"
Reason
Requested By
Approved By
Cancelled At
Notes
```

Do not delete the surgical case.

---

# 48. Surgery Postponement

Support:

```text id="0h2f3x"
Scheduled
 ↓
Postponed
 ↓
New Date
 ↓
Rescheduled
```

Preserve the original scheduling history.

---

# 49. No-Show

For scheduled day surgery or elective surgery:

```text id="snr3fi"
Scheduled
 ↓
Patient Not Present
 ↓
No Show
```

Do not automatically cancel the underlying clinical order.

---

# 50. OT Daily Operating List

Create an operating list view:

```text id="r2lq3r"
Date
Theatre
Time
Patient
MRN
Procedure
Surgeon
Anesthetist
Priority
Status
Special Requirements
```

Support:

- drag/reorder where permitted;
- conflict detection;
- emergency insertion;
- postponement;
- status tracking.

All modifications must be audited.

---

# 51. OT Dashboard

Dashboard should display:

### Today

- scheduled cases;
- completed;
- ongoing;
- cancelled;
- postponed;
- emergency cases;
- delayed cases.

### Theatre

- available;
- occupied;
- cleaning;
- maintenance;
- turnover.

### Clinical

- pending pre-op;
- missing consent;
- missing clearance;
- pending blood;
- pending implant;
- pending specimen.

---

# 52. OT Turnaround / Utilization

Track:

```text id="ryy0p5"
Scheduled Start
Actual Start
Scheduled End
Actual End
Turnover Start
Turnover End
```

Reports:

- OT utilization;
- procedure duration;
- delay;
- turnover time;
- cancellation rate;
- emergency utilization.

Do not use these metrics as independent clinical quality judgments.

---

# 53. OT Delay Tracking

Track delays:

```text id="q2b3t5"
Patient Delay
Surgeon Delay
Anesthesia Delay
Equipment Delay
Blood Delay
Implant Delay
Theatre Delay
Cleaning Delay
Administrative Delay
Other
```

Reasons should be configurable.

---

# 54. Surgical Case Charges

Use Phase 4 Billing.

Potential chargeable events:

- procedure;
- OT usage;
- anesthesia;
- special equipment;
- implants;
- consumables;
- surgeon professional fee;
- assistant fee;
- other configured charges.

Billing owns:

- pricing;
- tax;
- discount;
- invoice;
- payment;
- receipt;
- refund.

OT only publishes chargeable events.

---

# 55. Billing Idempotency

Every surgical chargeable event should have an idempotency/reference key.

For example:

```text id="70ac76"
organization
surgery_case
procedure
charge_type
event_reference
```

Repeated processing must not create duplicate billing lines.

---

# 56. Insurance Foundation

Prepare:

```text id="c9b29b"
Surgery
 ↓
Authorization
 ↓
Coverage
 ↓
Chargeable Events
 ↓
Billing
 ↓
Future Claim
```

Do not implement full insurance claims in Phase 10.

---

# 57. Clinical Timeline

Patient timeline should include:

```text id="j6g2a0"
Surgery Requested
Surgery Scheduled
Pre-op Completed
Consent Obtained
Patient Received
Procedure Started
Procedure Completed
Specimen Collected
Implant Used
Recovery Started
Recovery Completed
Transferred
Operative Note Finalized
```

Use the existing clinical timeline infrastructure.

---

# 58. Surgical Documents

Use existing File Management.

Potential documents:

- consent;
- operative report;
- anesthesia record;
- implant documentation;
- specimen documentation;
- checklist;
- external report.

Do not build another document-storage system.

---

# 59. Audit Requirements

Audit:

### Surgery

- request;
- approval;
- schedule;
- reschedule;
- cancellation;
- postponement;
- start;
- completion;
- closure.

### Checklist

- each item;
- completion;
- override/exception.

### Consent

- create;
- view where required;
- finalize;
- amendment.

### Procedure

- actual procedure;
- additional procedure;
- complication;
- operative note.

### Team

- assignment;
- removal.

### Implant/specimen

- creation;
- modification;
- finalization.

### Billing

- chargeable event creation.

### Security

- export;
- sensitive access;
- break-glass.

---

# 60. Clinical Record Integrity

Never silently modify:

- surgical request;
- consent;
- scheduled procedure;
- completed procedure;
- operative note;
- surgical checklist;
- specimen;
- implant;
- complication.

Completed records require:

- amendment;
- correction;
- addendum;
- reversal;

as appropriate.

---

# 61. Break-Glass Access

Reuse existing clinical break-glass architecture.

Example:

```text id="8h6b0y"
Restricted Record
      ↓
Emergency Access
      ↓
Reason Required
      ↓
Temporary Access
      ↓
Audit
```

Do not create a separate break-glass implementation.

---

# 62. Permissions

Suggested permissions:

```text id="cr4v5q"
ot.dashboard.view

ot.surgery.view
ot.surgery.create
ot.surgery.update
ot.surgery.approve
ot.surgery.cancel
ot.surgery.postpone
ot.surgery.start
ot.surgery.complete

ot.schedule.view
ot.schedule.create
ot.schedule.update
ot.schedule.approve

ot.theatre.view
ot.theatre.manage

ot.team.view
ot.team.assign
ot.team.update

ot.preop.view
ot.preop.create
ot.preop.complete

ot.consent.view
ot.consent.create
ot.consent.finalize

ot.checklist.view
ot.checklist.complete
ot.checklist.override

ot.procedure.view
ot.procedure.document
ot.procedure.complete

ot.specimen.view
ot.specimen.create
ot.specimen.update

ot.implant.view
ot.implant.record

ot.recovery.view
ot.recovery.record
ot.recovery.complete

ot.billing.view

ot.reports.view
ot.reports.export

ot.audit.view
ot.settings.manage
```

Actual permissions must follow existing naming conventions.

---

# 63. Roles

Potential roles:

### OT Coordinator

- surgery scheduling;
- operating list;
- case preparation.

### OT Nurse

- pre-op checklist;
- patient reception;
- surgical checklist;
- intraoperative documentation;
- recovery.

### Surgeon

- surgery request;
- operative documentation;
- procedure completion;
- complications.

### Assistant Surgeon

- assigned case access.

### Anesthetist

- anesthesia-related workflow;
- preoperative clearance;
- intraoperative anesthesia data where supported.

### OT Technician

- theatre preparation;
- equipment;
- case support.

### OT Manager

- scheduling;
- utilization;
- staffing;
- configuration.

### Hospital Administrator

- reports;
- operational oversight.

Access must be permission-based.

---

# 64. API

Base:

```text id="n1iy1a"
/api/v1/ot
```

Suggested endpoints:

```http id="m3m4ve"
GET    /procedures
POST   /procedures

GET    /surgery-requests
POST   /surgery-requests
GET    /surgery-requests/{id}
PUT    /surgery-requests/{id}
POST   /surgery-requests/{id}/approve
POST   /surgery-requests/{id}/cancel

GET    /surgeries
POST   /surgeries
GET    /surgeries/{id}
PUT    /surgeries/{id}

POST   /surgeries/{id}/schedule
POST   /surgeries/{id}/reschedule
POST   /surgeries/{id}/postpone

GET    /schedule
GET    /operating-list

GET    /theatres
GET    /theatres/{id}

POST   /surgeries/{id}/team
PUT    /surgeries/{id}/team

GET    /surgeries/{id}/preop
POST   /surgeries/{id}/preop
POST   /surgeries/{id}/preop/complete

GET    /surgeries/{id}/consent
POST   /surgeries/{id}/consent
POST   /surgeries/{id}/consent/finalize

GET    /surgeries/{id}/checklist
POST   /surgeries/{id}/checklist
POST   /surgeries/{id}/checklist/complete

POST   /surgeries/{id}/receive
POST   /surgeries/{id}/start
POST   /surgeries/{id}/complete

GET    /surgeries/{id}/procedure
POST   /surgeries/{id}/procedure

GET    /surgeries/{id}/specimens
POST   /surgeries/{id}/specimens

GET    /surgeries/{id}/implants
POST   /surgeries/{id}/implants

GET    /surgeries/{id}/recovery
POST   /surgeries/{id}/recovery
POST   /surgeries/{id}/recovery/complete

GET    /dashboard
GET    /reports
```

Use existing API standards.

---

# 65. API Security

Every API must validate:

- authentication;
- permission;
- organization;
- hospital;
- branch;
- patient;
- admission;
- surgery;
- theatre.

Never trust client-provided relationships.

For example:

```text id="7lcv7c"
POST /ot/surgeries/100/complete
```

must verify that surgery `100` belongs to an authorized hospital and that the user has permission to complete it.

---

# 66. Events

Potential events:

```text id="k9y86s"
SurgeryRequested
SurgeryApproved
SurgeryScheduled
SurgeryRescheduled
SurgeryPostponed
SurgeryCancelled

PatientReceivedForSurgery
PreoperativeAssessmentCompleted
ConsentFinalized
SurgicalChecklistCompleted

SurgeryStarted
SurgeryCompleted
ProcedureDocumented

SpecimenCollected
ImplantRecorded
SurgicalComplicationRecorded

RecoveryStarted
RecoveryCompleted
SurgicalPatientTransferred

SurgicalChargeableEventCreated
```

Follow existing project conventions.

---

# 67. Jobs

Potential jobs:

```text id="y0s7d4"
SendSurgeryReminder
DetectMissingPreoperativeRequirements
DetectScheduleConflicts
GenerateOTDailyList
GenerateOTUtilizationReport
NotifySurgeryCancellation
NotifyPendingSurgicalCase
```

Jobs must be:

- idempotent;
- retry-safe;
- auditable.

---

# 68. Notifications

### Patient/authorized contact

Where supported:

- surgery scheduled;
- surgery postponed;
- surgery cancelled;
- discharge/follow-up information.

### Surgical team

- case assigned;
- schedule changed;
- emergency case;
- missing pre-op requirement.

### OT coordinator

- scheduling conflict;
- missing consent;
- missing clearance;
- missing implant;
- missing blood;
- delayed case.

Notifications must respect privacy and authorization.

---

# 69. Database Design

Potential tables:

```text id="0af0o1"
ot_procedure_types
ot_procedure_categories
ot_specialties

ot_complexes
ot_theatres
ot_theatre_capabilities

ot_surgery_requests
ot_surgery_request_items

ot_surgeries
ot_surgery_procedures
ot_surgery_status_history

ot_surgery_schedules
ot_surgery_schedule_history

ot_surgical_team
ot_surgical_team_history

ot_preoperative_assessments
ot_preoperative_checklists

ot_consents
ot_surgical_safety_checklists
ot_surgical_safety_checklist_items

ot_operating_records
ot_operative_notes

ot_surgical_complications
ot_surgical_counts

ot_specimens
ot_specimen_events

ot_implants
ot_implant_usage

ot_recovery_records
ot_transfer_records

ot_cancellations
ot_postponements
ot_delay_records

ot_chargeable_events
```

These are conceptual.

Before creating them, inspect whether equivalent tables/abstractions already exist.

---

# 70. Suggested `ot_surgeries`

Conceptual fields:

```text id="vry10c"
id
organization_id
hospital_id
branch_id
surgery_number
patient_id
admission_id
encounter_id
surgery_request_id
primary_procedure_id
specialty_id
priority
surgery_type
scheduled_date
scheduled_start_at
scheduled_end_at
actual_start_at
actual_end_at
theatre_id
status
cancellation_reason
postponement_reason
primary_surgeon_id
anesthetist_id
created_by
completed_by
completed_at
created_at
updated_at
```

Surgery number must be unique and concurrency-safe.

Example:

```text
OT-2026-00001234
```

Never use `COUNT()` for numbering.

---

# 71. Suggested `ot_surgery_team`

```text id="kz17bj"
id
organization_id
hospital_id
surgery_id
user_id
provider_id
role
assigned_at
removed_at
status
assigned_by
created_at
updated_at
```

Adapt to the actual provider/user architecture.

---

# 72. Suggested `ot_operating_records`

```text id="d4exd3"
id
organization_id
hospital_id
surgery_id
patient_id
preoperative_diagnosis
postoperative_diagnosis
actual_procedure
anesthesia_type
position
findings
estimated_blood_loss
complications
procedure_started_at
procedure_completed_at
status
documented_by
finalized_by
finalized_at
created_at
updated_at
```

Detailed procedure documentation may be normalized further depending on the existing EMR architecture.

---

# 73. Concurrency

Protect:

- OT schedule;
- theatre booking;
- surgical team assignment;
- patient scheduling;
- procedure completion;
- specimen numbering;
- implant recording;
- chargeable events.

Example:

```text id="2yw9jw"
Two users
   ↓
Same OT
   ↓
Same time
   ↓
Only valid non-conflicting booking succeeds
```

Use transactions and locking where necessary.

---

# 74. Scheduling Integrity

Prevent:

- overlapping surgery in the same theatre;
- overlapping surgeon assignments where prohibited;
- overlapping anesthetist assignments where prohibited;
- duplicate patient booking;
- booking inactive theatre;
- booking theatre outside its configured capability where prohibited.

Emergency override must be explicit and audited.

---

# 75. Clinical Safety Rules

The system must prioritize:

1. Correct patient
2. Correct procedure
3. Correct site/side
4. Correct consent
5. Correct team
6. Correct theatre
7. Correct anesthesia workflow
8. Correct implant/specimen documentation
9. Correct postoperative destination
10. Complete audit trail

The system is a safety-support tool and does not replace professional clinical judgment.

---

# 76. Negative Tests

Verify:

- surgery cannot be scheduled without required patient;
- invalid procedure rejected;
- inactive theatre cannot be booked;
- schedule conflict rejected;
- missing mandatory consent blocks where configured;
- site mismatch detected;
- patient mismatch prevented;
- cancelled surgery cannot be started;
- completed surgery cannot be silently reopened;
- duplicate specimen prevented;
- duplicate implant usage prevented;
- duplicate billing event prevented;
- unauthorized user cannot complete surgery.

---

# 77. Security Tests

Mandatory:

```text id="b5yt3j"
Hospital A OT User
       ↓
Hospital B Surgery
```

must fail.

Also test:

- unauthorized surgeon access;
- unauthorized OT access;
- direct ID manipulation;
- API scope bypass;
- privilege escalation;
- report export;
- consent access;
- operative note access;
- break-glass.

---

# 78. Performance

Optimize:

- OT operating list;
- surgery search;
- schedule conflict detection;
- today's cases;
- theatre dashboard;
- patient surgical history;
- pending pre-op requirements.

Use:

- indexes;
- pagination;
- eager loading;
- caching of relatively static procedure/theatre masters;
- efficient date-range queries.

---

# 79. Suggested Indexes

Potential indexes:

```text id="ck7z1g"
surgery_number
patient_id
admission_id
encounter_id
procedure_id
surgeon_id
anesthetist_id
theatre_id
status
scheduled_date
scheduled_start_at
scheduled_end_at
actual_start_at
actual_end_at
created_at
```

Composite:

```text id="a7p3es"
theatre_id + scheduled_start_at + scheduled_end_at
surgeon_id + scheduled_start_at
anesthetist_id + scheduled_start_at
hospital_id + scheduled_date + status
patient_id + scheduled_date
```

Validate against real query patterns.

---

# 80. Transactions

Use transactions for:

### Scheduling

```text id="l93xq1"
Validate patient
Validate theatre
Validate team
Check conflict
Create schedule
Create history
Publish event
```

### Surgery start

```text id="4v0qhs"
Validate status
Validate pre-op requirements
Record patient reception
Start procedure
Create audit
```

### Surgery completion

```text id="9gk9cm"
Finalize procedure
Finalize operative data
Record complications/specimens/implants
Create chargeable events
Publish completion event
```

### Cancellation

```text id="5w1w3g"
Validate state
Record reason
Close schedule
Release resources
Audit
Notify
```

---

# 81. Idempotency

Important operations must be idempotent:

- schedule;
- start;
- complete;
- cancellation;
- specimen creation;
- implant recording;
- charge generation;
- notifications.

Repeated API requests must not create duplicate surgical records.

---

# 82. FHIR Readiness

Prepare mappings for:

```text id="v6z6w5"
Patient
Encounter
Procedure
ServiceRequest
Practitioner
PractitionerRole
Organization
Location
Observation
DiagnosticReport
Specimen
Device
MedicationRequest
MedicationAdministration
CarePlan
DocumentReference
Consent
Task
```

Particularly:

### Procedure

Represent surgical procedure.

### ServiceRequest

Represent requested surgery.

### Consent

Represent surgical consent.

### Procedure

Link actual performed procedure.

### Specimen

Represent surgical specimen.

### Device

Represent implanted devices where appropriate.

---

# 83. HL7 Readiness

Prepare for future:

- ADT;
- ORM;
- ORU;
- surgical scheduling;
- procedure events.

Use the existing interoperability architecture.

Do not place external-system calls directly inside controllers.

---

# 84. AI Readiness

Future AI capabilities may include:

### Operational

- OT schedule optimization;
- predicted case duration;
- delay prediction;
- theatre utilization forecasting;
- cancellation pattern analysis.

### Clinical documentation

- operative note draft assistance;
- surgical timeline summarization;
- preoperative information summarization.

### Safety

- checklist completeness;
- inconsistency detection;
- procedure/site mismatch detection;
- missing-document detection.

AI must be advisory.

AI must never:

- independently schedule emergency surgery;
- approve surgery;
- change procedure;
- override consent;
- decide surgical eligibility;
- diagnose;
- modify operative notes;
- finalize documentation;
- order blood;
- select implants autonomously.

---

# 85. AI Human Review

Future AI workflow:

```text id="4wx5hc"
Clinical/Operational Data
       ↓
AI Suggestion
       ↓
Human Review
       ↓
Accept / Modify / Reject
       ↓
Audit
```

Never:

```text id="k4p4mi"
AI
 ↓
Autonomous Surgical Decision
```

---

# 86. Reporting

Provide:

### Surgery

- surgeries by date;
- specialty;
- procedure;
- surgeon;
- theatre;
- priority;
- status.

### OT

- utilization;
- turnaround;
- delays;
- cancellations;
- postponements;
- emergency cases.

### Clinical

- procedure duration;
- complications;
- specimens;
- implants.

### Operational

- pending cases;
- missing pre-op requirements;
- scheduling conflicts;
- delayed cases.

---

# 87. Surgical Quality/Operational Indicators

Provide configurable reporting for:

- cancellation rate;
- postponement rate;
- OT utilization;
- turnover time;
- scheduled vs actual duration;
- first-case delay;
- documentation completion;
- checklist completion;
- specimen traceability;
- implant traceability.

Do not present these as universal clinical quality standards.

---

# 88. UI Menu

Recommended:

```text id="1n9wmy"
OT / Surgery
├── Dashboard
├── Surgery Requests
├── Surgical Cases
├── Operating List
├── Schedule
├── Theatres
├── Procedures
├── Surgical Teams
├── Preoperative
│   ├── Assessments
│   ├── Checklists
│   └── Consents
├── Surgical Safety Checklist
├── Intraoperative
│   ├── Current Cases
│   ├── Operative Notes
│   ├── Specimens
│   ├── Implants
│   └── Complications
├── Recovery / PACU
├── Cancellations
├── Postponements
├── Reports
└── Settings
```

---

# 89. Surgeon Workspace

Show:

```text id="n2nqwo"
Patient Header
MRN
Admission
Allergies
Diagnosis
Procedure
Site/Side
Consent
Investigations
Imaging
Blood Status
Implant Status
Pre-op Status
Checklist
Operative Note
Specimens
Implants
Complications
Postoperative Plan
```

---

# 90. OT Nurse Workspace

Show:

```text id="v9n5bs"
Today's Operating List
Patient
Procedure
Surgeon
Anesthetist
Theatre
Pre-op Checklist
Consent
Patient Verification
Time Out
Counts
Specimens
Implants
Recovery
Transfer
```

---

# 91. OT Coordinator Workspace

Show:

```text id="fl8f6k"
Operating List
Theatre Availability
Conflicts
Pending Cases
Missing Requirements
Emergency Cases
Delays
Cancellations
Utilization
```

---

# 92. Anesthesia Workspace Boundary

If anesthesia functionality is later expanded, the architecture should support:

```text id="j4jqu8"
Surgical Case
    ↓
Anesthesia Case
    ├ Pre-op Assessment
    ├ Anesthesia Plan
    ├ Intraoperative Record
    ├ Medications
    ├ Monitoring
    └ Post-anesthesia Recovery
```

Do not build a competing anesthesia system in the basic OT implementation unless specifically required.

---

# 93. Non-Goals

Do NOT implement as part of basic Phase 10:

- full anesthesia information system;
- ICU;
- emergency department;
- blood bank;
- pharmacy inventory;
- enterprise inventory;
- procurement;
- accounting;
- insurance claims;
- PACS;
- LIS;
- independent EMR;
- independent patient registration;
- autonomous AI surgery decisions.

---

# 94. Implementation Order

Implement in this sequence:

```text id="0p0k4d"
1. Inspect existing architecture
2. Inspect Phases 0–9
3. Inspect Phase 3 clinical orders/EMR
4. Inspect Phase 4 billing
5. Inspect Phase 5 LIS
6. Inspect Phase 6 radiology
7. Inspect Phase 7 pharmacy
8. Inspect Phase 8 IPD
9. Inspect Phase 9 nursing

10. Surgical procedure masters
11. OT/theatre masters
12. Theatre capabilities
13. Surgery request
14. Surgical case
15. Surgery numbering
16. Surgical team
17. Scheduling
18. Conflict detection

19. Preoperative assessment
20. Consent
21. Preoperative checklist
22. Patient verification
23. Surgical safety checklist

24. Patient reception
25. Procedure start
26. Intraoperative documentation
27. Operative note
28. Complications
29. Surgical counts
30. Specimens
31. Implants

32. Recovery/PACU
33. Postoperative transfer
34. Cancellation
35. Postponement

36. Billing integration
37. LIS integration
38. Radiology integration
39. Pharmacy integration
40. Blood Bank integration foundation
41. Inventory integration foundation

42. Dashboard
43. Operating list
44. Reports
45. Notifications
46. Audit
47. API
48. FHIR
49. HL7 readiness

50. Unit tests
51. Feature tests
52. Integration tests
53. Security tests
54. Concurrency tests
55. Clinical safety tests
56. Performance tests
57. Documentation
58. Deployment verification
```

---

# 95. Definition of Done

Phase 10 is complete when:

### Surgery

- surgery request;
- approval;
- case;
- procedure;
- scheduling;
- rescheduling;
- postponement;
- cancellation;
- emergency workflow.

### OT

- theatre management;
- capabilities;
- operating list;
- team assignment;
- conflict detection;
- utilization.

### Preoperative

- assessment;
- checklist;
- consent;
- identity verification;
- site/side verification.

### Intraoperative

- sign-in/time-out/sign-out;
- procedure;
- operative note;
- complications;
- counts;
- specimens;
- implants.

### Recovery

- PACU;
- recovery documentation;
- postoperative transfer.

### Integration

- Patient;
- Encounter;
- IPD;
- Nursing;
- Billing;
- LIS;
- Radiology;
- Pharmacy;
- Blood Bank foundation;
- Inventory foundation.

### Security

- RBAC;
- hospital scope;
- patient access;
- audit;
- break-glass.

### Reliability

- transactions;
- concurrency;
- idempotency;
- duplicate prevention.

### Interoperability

- FHIR mappings;
- HL7 readiness.

### Testing

- unit;
- feature;
- integration;
- security;
- concurrency;
- negative;
- clinical safety.

### Documentation

- architecture;
- DB;
- API;
- deployment;
- configuration;
- integration;
- testing.

---

# 96. FINAL AI IMPLEMENTATION PROMPT

You are a senior Laravel architect, hospital information-system architect, surgical workflow specialist, perioperative informatics architect, database engineer, security engineer, and healthcare interoperability engineer.

Implement **Phase 10 — OT / Surgery Management** in the existing Hospital Management System repository.

## FIRST — INSPECT BEFORE CODING

Before writing code:

1. Inspect the complete repository.
2. Identify the existing modular architecture.
3. Inspect Phases 0–9.
4. Inspect existing:
   - Patient/MPI;
   - Appointment;
   - Clinical Encounter;
   - Clinical Orders;
   - Diagnosis;
   - Billing;
   - LIS;
   - Radiology;
   - Pharmacy;
   - IPD;
   - Nursing;
   - Workflow;
   - Audit;
   - Notifications;
   - File Management;
   - Users;
   - Roles;
   - Permissions;
   - API;
   - testing architecture.

Create a dependency map before implementation.

## MANDATORY RULE

The existing modular architecture is already implemented.

**DO NOT create a second modular architecture.**

Reuse all existing shared services and domain entities.

Do not duplicate:

- Patient;
- Encounter;
- Diagnosis;
- Clinical Orders;
- Prescription;
- Medication;
- Pharmacy;
- Billing;
- Admission;
- Bed;
- Nursing;
- Users;
- Roles;
- Permissions;
- Audit;
- Notification;
- Workflow;
- File Management.

## IMPLEMENT

Build:

```text id="w2qf8b"
OT / Surgery Management
```

including:

- surgery requests;
- surgical cases;
- procedure master;
- theatre master;
- theatre capability;
- scheduling;
- operating list;
- conflict detection;
- surgical team;
- pre-op assessment;
- consent;
- pre-op checklist;
- patient verification;
- surgical safety checklist;
- intraoperative documentation;
- operative note;
- surgical complications;
- surgical counts;
- specimen management;
- implant documentation;
- recovery/PACU;
- postoperative transfer;
- cancellation;
- postponement;
- emergency surgery;
- billing integration;
- LIS integration;
- radiology integration;
- pharmacy integration;
- blood bank integration boundary;
- inventory integration boundary;
- notifications;
- audit;
- API;
- FHIR readiness;
- HL7 readiness;
- reporting.

## CRITICAL BOUNDARIES

### Phase 3 owns

- clinical encounter;
- diagnosis;
- clinical orders;
- prescription.

### Phase 4 owns

- charges;
- invoices;
- payments;
- receipts;
- refunds.

### Phase 5 owns

- laboratory;
- specimens after handover to LIS;
- laboratory results.

### Phase 6 owns

- radiology;
- PACS;
- DICOM.

### Phase 7 owns

- medications;
- pharmacy;
- dispensing;
- medication stock.

### Phase 8 owns

- admission;
- bed;
- current location;
- inpatient transfer;
- operational discharge.

### Phase 9 owns

- nursing;
- nursing observations;
- MAR;
- nursing care.

### Phase 10 owns

- surgical request;
- surgical case;
- theatre;
- surgical schedule;
- surgical team;
- perioperative workflow;
- safety checklist;
- operative documentation;
- surgical specimens before laboratory handover;
- implant clinical documentation;
- recovery transition.

Do not violate these boundaries.

## SURGICAL SAFETY

The workflow must support:

```text id="qtrv6b"
Correct Patient
Correct Procedure
Correct Site/Side
Correct Consent
Correct Team
Correct Theatre
Correct Anesthesia Workflow
Correct Implant/Specimen
Correct Postoperative Destination
Complete Audit Trail
```

The software supports safety; it does not replace clinical judgment.

## SCHEDULING SAFETY

Prevent:

- theatre conflicts;
- surgeon conflicts;
- anesthetist conflicts;
- patient conflicts;
- invalid theatre capability;
- duplicate bookings.

Use database transactions and appropriate locking.

## CONSENT

Never silently modify finalized consent.

Use versioning/amendment.

## OPERATIVE DOCUMENTATION

Never silently modify finalized:

- operative notes;
- checklist;
- procedure;
- specimen;
- implant;
- complication.

Use amendment/correction workflows.

## BILLING

Use:

```text id="kzaj51"
Surgery
 ↓
Chargeable Event
 ↓
Existing Billing Engine
```

Never create a separate OT billing engine.

## SPECIMEN

Use:

```text id="o2dx9j"
Surgery
 ↓
Specimen
 ↓
Traceability
 ↓
LIS
```

Do not implement pathology/LIS processing.

## IMPLANT

Use:

```text id="y7s8y4"
Surgery
 ↓
Implant Usage
 ↓
Inventory Integration
```

Do not create an OT stock ledger.

## BLOOD

Use:

```text id="5lkw3b"
Surgery
 ↓
Blood Requirement
 ↓
Blood Bank
```

Do not implement blood inventory or compatibility management.

## PHARMACY

Use:

```text id="8c4k0m"
Surgery
 ↓
Medication Requirement
 ↓
Phase 7 Pharmacy
```

Do not create OT medication inventory.

## SECURITY

Mandatory tests:

```text id="n0tjsh"
Hospital A OT User
       ↓
Hospital B Surgery
```

must fail.

Also test:

- direct URL manipulation;
- API IDOR;
- privilege escalation;
- unauthorized operative-note access;
- unauthorized consent access;
- unauthorized schedule changes;
- unauthorized completion;
- unauthorized exports;
- break-glass.

## CONCURRENCY

Prove that two users cannot successfully book the same theatre/time combination when the cases conflict.

Also prevent:

- duplicate surgery start;
- duplicate surgery completion;
- duplicate specimen;
- duplicate implant usage;
- duplicate chargeable events.

## API

Implement:

```text id="c1yn4n"
/api/v1/ot
```

with secure REST endpoints.

Use existing API response and authorization conventions.

## UI

Implement:

```text id="z1j7bw"
OT / Surgery
├── Dashboard
├── Surgery Requests
├── Surgical Cases
├── Operating List
├── Schedule
├── Theatres
├── Procedures
├── Surgical Teams
├── Preoperative
├── Surgical Safety Checklist
├── Intraoperative
├── Specimens
├── Implants
├── Recovery / PACU
├── Cancellations
├── Postponements
├── Reports
└── Settings
```

Reuse existing Blade/AdminLTE components.

## EVENTS

Implement appropriate events including:

```text id="s7w2ep"
SurgeryRequested
SurgeryApproved
SurgeryScheduled
SurgeryCancelled
SurgeryPostponed
PreoperativeAssessmentCompleted
ConsentFinalized
SurgicalChecklistCompleted
SurgeryStarted
SurgeryCompleted
SpecimenCollected
ImplantRecorded
SurgicalComplicationRecorded
RecoveryCompleted
SurgicalPatientTransferred
SurgicalChargeableEventCreated
```

## JOBS

Where required:

```text id="4v0x3p"
SendSurgeryReminder
DetectMissingPreoperativeRequirements
DetectScheduleConflicts
GenerateOTDailyList
GenerateOTUtilizationReport
NotifyPendingSurgicalCase
```

Make jobs idempotent and retry-safe.

## TESTING

Write and execute:

### Unit

- procedure;
- schedule;
- conflict;
- checklist;
- consent;
- specimen;
- implant;
- complication;
- cancellation.

### Feature

- surgery request;
- approval;
- scheduling;
- pre-op;
- checklist;
- start;
- complete;
- recovery;
- cancellation;
- postponement.

### Integration

```text id="0c3q67"
Patient → Surgery
Clinical Order → Surgery
Surgery → IPD
Surgery → Nursing
Surgery → Billing
Surgery → LIS
Surgery → Radiology
Surgery → Pharmacy
Surgery → Blood Bank interface
Surgery → Audit
```

### Security

- multi-hospital;
- RBAC;
- IDOR;
- API authorization;
- privilege escalation.

### Concurrency

- theatre booking;
- surgeon booking;
- surgery start;
- surgery completion;
- specimen;
- billing.

### Negative

Test:

- invalid patient;
- invalid procedure;
- inactive theatre;
- conflicting schedule;
- missing mandatory consent;
- site mismatch;
- cancelled case;
- duplicate specimen;
- duplicate implant;
- duplicate billing event;
- unauthorized completion.

Only report tests as passed if actually executed.

## AI SAFETY

Prepare future AI features for:

- schedule optimization;
- duration prediction;
- OT utilization;
- delay prediction;
- operative-note drafting;
- surgical timeline summarization;
- checklist completeness;
- inconsistency detection.

AI must never autonomously:

- approve surgery;
- modify surgery;
- override consent;
- select a surgical procedure;
- order blood;
- select implants;
- diagnose;
- finalize operative notes;
- make clinical decisions.

Every AI-generated suggestion must require human review and be auditable.

## FINAL IMPLEMENTATION REPORT

After implementation, provide:

1. architecture changes;
2. database/migrations;
3. models;
4. services/actions;
5. controllers;
6. Form Requests;
7. policies;
8. routes;
9. permissions;
10. UI;
11. surgery workflow;
12. scheduling;
13. pre-op;
14. checklist;
15. operative documentation;
16. specimen;
17. implant;
18. recovery;
19. cancellation/postponement;
20. billing integration;
21. LIS integration;
22. radiology integration;
23. pharmacy integration;
24. IPD integration;
25. nursing integration;
26. audit/security;
27. API;
28. FHIR/HL7 readiness;
29. tests actually executed;
30. deployment instructions;
31. known limitations;
32. deferred features.

The implementation must be **production-grade, secure, clinically traceable, concurrency-safe, auditable, multi-hospital capable, interoperable, and consistent with the existing HMS modular architecture**.

Do not stop at CRUD. Implement the complete surgical workflow.