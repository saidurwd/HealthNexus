
# Phase 9 — Nursing & Inpatient Clinical Care
## Module Specification & AI Implementation Prompt

## 1. Module Overview

Build **Phase 9 — Nursing & Inpatient Clinical Care** as a production-grade module within the existing Hospital Management System.

The module provides the operational and clinical nursing layer for admitted patients.

Core workflow:

```text
IPD Admission
      ↓
Nursing Assignment
      ↓
Initial Nursing Assessment
      ↓
Vital Signs / Observations
      ↓
Nursing Care Plan
      ↓
Medication Administration
      ↓
Nursing Tasks & Interventions
      ↓
Progress / Nursing Notes
      ↓
Handover
      ↓
Ongoing Reassessment
      ↓
Discharge Nursing Workflow
```

The module must integrate with:

- Phase 0 — Foundation & Platform
- Phase 1 — Patient / MPI
- Phase 2 — Appointment & Scheduling
- Phase 3 — OPD / Clinical Encounter / EMR
- Phase 4 — Billing & Revenue
- Phase 5 — LIS
- Phase 6 — Radiology / PACS
- Phase 7 — Pharmacy & Medication Management
- Phase 8 — IPD / Admission / Bed Management
- Future Phase 10 — OT / Surgery
- Future Phase 11 — ICU / Critical Care
- Future Phase 12 — Emergency
- Future Phase 13 — Blood Bank
- Future Phase 14 — Insurance
- Future Phase 15 — Inventory / Procurement
- Future Phase 17 — Patient Portal / Mobile
- Future Phase 18 — FHIR / HL7 / Interoperability
- Future Phase 20 — AI / Clinical Intelligence

---

# 2. Critical Architecture Rule

The existing modular architecture is **already implemented**.

Before coding, inspect and reuse it.

Do **NOT** create another:

- modular framework;
- Patient system;
- Clinical Encounter system;
- EMR;
- Prescription system;
- Medication master;
- Pharmacy system;
- Billing system;
- Admission system;
- Bed management system;
- User/RBAC system;
- Notification system;
- Audit system;
- File management system;
- Workflow engine.

Phase 9 is a **Nursing and Inpatient Clinical Care extension**, not a replacement for those systems.

---

# 3. Primary Objectives

The module should provide:

1. Nursing dashboard
2. Nurse/ward assignment
3. Patient assignment
4. Nursing shift management
5. Initial nursing assessment
6. Ongoing nursing assessment
7. Vital signs
8. Pain assessment
9. Fall-risk assessment
10. Pressure-injury risk assessment
11. Nutrition assessment
12. Fluid balance
13. Intake/output monitoring
14. Nursing care plans
15. Nursing diagnoses
16. Nursing interventions
17. Nursing tasks
18. Medication Administration Record (MAR)
19. Medication administration safety checks
20. Medication refusal/omission documentation
21. PRN medication documentation
22. IV/infusion monitoring
23. Device/tube/line monitoring
24. Wound/skin assessment
25. Patient education
26. Nursing notes
27. Shift handover
28. Clinical escalation
29. Observation alerts
30. Discharge nursing checklist
31. Nursing documentation audit
32. Multi-hospital support
33. API
34. FHIR readiness
35. Future AI integration points

---

# 4. Nursing Domain Boundary

The architectural relationship should be:

```text
Patient
   ↓
IPD Admission
   ↓
Inpatient Encounter
   ↓
Nursing Care
   ├── Assessment
   ├── Vitals
   ├── Care Plan
   ├── Interventions
   ├── Medication Administration
   ├── Intake / Output
   ├── Notes
   ├── Handover
   └── Discharge Nursing
```

Clinical physician workflow remains:

```text
Clinical Encounter
   ↓
Diagnosis
   ↓
Orders
   ↓
Prescription
```

Nursing consumes and documents execution/observation of those orders.

---

# 5. Appointment vs Encounter vs Admission vs Nursing Episode

Maintain clear distinctions:

```text
Appointment
    = Planned visit

Encounter
    = Clinical interaction

Admission
    = Inpatient stay

Nursing Episode
    = Nursing care associated with the inpatient stay
```

Do not create unnecessary duplicate encounter records.

A nursing episode should reference the appropriate:

- patient;
- admission;
- inpatient encounter.

---

# 6. Nursing Episode

A nursing episode represents nursing care during an inpatient stay.

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
admission_id
patient_id
encounter_id
ward_id
room_id
bed_id
primary_nurse_id
start_at
end_at
status
created_by
created_at
updated_at
```

Possible statuses:

```text
Planned
Active
Transferred
Completed
Cancelled
```

The actual patient location should continue to come from Phase 8.

Do not maintain a competing bed-location source of truth.

---

# 7. Nursing Assignment

Support assignment of:

- nurse;
- nursing assistant;
- charge nurse;
- ward nurse;
- team;
- shift.

Assignment can be:

- patient-based;
- bed-based;
- ward-based;
- shift-based.

Example:

```text
Ward A
  ↓
Morning Shift
  ↓
Nurse Team 1
  ├ Patient A
  ├ Patient B
  └ Patient C
```

Assignments must be auditable.

---

# 8. Nurse-to-Patient Assignment

Track:

```text
Patient
Admission
Ward
Bed
Nurse
Shift
Assignment Type
Start
End
Assigned By
```

Do not overwrite previous assignments.

Maintain assignment history.

---

# 9. Shift Management

Support configurable shifts:

- Morning
- Evening
- Night
- Custom

Shift configuration:

```text
Shift Name
Start Time
End Time
Grace Period
Active
```

Do not assume every hospital uses the same shift schedule.

---

# 10. Nursing Handover

Provide structured shift handover.

Workflow:

```text
Outgoing Nurse
       ↓
Prepare Handover
       ↓
Incoming Nurse
       ↓
Review
       ↓
