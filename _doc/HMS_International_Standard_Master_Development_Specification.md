# International Standard Hospital Management System (HMS)
## Master Software Development Specification for AI-Assisted Development

**Document Version:** 1.0  
**Target Stack:** Laravel + MySQL + Redis + Blade/AdminLTE + REST/FHIR APIs  
**Architecture:** Modular Monolith, API-first, integration-ready  
**Purpose:** Master specification for designing, developing, testing, deploying, and maintaining an international-standard Hospital Management System.

---

# 1. Project Vision

Build a secure, scalable, modular Hospital Management System (HMS) capable of supporting:

- Single hospital
- Multi-branch hospitals
- Multi-hospital healthcare groups
- OPD
- Emergency
- IPD
- ICU
- OT
- Pharmacy
- Laboratory
- Radiology
- Blood Bank
- Nursing
- EMR
- Billing
- Insurance
- Procurement
- Inventory
- HR/Payroll
- Finance
- Patient Portal
- Doctor/Nurse portals
- Mobile applications
- Telemedicine
- Healthcare interoperability
- Analytics/BI
- AI-assisted functionality

The system must be designed for maintainability, security, interoperability, auditability, and future expansion.

---

# 2. Development Principles

The AI/development team MUST follow these principles:

1. Do not build the whole system in one step.
2. Build one module at a time.
3. Never skip database design.
4. Never create duplicate business logic.
5. Keep business rules inside appropriate service/domain classes.
6. Controllers should remain thin.
7. Use Form Requests for validation.
8. Use Policies/Gates for authorization.
9. Use Jobs/Queues for long-running processes.
10. Use Events/Listeners where asynchronous processing is appropriate.
11. Use database transactions for financial and clinical operations that require atomicity.
12. Maintain complete audit trails for sensitive actions.
13. Never hard-code hospital, branch, department, tax, currency, or workflow assumptions.
14. Design for multi-hospital use from the beginning.
15. Keep clinical data immutable where legally/operationally appropriate; use amendments rather than destructive edits.
16. Use soft deletion only where appropriate.
17. Never expose sensitive patient information through unauthorized APIs, logs, URLs, or notifications.
18. All external integrations must be isolated behind service/adaptor interfaces.
19. All important functionality must have automated tests.
20. Documentation must be updated with each module.

---

# 3. Recommended Technology Stack

## Backend

- PHP 8.4+
- Laravel current stable release
- Laravel Eloquent ORM
- Laravel Queues
- Laravel Scheduler
- Laravel Events/Listeners
- Laravel Notifications
- Laravel Policies
- Laravel Sanctum or Passport as appropriate

## Database

Primary recommendation:

- MySQL

Use:
- Foreign keys
- Proper indexes
- Unique constraints
- Check constraints where supported
- Transactions
- Database views where appropriate
- Carefully designed audit/history tables

## Frontend

Initial:

- Blade
- AdminLTE
- Bootstrap
- Alpine.js where useful

Advanced interactive screens may use:
- Vue.js or React

Do not introduce a SPA everywhere unless there is a clear requirement.

## Infrastructure

- Linux
- Nginx
- PHP-FPM
- Redis
- Supervisor
- Cron
- Docker support
- Object storage compatible with S3
- HTTPS/TLS

## Monitoring

Support integration with:

- Application logs
- Centralized logs
- Prometheus/Grafana or equivalent
- Error monitoring
- Server monitoring
- Queue monitoring

---

# 4. High-Level Architecture

Use a modular monolith initially.

```text
                    ┌──────────────────────┐
                    │     Patient Portal   │
                    └──────────┬───────────┘
                               │
┌──────────────┐      ┌────────▼────────┐      ┌──────────────┐
│ Doctor App   │─────►│   API Layer     │◄─────│ Nurse App    │
└──────────────┘      └────────┬────────┘      └──────────────┘
                               │
                      ┌────────▼────────┐
                      │    HMS Core     │
                      ├─────────────────┤
                      │ Patient / MPI   │
                      │ Appointment     │
                      │ OPD / IPD       │
                      │ Emergency       │
                      │ EMR             │
                      │ Pharmacy        │
                      │ Laboratory      │
                      │ Radiology       │
                      │ OT / ICU        │
                      │ Billing         │
                      │ Inventory       │
                      │ HR / Finance    │
                      └────────┬────────┘
                               │
                    ┌──────────▼──────────┐
                    │ Integration Layer   │
                    ├─────────────────────┤
                    │ FHIR │ HL7 │ DICOM │
                    │ LIS  │ PACS│ Payment│
                    │ SMS  │ Email│Insurance
                    └─────────────────────┘
```

