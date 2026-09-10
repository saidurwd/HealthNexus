# Multi-Tenant SaaS Hospital Management System (HMS)
## AI Development Specification for Laravel

**Document Version:** 1.0  
**Architecture:** Multi-Tenant SaaS, Modular Monolith  
**Primary Framework:** Laravel / PHP  
**Database:** MySQL  
**Frontend:** Blade
**Purpose:** Master specification and coding guide for AI-assisted development

---

# 1. Project Objective

Build a robust, scalable, secure, API-first **Hospital Management System (HMS)** delivered as a multi-tenant SaaS platform.

The application must support:

- Multiple companies/tenants
- Multiple branches/hospitals per company
- Multiple departments per branch
- Multiple users and roles
- Patient registration and enterprise patient identity
- OPD and IPD
- EHR
- Physician CPOE
- Pharmacy
- Inventory and procurement
- Pathology/Laboratory
- Radiology and PACS integration
- Billing and payments
- Notifications
- Reporting and analytics
- Audit logging
- Subscription and feature management
- FHIR / HL7 / DICOM integration hooks

The system must be designed so that additional modules can be added without rewriting the core architecture.

---

# 2. Critical AI Development Rules

The coding AI must follow these rules on every task.

## 2.1 Do not code blindly

Before modifying code:

1. Inspect the existing project structure.
2. Identify related modules/classes/migrations/tests.
3. Check existing conventions.
4. Reuse existing services, policies, components, and utilities when appropriate.
5. Do not create duplicate implementations without justification.

## 2.2 Preserve architecture

Do not place business logic directly into controllers.

Use:

- Domain services
- Application services
- Actions/use-cases
- Policies
- Form Requests
- DTOs
- Events
- Jobs
- Repositories only where they provide real value
- Value Objects for important domain concepts

## 2.3 Tenant isolation is mandatory

Every tenant-owned operation must validate:

- authenticated user
- company
- branch
- department where applicable
- role
- permission
- data scope

Never trust `company_id`, `branch_id`, or similar values supplied by the client.

## 2.4 Database first for core workflows

Important clinical and financial transactions must be transaction-safe.

Use database transactions for operations such as:

- medication dispensing
- stock receipt
- stock transfer
- stock adjustment
- diagnostic order creation
- result finalization
- invoice creation
- payment posting

## 2.5 No destructive shortcuts

Never:

- overwrite finalized clinical results
- silently delete financial transactions
- modify inventory balances without ledger entries
- bypass authorization
- disable audit logging
- expose tenant data through unscoped queries

Use versioning, reversal, amendment, or cancellation workflows where appropriate.

## 2.6 Every significant feature requires tests

AI-generated features must include:

- unit tests for domain logic
- feature/API tests
- authorization tests
- tenant isolation tests
- validation tests
- failure/rollback tests

---

# 3. Architecture Principles

## 3.1 Architectural style

Start as a **Modular Monolith**.

Do not begin with many microservices.

The system should have strong domain boundaries so that selected domains can later become independent services.

## 3.2 High-level architecture

```text
Web / Mobile Clients
        |
        v
API / Web Layer
        |
        v
Authentication + Tenant Resolver
        |
        v
Application / Domain Modules
        |
 ---------------------------------------------------------
 |         |          |        |        |        |       |
Patient  Clinical  Pharmacy Inventory Lab  Radiology Billing
 ---------------------------------------------------------
        |
        v
Domain Events / Queue
        |
        +---- Notifications
        +---- Integration
        +---- Reporting
        +---- Background Jobs

External Integration Layer
    |
    +---- FHIR
    +---- HL7 v2
    +---- DICOM / DICOMweb
    +---- PACS
    +---- Analyzer Middleware
```

---

# 4. Recommended Technology Stack

## Backend

- PHP 8.4+
- Laravel (current supported stable release)
- Laravel Queue
- Laravel Scheduler
- Laravel Events
- Laravel Notifications
- Laravel Sanctum or Passport depending on API requirements
- Laravel Horizon for Redis queues
- Spatie Laravel Permission or equivalent RBAC implementation

## Frontend

Recommended:

- Blade
- Tailwind CSS
- A reusable component library

## Database

- MySQL

## Cache

- Redis

## Queue / Messaging

- RabbitMQ for moderate integration workloads
- Kafka may be introduced later for very high event throughput

## Integration

Use a healthcare integration engine where appropriate:

- NextGen Connect / Mirth
- FHIR adapter
- HL7 v2 adapter
- DICOM integration gateway

## Storage

S3-compatible object storage for:

- reports
- attachments
- documents
- generated PDFs
- exports
- non-DICOM large files

DICOM images should remain primarily in PACS / DICOM object storage.

## Observability

- OpenTelemetry
- Prometheus
- Grafana
- centralized logs

---

# 5. Multi-Tenant SaaS Model

## 5.1 Hierarchy

```text
SaaS Platform
|
+-- Company / Tenant
    |
    +-- Branch / Hospital
        |
        +-- Department
            |
            +-- User
```

Example:

```text
Company A
|
+-- Dhaka Hospital
|   +-- OPD
|   +-- IPD
|   +-- Pharmacy
|   +-- Laboratory
|   +-- Radiology
|   +-- Central Store
|
+-- Chattogram Hospital
    +-- OPD
    +-- Pharmacy
    +-- Laboratory
```

## 5.2 Tenant model

Use a shared application and initially a shared MySQL database with tenant-aware rows.

Tenant-owned tables should include:

```text
company_id
branch_id
```

when relevant.

Department-specific entities should additionally include:

```text
department_id
```

Do not blindly add all three columns to every table. Use only the scope relevant to the domain entity.

