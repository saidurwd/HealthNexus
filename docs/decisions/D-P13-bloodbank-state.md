# D-P13 — Blood Bank: state at the end of the Phase 13 build

Date: 2026-11-01. Slices S01–S13 of guideline §26.2 are built and tested. This is not a clinical sign-off.

## What is built
Configuration and masters, donor registry with consent and editable eligibility rules, deferral, donation with bag identity and a unique number, donor reactions, component separation, quarantine, lab-driven infectious-disease screening and grouping (positive → blocked; discordant → held), inventory with storage locations, temperature logging with excursion handling, expiry, FEFO and low stock, blood requests with priority, patient sample with the two-sample rule and wrong-sample rejection, cross-match and reservation (incompatible bags cannot be reserved; reservations lapse), issue (once, never expired), returns, discards, bedside two-person check, transfusion record with observations, reactions and incidents, recall and lookback in both directions, emergency release, massive transfusion protocol, utilization and wastage report, dashboard, the real `BloodRequestGateway`, demo history and test users.

## Deviations from the guideline (decided here)
- **Temperature readings are not Observations.** The Observation Service requires a patient; a fridge has none. Readings go to `blood_temperature_logs` and breaches to the Alert Service. (Override 5, partly.)
- **The donor's group and the patient's samples are typed at the blood-bank bench**, not read from the LIS: donor grouping IS read from the lab order; the patient sample grouping on a request is entered by the technician (`lab_result_id` not yet linked). A patient-sample LIS order is a follow-up.
- **A donor is a patient row** (MPI), so the screening order can be a normal clinical order on a "blood donation" encounter.

## Needs the blood-bank medical officer / owner before clinical use (OD-13, P-24)
National screening panel and the exact test names; donor eligibility limits and deferral periods; component shelf lives and storage ranges; cross-match method per hospital; reservation hours; return rule (minutes out, temperature); reaction types; massive-transfusion pack ratio; low-stock levels; prices for BLD-* codes (demo prices only).

## Deferred
- ISBT-128 donation numbers (needs the licensed facility identification number; a hospital series is used).
- Patient-sample LIS orders and linking `lab_result_id` on samples.
- Antibody screening / identification, direct antiglobulin test, special requirements (irradiated, leuco-reduced, CMV-safe) as request attributes.
- Blood-bank API endpoints (`/api/v1/blood-bank`), FHIR (BiologicallyDerivedProduct) mapping, device (fridge logger) ingestion endpoint — readings are entered by hand or through the service.
- Apheresis platelets, autologous and directed donations, external supplier receipts (`is_external` exists on units but has no receiving screen).
- Hand-held scanner support beyond text fields; wristband/bag label printing through the Document Service.
- Insurance hand-off for blood charges (Phase 14) and consolidated stock *view* posting (D-11).
- Real-user, scanner-hardware and load testing; native review of Bangla wording.
