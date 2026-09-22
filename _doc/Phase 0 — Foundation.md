Below is the **Phase 0 — Foundation Module Specification** I would use as the baseline development specification for the HMS. This phase should establish the platform architecture and reusable infrastructure before Patient, OPD, IPD, Pharmacy, Laboratory, or other clinical modules are built.

# Phase 0 — Foundation Module Specification

**Version:** 1.0  
**Module:** Foundation / Core Platform  
**Architecture:** Laravel Modular Monolith  
**UI:** Blade + AdminLTE  
**Database:** MySQL/PostgreSQL  
**Cache/Queue:** Redis  
**API:** REST `/api/v1`  
**Primary objective:** Build a secure, reusable, multi-hospital foundation for every future HMS module.

---

# 1. Phase 0 Objectives

Phase 0 must provide the following capabilities:

1. Application installation and environment management
2. Organization/hospital structure
3. Authentication
4. User management
5. Role and permission management
6. Multi-hospital / multi-branch authorization scope
7. Department and basic organizational masters
8. System settings
9. Master-data framework
10. Audit logging
11. Activity logging
12. File/document storage foundation
13. Notification framework
14. Workflow/approval foundation
15. API foundation
16. Exception/error handling
17. Queue infrastructure
18. Scheduled-task infrastructure
19. Dashboard framework
20. Localization foundation
21. Security foundation
22. Automated testing foundation

---

# 2. Phase 0 Boundary

## Included

```text
Foundation
├── Application Core
├── Organization
├── Hospital
├── Branch
├── Department
├── Authentication
├── Users
├── Roles
├── Permissions
├── Access Scope
├── Settings
├── Master Data
├── Audit
├── Activity Log
├── Notifications
├── Files
├── Workflow
├── API
├── Queue
├── Scheduler
├── Dashboard
├── Localization
├── Security
└── Testing
```

## Explicitly NOT included

Do not implement these in Phase 0:

- Patient registration
- OPD
- IPD
- Emergency
- EMR
- Pharmacy
- Laboratory
- Radiology
- Billing
- Insurance
- Procurement
- Inventory
- HR
- Payroll
- OT
- ICU
- Blood Bank

Those modules will consume the Foundation services created here.

---

# 3. Architectural Principle

The Foundation module is the **platform layer**.

Future modules should depend on Foundation:

```text
                  Foundation
                       │
       ┌───────────────┼────────────────┐
       │               │                │
    Patient          Billing          Pharmacy
       │               │                │
       └───────────────┼────────────────┘
                       │
                  Common Services
```

The Foundation must **not** contain clinical or financial business logic.

---

# 4. Recommended Project Structure

For the Laravel application:

```text
app/
├── Domain/
│   └── Core/
│       ├── Organization/
│       ├── User/
│       ├── Role/
│       ├── Permission/
│       ├── Settings/
│       ├── MasterData/
│       ├── Audit/
│       ├── ActivityLog/
│       ├── Notification/
│       ├── File/
│       ├── Workflow/
│       └── Dashboard/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
│
├── Policies/
├── Jobs/
├── Events/
├── Listeners/
├── Notifications/
└── Services/
```

If the project later adopts a package-based modular architecture, these domains can be extracted without redesigning the business model.

---

# 5. Organization Hierarchy

The system should support:

```text
Organization
│
├── Hospital A
│   ├── Branch A1
│   │   ├── Department
│   │   ├── Department
│   │   └── Department
│   │
│   └── Branch A2
│
└── Hospital B
    ├── Branch B1
    └── Branch B2
```

Recommended entities:

```text
organizations
hospitals
branches
departments
```

---

# 6. Organization

## Purpose

Represent the healthcare organization or hospital group.

### Fields

```text
id
name
legal_name
code
registration_number
tax_number
email
phone
website
address
country_id
timezone
currency_id
locale
logo
status
settings
created_at
updated_at
deleted_at
```

### Features

- Create organization
- Edit organization
- Activate/deactivate
- Organization profile
- Logo
- Contact information
- Legal information
- Default currency
- Default timezone
- Default locale

