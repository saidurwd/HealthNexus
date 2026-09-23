# Phase 18 — Interoperability / FHIR / HL7 / DICOM
## Modular Architecture Specification & AI Implementation Prompt

**Document Version:** 1.0  
**Target Architecture:** Existing Laravel Modular Monolith  
**PHP:** 8.4+  
**Database:** MySQL/PostgreSQL  
**Cache/Queue:** Redis  
**UI:** Blade + AdminLTE  
**API:** REST `/api/v1`  
**Primary Standards:** HL7 FHIR, HL7 v2, DICOM/DICOMweb  
**Implementation Principle:** Standards-based integration without duplicating clinical or financial domain ownership.

---

# 1. Purpose

Phase 18 establishes the Hospital Management System's enterprise **Interoperability Layer**.

It provides a controlled framework through which the HMS can exchange information with:

- External hospitals
- Laboratories
- Radiology/PACS systems
- Pharmacy systems
- Blood banks
- ERP/accounting systems
- Insurance systems
- Government health systems
- Medical devices
- Patient applications
- Third-party clinical systems
- Health information exchanges
- Future AI/analytics platforms

The module provides:

> **Internal HMS Domain → Interoperability Layer → External System**

and:

> **External System → Interoperability Layer → HMS Domain**

The interoperability layer must not become the owner of patient, clinical, laboratory, radiology, pharmacy, billing, admission, or other business records.

---

# 2. Architectural Principle

The existing modular architecture is **already implemented**.

The AI coding agent MUST:

1. Inspect the existing repository.
2. Identify the existing modular structure.
3. Reuse existing modules and services.
4. Reuse existing models, repositories, services, policies, events, jobs, queues and APIs.
5. NOT create a second modular framework.
6. NOT restructure the existing application unnecessarily.
7. NOT duplicate existing domain functionality.

Phase 18 is an **integration and translation layer**.

### Core principle

```text
HMS Domain Modules
       │
       ▼
Domain Events / Integration Contracts
       │
       ▼
Interoperability Layer
       │
       ├── FHIR
       ├── HL7 v2
       ├── DICOM / DICOMweb
       ├── REST / JSON
       ├── SOAP Adapter
       ├── CSV/File Adapter
       └── External Vendor APIs
       │
       ▼
External Systems
```

---

# 3. Existing Modules to Reuse

Phase 18 must integrate with the existing:

- Phase 0 — Foundation
- Phase 1 — Patient / MPI
- Phase 2 — Appointment
- Phase 3 — Clinical Encounter / EMR
- Phase 4 — Billing & Revenue
- Phase 5 — LIS
- Phase 6 — Radiology / PACS
- Phase 7 — Pharmacy
- Phase 8 — IPD / Admission / Bed
- Phase 9 — Nursing
- Phase 10 — OT / Surgery
- Phase 11 — ICU
- Phase 12 — Emergency
- Phase 13 — Blood Bank
- Phase 14 — Insurance
- Phase 15 — Inventory / Procurement
- Phase 16 — HR / Workforce
- Phase 17 — Patient Portal / Mobile / CRM

Do not create duplicate versions of:

- Patient
- Patient identity
- MRN
- Encounter
- Appointment
- Diagnosis
- Prescription
- Medication
- Laboratory result
- Radiology result
- Billing
- Invoice
- Payment
- Admission
- Bed
- Surgery
- ICU record
- Emergency record
- Blood product
- Insurance claim
- User
- Organization
- Hospital
- Department

---

# 4. Scope

Phase 18 includes:

1. Interoperability gateway
2. Integration configuration
3. External system registry
4. FHIR support
5. FHIR REST API
6. FHIR resource mapping
7. FHIR profiles
8. FHIR validation
9. FHIR search
10. FHIR bundles
11. FHIR transactions
12. FHIR subscriptions foundation
13. HL7 v2 messaging
14. HL7 message parsing
15. HL7 message generation
16. HL7 ACK handling
17. HL7 message validation
18. HL7 routing
19. DICOM integration
20. DICOM metadata handling
21. DICOM Modality Worklist integration
22. DICOMweb integration foundation
23. External API integration
24. Webhooks
25. Inbound/outbound message queues
26. Transformation/mapping engine
27. Terminology mapping
28. Identifier mapping
29. Integration monitoring
30. Retry/dead-letter handling
31. Idempotency
32. Message correlation
33. Audit
34. Security
35. Integration credentials
36. API authentication
37. Rate limiting
38. Data minimization
39. Consent-aware exchange
40. Integration dashboards
41. Operational reports
42. AI-assisted interoperability features

---

# 5. Non-Goals

Phase 18 must NOT become:

- A second EMR
- A second Patient/MPI system
- A second LIS
- A second RIS
- A second PACS
- A second pharmacy system
- A second billing system
- A second insurance system
- A second admission system
- A second blood bank
- A second inventory system
- An independent clinical decision-support system
- An autonomous clinical AI system

It transports, translates, validates, routes and audits information owned by other modules.

---

# 6. Interoperability Architecture

Recommended architecture:

```text
                    ┌─────────────────────────┐
                    │ Existing HMS Modules    │
                    │                         │
                    │ Patient                 │
                    │ Encounter               │
                    │ LIS                     │
                    │ Radiology               │
                    │ Pharmacy                │
                    │ Billing                 │
                    │ Admission               │
                    │ Surgery                 │
                    │ ICU                     │
                    │ Emergency               │
                    │ Blood Bank              │
                    └───────────┬─────────────┘
                                │
                         Domain Events
                                │
                                ▼
                    ┌─────────────────────────┐
                    │ Interoperability Core   │
                    ├─────────────────────────┤
                    │ Integration Registry    │
                    │ Routing Engine           │
                    │ Mapping Engine           │
                    │ Terminology Engine       │
                    │ Message Manager          │
                    │ Validation Engine        │
                    │ Audit                    │
                    │ Retry/DLQ                │
                    └───────────┬─────────────┘
                                │
              ┌─────────────────┼─────────────────┐
              │                 │                 │
              ▼                 ▼                 ▼
            FHIR             HL7 v2            DICOM
              │                 │                 │
              ▼                 ▼                 ▼
         External APIs      Hospital/Lab       PACS/
         HIE/Govt           Interfaces         Modalities
```

---

# 7. External System Registry

Every external integration must be registered.

Example systems:

- External Laboratory
- External Hospital
- PACS
- Radiology Modality
- Insurance Provider
- Government Health Platform
- ERP
- Pharmacy
- Medical Device
- Payment Gateway
- Patient Application

Suggested fields:

```text
id
organization_id
hospital_id
name
code
system_type
vendor
version
environment
base_url
endpoint
authentication_type
status
direction
data_format
standard
timeout_seconds
retry_policy
rate_limit
certificate_reference
credential_reference
created_by
updated_by
created_at
updated_at
```

