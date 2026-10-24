
# Phase 11 — ICU / Critical Care
## Module Specification & AI Implementation Prompt

**Architecture:** Existing Laravel Modular Monolith  
**Technology:** PHP 8.4+, Laravel, MySQL/PostgreSQL, Redis, Blade + AdminLTE, REST API  
**Phase:** 11  
**Module:** ICU / Critical Care Management  
**Prerequisites:** Phases 0–10  
**Primary Objective:** Production-grade ICU and critical-care workflow integrated with Patient/MPI, Clinical Encounter/EMR, Billing, LIS, Radiology, Pharmacy, IPD/Bed Management, Nursing, OT/Surgery, Blood Bank, and future Emergency/ICU analytics.

---

# 1. Module Purpose

Phase 11 implements the **ICU / Critical Care Management** capabilities of the Hospital Management System.

The module must support the complete lifecycle of critically ill patients:

> Patient → Admission/Transfer to ICU → ICU Bed Assignment → Critical Care Assessment → Monitoring → Nursing Care → Physician Rounds → Orders → Medication → Ventilation/Respiratory Support → Procedures → Investigations → Daily Care → Clinical Documentation → Transfer/Step-down → Discharge

The module should support:

- ICU admission
- ICU bed and location integration
- Critical-care assessment
- Continuous patient monitoring
- Vital-sign observations
- ICU flowsheets
- Ventilator/respiratory support documentation
- Oxygen therapy
- Infusions
- Lines/tubes/drains
- Nursing care
- Physician rounds
- Critical-care progress notes
- ICU orders
- Medication administration integration
- Laboratory integration
- Radiology integration
- Procedures
- Sedation/pain assessment
- Fluid balance
- Intake/output
- Clinical scoring
- Sepsis workflow foundation
- Rapid deterioration alerts
- Code/emergency event documentation
- Family communication documentation
- Transfer and step-down
- ICU discharge
- ICU utilization reporting
- Critical-care audit
- AI-ready clinical decision-support extension points

---

# 2. Critical Architecture Rule

The existing modular architecture is **already implemented**.

The AI implementation must:

1. Inspect the existing repository.
2. Inspect Phases 0–10.
3. Reuse existing architecture.
4. Reuse existing models, services, traits, policies, permissions, workflows, audit systems and UI patterns.
5. Reuse existing Patient/MPI.
6. Reuse existing Clinical Encounter/EMR.
7. Reuse existing Admission/Bed Management.
8. Reuse existing Nursing.
9. Reuse existing Pharmacy.
10. Reuse existing Laboratory.
11. Reuse existing Radiology.
12. Reuse existing Billing.
13. Reuse existing OT/Surgery.
14. Reuse existing Blood Bank integration interfaces.
15. **Do NOT create another modular framework.**
16. **Do NOT create duplicate patient, admission, bed, prescription, medication, billing or nursing systems.**

ICU should become a specialized critical-care layer over the existing HMS platform.

---

# 3. Phase 11 Scope

## 3.1 Core ICU Management

Implement:

- ICU master configuration
- ICU types
- ICU units
- ICU rooms/bays
- ICU beds
- Bed capability
- Isolation capability
- Ventilator availability
- Monitoring equipment reference
- ICU admission
- ICU transfer
- ICU discharge
- ICU occupancy
- ICU patient census
- ICU bed board

Examples:

- Medical ICU
- Surgical ICU
- Cardiac ICU
- Neuro ICU
- Pediatric ICU
- Neonatal ICU

These should be configurable rather than hardcoded.

---

# 4. ICU Architecture

Recommended hierarchy:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
ICU Unit
    ↓
ICU Room / Bay
    ↓
ICU Bed
```

Example:

```text
Duncan Hospital
    └── Main Hospital
        └── Critical Care Department
            ├── Medical ICU
            │   ├── Bed MICU-01
            │   ├── Bed MICU-02
            │   └── Bed MICU-03
            │
            └── Surgical ICU
                ├── Bed SICU-01
                └── Bed SICU-02
```

---

# 5. ICU Bed Management Boundary

Phase 8 owns the hospital-wide:

- Admission
- Bed master
- Bed allocation
- Patient location
- Transfer
- Discharge

Phase 11 provides ICU-specific behavior and workflows.

Do not create a second independent bed-management engine.

ICU should integrate with Phase 8:

```text
Admission / Transfer Request
        ↓
Phase 8 Bed Management
        ↓
ICU Bed Assignment
        ↓
Phase 11 ICU Admission
```

The ICU module may expose ICU-specific bed requirements such as:

- Ventilator-capable
- Isolation
- Negative-pressure
- Cardiac monitoring
- Dialysis capability
- Pediatric capability
- Bariatric capability

But the authoritative hospital bed/location system remains Phase 8.

---

# 6. ICU Admission Workflow

Standard workflow:

```text
Critical Patient Identified
        ↓
ICU Request
        ↓
Clinical Review
        ↓
ICU Acceptance
        ↓
Bed Availability
        ↓
Admission / Transfer
        ↓
ICU Bed Assignment
        ↓
Initial ICU Assessment
        ↓
Baseline Monitoring
        ↓
Treatment / Monitoring
```

Statuses:

```text
Requested
Under Review
Accepted
Rejected
Waiting for Bed
Admitted
Transferred
Step-down
Discharged
Cancelled
```

Emergency admission may bypass selected administrative steps, but:

> Patient safety, identity verification and clinical documentation requirements must not be silently bypassed.

Emergency overrides must be:

- Explicit
- Authorized
- Reason-based
- Audited

---

# 7. ICU Patient Record

Each ICU patient should have a specialized ICU episode linked to:

- Patient
- Encounter
- Admission
- ICU unit
- ICU bed
- Attending physician
- Primary diagnosis
- ICU admission reason
- Admission date/time
- Transfer history
- Critical-care status

Example:

```text
Patient
  ↓
Admission
  ↓
ICU Episode
  ↓
ICU Bed
  ↓
ICU Monitoring
  ↓