Acknowledge
```

Handover may include:

- patient identity;
- diagnosis;
- current condition;
- allergies;
- code status where configured;
- current medications;
- pending medications;
- recent observations;
- abnormal observations;
- lines/tubes/devices;
- wounds;
- mobility;
- fall risk;
- pressure injury risk;
- nutrition;
- intake/output;
- pending investigations;
- pending orders;
- pending procedures;
- care plan;
- discharge plan;
- escalation concerns.

The system must not generate unsupported clinical conclusions.

---

# 11. Initial Nursing Assessment

Create structured admission nursing assessment.

Potential sections:

### Patient Information

- identity;
- admission;
- source;
- accompanying person.

### General Assessment

- consciousness;
- orientation;
- appearance;
- mobility;
- communication.

### Vital Signs

- temperature;
- pulse;
- respiratory rate;
- blood pressure;
- SpO₂;
- pain.

### System Assessment

- neurological;
- respiratory;
- cardiovascular;
- gastrointestinal;
- genitourinary;
- musculoskeletal;
- skin;
- psychosocial.

### Risk Assessment

- fall;
- pressure injury;
- nutrition;
- infection;
- aspiration;
- other configured assessments.

---

# 12. Vital Signs

Vital signs are a core nursing function.

Support configurable observations such as:

```text
Temperature
Pulse
Respiratory Rate
Blood Pressure
SpO2
Pain Score
Weight
Height
BMI
Blood Glucose
Consciousness Score
```

Additional clinical observations should be configurable.

### Critical rule

Never overwrite previous observations.

Each observation is a time-series clinical record.

---

# 13. Observation Model

Conceptually:

```text
Patient
Admission
Encounter
Observation Type
Value
Unit
Reference Range
Observed At
Observed By
Device Source
Status
```

Observation status:

```text
Preliminary
Final
Corrected
Cancelled
```

Use the existing clinical observation infrastructure if Phase 3 already provides it.

Do not create a duplicate observation model unless necessary.

---

# 14. Vital Sign Frequency

Support configurable schedules:

```text
Every 15 minutes
Every 30 minutes
Hourly
2 hourly
4 hourly
6 hourly
8 hourly
12 hourly
Daily
PRN
```

Frequency should be driven by care plans/orders where applicable.

Do not hardcode universal clinical rules.

---

# 15. Observation Alerts

The system may identify configured threshold exceptions.

Example:

```text
Observation
   ↓
Configured Threshold
   ↓
Alert
   ↓
Nurse Review
   ↓
Escalation if required
```

The system should clearly distinguish:

- measured value;
- configured threshold;
- alert;
- clinical decision.

An alert is not automatically a diagnosis.

---

# 16. Pain Assessment

Support configurable pain scales.

Examples may include:

- numeric;
- visual analog;
- faces;
- custom hospital-approved scale.

Capture:

```text
Pain Score
Location
Character
Onset
Duration
Aggravating Factors
Relieving Factors
Intervention
Reassessment
```

Do not hardcode one clinical scale if the hospital requires another.

---

# 17. Fall Risk Assessment

Provide a configurable assessment framework.

Capture:

- assessment tool;
- score;
- risk level;
- contributing factors;
- interventions;
- reassessment;
- assessor;
- timestamp.

Do not hardcode a single named clinical scoring system unless explicitly configured by the hospital.

---

# 18. Pressure Injury Assessment

Provide configurable pressure-injury risk assessment.

Capture:

- assessment tool;
- score;
- risk category;
- skin condition;
- interventions;
- reassessment;
- assessor;
- timestamp.

The system should support hospital-approved assessment protocols.

---

# 19. Nutrition Assessment

Capture:

- nutritional status;
- appetite;
- diet;
- allergies;
- swallowing concerns;
- feeding assistance;
- nutritional risk;
- dietitian referral;
- intervention.

The detailed clinical rules should remain configurable.

---

# 20. Fluid Balance

Support:

### Intake

- oral;
- IV;
- enteral;
- parenteral;
- other configured sources.

### Output

- urine;
- drain;
- emesis;
- stool;
- other configured outputs.

Calculate:

```text
Net Balance = Total Intake - Total Output
```

Support configurable time periods:

- shift;
- 12 hours;
- 24 hours;
- custom.

---

# 21. Intake/Output Record

Conceptual fields:

```text
id
patient_id
admission_id
encounter_id
type
category
amount
unit
route
recorded_at
recorded_by
source
notes
```

Never overwrite historical entries.

Corrections should be auditable.

---

# 22. Nursing Care Plan

The care plan is a central feature.

Workflow:

```text
Assessment
   ↓
Nursing Problem / Diagnosis
   ↓
Goal
   ↓
Intervention
   ↓
Frequency
   ↓
Responsible Nurse
   ↓
Evaluation
```

Care plans may be:

- active;
- paused;
- completed;
- discontinued;
- superseded.

---

# 23. Nursing Diagnosis

Provide structured nursing diagnosis support.

Conceptual fields:

```text
Diagnosis
Related Factors
Evidence
Priority
Status
Created By
Created At
```

Use configurable standardized terminology where available.

Do not confuse nursing diagnoses with physician medical diagnoses.

Physician diagnoses remain in the existing clinical diagnosis model.

---

# 24. Nursing Interventions

Examples:

- vital monitoring;
- repositioning;
- wound care;
- hygiene;
- mobility assistance;
- fall precautions;
- pressure injury prevention;
- nutrition support;
- education;
- comfort measures;
- monitoring;
- escalation.

Interventions should be configurable.

---

# 25. Nursing Task Engine

Support scheduled nursing tasks.

Task structure:

```text
Patient
Admission
Care Plan
Task Type
Due At
Priority
Assigned Nurse
Status
Completed At
Completed By
Outcome
Notes
```

Statuses:

```text
Pending
In Progress
Completed
Skipped
Refused
Cancelled
Overdue
```

Do not build a second enterprise task engine if the existing platform task framework can be reused appropriately.

---

# 26. Medication Administration Record — MAR

MAR is one of the most important Phase 9 components.

Workflow:

```text
Physician Prescription
       ↓
Pharmacy Verification
       ↓
Pharmacy Dispensing
       ↓
Medication Available
       ↓
Nursing MAR
       ↓
Patient Verification
       ↓
Medication Administration
       ↓
