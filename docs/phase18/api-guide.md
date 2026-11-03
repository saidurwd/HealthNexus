# Interoperability — guide for partners and for the hospital's IT

Base URL: `https://<hospital>/api/v1`. All answers are JSON (`application/fhir+json` for FHIR). Every problem is an `OperationOutcome` with a stable code. Nothing here is a clinical decision aid.

## 1. Get connected (hospital side)
1. **Connections → Partners → Add a partner.** Choose the kind (FHIR client, HL7 peer, scanner, webhook, national exchange), whether the patient must have agreed (and to what), and save. It stays in draft until you switch it on.
2. **Secrets / tokens.** Make a token (shown once) or a signing secret. To change a secret, press *Rotate*: the old one keeps working for the overlap period (default 24 h).
3. **Rules.** Choose what is sent automatically, to which partner, in which format, and under which condition.
4. Test with *Send one patient now* and read the result on **Messages between systems**.

## 2. FHIR (token needs `fhir.read`; writing needs `fhir.write`)
```sh
TOKEN=...   # shown once when the token was made
curl -H "Authorization: Bearer $TOKEN" https://hospital.example/api/v1/fhir/metadata
curl -H "Authorization: Bearer $TOKEN" "https://hospital.example/api/v1/fhir/Patient?identifier=urn:healthnexus:mrn|P-00123"
curl -H "Authorization: Bearer $TOKEN" https://hospital.example/api/v1/fhir/Patient/<id>/\$everything
curl -H "Authorization: Bearer $TOKEN" "https://hospital.example/api/v1/fhir/Observation?patient=<id>&_count=20&page=2"
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/fhir+json" -d @patient.json https://hospital.example/api/v1/fhir/Patient
curl -X POST -H "Authorization: Bearer $TOKEN" -d @resource.json https://hospital.example/api/v1/fhir/\$validate
curl -H "Authorization: Bearer $TOKEN" "https://hospital.example/api/v1/fhir/CodeSystem/\$lookup?system=http://hl7.org/fhir/sid/icd-10&code=J18.9"
```
Types: Patient, Encounter, Observation, DiagnosticReport, Condition, AllergyIntolerance, MedicationRequest, ServiceRequest, Appointment, Coverage, Organization, ImagingStudy, Invoice. A partner that needs consent sees only patients who agreed; others get `403` with code `forbidden`.

## 3. HL7 v2 over MLLP
Run the listener under a supervisor and put it behind a VPN or TLS tunnel (MLLP itself is not encrypted):
```ini
[program:hl7-listen]
command=php /var/www/healthnexus/artisan hl7:listen --host=0.0.0.0 --port=2575
autostart=true
autorestart=true

[program:integration-work]
command=php /var/www/healthnexus/artisan integration:work --loop --sleep=5
autostart=true
autorestart=true
```
Accepted: ADT^A01/A04/A08 (patient and visit) and ORU^R01 (results are parked for review). The sender is identified by MSH-3 and must be a switched-on partner. Answer: ACK with AA (accepted), AE (fix and send again) or AR (rejected).

## 4. DICOM worklist (token needs `dicom.read`; status needs `dicom.write`)
`GET /dicom/worklist?modality=CT&ae=HNWORKLIST&date=2026-10-28&PatientID=<patient number>` (DICOM JSON) · `GET /dicom/studies?PatientID=<patient number>&AccessionNumber=...&ModalitiesInStudy=CT` (any of the filters) · `PUT /dicom/worklist/{accession}/status` with `{"status":"in_progress"}`.

## 5. Webhooks to us
`POST /interoperability/webhooks/{partner-code}` with headers `X-HN-Timestamp` (unix seconds, within 5 minutes), `X-HN-Delivery` (unique id) and `X-HN-Signature: sha256=` + HMAC-SHA256 of `"{timestamp}.{body}"` with the shared secret. A repeated delivery id is accepted once.

## 6. Commands
`integration:work [--loop]` · `hl7:listen` · `interop:check` · `interop:benchmark --n=500` (writes and removes its own test data).