Never store plaintext secrets in normal application tables.

---

# 8. Integration Types

Support:

### Synchronous

```text
HMS → External API → Response
```

### Asynchronous

```text
HMS
 ↓
Queue
 ↓
Integration Worker
 ↓
External System
```

### Event-driven

```text
Domain Event
 ↓
Integration Event
 ↓
Route
 ↓
Adapter
```

### File-based

Support controlled:

- CSV
- JSON
- XML
- XML-based HL7
- Secure file exchange

File-based integrations must use secure storage and validation.

---

# 9. FHIR Implementation

FHIR should be the primary modern interoperability standard.

Target compatibility should be configurable by deployment and external system.

The implementation should initially target a modern FHIR release such as **FHIR R4**, while keeping the architecture extensible for later versions.

---

# 10. FHIR Resources

Initial resource support:

### Patient

```text
Patient
```

Mapped from Phase 1 MPI.

### Organization

```text
Organization
```

Mapped from Phase 0.

### Location

```text
Location
```

Mapped from hospital/branch/department/bed/location domains.

### Practitioner

Mapped from existing provider/user structures.

### PractitionerRole

Represents:

- Practitioner
- Organization
- Department
- Specialty
- Role

### Encounter

Mapped from Phase 3.

### Appointment

Mapped from Phase 2.

### Condition

Mapped from diagnosis/problem list.

### Observation

Mapped from:

- Vitals
- Lab results
- Clinical observations
- Other structured observations

### Procedure

Mapped from:

- Clinical procedures
- Surgery
- OT procedures

### ServiceRequest

Mapped from:

- Clinical orders
- Laboratory orders
- Radiology orders
- Other service requests

### DiagnosticReport

Mapped from:

- LIS
- Radiology

### Specimen

Mapped from LIS/Blood Bank specimen workflows.

### Medication

Mapped from Phase 7 medication master.

### MedicationRequest

Mapped from prescriptions.

### MedicationDispense

Mapped from pharmacy dispensing.

### MedicationAdministration

Mapped from medication administration where available.

### AllergyIntolerance

Mapped from patient allergy data.

### DocumentReference

Mapped from the existing file/document architecture.

### Consent

Mapped from existing consent structures.

### ImagingStudy

Mapped from Radiology/PACS.

### Device

Mapped from applicable device records.

### Coverage

Mapped from insurance.

### Claim

Mapped from Phase 14 claims.

### Invoice

Mapped where appropriate to billing.

### PaymentNotice / payment-related resources

Used according to the selected FHIR implementation profile.

### CarePlan

Mapped from care planning where supported.

### Task

Used for workflow/integration tasks.

---

# 11. FHIR Resource Mapping

Create an explicit mapping architecture.

Example:

```text
FHIR Patient
      ↓
PatientResourceMapper
      ↓
Patient Domain Model
```

and:

```text
Patient Domain Model
      ↓
PatientResourceMapper
      ↓
FHIR Patient
```

Do not embed complex transformation logic directly inside controllers.

Use dedicated mapper/transformer classes.

Example:

```text
FhirPatientMapper
FhirEncounterMapper
FhirObservationMapper
FhirConditionMapper
FhirDiagnosticReportMapper
FhirMedicationRequestMapper
FhirMedicationDispenseMapper
FhirProcedureMapper
FhirImagingStudyMapper
```

---

# 12. FHIR API

Expose versioned APIs:

```text
/api/v1/fhir
```

Examples:

```text
GET    /api/v1/fhir/Patient/{id}
POST   /api/v1/fhir/Patient
PUT    /api/v1/fhir/Patient/{id}

GET    /api/v1/fhir/Encounter/{id}
GET    /api/v1/fhir/Observation/{id}
GET    /api/v1/fhir/DiagnosticReport/{id}
GET    /api/v1/fhir/MedicationRequest/{id}
GET    /api/v1/fhir/MedicationDispense/{id}
GET    /api/v1/fhir/ImagingStudy/{id}
```

FHIR search support should be introduced incrementally.

Example:

```text
GET /api/v1/fhir/Patient?identifier=...
GET /api/v1/fhir/Patient?name=...
GET /api/v1/fhir/Patient?birthdate=...
GET /api/v1/fhir/Encounter?patient=...
```

Search parameters must be explicitly whitelisted.

Never expose arbitrary database filtering.

---

# 13. FHIR Bundles

Support:

- Collection
- Searchset
- Transaction
- Batch
- History where appropriate

Example:

```text
Bundle
 ├── Patient
 ├── Encounter
 ├── Practitioner
 ├── Condition
 ├── Observation
 └── MedicationRequest
```

Transaction processing must be:

- Atomic where required
- Idempotent
- Validated
- Audited
- Permission-controlled

---

# 14. FHIR Validation

Every inbound FHIR resource should pass:

1. JSON/XML parsing
2. Resource type validation
3. Required-field validation
4. Profile validation where configured
5. Identifier validation
6. Terminology validation where applicable
7. Organization scope validation
8. Authorization
9. Business validation
10. Duplicate/idempotency check

Invalid resources must not silently enter clinical data.

---

# 15. HL7 v2

Implement HL7 v2 integration architecture.

Initial message families:

### ADT

Examples:

```text
ADT^A01
ADT^A02
ADT^A03
ADT^A04
ADT^A05
ADT^A08
ADT^A11
ADT^A12
ADT^A13
```

Used for:

- Admission
- Transfer
- Discharge
- Registration
- Patient update
- Cancellation

### ORM

Order messages.

Examples:

```text
ORM^O01
```

Used for:

- Laboratory orders
- Radiology orders
- Other clinical service orders

### ORU

Result messages.

Examples:

```text
ORU^R01
```

Used for:

- Laboratory results
- Diagnostic results

### SIU

Scheduling messages.

Used for appointment/scheduling interoperability.

### MDM

Document/message exchange where appropriate.

---

# 16. HL7 Message Processing

Pipeline:

```text
Receive Message
      ↓
Authenticate
      ↓
Validate Envelope
      ↓
Parse
      ↓
Identify Message Type
      ↓
Validate Segments
      ↓
Resolve Identifiers
      ↓
Transform
      ↓
Business Validation
      ↓
Route
      ↓
Persist/Publish
      ↓
ACK
```

---

# 17. HL7 ACK

Support appropriate acknowledgment handling.

Example:

```text
Inbound HL7
      ↓
Process
      ↓
ACK
```

Track:

- Message ID
- Control ID
- Message type
- Sending application
- Receiving application
- Timestamp
- ACK status
- Error code
- Error description

---

# 18. HL7 Message Storage

Do not rely solely on raw message storage.

Maintain:

```text
integration_messages
integration_message_payloads
integration_message_events
integration_message_errors
integration_acknowledgements
```

Store:

- Direction
- Standard
- Message type
- External system
- Correlation ID
- Message ID
- Status
- Received/sent timestamp
- Processing timestamp
- Error information
- Retry count
- Hash/checksum
- Payload reference

Sensitive payload retention must be configurable.

---

# 19. DICOM Integration

Phase 18 provides the interoperability foundation for DICOM.

Phase 6 remains the owner of Radiology/RIS/PACS workflows.

DICOM responsibilities include:

- Modality integration
- DICOM metadata
- Worklist integration
- Study identifiers
- Series identifiers
- Instance identifiers
- PACS routing
- DICOMweb integration
- External image references
- Study status synchronization

---

# 20. DICOM Modality Worklist

Flow:

```text
Radiology Order
      ↓
Scheduled Examination
      ↓
DICOM Modality Worklist
      ↓
Modality
      ↓
Performed Study
      ↓
PACS
      ↓
Radiology/RIS
```

The interoperability layer should provide an adapter/interface for modality worklist integration.

Do not build a second Radiology scheduling engine.

---

# 21. DICOMweb

Provide integration interfaces for:

```text
QIDO-RS
WADO-RS
STOW-RS
```

Use them when supported by the connected PACS.

Example:

```text
HMS
 ↓
DICOMweb Gateway
 ↓
PACS
```

Images must remain in the PACS/image repository.

Do not store large medical image binaries directly in ordinary HMS relational tables.

---

# 22. DICOM Identifiers

Support tracking of:

- Patient ID
- Accession Number
- Study Instance UID
- Series Instance UID
- SOP Instance UID
- Modality
- Study Date
- Study Description

Identifier mapping must be auditable.

---

# 23. Terminology Service

Interoperability requires controlled terminology mapping.

Support configurable mappings between:

- Local diagnosis codes
- ICD-10
- ICD-11
- SNOMED CT
- LOINC
- RxNorm or applicable medication terminology
- Local laboratory codes
- Local radiology codes
- Local procedure codes
- Blood component codes
- Local department/specialty codes

The system must not automatically invent mappings.

---

# 24. Terminology Mapping Model

Example:

```text
Local Code
    ↓
Mapping
    ↓
External Code System
    ↓
External Code
```

Suggested fields:

```text
id
organization_id
source_system
source_code
source_display
target_system
target_code
target_display
mapping_type
confidence
status
effective_from
effective_to
created_by
approved_by
created_at
updated_at
```

Possible mapping types:

```text
Equivalent
Narrower
Broader
Related
No Match
Pending Review
```

Mappings affecting clinical meaning should support approval.

---

# 25. Identifier Mapping

External systems frequently use different identifiers.

Support mapping for:

- Patient MRN
- External patient ID
- Encounter number
- Accession number
- Order number
- Prescription number
- Claim number
- Provider identifier
- Organization identifier

Example:

```text
HMS Patient ID
      ↕
External Hospital Patient ID
```

Never overwrite the HMS master identifier.

---

# 26. Integration Routing Engine

The routing engine determines:

```text
Message/Event
      ↓
Source
      ↓
Message Type
      ↓
Destination
      ↓
Transformation
      ↓
Adapter
```

Example:

```text
Lab Result Finalized
       ↓
ORU-R01
       ↓
External Hospital
```

Another example:

```text
Patient Registered
       ↓
FHIR Patient
       ↓
Government HIE
```

---

# 27. Adapter Pattern

Use interfaces such as:

```php
interface InteroperabilityAdapterInterface
{
    public function send(IntegrationMessage $message): IntegrationResult;
}
```

Possible adapters:

```text
FhirAdapter
Hl7V2Adapter
DicomAdapter
DicomWebAdapter
RestApiAdapter
SoapAdapter
WebhookAdapter
FileExchangeAdapter
```

Vendor-specific implementations must sit behind adapters.

Do not scatter vendor-specific code throughout clinical modules.

---

# 28. Integration Message Lifecycle

Recommended:

```text
Created
Queued
Processing
Sent
Acknowledged
Completed
Failed
Retrying
Dead Letter
Cancelled
```

Inbound:

```text
Received
Validated
Parsed
Mapped
Processed
Completed
Failed
Dead Letter
```

---

# 29. Idempotency

Every integration operation must support idempotency where applicable.

Use identifiers such as:

```text
external_message_id
external_control_id
idempotency_key
source_system
event_id
correlation_id
```

Example:

```text
Same HL7 ORU message received twice
        ↓
Detect duplicate
        ↓
Do not create duplicate result
```

Critical operations must be protected by database uniqueness constraints.

---

# 30. Retry Strategy

Retry transient failures.

Example:

```text
Attempt 1
   ↓
5 minutes
   ↓
Attempt 2
   ↓
15 minutes
   ↓
Attempt 3
   ↓
30 minutes
   ↓
Dead Letter Queue
```

Retry policy must be configurable per integration.

Do not retry permanent validation errors indefinitely.

---

# 31. Dead Letter Queue

Failed messages must be visible.

DLQ record should include:

```text
message_id
integration_id
error_type
error_code
error_message
retry_count
last_attempt_at
payload_reference
status
resolved_by
resolved_at
resolution_notes
```

Authorized integration administrators can:

- Inspect
- Retry
- Cancel
- Reprocess
- Resolve
- Export diagnostic information

All such actions must be audited.

---

# 32. Integration Monitoring

Dashboard:

```text
Integrations
├── Active
├── Inactive
├── Healthy
├── Degraded
├── Failed
├── Message Throughput
├── Error Rate
├── Pending Queue
├── Retry Queue
├── Dead Letters
└── Response Time
```

Integration health should display:

- Last successful transaction
- Last failed transaction
- Error rate
- Queue depth
- Average response time
- Availability
- Certificate status where applicable

---

# 33. Security

Interoperability can expose highly sensitive health information.

Implement:

### Authentication

Support configurable:

- OAuth 2.0
- OAuth 2.0 client credentials
- API keys
- Mutual TLS
- Basic authentication only where legacy systems require it
- HL7 interface credentials
- DICOM AE Title/network controls

### Transport

Prefer:

```text
HTTPS/TLS
```

Never send clinical data over unsecured HTTP in production.

---

# 34. Authorization

Authorization must consider:

```text
Organization
Hospital
Branch
Department
External System
Resource
Operation
Patient
Data Category
```

Example:

```text
External Lab A
    ↓
Only Hospital A laboratory data
```

must not access:

```text
Hospital B
```

---

# 35. Patient Data Protection

Implement:

- Minimum necessary data
- Field-level filtering where appropriate
- Resource-level authorization
- Patient ownership checks
- Proxy/family access controls
- Consent checks
- Integration-specific data policies
- Audit logging
- Export logging

