# D-P18 — Interoperability (FHIR, HL7, DICOM): state and decisions

Status at the end of the build: see `docs/phase18/gate-f2-report.md`. The guideline's section 26.7 and the slice table (S00–S12) were the brief; every number is an example for the integration lead and the privacy officer to confirm (OD-18).

## Decisions
1. **FHIR is an adapter, not a second model.** One mapper per resource type (15 types) reads the owning module's records; nothing is copied. The one write is a Patient (`PatientIntakeService`, strict matching: national id or number plus date of birth; a doubtful match is refused, never merged).
2. **No database ids outside.** Resources are shown under opaque ULIDs from `integration_id_maps` (scope `local`); a partner's own number for the same record is a second row scoped to that partner, so numbers never collide.
3. **Errors are OperationOutcome**, always, with a stable `code`; validation is per partner profile (`r4` now, `bd-core` is a placeholder).
4. **Consent gate in one place** (`ExchangeGuard`): partners that need consent get only patients with an active consent of the partner's type; an emergency override needs its own permission and a reason; every read, search, refusal and override is written to an append-only log.
5. **Tokens and secrets.** Partners use Sanctum tokens with abilities `fhir.read/write`, `dicom.read/write`; a client cannot choose its own abilities or hospital. Secrets are encrypted, shown once, rotated by making a new version (the old one keeps working for the overlap), and checked in constant time.
6. **One queue.** Every outgoing and incoming message is a row with a unique (hospital, direction, idempotency key); sending is a separate process; retry with backoff; dead-letter with an alert; a repeat is a new message pointing at the old.
7. **Inbound HL7 never touches the clinical record on its own.** ADT creates the patient and visit (through the usual services); ORU results wait in `hl7_inbound_results` for a person. Duplicates (same control id) are acknowledged and processed once. Replies are always an ACK (AA / AE / AR).
8. **DICOM** here means the worklist (made from radiology orders; accession number ties images back), DICOM-JSON for it, and a QIDO-like study list. There is no image store and no C-FIND server.
9. **Webhooks**: HMAC over `timestamp.body`, replay window and nonce list; the signed notice is queued like any other message.
10. **Events** go out through routes (`integration_routes`: event, partner, format, conditions, priority); a route to a partner who has not agreed is dropped and counted, not sent.
11. **National exchange** goes through `NationalExchangeGateway`: null (default; queues and fails honestly), sandbox (keeps records for a day, goes nowhere).
12. **Terminology** is read from the Code System Registry (`$lookup`, `$translate`); no second code list.

## Deferred / needs people (not code)
- **Onboarding:** the national authority / DGHS (real national adapter, client credentials, implementation guide, real `bd-core` profile and identifier systems); each partner's technical contact, endpoints and test messages.
- **Confirmations:** every number in `config/interop.php`; which events go to which partner; the consent wording that covers sharing; retention periods for message content and the who-saw-what log.
- **Operations:** a supervisor entry for `hl7:listen` and `integration:work --loop`; network rules/TLS for the listener (MLLP has no encryption of its own — use a VPN or a TLS tunnel); monitoring of the dead-letter count.
- **Not built:** SMART-on-FHIR / OAuth2 for apps; a full FHIR validator (only the checks in `FhirValidator`); FHIR writes other than Patient; inbound orders (ORM/OMG); a PACS or DICOMweb server; HL7 batch files; per-patient partner numbers entered in bulk.
- **Next to watch:** the dashboard — messages waiting or retrying, the dead-letter list, results waiting for a person, patients not shared for lack of consent, overrides.
