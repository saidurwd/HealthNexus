
# Phase 12 — Emergency Department
## Module Specification & AI Implementation Prompt

**Architecture:** Existing Laravel Modular Monolith  
**Technology:** PHP 8.4+, Laravel, MySQL/PostgreSQL, Redis, Blade + AdminLTE, REST API  
**Phase:** 12  
**Module:** Emergency Department (ED) / Accident & Emergency  
**Prerequisites:** Phases 0–11

---

# 1. Module Purpose

Phase 12 implements a production-grade **Emergency Department Management System** for managing patients from arrival through triage, emergency assessment, treatment, observation, admission, transfer, referral or discharge.

The core emergency workflow is:

```text
Patient Arrival
      ↓
Registration / Identification
      ↓
Triage
      ↓
Emergency Queue
      ↓
Initial Assessment
      ↓
Emergency Encounter
      ↓
Orders / Treatment
      ↓
Monitoring / Observation
      ↓
Investigation
      ↓
Clinical Decision
      ↓
┌───────────────┬──────────────┬───────────────┐
│ Discharge     │ Admission    │ ICU Transfer  │
│               │              │               │
│ Home/Referral │ IPD / Ward   │ ICU           │
└───────────────┴──────────────┴───────────────┘
```

The module must support:

- 24/7 emergency operations
- Walk-in patients
- Ambulance arrivals
- Referred patients
- Unknown/unidentified patients
- Mass-casualty foundation
- Triage
- Emergency queue
- Emergency registration
- Rapid assessment
- Emergency clinical encounter
- Resuscitation
- Emergency procedures
- Critical alerts
- Observation
- Emergency medications
- Laboratory orders
- Radiology orders
- Blood requests
- Surgery requests
- ICU requests
- Admission requests
- Discharge
- Referral/transfer
- Death documentation
- Emergency billing integration
- Emergency department dashboards
- Operational reporting
- Audit/security
- AI-ready decision-support architecture

---

# 2. Critical Architecture Rule

The existing modular architecture is **already implemented**.

The implementation must:

1. Inspect the repository first.
2. Inspect Phases 0–11.
3. Reuse existing architecture.
4. Reuse existing Patient/MPI.
5. Reuse existing Clinical Encounter.
6. Reuse existing Billing.
7. Reuse existing LIS.
8. Reuse existing Radiology/RIS.
9. Reuse existing Pharmacy.
10. Reuse existing Admission/Bed Management.
11. Reuse existing Nursing.
12. Reuse existing OT/Surgery.
13. Reuse existing ICU.
14. Reuse existing Blood Bank integration.
15. Reuse existing Authentication/RBAC.
16. Reuse existing Audit/Security.
17. Reuse existing Workflow/Notifications/File Management.
18. **Do NOT create a second modular framework.**
19. **Do NOT create duplicate patient, billing, prescription, bed, nursing, ICU or clinical-order systems.**

Emergency should become the hospital's **acute-care orchestration layer**.

---

# 3. Phase 12 Scope

## Core Modules

```text
Emergency Department
├── ED Configuration
├── Emergency Registration
├── Patient Arrival
├── Triage
├── Emergency Queue
├── Emergency Encounter
├── Rapid Assessment
├── Resuscitation
├── Emergency Orders
├── Emergency Procedures
├── Emergency Medications
├── Emergency Monitoring
├── Emergency Observation
├── Critical Alerts
├── Trauma Management
├── Stroke / Cardiac / Sepsis Foundations
├── Ambulance / Arrival Management
├── Referral / Transfer
├── Admission
├── ICU Transfer
├── Discharge
├── Death / Mortuary Integration
├── Emergency Billing Integration
├── Dashboard
├── Reports
├── Audit
├── API
└── AI Extension Points
```

---

# 4. ED Architecture

Recommended hierarchy:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
Emergency Department
    ↓
Zone
    ↓
Treatment Area
    ↓
Bed / Trolley
```

Example:

```text
Emergency Department
├── Triage
├── Resuscitation
│   ├── Resus-01
│   └── Resus-02
├── Acute Care
│   ├── Bay-01
│   ├── Bay-02
│   └── Bay-03
├── Minor Treatment
├── Observation
└── Isolation
```

All areas must be configurable.

---

# 5. Emergency Department Zones

Support configurable zone types:

- Triage
- Resuscitation
- Acute
- Minor
- Observation
- Isolation
- Procedure
- Trauma
- Pediatric
- Cardiac
- Other

Do not hardcode zone names.

---

# 6. Emergency Patient Arrival

Patients may arrive through:

- Walk-in
- Ambulance
- Police
- Referral
- Another hospital
- Clinic
- Workplace
- Road traffic accident
- Brought by family
- Unknown/unidentified patient
- Mass-casualty event

Capture:

```text
Arrival Date/Time
Arrival Mode
Source
Accompanied By
Referring Organization
Ambulance
Police Case
Accident Information
Initial Condition
Registration Status
```

---

# 7. Emergency Registration

Emergency registration must support both:

### Identified Patient

Search existing MPI using:

- MRN
- Name
- Mobile
- National ID/identifier
- Date of birth
- Other configured identifiers

### Unknown Patient

Support temporary emergency identity:

```text
UNKNOWN-2026-000001
```

Record:

- Temporary identity
- Approximate age
- Sex
- Physical description
- Arrival location
- Found/brought by
- Identification information
- Photograph where legally permitted
- Belongings
- Identification status

When identified later:

```text
Temporary Emergency Identity
          ↓
MPI Identification
          ↓
Patient Record
          ↓
Identity Link Audit
```

Never silently overwrite identity.

---

# 8. Emergency Registration vs Encounter

Maintain the same architectural distinction used in Phase 2/3.

### Emergency Registration

Represents administrative arrival.

### Emergency Encounter

Represents actual clinical care.

Example:

```text
Patient Arrival
     ↓
ED Registration
     ↓
Triage
     ↓