Documentation
```

Phase 9 owns **administration**, not prescribing or dispensing.

---

# 27. MAR Integration with Phase 7

Phase 7 owns:

- medication master;
- prescription integration;
- pharmacy verification;
- batch;
- dispensing;
- stock;
- returns.

Phase 9 owns:

- medication administration;
- administration status;
- administration time;
- administering nurse;
- patient verification;
- omission/refusal;
- administration notes.

---

# 28. Five/Six/Seven Rights Architecture

The MAR workflow should support configurable medication safety checks such as:

```text
Right Patient
Right Medication
Right Dose
Right Route
Right Time
Right Documentation
```

Additional checks may include:

- indication;
- allergy;
- expiry;
- batch;
- dilution;
- infusion rate.

Do not treat these as a substitute for clinical judgment.

---

# 29. Medication Administration Record

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
admission_id
encounter_id
patient_id
prescription_id
prescription_item_id
dispensing_id
medication_id
batch_id
scheduled_at
administered_at
dose
dose_unit
route
site
status
reason_if_not_administered
administered_by
witnessed_by
notes
created_at
updated_at
```

The exact schema must follow the actual Phase 7 prescription/dispensing models.

Do not duplicate medication master data.

---

# 30. MAR Statuses

Recommended statuses:

```text
Scheduled
Due
Administered
Held
Refused
Omitted
Missed
Cancelled
Not Available
Contraindicated
Deferred
Not Applicable
```

The final terminology should follow hospital policy.

---

# 31. Medication Administration Safety

Before administration, show:

```text
Patient
MRN
Allergies
Medication
Strength
Dose
Route
Scheduled Time
Frequency
Prescriber
Pharmacy Status
Batch
Expiry
Relevant Alerts
```

The nurse must explicitly confirm required safety checks.

---

# 32. Medication Refusal

If a patient refuses medication:

```text
Scheduled
   ↓
Refused
```

Record:

- reason;
- patient communication;
- nurse;
- time;
- notification/escalation where configured.

Never mark it as administered.

---

# 33. Medication Omission

If a medication is not administered:

Capture:

- omission reason;
- nurse;
- time;
- medication;
- dose;
- notification;
- follow-up action where required.

Examples of reasons may include:

- patient unavailable;
- medication unavailable;
- clinical hold;
- refusal;
- procedure;
- NPO;
- other configured reason.

Do not automatically classify a reason as clinically valid.

---

# 34. PRN Medication

Support PRN administration.

Workflow:

```text
PRN Prescription
       ↓
Patient Need
       ↓
Nurse Assessment
       ↓
Administration
       ↓
Response/Reassessment
```

Capture:

- reason/indication;
- dose;
- time;
- response;
- reassessment.

---

# 35. PRN Reassessment

Where configured, require reassessment after administration.

Example:

```text
PRN Medication
      ↓
Administration
      ↓
Reassessment Due
      ↓
Response Recorded
```

Timing should be configurable.

---

# 36. IV / Infusion Monitoring

Support documentation for:

- IV fluid;
- infusion;
- rate;
- start time;
- expected completion;
- actual completion;
- site;
- line;
- volume;
- complications;
- monitoring.

The system should integrate with prescriptions and orders.

Do not build an independent prescribing system.

---

# 37. Lines, Tubes & Devices

Track clinically relevant devices such as:

- IV cannula;
- central line;
- urinary catheter;
- drain;
- feeding tube;
- oxygen device;
- other configurable devices.

Track:

```text
Device
Insertion Date
Site
Inserted By
Status
Care Schedule
Last Assessment
Removal Date
Removal By
Complication
```

Do not duplicate hospital asset management.

This is clinical device tracking, not enterprise asset tracking.

---

# 38. Wound & Skin Care

Support:

- wound assessment;
- location;
- type;
- size;
- appearance;
- drainage;
- dressing;
- intervention;
- reassessment;
- images/documents where permitted.

Use existing File Management for attachments.

Do not store large clinical images directly in ordinary relational fields.

---

# 39. Clinical Documentation

Nursing notes should support:

- narrative notes;
- structured notes;
- shift notes;
- progress notes;
- procedure/intervention notes;
- education notes;
- handover notes.

Notes should have:

```text
Author
Timestamp
Note Type
Status
Signed At
Signed By
Amended At
```

---

# 40. Nursing Note Locking

Once finalized/signed:

> Do not silently modify the note.

Corrections should use:

- amendment;
- addendum;
- correction workflow.

Maintain original version and audit trail.

---

# 41. Nursing Documentation Templates

Support configurable templates for:

- admission assessment;
- shift assessment;
- progress note;
- wound care;
- discharge nursing note;
- handover;
- education.

Templates should be configuration-driven.

Do not hardcode hospital-specific forms into controllers.

---

# 42. Patient Education

Track:

```text
Topic
Education Provided
Method
Patient Understanding
Caregiver Involvement
Materials
Provided By
Date/Time
```

Examples:

- medication;
- wound care;
- mobility;
- diet;
- discharge instructions;
- follow-up;
- warning signs.

---

# 43. Escalation Workflow

Nurses may escalate concerns to:

- charge nurse;
- attending physician;
- on-call physician;
- rapid response team;
- ICU team;
- other configured service.

Workflow:

```text
Observation / Concern
       ↓
Nurse Assessment
       ↓
Escalation
       ↓
Recipient
       ↓
Acknowledgement
       ↓
Action
       ↓
Resolution
```

The system should document the workflow.

It must not autonomously diagnose the patient.

---

# 44. Clinical Alert

An alert should include:

```text
Patient
Admission
Observation/Trigger
Severity
Created At
Created By
Assigned To
Acknowledged At
Acknowledged By
Action
Resolved At
```

Potential severity:

```text
Informational
Low
Moderate
High
Critical
```

Severity rules must be configurable.

---

# 45. Rapid Response Foundation

Prepare a workflow for future emergency escalation:

```text
Concern
 ↓
Rapid Response Request
 ↓
Team Notification
 ↓
Acknowledgement
 ↓
Intervention
 ↓
Outcome
```

Do not implement a complete Emergency Department or ICU system in Phase 9.

---

# 46. Nursing Handover Dashboard

Show:

- patients assigned;
- latest vitals;
- abnormal observations;
- pending medications;
- overdue medications;
- pending tasks;
- care plan;
- lines/tubes;
- wounds;
- risks;
- pending investigations;
- pending transfers;
- discharge plans.

Only show information authorized for the user's scope.

---

# 47. Nursing Dashboard

Main metrics:

```text
My Patients
Ward Patients
Pending Assessments
Due Medications
Overdue Medications
Pending Tasks
Overdue Tasks
Critical Alerts
Pending Handover
Patients for Discharge
Patients Awaiting Transfer
```

---

# 48. Ward Dashboard

Ward-level dashboard:

```text
Total Patients
Occupied Beds
Nurses on Duty
Patient Assignments
Uncompleted Assessments
Medication Due
Medication Overdue
Critical Alerts
Fall Risk
Pressure Injury Risk
Pending Transfers
Pending Discharges
```

Do not expose patient information beyond the user's authorized ward/hospital scope.

---

# 49. Discharge Nursing Workflow

Phase 9 contributes nursing clearance.

Possible workflow:

```text
Doctor Discharge Plan
        ↓
Nursing Discharge Checklist
        ↓
Education
        ↓
Medication Education
        ↓
Device/Line Removal
        ↓
Patient Belongings
        ↓
Follow-up Instructions
        ↓
Nursing Clearance
        ↓
Phase 8 Discharge
```

Phase 8 remains the admission/discharge operational owner.

Phase 9 provides nursing completion.

---

# 50. Transfer Nursing Workflow

When Phase 8 transfers a patient:

```text
Transfer Request
       ↓
Nursing Preparation
       ↓
Handover
       ↓
Medication/Device Review
       ↓
Destination Ward
       ↓
Receiving Nurse Acknowledgement
```

The actual bed/location change remains owned by Phase 8.

---

# 51. ICU Integration

Future Phase 11 may consume:

- nursing observations;
- medication administration;
- patient history;
- current devices;
- care plans.

Phase 9 should provide integration points but must not implement the full ICU chart.

---

# 52. OT Integration

Future Phase 10 may consume:

- nursing assessment;
- allergies;
- current medications;
- vital signs;
- preoperative checklist;
- transfer status.

Prepare interfaces without implementing OT.

---

# 53. Emergency Integration

Future Phase 12 may consume:

- current admission;
- clinical history;
- medication history;
- allergies;
- nursing observations.

Do not implement ED functionality.

---

# 54. Blood Bank Integration

Future Phase 13 may integrate transfusion documentation with nursing administration.

Prepare an integration boundary:

```text
Blood Product
    ↓
Blood Bank
    ↓
Transfusion Order
    ↓
Nursing Administration
    ↓
Observation
```

Do not implement the Blood Bank module here.

---

# 55. Transfusion Administration Foundation

The nursing module may eventually record:

- blood product;
- unit;
- patient verification;
- compatibility verification;
- start time;
- completion;
- observations;
- reaction;
- administering nurse;
- witness.

However, full blood inventory and compatibility management belongs to Phase 13.

---

# 56. Database Design

Potential tables:

```text
nursing_episodes
nursing_assignments
nursing_shifts
nursing_handover
nursing_handover_items

nursing_assessments
nursing_assessment_items

nursing_observations
nursing_vitals

nursing_pain_assessments
nursing_risk_assessments

nursing_care_plans
nursing_care_plan_goals
nursing_care_plan_interventions

nursing_tasks
nursing_task_completions

nursing_medication_administrations
nursing_medication_administration_checks

nursing_fluid_balance
nursing_intake_records
nursing_output_records

nursing_devices
nursing_device_assessments

nursing_wound_assessments

nursing_notes
nursing_note_amendments

nursing_education

nursing_escalations
nursing_alerts

nursing_discharge_checklists
```

The actual schema must be determined after inspecting existing modules.

---

# 57. Reuse Existing Clinical Observation Infrastructure

If Phase 3 already contains a generic `observations` or equivalent clinical observation model:

**reuse it.**

Do not create:

```text
nursing_vitals
```

merely because the UI calls them vitals, if a reusable clinical observation abstraction already exists.

A nursing-specific projection/view/service may be preferable.

---

# 58. Reuse Existing Prescription/Medication Data

The system must maintain:

```text
Prescription
     ↓
Prescription Item
     ↓
Pharmacy Dispensing
     ↓
Medication Administration
```

Do not create a second medication master.

Do not create a second prescription.

Do not create a second pharmacy stock system.

---

# 59. Medication Administration Data Integrity

Every administration should be traceable to:

- patient;
- admission;
- encounter;
- prescription item;
- medication;
- dispensing where required;
- administering nurse;
- date/time.

Where the hospital permits administration without prior pharmacy dispensing, this must be an explicit configured workflow with authorization and audit.

Do not silently allow bypass.

---

# 60. Controlled Medication

Controlled medication administration must support additional authorization according to hospital policy.

Record:

- medication;
- dose;
- patient;
- prescription;
- dispensing;
- administering nurse;
- witness where required;
- timestamp;
- remaining quantity/reference where applicable.

Phase 7 remains responsible for controlled medication stock/register.

Phase 9 records administration.

---

# 61. High-Alert Medication

Support configurable high-alert flags inherited from Phase 7.

For high-alert medications, configurable additional checks may include:

- independent verification;
- second nurse/witness;
- dose confirmation;
- route confirmation.

Do not hardcode universal clinical rules.

---

# 62. Medication Administration Correction

Once an administration is finalized:

> Never silently edit it.

Corrections must be:

- amended;
- reversed/corrected;
- audited.

Original documentation must remain traceable.

---

# 63. Clinical Timeline

Patient timeline should include:

```text
Admission
Nursing Assessment
Vital Observation
Medication Administration
Nursing Intervention
Care Plan
Clinical Note
Escalation
Transfer
Discharge Nursing
```

Use the existing Patient/Clinical Timeline infrastructure where available.

---

# 64. Permissions

Suggested permissions:

```text
nursing.dashboard.view

nursing.assignment.view
nursing.assignment.create
nursing.assignment.update

nursing.assessment.view
nursing.assessment.create
nursing.assessment.update
nursing.assessment.finalize

nursing.vitals.view
nursing.vitals.create
nursing.vitals.update

nursing.care_plan.view
nursing.care_plan.create
nursing.care_plan.update
nursing.care_plan.complete

nursing.task.view
nursing.task.create
nursing.task.complete

nursing.mar.view
nursing.mar.administer
nursing.mar.hold
nursing.mar.refuse
nursing.mar.omit
nursing.mar.correct

nursing.handover.view
nursing.handover.create
nursing.handover.acknowledge

nursing.notes.view
nursing.notes.create
nursing.notes.finalize
nursing.notes.amend

nursing.escalation.view
nursing.escalation.create
nursing.escalation.acknowledge
nursing.escalation.resolve

nursing.discharge.view
nursing.discharge.complete

nursing.reports.view
nursing.reports.export

nursing.audit.view
nursing.settings.manage
```