---

# 7. Hospital

### Fields

```text
id
organization_id
name
code
registration_number
license_number
phone
email
address
timezone
currency_id
status
created_at
updated_at
deleted_at
```

### Features

- Create hospital
- Edit hospital
- Activate/deactivate
- Hospital logo
- Hospital contact information
- Hospital configuration

---

# 8. Branch

### Features

- Branch creation
- Branch modification
- Branch activation/deactivation
- Branch address
- Contact information
- Operating hours

### Relationship

```text
Organization
    ↓
Hospital
    ↓
Branch
```

---

# 9. Department

Examples:

```text
Medicine
Cardiology
Neurology
Surgery
Pediatrics
Emergency
ICU
Laboratory
Radiology
Pharmacy
Billing
HR
Finance
```

### Fields

```text
id
hospital_id
branch_id
name
code
type
parent_id
head_user_id
phone
email
status
sort_order
```

Support hierarchical departments:

```text
Clinical
├── Medicine
├── Surgery
└── Pediatrics

Diagnostics
├── Laboratory
└── Radiology
```

---

# 10. Authentication

The authentication layer must support:

- Login
- Logout
- Password reset
- Password change
- Remember session
- Session timeout
- Account lockout
- Login history
- Failed login tracking
- Email verification
- MFA-ready architecture

Future:

- LDAP
- Active Directory
- SSO
- OAuth/OIDC

---

# 11. Login Flow

```text
Username / Email
       ↓
Credential Validation
       ↓
Account Status
       ↓
MFA if enabled
       ↓
Organization Scope
       ↓
Role / Permission
       ↓
Dashboard
```

---

# 12. User Management

## User fields

```text
id
organization_id
name
username
email
phone
password
employee_id
user_type
status
locale
timezone
last_login_at
email_verified_at
password_changed_at
created_at
updated_at
deleted_at
```

Do not store plaintext passwords.

---

# 13. User Types

Initial types:

```text
system_admin
hospital_admin
doctor
nurse
technician
pharmacist
cashier
accountant
store_officer
hr_officer
receptionist
auditor
patient
```

These are classifications, not substitutes for permissions.

---

# 14. Role Management

Recommended initial roles:

```text
Super Administrator
Organization Administrator
Hospital Administrator
IT Administrator
Doctor
Nurse
Laboratory Technician
Radiology Technician
Pharmacist
Receptionist
Cashier
Accountant
Procurement Officer
Store Officer
HR Officer
Quality Officer
Auditor
```

Allow custom roles.

---

# 15. Permission Architecture

Use granular permissions.

Format:

```text
module.resource.action
```

Examples:

```text
core.user.view
core.user.create
core.user.update
core.user.delete

core.role.view
core.role.create
core.role.update

core.department.view
core.department.create

core.settings.view
core.settings.update

core.audit.view
core.audit.export
```

Future examples:

```text
patient.view
patient.create
patient.update

billing.invoice.view
billing.invoice.create
billing.payment.create
```

---

# 16. Permission Categories

Permissions should cover:

```text
View
Create
Update
Delete
Approve
Reject
Cancel
Export
Print
Import
Manage
Configure
```

Not every resource needs every action.

---

# 17. Access Scope

This is one of the most important Foundation features.

A user may have:

```text
Organization-wide access

OR

Hospital-level access

OR

Branch-level access

OR

Department-level access
```

Example:

```text
Dr. Rahman
   ↓
Hospital A
   ↓
Branch A1
   ↓
Cardiology
```

He should not automatically see patients from Hospital B.

---

# 18. Access Scope Model

Potential structure:

```text
user_organization
user_hospital
user_branch
user_department
```

Or a unified access-scope model if appropriate.

The authorization layer should answer:

```text
Can this user access this record?
```

before returning data.

---

# 19. Critical Security Rule

Never depend on:

```text
WHERE hospital_id = current_hospital
```

only in controllers.

Access scoping should be enforced through reusable authorization/query mechanisms.

Recommended flow:

```text
Request
 ↓
Authentication
 ↓
Tenant/Organization Context
 ↓
Authorization
 ↓
Policy
 ↓
Scoped Query
 ↓
Service
```

---

# 20. System Settings

Create a centralized settings system.

Categories:

```text
General
Hospital
Localization
Currency
Tax
Email
SMS
WhatsApp
Security
Authentication
Notifications
File Storage
API
Audit
Queue
Backup
```

Example settings:

```text
hospital.name
hospital.logo
hospital.phone

system.timezone
system.locale
system.currency

security.session_timeout
security.max_login_attempts
security.password_expiry

notification.email.enabled
notification.sms.enabled
```

Do not hard-code these values.

---

# 21. Configuration vs Settings

Keep technical deployment configuration in `.env`.

Example:

```text
APP_ENV
APP_KEY
DB_CONNECTION
DB_HOST
REDIS_HOST
MAIL_HOST
```

Keep business/application settings in the database:

```text
hospital.name
hospital.address
default.currency
appointment.default_duration
```

---

# 22. Master Data Framework

Create a reusable master-data mechanism.

Examples:

```text
Countries
Currencies
Languages
Blood Groups
Genders
Marital Statuses
Identification Types
Contact Types
Address Types
Payment Methods
Document Types
Department Types
Service Categories
```

Each master should support:

```text
id
code
name
description
status
sort_order
metadata
created_at
updated_at
```

Avoid building separate inconsistent CRUD systems for every simple lookup table.

---

# 23. Audit Logging

Audit is mandatory.

Track:

```text
Login
Logout
User creation
User modification
Role changes
Permission changes
Settings changes
Data access
Data creation
Data modification
Data deletion
Data export
API access
Security events
```

Audit record:

```text
id
organization_id
user_id
action
module
entity_type
entity_id
old_values
new_values
ip_address
user_agent
request_id
created_at
```

---

# 24. Audit Examples

```text
USER_CREATED
USER_ROLE_CHANGED
PERMISSION_UPDATED
PATIENT_VIEWED
PATIENT_UPDATED
BILL_CREATED
BILL_REFUNDED
LAB_RESULT_APPROVED
DATA_EXPORTED
LOGIN_FAILED
```

Clinical/financial modules will use the same audit engine later.

---

# 25. Activity Log vs Audit Log

Keep these conceptually separate.

### Activity Log

For ordinary application activities:

```text
User viewed dashboard
User opened patient list
User generated report
```

### Audit Log

For security/compliance-sensitive events:

```text
Patient record changed
Prescription amended
Bill refunded
Permission changed
Patient data exported
```

---

# 26. Request Correlation ID

Every HTTP request should have a unique identifier:

```text
X-Request-ID
```

Example:

```text
REQ-20260922-8F92A21
```

Store it in logs and relevant audit records.

This makes troubleshooting much easier.

---

# 27. Error Handling

Create centralized exception handling.

Production users should see:

```text
Something went wrong.
Please contact the system administrator.
Reference: REQ-20260922-8F92A21
```

Developers/logs should receive the detailed exception.

Never expose:

- SQL errors
- stack traces
- server paths
- credentials
- API secrets

to normal users.

---

# 28. API Foundation

Base:

```text
/api/v1
```

Core endpoints:

```text
/api/v1/auth/login
/api/v1/auth/logout
/api/v1/auth/me

/api/v1/organizations
/api/v1/hospitals
/api/v1/branches
/api/v1/departments

/api/v1/users
/api/v1/roles
/api/v1/permissions

/api/v1/settings
```

Future modules will follow the same convention.

---

# 29. API Standards