Emergency Encounter
```

A patient may arrive and leave before a full clinical encounter depending on configured workflow.

---

# 9. Triage

Triage is a core Phase 12 feature.

Support configurable triage systems.

Potential categories:

- Immediate
- Very Urgent
- Urgent
- Standard
- Non-Urgent

If a formal five-level triage model is configured, the system should support it without hardcoding one specific country's policy.

---

# 10. Triage Assessment

Capture:

### General

- Chief complaint
- Arrival mode
- Injury/illness
- Onset
- Severity

### Vital Signs

- Temperature
- Heart rate
- Respiratory rate
- Blood pressure
- MAP
- SpO₂
- Pain score
- GCS
- Blood glucose

### Clinical Risk

- Airway compromise
- Respiratory distress
- Circulatory instability
- Altered consciousness
- Severe pain
- Active bleeding
- Trauma
- Stroke symptoms
- Chest pain
- Sepsis indicators
- Pregnancy-related emergency
- Pediatric risk

### Triage Level

```text
Triage Level
Triage Nurse
Date/Time
Reason
Clinical Findings
Disposition
```

Historical triage records must never be overwritten.

---

# 11. Triage Reassessment

Emergency patients can deteriorate while waiting.

Support:

```text
Initial Triage
      ↓
Waiting
      ↓
Reassessment
      ↓
Priority Changed
      ↓
Clinical Queue Updated
```

Each reassessment must record:

- Time
- Vitals
- Symptoms
- Clinical changes
- New triage level
- Reason
- User

---

# 12. Emergency Queue

The queue must be **clinical priority-based**, not simply first-come-first-served.

Display:

```text
Priority
Arrival Time
Patient
MRN
Chief Complaint
Triage Level
Waiting Time
Location
Assigned Provider
Status
Alerts
```

Statuses:

```text
Waiting
Called
In Assessment
In Treatment
Observation
Awaiting Investigation
Awaiting Decision
Ready for Discharge
Ready for Admission
Transferred
Discharged
Left Without Being Seen
Left Against Medical Advice
Expired
```

The exact queue algorithm must be configurable.

---

# 13. Queue Priority

Priority calculation may consider:

- Triage category
- Clinical deterioration
- Critical alert
- Arrival time
- Pediatric priority
- Trauma priority
- Isolation requirement
- Physician escalation

Do not allow AI to silently reorder a clinical queue.

If automated prioritization is introduced, the system must:

- expose the rule
- record the reason
- allow authorized clinical override
- audit the change

---

# 14. Emergency Encounter

Phase 3 owns the core Clinical Encounter.

Phase 12 creates an emergency-specific encounter context.

Example:

```text
Encounter Type = Emergency
Source = Emergency Department
```

Reuse:

- Chief complaint
- HPI
- History
- Examination
- Diagnosis
- Problem list
- Clinical orders
- Prescription
- Referral
- Clinical notes

Do not create a separate EMR.

---

# 15. Emergency Rapid Assessment

Provide a structured rapid assessment.

Suggested structure:

```text
ABCDE
```

### A — Airway

- Patent
- Compromised
- Obstruction
- Intubated
- Airway device

### B — Breathing

- Respiratory rate
- SpO₂
- Respiratory effort
- Breath sounds
- Oxygen support

### C — Circulation

- BP
- HR
- Peripheral perfusion
- Bleeding
- IV access

### D — Disability

- GCS
- Pupils
- Blood glucose
- Neurological status

### E — Exposure

- Temperature
- Injury
- Rash
- Bleeding
- Burns
- Other findings

The framework should be configurable.

---

# 16. Emergency Resuscitation

Provide a dedicated resuscitation workflow.

Support:

- Resuscitation area
- Resuscitation event
- Team assignment
- Airway management
- Breathing support
- Circulation
- CPR
- Defibrillation
- Cardioversion
- Emergency medication
- Procedures
- Blood products
- Critical event documentation
- Outcome

Workflow:

```text
Emergency Arrival
      ↓
Recognition of Arrest/Critical Event
      ↓
Resuscitation
      ↓
Team Activation
      ↓
Interventions
      ↓
ROSC / No ROSC
      ↓
ICU / Ward / Transfer / Death
```

---

# 17. Code Blue Integration

Reuse the critical-event concepts from Phase 11 where possible.

Do not create duplicate code-blue architecture.

Emergency-specific events should link to the same critical-event framework.

---

# 18. Emergency Team

Support:

- Emergency physician
- Triage nurse
- Emergency nurse
- Resuscitation nurse
- Technician
- Respiratory therapist
- Pharmacist where applicable
- Surgeon
- Anesthetist
- Intensivist
- Other specialist

Use existing users/providers.

Do not create another provider master.

Track:

- Role
- User/provider
- Assigned time
- Removed time
- Assignment source

---

# 19. Emergency Clinical Orders

Reuse Phase 3 Clinical Orders.

Examples:

- Laboratory
- Radiology
- Medication
- Procedure
- Blood
- Surgery
- ICU
- Observation
- Referral

Workflow:

```text
ED Physician
    ↓
Clinical Order
    ↓
Specialized Module
    ↓
Result
    ↓
Emergency Encounter
```

---

# 20. Laboratory Integration

Phase 5 owns LIS.

Emergency supports:

- STAT laboratory orders
- Priority
- Specimen collection
- Critical results
- Result acknowledgement

Example:

```text
ED Order
  ↓
STAT Lab
  ↓
LIS
  ↓
Result
  ↓
Critical Result Alert
  ↓
ED Clinician
```

No duplicate laboratory result storage.

---

# 21. Radiology Integration

Phase 6 owns RIS/PACS.

Emergency supports:

- STAT imaging
- Trauma imaging
- Portable imaging
- CT
- MRI
- X-ray
- Ultrasound

Emergency displays:

- Order status
- Report
- Critical finding
- PACS reference

---

# 22. Pharmacy Integration

Phase 7 owns medication master, prescription, dispensing and pharmacy stock.

Emergency may require:

- Emergency medications
- STAT medications
- Crash-cart medications
- Controlled medications
- Infusions

Workflow:

```text
Emergency Prescription/Order
       ↓
Pharmacy
       ↓
Verification
       ↓
Dispensing
       ↓
MAR
       ↓