Do not expose the complete Patient 360 to every external integration.

---

# 36. Consent

Where required, interoperability requests should evaluate:

```text
Is exchange permitted?
      ↓
Which organization?
      ↓
Which resource?
      ↓
Which data category?
      ↓
Which purpose?
      ↓
Is consent valid?
```

Emergency/break-glass interoperability must be explicitly supported where legally/operationally required and fully audited.

---

# 37. Integration Credentials

Secrets should be stored using secure application secret mechanisms.

Never:

- Store plaintext passwords in logs.
- Return credentials through APIs.
- Put API secrets into Git.
- Include tokens in audit payloads.
- Expose private keys through normal UI.

Support credential rotation.

---

# 38. Certificate Management

Provide integration certificate metadata:

```text
certificate_name
system
issuer
valid_from
valid_until
status
fingerprint
credential_reference
```

Generate alerts before expiry.

Private keys must remain protected.

---

# 39. Webhooks

Support outbound webhooks for selected events.

Example:

```text
patient.created
appointment.created
appointment.cancelled
encounter.completed
lab.result.finalized
radiology.report.finalized
prescription.created
pharmacy.dispensed
admission.created
patient.discharged
claim.submitted
```

Webhook security:

- HTTPS
- Signature
- Timestamp
- Replay protection
- Retry
- Idempotency
- Delivery audit

---

# 40. Domain Event Integration

Existing modules should publish events.

Example:

```php
LabResultFinalized
RadiologyReportFinalized
PatientRegistered
EncounterCompleted
PrescriptionCreated
MedicationDispensed
AdmissionCreated
PatientDischarged
```

Phase 18 subscribes to these events.

Clinical modules should not contain external-system-specific code.

---

# 41. Example Event Flow

### Laboratory

```text
LIS
 ↓
Result Finalized
 ↓
LabResultFinalized Event
 ↓
Interoperability
 ↓
FHIR DiagnosticReport
 ↓
External System
```

### Radiology

```text
Radiology
 ↓
Report Finalized
 ↓
Event
 ↓
FHIR DiagnosticReport
 +
ImagingStudy
 ↓
External System
```

### Patient

```text
MPI
 ↓
PatientRegistered
 ↓
FHIR Patient
 ↓
HIE / External Hospital
```

---

# 42. Integration Workflows

Support configurable workflows:

```text
Trigger
 ↓
Validation
 ↓
Mapping
 ↓
Transformation
 ↓
Destination
 ↓
Transmission
 ↓
Acknowledgement
 ↓
Completion
```

Workflow failures must not silently alter source-domain records.

---

# 43. Data Transformation

Use explicit transformation pipelines.

Example:

```text
Local Lab Result
      ↓
Local Code Mapping
      ↓
LOINC Mapping
      ↓
FHIR Observation
      ↓
FHIR Validation
      ↓
External API
```

Transformation must preserve provenance.

---

# 44. Provenance

Where supported, track:

- Source system
- Source record ID
- Source timestamp
- Transformation version
- Mapping version
- User/system actor
- Integration ID
- Correlation ID

FHIR Provenance should be supported where appropriate.

---

# 45. Integration Audit

Audit:

- Integration created
- Integration modified
- Credential changed
- Message received
- Message sent
- Message rejected
- Message transformed
- Message retried
- Message failed
- Message reprocessed
- Mapping changed
- Terminology mapping approved
- Webhook delivered
- Export performed
- Break-glass exchange
- Configuration changes

Never log sensitive payloads unnecessarily.

---

# 46. Suggested Database Tables

## Integration Core

```text
integration_systems
integration_endpoints
integration_credentials
integration_routes
integration_route_conditions
integration_adapters
integration_configurations
integration_identifiers
integration_mappings
integration_messages
integration_message_payloads
integration_message_events
integration_message_errors
integration_acknowledgements
integration_retries
integration_dead_letters
integration_webhooks
integration_webhook_deliveries
integration_health_checks
integration_certificates
```

## FHIR

```text
fhir_resource_mappings
fhir_resource_versions
fhir_profiles
fhir_search_parameters
fhir_subscriptions
fhir_resource_links
fhir_provenance_records
```

## HL7

```text
hl7_message_types
hl7_message_definitions
hl7_message_segments
hl7_messages
hl7_acknowledgements
hl7_mappings
```

## DICOM

```text
dicom_systems
dicom_modalities
dicom_worklist_items
dicom_studies
dicom_series
dicom_instances
dicom_identifier_mappings
dicom_events
```

## Terminology

```text
terminology_systems
terminology_codes
terminology_concept_maps
terminology_mappings
terminology_mapping_reviews
```

---

# 47. Important Database Constraints

Examples:

```text
UNIQUE(
    integration_system_id,
    external_message_id
)
```

and:

```text
UNIQUE(
    source_system,
    source_record_type,
    source_record_id
)
```

FHIR resource identity must be stable.

DICOM identifiers must not be duplicated incorrectly.

Terminology mappings must have appropriate uniqueness rules.

---

# 48. API Architecture

Base:

```text
/api/v1/interoperability
```

Possible endpoints:

```text
GET    /integrations
POST   /integrations
GET    /integrations/{id}
PUT    /integrations/{id}
POST   /integrations/{id}/test

GET    /messages
GET    /messages/{id}
POST   /messages/{id}/retry
POST   /messages/{id}/reprocess

GET    /dead-letters
POST   /dead-letters/{id}/retry

GET    /routes
POST   /routes

GET    /terminology
GET    /mappings

GET    /health
```

FHIR:

```text
/api/v1/fhir/*
```

HL7:

```text
/api/v1/hl7/*
```

DICOM:

```text
/api/v1/dicom/*
```

Actual routing should remain consistent with the existing application's API conventions.

---

# 49. UI / Admin Menu

## Interoperability

### Dashboard

- Integration Health
- Message Throughput
- Failed Messages
- Queue Status
- Dead Letters
- Response Time

### Integrations

- Systems
- Endpoints
- Credentials
- Routes
- Adapters
- Health Checks

### Messages

- Inbound
- Outbound
- Failed
- Retry Queue
- Dead Letter Queue

### Standards

- FHIR
- HL7
- DICOM

### Terminology

- Code Systems
- Mappings
- Mapping Review

### Monitoring

- Delivery Logs
- Errors
- Audit
- Certificates

### Configuration

- Retry Policies
- Data Exchange Policies
- Rate Limits
- Retention

---

# 50. Permissions

Examples:

```text
interoperability.dashboard.view

interoperability.system.view
interoperability.system.create
interoperability.system.update
interoperability.system.delete

interoperability.endpoint.view
interoperability.endpoint.create
interoperability.endpoint.update

interoperability.message.view
interoperability.message.retry
interoperability.message.reprocess
interoperability.message.cancel
interoperability.message.export

interoperability.route.view
interoperability.route.create
interoperability.route.update

interoperability.fhir.view
interoperability.hl7.view
interoperability.dicom.view

interoperability.mapping.view
interoperability.mapping.create
interoperability.mapping.approve

interoperability.credential.manage
interoperability.certificate.manage

interoperability.audit.view
interoperability.configuration.manage
```

---

# 51. Roles

Recommended:

### Interoperability Administrator

Full integration administration.

### Integration Engineer

Integration configuration and troubleshooting.

### Integration Operator

Message monitoring/reprocessing.

### Terminology Administrator

Code-system and mapping management.

### Clinical Informatics Administrator

Clinical interoperability configuration.

### Security Administrator

Credentials, certificates and security configuration.

### Auditor

Read-only audit access.

---

# 52. Notifications

Generate alerts for:

- Integration unavailable
- Certificate nearing expiry
- Message failure threshold
- Dead-letter growth
- Authentication failure
- Repeated API errors
- Queue backlog
- HL7 ACK failure
- DICOM communication failure
- FHIR validation failures
- Terminology mapping gaps

Use existing Phase 0 Notifications.

Do not create another notification engine.

---

# 53. Reporting

Reports should include:

### Integration Performance

- Messages sent
- Messages received
- Success rate
- Failure rate
- Average latency

### HL7

- Message count
- ACK failures
- Processing failures

### FHIR

- Resource exchange
- Validation failures
- API response time

### DICOM

- Modality transactions
- Worklist failures
- PACS communication
- Study synchronization

### Terminology

- Unmapped codes
- Pending mappings
- Mapping changes

### Security

- Authentication failures
- Unauthorized requests
- Break-glass exchanges
- Export activity

---

# 54. Observability

Use structured logging.

Every transaction should have:

```text
request_id
correlation_id
integration_id
external_system_id
message_id
event_id
user_id
timestamp
status
duration
```

Use correlation IDs throughout the lifecycle.

Example:

```text
PatientRegistered
      correlation_id
          ↓
FHIR Message
      correlation_id
          ↓
External API
      correlation_id
          ↓
Response
```

---

# 55. Queue Architecture

Use Laravel queues/Redis where appropriate.

Queues may include:

```text
interoperability.outbound
interoperability.inbound
interoperability.fhir
interoperability.hl7
interoperability.dicom
interoperability.webhooks
interoperability.retry
interoperability.dead-letter
```

Do not block user-facing clinical transactions waiting for slow external systems unless the workflow explicitly requires synchronous confirmation.

---

# 56. Transaction and Reliability Rules

For critical integration workflows:

- Use database transactions.
- Use idempotency.
- Use unique constraints.
- Use queue-safe jobs.
- Use retry policies.
- Use distributed-lock strategy where required.
- Prevent duplicate messages.
- Prevent duplicate clinical records.
- Preserve source data.
- Preserve message history.

---

# 57. External API Failure

External integration failure must not corrupt the source domain.

Example:

```text
Lab Result Finalized
      ↓
FHIR transmission fails
      ↓
Lab Result remains FINAL
      ↓
Integration status = FAILED
      ↓
Retry
```

Do not roll back the clinical result merely because an external API is unavailable.

---

# 58. Data Ownership Matrix

| Domain | Owner | Interoperability Role |
|---|---|---|
| Patient | Phase 1 | Exchange |
| Appointment | Phase 2 | Exchange |
| Encounter | Phase 3 | Exchange |
| Diagnosis | Phase 3 | Map/exchange |
| Billing | Phase 4 | Exchange |
| Laboratory | Phase 5 | Exchange |
| Radiology | Phase 6 | DICOM/FHIR exchange |
| Pharmacy | Phase 7 | Exchange |
| Admission | Phase 8 | Exchange |
| Nursing | Phase 9 | Exchange |
| Surgery | Phase 10 | Exchange |
| ICU | Phase 11 | Exchange |
| Emergency | Phase 12 | Exchange |
| Blood Bank | Phase 13 | Exchange |
| Insurance | Phase 14 | Exchange |
| Inventory | Phase 15 | Exchange |
| HR | Phase 16 | Exchange |
| Patient Portal | Phase 17 | Exchange |
| Interoperability | Phase 18 | Owns integration |

---

# 59. AI Capabilities

AI may assist with:

### Integration Mapping

Suggest:

```text
Local Field
      ↓
FHIR Field
```

### Terminology Mapping

Suggest possible mappings between local and standard codes.

Human approval is required for clinically significant mappings.

### Message Error Analysis

Summarize:

- Error
- probable cause
- affected system
- suggested troubleshooting

### Data Quality

Detect:

- Missing identifiers
- Invalid codes
- Inconsistent dates
- Missing required fields
- Duplicate messages
- Unmapped terminology

### Integration Monitoring

Detect abnormal:

- Error rate
- latency
- message volume
- retry volume
- downtime patterns

### Mapping Documentation

Generate draft:

- Integration documentation
- Field mappings
- Transformation descriptions
- Test cases

---

# 60. AI Safety Rules

AI must NEVER:

- Invent clinical values.
- Invent patient identifiers.
- Automatically change diagnosis codes.
- Automatically change medication codes.
- Automatically approve clinical terminology mappings.
- Modify patient records without explicit authorized workflow.
- Bypass authorization.
- Bypass consent.
- Send data to an external system without the configured integration policy.
- Override FHIR/HL7 validation.
- Suppress integration errors.
- Automatically approve security exceptions.

AI output is advisory.

Human authorization is required for clinically meaningful mappings and configuration changes.

---

# 61. FHIR / HL7 / DICOM AI Boundary

AI may say:

> "This local laboratory code appears potentially equivalent to LOINC X based on the configured mapping candidates."

It must NOT silently create:

> `local code → LOINC X`

without the required approval workflow.

---

# 62. Privacy-Preserving AI

AI services should receive minimum necessary information.

Prefer:

```text
Structured error
+
message metadata
+
sanitized payload
```

instead of sending entire clinical payloads.

Never expose:

- Passwords
- API keys
- Tokens
- Private certificates
- Encryption keys
- Unnecessary patient identifiers

---

# 63. Events

Suggested events:

```text
IntegrationCreated
IntegrationUpdated
IntegrationDisabled

IntegrationMessageReceived
IntegrationMessageValidated
IntegrationMessageRejected
IntegrationMessageProcessed
IntegrationMessageFailed
IntegrationMessageRetried
IntegrationMessageDeadLettered
IntegrationMessageReprocessed

FhirResourceExported
FhirResourceReceived
FhirValidationFailed

Hl7MessageReceived
Hl7MessageSent
Hl7AckReceived
Hl7AckFailed

DicomStudyReceived
DicomWorklistCreated
DicomWorklistUpdated
DicomStudySynchronized

TerminologyMappingCreated
TerminologyMappingApproved
TerminologyMappingRejected

IntegrationCertificateExpiring
IntegrationHealthChanged
```