---

# 65. Roles

Potential roles:

### Staff Nurse

- assigned patients;
- assessments;
- vitals;
- tasks;
- MAR;
- notes;
- handover.

### Senior Staff Nurse

Additional:

- care-plan review;
- escalation;
- assignment oversight.

### Charge Nurse

Additional:

- shift management;
- nurse assignment;
- ward dashboard;
- handover;
- escalation management.

### Nursing Supervisor

Additional:

- multi-ward oversight;
- reports;
- staffing;
- quality monitoring.

### Nursing Administrator

Additional:

- configuration;
- templates;
- workflows;
- reports.

### Physician

Read nursing data and respond to escalations according to authorization.

### Pharmacist

Read appropriate medication administration context where required.

Do not automatically give all roles access to all patient information.

---

# 66. API

Base:

```text
/api/v1/nursing
```

Suggested endpoints:

```http
GET    /dashboard

GET    /episodes
GET    /episodes/{id}

GET    /assignments
POST   /assignments
PUT    /assignments/{id}

GET    /assessments
POST   /assessments
PUT    /assessments/{id}
POST   /assessments/{id}/finalize

GET    /observations
POST   /observations

GET    /vitals
POST   /vitals

GET    /care-plans
POST   /care-plans
PUT    /care-plans/{id}

GET    /tasks
POST   /tasks
POST   /tasks/{id}/complete

GET    /mar
GET    /mar/{id}
POST   /mar/{id}/administer
POST   /mar/{id}/hold
POST   /mar/{id}/refuse
POST   /mar/{id}/omit
POST   /mar/{id}/correct

GET    /handover
POST   /handover
POST   /handover/{id}/acknowledge

GET    /notes
POST   /notes
POST   /notes/{id}/finalize
POST   /notes/{id}/amend

GET    /escalations
POST   /escalations
POST   /escalations/{id}/acknowledge
POST   /escalations/{id}/resolve

GET    /reports
```

Use the existing API response standard.

---

# 67. API Security

Every endpoint must verify:

- authentication;
- permission;
- organization;
- hospital;
- branch;
- ward;
- assignment scope;
- patient access;
- admission access.

Never trust IDs supplied by the client.

Example:

```text
POST /api/v1/nursing/mar/123/administer
```

must verify that MAR record `123` belongs to a patient/admission the nurse is authorized to access.

---

# 68. Events

Potential events:

```text
NursingEpisodeStarted
NurseAssigned
NursingAssessmentCompleted

VitalRecorded
CriticalObservationDetected

CarePlanCreated
CarePlanUpdated
CarePlanCompleted

NursingTaskCreated
NursingTaskCompleted
NursingTaskOverdue

MedicationAdministrationRecorded
MedicationHeld
MedicationRefused
MedicationOmitted

NursingHandoverCreated
NursingHandoverAcknowledged

NursingEscalationCreated
NursingEscalationAcknowledged
NursingEscalationResolved

NursingDischargeChecklistCompleted
```

Follow the project's existing event conventions.

---

# 69. Jobs

Potential jobs:

```text
GenerateDueNursingTasks
GenerateMedicationAdministrationSchedule
DetectOverdueMedicationAdministration
DetectOverdueNursingTasks
GenerateNursingEscalationNotification
GenerateShiftHandoverReminder
GenerateNursingQualityReport
```

Jobs must be:

- idempotent;
- retry-safe;
- observable;
- auditable.

---

# 70. Notifications

Examples:

### Nurse

- new patient assignment;
- medication due;
- medication overdue;
- task due;
- critical observation;
- handover pending.

### Charge Nurse

- unassigned patient;
- overdue medication;
- critical alert;
- overdue task;
- escalation pending.

### Physician

- nursing escalation;
- configured critical observation;
- relevant patient concern.

Notifications should respect hospital policy and user permissions.

---

# 71. Audit Requirements

Audit:

### Nursing

- assessment created;
- assessment finalized;
- observation recorded;
- observation corrected;
- care plan created/changed;
- intervention completed.

### MAR

- viewed;
- administered;
- held;
- refused;
- omitted;
- corrected.

### Notes

- created;
- viewed where required;
- finalized;
- amended.

### Handover

- created;
- acknowledged;
- amended.

### Escalation

- created;
- acknowledged;
- resolved.

### Security

- unauthorized access;
- break-glass;
- export;
- sensitive record access.

---

# 72. Clinical Documentation Integrity

Important rules:

1. Historical observations must remain immutable.
2. Finalized nursing notes cannot be silently edited.
3. Medication administration cannot be silently changed.
4. Handover records should preserve historical state.
5. Care-plan changes must remain auditable.
6. Corrections require explicit workflow.
7. Deleted clinical records should generally be avoided.
8. User/time/source must be traceable.

---

# 73. Break-Glass Access

Reuse the existing clinical break-glass framework.

Example:

```text
Normal Access Denied
       ↓
Emergency Access
       ↓
Reason Required
       ↓
Temporary Access
       ↓
Security Audit
```

Do not create a second break-glass mechanism.

---

# 74. Privacy

Nursing screens should follow minimum necessary access.

For example:

- nurse assigned to Ward A should not automatically see all hospitals;
- ward nurse should not automatically see unrelated departments;
- administrative staff should not automatically see detailed clinical notes;
- exports require explicit authorization.

---

# 75. Multi-Hospital Security

Mandatory tests:

```text
Hospital A Nurse
    ↓
Hospital B Patient
```

must fail.

Also test:

```text
Ward A Nurse
    ↓
Unauthorized Ward B Patient
```

must fail when ward-level restrictions apply.

Test through:

- UI;
- direct URL;
- API;
- export;
- search;
- reports.

---

# 76. FHIR Readiness

Prepare mappings for:

```text
Patient
Encounter
Practitioner
PractitionerRole
Observation
CarePlan
Condition
MedicationRequest
MedicationAdministration
MedicationDispense
AllergyIntolerance
Procedure
Device
DocumentReference
Task
```

Particularly important:

### Observation

Vital signs and clinical observations.

### CarePlan

Nursing care plans.

### MedicationAdministration

MAR records.

### Task

Nursing tasks.

### Device

Clinical device tracking where applicable.

Do not build a complete FHIR server unless required by Phase 18.

---

# 77. HL7 Readiness

Prepare for future:

- ADT;
- ORU;
- medication events;
- clinical observations;
- patient movement.

Phase 9 should integrate through the existing interoperability layer rather than directly connecting to external systems from nursing controllers.

---

# 78. Reporting

Reports should include:

### Nursing workload

- patients per nurse;
- assignments;
- completed tasks;
- overdue tasks.

### Clinical observations

- vital-sign trends;
- abnormal observations;
- observation completion.

### Medication

- administration;
- omissions;
- refusals;
- held medications;
- overdue administrations.

### Care

- active care plans;
- completed interventions;
- risk assessments.

### Quality

- fall-risk population;
- pressure-injury risk;
- escalation events;
- documentation completion.

### Handover

- handovers completed;
- pending acknowledgements.

---

# 79. Nursing Quality Indicators

Create configurable reporting foundations for:

- medication administration completion;
- overdue medication administration;
- omitted medication;
- fall-risk assessment completion;
- pressure-risk assessment completion;
- nursing assessment completion;
- care-plan completion;
- handover completion;
- escalation response time.

Do not hardcode hospital accreditation standards.

---

# 80. Performance

Optimize:

- nursing dashboard;
- patient assignment;
- MAR;
- medication due list;
- vital history;
- nursing task queue;
- handover;
- ward dashboard.

Use:

- indexes;
- pagination;
- eager loading;
- caching;
- optimized time-series queries.

Do not load an entire patient's lifetime EMR into every nursing screen.

---

# 81. Suggested Indexes

Potential indexes:

```text
patient_id
admission_id
encounter_id
nurse_id
ward_id
bed_id
shift_id
status
scheduled_at
administered_at
observed_at
created_at
```

Composite indexes based on actual queries:

```text
hospital_id + ward_id + status
nurse_id + status
admission_id + status
patient_id + observed_at
patient_id + scheduled_at
prescription_item_id + scheduled_at
```

Validate indexes using actual query plans.

---

# 82. Transaction Requirements

Use transactions for:

### Medication administration

```text
Validate MAR
Validate patient
Validate prescription
Validate required checks
Record administration
Create audit
Publish event
```

### Assignment

```text
Validate nurse
Validate patient location
Create assignment
Close previous assignment where appropriate
Create audit
```

### Handover

```text
Create handover
Attach relevant structured information
Finalize
Record acknowledgement
```

### Discharge checklist

```text
Validate required checklist
Complete nursing clearance
Record audit
Notify IPD
```

---

# 83. Concurrency

Prevent:

- two nurses simultaneously finalizing the same administration;
- duplicate administration;
- duplicate task completion;
- conflicting assignments;
- duplicate handover acknowledgement.

Use transactions and appropriate locking/idempotency.

---

# 84. Medication Duplicate Administration Protection

A particularly important safety rule:

If the same MAR item has already been finalized as administered:

```text
Second administration request
        ↓
REJECT
```

unless an explicit hospital-approved correction/reversal workflow exists.

Never rely only on frontend button disabling.

---

# 85. Offline/Network Consideration

If mobile/offline nursing support is added later, the architecture should support:

- client-generated idempotency keys;
- server timestamps;
- conflict detection;
- synchronization;
- audit.

Do not implement uncontrolled offline writes that can duplicate medication administration.

---

# 86. Mobile Readiness

Future nursing mobile application may require:

```text
My Patients
Vitals
Assessment
Tasks
MAR
Alerts
Handover
Notes
```

The REST API should therefore not depend on Blade-specific logic.

---

# 87. AI Readiness

Potential future AI capabilities:

### Nursing documentation

- note summarization;
- handover summarization;
- timeline summarization.

### Risk support

- deterioration-risk signals;
- fall-risk support;
- pressure-injury risk support.

### Operational

- workload forecasting;
- staffing recommendations;
- overdue-task detection.

### Medication

- administration anomaly detection;
- adherence pattern detection.

AI must be advisory.

AI must never:

- administer medication;
- change medication;
- cancel medication;
- change dose;
- create a diagnosis;
- override a nurse;
- override physician orders;
- finalize clinical documentation;
- suppress safety alerts.

All AI-generated content must be clearly identified and auditable.

---

# 88. AI Safety

For any future AI feature:

```text
AI Suggestion
     ↓
Human Review
     ↓
Accept / Modify / Reject
     ↓
Audit
```

Never:

```text
AI
 ↓
Automatic Clinical Action
```

---

# 89. Non-Goals

Do NOT implement in Phase 9:

- full physician EMR;
- independent prescription system;
- pharmacy inventory;
- pharmacy dispensing;
- enterprise inventory;
- full ICU;
- OT;
- Emergency Department;
- Blood Bank;
- insurance claims;
- autonomous clinical decision-making;
- autonomous diagnosis;
- autonomous medication administration.

---

# 90. Implementation Order

Implement in this sequence:

```text
1. Inspect existing architecture
2. Inspect Phases 0–8
3. Inspect Phase 3 clinical observation/EMR
4. Inspect Phase 7 prescription/pharmacy
5. Inspect Phase 8 admission/bed management
6. Map existing clinical entities

7. Nursing module foundation
8. Nursing episode
9. Nursing assignment
10. Shift management
11. Patient assignment
12. Nursing dashboard

13. Initial nursing assessment
14. Observation/vitals
15. Pain assessment
16. Risk assessments
17. Intake/output
18. Nursing notes

19. Care plans
20. Nursing diagnoses
21. Interventions
22. Nursing task engine

23. MAR
24. Medication administration safety
25. PRN medication
26. Refusal/omission
27. IV/infusion monitoring
28. Devices/lines/tubes

29. Wound/skin
30. Patient education
31. Escalation
32. Clinical alerts

33. Handover
34. Transfer nursing workflow
35. Discharge nursing workflow

36. Notifications
37. Audit
38. API
39. Dashboard
40. Reports
41. FHIR mappings
42. HL7 readiness

43. Unit tests
44. Feature tests
45. Integration tests
46. Security tests
47. Concurrency tests
48. Medication safety tests
49. Performance tests
50. Documentation
51. Deployment verification
```