Every API should use:

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
        "email": [
            "The email field is required."
        ]
    }
}
```

Use HTTP status codes correctly.

---

# 30. API Authentication

For first-party web requests:

- Laravel session authentication where appropriate.

For external/mobile clients:

- Sanctum or Passport depending on the requirements.

Future interoperability APIs may use OAuth2/OIDC and FHIR-specific authorization patterns.

---

# 31. Notification Foundation

Create a centralized notification service.

Channels:

```text
Database
Email
SMS
WhatsApp
Push
```

Notification model:

```text
id
user_id
type
title
message
data
read_at
created_at
```

Example:

```text
Appointment Reminder
Payment Received
Approval Required
Security Alert
System Maintenance
```

---

# 32. Notification Template Engine

Do not hard-code notification messages throughout controllers.

Create templates:

```text
appointment_reminder
password_reset
approval_required
payment_received
security_alert
```

Later:

```text
lab_result_available
critical_lab_result
discharge_ready
prescription_ready
```

---

# 33. File Management Foundation

Create a reusable file service.

Capabilities:

- Upload
- Download
- View
- Delete
- Version
- Metadata
- Access control

Metadata:

```text
id
disk
path
original_name
mime_type
extension
size
checksum
uploaded_by
entity_type
entity_id
created_at
```

---

# 34. File Security

Implement:

- MIME validation
- Extension validation
- File-size limits
- Randomized storage names
- Private storage by default
- Authorization before download
- Optional malware scanning

Do not expose private patient documents through public URLs.

---

# 35. Workflow Foundation

Create a generic workflow/approval engine.

Example:

```text
Draft
 ↓
Submitted
 ↓
Pending Approval
 ↓
Approved
 ↓
Completed
```

Future modules can use this for:

- Purchase requisitions
- Discounts
- Refunds
- Insurance approvals
- Admissions
- Clinical approvals

---

# 36. Approval Architecture

Entities:

```text
workflows
workflow_steps
workflow_instances
workflow_actions
workflow_approvers
```

Support:

- Role-based approval
- User-based approval
- Department-based approval
- Sequential approval
- Parallel approval
- Reject
- Return
- Escalation
- Delegation

---

# 37. Queue Infrastructure

Configure Redis queue.

Use queues for:

```text
Email
SMS
Notifications
Report generation
File processing
External API calls
Integration synchronization
Large exports
```

Use Laravel Horizon if Redis queues are adopted.

---

# 38. Scheduled Task Foundation

Use Laravel Scheduler.

Examples:

```text
Daily:
- Clean expired sessions
- Generate system statistics

Hourly:
- Check failed integrations