---

# 5. Core Modules

Implement the following modules as separate logical domains:

1. Core Administration
2. Organization/Hospital
3. Patient & Master Patient Index
4. Appointment
5. OPD
6. Emergency
7. IPD/Admission
8. Bed Management
9. Nursing
10. Doctor/Physician
11. EMR
12. e-Prescription
13. Pharmacy
14. Laboratory/LIS
15. Radiology/RIS
16. PACS/DICOM Integration
17. Operation Theatre
18. ICU
19. Blood Bank
20. Diet & Nutrition
21. Ambulance
22. Billing
23. Insurance
24. Corporate/Credit Patient
25. Accounts & Finance
26. Procurement
27. Inventory
28. Medical Asset Management
29. HR
30. Payroll
31. Document Management
32. Consent Management
33. Infection Control
34. Quality & Patient Safety
35. Compliance & Accreditation
36. Telemedicine
37. Patient Portal
38. Doctor Portal
39. Nurse Portal
40. Notification Engine
41. Workflow/Approval Engine
42. Reporting
43. BI/Analytics
44. API/Integration
45. Security
46. Audit
47. AI Services

---

# 6. Phase-Based Development Strategy

Do NOT implement all modules simultaneously.

## Phase 0 — Foundation

Build:

- Laravel project
- Environment configuration
- Database
- Authentication
- Authorization
- Organization structure
- Hospital
- Branch
- Department
- User
- Role
- Permission
- Audit framework
- Activity logging
- File storage
- Notification framework
- Queue infrastructure
- Common master-data framework
- API foundation
- Error handling
- Testing framework

Deliverable:

A production-ready HMS foundation.

---

# 7. Phase 1 — Patient Core

Build:

- Patient registration
- MRN/UHID
- Patient profile
- Demographics
- Contact
- Emergency contact
- Next of kin
- Identification documents
- Allergies
- Medical history
- Patient search
- Duplicate detection
- Patient merge
- Patient timeline

Required screens:

- Patient dashboard
- Register patient
- Edit patient
- Patient search
- Patient profile
- Patient timeline
- Patient documents
- Patient history

---

# 8. Phase 2 — Appointment + OPD

Build:

- Doctor schedules
- Appointment slots
- Appointment booking
- Walk-in
- Queue
- Token
- Check-in
- OPD consultation
- Vital signs
- Diagnosis
- Clinical notes
- Prescription
- Investigation order
- Follow-up

Workflow:

```text
Appointment
    ↓
Check-in
    ↓
Queue
    ↓
Doctor Consultation
    ↓
Diagnosis
    ↓
Investigation / Prescription
    ↓
Billing
    ↓
Follow-up
```

---

# 9. Phase 3 — Billing

Build:

- Service catalog
- Charge master
- Price lists
- Patient billing
- OPD billing
- Payment
- Refund
- Discount
- Tax
- Advance
- Receipt
- Invoice
- Cashier closing
- Financial audit trail

Important rule:

Every bill-changing operation must be traceable.

Do not permanently delete financial transactions.

---

# 10. Phase 4 — IPD + Bed Management

Build:

- Admission
- Admission approval
- Bed allocation
- Room management
- Bed transfer
- Consultant assignment
- Daily progress
- Nursing notes
- Patient charges
- Discharge planning
- Discharge
- Final billing

Bed states:

- Available
- Reserved
- Occupied
- Cleaning
- Maintenance
- Isolation
- Blocked

---

# 11. Phase 5 — Pharmacy

Build:

- Drug master
- Generic
- Brand
- Batch
- Expiry
- Purchase
- Receiving
- Stock
- Dispensing
- Return
- Transfer
- Adjustment
- Reorder
- Pharmacy billing

Use FEFO where appropriate for medicines.

---

# 12. Phase 6 — Laboratory

Build:

- Test master
- Test packages
- Sample types
- Orders
- Sample collection
- Barcode
- Sample tracking
- Result entry
- Verification
- Approval
- Critical result alerts
- Reference ranges
- Lab reports

Architecture must allow:

- LIS integration
- HL7
- ASTM
- Analyzer integration

---

# 13. Phase 7 — Radiology

Build:

- Radiology orders
- Scheduling
- Modality
- Technician assignment
- Radiologist assignment
- Reporting
- Report approval
- Critical findings
- Imaging history

Integration-ready for:

- DICOM
- PACS
- RIS

---

# 14. Phase 8 — Emergency