---

# 6. Company and Branch Context

## Company

Represents a customer/tenant.

Fields:

```text
id
code
name
legal_name
tax_id
status
timezone
currency
settings
created_at
updated_at
```

## Branch

Represents a hospital, clinic, diagnostic center, or physical site.

Fields:

```text
id
company_id
code
name
branch_type
address
phone
email
timezone
status
settings
created_at
updated_at
```

Constraints:

```text
branches.company_id -> companies.id
UNIQUE(company_id, code)
```

---

# 7. User Access Architecture

Do not rely only on `users.company_id`.

Recommended relationship tables:

```text
users
user_company_access
user_branch_access
user_department_access
roles
permissions
role_permissions
user_roles
```

This allows a user to work with:

- one company / one branch
- one company / multiple branches
- corporate company-level access
- multiple companies where explicitly allowed

A user's active context consists of:

```text
company
branch
department
role
```

---

# 8. Authentication and Authorization

## Login flow

```text
Login
 |
 v
Authenticate User
 |
 v
Resolve permitted companies
 |
 v
Resolve permitted branches
 |
 v
Select active context if necessary
 |
 v
Load dashboard
```

## Authorization

Use:

```text
RBAC + scope-aware authorization
```

Permission naming convention:

```text
patients.view
patients.create
patients.update

lab.orders.create
lab.samples.collect
lab.results.enter
lab.results.validate
lab.results.approve

inventory.items.view
inventory.stock.receive
inventory.stock.transfer
inventory.stock.adjust

pharmacy.prescriptions.view
pharmacy.dispensing.create

billing.invoice.create
billing.invoice.approve
billing.payment.receive
```

Authorization must be enforced through:

- Policies
- Gates where appropriate
- Middleware
- Domain-level permission checks for sensitive workflows

---

# 9. Application Folder Structure

Use a modular structure.

```text
app/
├── Core/
│   ├── Authentication/
│   ├── Authorization/
│   ├── Tenancy/
│   ├── Audit/
│   ├── Notifications/
│   ├── Files/
│   ├── Settings/
│   └── Support/
│
├── Modules/
│   ├── Organization/
│   ├── Patient/
│   ├── Encounter/
│   ├── OPD/
│   ├── IPD/
│   ├── EHR/
│   ├── CPOE/
│   ├── Pharmacy/
│   ├── Inventory/
│   ├── Procurement/
│   ├── Laboratory/
│   ├── Radiology/
│   ├── Billing/
│   ├── Accounting/
│   ├── Reporting/
│   └── Subscription/
│
├── Integrations/
│   ├── FHIR/
│   ├── HL7/
│   ├── DICOM/
│   ├── PACS/
│   ├── LaboratoryAnalyzers/
│   ├── SMS/
│   ├── Email/
│   └── Payments/
```

A typical module:

```text
Modules/Laboratory/
├── Actions/
├── DTOs/
├── Events/
├── Exceptions/
├── Jobs/
├── Models/
├── Policies/
├── Services/
├── Queries/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Database/
│   ├── Migrations/
│   └── Seeders/
└── Tests/
```

---

# 10. Core Modules

Implement modules in this order:

1. Organization
2. Authentication / RBAC / Tenancy
3. Patient / MPI
4. Encounter
5. Billing
6. OPD
7. IPD
8. EHR / CPOE
9. Inventory
10. Procurement
11. Pharmacy
12. Laboratory
13. Radiology
14. Notifications
15. Reporting
16. Subscription / SaaS administration
17. External integrations

---

# 11. Master Data Strategy

Separate master data into:

## Platform-level

- countries
- currencies
- standard units
- global terminology mappings
- standard codes
- FHIR mappings

## Company-level

- doctors
- services
- pricing
- suppliers
- insurance companies
- company policies
- medication catalog configuration
- test catalog configuration

## Branch-level

- wards
- beds
- stores
- pharmacy counters
- laboratories
- radiology rooms
- medical equipment
- local price overrides

---

# 12. Patient / MPI

## Primary patient entity

```text
patients
```

Fields:

```text
id
company_id
enterprise_patient_no
national_identifier
first_name
middle_name
last_name
date_of_birth
sex
blood_group
phone
email
address
emergency_contact
status
created_at
updated_at
```

Patient identity must be unique within a company according to configurable matching rules.

## Branch registration

```text
patient_branch_registrations
```

Fields:

```text
id
patient_id
company_id
branch_id
local_patient_no
registered_at
status
```

## Patient identity rule

Do not create a second patient for the same person merely because they visit another branch.

---

# 13. Encounter Model

```text
encounters
```

Fields:

```text
id
company_id
branch_id
patient_id
encounter_no
encounter_type
attending_doctor_id
department_id
started_at
ended_at
status
```

Encounter types:

```text
OPD
IPD
ER
LAB
RADIOLOGY
FOLLOW_UP
TELEMEDICINE
```

---

# 14. Clinical Order / CPOE

All clinical orders should have a unified architecture.

```text
clinical_orders
clinical_order_items
```

Orders may lead to:

- laboratory
- imaging
- medication
- procedures
- other services

The CPOE layer should not contain laboratory-specific processing logic.

---

# 15. Inventory Module

## Item master

```text
inventory_items
```

Required fields:

```text
id
company_id
item_code
name
generic_name
brand_name
category_id
item_type
manufacturer_id
base_uom_id
dispensing_uom_id
track_batch
track_expiry
track_serial
controlled_flag
reorder_level
reorder_qty
minimum_stock
maximum_stock
status
```

## Batch

```text
inventory_batches
```

Fields:

```text
id
company_id
item_id
batch_no
manufacture_date
expiry_date
supplier_id
purchase_rate
selling_rate
status
```

Unique:

```text
UNIQUE(item_id, batch_no)
```

## Stock

```text
inventory_stocks
```

Fields:

```text
id
company_id
branch_id
location_id
item_id
batch_id
quantity_on_hand
quantity_reserved
quantity_available
```

## Stock ledger

```text
inventory_transactions
```

Fields:

```text
id
company_id
branch_id
transaction_no
item_id
batch_id
location_id
transaction_type
quantity
unit_cost
reference_type
reference_id
transaction_datetime
performed_by
```

Transaction types:

```text
PURCHASE_RECEIPT
TRANSFER_IN
TRANSFER_OUT
DISPENSE
RETURN
WASTAGE
ADJUSTMENT
OPENING_BALANCE
STOCK_CORRECTION
```

Never update stock without a corresponding ledger entry.

---

# 16. Inventory Workflow

## Purchase

```text
Reorder Trigger
 -> Purchase Requisition
 -> Approval
 -> RFQ / Vendor Selection
 -> Purchase Order
 -> Goods Receipt
 -> Batch / Serial Capture
 -> QC
 -> Stock Posting
```

## Reorder

Use:

```text
Reorder Point =
Average Daily Consumption * Lead Time + Safety Stock
```

The reorder engine should consider:

- current available stock
- reserved stock
- open POs
- in-transit stock
- lead time
- minimum order quantity
- maximum stock
- expiry risk

## Stock transfer

```text
Transfer Request
 -> Approval
 -> Pick
 -> FEFO allocation
 -> Dispatch
 -> Receiving
 -> Confirm
```

Use separate ledger events for transfer out and transfer in.

## Expiry

Support:

- expiry dashboard
- 30/60/90 day alerts
- blocked expired stock
- quarantine
- near-expiry consumption suggestions
- disposal workflow

Use FEFO for appropriate medicines and consumables.

---

# 17. Procurement Module

Entities:

```text
suppliers
purchase_requisitions
purchase_requisition_items
purchase_orders
purchase_order_items
goods_receipts
goods_receipt_items
```

PO status:

```text
DRAFT
SUBMITTED
APPROVED
PARTIALLY_RECEIVED
FULLY_RECEIVED
CANCELLED
CLOSED
```

Approval thresholds should be configurable.

Example:

```text
< 50,000 -> Department Manager
50,000-250,000 -> Finance
> 250,000 -> Executive approval
```

Do not hard-code organization-specific thresholds.

---

# 18. Pharmacy

Core entities:

```text
medications
prescriptions
prescription_items
dispensing
dispensing_items
medication_returns
```

Workflow:

```text
Doctor Prescription
 -> Pharmacy Queue
 -> Clinical Check
 -> FEFO Batch Selection
 -> Dispense
 -> Inventory Ledger
 -> Billing Charge
 -> Medication History
```

Controlled substances require enhanced audit logging and configurable witness/approval rules.

---

# 19. Laboratory Module

## Test catalog

```text
lab_tests
```

Fields:

```text
id
company_id
test_code
name
department
specimen_type_id
container_type_id
loinc_code
tat_minutes
fasting_required
critical_enabled
price
status
```

## Diagnostic order

```text
diagnostic_orders
diagnostic_order_items
```

The order should originate from CPOE or authorized operational screens.

## Specimen

```text
specimens
```

Fields:

```text
id
company_id
branch_id
accession_no
patient_id
encounter_id
order_id
specimen_type_id
container_type_id
barcode
collected_at
collected_by
received_at
received_by
status
rejection_reason
```

Sample states:

```text
PENDING_COLLECTION
COLLECTED
IN_TRANSIT
RECEIVED
REJECTED
PROCESSING
COMPLETED
DISPOSED
```

---

# 20. Laboratory Result Model

```text
lab_results
lab_result_values
lab_reference_ranges
```

Result lifecycle:

```text
ORDERED
 -> COLLECTED
 -> RECEIVED
 -> PROCESSING
 -> RESULT_ENTERED
 -> TECHNICALLY_VALIDATED
 -> CLINICALLY_VALIDATED
 -> FINAL
```

A final report must not be overwritten.

Corrections use:

```text
Amendment
 -> Authorized approval
 -> New version
 -> Preserve original
 -> Audit
```

Reference ranges should support:

- age
- sex
- analyzer
- methodology
- specimen type
- effective dates
- units

---

# 21. Critical Lab Value Workflow

When a result meets critical criteria:

```text
Result
 -> Critical Value Engine
 -> Alert
 -> Ordering Doctor
 -> Nurse/Ward
 -> Lab Supervisor
```

Store:

```text
critical_alerts
```

Fields:

```text
id
result_id
rule_id
triggered_at
recipient
acknowledged_at
acknowledged_by
communication_method
escalation_level
status
```

Support escalation when alerts are not acknowledged.

---

# 22. Laboratory Analyzer Integration

Never connect every analyzer directly to the core HMS application.

Use:

```text
Analyzer
 -> Integration Engine / Middleware
 -> Normalize
 -> Validate
 -> Message/Event
 -> HMS Laboratory
```

Support as required:

- HL7 v2
- ASTM
- TCP/IP
- serial interfaces
- FHIR
- analyzer-specific adapters

Maintain an analyzer mapping table:

```text
equipment_mappings
```

Example:

```text
external_test_code = GLU_A1
internal_test_code = BLOOD_GLUCOSE
```

Analyzer-specific code must stay inside integration adapters, not inside the core laboratory domain.

---

# 23. Radiology Module

Workflow:

```text
Doctor
 -> Imaging Order
 -> Scheduling
 -> DICOM Modality Worklist
 -> Modality
 -> PACS
 -> Radiologist
 -> Report
 -> EHR
```

Entities:

```text
imaging_orders
imaging_studies
imaging_series
imaging_reports
```

Important identifiers:

```text
accession_number
study_instance_uid
series_instance_uid
sop_instance_uid
```

Do not store large DICOM objects in MySQL as the normal architecture.

Store metadata and references in HMS and images in PACS/object storage.

---

# 24. PACS / DICOM Integration

Provide adapter interfaces such as:

```text
PacsClient
DicomWorklistClient
DicomStudyClient
ImagingReportClient
```

Potential capabilities:

```text
Modality Worklist
C-STORE
C-FIND
C-MOVE
DICOMweb
```

Keep vendor-specific implementation outside the Radiology domain.

---

# 25. Billing Integration

Billing must remain a separate domain.

Operational modules generate charge events.

Examples:

```text
DiagnosticOrderFulfilled
MedicationDispensed
ImagingCompleted
ProcedureCompleted
```

These generate charge records.

Example:

```text
LAB_ORDER
 -> Chargeable Service
 -> Invoice Line
```

Medication:

```text
DISPENSE
 -> Chargeable Item
 -> Invoice Line
```

Do not directly manipulate invoices from Inventory or Laboratory controllers.

---

# 26. Financial Transaction Integrity

For critical operations:

```php
DB::transaction(function () {
    // validate
    // lock required rows
    // perform domain action
    // write ledger
    // create charge/event
});
```

Example medication dispensing transaction:

```text
Validate prescription
 -> Lock stock
 -> Select batch
 -> Create dispensing
 -> Create inventory transaction
 -> Create billing charge
 -> Commit
```

Rollback everything on failure.

---

# 27. Audit Logging

Create a centralized audit system.

```text
audit_logs
```

Suggested fields:

```text
id
company_id
branch_id
user_id
role_id
timestamp
ip_address
device_id
session_id
module
entity_type
entity_id
action
old_values
new_values
reason
correlation_id
```

Audit events must include at least:

```text
CREATE
UPDATE
DELETE
APPROVE
REJECT
CANCEL
DISPENSE
TRANSFER
ADJUST
EXPORT
PRINT
LOGIN
LOGOUT
BREAK_GLASS
```

Sensitive clinical records and controlled drugs require enhanced auditing.

---

# 28. Security Requirements

Implement:

- TLS
- encrypted database/storage where required
- Argon2id password hashing
- MFA support
- OIDC/OAuth2 where required
- short-lived access tokens
- refresh-token rotation
- session/device tracking
- CSRF protection
- rate limiting
- secure headers
- validation and output encoding
- secrets management
- least privilege
- centralized audit logs

Never place secrets directly in source code.

---

# 29. Break-Glass Access

Support emergency access for restricted clinical data.

Workflow:

```text
Request emergency access
 -> Require reason
 -> Temporary elevated access
 -> Log action
 -> Security notification
 -> Automatic expiration
```

Break-glass access must not bypass audit logging.

---

# 30. Compliance-Oriented Design

The software should be capable of being operated under applicable healthcare/privacy requirements.

Design for:

- HIPAA Security Rule style controls where relevant
- GDPR principles where applicable
- local healthcare/cybersecurity law
- health data confidentiality
- auditability
- consent where required
- data retention policies
- access controls
- breach investigation
- secure export/deletion processes

Do not claim regulatory compliance solely because the application implements security features. Compliance must be assessed for the actual organization, jurisdiction, hosting arrangement, policies, contracts, and operations.

---

# 31. FHIR Architecture

Use FHIR as an interoperability boundary.

Potential resources:

```text
Patient
Encounter
Practitioner
Organization
ServiceRequest
Specimen
Observation
DiagnosticReport
ImagingStudy
MedicationRequest
MedicationDispense
Medication
Invoice
```

Do not make internal business entities depend directly on FHIR's database representation.

Use:

```text
Internal Domain Model
        |
        v
FHIR DTO / Mapper
        |
        v
FHIR Resource
```

---

# 32. HL7 Architecture

Support HL7 v2 through the integration layer.

Possible use cases:

- lab orders
- lab results
- ADT
- patient demographics
- scheduling

Example:

```text
Hospital Device / LIS
 -> HL7 Parser
 -> Validation
 -> Internal Command
 -> Domain Service
```

Never place raw HL7 parsing inside ordinary business controllers.

---

# 33. API Standards

Use:

```text
/api/v1/...
```

Examples:

```text
GET    /api/v1/patients
POST   /api/v1/patients

GET    /api/v1/diagnostic-orders
POST   /api/v1/diagnostic-orders

GET    /api/v1/specimens/{id}
POST   /api/v1/specimens/{id}/collect

POST   /api/v1/lab-results/{id}/validate
POST   /api/v1/lab-results/{id}/approve

GET    /api/v1/inventory/items
POST   /api/v1/inventory/transfers
POST   /api/v1/pharmacy/dispensing

GET    /api/v1/invoices
POST   /api/v1/payments
```

API responses should use consistent envelopes or consistent REST conventions throughout the application.

---

# 34. Idempotency

All external or retryable integration endpoints should support idempotency.

Examples:

```text
Idempotency-Key
External Message ID
External Order ID
```

A repeated analyzer message must not create duplicate results.

A repeated payment notification must not create duplicate payments.

A repeated webhook must not create duplicate transactions.

---

# 35. Event-Driven Architecture

Define domain events such as:

```text
PatientRegistered
EncounterCreated
DiagnosticOrderCreated
SpecimenCollected
SpecimenReceived
LabResultEntered
LabResultValidated
CriticalResultDetected
DiagnosticReportFinalized
MedicationDispensed
StockBelowReorderPoint
PurchaseOrderApproved
GoodsReceived
InvoiceGenerated
PaymentReceived
```

Use listeners/jobs for secondary processing.

Do not make a core transaction depend synchronously on every notification/reporting integration.

---

# 36. Queue Architecture

Use Redis for application cache and/or queue where suitable.

Use RabbitMQ for durable integration messaging when required.

Suggested queues:

```text
default
notifications
reports
integration
laboratory
radiology
billing
imports
exports
```

Critical events should have retry and dead-letter strategies.

---

# 37. Notifications

Central notification domain:

```text
notifications
notification_templates
notification_deliveries
```

Channels:

```text
in-app
email
SMS
push
WhatsApp where contractually/technically supported
```

Support:

- retries
- delivery status
- priority
- templates
- localization
- escalation

Critical lab alerts must have stronger delivery/acknowledgment controls than normal notifications.

---

# 38. Reporting

Do not run complex BI queries directly against the transactional schema.

Architecture:

```text
Operational DB
 -> ETL / CDC
 -> Reporting DB / Warehouse
 -> BI
```

Dashboards:

## Inventory

- stock value
- expiry risk
- stock-outs
- consumption
- slow-moving stock
- supplier performance

## Laboratory

- test volume
- turnaround time
- pending samples
- rejected samples
- critical values
- analyzer utilization

## Radiology

- study volume
- report TAT
- modality utilization
- pending reports

## Business

- revenue
- outstanding invoices
- patient volume
- branch performance
- service profitability

---

# 39. Subscription and SaaS Administration

Core entities:

```text
plans
plan_features
company_subscriptions
company_features
usage_metrics
```

Examples of feature codes:

```text
OPD
IPD
PHARMACY
INVENTORY
PROCUREMENT
LABORATORY
RADIOLOGY
PACS
BILLING
ACCOUNTING
API
PATIENT_PORTAL
```

Support feature limits:

```text
max_branches
max_users
max_patients
max_storage
```

The application must enforce subscription limits centrally.

---

# 40. SaaS Admin Portal

Platform-level functions:

```text
Companies
Subscriptions
Plans
Features
Global Masters
Platform Users
Integration Configurations
System Audit
Usage
Platform Monitoring
```

Customer-level administration:

```text
Company Settings
Branches
Departments
Users
Roles
Local Masters
Pricing
Clinical Configuration
Inventory Configuration
Lab Configuration
Radiology Configuration
```

---

# 41. Settings Architecture

Avoid hard-coding configurable hospital behavior.

Create a configuration service backed by database settings.

Examples:

```text
patient.number.format
invoice.number.format
lab.accession.format
default_currency
timezone
critical_alert.timeout
inventory.expiry.days
stock.adjustment.approval.required
```

Use typed configuration/value objects where possible.

---

# 42. Database Rules

Use:

- foreign keys
- unique constraints
- check constraints where useful
- indexes based on query patterns
- composite indexes for tenant-scoped queries
- soft deletes only where semantically appropriate
- immutable ledger patterns for financial/stock history

Typical indexes:

```text
(company_id, branch_id)
(company_id, patient_no)
(company_id, order_no)
(company_id, accession_no)
(company_id, item_code)
(company_id, batch_no)
```

Do not create indexes blindly; verify through query plans and real workloads.

---

# 43. Data Migration Strategy

Every migration must:

- be reversible where practical
- avoid locking huge tables unnecessarily
- include indexes intentionally
- handle production data
- preserve historical records
- be tested against realistic sample data

For large tables, use phased migrations:

```text
Add nullable column
 -> backfill
 -> validate
 -> add constraint
 -> make non-null if required
```

---

# 44. Coding Standards

Follow Laravel conventions unless the project has an explicitly documented exception.

Use:

- PSR-12
- strict typing where practical
- typed properties
- return types
- PHPDoc only where it adds meaningful information
- meaningful names
- small cohesive classes
- dependency injection
- immutable DTOs where appropriate

Avoid:

- huge controllers
- static helper abuse
- god classes
- hidden side effects
- duplicated business logic
- raw SQL when Eloquent/query builder is sufficient
- business logic in Blade/Blade components

---

# 45. Frontend Architecture

Use feature-based frontend modules.

```text
src/
├── modules/
│   ├── patient/
│   ├── opd/
│   ├── ipd/
│   ├── pharmacy/
│   ├── inventory/
│   ├── laboratory/
│   ├── radiology/
│   ├── billing/
│   └── administration/
├── components/
├── layouts/
├── hooks/
├── services/
├── api/
├── stores/
└── types/
```

Role-aware navigation must be generated from backend-authorized capabilities, not merely hidden in the frontend.

---

# 46. Clinical UI Principles

Clinical screens must optimize for:

- speed
- accuracy
- low cognitive load
- keyboard accessibility
- barcode workflows
- status visibility
- minimal duplicate data entry

Examples:

Lab collection:

```text
Scan Patient
 -> Scan Sample
 -> Confirm Test
 -> Collect
 -> Print Label
```

Pharmacy:

```text
Scan Prescription
 -> Verify
 -> Scan Medication
 -> Batch Selection
 -> Dispense
```

Inventory:

```text
Scan Item
 -> Batch
 -> Quantity
 -> Source
 -> Destination
 -> Confirm
```

---

# 47. Error Handling

Use domain-specific exceptions.

Examples:

```text
InsufficientStockException
ExpiredBatchException
InvalidTenantContextException
UnauthorizedBranchAccessException
FinalizedResultModificationException
DuplicateExternalMessageException
InvoiceAlreadyPaidException
```

Return safe API errors without exposing internal stack traces.

---

# 48. Logging

Log:

- authentication failures
- authorization failures
- external integration failures
- job failures
- payment errors
- critical clinical workflow errors
- tenant resolution errors

Never log:

- passwords
- access tokens
- full sensitive health data unless explicitly required and protected
- secrets
- unnecessary patient identifiers

---

# 49. Observability

Use correlation IDs across requests and async jobs.

Example:

```text
COR-20260910-98271
```

Trace:

```text
Clinical Order
 -> Billing Charge
 -> Lab Sample
 -> Analyzer Message
 -> Result
 -> Report
 -> Notification
```

Metrics should include:

- API latency
- queue depth
- failed jobs
- database latency
- integration failures
- analyzer message throughput
- critical alert delivery

---

# 50. Backup and Disaster Recovery

Use:

```text
Primary
 -> Database backup/replica
 -> Object storage backup
 -> DR environment
 -> Offline/immutable backup
```

Configure:

- RPO
- RTO
- backup encryption
- retention
- restore testing

Do not claim disaster readiness without performing restore tests.

---

# 51. Testing Strategy

Testing layers:

```text
Unit
Integration
Feature
API
Authorization
Tenant Isolation
Workflow
End-to-End
Performance
Security
```

Mandatory tenant test:

```text
User from Company A
MUST NOT access Company B data
```

Mandatory branch test:

```text
User from Branch A
MUST NOT access Branch B data
unless explicitly granted
```

Mandatory inventory test:

```text
Dispense 10
 -> stock decreases by 10
 -> ledger created
 -> charge created
 -> all rollback on failure
```

Mandatory lab test:

```text
Finalized result
 -> direct update rejected
 -> amendment workflow required
```

---

# 52. AI Prompt Protocol

When asking an AI coding agent to implement a feature, use this structure:

```text
You are a Senior Laravel Architect and Healthcare Software Engineer.

PROJECT:
Multi-tenant SaaS HMS.

CONTEXT:
[describe module and current state]

TASK:
[one specific task]

BUSINESS RULES:
[list rules]

TENANCY:
Company and branch isolation is mandatory.

SECURITY:
RBAC and policy checks are mandatory.

DATABASE:
Use existing conventions and preserve referential integrity.

API:
Follow /api/v1 conventions.

TESTING:
Create unit + feature + authorization + tenant isolation tests.

DO NOT:
- modify unrelated modules
- bypass authorization
- duplicate existing services
- overwrite finalized clinical records
- manipulate stock without ledger entries

DELIVER:
1. Analysis of existing implementation
2. Proposed changes
3. Database changes
4. Backend implementation
5. API changes
6. Frontend changes
7. Tests
8. Migration/rollback notes
9. Security considerations
10. Files changed
```

---

# 53. AI Coding Workflow

For every development task:

```text
STEP 1
Inspect existing code.

STEP 2
Identify affected module boundaries.

STEP 3
Explain proposed implementation briefly.

STEP 4
Implement database changes.

STEP 5
Implement domain/application logic.

STEP 6
Implement authorization.

STEP 7
Implement APIs.

STEP 8
Implement UI.

STEP 9
Add tests.

STEP 10
Run formatter/static checks/tests.

STEP 11
Review tenant isolation.

STEP 12
Review security and audit implications.

STEP 13
Summarize changed files and remaining risks.
```

Never ask the coding AI to rewrite the whole project for a small feature.

---

# 54. Definition of Done

A feature is complete only when:

- requirements are implemented
- migration exists
- model relationships are correct
- validation exists
- tenant isolation exists
- authorization exists
- business logic is testable
- API is implemented where required
- UI is implemented where required
- audit requirements are handled
- error handling is implemented
- tests pass
- no unrelated code was broken
- documentation is updated if architecture or APIs changed

---

# 55. Development Milestones

## Milestone 1 — Platform Foundation

Deliver:

- Laravel application
- MySQL
- Redis
- authentication
- tenancy
- company
- branch
- department
- RBAC
- permissions
- audit logs
- settings
- API versioning
- base UI layout

## Milestone 2 — Patient & Encounter

Deliver:

- MPI
- patient registration
- duplicate detection
- branch registration
- OPD
- encounter lifecycle

## Milestone 3 — Billing

Deliver:

- services
- pricing
- invoice
- invoice lines
- payments
- refunds
- charge engine

## Milestone 4 — Inventory & Procurement

Deliver:

- item master
- categories
- suppliers
- warehouses/stores
- batches
- expiry
- stock ledger
- PO
- GRN
- transfers
- reorder

## Milestone 5 — Pharmacy

Deliver:

- medication master
- prescriptions
- dispensing
- returns
- FEFO
- controlled substances

## Milestone 6 — Laboratory

Deliver:

- test catalog
- diagnostic orders
- specimens
- barcode
- lab worklist
- manual result entry
- validation
- approval
- critical values
- report generation

## Milestone 7 — Radiology

Deliver:

- imaging orders
- scheduling
- DICOM worklist integration
- PACS hooks
- radiology reports

## Milestone 8 — External Integration

Deliver:

- FHIR
- HL7
- analyzer adapters
- SMS/email
- optional payment integrations

## Milestone 9 — SaaS Commercialization

Deliver:

- subscription plans
- feature flags
- usage tracking
- tenant onboarding
- billing for SaaS customers
- platform admin

---

# 56. Initial Database Domains

Create migrations in dependency order:

```text
companies
branches
departments

users
roles
permissions
user_company_access
user_branch_access
user_department_access
user_roles
role_permissions

patients
patient_branch_registrations
encounters

services
service_prices
charges
invoices
invoice_items
payments
refunds

inventory_categories
inventory_items
inventory_locations
inventory_batches
inventory_stocks
inventory_transactions

suppliers
purchase_requisitions
purchase_requisition_items
purchase_orders
purchase_order_items
goods_receipts
goods_receipt_items

medications
prescriptions
prescription_items
dispensings
dispensing_items

lab_test_categories
lab_tests
lab_reference_ranges
diagnostic_orders
diagnostic_order_items
specimens
lab_results
lab_result_values
critical_alerts
diagnostic_reports

imaging_orders
imaging_studies
imaging_reports
diagnostic_equipment
equipment_mappings

audit_logs
notifications
notification_deliveries

plans
plan_features
company_subscriptions
company_features
```

---

# 57. Important Database Relationships

```text
companies
  |
  +-- branches
  |     |
  |     +-- departments
  |     +-- inventory_locations
  |     +-- diagnostic_equipment
  |
  +-- patients
  |     |
  |     +-- encounters
  |           |
  |           +-- diagnostic_orders
  |           +-- prescriptions
  |           +-- imaging_orders
  |           +-- charges
  |
  +-- inventory_items
  |     |
  |     +-- inventory_batches
  |     +-- inventory_stocks
  |     +-- inventory_transactions
  |
  +-- suppliers
  |
  +-- lab_tests
  |
  +-- invoices
```

---

# 58. Important Domain Events

Standardize event naming.

```text
PatientRegistered
EncounterCreated
DiagnosticOrderPlaced
SpecimenCollected
SpecimenReceived
LabResultEntered
LabResultValidated
CriticalResultDetected
DiagnosticReportFinalized
ImagingStudyCompleted
MedicationDispensed
StockTransferred
StockAdjusted
StockBelowReorderPoint
PurchaseOrderApproved
GoodsReceived
InvoiceGenerated
PaymentReceived
```

Events should contain identifiers rather than unnecessarily large objects.

Example:

```php
final readonly class MedicationDispensed
{
    public function __construct(
        public int $companyId,
        public int $branchId,
        public int $dispensingId,
        public int $patientId,
        public int $performedBy,
        public string $correlationId,
    ) {}
}
```

---

# 59. Example AI Task — Inventory Transfer

Use this pattern for AI coding.

```text
Implement Inventory Stock Transfer.

Requirements:
- User creates transfer request from one permitted location to another.
- Source and destination must belong to the same company.
- User must have permission for both locations.
- Approval may be required based on company settings.
- Batch-controlled products must use FEFO unless authorized override is enabled.
- Transfer-out and transfer-in must be represented in the stock ledger.
- Stock cannot become negative.
- The operation must be transactional.
- Every action must be audited.
- Generate an event after successful completion.
- Add unit, feature, authorization and tenant isolation tests.

First inspect the existing Inventory module and reuse established architecture.
Do not modify unrelated modules.
```

---

# 60. Example AI Task — Laboratory Result Approval

```text
Implement Laboratory Result Approval.

Rules:
- Only authorized Lab Supervisor/Pathologist can approve.
- Result must be technically validated first.
- Rejected or incomplete results cannot be approved.
- Approval creates an immutable final version.
- Final result cannot be edited directly.
- Critical result detection must occur before finalization.
- Critical alert must be delivered and recorded.
- EHR visibility begins according to result publication rules.
- Create audit record.
- Dispatch DiagnosticReportFinalized event.
- Add authorization, workflow, tenant isolation and versioning tests.
```

---

# 61. Example AI Task — Multi-Company Security

```text
Audit the application for cross-tenant data leakage.

Check:
- controllers
- API endpoints
- model scopes
- relationships
- policies
- jobs
- exports
- reports
- search
- notifications
- background workers

Verify that a Company A user cannot read or mutate Company B data.

Also verify branch-level isolation.

Report:
1. vulnerability
2. affected file
3. exploit scenario
4. recommended fix
5. tests required

Do not modify code until the findings are summarized.
```

---

# 62. Performance Guidelines

Use:

- eager loading
- pagination
- cursor pagination for large datasets
- database indexes
- caching for stable master data
- queues for expensive tasks
- batch imports
- bulk inserts where safe
- chunking for large exports

Avoid:

```text
N+1 queries
large unbounded queries
synchronous PDF generation in request cycles
synchronous external analyzer processing
```

---

# 63. Data Import / Export

Support controlled import of:

- patient master
- medication master
- inventory opening balance
- supplier master
- lab test master
- pricing
- users

Imports should have:

```text
Upload
 -> Validate
 -> Preview
 -> Approve
 -> Process
 -> Report errors
```

Never directly import raw spreadsheets into production tables without validation.

---

# 64. Barcode Architecture

Use barcode support for:

- patients
- specimens
- medicines
- inventory items
- batches
- assets
- locations

Barcode values should have unique business identifiers and not expose sensitive patient information.

---

# 65. Search Architecture

Search should be tenant-scoped.

Patient search may use:

```text
enterprise_patient_no
local_patient_no
name
phone
date_of_birth
national_identifier where legally permitted
```

Search results must always apply tenant and branch permissions.

For high scale, introduce Elasticsearch/OpenSearch only after MySQL search is no longer sufficient.

---

# 66. File and Document Architecture

Create a centralized file abstraction.

```text
Document
 -> Storage disk
 -> metadata
 -> owner entity
 -> access policy
 -> retention policy
 -> audit
```

Never expose raw object-storage URLs to unauthorized users.