ICU Clinical Documentation
```

Do not duplicate the patient's demographic information.

---

# 8. ICU Episode

Suggested fields:

```text
id
organization_id
hospital_id
branch_id
patient_id
encounter_id
admission_id
icu_unit_id
icu_bed_id
episode_number
admission_reason
primary_diagnosis_id
admitting_provider_id
attending_provider_id
admitted_at
discharged_at
status
priority
source
created_by
completed_by
completed_at
created_at
updated_at
```

ICU episode number must be:

- Unique
- Immutable
- Concurrency-safe

Example:

```text
ICU-2026-00000125
```

Never generate numbers using:

```text
COUNT(*) + 1
```

Use a transaction-safe sequence/counter mechanism.

---

# 9. Critical Care Assessment

Implement structured ICU assessment.

Sections may include:

## General

- Mental status
- Consciousness
- General appearance
- Mobility
- Pain
- Sedation

## Respiratory

- Respiratory rate
- SpO₂
- Oxygen requirement
- Oxygen device
- Ventilator status
- Respiratory effort
- Breath sounds

## Cardiovascular

- Heart rate
- Blood pressure
- MAP
- Rhythm
- Peripheral perfusion
- Vasopressor requirement

## Neurological

- GCS
- Pupils
- Neurological findings
- Seizure activity
- Delirium screening

## Renal

- Urine output
- Renal function
- Dialysis status
- Fluid balance

## Gastrointestinal

- Abdomen
- Feeding
- Bowel status
- GI bleeding

## Skin

- Pressure injury
- Wounds
- Surgical site
- Edema

## Infection

- Infection suspected
- Infection source
- Culture status
- Antibiotic therapy

All assessment values must be time-stamped.

---

# 10. Vital Signs and Continuous Monitoring

ICU monitoring is time-series data.

Never overwrite previous observations.

Support:

- Temperature
- Heart rate
- Respiratory rate
- SpO₂
- Systolic BP
- Diastolic BP
- MAP
- CVP where applicable
- Pain score
- GCS
- Sedation score
- Blood glucose
- Urine output

Future device integration should support:

```text
Patient Monitor
       ↓
Device Adapter
       ↓
Validation
       ↓
Observation
       ↓
ICU Flowsheet
       ↓
Clinical Record
```

Do not directly write device data into arbitrary clinical tables.

---

# 11. ICU Flowsheet

The ICU flowsheet is one of the core features.

Provide configurable time intervals:

- Hourly
- 2-hourly
- 4-hourly
- Shift-based
- Daily

Display:

```text
Time
Vitals
Respiratory
Cardiovascular
Neurological
Fluid Balance
Medications
Ventilator
Lines
Drains
Nutrition
Nursing Care
Clinical Events
```

Example:

| Time | HR | BP | MAP | SpO₂ | Temp | RR | Urine |
|---|---:|---|---:|---:|---:|---:|---:|
| 08:00 | 102 | 110/70 | 83 | 96 | 37.2 | 20 | 45 ml |
| 09:00 | 108 | 105/68 | 80 | 95 | 37.3 | 22 | 40 ml |

Use optimized time-series queries and indexes.

---

# 12. ICU Nursing Integration

Phase 9 owns the primary nursing platform.

Phase 11 consumes and extends it for critical-care workflows.

Reuse:

- Nursing assessment
- Nursing notes
- Care plans
- Medication Administration Record
- Patient observations
- Nursing tasks

ICU-specific nursing features:

- ICU flowsheet
- Ventilator observations
- Lines/drains
- Pressure injury assessment
- Critical-care nursing assessment
- Intake/output
- Turning/repositioning
- Oral care
- Catheter care
- Ventilator care
- Isolation precautions

Do not create a second MAR.

---

# 13. Physician ICU Rounds

Provide an ICU physician workspace.

Sections:

```text
Patient Header
↓
ICU Status
↓
Current Diagnosis
↓
Vitals
↓
Ventilation
↓
Hemodynamics
↓
Labs
↓
Imaging
↓
Medications
↓
Fluid Balance
↓
Lines / Tubes / Drains
↓
Procedures
↓
Clinical Scores
↓
Assessment
↓
Plan
```

Support:

- Daily ICU progress note
- Critical-care assessment
- Problem-based assessment
- Daily plan
- Family discussion
- Prognostic discussion documentation
- Transfer readiness

---

# 14. ICU Progress Notes

Suggested structure:

```text
Date / Time

Subjective
Objective
Assessment
Plan
```

Also support ICU-specific:

- Overnight events
- Respiratory status
- Hemodynamic status
- Neurological status
- Renal status
- Infection
- Nutrition
- Lines
- Drains
- Procedures
- Critical issues
- Plan

Finalized notes must not be silently modified.

Corrections should use:

- Addendum
- Amendment
- Version history

---

# 15. Clinical Orders Integration

Phase 3 owns the generic Clinical Order framework.

ICU should consume/reuse it.

Examples:

```text
Laboratory Order
Radiology Order
Medication Order
Procedure Order
Blood Request
Nutrition Order
Monitoring Order
```

Workflow:

```text
ICU Physician
     ↓
Clinical Order
     ↓
Appropriate Module
     ↓
Result / Execution
     ↓
ICU Clinical Record
```

Do not build separate ICU lab/radiology/pharmacy order systems.

---

# 16. Laboratory Integration

Phase 5 owns LIS.

ICU consumes:

- Lab orders
- Results
- Critical values
- Microbiology
- Blood gas
- Hematology
- Chemistry
- Coagulation

Critical result workflow:

```text
LIS Critical Result
        ↓
Clinical Notification
        ↓
ICU Alert
        ↓
Acknowledgement
        ↓
Clinical Action
        ↓
Audit
```

Support:

- Critical result notification
- Acknowledgement
- Escalation
- Response documentation

Do not duplicate LIS results.

---

# 17. Radiology Integration

Phase 6 owns RIS/PACS.

ICU should display:

- Imaging orders
- Exam status
- Reports
- Critical findings
- PACS/image links

Do not store complete DICOM image sets in ICU relational tables.

---

# 18. Pharmacy / Medication Integration

Phase 7 owns:

- Medication master
- Prescription
- Pharmacy stock
- Dispensing
- Medication safety

Phase 9 owns MAR.

Phase 11 integrates with both.

Workflow:

```text
ICU Physician
     ↓
Medication Order
     ↓
Pharmacy
     ↓
Verification
     ↓
Dispensing
     ↓
Nursing MAR
     ↓
Medication Administration
     ↓