Build:

- Emergency registration
- Triage
- Severity
- Emergency queue
- Emergency consultation
- Emergency procedures
- Emergency medication
- Emergency investigations
- Emergency bed
- Admission from ER
- Discharge
- Referral
- Ambulance

---

# 15. Phase 9 — Nursing

Build:

- Nursing station
- Patient assignment
- Nurse assignment
- Vital signs
- Nursing assessment
- Nursing care plan
- Intake/output
- Medication administration
- Nursing notes
- Shift handover
- Nursing tasks
- Alerts

---

# 16. Phase 10 — OT + ICU

## OT

- OT scheduling
- Pre-op checklist
- Consent
- Surgical safety checklist
- Anesthesia
- Procedure
- Operation notes
- Implants
- Supplies
- Recovery

## ICU

- ICU beds
- Vital monitoring
- Ventilator data
- Medication
- Fluid balance
- ICU notes
- Critical care scoring
- ICU rounds

---

# 17. Phase 11 — Blood Bank

Build:

- Donor
- Blood collection
- Blood group
- Component separation
- Inventory
- Cross-match
- Issue
- Return
- Transfusion
- Reaction
- Wastage
- Expiry

---

# 18. Phase 12 — Procurement + Inventory

Build:

- Supplier
- Purchase requisition
- Approval
- RFQ
- Purchase order
- Goods receipt
- Purchase invoice
- Purchase return
- Stock transfer
- Stock issue
- Stock return
- Inventory valuation
- Reorder level

Support:

- Batch
- Serial number
- Expiry
- Barcode
- QR code

---

# 19. Phase 13 — Insurance

Build:

- Insurance company
- Plan
- Coverage
- Eligibility
- Preauthorization
- Claim
- Claim submission
- Claim response
- Rejection
- Resubmission
- Settlement
- TPA

---

# 20. Phase 14 — Finance

Build:

- Chart of accounts
- General ledger
- Accounts receivable
- Accounts payable
- Cash
- Bank
- Journal
- Cost center
- Department accounting
- Budget
- Financial reports

Financial integration must be designed so clinical transactions can generate financial postings without duplicating business logic.

---

# 21. Phase 15 — HR & Payroll

Build:

- Employee
- Department
- Designation
- Attendance
- Shift
- Roster
- Leave
- Payroll
- Overtime
- Allowance
- Deduction
- License/certification expiry

---

# 22. Phase 16 — Portals

## Patient Portal

- Appointment
- Reports
- Prescription
- Billing
- Payment
- Documents
- Follow-up
- Notifications

## Doctor Portal

- Appointment
- Patient list
- EMR
- Consultation
- Prescription
- Investigation
- Reports

## Nurse Portal

- Assigned patients
- Vital signs
- Medication
- Nursing notes
- Tasks

---

# 23. Phase 17 — Interoperability

Implement an integration layer.

## FHIR

Design APIs around resources such as:

- Patient
- Practitioner
- Organization
- Encounter
- Appointment
- Observation
- Condition
- Procedure
- Medication
- MedicationRequest
- DiagnosticReport
- ImagingStudy
- Coverage
- Claim

## HL7

Support integration patterns for:

- ADT
- ORM
- ORU
- MDM

## DICOM

Support:

- Modality
- Study
- Series
- Image
- PACS integration

Do not tightly couple the core HMS to any single vendor.

---

# 24. Database Design Rules

## Common Fields

Most transactional tables should use appropriate equivalents of:

```text
id
organization_id
hospital_id
branch_id
created_by
updated_by
created_at
updated_at
deleted_at
```

Do not add every field blindly. Apply fields according to the domain.

## Important Master Tables

Examples:

```text
organizations
hospitals
branches
departments
wards
rooms
beds
users
roles
permissions
patients
patient_identifiers
patient_contacts
patient_allergies
patient_documents
practitioners
appointments
encounters
diagnoses
procedures
medications
prescriptions
prescription_items
services
service_prices
invoices
invoice_items
payments
admissions
bed_assignments
lab_orders
lab_order_items
lab_samples
lab_results
radiology_orders
radiology_reports
pharmacy_products
inventory_transactions
purchase_orders
purchase_order_items
insurance_companies
insurance_claims
audit_logs
notifications
```

The AI developer must expand this model based on the module being implemented rather than attempting to create an enormous database migration in one step.

---

# 25. Multi-Tenancy / Multi-Hospital

The system must support:

```text
Organization
   ├── Hospital A
   │     ├── Branch
   │     ├── Departments
   │     └── Users
   │
   └── Hospital B
         ├── Branch
         ├── Departments
         └── Users
```