Administration
```

Do not create a second pharmacy system.

---

# 23. Emergency Medication Administration

Phase 9 owns MAR.

Phase 12 provides emergency context:

- STAT
- Immediate
- One-time
- Emergency protocol

All medication administration must remain traceable.

---

# 24. Emergency Procedures

Support structured emergency procedures:

- Suturing
- Wound care
- Splinting
- Casting
- Incision/drainage
- Intubation
- Central line
- Arterial line
- Chest tube
- Cardioversion
- Defibrillation
- Reduction
- Emergency surgery request
- Other configurable procedures

Reuse existing clinical procedure architecture where available.

---

# 25. Trauma Management

Provide a trauma foundation.

Capture:

- Trauma type
- Mechanism
- Date/time
- Location
- Road traffic accident
- Occupational injury
- Assault
- Fall
- Burn
- Other

Support:

```text
Primary Survey
Secondary Survey
Injuries
Body Region
Laterality
Imaging
Procedures
Consultations
Disposition
```

Do not create a separate diagnosis engine.

---

# 26. Trauma Body Map

Provide a structured injury representation.

Potential body regions:

- Head
- Face
- Neck
- Chest
- Abdomen
- Pelvis
- Spine
- Upper limb
- Lower limb
- Skin
- Other

Support:

- Injury type
- Site
- Side
- Severity
- Description
- Diagram reference if implemented

Do not make a graphical body-map dependency mandatory for initial release.

---

# 27. Stroke Emergency Foundation

Support configurable stroke workflow.

Potential workflow:

```text
Arrival
↓
Stroke Recognition
↓
Last Known Well
↓
Rapid Assessment
↓
Imaging
↓
Neurology Review
↓
Treatment Decision
↓
ICU / Stroke Unit / Ward / Transfer
```

Record:

- Last known well
- Symptom onset
- Stroke signs
- Assessment
- Imaging
- Treatment decision
- Specialist consultation
- Outcome

Do not automatically determine treatment eligibility.

---

# 28. Acute Coronary Syndrome Foundation

Support emergency cardiac workflow.

Capture:

- Chest pain
- Onset
- ECG
- Troponin
- Risk factors
- Cardiac assessment
- Cardiology consultation
- Treatment
- Disposition

Integrate with:

- LIS
- Radiology
- Pharmacy
- Cardiology workflows when available

---

# 29. Sepsis Foundation

Integrate with Phase 11 critical-care concepts.

Workflow:

```text
Suspected Infection
       ↓
Risk Assessment
       ↓
Investigations
       ↓
Treatment
       ↓
Reassessment
       ↓
Disposition
```

Support:

- Infection source
- Lactate
- Cultures
- Antibiotics
- Fluids
- Vasopressors
- Reassessment

The system must not automatically diagnose sepsis.

---

# 30. Emergency Observation

Patients may remain in ED observation.

Support:

- Observation beds
- Observation episode
- Periodic monitoring
- Reassessment
- Investigation waiting
- Treatment
- Observation notes
- Disposition decision

Statuses:

```text
Observation Started
Under Observation
Ready for Decision
Admitted
Discharged
Transferred
```

---

# 31. ED Monitoring

Support:

- Vital signs
- SpO₂
- Pain
- GCS
- Blood glucose
- Cardiac monitoring reference
- Respiratory status
- Neurological status

Reuse Phase 11 observation architecture where appropriate.

---

# 32. Critical Alerts

Examples:

- Critical vital
- Critical laboratory result
- Deterioration
- Sepsis risk
- Stroke alert
- Cardiac alert
- Trauma alert
- Allergy
- Medication safety
- Isolation requirement

Alert structure:

```text
Patient
Alert Type
Severity
Trigger
Triggered At
Acknowledged At
Acknowledged By
Action
Escalation
```

---

# 33. Emergency Consultations

Support specialist consultation requests.

Workflow:

```text
ED Physician
   ↓
Consult Request
   ↓
Specialist
   ↓
Accepted
   ↓
Consultation
   ↓
Recommendation
```

Potential specialties:

- Surgery
- Orthopedics
- Cardiology
- Neurology
- Neurosurgery
- Anesthesiology
- ICU
- Pediatrics
- Obstetrics
- ENT
- Ophthalmology
- Other

Use existing provider/specialty masters.

---

# 34. Consultation SLA

Support configurable response targets.

Example:

```text
Critical → 5 minutes
Urgent → 15 minutes
Routine → 30 minutes
```

These are configuration examples, not universal clinical rules.

Track:

- Requested time
- Accepted time
- Seen time
- Completed time
- Delay reason

---

# 35. Emergency Surgery Integration

Emergency may create:

```text
Emergency Surgical Request
```

which is handed to Phase 10.

Workflow:

```text
ED
 ↓
Clinical Assessment
 ↓
Surgery Required
 ↓
Phase 10 Surgery Request
 ↓
OT Workflow
```

Do not duplicate OT scheduling or surgical documentation.

---

# 36. ICU Integration

Critical patients may require ICU.

Workflow:

```text
ED
 ↓
ICU Request
 ↓
Phase 11 ICU Review
 ↓
Bed Availability
 ↓
ICU Admission
 ↓
ED Encounter Transfer
```

Phase 11 owns ICU workflow.

Phase 8 owns hospital location/bed allocation.

---

# 37. IPD Admission Integration

For patients requiring admission:

```text
ED
 ↓
Admission Decision
 ↓
Phase 8 Admission
 ↓
Bed Assignment
 ↓
Ward / ICU / Other Unit
```

ED does not create a separate admission system.

---

# 38. Referral / External Transfer

Support:

- Internal referral
- External hospital transfer
- Specialist referral
- Ambulance transfer

Capture:

- Receiving organization
- Receiving department
- Contact
- Reason
- Clinical condition
- Treatment provided
- Investigations
- Medications
- Transfer documents
- Transport
- Accompanying staff
- Time

---

# 39. Ambulance Management

Provide an integration-ready ambulance module.

Capture:

- Ambulance number
- Source
- Arrival time
- Departure time
- Crew
- Patient
- Transport type
- Prehospital information

Do not create a full fleet-management system in Phase 12.

Future fleet management can be separate.

---

# 40. Left Without Being Seen

Support:

- Patient called
- Waiting time
- Departure time
- Reason if known
- Staff documentation

Status:

```text
Left Without Being Seen
```

Never delete the registration.

---

# 41. Left Against Medical Advice

Support:

- Risk explanation
- Capacity assessment where applicable
- Patient/family decision
- Documentation
- Witness
- Signature/consent integration
- Discharge instructions
- Audit

Use the existing consent/document framework.

---

# 42. Emergency Discharge

Emergency discharge should support:

- Final diagnosis
- Treatment provided
- Medication
- Follow-up
- Warning signs
- Referral
- Instructions
- Discharge time
- Responsible clinician

Reuse Phase 3 clinical documentation and Phase 8 discharge architecture where applicable.

---

# 43. Death in Emergency Department

Support emergency death documentation.

Workflow:

```text
Critical Event
     ↓