ICU Record
```

Support ICU medication concepts:

- Continuous infusion
- Titration
- PRN medication
- High-alert medication
- Vasopressor
- Sedative
- Analgesic
- Antibiotic
- Anticoagulant

Medication safety remains governed by the existing medication/pharmacy architecture.

---

# 19. Infusion Management

Provide structured infusion documentation.

Examples:

- Vasopressors
- Inotropes
- Sedatives
- Analgesics
- Insulin
- Electrolytes
- Nutrition

Fields may include:

```text
Medication
Concentration
Dose
Dose Unit
Rate
Rate Unit
Route
Start Time
Stop Time
Titration
Target
Current Rate
Prescriber
Nurse
```

Do not create an independent medication master.

---

# 20. Ventilator Management

Implement a configurable ventilator documentation layer.

Support:

## Ventilator Mode

Examples:

- Volume Control
- Pressure Control
- SIMV
- CPAP
- BiPAP
- PSV
- Other

## Parameters

- FiO₂
- PEEP
- Tidal Volume
- Respiratory Rate
- Pressure Support
- Peak Pressure
- Plateau Pressure
- Mean Airway Pressure
- I:E Ratio

## Patient Response

- SpO₂
- ABG relationship
- Respiratory effort
- Compliance where documented

## Ventilator Events

- Intubation
- Ventilator initiation
- Mode change
- Parameter change
- Weaning trial
- Extubation
- Re-intubation

Do not automatically control ventilators.

Future device integration should use an adapter architecture.

---

# 21. Respiratory Support

Support:

- Room air
- Nasal cannula
- Face mask
- Non-rebreather
- HFNC
- NIV
- Invasive ventilation
- Other configurable support

Record:

- Device
- Flow
- FiO₂
- Start/end time
- Provider/nurse
- Reason
- Response

---

# 22. Lines, Tubes and Drains

Implement structured tracking.

Examples:

### Lines

- Peripheral IV
- Central venous catheter
- Arterial line
- PICC

### Tubes

- Endotracheal tube
- Nasogastric tube
- Feeding tube
- Urinary catheter

### Drains

- Chest drain
- Surgical drain
- Other

Track:

```text
Type
Site
Side
Insertion Date/Time
Inserted By
Indication
Status
Last Assessment
Removal Date/Time
Removal By
Complications
```

Important:

> Lines/tubes/drains are clinical devices and must be traceable throughout the ICU stay.

---

# 23. Fluid Balance

Implement:

```text
Intake
-
Output
=
Net Balance
```

### Intake

- IV fluids
- Oral
- Enteral feeding
- Blood products
- Medication fluids
- Other

### Output

- Urine
- Drain
- NG aspirate
- Stool
- Vomit
- Chest tube
- Other

Support:

- Hourly balance
- Shift balance
- Daily balance
- Cumulative balance

Use precise units and configurable conversions.

---

# 24. Nutrition Integration

Provide ICU nutrition documentation.

Support:

- Oral
- Enteral
- Parenteral
- NPO
- Feeding route
- Feeding rate
- Nutrition plan
- Dietitian assessment

If a separate Nutrition/Dietary module exists later, integrate with it rather than duplicating the master.

---

# 25. Clinical Scoring Framework

Provide a configurable scoring framework.

Potential examples:

- SOFA
- qSOFA
- APACHE II
- GCS
- NEWS/NEWS2
- RASS
- CAM-ICU
- Braden

Important:

Do not hardcode clinical scores into controllers.

Create a configurable scoring framework supporting:

```text
Score Definition
    ↓
Variables
    ↓
Rules
    ↓
Calculation
    ↓
Result
    ↓
Interpretation
    ↓
Audit
```

Clinical scores should be calculated from documented observations where possible.

The system must preserve:

- Input values
- Calculation version
- Score
- Date/time
- User
- Source

Do not overwrite historical scores when scoring rules change.

---

# 26. Sepsis Management Foundation

Provide a configurable sepsis workflow foundation.

Potential stages:

```text
Risk / Trigger
↓
Clinical Assessment
↓
Sepsis Suspected
↓
Investigations
↓
Treatment
↓
Reassessment
↓
Escalation / Monitoring
```

Support documentation of:

- Suspected infection
- Source
- Relevant observations
- Lactate
- Cultures
- Antibiotics
- Fluids
- Vasopressors
- Reassessment
- Escalation

Do not automatically diagnose sepsis.

AI/rule engines may provide alerts but clinician confirmation is required.

---

# 27. Clinical Deterioration Alerts

Implement configurable alerts based on documented data.

Examples:

- Low oxygen saturation
- Hypotension
- High heart rate
- Severe fever
- Low urine output
- Critical laboratory result
- Rapid deterioration
- Ventilator parameter concern

Alerts should contain:

```text
Patient
Rule
Severity
Triggered Value
Expected Range
Triggered At
Acknowledged At
Acknowledged By
Action
Escalation Status
```

Avoid alert fatigue.

Support:

- Severity
- Suppression
- Snooze
- Acknowledgement
- Escalation
- Audit

---

# 28. Code Blue / Critical Event Documentation

Implement a structured critical event record.

Examples:

- Cardiac arrest
- Respiratory arrest
- Code Blue
- Rapid deterioration
- Emergency intubation
- Emergency procedure

Capture:

```text
Event Time
Recognition Time
Response Time
Event Type
Location
Participants
Initial Condition
Interventions
Medications
Procedures
Defibrillation
Airway Management
ROSC
Outcome
Post-event Plan
```

The module should support integration with:

- Pharmacy/MAR
- Nursing
- Clinical Encounter
- Procedure
- Billing where applicable

---

# 29. ICU Procedures

ICU procedure documentation may include:

- Intubation
- Central line insertion
- Arterial line
- Chest tube
- Bronchoscopy integration
- Bedside ultrasound integration
- Tracheostomy
- Dialysis-related procedure
- Other configurable procedures

Phase 11 documents ICU execution.

Existing Clinical Procedure infrastructure should be reused where available.

---

# 30. Dialysis Integration

Provide an integration-ready interface for renal replacement therapy.

Possible modalities:

- Hemodialysis
- CRRT
- Peritoneal dialysis

Record:

- Therapy type
- Start/end
- Access
- Parameters
- Fluid removal
- Complications
- Provider
- Status

Do not build a complete standalone dialysis subsystem unless explicitly included in a later phase.

---

# 31. Blood Bank Integration

Phase 13 owns Blood Bank.

ICU should support:

```text
Blood Requirement
      ↓
Blood Bank
      ↓
Blood Product
      ↓
Transfusion
      ↓
Clinical Record
```

Support display of:

- Blood requirement
- Compatibility status
- Blood products
- Transfusion status
- Reaction alerts

Do not duplicate blood inventory.

---

# 32. ICU-to-Ward Transfer

Workflow:

```text
ICU Patient
     ↓
Clinical Review
     ↓
Transfer Decision
     ↓
Transfer Order
     ↓
Receiving Unit
     ↓
Bed Assignment
     ↓
Handover
     ↓
ICU Episode Closed
```

Phase 8 owns actual hospital location/bed transfer.

Phase 11 owns critical-care handover.

Handover should include:

- Diagnosis
- ICU course
- Current condition
- Respiratory support
- Medications
- Lines/tubes/drains
- Outstanding investigations
- Pending results
- Current risks
- Follow-up plan

---

# 33. ICU Discharge

Support:

- Transfer to ward
- Transfer to HDU
- Transfer to another ICU
- Transfer to another facility
- Discharge
- Death

ICU discharge summary should include:

- ICU admission reason
- Diagnoses
- Major events
- Procedures
- Ventilation
- Medications
- Complications
- Laboratory course
- Imaging
- Procedures
- Current status
- Transfer destination
- Follow-up plan

Hospital-wide discharge remains governed by Phase 8.

---

# 34. Death / Expired Patient Integration

The ICU module must not create a competing patient-death system.

If death occurs:

```text
ICU Event
   ↓
Clinical Documentation
   ↓
Hospital Patient Status / Death Workflow
   ↓