All queries must respect the user's authorized organizational scope.

Never rely only on frontend filtering for tenant isolation.

---

# 26. RBAC

Recommended roles:

- Super Administrator
- Hospital Administrator
- IT Administrator
- Doctor
- Consultant
- Nurse
- Lab Technician
- Radiology Technician
- Radiologist
- Pharmacist
- Cashier
- Accountant
- Store Officer
- Procurement Officer
- HR Officer
- Receptionist
- Insurance Officer
- Quality Officer
- Auditor
- Patient

Permissions should follow:

```text
module.action
```

Examples:

```text
patient.view
patient.create
patient.update
patient.merge
patient.export

appointment.view
appointment.create
appointment.update
appointment.cancel

prescription.create
prescription.view
prescription.amend

billing.create
billing.view
billing.discount
billing.refund
```

---

# 27. Clinical Data Rules

Clinical records require special treatment.

Implement:

- Version/history
- Amendment tracking
- Author tracking
- Timestamp
- Approval where required
- Audit trail
- Access logging

Do not allow ordinary users to silently overwrite approved clinical results.

For laboratory results:

```text
Draft
  ↓
Entered
  ↓
Verified
  ↓
Approved
  ↓
Released
```

---

# 28. Financial Data Rules

Financial transactions must be auditable.

Do not hard-delete:

- Invoice
- Payment
- Refund
- Journal
- Claim settlement

Use:

- Reversal
- Cancellation
- Credit note
- Debit note
- Adjustment

according to the business process.

---

# 29. API Standards

Use versioned APIs:

```text
/api/v1/auth
/api/v1/patients
/api/v1/appointments
/api/v1/encounters
/api/v1/prescriptions
/api/v1/laboratory
/api/v1/radiology
/api/v1/billing
/api/v1/pharmacy
```

Use consistent response structures.

Example:

```json
{
  "success": true,
  "message": "Patient created successfully.",
  "data": {},
  "meta": {}
}
```

Errors:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

---

# 30. Integration Architecture

Never call third-party APIs directly from controllers.

Use:

```text
Controller
   ↓
Application Service
   ↓
Integration Interface
   ↓
Vendor Adapter
   ↓
External System
```

Example:

```text
PaymentGatewayInterface
    ├── StripeAdapter
    ├── LocalGatewayAdapter
    └── BankAdapter
```

Similarly:

```text
SmsProviderInterface
EmailProviderInterface
WhatsAppProviderInterface
LabAnalyzerInterface
PacsInterface
InsuranceInterface
```

---

# 31. Queue & Background Processing

Use queues for:

- Email
- SMS
- WhatsApp
- Report generation
- PDF generation
- Large exports
- Notifications
- FHIR synchronization
- HL7 processing
- DICOM metadata processing
- Insurance submission
- Analytics processing
- Backup jobs

Use Laravel Horizon where Redis queues are used.

---

# 32. Scheduled Jobs

Examples:

```text
Every minute
- Queue monitoring

Every 5 minutes
- Appointment reminders

Hourly
- Expiry checks

Daily
- Backup verification
- Pending claim checks
- Unreleased result checks

Monthly
- Financial closing checks
- KPI generation
```

Use Laravel Scheduler rather than ad-hoc scripts wherever practical.

---

# 33. Notification Engine

Support channels:

- In-app
- Email
- SMS
- WhatsApp
- Push

Notification types:

- Appointment
- Lab result
- Critical result
- Payment
- Prescription
- Follow-up
- Stock
- Expiry
- Approval
- Security

All notifications should be template-driven.

---

# 34. Reporting Engine

Reports must support:

- Filters
- Date ranges
- Hospital
- Branch
- Department
- Doctor
- Patient
- Service
- Export
- Print
- PDF
- Excel
- CSV

Implement a reusable reporting service rather than custom report logic in every controller.

---

# 35. Dashboard Design

## Executive Dashboard

Show:

- Patients
- OPD
- IPD
- Emergency
- Bed occupancy
- ICU occupancy
- OT utilization
- Revenue
- Outstanding
- Pharmacy
- Laboratory
- Patient satisfaction

## Operational Dashboard

Show:

- Queue
- Beds
- Pending lab
- Pending radiology
- Pending discharge
- Emergency
- Pharmacy
- Critical alerts

---

# 36. Security Requirements

Mandatory:

- HTTPS
- Password hashing
- MFA capability
- RBAC
- Session management
- CSRF protection
- XSS protection
- SQL injection protection
- Rate limiting
- Secure file upload
- File type validation
- Virus/malware scanning where appropriate
- API authentication
- Token expiry
- Audit logs
- Login history
- Failed login detection
- Security event logging
- Encryption for sensitive data where required

Never log:

- Passwords
- Authentication tokens
- Full sensitive medical information unnecessarily
- Payment secrets

---

# 37. Privacy

Implement:

- Consent
- Minimum necessary access
- Access logging
- Patient data export controls
- Data retention policies
- Data correction workflow
- Privacy notice
- Sensitive-data protection

The exact legal requirements must be configured for the jurisdiction where the HMS is deployed.

---

# 38. File Management

Use object storage for:

- Patient documents
- Lab reports
- Radiology reports
- Consent documents
- Insurance documents
- Employee documents

Store metadata in the database:

```text
file_id
entity_type
entity_id
file_name
mime_type
size
storage_disk
storage_path
uploaded_by
created_at
```

Do not store large files directly in database BLOB fields unless there is a specific architectural reason.

---

# 39. Audit Framework

Create centralized audit functionality.

Track:

- Login
- Logout
- Patient access
- Patient modification
- Clinical record changes
- Prescription changes
- Lab result changes
- Billing changes
- Discounts
- Refunds
- User/role changes
- Data exports
- API access
- Configuration changes

Audit record:

```text
user_id
action
module
entity_type
entity_id
old_values
new_values
ip_address
user_agent
created_at
```

---

# 40. Testing Strategy

Every module must contain:

## Unit Tests

Test:

- Business rules
- Services
- Calculations
- Validation logic

## Feature Tests

Test:

- Authentication
- Authorization
- CRUD
- Workflows
- APIs

## Integration Tests

Test:

- Payment
- SMS
- Email
- LIS
- PACS
- Insurance
- FHIR
- HL7

## Security Tests

Test:

- Unauthorized access
- Tenant isolation
- IDOR
- Privilege escalation
- File upload
- API abuse
- Rate limiting

## Performance Tests

Test:

- Patient search
- Appointment search
- Dashboard
- Billing
- Large reports
- API throughput

---

# 41. Definition of Done

A feature is NOT complete until:

- Migration exists
- Model exists
- Relationships are correct
- Validation exists
- Authorization exists
- Service/business logic exists
- UI exists
- API exists where applicable
- Audit exists where required
- Notifications exist where required
- Tests exist
- Error handling exists
- Documentation exists
- No major static-analysis errors
- No obvious security vulnerabilities
- Database indexes are reviewed
- Performance is acceptable

---

# 42. Development Workflow for AI

For every AI coding request, use this sequence:

```text
1. Understand requirement
2. Identify affected module
3. Inspect existing code
4. Inspect existing migrations/models/routes
5. Identify dependencies
6. Propose implementation plan
7. Design database changes
8. Implement migration
9. Implement models/relationships
10. Implement service/business logic
11. Implement authorization
12. Implement controllers
13. Implement routes/API
14. Implement UI
15. Implement validation
16. Implement notifications/jobs
17. Implement tests
18. Run tests
19. Review security
20. Review performance
21. Update documentation
```

Never overwrite existing functionality without first understanding its dependencies.

---

# 43. AI Coding Rules

When using an AI coding assistant:

### Before coding

The AI must inspect:

- Laravel version
- PHP version
- composer.json
- package.json
- routes
- migrations
- models
- controllers
- services
- policies
- existing modules
- database schema

### During coding

The AI must:

- Reuse existing components
- Follow project naming conventions
- Avoid unnecessary dependencies
- Avoid duplicate classes
- Avoid massive controllers
- Avoid massive models
- Use reusable services
- Write tests

### After coding

The AI must provide:

1. Files changed
2. Files created
3. Database changes
4. Routes added
5. Commands to execute
6. Environment variables
7. Test instructions
8. Rollback instructions
9. Security considerations

---

# 44. Git Strategy

Recommended:

```text
main
develop
feature/*
bugfix/*
hotfix/*
release/*
```

Commit example:

```text
feat(patient): add duplicate patient detection
feat(opd): implement consultation workflow
fix(billing): correct refund calculation
test(lab): add result approval tests
```

Never commit:

- .env
- passwords
- API secrets
- private keys
- production database dumps

---

# 45. Deployment Environments

Maintain:

```text
Local
   ↓
Development
   ↓
QA/Test
   ↓
UAT
   ↓
Staging
   ↓
Production
```

Do not develop directly on production.

---