Resuscitation where applicable
     ↓
Outcome
     ↓
Death Documentation
     ↓
Hospital Death Workflow
     ↓
Mortuary / Administrative Integration
```

Do not create a competing hospital-wide death system.

---

# 44. Emergency Billing Integration

Phase 4 owns billing.

ED generates chargeable events for:

- Emergency registration
- Consultation
- Procedures
- Observation
- Emergency services
- Consumables
- Emergency imaging
- Laboratory
- Other configured services

Workflow:

```text
ED Service
   ↓
Chargeable Event
   ↓
Billing Engine
   ↓
Invoice
   ↓
Payment
```

Use idempotent event keys.

Example:

```text
ED:{encounter_id}:{service_code}:{source_event_id}
```

ED must not implement:

- invoice
- payment
- receipt
- refund
- tax engine

---

# 45. Emergency Dashboard

## Operational

Display:

- Current patients
- Waiting patients
- Triage queue
- Patients in treatment
- Observation patients
- Critical patients
- Available treatment areas
- Available resuscitation beds
- Available observation beds
- Waiting time
- Long-wait patients

## Clinical

Display:

- Critical alerts
- Stroke alerts
- Cardiac alerts
- Trauma alerts
- Sepsis alerts
- Critical laboratory results
- Deteriorating patients

## Management

Display:

- ED arrivals
- Admissions
- Discharges
- Transfers
- Average waiting time
- Length of stay
- Left without being seen
- Referral/transfer
- Utilization

---

# 46. Emergency Tracking Board

Example:

```text
┌────────┬──────────────┬──────────┬──────────────┬────────────┐
│ Priority│ Patient      │ Location │ Status       │ Wait Time  │
├────────┼──────────────┼──────────┼──────────────┼────────────┤
│ Critical│ Patient A    │ Resus-01 │ Resuscitation│ 00:05      │
│ Urgent  │ Patient B    │ Bay-02   │ Assessment   │ 00:18      │
│ Urgent  │ Patient C    │ Bay-04   │ Investigation│ 00:31      │
│ Standard│ Patient D    │ Waiting  │ Triage       │ 00:42      │
└────────┴──────────────┴──────────┴──────────────┴────────────┘
```

Patient details must respect access scope.

---

# 47. Emergency Patient Workspace

Recommended:

```text
Patient Header
├── MRN
├── Allergies
├── Triage Level
├── Arrival Time
├── Current Location
└── Critical Alerts

Chief Complaint

Rapid Assessment

Vitals

Clinical Assessment

Diagnoses

Orders

Laboratory

Radiology

Medications

Procedures

Consultations

Observation

Critical Events

Trauma

Clinical Notes

Disposition
```

---

# 48. Emergency Nurse Workspace

Workflow:

```text
ED Queue
↓
Patient
↓
Triage
↓
Vitals
↓
Reassessment
↓
Orders
↓
Medication/MAR
↓
Monitoring
↓
Procedures
↓
Handover
↓
Disposition
```

---

# 49. Emergency Physician Workspace

Workflow:

```text
Emergency Queue
↓
Patient
↓
Triage
↓
Rapid Assessment
↓
History
↓
Examination
↓
Vitals
↓
Orders
↓
Results
↓
Consultations
↓
Procedures
↓
Clinical Decision
↓
Disposition
```

---

# 50. Suggested Database Tables

The exact structure must follow the existing project.

Potential tables:

```text
ed_departments
ed_zones
ed_treatment_areas
ed_area_capabilities

ed_arrivals
ed_registrations
ed_unknown_patient_identifiers

ed_triage_records
ed_triage_reassessments
ed_queue_entries
ed_queue_events

ed_emergency_encounters
ed_emergency_status_history

ed_rapid_assessments
ed_resuscitation_events
ed_resuscitation_team
ed_resuscitation_interventions

ed_monitoring_records
ed_observations

ed_trauma_cases
ed_trauma_injuries
ed_trauma_assessments

ed_consultations
ed_consultation_events

ed_ambulance_arrivals

ed_emergency_observations
ed_observation_records

ed_critical_alerts
ed_alert_acknowledgements
ed_alert_escalations

ed_critical_events

ed_referrals
ed_transfers

ed_discharge_records
ed_lama_records
ed_left_without_being_seen

ed_death_records

ed_chargeable_events
```

Do not blindly create all of these.

First inspect existing equivalents.

---

# 51. Emergency Encounter Table

Conceptual structure:

```text
ed_emergency_encounters

id
organization_id
hospital_id
branch_id
encounter_id
patient_id
arrival_id
triage_id
ed_zone_id
treatment_area_id
priority
arrival_mode
chief_complaint
status
arrival_at
first_clinical_contact_at
disposition_at
disposition
assigned_provider_id
assigned_nurse_id
created_by
completed_by
completed_at
created_at
updated_at
```

Use foreign keys and appropriate indexes.

---

# 52. Triage Table

Conceptually:

```text
ed_triage_records

id
organization_id
hospital_id
branch_id
emergency_encounter_id
patient_id
triage_level
chief_complaint
pain_score
gcs
temperature
heart_rate
respiratory_rate
systolic_bp
diastolic_bp
spo2
blood_glucose
clinical_summary
risk_flags
triage_reason
triaged_by
triaged_at
created_at
updated_at
```

If observations already have a standardized architecture, reuse it instead of duplicating columns.

---

# 53. Permissions

Suggested:

```text
ed.dashboard.view

ed.arrival.view
ed.arrival.create
ed.arrival.update

ed.registration.view
ed.registration.create
ed.registration.update

ed.triage.view
ed.triage.create
ed.triage.update
ed.triage.reassess

ed.queue.view
ed.queue.manage
ed.queue.override

ed.encounter.view
ed.encounter.create
ed.encounter.update
ed.encounter.complete

ed.assessment.view
ed.assessment.create
ed.assessment.update
ed.assessment.finalize

ed.resuscitation.view
ed.resuscitation.manage
ed.resuscitation.finalize