Periodic:
- Notification processing
- Maintenance jobs
```

Future modules will add:

```text
Medicine expiry
Appointment reminders
Insurance expiry
License expiry
```

---

# 39. Dashboard Framework

Create a reusable dashboard architecture.

Widgets should support:

```text
title
value
icon
route
permission
refresh interval
data provider
```

Example:

```text
┌────────────────┐
│ Total Users    │
│     128        │
└────────────────┘
```

The dashboard should be role-aware.

---

# 40. Dashboard Permissions

A user should only see widgets they are authorized to see.

Example:

```text
doctor.dashboard
finance.dashboard
pharmacy.dashboard
admin.dashboard
```

Do not query sensitive financial data for a doctor simply to hide it afterward.

---

# 41. Menu Framework

Create a centralized menu configuration.

Example conceptual structure:

```php
[
    'label' => 'Patients',
    'icon' => 'fas fa-user-injured',
    'permission' => 'patient.view',
    'children' => [...]
]
```

Menu visibility should derive from permissions.

However:

> Menu visibility is not authorization.

Every route/controller/service must still enforce authorization.

---

# 42. Localization

Create:

```text
resources/lang/en/
```

Prepare architecture for:

```text
English
Bangla
Arabic
```

Do not hard-code UI labels.

Example:

```php
__('core.users.title')
```

rather than:

```php
'Users'
```

---

# 43. Timezone

Store timestamps consistently.

Recommended:

```text
Database: UTC
Application: configured timezone
Display: user/hospital timezone
```

For healthcare systems with multiple locations, timezone must be configurable.

---

# 44. Currency

Create currency master:

```text
USD
BDT
EUR
GBP
SAR
QAR
AED
```

Do not assume a single currency in database design.

Financial modules will later use:

```text
currency_id
exchange_rate
```

where appropriate.

---

# 45. Common UI Components

Phase 0 should establish reusable components for:

- Data tables
- Search
- Filters
- Forms
- Date picker
- Date range
- Select
- Multi-select
- Modal
- Confirmation dialog
- Alert
- Badge
- Status
- Timeline
- Tabs
- Cards
- Dashboard widgets
- File upload
- Activity timeline

This will dramatically reduce duplicated UI code later.

---

# 46. Common Data Table

Every major listing should eventually support:

```text
Search
Filter
Sort
Pagination
Column selection
Export
Bulk actions
Row actions
```

But export/bulk actions must be permission-controlled.

---

# 47. Status Framework

Use standardized statuses where appropriate.

Example:

```text
active
inactive
draft
pending
approved
rejected
cancelled
completed
archived
```

Do not create inconsistent status names such as:

```text
ACTIVE
Enabled
Running
Available
```

for the same conceptual state.

---

# 48. Soft Delete Policy

Do not automatically add `deleted_at` to every table.

Use soft deletes where historical/reference preservation matters.

Never use ordinary deletion for:

- Financial transactions
- Audit records
- Clinical records
- Security logs

Use appropriate lifecycle states or reversal mechanisms.

---

# 49. Database Standards

Every migration must consider:

- Primary key
- Foreign key
- Unique constraints
- Indexes
- Nullable fields
- Data types
- Delete/update behavior
- Audit requirements

Example:

```text
users.organization_id
users.email
users.username
users.status
```

should have appropriate indexes/constraints.

---

# 50. Coding Standards

Follow:

- PSR-12
- Laravel conventions
- SOLID principles
- Dependency injection
- Single Responsibility Principle
- Repository pattern only when justified
- Service layer for complex business operations
- Form Requests for validation
- Policies for authorization

Avoid:

```text
1000-line controllers
1000-line models
raw SQL everywhere
business logic in Blade
business logic in JavaScript
duplicate validation
```

---

# 51. Service Layer

Example:

```text
UserService
OrganizationService
HospitalService
BranchService
DepartmentService
SettingsService
AuditService
NotificationService
FileService
WorkflowService
```

Example:

```php
$userService->createUser($data);
```

rather than putting all creation logic inside the controller.

---

# 52. Events

Foundation events may include:

```text
UserCreated
UserUpdated
UserDeleted
RoleAssigned
PermissionChanged
LoginSucceeded
LoginFailed
FileUploaded
WorkflowSubmitted
WorkflowApproved
```

Future modules can listen to the same architecture.

---

# 53. Security Events

Create a security event mechanism.

Examples:

```text
LOGIN_FAILED
ACCOUNT_LOCKED
PASSWORD_CHANGED
MFA_ENABLED
MFA_DISABLED
ROLE_CHANGED
PERMISSION_CHANGED
SENSITIVE_DATA_EXPORTED
SUSPICIOUS_API_REQUEST
```

These should be separately searchable from ordinary activity logs.

---

# 54. Testing Requirements

Phase 0 must have:

### Unit tests

- Settings
- Permission calculations
- Access scope
- Workflow state transitions
- Services

### Feature tests

- Login
- Logout
- Password reset
- User CRUD
- Role CRUD
- Permission enforcement
- Hospital CRUD
- Branch CRUD
- Department CRUD

### Security tests

Test that:

```text
Hospital A user
    X
Hospital B data
```

and:

```text
Doctor
    X
Finance administration
```

unless explicitly authorized.

---

# 55. Phase 0 Database Tables

A reasonable initial schema is:

```text
organizations
hospitals
branches
departments

users
roles
permissions
role_permissions
user_roles

user_organizations
user_hospitals
user_branches
user_departments

settings

master_data_types
master_data

files
file_versions

notifications

workflows
workflow_steps
workflow_instances
workflow_actions

audit_logs
activity_logs
security_events

sessions
```

The exact schema can be adjusted after inspecting the selected Laravel authentication/permission packages and project conventions.

---

# 56. Foundation Menu

Phase 0 should expose:

```text
Dashboard