# 46. Database Migration Rules

Never manually modify production tables without a documented migration.

Every schema change must have:

- Migration
- Rollback strategy
- Index review
- Foreign-key review
- Data migration if required

For large production tables, assess migration downtime before deployment.

---

# 47. Backup Strategy

At minimum:

```text
Production Database
       ↓
Daily Full Backup
       ↓
Encrypted Off-site Storage
       ↓
Backup Verification
       ↓
Periodic Restore Test
```

Define:

- RPO
- RTO
- Backup retention
- Disaster recovery procedure

---

# 48. Disaster Recovery

Document:

- Database recovery
- File recovery
- Application recovery
- Redis recovery
- Queue recovery
- DNS recovery
- SSL recovery
- External integration recovery

Perform scheduled restore tests.

---

# 49. Performance Requirements

The application should be designed for:

- Indexed searches
- Pagination
- Lazy loading
- Eager loading where appropriate
- Query optimization
- Redis caching
- Queue processing
- Background report generation
- Database connection optimization

Avoid:

- N+1 queries
- Unbounded queries
- Huge synchronous exports
- Loading complete patient histories unnecessarily
- Heavy dashboard queries on every page request

---

# 50. Observability

Track:

- Application errors
- HTTP errors
- Queue failures
- Slow queries
- Failed jobs
- API failures
- Integration failures
- Authentication failures
- Database health
- Server resources

Create alerts for critical failures.

---

# 51. Localization

The system should support:

- English
- Additional languages later
- Date formats
- Time formats
- Number formats
- Currency
- Tax rules
- Timezone

Do not hard-code labels or currency symbols.

---

# 52. Internationalization

Design configuration for:

- Country
- Currency
- Timezone
- Tax
- Date format
- Phone format
- Address format
- Identification types
- Healthcare coding systems

---

# 53. Master Data Management

Create centralized masters for:

- Country
- State/Division
- District
- City
- Currency
- Tax
- Department
- Specialty
- Doctor
- Service
- Medicine
- Diagnosis
- Procedure
- Laboratory test
- Radiology study
- Insurance
- Supplier
- Payment method

Master data must have:

- Active/inactive
- Effective dates where needed
- Audit trail
- Permission control

---

# 54. Workflow Engine

Build a reusable approval system.

Example:

```text
Request
   ↓
Submitted
   ↓
Pending Approval
   ↓
Approved / Rejected
   ↓
Processed
```

Support:

- Sequential approval
- Parallel approval
- Amount-based approval
- Department-based approval
- Role-based approval
- Escalation
- Delegation
- Approval history

---

# 55. AI Module

AI should be an isolated service layer.

Possible features:

- Patient summary
- Clinical note assistance
- Discharge summary assistance
- Medical document extraction
- Report summarization
- Demand forecasting
- Appointment no-show prediction
- Inventory forecasting

AI output must:

- Be clearly identified as AI-assisted
- Preserve human review
- Not silently modify clinical records
- Be logged appropriately
- Follow privacy/security controls

---

# 56. Recommended Folder Structure

Example:

```text
app/
├── Domain/
│   ├── Core/
│   ├── Patient/
│   ├── Appointment/
│   ├── OPD/
│   ├── Emergency/
│   ├── IPD/
│   ├── Nursing/
│   ├── Pharmacy/
│   ├── Laboratory/
│   ├── Radiology/
│   ├── OT/
│   ├── ICU/
│   ├── Billing/
│   ├── Insurance/
│   ├── Inventory/
│   ├── Procurement/
│   ├── Finance/
│   ├── HR/
│   └── Reporting/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
├── Policies/
├── Jobs/
├── Events/
├── Listeners/
├── Notifications/
└── Services/
```

Adapt this structure to the chosen Laravel architecture. Do not force domain classes into artificial folders if the project already has a coherent architecture.

---

# 57. UI Standards

Every module should provide:

- Dashboard
- List page
- Search/filter
- Create
- View
- Edit
- History
- Print/export where applicable

Use:

- Consistent buttons
- Consistent forms
- Confirmation dialogs
- Validation messages
- Loading indicators
- Empty states
- Error states
- Responsive layouts

Clinical screens should prioritize readability and speed over visual complexity.

---

# 58. Accessibility

Target modern accessibility practices:

- Keyboard navigation
- Clear labels
- Appropriate contrast
- Accessible forms
- Error descriptions
- Screen-reader-friendly structure
- Responsive design

---

# 59. Reporting and KPI Framework

Define KPIs centrally.

Examples:

### Clinical