---

# 64. Jobs

Examples:

```text
ProcessInboundIntegrationMessage
SendOutboundIntegrationMessage
GenerateFhirResource
ProcessFhirBundle
ProcessHl7Message
SendHl7Ack
ProcessDicomWorklist
SynchronizeDicomStudy
DeliverWebhook
RetryIntegrationMessage
ProcessDeadLetter
RunIntegrationHealthCheck
CheckCertificateExpiry
ValidateTerminologyMappings
```

---

# 65. Security Testing

Mandatory tests include:

### Tenant Isolation

Hospital A cannot retrieve Hospital B integration data.

### External-System Isolation

Integration A cannot use Integration B credentials.

### API Security

Unauthorized systems cannot call FHIR/HL7 endpoints.

### Patient Data Security

An external integration cannot retrieve patients outside its permitted scope.

### Credential Security

Secrets are never returned by APIs.

### Replay Protection

Old webhook/message cannot be replayed successfully where replay protection is required.

### Idempotency

Duplicate messages do not create duplicate records.

### Authorization

A user without reprocess permission cannot reprocess messages.

---

# 66. Testing Strategy

## Unit Tests

Test:

- FHIR mappers
- HL7 parser
- HL7 generator
- DICOM mappings
- Terminology mappings
- Identifier resolution
- Routing rules
- Retry logic
- Idempotency

## Feature Tests

Test:

- Integration creation
- Message ingestion
- Message routing
- FHIR API
- HL7 processing
- Webhooks
- DICOM worklist

## Integration Tests

Test actual adapters against test environments where available.

## Contract Tests

Validate:

```text
HMS ↔ External System
```

against agreed message/resource contracts.

## Security Tests

Test:

- authorization
- tenant isolation
- credential protection
- replay protection
- rate limiting
- injection
- malformed messages

## Reliability Tests

Test:

- external timeout
- external downtime
- queue failure
- duplicate delivery
- retry
- dead-letter
- partial failure

---

# 67. Performance

Design for asynchronous processing.

Avoid:

```text
Clinical Save
   ↓
External API
   ↓
wait 30 seconds
```

Prefer:

```text
Clinical Save
   ↓
Commit
   ↓
Event
   ↓
Queue
   ↓
Integration Worker
```

Use synchronous integration only when business requirements require immediate confirmation.

---

# 68. API Response Standard

Internal APIs continue using the existing standard:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {},
  "meta": {}
}
```

Validation:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "endpoint": [
      "The endpoint field is required."
    ]
  }
}
```

FHIR endpoints should return standards-compliant FHIR responses where appropriate rather than wrapping FHIR resources in the application's generic response structure.

HL7 endpoints should return the appropriate HL7 acknowledgment.

---

# 69. Localization

All administrative UI must be localization-ready.

Initial languages:

- English
- Bangla
- Arabic

Do not hardcode user-facing strings.

---

# 70. Multi-Hospital Architecture

Every integration must support organization/hospital scope where applicable.

Example:

```text
Organization
 ├── Hospital A
 │    ├── LIS Integration
 │    └── PACS Integration
 │
 └── Hospital B
      ├── LIS Integration
      └── PACS Integration
```

Do not assume one global integration endpoint.

---

# 71. File Exchange

Where file-based integration is required:

- Validate file type.
- Validate size.
- Virus/malware scan where available.
- Store privately.
- Generate checksum.
- Track source/destination.
- Encrypt where appropriate.
- Expire temporary files.
- Audit access.

---

# 72. Data Retention

Integration data retention must be configurable according to:

- Organization policy
- Legal requirements
- Clinical retention requirements
- External contract
- Security requirements

Do not hardcode an arbitrary retention period.

---

# 73. Implementation Order

Implement Phase 18 in this sequence:

### Step 1
Inspect existing repository.

### Step 2
Inspect Phases 0–17.

### Step 3
Map all existing domain events and services.

### Step 4
Create interoperability architecture.

### Step 5
Create integration registry.

### Step 6
Create endpoint and credential architecture.

### Step 7
Create message lifecycle.

### Step 8
Implement queues and workers.

### Step 9
Implement routing engine.

### Step 10
Implement transformation architecture.

### Step 11
Implement identifier mapping.

### Step 12
Implement terminology mapping.

### Step 13
Implement FHIR foundation.

### Step 14
Implement FHIR Patient/Organization/Practitioner.

### Step 15
Implement FHIR Encounter/Appointment.

### Step 16
Implement FHIR clinical resources.

### Step 17
Implement FHIR laboratory/radiology resources.

### Step 18
Implement FHIR medication resources.

### Step 19
Implement FHIR billing/insurance resources.

### Step 20
Implement HL7 parser/generator.

### Step 21
Implement ADT.

### Step 22
Implement ORM.

### Step 23
Implement ORU.

### Step 24
Implement SIU.

### Step 25
Implement DICOM foundation.

### Step 26
Implement DICOM Modality Worklist.

### Step 27
Implement DICOMweb adapters.

### Step 28
Implement webhook architecture.

### Step 29
Implement monitoring.

### Step 30
Implement retry/DLQ.

### Step 31
Implement audit/security.

### Step 32
Implement dashboards.

### Step 33
Implement AI assistance.

### Step 34
Implement comprehensive testing.

### Step 35
Implement documentation.

---

# 74. Definition of Done

Phase 18 is complete only when:

## Architecture

- Existing modular architecture reused.
- No duplicate module framework created.
- Integration boundaries documented.

## Integration Core

- External systems registered.
- Endpoints configurable.
- Credentials secured.
- Routing implemented.
- Mapping implemented.
- Message lifecycle implemented.

## FHIR

- FHIR API implemented.
- Resource mapping implemented.
- Validation implemented.
- Search implemented for supported resources.
- Bundle support implemented.
- Provenance supported where applicable.

## HL7

- Parser implemented.
- Generator implemented.
- ACK implemented.
- ADT supported.
- ORM supported.
- ORU supported.
- SIU foundation implemented.

## DICOM

- DICOM architecture implemented.
- Modality Worklist integration supported.
- DICOMweb adapter foundation implemented.
- PACS remains external image owner.

## Reliability

- Idempotency implemented.
- Retry implemented.
- Dead-letter implemented.
- Correlation IDs implemented.
- Queue monitoring implemented.

## Security

- Authentication implemented.
- Authorization implemented.
- Hospital isolation tested.
- Credential protection implemented.
- Audit implemented.
- Replay protection implemented where required.

## Terminology