Use signed, temporary URLs where appropriate.

---

# 67. PDF Reporting

Clinical reports should be versioned.

Example:

```text
Report #LAB-20260910-00124
Version 1
Status FINAL
```

For an amendment:

```text
Version 2
Amended
Reason: correction of reference range
Approved by: authorized user
```

The original version remains retrievable according to policy.

---

# 68. Localization

Design for:

- English
- Bangla
- future additional languages

Store translatable labels and templates separately.

Do not hard-code clinical or financial terminology into JavaScript components.

---

# 69. Date, Time and Number Rules

Store timestamps consistently, preferably UTC in backend storage, and render using tenant/branch timezone.

All important documents must display the appropriate local timezone where required.

Use configurable formats for:

- patient numbers
- invoice numbers
- accession numbers
- purchase order numbers
- transaction numbers

---

# 70. Healthcare Data Boundaries

Separate:

```text
Identity Data
Clinical Data
Financial Data
Operational Data
Audit Data
Integration Data
```

Do not give broad "patient read" access to every role.

For example:

```text
Billing
 -> demographics needed for billing
 -> financial data
 -> no unrestricted clinical notes

Pharmacist
 -> medication + relevant allergy/interaction information
 -> no unrestricted laboratory narrative if not required

Lab technician
 -> specimen + required clinical context
 -> no financial details
```

Apply minimum-necessary access principles where applicable.

---

# 71. Final Architecture Target

```text
                         SaaS Platform
                               |
                    +----------+----------+
                    |                     |
               Platform Admin       Tenant HMS
                                          |
                                  Company / Tenant
                                          |
                           +--------------+--------------+
                           |                             |
                       Branch A                       Branch B
                           |                             |
                  +--------+--------+           +--------+--------+
                  |        |        |           |        |        |
                 OPD      IPD      Pharmacy     OPD      Lab    Radiology
                  |        |        |                    |
                  +--------+--------+                    |
                           |                             |
                           +-------------+---------------+
                                         |
                                      Clinical
                                         |
                               CPOE / EHR / Encounter
                                         |
                   +---------------------+---------------------+
                   |                     |                     |
                Laboratory           Pharmacy             Radiology
                   |                     |                     |
              Analyzer/Middleware    Inventory                PACS
                   |                     |
                   +----------+----------+
                              |
                           Billing
                              |
                         Accounting
                              |
                       Reporting / BI

                   Integration Platform
             FHIR / HL7 v2 / DICOM / REST
```

---

# 72. Master Prompt for AI Coding Agents

Copy this prompt into your coding agent at the beginning of the project:

```text
You are the Principal Software Architect and Senior Healthcare Software Engineer for a multi-tenant SaaS Hospital Management System.

STACK:
- Laravel / PHP
- MySQL
- Redis
- Blade
- RabbitMQ for integration messaging where needed
- FHIR / HL7 v2 / DICOM integration
- Modular monolith architecture

BUSINESS MODEL:
- SaaS platform
- Multiple companies/tenants
- Multiple branches/hospitals per company
- Multiple departments
- Users may have scoped access to one or more companies/branches/departments

CORE MODULES:
Organization
Authentication
RBAC
Patient/MPI
Encounter
OPD
IPD
EHR
CPOE
Pharmacy
Inventory
Procurement
Laboratory
Radiology
Billing
Accounting
Reporting
Notifications
Subscription
FHIR/HL7/DICOM integrations

NON-NEGOTIABLE RULES:
1. Tenant isolation is mandatory.
2. Never trust tenant identifiers from the client.
3. Every sensitive operation requires authorization.
4. Use database transactions for critical workflows.
5. Never modify stock without a ledger transaction.
6. Never overwrite finalized clinical results.
7. Clinical reports must support versioning/amendments.
8. Financial records must be auditable.
9. Controlled substances require enhanced auditability.
10. External integrations must be idempotent.
11. Do not expose sensitive data unnecessarily.
12. Do not put business logic in controllers.
13. Do not create duplicate services or models.
14. Do not modify unrelated code.
15. Every feature must include automated tests.
16. All APIs use versioning.
17. Audit logs must be generated for sensitive actions.
18. PACS/DICOM images belong in PACS/object storage, not ordinary relational tables.
19. Analyzer-specific logic belongs in integration adapters.
20. Preserve backward compatibility where existing APIs are already in use.

DEVELOPMENT METHOD:
Before changing code:
- inspect the repository
- identify affected modules
- identify reusable components
- explain the proposed implementation

Then:
- implement migrations
- implement domain/application logic
- implement policies/authorization
- implement APIs
- implement frontend
- implement events/jobs
- add tests
- run static analysis/formatters
- run relevant tests
- perform a tenant isolation review
- summarize changed files

When requirements are ambiguous:
- choose the safest enterprise architecture
- make reasonable assumptions
- document assumptions
- do not block progress unnecessarily

QUALITY STANDARD:
Write production-quality code suitable for a hospital environment.
Prioritize correctness, auditability, security, maintainability, interoperability, and scalability over speed of implementation.
```

---

# 73. AI Development Checklist

For every pull request:

```text
[ ] Tenant isolation reviewed
[ ] Company scope reviewed
[ ] Branch scope reviewed
[ ] Department scope reviewed
[ ] RBAC reviewed
[ ] Policy tests added
[ ] Validation added
[ ] Audit logging reviewed
[ ] Database constraints reviewed
[ ] Transaction boundaries reviewed
[ ] Idempotency reviewed
[ ] API versioning reviewed
[ ] Error handling reviewed
[ ] Tests added
[ ] Performance considered
[ ] Security reviewed
[ ] Documentation updated