# Emergency Department — Module Charter

_Updated in every slice (guideline 7.4). Phase 12 slices S00–S12 are built; clinical values are examples awaiting sign-off (OD-12)._

- Phase / owner: 12 / **owner to be named by the hospital**
- Status: built · switchable per organization / hospital (`ed`, depends on opd, billing)
- Purpose: one tracking board for every patient in the emergency department, from arrival (including an unknown patient) to the moment they leave, with triage, resuscitation, orders, protocols, consultations, observation and a single closing decision.
- Permission prefixes: `ed` (see docs/registry/permissions.md). Table prefix: `ed_`. API base: /api/v1/emergency (no API endpoints yet — screens only).
- Owns: ED visits, zones and bays, triage records, bay assignments, status history, resuscitation events, dose records, protocol runs and steps, injuries, mass-casualty activations, consultations, observation stays, dispositions.
- Does NOT own: the patient (Patients), the encounter (Clinical — every ED visit points at one Emergency encounter), vital signs (Observation Service — no values are copied), alerts (Alert Service), prices (Billing), lab / radiology / pharmacy orders (those modules, reached through the clinical order and prescription events), IPD / ICU / OT requests (created through their own services).
- Events published: EdPatientArrived, EdTriageCompleted, EdBayAssigned, EdTreatmentStarted, EdVisitDispositioned (ids only). Consumed: ClinicalOrderCreated / PrescriptionIssued are handled by the receiving modules, not by ED.
- Charge events (module ED): `ED-VISIT` (visit closed, once; not charged for a patient who left before triage or being seen), `ED-RESUS` (only if a resuscitation record exists), `ED-PROC` (per procedure), `ED-OBS-HR` (per billed observation hour).
- Alert categories raised: ed_resuscitation, code_blue, ed_deterioration, ed_triage_overdue, ed_door_to_doctor, ed_retriage_due, ed_protocol_overdue, ed_consult_overdue, ed_observation_long. Timers run from `ed:check-timers` every minute and de-duplicate.
- Status machine (visit): awaiting_triage → waiting → in_treatment ⇄ observation → awaiting_disposition → dispositioned; cancelled. A visit is closed exactly once (unique key on ed_dispositions.visit_id).
- Settings keys: ed.triage_scale, ed.door_to_triage_minutes, ed.protocols. Config: config/ed.php, config/ed_protocols.php.
- Reports / KPIs: Performance page (door-to-triage, door-to-doctor, length of stay — median and 90th percentile; share seen within target; LWBS %); export report `ed.visits` (CSV / XLSX).
- Security: every route is organization- and branch-scoped (404 for others); another branch of the same organization is readable only with an active break-glass grant on the encounter. Death can be recorded only with `ed.death.declare`.
- Known limitations & deferred items: see docs/decisions/D-P12-ed-state.md.

## Tables