- Mapping engine implemented.
- Approval workflow implemented.
- Unmapped terminology reporting implemented.

## Monitoring

- Integration dashboard implemented.
- Health checks implemented.
- Failure alerts implemented.
- Certificate monitoring implemented.

## AI

- Mapping assistance implemented safely.
- Error analysis implemented.
- Data-quality assistance implemented.
- No autonomous clinical changes.

## Testing

- Unit tests.
- Feature tests.
- Integration tests.
- Contract tests.
- Security tests.
- Idempotency tests.
- Failure/retry tests.
- Multi-hospital isolation tests.

## Documentation

Document:

- Architecture
- FHIR resources
- HL7 message types
- DICOM integration
- Mapping rules
- API
- Authentication
- Deployment
- Monitoring
- Troubleshooting
- Disaster recovery
- Security
- Data retention
- Integration onboarding

---

# 75. Recommended Future Extensions

Phase 18 should provide extension points for:

- National HIE
- Government health platforms
- Public-health reporting
- External referral networks
- Telemedicine interoperability
- SMART on FHIR
- CDS Hooks
- Bulk FHIR
- FHIR Subscriptions
- FHIR terminology services
- Advanced DICOMweb
- IoT/medical-device integration
- Remote patient monitoring
- Genomics interoperability
- Regional health information exchange

These should be added without redesigning the core interoperability architecture.

---

# 76. Final AI Implementation Prompt

## COPY-PASTE PROMPT FOR AI CODING AGENT

You are a senior healthcare interoperability architect and Laravel/PHP enterprise software engineer.

Implement **Phase 18 — Interoperability / FHIR / HL7 / DICOM** in the existing Hospital Management System.

### Critical instruction

The existing HMS modular architecture is ALREADY IMPLEMENTED.

Before changing anything:

1. Inspect the entire repository.
2. Identify the existing module structure.
3. Inspect the architecture/base classes/services.
4. Inspect Phases 0–17.
5. Inspect existing:
   - Patient/MPI
   - Appointment
   - Encounter/EMR
   - Billing
   - LIS
   - Radiology/PACS
   - Pharmacy
   - Admission/Bed
   - Nursing
   - OT/Surgery
   - ICU
   - Emergency
   - Blood Bank
   - Insurance
   - Inventory/Procurement
   - HR
   - Patient Portal/Mobile/CRM
   - Authentication
   - RBAC
   - Audit
   - Notifications
   - File Management
   - Workflow
   - API architecture
   - Events
   - Queues
6. Reuse the existing architecture.
7. DO NOT create a second modular framework.
8. DO NOT duplicate existing business-domain modules.

---

## Objective

Build Phase 18 as the enterprise interoperability layer responsible for:

- FHIR
- HL7 v2
- DICOM
- DICOMweb
- External REST APIs
- Webhooks
- Message routing
- Data transformation
- Terminology mapping
- Identifier mapping
- Integration monitoring
- Retry
- Dead-letter processing
- Idempotency
- Integration audit
- Security
- Consent-aware exchange
- AI-assisted interoperability

The interoperability module must NOT become the owner of clinical or financial domain data.

---

## Architecture

Use:

- Laravel
- PHP 8.4+
- Existing modular monolith
- MySQL/PostgreSQL
- Redis
- Laravel queues
- Blade
- AdminLTE
- REST APIs
- Dependency injection
- Services/actions
- Form Requests
- Policies/Gates
- Events/Listeners
- Jobs
- Transactions
- Repository patterns only where the existing architecture already uses them

Follow PSR-12 and Laravel conventions.

---

## Domain Ownership Rule

Respect this ownership:

```text
Patient              → Phase 1
Appointment          → Phase 2
Encounter            → Phase 3
Billing              → Phase 4
Laboratory           → Phase 5
Radiology/PACS       → Phase 6
Pharmacy             → Phase 7
Admission/Beds       → Phase 8
Nursing              → Phase 9
Surgery/OT           → Phase 10
ICU                  → Phase 11
Emergency            → Phase 12
Blood Bank           → Phase 13
Insurance            → Phase 14
Inventory            → Phase 15
HR                   → Phase 16
Patient Portal/CRM   → Phase 17
Interoperability     → Phase 18
```

Phase 18 transports and translates domain information.

It must not duplicate domain ownership.

---

## Implement

### 1. Integration Registry

Implement:

- External systems
- Endpoints
- Credentials
- Adapters
- Routes
- Integration policies
- Health checks
- Certificates

---

### 2. Message Engine

Implement:

- inbound messages
- outbound messages
- message lifecycle
- message IDs
- correlation IDs
- idempotency keys
- message payload references
- processing events
- failures
- retries
- dead letters
- acknowledgments

---

### 3. Routing Engine

Implement configurable:

```text
Event
→ condition
→ transformation
→ destination
→ adapter
```

Do not place external-system-specific logic inside clinical modules.

---

### 4. FHIR

Implement a robust FHIR foundation.

Support at minimum:

```text
Patient
Organization
Location
Practitioner
PractitionerRole
Appointment
Encounter
Condition
Observation
Procedure
ServiceRequest
DiagnosticReport
Specimen
Medication
MedicationRequest
MedicationDispense
MedicationAdministration
AllergyIntolerance
DocumentReference
Consent
ImagingStudy
Device
Coverage
Claim
Task
CarePlan
Provenance
```

Implement:

- FHIR resource mapping
- validation
- profiles
- search
- bundles
- transaction/batch foundation
- resource versioning where required
- resource references
- provenance
- authorization

Expose versioned FHIR APIs.

---

### 5. HL7 v2

Implement:

- parser
- generator
- validation
- ACK
- message storage
- routing
- mapping

Support:

```text
ADT
ORM
ORU
SIU
```

Design for future message types.

---

### 6. DICOM

Implement:

- DICOM system registry
- modality registry
- modality worklist architecture
- study/series/instance metadata
- identifier mapping
- DICOM events
- DICOMweb adapter foundation
- QIDO-RS
- WADO-RS
- STOW-RS

Do NOT store medical image binaries inside normal HMS database tables.

Phase 6 remains the Radiology/PACS domain owner.

---

### 7. Terminology

Implement:

- terminology systems
- codes
- concept maps
- local-to-standard mappings
- mapping approval
- mapping history

Provide architecture for:

- ICD-10
- ICD-11
- SNOMED CT
- LOINC
- medication terminology
- local laboratory codes
- local radiology codes
- local procedure codes

Do not invent mappings.

---

### 8. Identifier Mapping

Support mapping between:

- local MRN
- external MRN
- encounter IDs
- accession numbers
- order IDs
- provider IDs
- organization IDs
- claim IDs

Never replace internal master identifiers.

---

### 9. Security

Implement:

- OAuth 2.0 where applicable
- API key support
- mTLS support
- HTTPS enforcement
- RBAC
- organization scope
- hospital scope
- integration scope
- patient/resource scope
- rate limiting
- replay protection
- credential protection
- certificate monitoring

Secrets must never be exposed through APIs or logs.

---

### 10. Consent and Privacy

Implement configurable:

- data exchange policies
- consent checks
- purpose-of-use controls
- minimum necessary data
- emergency/break-glass interoperability

Audit all sensitive exchange operations.

---

### 11. Reliability

Implement:

- asynchronous queues
- retry policy
- exponential/backoff strategy
- dead-letter queue
- idempotency
- unique constraints
- transaction boundaries
- correlation IDs
- failure isolation

External system downtime must not corrupt source-domain records.

---

### 12. Webhooks

Implement secure outbound webhooks with:

- HMAC/signature
- timestamp
- replay protection
- retries
- delivery status
- idempotency
- audit

---

### 13. Monitoring

Build an interoperability dashboard showing:

- integrations
- health
- messages
- success/failure
- queues
- retries
- dead letters
- latency
- certificates
- terminology gaps

---

### 14. Notifications

Reuse the existing notification system.

Do not create another notification framework.

Alert for:

- integration failures
- repeated errors
- certificate expiry
- queue backlog
- dead-letter growth
- authentication failures
- unavailable systems

---

### 15. Audit

Reuse the existing audit infrastructure.

Audit:

- configuration changes
- credentials
- messages
- retries
- reprocessing
- exports
- mapping changes
- terminology approvals
- security failures
- break-glass exchange

Do not unnecessarily store sensitive clinical payloads inside ordinary audit logs.

---

### 16. AI

Implement safe AI extension points for:

- terminology mapping suggestions
- FHIR mapping suggestions
- HL7 error analysis
- message troubleshooting
- data-quality detection
- integration anomaly detection
- documentation generation

AI must remain advisory.

AI must NEVER:

- alter clinical records autonomously
- alter diagnoses
- alter medications
- approve clinical terminology mappings
- bypass authorization
- bypass consent
- send unapproved patient data
- suppress integration errors
- modify security controls autonomously

---

## API

Use the existing API architecture.

Potential namespaces:

```text
/api/v1/interoperability
/api/v1/fhir
/api/v1/hl7
/api/v1/dicom
```

Do not violate the existing project's API conventions.

FHIR endpoints should return standards-compliant FHIR responses.

HL7 endpoints should return appropriate HL7 acknowledgments.

---

## Database

Implement the required interoperability tables, including appropriate equivalents of:

```text
integration_systems
integration_endpoints
integration_credentials
integration_routes
integration_route_conditions
integration_adapters
integration_configurations
integration_identifiers
integration_mappings
integration_messages
integration_message_payloads
integration_message_events
integration_message_errors
integration_acknowledgements
integration_retries
integration_dead_letters
integration_webhooks
integration_webhook_deliveries
integration_health_checks
integration_certificates

fhir_resource_mappings
fhir_resource_versions
fhir_profiles
fhir_search_parameters
fhir_subscriptions
fhir_resource_links
fhir_provenance_records

hl7_message_types
hl7_message_definitions
hl7_message_segments
hl7_messages
hl7_acknowledgements
hl7_mappings

dicom_systems
dicom_modalities
dicom_worklist_items
dicom_studies
dicom_series
dicom_instances
dicom_identifier_mappings
dicom_events

terminology_systems
terminology_codes
terminology_concept_maps
terminology_mappings
terminology_mapping_reviews
```

Adjust names to match the project's existing naming conventions.

---

## Concurrency

Protect:

- duplicate message processing
- duplicate FHIR resource creation
- duplicate HL7 processing
- DICOM worklist creation
- webhook delivery
- retry execution
- terminology mapping approval
- integration configuration updates

Use:

- database unique constraints
- transactions
- locking where required
- idempotency
- queue-safe jobs

Never use `COUNT()` to generate business identifiers.

---

## Multi-Hospital Security Test

This test is mandatory:

```text
Hospital A Integration User
        ↓
attempts
        ↓
Hospital B Message
```

Expected:

```text
ACCESS DENIED
```

Likewise test:

```text
Integration A
    ↓
Integration B credential
```

Expected:

```text
ACCESS DENIED
```

---

## Testing

Implement:

### Unit

- FHIR mapping
- HL7 parser
- HL7 generator
- DICOM mapping
- terminology mapping
- identifier mapping
- routing
- retry
- idempotency

### Feature

- integration CRUD
- inbound messages
- outbound messages
- FHIR APIs
- HL7 APIs
- DICOM worklist
- webhooks

### Integration

Test adapters against test endpoints where available.

### Contract

Validate FHIR/HL7/DICOM contracts.

### Security

Test:

- tenant isolation
- authorization
- credential security
- replay protection
- rate limiting
- malformed input
- injection
- data leakage

### Reliability

Test:

- timeout
- external outage
- duplicate delivery
- queue failure
- retry
- dead letter
- reprocessing
- partial failure

---

## Documentation

Create/update:

```text
docs/interoperability/
```

Document:

- Architecture
- Integration onboarding
- FHIR
- HL7
- DICOM
- Terminology
- Identifier mapping
- Authentication
- Security
- Consent
- API
- Webhooks
- Queue architecture
- Retry
- Dead letters
- Monitoring
- Troubleshooting
- Certificate management
- Disaster recovery
- Deployment
- Testing
```

---

## Final Validation

Before declaring Phase 18 complete:

1. Inspect all existing Phase 0–17 code.
2. Confirm no duplicated domain ownership.
3. Run migrations.
4. Run static analysis if configured.
5. Run automated tests.
6. Run security tests.
7. Run integration/contract tests where available.
8. Test idempotency.
9. Test retry/DLQ.
10. Test Hospital A/Hospital B isolation.
11. Test FHIR resources.
12. Test HL7 parsing/ACK.
13. Test DICOM worklist integration.
14. Test terminology mapping approval.
15. Test webhook security.
16. Test credential protection.
17. Test queue failure scenarios.
18. Test external-system downtime.
19. Review API authorization.
20. Review audit coverage.
21. Review documentation.

Never claim a test passed unless it was actually executed and passed.

At the end, provide a concise implementation report containing:

```text
1. Files created
2. Files modified
3. Database migrations
4. Services/classes added
5. APIs added
6. FHIR resources implemented
7. HL7 messages implemented
8. DICOM capabilities implemented
9. Integrations implemented
10. Events/jobs added
11. Permissions added
12. Security controls
13. Tests executed
14. Test results
15. Known limitations
16. Remaining TODOs
17. Deployment steps
18. Documentation created
```

The final implementation must be production-oriented, secure, auditable, interoperable, multi-hospital capable, extensible, and consistent with the existing HMS architecture.