ed.monitoring.view
ed.monitoring.create
ed.monitoring.update

ed.procedure.view
ed.procedure.create
ed.procedure.finalize

ed.trauma.view
ed.trauma.create
ed.trauma.update
ed.trauma.finalize

ed.consultation.view
ed.consultation.create
ed.consultation.update

ed.observation.view
ed.observation.create
ed.observation.update

ed.alert.view
ed.alert.acknowledge
ed.alert.manage

ed.transfer.view
ed.transfer.create
ed.transfer.approve

ed.discharge.view
ed.discharge.create
ed.discharge.finalize

ed.reports.view
ed.reports.export

ed.audit.view

ed.settings.manage
```

---

# 54. Roles

Potential roles:

- Emergency Physician
- Emergency Nurse
- Triage Nurse
- Emergency Technician
- Emergency Coordinator
- Emergency Department Manager
- Emergency Receptionist
- Emergency Registrar
- Consultant
- Resuscitation Team Member

Reuse existing system roles where appropriate.

---

# 55. API

Base:

```text
/api/v1/emergency
```

Potential endpoints:

```http
GET    /dashboard

GET    /arrivals
POST   /arrivals
GET    /arrivals/{arrival}

GET    /registrations
POST   /registrations

GET    /triage
POST   /triage
POST   /triage/{triage}/reassess

GET    /queue
POST   /queue/{entry}/call
POST   /queue/{entry}/prioritize

GET    /encounters
POST   /encounters
GET    /encounters/{encounter}

POST   /encounters/{encounter}/rapid-assessment
POST   /encounters/{encounter}/observations

GET    /encounters/{encounter}/resuscitation
POST   /encounters/{encounter}/resuscitation

GET    /encounters/{encounter}/trauma
POST   /encounters/{encounter}/trauma

GET    /consultations
POST   /consultations

GET    /observations
POST   /observations

GET    /alerts
POST   /alerts/{alert}/acknowledge

POST   /encounters/{encounter}/icu-request
POST   /encounters/{encounter}/admission-request
POST   /encounters/{encounter}/surgery-request

POST   /encounters/{encounter}/referral
POST   /encounters/{encounter}/transfer

POST   /encounters/{encounter}/discharge
POST   /encounters/{encounter}/lama

GET    /reports/waiting-time
GET    /reports/throughput
GET    /reports/triage
GET    /reports/disposition
```

Every endpoint must enforce:

- Authentication
- Authorization
- Organization scope
- Hospital scope
- Branch scope
- ED scope
- Patient access

---

# 56. Events

Potential events:

```text
EDPatientArrived
EDRegistrationCompleted
EDPatientIdentified
EDTriageCompleted
EDTriageReassessed
EDPriorityChanged

EDEmergencyEncounterStarted
EDPatientCalled
EDPatientAssigned
EDPatientMoved

EDRapidAssessmentCompleted
EDCriticalAlertTriggered
EDCriticalAlertAcknowledged

EDResuscitationStarted
EDResuscitationCompleted
EDCriticalEventCreated

EDTraumaRegistered
EDConsultationRequested
EDConsultationCompleted

EDObservationStarted
EDObservationCompleted

EDICURequested
EDAdmissionRequested
EDSurgeryRequested

EDReferralCreated
EDTransferStarted
EDTransferCompleted

EDDischargeStarted
EDDischarged
EDLAMA
EDLeftWithoutBeingSeen
EDDeathRecorded

EDChargeableEventCreated
```

---

# 57. Scheduled Jobs

Potential jobs:

```text
MonitorEDWaitingTime
EscalateCriticalEDAlerts
DetectOverdueTriageReassessment
NotifyPendingConsultations
NotifyPendingCriticalResults
GenerateEDDailyCensus
GenerateEDOperationalReport
GenerateEDUtilizationReport
```

Use the existing queue/scheduler.

---

# 58. Notifications

Support:

- Critical triage
- Critical patient alert
- Critical lab
- Consultation request
- Consultation overdue
- ICU acceptance
- Admission availability
- Surgery request
- Transfer readiness
- Discharge readiness

Reuse existing notification infrastructure.

---

# 59. Audit

Audit:

- Arrival
- Registration
- Patient identification
- Triage
- Triage reassessment
- Queue priority
- Clinical assessment
- Resuscitation
- Procedures
- Medication-related events
- Critical alerts
- Consultations
- Trauma records
- ICU request
- Admission request
- Surgery request
- Transfer
- Referral
- Discharge
- LAMA
- Left without being seen
- Death
- Export
- Print
- Break-glass

---

# 60. Clinical Record Integrity

Finalized records cannot be silently modified.

Use:

```text
Amendment
Addendum
Version History
Correction Reason
User
Timestamp
```

Particularly protect:

- Triage
- Resuscitation
- Critical events
- Trauma documentation
- Procedures
- Consultations
- Final disposition
- Death documentation

---

# 61. Break-Glass

Reuse existing platform break-glass.

Example:

```text
Normal Access
     ↓
Denied
     ↓
Break Glass
     ↓
Reason
     ↓
Temporary Access
     ↓
Security Audit
```

Emergency urgency must not become an excuse for invisible access.

---

# 62. Multi-Hospital Security

Mandatory test:

```text
Hospital A ED User
       ↓
Hospital B Emergency Encounter
       ↓
Denied
```

Test:

- Direct URL
- API manipulation
- Patient ID manipulation
- Encounter ID manipulation
- Hospital ID manipulation
- Queue manipulation
- Report export
- Privilege escalation

---

# 63. Concurrency

Protect:

- Queue assignment
- Patient call
- Treatment-area assignment
- Resuscitation activation
- ICU request
- Admission request
- Surgery request
- Disposition
- Billing event

Use:

- Transactions
- Unique constraints
- Row locks
- Idempotency
- Appropriate status transitions

Two users must not simultaneously assign the same ED treatment area if the architecture requires exclusive occupancy.

---

# 64. Performance

Optimize:

- Emergency queue
- Tracking board
- Triage
- Patient search
- Current ED census
- Critical alerts
- Observation
- Dashboard

Use:

- Indexed search
- Pagination
- Eager loading
- Redis caching
- Background reporting
- Efficient time-based queries

Avoid loading the entire patient's lifetime EMR for every queue refresh.

---

# 65. FHIR Readiness

Prepare mappings for:

```text
Patient
Encounter
Location
Practitioner
PractitionerRole
Observation
Condition
Procedure
ServiceRequest
MedicationRequest
MedicationAdministration
DiagnosticReport
ImagingStudy
Specimen
CarePlan
ClinicalImpression
Task
ReferralRequest
DocumentReference
Consent
```

Potential mapping:

```text
ED Encounter
    → Encounter