Audit
```

Document:

- Date/time
- Location
- Clinical circumstances
- Resuscitation status
- Code event
- Physician documentation

Follow the hospital's configured legal/administrative workflow.

---

# 35. ICU Handover

Provide structured shift handover.

Example:

```text
Patient
Diagnosis
Current condition
Airway
Breathing
Circulation
Neurology
Renal
Nutrition
Lines
Drains
Medications
Pending Results
Pending Orders
Risks
Plan
```

Support:

- Nurse-to-nurse
- Physician-to-physician
- Shift handover
- Transfer handover

---

# 36. Family Communication

Provide a structured documentation feature.

Record:

```text
Date/Time
Family Contact
Relationship
Communication Type
Participants
Clinical Information Shared
Treatment Discussion
Prognosis Discussion
Questions
Decisions
Follow-up
Documented By
```

Do not automatically generate or finalize consent/legal decisions.

---

# 37. ICU Consent Integration

Reuse the existing consent framework.

Potential ICU-specific consent contexts:

- Procedures
- Invasive devices
- Blood products
- Surgery
- Research where applicable

Do not create a second consent engine.

---

# 38. ICU Equipment Integration

ICU may reference:

- Patient monitors
- Ventilators
- Infusion pumps
- Dialysis machines
- Defibrillators

Enterprise asset/inventory ownership remains outside ICU.

Future integration:

```text
Device
 ↓
Device Adapter
 ↓
Data Validation
 ↓
Clinical Observation
 ↓
ICU Record
```

Never allow unvalidated device data to silently become a finalized clinical record.

---

# 39. Billing Integration

Phase 4 remains the single financial engine.

ICU publishes chargeable events such as:

- ICU bed/day
- Critical-care services
- Procedures
- Ventilation
- Specialized monitoring
- Consumables
- Professional services

Workflow:

```text
ICU Service
    ↓
Chargeable Event
    ↓
Billing Engine
    ↓
Invoice
    ↓
Payment
```

Billing owns:

- Price
- Discount
- Tax
- Invoice
- Payment
- Receipt
- Refund

ICU must not implement its own invoice/payment engine.

Use an idempotency key such as:

```text
ICU:{icu_episode_id}:{service_code}:{service_date}:{source_event_id}
```

---

# 40. Insurance Integration

Provide insurance-ready references:

- Patient insurance
- Coverage
- Authorization
- Chargeable services
- ICU stay

Phase 14 owns full insurance/claims processing.

Do not build a second claims engine.

---

# 41. Suggested Database Tables

Exact schema must follow the existing repository.

Potential tables:

```text
icu_types
icu_units
icu_rooms
icu_beds
icu_bed_capabilities

icu_episodes
icu_episode_status_history
icu_admission_requests
icu_transfer_requests

icu_assessments
icu_assessment_items

icu_observations
icu_vital_signs
icu_flowsheets
icu_flowsheet_entries

icu_progress_notes
icu_rounds
icu_handover_records

icu_respiratory_support
icu_ventilator_records
icu_ventilator_events

icu_infusions
icu_infusion_events

icu_lines
icu_tubes
icu_drains

icu_fluid_balance
icu_fluid_balance_entries

icu_nutrition_records

icu_clinical_scores
icu_clinical_score_results

icu_alerts
icu_alert_acknowledgements
icu_alert_escalations

icu_critical_events
icu_code_blue_events

icu_procedures
icu_procedure_records

icu_family_communications

icu_transfer_records
icu_discharge_summaries

icu_chargeable_events
```

Do not blindly create every table.

First inspect existing equivalents.

---

# 42. Suggested ICU Episode Table

Conceptual structure:

```text
icu_episodes

id
organization_id
hospital_id
branch_id
patient_id
encounter_id
admission_id
icu_unit_id
icu_bed_id
episode_number
admission_reason
primary_diagnosis_id
admitting_provider_id
attending_provider_id
priority
status
admitted_at
discharged_at
created_by
completed_by
completed_at
created_at
updated_at
```

Use foreign keys where appropriate.

Use indexes for:

```text
organization_id
hospital_id
branch_id
patient_id
encounter_id
admission_id
icu_unit_id
icu_bed_id
status
admitted_at
```

---

# 43. ICU Observation Model

Do not create one giant table containing every possible clinical value.

Use a flexible but controlled observation architecture.

Possible structure:

```text
icu_observations

id
organization_id
hospital_id
branch_id
icu_episode_id
patient_id
observation_type
code
value_numeric
value_text
unit
reference_range
observed_at
recorded_by
source
created_at
updated_at
```

For high-frequency observations, optimize storage and indexing appropriately.

---

# 44. Audit Requirements

Audit at minimum:

- ICU admission
- ICU transfer
- Bed assignment
- Bed change
- Assessment
- Vital entry
- Observation correction
- Progress note
- Score calculation
- Ventilator documentation
- Infusion change
- Line insertion/removal
- Procedure
- Code Blue
- Critical alert
- Alert acknowledgement
- Alert override
- Medication-related events
- Lab result acknowledgement
- Transfer
- Discharge
- Family communication
- Export
- Print
- Break-glass access

Audit should capture:

```text
user
timestamp
action
module
entity
entity_id
old_values
new_values
IP
user_agent
request_id
reason
```

---

# 45. Clinical Record Integrity

The following must never be silently changed after finalization:

- ICU admission assessment
- Critical event record
- Code Blue record
- Final progress note
- Ventilator event
- Procedure record
- Transfer summary
- ICU discharge summary
- Clinical score result

Corrections use:

```text
Amendment
Addendum
Version
Correction Reason
User
Timestamp
```

---

# 46. Break-Glass Access

Reuse the platform's existing break-glass mechanism.

Workflow:

```text
Normal Authorization
       ↓
Access Denied
       ↓
Break Glass
       ↓
Reason Required
       ↓
Temporary Access
       ↓
Security Event
       ↓
Audit
```

Do not create another emergency-access framework.

---

# 47. Permissions

Suggested permissions:

```text
icu.dashboard.view

icu.unit.view
icu.unit.create
icu.unit.update

icu.bed.view
icu.bed.manage

icu.episode.view
icu.episode.create
icu.episode.update
icu.episode.admit
icu.episode.transfer
icu.episode.discharge

icu.assessment.view
icu.assessment.create
icu.assessment.update
icu.assessment.finalize

icu.observation.view
icu.observation.create
icu.observation.update

icu.flowsheet.view
icu.flowsheet.create
icu.flowsheet.update

icu.round.view
icu.round.create
icu.round.update
icu.round.finalize

icu.note.view
icu.note.create
icu.note.update
icu.note.finalize

icu.ventilator.view
icu.ventilator.create
icu.ventilator.update

icu.infusion.view
icu.infusion.create
icu.infusion.update

icu.lines.view
icu.lines.manage

icu.drains.view
icu.drains.manage

icu.fluid_balance.view
icu.fluid_balance.manage

icu.score.view
icu.score.calculate

