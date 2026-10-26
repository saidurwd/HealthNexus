# P13-S00 — Inspect & Map: Blood Bank and Transfusion

Read-only report (guideline 26.2). Built on the owner's standing instruction "go with your recommendations" (as for Phase 12): the detailed
`Phase 13 — Blood Bank …` spec (100 sections) is not in the repository, so the clinical rules below are **configurable examples** in
`config/blood.php` (overridable by settings `blood.*`) that the blood-bank medical officer must approve before use (P-24; national blood policy to be verified).

## Reused, not rebuilt
| Need | Owner | Note |
|---|---|---|
| Donor as a person | MPI `patients` | A donor is a patient row (`PatientService::createPatient`) plus a `blood_donors` row; no second people table |
| Screening and grouping tests | Laboratory (Phase 5) | Ordered as a clinical order on a "blood donation" encounter → `LabOrder`; results are read from `lab_results` (`lab_result_id` kept), never retyped (override 3) |
| Donation / unit numbers | `NumberSeriesService` | series `blood.donor`, `blood.donation`, `blood.unit`, `blood.request`, `blood.issue` |
| Alerts (excursion, reaction, expiry, unit blocked) | `AlertService` | |
| Transfusion vitals | `ObservationService` | context `transfusion`; the patient exists, so no local copy |
| Charges | `ChargeEventPublisher` (module `BLD`) | codes BLD-PRBC, BLD-FFP, BLD-PLT, BLD-CRYO, BLD-WB, BLD-XM (crossmatch) |
| Hooks | `BloodRequestGateway` (null adapter) | real adapter bound in S12; ED/ICU/OT/IPD call only the interface |
| Public ids, module flag, permissions | `HasPublicId`, `config/modules.php` key `bloodbank` (tables `blood_*`, permissions `bloodbank.*`) | |

## Decisions made (see D-P13)
- Temperature readings from fridges are **not** patient observations; the Observation Service requires a patient. They go to `blood_temperature_logs`, and breaches to the Alert Service. (Deviation from override 5, recorded.)
- Blood units live in `blood_units` with an append-only `blood_unit_events` trail; they never touch the Stock Ledger (guideline 12.1). Quantity summaries are read from `blood_units`.
- Bedside verification is a two-person check with wristband and bag scan; an override needs a reason and creates an incident.

## Questions for the owner (not blocking)
1. National screening panel and donor-eligibility limits (config examples: HIV, HBsAg, HCV, syphilis, malaria; age 18–60, weight ≥ 50 kg, Hb ≥ 12.5 g/dL).
2. Does Premier Health Center collect blood (donor side) or only store and transfuse (hospital transfusion service)? The donor side is switchable.
3. ISBT-128 donation numbers: licensed facility identification number needed; until then a hospital series is used.