Triage
    → Observation / ClinicalImpression

Emergency Order
    → ServiceRequest

Emergency Procedure
    → Procedure

ED Consultation
    → Task / ServiceRequest

Emergency Disposition
    → Encounter status / CarePlan context
```

---

# 66. HL7 Readiness

Potential interfaces:

```text
ADT
ORM
ORU
MDM
```

Emergency-specific events:

- ED arrival
- ED registration
- Admission
- Transfer
- Discharge
- Laboratory result
- Radiology result
- Clinical document

Reuse the existing interoperability layer.

---

# 67. AI Readiness

Future AI capabilities may include:

### Triage Assistance

Analyze documented:

- Symptoms
- Vitals
- Chief complaint
- Risk factors

and provide an advisory priority suggestion.

### Patient Deterioration

Analyze trends to identify patients who may require reassessment.

### Clinical Summary

Generate an ED summary from documented records.

### Documentation Assistance

Draft:

- ED physician note
- Handover
- Discharge summary
- Referral letter

### Operational Forecasting

Predict:

- ED demand
- Waiting time
- Staffing requirements
- Treatment-area utilization

### Queue Optimization

AI may recommend operational prioritization, but clinical priority must remain under authorized human control.

---

# 68. AI Safety

AI must never autonomously:

- Diagnose
- Assign final triage category
- Prescribe
- Change medication
- Order surgery
- Order ICU admission
- Discharge
- Transfer
- Override critical alerts
- Override allergy warnings
- Finalize clinical notes
- Modify patient records

Every AI suggestion must record:

```text
AI Generated
Model
Version
Generated At
Input Data Range
Recommendation
Confidence where applicable
Reviewed By
Review Time
Action Taken
```

---

# 69. Emergency Mass-Casualty Foundation

Build the data model so future mass-casualty functionality can be added.

Potential concepts:

- Incident
- Incident ID
- Surge capacity
- Casualty count
- Temporary patient identifier
- Triage category
- Resource allocation
- Tracking

Do not build a complete disaster-management system in this phase unless specifically required.

---

# 70. Unknown Patient Safety

Unknown patients are a critical requirement.

Use temporary identifiers.

Never:

- Create multiple permanent patients unnecessarily
- Merge records automatically
- Delete temporary identity history

Identity merge must be:

- Authorized
- Audited
- Reversible where appropriate
- Based on established matching rules

---

# 71. Emergency Patient Identification

At every critical point verify:

- Patient identity
- Temporary ID where applicable
- Procedure
- Medication
- Blood product
- Imaging
- Laboratory specimen

Unknown-patient workflows must remain safe even before identity is established.

---

# 72. Reporting

## Operational

- ED arrivals
- Arrivals by hour
- Arrivals by source
- Triage distribution
- Waiting time
- Door-to-provider time
- Length of stay
- Treatment-area utilization
- Observation utilization
- Discharge
- Admission
- ICU transfer
- Surgery transfer
- External referral
- LAMA
- Left without being seen

## Clinical

- Emergency diagnoses
- Trauma
- Critical events
- Resuscitation
- Procedures
- Stroke pathway
- Cardiac pathway
- Sepsis pathway
- Critical alerts

## Quality

- Triage reassessment compliance
- Consultation response time
- Critical result acknowledgement
- Door-to-provider
- Door-to-disposition
- Return visits
- Unplanned ICU admission
- Mortality
- Left without being seen

All metrics require clearly defined:

- numerator
- denominator
- timeframe
- inclusion criteria
- exclusion criteria
- source system

---

# 73. File Management

Reuse existing private file management.

Potential documents:

- Referral documents
- External medical records
- Police documents
- Accident documents
- Consent
- Discharge documents
- Transfer documents

Never expose sensitive clinical files through public URLs.

---

# 74. Testing Strategy

## Unit Tests

Test:

- Triage rules
- Priority changes
- Queue ordering
- Waiting time
- Unknown patient identifier
- Patient identification
- Observation status
- Disposition
- Billing idempotency
- Alert severity

## Feature Tests

Test:

- Arrival
- Registration
- Triage
- Reassessment
- Queue
- Rapid assessment
- Resuscitation
- Trauma
- Consultation
- Observation
- ICU request
- Admission request
- Surgery request
- Transfer
- Referral
- Discharge
- LAMA
- Left without being seen
- Death

## Integration Tests

Test:

```text
ED → Patient/MPI
ED → Clinical Encounter
ED → LIS
ED → Radiology
ED → Pharmacy
ED → Nursing
ED → Billing
ED → IPD
ED → ICU
ED → OT
ED → Blood Bank
```

---

# 75. Security Test Cases

Mandatory:

```text
Hospital A ED user cannot access Hospital B ED patient.

Unauthorized user cannot change triage.

Unauthorized user cannot override queue priority.

Unauthorized user cannot modify finalized resuscitation.

Unauthorized user cannot create ICU request.

Unauthorized user cannot discharge patient.

Unauthorized user cannot export ED records.

API scope bypass fails.

Direct object ID manipulation fails.

Privilege escalation fails.