icu.alert.view
icu.alert.acknowledge
icu.alert.manage

icu.critical_event.view
icu.critical_event.create
icu.critical_event.finalize

icu.procedure.view
icu.procedure.create
icu.procedure.finalize

icu.handover.view
icu.handover.create
icu.handover.finalize

icu.transfer.view
icu.transfer.create

icu.discharge.view
icu.discharge.create
icu.discharge.finalize

icu.reports.view
icu.reports.export

icu.audit.view

icu.settings.manage
```

---

# 48. Suggested Roles

Reuse existing provider/user roles wherever possible.

Potential ICU roles:

- ICU Physician
- Intensivist
- ICU Nurse
- Senior ICU Nurse
- ICU Charge Nurse
- Respiratory Therapist
- ICU Technician
- ICU Coordinator
- ICU Manager
- Clinical Administrator

Role assignment must use the existing RBAC system.

---

# 49. API

Base:

```text
/api/v1/icu
```

Potential endpoints:

```http
GET    /units
GET    /units/{unit}

GET    /beds
GET    /bed-board

GET    /episodes
POST   /episodes
GET    /episodes/{episode}
PUT    /episodes/{episode}

POST   /episodes/{episode}/admit
POST   /episodes/{episode}/transfer
POST   /episodes/{episode}/discharge

GET    /episodes/{episode}/assessment
POST   /episodes/{episode}/assessment

GET    /episodes/{episode}/observations
POST   /episodes/{episode}/observations

GET    /episodes/{episode}/flowsheet
POST   /episodes/{episode}/flowsheet

GET    /episodes/{episode}/rounds
POST   /episodes/{episode}/rounds

GET    /episodes/{episode}/notes
POST   /episodes/{episode}/notes

GET    /episodes/{episode}/ventilator
POST   /episodes/{episode}/ventilator

GET    /episodes/{episode}/infusions
POST   /episodes/{episode}/infusions

GET    /episodes/{episode}/lines
POST   /episodes/{episode}/lines

GET    /episodes/{episode}/drains
POST   /episodes/{episode}/drains

GET    /episodes/{episode}/fluid-balance
POST   /episodes/{episode}/fluid-balance

GET    /episodes/{episode}/scores
POST   /episodes/{episode}/scores/calculate

GET    /alerts
POST   /alerts/{alert}/acknowledge

GET    /critical-events
POST   /critical-events

GET    /episodes/{episode}/handover
POST   /episodes/{episode}/handover

GET    /dashboard
GET    /reports/occupancy
GET    /reports/utilization
GET    /reports/outcomes
```

Every endpoint must enforce:

- Authentication
- Authorization
- Organization scope
- Hospital scope
- Branch scope
- ICU scope
- Patient access rules

Never trust IDs supplied by the client.

---

# 50. Standard API Response

Success:

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
    "icu_bed_id": [
      "The selected ICU bed is not available."
    ]
  }
}
```

Never expose:

- SQL
- Stack traces
- File paths
- Secrets
- Internal infrastructure details

---

# 51. Events

Potential domain events:

```text
ICUAdmissionRequested
ICUAdmissionAccepted
ICUPatientAdmitted
ICUBedAssigned
ICUBedChanged

ICUAssessmentCompleted
ICUObservationRecorded

ICUProgressNoteCreated
ICUProgressNoteFinalized

ICUVentilatorStarted
ICUVentilatorChanged
ICUVentilatorStopped

ICUInfusionStarted
ICUInfusionChanged
ICUInfusionStopped

ICULineInserted
ICULineRemoved

ICUTubeInserted
ICUTubeRemoved

ICUDrainInserted
ICUDrainRemoved

ICUClinicalScoreCalculated

ICUCriticalAlertTriggered
ICUCriticalAlertAcknowledged
ICUCriticalAlertEscalated

ICUCriticalEventCreated
ICUCodeBlueStarted
ICUCodeBlueCompleted

ICUProcedureCompleted

ICUTransferRequested
ICUPatientTransferred

ICUDischargeStarted
ICUDischarged

ICUChargeableEventCreated
```

Events must be idempotent where downstream financial or notification processing is involved.

---

# 52. Scheduled Jobs

Potential jobs:

```text
GenerateICUAlerts
EscalateUnacknowledgedCriticalAlerts
GenerateICUFlowsheetTasks
DetectMissingICUDocumentation
GenerateICUDailyCensus
GenerateICUUtilizationReport
GenerateICUBedAvailabilityReport
NotifyPendingICUTransfer
GenerateICUQualityMetrics
```

Do not generate excessive automated alerts without configurable thresholds.

---

# 53. Notifications

Potential notifications:

- ICU admission
- Bed assignment
- Critical lab result
- Critical vital alert
- Unacknowledged alert
- Pending transfer
- Missing critical documentation
- Device/ventilator event
- Code Blue notification
- Family communication reminder
- ICU discharge/transfer

Notification channels should reuse the existing platform:

- In-app
- Email
- SMS
- Future push notification

---

# 54. ICU Dashboard

## Executive View

Display:

- Total ICU beds
- Occupied
- Available
- Cleaning
- Maintenance
- Isolation
- Ventilator-capable
- Current patients
- Admissions today
- Transfers today
- Discharges today

## Clinical View

Display:

- Critical alerts
- Unacknowledged alerts
- Ventilated patients
- Vasopressor patients
- High-risk patients
- Pending critical results
- Pending investigations
- Patients requiring review

## Operational View

Display:

- Bed utilization
- Average ICU stay
- Bed turnover
- Admissions
- Transfers
- Discharges
- Mortality statistics where appropriately authorized
- Staffing indicators where available

---

# 55. ICU Bed Board

Example:

```text
┌──────────┬──────────────┬──────────────┬─────────────┐
│ Bed      │ Patient      │ Status       │ Critical    │
├──────────┼──────────────┼──────────────┼─────────────┤
│ MICU-01  │ Patient A    │ Ventilated   │ High        │
│ MICU-02  │ Patient B    │ Stable       │ Medium      │
│ MICU-03  │ Available    │ Available    │             │
│ MICU-04  │ Patient C    │ Isolation    │ High        │
└──────────┴──────────────┴──────────────┴─────────────┘
```

Do not expose unnecessary patient information to unauthorized users.

---

# 56. ICU Patient Workspace

Recommended layout:

```text
Patient Header
├── MRN
├── Age/Sex
├── Allergies
├── Code Status
├── ICU Bed
└── Current Status

Clinical Summary

Vitals / Flowsheet

Respiratory
├── Oxygen
├── Ventilator
└── ABG

Hemodynamics

Neurology

Fluid Balance

Medications / Infusions

Lines / Tubes / Drains

Laboratory

Radiology

Clinical Scores

Procedures

Progress Notes

Nursing Care

Critical Alerts

Handover

Transfer / Discharge
```

---

# 57. ICU Nurse Workspace

Primary workflow:

```text
ICU Census
↓
Select Patient
↓
Review Alerts
↓
Vitals
↓
Flowsheet
↓
Medication / MAR
↓
Lines / Tubes / Drains
↓
Intake / Output
↓
Nursing Assessment
↓
Tasks
↓
Handover
```

The nurse should not need to navigate through unrelated hospital modules for common ICU tasks.

---

# 58. ICU Physician Workspace

Primary workflow:

```text
ICU Census
↓
Patient
↓
Overnight Events
↓
Vitals
↓
Ventilator
↓
Hemodynamics
↓
Labs
↓
Imaging
↓
Medication
↓
Fluid Balance
↓
Clinical Scores
↓
Assessment
↓
Plan
↓
Orders
↓
Progress Note
```

---

# 59. Reporting

Provide:

## Operational Reports

- ICU census
- Bed occupancy
- Bed utilization
- Admissions
- Transfers
- Discharges
- Average length of stay
- Bed turnover
- Ventilator utilization

## Clinical Reports

- ICU diagnosis
- Procedures
- Ventilation
- Critical events
- Code Blue
- Clinical scores
- Sepsis workflow
- Critical alerts

## Quality Reports

- ICU mortality
- Readmission
- Length of stay
- Ventilator days
- Infection indicators
- Pressure injuries
- Unplanned extubation
- Central line events
- Medication safety events

Any quality metric must have a clearly defined denominator, timeframe and data source.

---

# 60. Data Export

Support authorized export of:

- ICU episode summary
- Flowsheet
- Progress notes
- Procedures
- Medication history
- Laboratory results
- Imaging reports
- Transfer summary

Exports must be:

- Permission-controlled
- Audited
- Patient-scope checked
- Privacy controlled

---

# 61. Security

Critical security requirement:

> A Hospital A ICU user must never access Hospital B ICU patient data unless explicitly authorized through configured access scope.

Test:

```text
Hospital A ICU User
        ↓
Hospital B ICU Episode
        ↓
403 / Not Found
```

Also test:

- Direct URL access
- API ID manipulation
- Scope bypass
- Privilege escalation
- Unauthorized report export
- Unauthorized clinical-note editing
- Unauthorized alert override
- Unauthorized ICU discharge
- Unauthorized patient access
- Break-glass misuse

---

# 62. Concurrency and Reliability

Protect concurrent operations:

- ICU bed assignment
- Patient transfer
- ICU admission
- Discharge
- Ventilator record updates
- Critical-event creation
- Critical alert acknowledgement
- Chargeable events

Use:

- Database transactions
- Row-level locking where required
- Unique constraints
- Idempotency keys
- Optimistic/pessimistic locking where appropriate

Example:

Two ICU coordinators must not be able to assign the same ICU bed simultaneously.

---

# 63. Performance

Optimize:

- ICU census
- Bed board
- Patient timeline
- Flowsheet
- Observation queries
- Critical alerts
- Dashboard
- Daily ICU reports

Use:

- Pagination
- Eager loading
- Appropriate indexes
- Redis caching for relatively static ICU configuration
- Aggregated reporting queries
- Background jobs
- Time-range filtering

Avoid loading an entire ICU patient's historical observations unnecessarily.

---

# 64. FHIR Readiness

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
MedicationRequest
MedicationAdministration
Device
DeviceUseStatement
ServiceRequest
DiagnosticReport
CarePlan
ClinicalImpression
DocumentReference
Consent
Task
```

Potential mapping:

```text
ICU Episode
    → Encounter

ICU Observation
    → Observation

Ventilator
    → Device / DeviceUseStatement

ICU Procedure
    → Procedure

ICU Clinical Assessment
    → ClinicalImpression

ICU Care Plan
    → CarePlan
```

Do not implement a full FHIR server unless the existing interoperability architecture requires it.

---

# 65. HL7 Readiness

Potential interfaces:

```text
ADT
ORM
ORU
MDM
```

Possible ICU integration events:

- Patient admitted
- Patient transferred
- Patient discharged
- Lab result
- Imaging result
- Clinical document
- Critical result

Use the existing integration layer.

---

# 66. AI Readiness

AI may eventually support:

### Clinical Summarization

Generate:

> "Last 24-hour ICU clinical summary"

from documented data.

### Deterioration Detection

Analyze:

- Vital trends
- Lab trends
- Oxygen requirement
- Hemodynamics
- Urine output

### Risk Prediction

Potential future models:

- Sepsis risk
- Deterioration risk
- ICU transfer risk
- Ventilator weaning support
- Readmission risk

### Documentation Assistance

AI may draft:

- ICU progress note
- Handover summary
- Daily summary
- Discharge summary

### Operational Optimization

AI may assist with:

- Bed utilization
- Staffing prediction
- ICU demand forecasting
- Length-of-stay analysis

---

# 67. AI Safety Rules

AI must remain advisory.

AI must **never autonomously**:

- Diagnose a patient
- Change diagnosis
- Prescribe medication
- Change medication dose
- Start/stop ventilation
- Change ventilator settings
- Order blood
- Approve blood products
- Cancel treatment
- Discharge patient
- Transfer patient
- Override allergy warnings
- Override critical alerts
- Finalize clinical documentation
- Modify patient records
- Change code status
- Make end-of-life decisions

Every AI-generated clinical suggestion must identify:

```text
AI-generated
Model/version
Generated time
Data/time range
Confidence where appropriate
Source/context
Review status
Reviewed by
Review time
```

---

# 68. AI Implementation Pattern

Use an abstraction such as:

```php
interface CriticalCareAIService
{
    public function summarizePatient(
        Patient $patient,
        CarbonPeriod $period
    ): AISummary;
}
```

Potential implementations:

```text
ICUPatientSummaryService
ICUDeteriorationRiskService
ICUHandoverAssistantService
ICUDocumentationAssistantService
ICUBedForecastService
```

AI services must be isolated from core clinical transactions.

---

# 69. Privacy

ICU information is highly sensitive.

Enforce:

- Minimum necessary access
- Patient-scope authorization
- Department restrictions
- Break-glass logging
- Export controls
- Audit trail
- Session security
- Secure API
- Encryption at rest where appropriate
- Encryption in transit
- No sensitive clinical data in application logs

---

# 70. File Management

Reuse existing Phase 0 file management.

Possible documents:

- ICU assessment documents
- Consent
- Clinical reports
- External medical records
- Transfer documents

Store sensitive files privately.

Use:

```text
DocumentReference
+
Private File Storage
+
Authorization
+
Audit
```

Do not expose direct public storage URLs.

---

# 71. Testing Strategy

## Unit Tests

Test:

- ICU episode lifecycle
- Bed availability
- Score calculation
- Fluid balance
- Observation validation
- Ventilator records
- Alert severity
- Alert escalation
- Transfer rules
- Discharge rules
- Chargeable event generation
- Idempotency

## Feature Tests

Test:

- ICU admission
- Bed assignment
- Transfer
- Observation entry
- Flowsheet
- Physician rounds
- Nursing workflow
- Ventilator documentation
- Infusion
- Critical alerts
- Code Blue
- ICU procedures
- Handover
- Discharge

## Integration Tests

Test:

```text
Patient → ICU
Admission → ICU
Nursing → ICU
Clinical Orders → ICU
LIS → ICU
RIS → ICU
Pharmacy → ICU
MAR → ICU
Blood Bank → ICU
Billing → ICU
OT → ICU
```

---

# 72. Security Test Cases

Mandatory:

```text
Hospital A ICU user cannot view Hospital B patient.

