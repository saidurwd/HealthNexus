# P18-S00 — Inspect & Map: Interoperability (FHIR, HL7 v2, DICOM)

Read-only report (guideline 26.7). The 76-section `Phase 18 — Interoperability` spec is not in the repository, and neither is the Bangladesh Core FHIR IG (DGHS). So: profile rules are a small, explicit, per-partner list in `config/interop.php` (base R4 required elements + hospital-chosen extras) and **must be replaced by the real IG profiles when DGHS supplies them** (OD-18); partner onboarding with the national exchange is a null adapter plus a test client (guideline override 6).

## What already exists (reused, not copied)
| Need | Owner | Note |
|---|---|---|
| Patient create / update / duplicate scoring | `PatientService` (Phase 1) | Inbound ADT and FHIR Patient go through it; nothing writes `patients` directly |
| Terminology | Code System Registry: `code_systems`, `code_concepts`, `code_maps` | The FHIR `$lookup` and `$translate` operations read it. **No second terminology store** |
| Domain events | `docs/events.md` (Laravel events) | Integration subscribes to a chosen few; payload carries ids |
| Consent | `patient_consents` (Phase 1, versioned) | Type `data_sharing` (and per-partner types) gate exchange |
| Radiology order, accession number, PACS servers | Phase 6 | The worklist entry is made from the radiology order; the accession number is the key |
| Per-module FHIR "readiness" mappers | `app/Services/*/Fhir/*`, `app/Support/Fhir/PatientFhirMapper` | Use numeric ids and are not profile-checked. They are **left alone**; the server uses new mappers in `app/Interop/Mappers/` with stable public ids and golden tests |
| National exchange | `NationalExchangeGateway` + `NullNationalExchangeGateway` (Phase 0) | A sandbox "test client" adapter is added; the real one needs DGHS |
| Alerts, audit, settings, numbers | `AlertService`, `AuditLogger`, `SettingsService` | Dead-lettered messages raise an alert |
| Sanctum | installed | Partner client tokens with abilities `fhir.read`, `fhir.write` (guideline override 5; SMART-on-FHIR/OAuth2 is a recorded deferral) |

## Gaps this phase fills
- Many records have no public id (providers, vitals, diagnoses, procedures, problems, organizations): a table `integration_id_maps` gives each an opaque ULID we expose, and holds each partner's own id for the same thing, both ways.
- No external-system registry, queue, retry or dead-letter: `integration_partners / credentials / endpoints / messages / attempts`.
- No HL7 v2, no DICOM worklist, no signed webhooks: built here as services plus long-running commands (`hl7:listen`, `integration:work`).

## Decisions (see D-P18)
- FHIR is an adapter over domain services; no FHIR database.
- Errors on `/api/v1/fhir/*` are `OperationOutcome`; the HL7 listener answers ACK / NACK (AA / AE / AR).
- Every message is idempotent on (partner, direction, key), retried with back-off, dead-lettered, and replayable by a person.
- A partner needing patient consent gets nothing without it; the denial is logged. Emergency override needs a reason and is logged.
- Inbound results (ORU) from outside are parked in a review queue; they never write into the clinical record by themselves.

## Questions for the owner (not blocking)
1. Which partners come first (national HIE, a reference lab, a PACS, an insurer)? Each is a configured partner row plus, at most, one adapter class.
2. The DGHS profile list and code bindings; test credentials for the national sandbox.
3. Which events must be pushed, to whom, in what format.
4. The consent wording for sharing, and who may override in an emergency.