- Patient volume
- Readmission
- Mortality
- Length of stay
- Infection rate

### Operational

- Bed occupancy
- Appointment utilization
- Waiting time
- Lab turnaround time
- OT utilization

### Financial

- Revenue
- Collection
- Outstanding
- Revenue per department
- Insurance receivables

---

# 60. Documentation Requirements

Maintain:

```text
docs/
├── architecture/
├── database/
├── api/
├── modules/
├── deployment/
├── security/
├── integrations/
├── workflows/
├── testing/
└── user-guides/
```

Each module should have:

- Purpose
- Features
- Workflow
- Database entities
- Permissions
- Routes
- APIs
- Reports
- Notifications
- Tests

---

# 61. Initial Project Backlog

Create these epics:

```text
EPIC-001 Project Foundation
EPIC-002 Organization Management
EPIC-003 Authentication & Authorization
EPIC-004 Patient Management
EPIC-005 Appointment
EPIC-006 OPD
EPIC-007 Billing
EPIC-008 IPD
EPIC-009 Bed Management
EPIC-010 Nursing
EPIC-011 Pharmacy
EPIC-012 Laboratory
EPIC-013 Radiology
EPIC-014 Emergency
EPIC-015 OT
EPIC-016 ICU
EPIC-017 Blood Bank
EPIC-018 Procurement
EPIC-019 Inventory
EPIC-020 Insurance
EPIC-021 Finance
EPIC-022 HR & Payroll
EPIC-023 Patient Portal
EPIC-024 Doctor Portal
EPIC-025 Interoperability
EPIC-026 Reporting
EPIC-027 BI
EPIC-028 Security
EPIC-029 Compliance
EPIC-030 AI
```

---

# 62. First Development Sprint

Do not begin with pharmacy, laboratory, or complex clinical modules.

Start with:

### Sprint 1

1. Laravel installation
2. Database configuration
3. Authentication
4. User management
5. Role management
6. Permission management
7. Organization
8. Hospital
9. Branch
10. Department
11. Common master data
12. Audit logging
13. File storage
14. Notification foundation
15. API versioning
16. Base AdminLTE layout
17. Automated test foundation

### Sprint 1 Acceptance Criteria

- User can log in
- User can log out
- MFA architecture exists
- Admin can create hospital
- Admin can create branch
- Admin can create department
- Admin can create users
- Admin can assign roles
- Permissions work
- Unauthorized users are blocked
- Audit logs are generated
- API authentication works
- Tests pass

---

# 63. Second Development Sprint

Build:

- Patient registration
- Patient search
- Patient profile
- MRN
- Patient identifiers
- Patient contacts
- Emergency contact
- Allergy
- Patient documents
- Duplicate detection
- Patient timeline

Acceptance criteria must include:

- Duplicate detection
- Authorization
- Audit
- Validation
- Tests
- Search performance

---

# 64. AI Prompt Template

Use the following prompt when asking an AI coding assistant to implement a module:

```text
You are a senior Laravel architect and healthcare software engineer.

We are developing an international-standard Hospital Management System (HMS).

Technology:
- Laravel
- PHP 8.4+
- MySQL/PostgreSQL
- Redis
- Blade
- AdminLTE
- REST API
- Modular Monolith

Before making changes:

1. Inspect the existing project structure.
2. Inspect composer.json and package.json.
3. Inspect relevant migrations.
4. Inspect relevant models.
5. Inspect controllers.
6. Inspect routes.
7. Inspect services.
8. Inspect policies.
9. Inspect tests.
10. Identify existing reusable components.

Task:
[DESCRIBE MODULE/FEATURE]

Requirements:
[LIST REQUIREMENTS]

You must:

1. Design the database changes.
2. Create migrations.
3. Create/update models.
4. Define relationships.
5. Implement services/business logic.
6. Implement validation.
7. Implement authorization.
8. Implement controllers.
9. Implement routes.
10. Implement Blade/AdminLTE UI.
11. Implement APIs where required.
12. Implement events/jobs/notifications where required.
13. Implement audit logging.
14. Add automated tests.
15. Consider multi-hospital and multi-branch authorization.
16. Consider clinical-data privacy.
17. Consider performance and indexing.
18. Consider security.
19. Do not duplicate existing functionality.
20. Do not modify unrelated modules.

After implementation provide:

- Files created
- Files modified
- Database changes
- Routes
- Permissions
- Commands to execute
- Environment variables
- Test commands
- Deployment notes
- Rollback notes
- Security considerations
```

---

# 65. Module Completion Checklist