Hospital A ICU user cannot modify Hospital B observation.

Unauthorized user cannot finalize ICU note.

Unauthorized user cannot assign ICU bed.

Unauthorized user cannot discharge ICU patient.

Unauthorized user cannot export ICU records.

Unauthorized user cannot override critical alert.

Unauthorized user cannot access controlled ICU reports.

Break-glass access creates security event.

API scope bypass fails.

Direct ID manipulation fails.

Privilege escalation fails.
```

---

# 73. Negative Tests

Test:

- Assigning occupied bed
- Assigning inactive bed
- Duplicate ICU admission
- Invalid patient
- Invalid encounter
- Invalid admission
- Transfer to unavailable bed
- Discharge without required documentation
- Invalid observation unit
- Invalid score input
- Invalid ventilator parameters
- Duplicate critical event
- Duplicate billing event
- Unauthorized amendment
- Finalized record modification
- Invalid patient scope

---

# 74. UI Menu

Recommended:

```text
ICU / Critical Care
├── Dashboard
├── ICU Census
├── Bed Board
├── Admissions
├── Transfers
├── Patients
├── Assessments
├── Flowsheets
├── Physician Rounds
├── Nursing Care
├── Ventilator / Respiratory
├── Infusions
├── Lines / Tubes / Drains
├── Fluid Balance
├── Clinical Scores
├── Critical Alerts
├── Code Blue / Critical Events
├── Procedures
├── Handover
├── Discharge
├── Reports
└── Settings
```

---

# 75. Implementation Order

Implement in this sequence:

### Step 1
Inspect repository and Phases 0–10.

### Step 2
Map existing:

- Patient
- Encounter
- Admission
- Bed
- Nursing
- Pharmacy
- LIS
- Radiology
- Billing
- OT
- Blood Bank interfaces

### Step 3
Implement ICU master data.

### Step 4
Implement ICU units, rooms and bed capabilities.

### Step 5
Implement ICU admission/episode.

### Step 6
Implement ICU bed board.

### Step 7
Implement ICU assessment.

### Step 8
Implement observations and flowsheets.

### Step 9
Implement ICU physician rounds.

### Step 10
Implement nursing integration.

### Step 11
Implement respiratory/ventilator documentation.

### Step 12
Implement infusions.

### Step 13
Implement lines/tubes/drains.

### Step 14
Implement fluid balance.

### Step 15
Implement clinical scoring.

### Step 16
Implement critical alerts.

### Step 17
Implement Code Blue/critical events.

### Step 18
Implement ICU procedures.

### Step 19
Implement handover.

### Step 20
Implement ICU transfer/discharge.

### Step 21
Implement laboratory/radiology/pharmacy integrations.

### Step 22
Implement Billing chargeable events.

### Step 23
Implement dashboard and reports.

### Step 24
Implement API.

### Step 25
Implement FHIR/HL7 integration points.

### Step 26
Implement AI extension points.

### Step 27
Run tests.

### Step 28
Run security tests.

### Step 29
Run concurrency tests.

### Step 30
Run performance tests.

### Step 31
Prepare deployment documentation.

---

# 76. Definition of Done

Phase 11 is complete only when:

- ICU masters implemented
- ICU units implemented
- ICU bed integration implemented
- ICU admission implemented
- ICU transfer implemented
- ICU discharge implemented
- ICU census implemented
- ICU bed board implemented
- Critical-care assessment implemented
- ICU observations implemented
- Flowsheet implemented
- Physician rounds implemented
- Nursing integration implemented
- Ventilator documentation implemented
- Respiratory support implemented
- Infusions implemented
- Lines/tubes/drains implemented
- Fluid balance implemented
- Nutrition foundation implemented
- Clinical scores implemented
- Critical alerts implemented
- Code Blue implemented
- Critical event documentation implemented
- ICU procedures implemented
- Handover implemented
- Family communication implemented
- LIS integration implemented
- Radiology integration implemented
- Pharmacy integration implemented
- MAR integration implemented
- Blood Bank integration implemented
- Billing integration implemented
- Audit implemented
- RBAC implemented
- Multi-hospital scope implemented
- API implemented
- FHIR readiness implemented
- HL7 readiness implemented
- AI extension points implemented
- Unit tests implemented
- Feature tests implemented
- Integration tests implemented
- Security tests implemented
- Concurrency tests implemented
- Performance checks completed
- Documentation completed

No regression to Phases 0–10.

---

# 77. Final AI Implementation Prompt

## COPY-PASTE PROMPT

You are a senior healthcare software architect, Laravel engineer, database architect, clinical informatics specialist, cybersecurity engineer and QA engineer.

Implement **Phase 11 — ICU / Critical Care Management** in the existing Hospital Management System.

### CRITICAL INSTRUCTION

The existing modular architecture is already implemented.

Before writing any code:

1. Inspect the complete repository.
2. Identify the current Laravel version and PHP version.
3. Inspect the existing module architecture.
4. Inspect Phases 0–10.
5. Inspect:
   - Patient/MPI
   - Appointment
   - Clinical Encounter/EMR
   - Billing
   - LIS
   - Radiology/RIS
   - Pharmacy
   - Admission/Bed Management
   - Nursing
   - OT/Surgery
   - Authentication
   - RBAC
   - Audit
   - Notifications
   - File Management
   - Workflow
   - API
6. Identify existing models, migrations, services, actions, policies, events, jobs, routes, components and UI patterns that can be reused.
7. Do NOT create a second modular architecture.
8. Do NOT duplicate existing modules.

---

## OBJECTIVE

Build a production-grade ICU / Critical Care module supporting:

- ICU units
- ICU rooms
- ICU beds
- ICU admission
- ICU transfer
- ICU discharge
- ICU patient census
- ICU bed board
- Critical-care assessment
- ICU observations
- ICU flowsheets
- Physician rounds
- Nursing integration
- Ventilator documentation
- Respiratory support
- Infusions
- Lines/tubes/drains
- Fluid balance
- Nutrition foundation
- Clinical scores
- Critical alerts
- Sepsis workflow foundation
- Code Blue
- Critical events
- ICU procedures
- Handover
- Family communication
- Laboratory integration
- Radiology integration
- Pharmacy integration
- MAR integration
- Blood Bank integration
- Billing integration
- Reporting
- API
- Audit
- RBAC
- Multi-hospital access control
- FHIR/HL7 readiness
- AI-ready architecture

---

## ARCHITECTURAL RULES

Use:

- Existing Laravel modular architecture
- PHP 8.4+
- Existing database platform
- Redis
- Blade
- AdminLTE
- REST API
- Existing authentication
- Existing authorization
- Existing policies
- Existing audit
- Existing notifications
- Existing queue
- Existing scheduler
- Existing file management

Follow:

- SOLID
- DRY
- KISS
- PSR-12
- Laravel conventions
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

Do NOT duplicate:

### Phase 1
Patient/MPI

### Phase 3
Clinical Encounter, Diagnosis, Clinical Orders, Prescription

### Phase 4
Billing, Invoice, Payment, Receipt

### Phase 5
Laboratory

### Phase 6
Radiology/PACS

### Phase 7
Pharmacy and Medication

### Phase 8
Admission, Bed Management, Patient Location, Hospital Discharge

### Phase 9
Nursing and MAR

### Phase 10
OT/Surgery

### Phase 13
Blood Bank

ICU must integrate with these modules.

---

## IMPLEMENT

Implement ICU master data, ICU episodes, admission, bed board, assessments, observations, flowsheets, physician rounds, nursing integration, respiratory support, ventilator documentation, infusions, lines/tubes/drains, fluid balance, nutrition, clinical scoring, alerts, Code Blue, critical events, procedures, handover, transfer and discharge.

Implement appropriate:

- migrations
- models
- relationships
- factories
- seeders
- services/actions
- Form Requests
- policies
- controllers
- routes
- Blade views
- reusable components
- events
- listeners
- jobs
- notifications
- reports
- API resources
- tests

Do not generate unnecessary files.

---

## ICU SAFETY

Treat ICU as a high-risk clinical module.

Never allow the system to silently:

- modify finalized clinical records
- change diagnoses
- change medication
- change ventilator settings
- override critical alerts
- discharge a patient
- transfer a patient
- change code status
- make end-of-life decisions

All critical actions must be:

- authorized
- auditable
- timestamped
- attributable to a user

---

## CONCURRENCY

Protect:

- ICU bed assignment
- admission
- transfer
- discharge
- critical events
- alert acknowledgement
- chargeable events

Use database transactions and appropriate locking.

Never use:

```text
COUNT(*) + 1
```

for ICU episode numbers.

---

## MULTI-HOSPITAL SECURITY

This is mandatory.

Verify:

```text
Organization
Hospital
Branch
ICU
Patient
Admission
Encounter
Episode
Bed
```

server-side.

Test that Hospital A cannot access Hospital B ICU data.

Test:

- URL manipulation
- API ID manipulation
- scope bypass
- privilege escalation
- unauthorized exports
- unauthorized clinical record modification

---

## CLINICAL DATA INTEGRITY

Historical observations must never be overwritten.

Clinical records must support:

- finalization
- amendment
- addendum
- version history

Maintain complete audit history.

---

## ALERTS

Implement configurable critical-care alerts.

Include:

- severity
- trigger
- patient
- timestamp
- acknowledgement
- escalation
- action
- audit

Avoid excessive alert generation.

---

## CLINICAL SCORING

Implement a reusable scoring framework.

Do not hardcode score calculations inside controllers.

Preserve:

- score definition/version
- input variables
- calculated result
- timestamp
- user
- source

---

## BILLING

ICU publishes chargeable events.

Billing owns:

- pricing
- discounts
- taxes
- invoices
- payments
- refunds

Implement idempotent ICU charge generation.

---

## API

Implement:

```text
/api/v1/icu
```

Use the existing API response standard:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {},
  "meta": {}
}
```