---

# 91. Definition of Done

Phase 9 is complete when:

### Nursing

- nursing episode;
- nurse assignment;
- shift;
- patient assignment;
- dashboard.

### Clinical Care

- initial assessment;
- ongoing assessment;
- observations;
- vital signs;
- pain;
- risk assessments;
- intake/output;
- care plans;
- interventions;
- nursing tasks;
- nursing notes.

### Medication

- MAR;
- medication safety checks;
- administration;
- PRN;
- refusal;
- omission;
- hold;
- correction;
- IV/infusion monitoring.

### Operational

- handover;
- escalation;
- transfer preparation;
- discharge nursing checklist.

### Integration

- Patient;
- Encounter;
- IPD;
- Pharmacy;
- Billing where applicable;
- LIS;
- Radiology;
- Notification;
- Audit.

### Security

- RBAC;
- ward/hospital scope;
- patient access control;
- break-glass;
- audit;
- API authorization.

### Reliability

- transaction safety;
- concurrency safety;
- idempotency;
- duplicate medication administration prevention.

### Interoperability

- FHIR-ready;
- HL7-ready.

### Quality

- automated tests;
- security tests;
- medication safety tests;
- concurrency tests;
- documentation;
- deployment instructions.

---

# 92. FINAL AI IMPLEMENTATION PROMPT

You are a senior Laravel architect, healthcare information-system architect, clinical workflow architect, nursing informatics specialist, database engineer, security engineer, and healthcare interoperability engineer.

Implement **Phase 9 — Nursing & Inpatient Clinical Care** in the existing Hospital Management System repository.

## FIRST — INSPECT BEFORE CODING

Before changing anything:

1. Inspect the complete repository structure.
2. Identify the existing modular architecture.
3. Inspect Phase 0 Foundation.
4. Inspect Phase 1 Patient/MPI.
5. Inspect Phase 2 Appointment.
6. Inspect Phase 3 Clinical Encounter/EMR.
7. Inspect Phase 4 Billing.
8. Inspect Phase 5 LIS.
9. Inspect Phase 6 Radiology/PACS.
10. Inspect Phase 7 Pharmacy.
11. Inspect Phase 8 IPD/Admission/Bed Management.
12. Inspect existing:
   - observation models;
   - clinical notes;
   - diagnosis;
   - prescription;
   - medication;
   - pharmacy dispensing;
   - patient timeline;
   - workflow;
   - notification;
   - audit;
   - security;
   - users;
   - roles;
   - permissions;
   - API;
   - file management;
   - testing framework.

Create a dependency map before coding.

---

## MANDATORY ARCHITECTURE RULE

The existing modular architecture is already implemented.

**DO NOT create another modular framework.**

Reuse existing:

- Patient/MPI;
- Encounter;
- Clinical Observation;
- Diagnosis;
- Prescription;
- Medication;
- Pharmacy;
- Admission;
- Bed;
- Billing;
- User;
- Role;
- Permission;
- Workflow;
- Audit;
- Notification;
- File;
- API.

---

## IMPLEMENT

Build:

```text
Nursing & Inpatient Clinical Care
```

with:

- nursing episode;
- nurse assignment;
- shift management;
- patient assignment;
- nursing dashboard;
- initial assessment;
- ongoing assessment;
- vital signs;
- pain assessment;
- risk assessment;
- fluid balance;
- intake/output;
- nursing diagnosis;
- care plan;
- nursing interventions;
- nursing tasks;
- MAR;
- medication administration;
- PRN administration;
- medication refusal;
- medication omission;
- medication hold;
- IV/infusion monitoring;
- lines/tubes/devices;
- wound/skin assessment;
- patient education;
- nursing notes;
- shift handover;
- escalation;
- clinical alerts;
- transfer nursing workflow;
- discharge nursing checklist;
- reporting;
- audit;
- API;
- FHIR readiness;
- HL7 readiness.

---

## CRITICAL BOUNDARIES

### Phase 3 owns

- physician clinical encounter;
- medical diagnosis;
- clinical orders;
- prescription;
- clinical notes where already implemented.

### Phase 7 owns

- medication master;
- pharmacy;
- stock;
- batch;
- dispensing;
- pharmacy verification.

### Phase 8 owns

- admission;
- current inpatient status;
- ward;
- room;
- bed;
- bed allocation;
- patient location;
- transfer;
- operational discharge.

### Phase 9 owns

- nursing care;
- nursing assessment;
- nursing observations;
- nursing care plans;
- nursing interventions;
- nursing tasks;
- medication administration;
- nursing notes;
- nursing handover;
- nursing escalation;
- nursing discharge checklist.

Do not cross these boundaries.

---

## MEDICATION SAFETY

Implement MAR using existing Phase 7 prescription and pharmacy records.

Before administration verify the configured safety checks for:

- patient;
- medication;
- dose;
- route;
- time;
- documentation;
- allergy;
- relevant safety alerts;
- dispensing status.

Never silently administer or alter a medication.

Support:

```text
Administered
Held
Refused
Omitted
Missed
Cancelled
Deferred
```

Record reasons where applicable.

Prevent duplicate administration.

Use transaction/locking/idempotency mechanisms.

---

## CLINICAL RECORD INTEGRITY

Never silently modify:

- vital signs;
- clinical observations;
- nursing notes;
- care plans;
- medication administration;
- handover records;
- assessments.

Finalized records require an explicit amendment/correction workflow.

Preserve the original record and audit trail.

---

## SECURITY

Implement server-side authorization for:

- organization;
- hospital;
- branch;
- ward;
- patient;
- admission;
- nursing assignment.

Mandatory tests:

```text
Hospital A Nurse
      ↓
Hospital B Patient
```

must fail.

Also test:

```text
Ward A Nurse
      ↓
Ward B Patient
```