Administration
├── Organization
├── Hospitals
├── Branches
├── Departments
├── Users
├── Roles
├── Permissions
├── Access Control
├── Master Data
├── Workflows
├── Notifications
└── Settings

Security & Audit
├── Audit Logs
├── Activity Logs
├── Security Events
├── Login History
└── User Sessions

System
├── System Health
├── Queue Monitor
├── Scheduled Jobs
├── API Logs
└── About
```

Clinical menus should not appear until their respective modules are implemented.

---

# 57. Phase 0 API

Initial API endpoints:

```text
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

GET    /api/v1/organizations
POST   /api/v1/organizations

GET    /api/v1/hospitals
POST   /api/v1/hospitals

GET    /api/v1/branches
POST   /api/v1/branches

GET    /api/v1/departments
POST   /api/v1/departments

GET    /api/v1/users
POST   /api/v1/users

GET    /api/v1/roles
GET    /api/v1/permissions

GET    /api/v1/settings

GET    /api/v1/notifications
PATCH  /api/v1/notifications/{id}/read
```

Do not create unnecessary CRUD APIs just because a database table exists.

---

# 58. System Health

Create:

```text
Administration
└── System Health
```

Show:

```text
Application
Database
Redis
Queue
Storage
Mail
Scheduler
External integrations
```

Example:

```text
Database       ● Healthy
Redis          ● Healthy
Queue          ● Healthy
Storage        ● Healthy
Mail           ● Healthy
Scheduler      ● Running
```

This will be extremely useful for IT administrators.

---

# 59. Initial Installation

The project should provide a repeatable installation process.

Example:

```bash
composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

php artisan storage:link

php artisan optimize
```

If the project uses additional packages, their installation must be documented.

---

# 60. Seed Data

Initial seeders should create:

### System

```text
Super Administrator
Organization Administrator
Hospital Administrator
IT Administrator
```

### Permissions

All Phase 0 permissions.

### Master data

Basic:

```text
Countries
Currencies
Languages
Timezones
```

Do not seed real patient or clinical data.

---

# 61. Super Administrator

The initial bootstrap administrator should have:

```text
Full organization access
Full hospital access
Full branch access
All Phase 0 permissions
```

But the system should make it possible to disable or replace bootstrap credentials after installation.

---

# 62. Phase 0 Acceptance Criteria

Phase 0 is complete only when all of these work:

### Organization

- [ ] Organization can be created
- [ ] Hospitals can be created
- [ ] Branches can be created
- [ ] Departments can be created

### Authentication

- [ ] Login
- [ ] Logout
- [ ] Password change
- [ ] Password reset
- [ ] Session management
- [ ] Login history

### Authorization

- [ ] Roles
- [ ] Permissions
- [ ] Hospital scope
- [ ] Branch scope
- [ ] Department scope
- [ ] Policy enforcement

### System

- [ ] Settings
- [ ] Master data
- [ ] Notifications
- [ ] Files
- [ ] Workflows
- [ ] Audit
- [ ] Activity logs
- [ ] Security events

### Technical

- [ ] API v1
- [ ] Redis
- [ ] Queue
- [ ] Scheduler
- [ ] Error handling
- [ ] Logging
- [ ] Testing
- [ ] Documentation

---

# 63. Phase 0 Definition of Done

The Foundation should be considered **production-ready** only when:

```text
[ ] Database migrations complete
[ ] Seeders complete
[ ] Authentication complete
[ ] RBAC complete
[ ] Multi-hospital scope complete
[ ] Multi-branch scope complete
[ ] Department scope complete
[ ] Settings complete
[ ] Master data framework complete
[ ] Audit framework complete
[ ] Activity framework complete
[ ] Security-event framework complete
[ ] Notification framework complete
[ ] File framework complete
[ ] Workflow framework complete
[ ] API foundation complete
[ ] Queue configured
[ ] Scheduler configured
[ ] Error handling complete
[ ] Logging complete
[ ] Dashboard framework complete
[ ] Menu framework complete
[ ] Localization foundation complete
[ ] Automated tests complete
[ ] Security tests complete
[ ] Documentation complete
[ ] Deployment documentation complete
[ ] Backup/restore procedure documented
```

---

# 64. Recommended Development Sequence

I would implement Phase 0 in this exact order:

```text
01. Laravel Project Setup
        ↓
