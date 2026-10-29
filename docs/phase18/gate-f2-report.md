# Phase 18 — Interoperability (FHIR, HL7, DICOM): Gate F-2 report

| Check | Result |
|---|---|
| Slices S00–S12 | built |
| Whole suite | 1,255 tests: 1,253 passed on the first full run; the 2 failures (the module-enablement test used `interop` as its "not built" example) are fixed and pass. Interop tests: `tests/Feature/Interop/*`, golden FHIR files in `tests/Golden/fhir/` |
| Pint · phpstan (level 5, no baseline entries added) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; about 165 new strings — **wording needs native review** |
| MySQL | the migrations apply on a fresh MySQL database and the six-month history ran there. One defect was found only on MySQL and fixed: two index names longer than 64 characters (now named explicitly) |

## Scratch MySQL build (six months)
6 partners, 165 patients who agreed to share, 4,251 messages (3,921 sent, 8 dead-lettered, 7 set aside, none left waiting or stuck), 4,041 attempts, 180 inbound HL7 results (16 waiting for review at the end), 130 scanner worklist entries, 6,204 who-saw-what log rows of which 1,882 are refusals, 25 records shared with the sandbox national exchange.

Integrity queries on that database, all **0**: outgoing message about a patient who had not agreed; dead message without a reason; message stuck "sending"; repeated idempotency key; sent message without a time.

## Behaviour that is tested
| Rule | Status |
|---|---|
| Every FHIR route refuses a caller without a token; read-only token cannot write; patient and stale tokens refused; a client cannot choose its own scopes or hospital; hospital never taken from a header | tested (`InteropSecurityTest`) |
| Partner of one hospital never sees another hospital's records; injection strings harmless; no secrets, hashes or database ids in responses | tested |
| Consent gate: only patients who agreed; refusal and emergency override logged; override needs permission and reason | tested |
| 15 resource types map to FHIR (golden files for 8); OperationOutcome errors; paging, search, `$everything`, `$validate`, `$lookup`, `$translate` | tested |
| Patient write: strict matching, a doubtful match refused, duplicates not created | tested |
| HL7: parse/build, MLLP framing, ACK AA/AE/AR, ADT creates patient and visit, ORU parked for review (nothing written to the record), repeat control id processed once | tested |
| Queue: idempotency, retry/backoff, dead-letter with alert, replay as a new message, set aside with a reason, stuck "sending" released | tested |
| Secrets: shown once, rotation with overlap, revocation, constant-time check; webhooks: signature, replay window, nonce | tested |
| DICOM worklist from radiology orders, DICOM JSON, status update, study list | tested |
| Staff screens: every screen draws; 403 without permission; 404 for another hospital | tested (`DeskTest`) |
| Demo history and per-module users | tested (`DemoHistoryTest`, `FactoriesTest`) |
| Speed | `interop:benchmark` measures read, search and queue throughput and removes its own data |

## Not done / needs people
See D-P18: onboarding with the national authority (real adapter, profile, identifiers), each partner's technical contact and test messages, confirmation of every number and of which events go to which partner, supervisor and TLS/VPN for the HL7 listener, native Bangla review, a penetration test of the API, SMART-on-FHIR, a full FHIR validator, inbound orders, a PACS/DICOMweb server.