Break-glass creates security event.
```

---

# 76. Negative Tests

Test:

- Invalid triage level
- Invalid patient
- Invalid encounter
- Duplicate registration
- Duplicate unknown patient
- Invalid treatment area
- Occupied treatment area
- Unauthorized priority change
- Invalid disposition
- Discharge without required documentation
- Duplicate ICU request
- Duplicate admission request
- Duplicate surgery request
- Duplicate billing event
- Unauthorized finalized-record modification

---

# 77. UI Menu

Recommended:

```text
Emergency Department
├── Dashboard
├── Tracking Board
├── Arrivals
├── Registration
├── Triage
├── Emergency Queue
├── Patients
├── Active Encounters
├── Resuscitation
├── Critical Alerts
├── Trauma
├── Consultations
├── Observation
├── Procedures
├── Referrals
├── Transfers
├── Admissions
├── ICU Requests
├── Surgery Requests
├── Discharge
├── LAMA
├── Left Without Being Seen
├── Death / Critical Events
├── Reports
└── Settings
```

---

# 78. Implementation Order

Implement in this sequence:

### Step 1
Inspect repository and Phases 0–11.

### Step 2
Map:

- Patient/MPI
- Clinical Encounter
- Billing
- LIS
- Radiology
- Pharmacy
- Admission/Bed
- Nursing
- ICU
- OT
- Blood Bank
- Notifications
- Audit

### Step 3
Implement ED configuration.

### Step 4
Implement arrival and registration.

### Step 5
Implement unknown-patient workflow.

### Step 6
Implement triage.

### Step 7
Implement triage reassessment.

### Step 8
Implement emergency queue.

### Step 9
Implement ED encounter integration.

### Step 10
Implement rapid assessment.

### Step 11
Implement resuscitation.

### Step 12
Implement monitoring.

### Step 13
Implement trauma foundation.

### Step 14
Implement consultations.

### Step 15
Implement observation.

### Step 16
Implement emergency procedures.

### Step 17
Implement critical alerts.

### Step 18
Implement ICU/admission/surgery/referral integrations.

### Step 19
Implement discharge/LAMA/left-without-being-seen.

### Step 20
Implement death/critical-event integration.

### Step 21
Implement billing integration.

### Step 22
Implement dashboard/tracking board.

### Step 23
Implement reports.

### Step 24
Implement API.

### Step 25
Implement FHIR/HL7 interfaces.

### Step 26
Implement AI extension points.

### Step 27
Implement tests.

### Step 28
Execute security testing.

### Step 29
Execute concurrency testing.

### Step 30
Execute performance checks.

### Step 31
Prepare deployment/documentation.

---

# 79. Definition of Done

Phase 12 is complete when:

- ED configuration exists
- Arrival workflow exists
- Registration exists
- Unknown patient workflow exists
- Triage exists
- Triage reassessment exists
- Emergency queue exists
- Tracking board exists
- Emergency encounter integration exists
- Rapid assessment exists
- Resuscitation exists
- Monitoring exists
- Trauma foundation exists
- Consultation workflow exists
- Observation exists
- Emergency procedures exist
- Critical alerts exist
- ICU integration exists
- IPD admission integration exists
- OT integration exists
- Laboratory integration exists
- Radiology integration exists
- Pharmacy integration exists
- Nursing/MAR integration exists
- Blood Bank integration exists
- Referral exists
- Transfer exists
- Discharge exists
- LAMA exists
- Left-without-being-seen exists
- Death/critical-event integration exists
- Billing integration exists
- Dashboard exists
- Reporting exists
- Audit exists
- RBAC exists
- Multi-hospital isolation exists
- API exists
- FHIR readiness exists
- HL7 readiness exists
- AI extension points exist
- Unit tests exist
- Feature tests exist
- Integration tests exist
- Security tests exist
- Concurrency tests exist
- Documentation exists

No regression to Phases 0–11.

---

# 80. Final AI Implementation Prompt

## COPY-PASTE PROMPT

You are a senior healthcare software architect, emergency-medicine informatics specialist, Laravel engineer, database architect, cybersecurity engineer and QA engineer.

Implement **Phase 12 — Emergency Department (ED)** in the existing Hospital Management System.

## CRITICAL INSTRUCTION

The existing modular architecture is already implemented.

Before coding:

1. Inspect the complete repository.
2. Inspect Phases 0–11.
3. Identify existing module architecture.
4. Inspect existing:
   - Patient/MPI
   - Appointment
   - Clinical Encounter/EMR
   - Billing
   - LIS
   - Radiology/RIS/PACS
   - Pharmacy
   - Admission/Bed Management
   - Nursing/MAR
   - OT/Surgery
   - ICU/Critical Care
   - Blood Bank integration
   - Authentication
   - RBAC
   - Audit
   - Notifications
   - Workflow
   - File Management
   - API
5. Map reusable models, services, actions, policies, events, jobs, routes, components and UI.
6. Reuse existing architecture.
7. Do NOT create a second modular architecture.
8. Do NOT duplicate existing patient, encounter, prescription, billing, nursing, ICU, bed or clinical-order systems.

---

## OBJECTIVE

Implement a production-grade Emergency Department supporting:

- Arrival
- Registration
- Unknown patients
- Triage
- Triage reassessment
- Emergency queue
- Tracking board
- Emergency encounter
- Rapid assessment
- Resuscitation
- Emergency monitoring
- Trauma
- Emergency procedures
- Consultations
- Observation
- Critical alerts
- Stroke foundation
- Cardiac emergency foundation
- Sepsis foundation
- ICU request
- IPD admission request
- Emergency surgery request
- Referral
- Transfer
- Discharge
- LAMA
- Left without being seen
- Death/critical events
- Billing
- Dashboard
- Reporting
- API
- Audit
- RBAC
- Multi-hospital isolation
- FHIR/HL7 readiness
- AI-ready architecture

---

## ARCHITECTURAL RULES

Use the existing:

- Laravel modular monolith
- PHP 8.4+
- Database architecture
- Redis
- Blade
- AdminLTE
- API architecture
- Authentication
- RBAC
- Policies
- Audit
- Notification system
- Workflow
- File management
- Queue
- Scheduler

Follow:

- SOLID
- DRY
- KISS
- PSR-12
- Thin controllers
- Form Requests
- Services/Actions
- Policies
- Events/Listeners
- Jobs
- Dependency Injection
- Database transactions

---

## MODULE BOUNDARIES

### Patient
Phase 1 owns MPI and identity.

### Clinical Encounter
Phase 3 owns clinical encounter, diagnosis, orders, prescription and core EMR.

### Billing
Phase 4 owns all financial transactions.

### LIS
Phase 5 owns laboratory workflow.

### Radiology
Phase 6 owns RIS/PACS.

### Pharmacy
Phase 7 owns medication master, pharmacy stock and dispensing.

### IPD
Phase 8 owns admission, hospital beds, location, transfer and discharge.

### Nursing
Phase 9 owns nursing and MAR.

### OT
Phase 10 owns surgical/OT workflow.

### ICU
Phase 11 owns ICU workflow.

### Blood Bank
Phase 13 will own blood bank.

ED must integrate with all of these.

---

## IMPLEMENT

Implement:

1. ED configuration
2. Zones and treatment areas
3. Arrival
4. Registration
5. Unknown patient
6. Triage
7. Triage reassessment
8. Queue
9. Tracking board
10. Emergency encounter
11. Rapid assessment
12. Resuscitation
13. Monitoring
14. Trauma
15. Consultation
16. Observation
17. Procedures
18. Critical alerts
19. ICU request
20. Admission request
21. Surgery request
22. Referral
23. Transfer
24. Discharge
25. LAMA
26. Left without being seen
27. Critical/death events
28. Billing
29. Dashboard
30. Reporting
31. API
32. Audit
33. FHIR/HL7 readiness
34. AI extension points

Implement only what is required and consistent with the existing repository.

---

## TRIAGE

Implement configurable triage.

Do not hardcode one country's triage policy.

Support:

- triage level
- vital signs
- chief complaint
- pain
- GCS
- risk factors
- clinical findings
- triage reason
- reassessment

Every triage reassessment must preserve history.

Do not allow unauthorized users to change triage priority.

---

## UNKNOWN PATIENT

Support temporary identifiers.

Never automatically merge unknown patients.

Identity reconciliation must be:

- authorized
- audited
- traceable

---

## QUEUE

Implement configurable priority-based queueing.

Consider:

- triage level
- deterioration
- critical alerts
- arrival time
- configured priority rules

AI must never silently change clinical priority.

All manual priority overrides must be:

- authorized
- reason-based
- audited

---

## RESUSCITATION

Implement structured resuscitation documentation:

- event
- team
- airway
- breathing
- circulation
- CPR
- defibrillation
- medication
- procedures
- blood products
- outcome

Reuse Phase 11 critical-event architecture where possible.

---

## INTEGRATION

Implement reliable integrations:

```text
ED → Patient/MPI
ED → Clinical Encounter
ED → LIS
ED → Radiology
ED → Pharmacy
ED → Nursing/MAR
ED → Billing
ED → Admission/Bed
ED → ICU
ED → OT
ED → Blood Bank
```

Do not duplicate downstream systems.

---

## BILLING

ED must publish chargeable events.

Billing owns:

- price
- tax
- discount
- invoice
- payment
- refund

Use idempotent event identifiers.

---

## SECURITY

Mandatory:

```text
Hospital A ED user
    ≠