02. Database & Environment
        ↓
03. Core Architecture
        ↓
04. Organization
        ↓
05. Hospital
        ↓
06. Branch
        ↓
07. Department
        ↓
08. Authentication
        ↓
09. Users
        ↓
10. Roles & Permissions
        ↓
11. Access Scope
        ↓
12. Settings
        ↓
13. Master Data
        ↓
14. Audit
        ↓
15. Activity Logs
        ↓
16. Security Events
        ↓
17. File Management
        ↓
18. Notifications
        ↓
19. Workflow
        ↓
20. API Foundation
        ↓
21. Queue & Scheduler
        ↓
22. Dashboard
        ↓
23. System Health
        ↓
24. Testing
        ↓
25. Security Review
        ↓
26. Documentation
```

---

# 65. AI Implementation Prompt

Use this as the **Phase 0 master prompt** with your AI coding assistant:

You are a senior Laravel architect, enterprise ERP/HMS developer, database architect, and healthcare software engineer.

We are building an international-standard Hospital Management System (HMS) using:

- PHP 8.4+
- Laravel
- MySQL/PostgreSQL
- Redis
- Blade
- AdminLTE
- REST API
- Modular Monolith architecture

You must implement **Phase 0 — Foundation** only.

The Foundation must provide the reusable platform required by all future HMS modules.

PHASE 0 MODULES:

1. Application Core
2. Organization
3. Hospital
4. Branch
5. Department
6. Authentication
7. Users
8. Roles
9. Permissions
10. Multi-hospital access scope
11. Settings
12. Master Data
13. Audit Logging
14. Activity Logging
15. Security Events
16. File Management
17. Notification Framework
18. Workflow/Approval Framework
19. API Foundation
20. Queue
21. Scheduler
22. Dashboard Framework
23. System Health
24. Localization
25. Testing Foundation

IMPORTANT:

Do NOT implement Patient, OPD, IPD, Emergency, EMR, Pharmacy, Laboratory, Radiology, Billing, Insurance, Procurement, Inventory, Finance, HR, OT, ICU, Blood Bank, or other business modules during Phase 0.

Before making any changes:

1. Inspect the existing project structure.
2. Inspect composer.json.
3. Inspect package.json.
4. Inspect .env.example.
5. Inspect existing migrations.
6. Inspect existing models.
7. Inspect existing controllers.
8. Inspect routes.
9. Inspect middleware.
10. Inspect policies.
11. Inspect tests.
12. Identify installed packages.
13. Reuse existing functionality where possible.
14. Do not overwrite unrelated code.

ARCHITECTURE REQUIREMENTS:

- Use modular/domain-oriented architecture.
- Keep controllers thin.
- Use service classes for business logic.
- Use Form Requests for validation.
- Use Policies/Gates for authorization.
- Use Events/Listeners where appropriate.
- Use Jobs for asynchronous processing.
- Use Redis queues where configured.
- Use database transactions for multi-step operations.
- Use dependency injection.
- Follow PSR-12 and Laravel conventions.
- Avoid unnecessary dependencies.
- Avoid duplicate functionality.
- Avoid giant controllers/models/services.

MULTI-HOSPITAL REQUIREMENTS:

The hierarchy must support:

Organization
    -> Hospital
        -> Branch
            -> Department

Users must have an explicit access scope.

A user assigned to Hospital A must not be able to access Hospital B unless explicitly authorized.

Authorization must be enforced server-side.

DATABASE REQUIREMENTS:

Create normalized tables with:

- Primary keys
- Foreign keys
- Unique constraints
- Appropriate indexes
- Status fields where appropriate
- created_at
- updated_at
- deleted_at only where justified

Do not create an unnecessarily complicated database.

AUTHENTICATION:

Implement:

- Login
- Logout
- Password change
- Password reset
- Session management
- Login history
- Failed login tracking
- Account status
- Email verification architecture
- MFA-ready architecture

AUTHORIZATION:

Implement:

- Roles
- Permissions
- User-role assignment
- Role-permission assignment
- Organization scope
- Hospital scope
- Branch scope
- Department scope

Use granular permissions such as:

core.user.view
core.user.create
core.user.update
core.user.delete

core.role.view
core.role.create
core.role.update

core.hospital.view
core.hospital.create
core.hospital.update

AUDIT:

Implement centralized audit logging.

Track:

- Authentication events
- User changes
- Role changes
- Permission changes
- Settings changes
- Data changes
- Data exports
- Security-sensitive actions

Audit records should contain:

- user_id
- organization_id
- action
- module
- entity_type
- entity_id
- old_values
- new_values
- ip_address
- user_agent
- request_id
- created_at

Do not allow ordinary users to modify or delete audit records.

SETTINGS:

Implement a reusable settings service.

Separate:

Technical configuration:
.env

Business/application settings:
Database

MASTER DATA:

Create a reusable master-data mechanism for simple lookup/configuration data.

NOTIFICATIONS:

Create a notification framework supporting:

- Database
- Email
- SMS-ready
- WhatsApp-ready
- Push-ready

Do not hard-code notification messages in controllers.

FILES:

Create a secure file-management service.

Files must be private by default.

Validate:

- MIME type
- Extension
- File size

Store metadata in the database.

WORKFLOW:

Create a reusable workflow engine supporting:

- Draft
- Submit
- Approve
- Reject
- Return
- Complete
- Sequential approval
- Role-based approval
- User-based approval

API:

Create versioned APIs under:

/api/v1

Use consistent JSON responses.

QUEUE:

Configure Redis queues and Laravel Horizon if appropriate.

SCHEDULER:

Configure Laravel Scheduler.

DASHBOARD:

Create a reusable dashboard/widget architecture.

SYSTEM HEALTH:

Create checks for:

- Application
- Database
- Redis
- Queue
- Storage
- Mail
- Scheduler

SECURITY:

Implement:

- CSRF protection
- Rate limiting
- Secure password hashing
- Authorization
- Session protection
- Secure file upload
- Sensitive-data protection
- Error sanitization
- Security events

TESTING:

Create:

- Unit tests
- Feature tests
- Authorization tests
- Multi-hospital isolation tests
- API tests
- Security tests

CRITICAL TEST:

A user belonging only to Hospital A must not access Hospital B data.

UI:

Use Blade + AdminLTE.

Create a clean menu:

Dashboard

Administration
- Organization
- Hospitals
- Branches
- Departments
- Users
- Roles
- Permissions
- Access Control
- Master Data
- Workflows
- Notifications
- Settings

Security & Audit
- Audit Logs
- Activity Logs
- Security Events
- Login History
- User Sessions

System
- System Health
- Queue Monitor
- Scheduled Jobs
- API Logs
- About

IMPLEMENTATION PROCESS:

Work incrementally.

For each sub-module:

1. Explain the implementation plan.
2. Inspect existing code.
3. Create/update migrations.
4. Create/update models.
5. Create relationships.
6. Implement services.
7. Implement validation.
8. Implement policies.
9. Implement controllers.
10. Implement routes.
11. Implement UI.
12. Implement API where required.
13. Implement audit.
14. Implement tests.
15. Run tests.
16. Review security.
17. Review performance.
18. Update documentation.

Do not implement the next sub-module until the current one is coherent and testable.

AFTER EACH IMPLEMENTATION:

Report:

1. Files created
2. Files modified
3. Database changes
4. Routes added
5. Permissions added
6. Environment variables added
7. Artisan commands required
8. Tests created
9. Test results
10. Security considerations
11. Deployment considerations
12. Rollback considerations

Do not claim that tests passed unless they were actually run.

The final result of Phase 0 must be a secure, testable, reusable HMS platform foundation upon which Patient Management can be implemented as Phase 1.