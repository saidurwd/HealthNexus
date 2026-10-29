# Interoperability — Module Charter

_Updated in every slice (guideline 7.4). Phase 18 slices S00–S12 are built; every number (tries, waits, secret overlap, token life, retention, page size) is an example awaiting the integration lead and the privacy officer (OD-18). The Bangladesh national profile and the national exchange are placeholders until onboarding with the national authority._

- Phase / owner: 18 / **owner to be named by the hospital** (integration lead and the privacy officer)
- Status: built · switchable per organization / hospital (`interop`, depends on core)
- Purpose: let other systems read and send what the hospital is allowed to share — through a FHIR API, HL7 v2 messages, DICOM worklists, signed webhooks and the national exchange — and let staff see, retry and audit every exchange.
- Permission prefixes: `interoperability` (21 permissions). Table prefixes: `integration_`, `hl7_`, `dicom_`. Staff screens `/admin/interop/*`; API `/api/v1/fhir/*`, `/api/v1/dicom/*`, `/api/v1/interoperability/webhooks/{partner}`; HL7 over MLLP by `hl7:listen`.
- Owns: partners, their endpoints, secrets (versions with an overlap), client tokens, routing rules, the message queue with attempts, the id map (our opaque ids and the partners' numbers), the nonce list, the who-saw-what log, parked inbound HL7 results, the scanner worklist.
- Does NOT own: patients, encounters, results, orders, invoices (FHIR is an adapter over their services: reads go through the mappers, the one write — a Patient — goes through `PatientService`/`PatientIntakeService`); consents (Phase 1 `patient_consents`); the code registry (Code System Registry, read only here).
- Security: every API route needs a partner token with the right ability (`fhir.read/write`, `dicom.read/write`); the hospital comes from the partner, never from a header; a partner that needs consent gets only patients who agreed (type per partner); an emergency override needs `interoperability.consent.override` and a reason and is logged; the log is append-only; secrets are encrypted, shown once, rotated with an overlap; webhooks are HMAC-signed over `timestamp.body` with a replay window and nonce list; responses never show database ids, hashes or secrets.
- Inbound HL7: the sender is identified by MSH-3; an ADT creates the patient and visit through the usual services; an ORU result is only **parked** for a person to review — nothing is written into the clinical record by itself.
- Delivery: one queue, one idempotency key per direction; `integration:work` sends with retry/backoff, then dead-letters with an alert; a person can send again (a new message that points to the old one) or set aside with a reason.
- Hooks: `integration:work` every minute (or `--loop` under a supervisor), `interop:check` every five minutes, `hl7:listen` as a supervised process.
- Gateways: `NationalExchangeGateway` (null by default; sandbox for development and the demo).
- Settings keys: `interop.*` override `config/interop.php`.
- Reports: interoperability dashboard (partners, waiting/retrying, dead, results to review, refused for lack of consent, overrides, reads, delivery time), message list, access log.
- Known limitations & deferred items: see docs/decisions/D-P18-interop-state.md.

## Tables