where ward-level restrictions apply.

Test:

- direct URL;
- API;
- manipulated IDs;
- exports;
- reports;
- privilege escalation;
- IDOR;
- break-glass.

---

## IPD INTEGRATION

Never create a second location system.

Get current:

```text
Hospital
Ward
Room
Bed
```

from Phase 8.

When a patient transfers:

```text
Phase 8
   ↓
Patient Location Changes
   ↓
Phase 9 Nursing Assignment/Handover
```

---

## MAR INTEGRATION

Implement:

```text
Prescription
    ↓
Pharmacy Verification
    ↓
Dispensing
    ↓
MAR
    ↓
Administration
```

Do not duplicate prescription or dispensing.

Every MAR entry should be traceable to the appropriate prescription item and patient/admission.

---

## NURSING CARE PLAN

Implement:

```text
Assessment
   ↓
Nursing Problem
   ↓
Goal
   ↓
Intervention
   ↓
Task
   ↓
Evaluation
```

Allow configurable templates.

Do not hardcode hospital-specific nursing protocols.

---

## OBSERVATIONS

If an existing generic Observation model exists:

**reuse it.**

Do not create a duplicate clinical observation framework.

Vital signs must remain time-series data.

Never overwrite previous values.

---

## ESCALATION

Implement:

```text
Concern
 ↓
Nurse Assessment
 ↓
Escalation
 ↓
Recipient
 ↓
Acknowledgement
 ↓
Action
 ↓
Resolution
```

Do not allow the software to autonomously diagnose or treat the patient.

---

## API

Implement:

```text
/api/v1/nursing
```

for:

- dashboard;
- assignments;
- assessments;
- observations;
- vitals;
- care plans;
- tasks;
- MAR;
- medication administration;
- handover;
- notes;
- escalation;
- reports.

Use the existing API response structure.

---

## UI

Implement:

```text
Nursing
├── Dashboard
├── My Patients
├── Ward Patients
├── Assessments
├── Vitals & Observations
├── Care Plans
├── Tasks
├── Medication Administration
├── Intake / Output
├── Wounds / Skin
├── Lines / Tubes / Devices
├── Notes
├── Handover
├── Escalations
├── Discharge Checklist
├── Reports
└── Settings
```

Reuse existing Blade/AdminLTE components.

---

## EVENTS

Implement appropriate events:

```text
NurseAssigned
NursingAssessmentCompleted
VitalRecorded
CriticalObservationDetected
CarePlanCreated
CarePlanCompleted
NursingTaskCompleted
MedicationAdministrationRecorded
MedicationRefused
MedicationOmitted
NursingHandoverCreated
NursingHandoverAcknowledged
NursingEscalationCreated
NursingEscalationResolved
NursingDischargeChecklistCompleted
```

Use existing project conventions.

---

## JOBS

Implement where necessary:

```text
GenerateDueNursingTasks
DetectOverdueMedicationAdministration
DetectOverdueNursingTasks
GenerateNursingEscalationNotification
GenerateShiftHandoverReminder
```

Jobs must be idempotent and retry-safe.

---

## TESTING

Write and execute:

### Unit

- observation;
- vital;
- assessment;
- care plan;
- task;
- MAR;
- medication administration;
- fluid balance;
- risk assessment.

### Feature

- nursing assignment;
- assessment;
- vitals;
- care plan;
- task;
- MAR;
- handover;
- escalation;
- discharge checklist.

### Integration

```text
Patient → Nursing
IPD → Nursing
Prescription → MAR
Pharmacy → MAR
Nursing → Clinical Timeline
Nursing → Notification
Nursing → Audit
```

### Security

- multi-hospital isolation;
- ward access;
- RBAC;
- IDOR;
- API authorization;
- privilege escalation.

### Concurrency

Prove:

```text
Two nurses
   ↓
Same MAR item
   ↓
Only one administration succeeds
```

### Negative Tests

Test:

- already-administered medication;
- cancelled prescription;
- invalid patient;
- invalid admission;
- unauthorized ward;
- unauthorized MAR;
- finalized note modification;
- invalid observation;
- duplicate task completion.

Only report tests as passed if they were actually executed.

---

## PERFORMANCE

Optimize:

- My Patients;
- ward dashboard;
- MAR;
- medication due list;
- vital history;
- nursing task queue;
- handover.

Use:

- indexes;
- pagination;
- eager loading;
- caching;
- efficient time-series queries.

---

## INTEROPERABILITY

Prepare mappings for:

```text
FHIR:

Patient
Encounter
Practitioner
PractitionerRole
Observation
CarePlan
Condition
MedicationRequest
MedicationAdministration
MedicationDispense
AllergyIntolerance
Procedure
Device
DocumentReference
Task
```

Prepare future HL7 integration through the existing interoperability architecture.

---

## AI SAFETY

Prepare extension points for:

- nursing note summarization;
- handover summarization;
- patient timeline summarization;
- workload forecasting;
- deterioration-risk support;
- fall-risk support;
- pressure-injury risk support;
- medication anomaly detection.

AI must remain advisory.

AI must never:

- administer medication;
- modify dose;
- change route;
- cancel medication;
- create a diagnosis;
- override physician orders;
- override nursing decisions;
- finalize clinical documentation;
- suppress safety alerts.

AI output must be clearly identified and auditable.

---

## FINAL REPORT

After implementation, provide:

1. architecture changes;
2. database changes;
3. models;
4. migrations;
5. services/actions;
6. controllers;
7. Form Requests;
8. policies;
9. routes;
10. permissions;
11. nursing workflow;
12. assessment workflow;
13. observation/vitals workflow;
14. care-plan workflow;
15. task workflow;
16. MAR workflow;
17. medication safety;
18. handover;
19. escalation;
20. discharge nursing;
21. Phase 7 integration;
22. Phase 8 integration;
23. audit/security;
24. API;
25. FHIR/HL7 readiness;
26. tests actually executed and results;
27. deployment instructions;
28. known limitations;
29. deferred features.

The implementation must be **production-grade, clinically traceable, secure, transaction-safe, concurrency-safe, auditable, multi-hospital capable, integration-ready, and fully consistent with the existing HMS architecture**.

Do not stop at CRUD screens. Implement the complete nursing workflow.