Implement authorization and scope validation on every endpoint.

---

## EVENTS

Implement appropriate domain events including:

```text
ICUPatientAdmitted
ICUBedAssigned
ICUAssessmentCompleted
ICUObservationRecorded
ICUProgressNoteFinalized
ICUVentilatorStarted
ICUInfusionStarted
ICUCriticalAlertTriggered
ICUCriticalAlertAcknowledged
ICUCriticalEventCreated
ICUCodeBlueStarted
ICUCodeBlueCompleted
ICUProcedureCompleted
ICUPatientTransferred
ICUDischarged
ICUChargeableEventCreated
```

Reuse existing event conventions if available.

---

## JOBS

Implement only jobs actually required, such as:

```text
GenerateICUAlerts
EscalateUnacknowledgedCriticalAlerts
DetectMissingICUDocumentation
GenerateICUDailyCensus
GenerateICUUtilizationReport
NotifyPendingICUTransfer
```

Use existing queue infrastructure.

---

## AI

Create AI-ready service boundaries for:

- ICU patient summarization
- deterioration-risk assistance
- handover drafting
- documentation assistance
- ICU demand forecasting
- bed utilization forecasting

AI must remain advisory.

AI must never autonomously modify the patient's medical record or treatment.

---

## TESTING

Create:

### Unit Tests

For:

- bed availability
- ICU episode lifecycle
- fluid balance
- score calculation
- observation validation
- alerts
- transfer
- discharge
- billing idempotency

### Feature Tests

For:

- admission
- bed assignment
- observation
- flowsheet
- physician rounds
- ventilator
- infusion
- alerts
- Code Blue
- handover
- transfer
- discharge

### Integration Tests

For:

```text
Patient
Admission
Nursing
LIS
Radiology
Pharmacy
MAR
Blood Bank
Billing
OT
```

### Security Tests

Mandatory cross-hospital isolation tests.

### Concurrency Tests

Test simultaneous:

- bed assignment
- transfer
- discharge
- charge generation

Do not claim a test passed unless it was actually executed.

---

## PERFORMANCE

Optimize:

- ICU census
- bed board
- patient workspace
- flowsheet
- observations
- alerts
- dashboards
- reports

Use appropriate:

- indexes
- pagination
- eager loading
- caching
- background jobs
- aggregation

---

## FINAL DOCUMENTATION

After implementation provide a concise implementation report containing:

1. Existing architecture discovered
2. Reused modules
3. New ICU module structure
4. Database tables/migrations
5. Models and relationships
6. Services/actions
7. Controllers
8. Form Requests
9. Policies
10. Routes/API
11. Permissions
12. UI screens
13. ICU workflows
14. Events
15. Jobs
16. Notifications
17. Billing integration
18. Nursing integration
19. LIS integration
20. Radiology integration
21. Pharmacy/MAR integration
22. Blood Bank integration
23. Audit/security
24. Multi-hospital isolation
25. FHIR/HL7 readiness
26. AI extension points
27. Tests actually executed
28. Deployment requirements
29. Known limitations
30. Deferred features

Do not claim anything was implemented, tested or verified unless it actually was.

The final implementation must be production-oriented, maintainable, secure, auditable, clinically safe and consistent with Phases 0–10.