Before marking any module complete:

```text
[ ] Requirements completed
[ ] Database designed
[ ] Migration created
[ ] Indexes reviewed
[ ] Models created
[ ] Relationships tested
[ ] Validation implemented
[ ] Authorization implemented
[ ] Service layer implemented
[ ] Controller implemented
[ ] Routes implemented
[ ] UI implemented
[ ] API implemented if required
[ ] Audit implemented
[ ] Notifications implemented if required
[ ] Queue implemented if required
[ ] Reports implemented
[ ] Unit tests
[ ] Feature tests
[ ] Integration tests
[ ] Security review
[ ] Performance review
[ ] Documentation updated
[ ] UAT completed
```

---

# 66. Final Quality Gates

Before production:

## Functional

- All critical workflows tested
- Financial calculations verified
- Clinical workflows verified
- User permissions verified

## Security

- Authentication reviewed
- Authorization reviewed
- API security reviewed
- Tenant isolation tested
- File upload security tested
- Audit logging verified

## Performance

- Database indexes reviewed
- Slow queries reviewed
- Queue performance tested
- Large reports tested
- Concurrent users tested

## Reliability

- Backup tested
- Restore tested
- Disaster recovery tested
- Queue failure recovery tested
- External integration failure tested

## Compliance

- Privacy requirements reviewed
- Retention requirements reviewed
- Audit requirements reviewed
- Local healthcare regulations reviewed
- Applicable accreditation requirements reviewed

---

# 67. Important Healthcare Software Disclaimer

This specification is a software architecture and product-development guide. It does not by itself establish regulatory or clinical compliance.

Before production deployment, the implementation must be reviewed against the laws, regulations, clinical governance requirements, privacy requirements, medical-device requirements, and accreditation requirements applicable to the target country and healthcare organization.

Clinical workflows must be validated by qualified healthcare professionals.

---

# 68. Recommended Build Order

The overall recommended sequence is:

```text
Foundation
    ↓
Organization
    ↓
Security/RBAC
    ↓
Patient/MPI
    ↓
Appointment
    ↓
OPD
    ↓
Billing
    ↓
IPD
    ↓
Bed Management
    ↓
Nursing
    ↓
Pharmacy
    ↓
Laboratory
    ↓
Radiology
    ↓
Emergency
    ↓
OT
    ↓
ICU
    ↓
Blood Bank
    ↓
Procurement
    ↓
Inventory
    ↓
Insurance
    ↓
Finance
    ↓
HR/Payroll
    ↓
Portals
    ↓
Interoperability
    ↓
Analytics
    ↓
AI
```

The project should be developed incrementally, with every phase producing a testable and deployable increment.

---

# 69. Master Instruction to AI Developer

```text
Treat this document as the master architecture and development specification for the Hospital Management System.

Do not attempt to implement the entire HMS in one response.

For every requested feature:

1. Identify the relevant module.
2. Review existing implementation.
3. Determine dependencies.
4. Propose a small implementation plan.
5. Implement database changes first.
6. Implement domain/business logic.
7. Implement authorization.
8. Implement UI/API.
9. Implement audit and notifications where applicable.
10. Add automated tests.
11. Run/recommend the appropriate tests.
12. Review security and performance.
13. Update documentation.
14. Clearly list every file changed.
15. Never break existing functionality.
16. Never invent existing project structures or database tables.
17. Ask for missing project information when it is necessary rather than guessing.

The HMS must remain modular, secure, auditable, interoperable, scalable, and maintainable throughout development.
```

---

# 70. End State

The completed platform should provide:

```text
                         HMS
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
     Clinical         Operations         Finance
        │                 │                 │
   ┌────┴────┐       ┌────┴────┐       ┌────┴────┐
   │ EMR     │       │ Bed     │       │ Billing │
   │ OPD     │       │ Pharmacy│       │ Insurance
   │ IPD     │       │ Supply  │       │ Finance │
   │ ER      │       │ HR      │       │ Payroll │
   │ ICU     │       │ Assets  │       │ Claims  │
   │ OT      │       └─────────┘       └─────────┘
   │ Lab     │
   │ Radiology│
   └─────────┘

                  ┌─────────────────┐
                  │ Integration     │
                  │ FHIR / HL7      │
                  │ DICOM / PACS    │
                  │ LIS / Devices   │
                  └────────┬────────┘
                           │
                  ┌────────▼────────┐
                  │ Analytics / AI  │
                  └─────────────────┘
```

This document should be treated as the **master development specification**, while individual module specifications should be created before implementing each major module.