Hospital B ED data
```

Test:

- direct URL access
- API ID manipulation
- organization bypass
- hospital bypass
- branch bypass
- patient scope bypass
- queue manipulation
- unauthorized triage
- unauthorized disposition
- unauthorized export
- privilege escalation

---

## CLINICAL SAFETY

The system must never autonomously:

- diagnose
- prescribe
- change medication
- assign final triage
- discharge
- transfer
- request ICU admission
- request surgery
- override allergy warnings
- override critical alerts
- finalize clinical decisions

AI is advisory only.

---

## AI

Create extension points for:

- triage assistance
- deterioration detection
- ED clinical summarization
- documentation assistance
- waiting-time prediction
- demand forecasting
- operational optimization

Every AI output must include:

- model
- version
- timestamp
- relevant input period
- recommendation
- confidence where applicable
- human review
- final action

AI must never silently alter clinical records.

---

## API

Use:

```text
/api/v1/emergency
```

Use the existing standard response:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {},
  "meta": {}
}
```

Enforce authorization and scope on every endpoint.

---

## EVENTS

Implement appropriate domain events, including:

```text
EDPatientArrived
EDRegistrationCompleted
EDPatientIdentified
EDTriageCompleted
EDTriageReassessed
EDPriorityChanged
EDEmergencyEncounterStarted
EDCriticalAlertTriggered
EDResuscitationStarted
EDResuscitationCompleted
EDTraumaRegistered
EDConsultationRequested
EDObservationStarted
EDICURequested
EDAdmissionRequested
EDSurgeryRequested
EDReferralCreated
EDTransferCompleted
EDDischarged
EDLAMA
EDLeftWithoutBeingSeen
EDDeathRecorded
EDChargeableEventCreated
```

Reuse existing event architecture where possible.

---

## TESTING

Create:

### Unit tests

- triage
- queue priority
- reassessment
- unknown identity
- waiting time
- disposition
- alerts
- billing idempotency

### Feature tests

- arrival
- registration
- triage
- queue
- assessment
- resuscitation
- trauma
- consultation
- observation
- referral
- transfer
- ICU request
- admission request
- surgery request
- discharge
- LAMA
- LWBS
- death

### Integration tests

```text
ED → Patient
ED → Encounter
ED → LIS
ED → Radiology
ED → Pharmacy
ED → Nursing
ED → Billing
ED → IPD
ED → ICU
ED → OT
ED → Blood Bank
```

### Security tests

Mandatory cross-hospital and scope-isolation tests.

### Concurrency tests

Test simultaneous:

- queue assignment
- treatment-area assignment
- ICU request
- admission request
- surgery request
- discharge
- billing

Do not claim tests passed unless actually executed.

---

## PERFORMANCE

Optimize:

- tracking board
- emergency queue
- triage
- patient search
- critical alerts
- current ED census
- dashboard

Use appropriate indexes, pagination, caching and background processing.

---

## FINAL IMPLEMENTATION REPORT

After coding, report:

1. Existing architecture discovered
2. Phases 0–11 reused
3. New ED module structure
4. Database changes
5. Models
6. Services/actions
7. Controllers
8. Form Requests
9. Policies
10. Routes
11. Permissions
12. UI
13. Triage workflow
14. Emergency workflow
15. Resuscitation workflow
16. Trauma workflow
17. Consultation workflow
18. Observation workflow
19. ICU/IPD/OT integration
20. LIS/Radiology/Pharmacy/Nursing integration
21. Billing integration
22. Events
23. Jobs
24. Notifications
25. Audit/security
26. Multi-hospital isolation
27. API
28. FHIR/HL7 readiness
29. AI extension points
30. Tests actually executed
31. Deployment requirements
32. Known limitations
33. Deferred features

Never claim implementation or testing that was not actually performed.

The final implementation must be production-oriented, secure, auditable, clinically safe, maintainable and fully consistent with Phases 0–11.