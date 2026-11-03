# D-P12 — Emergency Department: state at the end of Phase 12 build

Date: 2026-10-26. Slices S00–S12 of guideline §26.1 are built and tested. This is not a clinical sign-off.

## What is built
Zones/bays, rapid arrival and unknown patient, triage and re-triage (vitals only through the Observation Service), tracking board, bay locking, door-to-triage / door-to-doctor / re-triage timers, resuscitation record and Code Blue, monitoring with deterioration alerts, lab/radiology orders and prescriptions through the ED encounter, dose recording (with verbal orders), procedures, stroke / ACS / sepsis / trauma protocols with timers, trauma body map, mass-casualty mode, consultations with answer-by alerts, observation unit with hourly billing, dispositions (discharge, IPD, ICU, surgery, transfer, referral, LWBS, LAMA, death), ED charge events, performance page, workspace, export report.

## Needs the hospital before clinical use (OD-12)
- Triage scale names and minutes (config/ed.php), deterioration limits, consult answer times, observation billing rule (grace, minimum, review limit) — all are examples.
- Every protocol step and target in config/ed_protocols.php.
- Prices for ED-VISIT, ED-RESUS, ED-PROC, ED-OBS-HR (the demo build uses placeholder prices only).
- Who counts as "doctor" and "charge_nurse" for alerts (config ed.alert_roles).

## Deferred
- Patient-facing ED workspace (needs the patient portal to know about the ED).
- Death documentation: only time, cause, declaring user and the patient's deceased status are recorded. No death certificate, mortuary hand-over or police notification. If Phase 8/10 define a form, connect it.
- API endpoints (/api/v1/emergency), FHIR / HL7 mapping of the ED visit, AI-assist hooks.
- Results shown on the visit are order status only (lab per-test result status); values stay in Laboratory / Radiology.
- Ambulance is a flag on the disposition; there is no ambulance dispatch module.
- Break-glass gives read access to one visit of another branch; the grant itself is requested through the existing encounter break-glass screen.
- Real-user, hardware (wristband printer, scanner) and load testing of the board are not done.
