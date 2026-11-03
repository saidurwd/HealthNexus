# P12-S00 — Inspect & Map: Emergency Department

Read-only report (guideline 26.1). **Status: waiting for approval, and for the detailed Phase 12 specification.** The 80-section spec
`Phase 12 — Emergency Department …` is not in the repository, only the guideline's slice table; the clinical content of triage,
resuscitation, stroke/ACS/sepsis/trauma protocols, dispositions and KPIs must come from it (or from your clinical champions, OD-12). I will not invent it.

## What already exists and will be reused (no second copy)
| ED need | Existing owner / contract | Notes |
|---|---|---|
| Unknown / temporary patient | `patients.is_temporary`, `patients.is_unknown`, `PatientService::createPatient`, merge via `PatientService::mergePatients` and the duplicate-review queue | Guideline override 7. The unknown-patient number series `UNKNOWN-{YYYY}-{6}` (8.3) goes through `NumberSeriesService`. No ED patient table |
| Rapid registration, wristband, scan | `WristbandDocument` + Code 128, `ScanResolver`, global search | Wristband for an unknown patient needs the temporary MRN |
| Encounter | Phase 3 `encounters` (+ `encounter_type`) | ED visit = an encounter plus an `ed_visits` context row; no duplicate encounter |
| Vitals, triage measurements, monitoring | `ObservationService` (H-03), catalogue `config/observations.php`, context `ed` already allowed | `ed_*` tables keep only context (who, zone, review status) — override 1 |
| Critical alerts (deterioration, door-to-doctor breach, consult SLA) | `AlertService` (H-04), policies `critical` / `warning`, `AlertReport` | Re-triage timers and SLA breaches use `next_escalation_at` |
| Billing | `ChargeEventPublisher` + `ChargeEventData` (module code `ED`), service codes in `billing_items` | Whole-number quantities only |
| Lab / radiology / pharmacy orders | Phase 5/6/7 registration services (clinical orders → LabOrder / RadiologyOrder / PharmacyOrder) | Results return through the existing encounter views |
| Blood requests, emergency release | `BloodRequestGateway` (null adapter, config/integrations.php) | Override 4; real adapter in Phase 13 |
| Dispositions | IPD admission request (`ipd`), ICU admission request (`icu`), OT surgery request (`ot`), discharge, death documentation rules of Phases 8/10 | Each disposition must close the ED visit exactly once |
| Code Blue | `IcuCriticalEvent` / `ICUCriticalAlert*` notifications exist for ICU | An ED "Code Blue" raises a `critical` clinical alert to a role list; link to the ICU event model is a design question |
| Numbers | `NumberSeriesService` (`EDV-{YYYY}-{8}`, `UNKNOWN-…`) | |
| Public IDs | `HasPublicId`; `PublicIdCoverageTest` fails a patient-linked model without it | New `ed_*` tables get `public_id` in their first migration |
| Permissions | `emergency.view / create / update / triage` already seeded (legacy prefix, registered as alias); new ones use the `ed` prefix | |
| Module switch | `config/modules.php` key `ed` (planned → set `built` when S01 lands), routes behind `module:ed` | |
| Board | T6 board template; the first Vue "island" (guideline D-15) — Vue is not installed yet | Polling endpoint every 5–10 s; needs a front-end decision (see questions) |

## Gaps to build (slices S01–S12 of guideline 26.1)
Zones and bays, triage scale setting, ED visit and team, triage + re-triage timers, tracking board, resuscitation timeline, protocol timers
(stroke, ACS, sepsis, trauma, mass-casualty), consultations with SLA, observation unit, dispositions (ten kinds), ED dashboards and KPIs
(door-to-triage, door-to-doctor, LOS, LWBS %), hardening.

## Questions I need answered before S01
1. **Please provide the Phase 12 detailed spec** (`Phase 12 — Emergency Department …`) so sections 3–5, 9–11 etc. are built as written.
2. **Triage scale (OD-12):** ESI (5 levels), Manchester, CTAS, or a local 5-level scale? Who approves the colour/time targets per level?
3. **Vue island:** may I add Vue 3 (+ the Vite plugin) for the tracking board only, as the guideline describes, or should the first board be a Blade page with polling?
4. **Pilot reality check:** does Premier Health Center run an emergency department at go-live? If not, the module can stay switched off for the pilot and the order of work could change.
