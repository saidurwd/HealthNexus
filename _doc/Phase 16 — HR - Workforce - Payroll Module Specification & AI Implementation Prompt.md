
# Phase 16 — HR / Workforce / Payroll
## Production-Grade Modular Architecture & AI Implementation Specification

## 1. Purpose

Phase 16 introduces the centralized **Human Resources, Workforce Management, Attendance, Leave, Time Management, Employee Lifecycle, Compensation, and Payroll** capability for the hospital/healthcare enterprise.

The module must support:

- Employee master data
- Employee lifecycle management
- Organization/department/position structure
- Job and position management
- Recruitment and onboarding foundation
- Employee contracts
- Employment history
- Attendance and time management
- Shift management
- Rostering/work schedules
- Leave management
- Holiday/calendar management
- Overtime
- Payroll
- Salary structures
- Allowances and deductions
- Loans/advances
- Bonuses/incentives
- Tax/statutory deduction foundation
- Payslips
- Payroll approval and locking
- Employee self-service
- Manager self-service
- Workforce analytics
- HR documents
- Performance-management foundation
- Training/certification foundation
- Compliance and expiry alerts
- Integration with biometric attendance devices
- Integration with Finance/Accounting
- Integration with existing Hospital modules
- Multi-organization / multi-hospital / multi-branch support
- Auditability and security
- API
- Reporting
- AI-assisted HR analytics and workforce optimization

The design must be suitable for a multi-hospital healthcare organization with employees such as:

- Doctors
- Nurses
- Pharmacists
- Lab technologists
- Radiology technologists
- Surgeons
- Anesthetists
- Administrative staff
- Finance staff
- IT staff
- Support staff
- Security
- Drivers
- Technicians
- Housekeeping
- Contract workers
- Temporary workers
- Consultants
- Visiting physicians

---

# 2. Architectural Principle

The existing Laravel modular architecture is **already implemented**.

The AI implementation agent MUST:

1. Inspect the existing repository.
2. Inspect Phases 0–15.
3. Identify existing models, services, traits, policies, workflows, notifications, audit mechanisms, API infrastructure, and database conventions.
4. Reuse existing architecture.
5. Extend the existing module structure.
6. NEVER create a second modular framework.
7. NEVER duplicate existing platform functionality.

Phase 16 must behave as a first-class module inside the existing application.

---

# 3. Existing Modules That Must Be Reused

Phase 16 must integrate with, rather than duplicate:

### Phase 0 — Foundation

Reuse:

- Organizations
- Hospitals
- Branches
- Departments
- Users
- Roles
- Permissions
- Access scopes
- Settings
- Master data
- Workflow
- Notifications
- Audit
- Activity logs
- Security events
- File management
- API framework
- Scheduler
- Queue
- Localization

### Phase 1 — Patient / MPI

HR must not duplicate patient identity.

Employee identity is separate from patient identity but may support a relationship where required.

Examples:

- Employee is also a patient
- Doctor is also a patient
- Employee dependent is a patient

### Phase 2 — Appointment

HR should provide provider availability/workforce information but must not duplicate appointment scheduling.

### Phase 3 — Clinical / EMR

HR provides:

- employee/provider identity
- employment status
- department
- designation
- specialty
- credential status

Clinical modules remain responsible for clinical records.

### Phase 4 — Billing

HR must not implement financial invoicing/payment logic.

Payroll is a separate financial domain.

### Phase 5 — LIS

Lab employees and staff may be linked to HR employee/provider records.

### Phase 6 — Radiology

Radiology staff should be linked to HR employee records.

### Phase 7 — Pharmacy

Pharmacists and pharmacy employees should be linked to HR employee records.

### Phase 8 — IPD

Nurses and inpatient staff should be linked to HR workforce/roster records.

### Phase 9 — Nursing

Nursing workforce, roster, shift, and employee data should integrate with Phase 16.

Phase 9 remains responsible for clinical nursing workflow.

### Phase 10 — OT

OT staff/team assignments should reference HR employee/provider records.

### Phase 11 — ICU

ICU staffing, roster, and workforce information should integrate with Phase 16.

### Phase 12 — Emergency

Emergency workforce and shift information should integrate with Phase 16.

### Phase 13 — Blood Bank

Blood Bank staff should reference HR employee/provider identity.

### Phase 14 — Insurance

HR should provide employee/dependent information where employee insurance benefits are applicable.

### Phase 15 — Inventory / Procurement

HR users and approval authorities should integrate with existing users/roles and approval workflows.

---

# 4. Core Domain Ownership

Phase 16 owns:

```text
Employee
Employment
Position
Job
Organization Structure
Workforce
Attendance
Shift
Roster
Leave
Overtime
Compensation
Payroll
Employee Benefits
Employee Documents
Employee Lifecycle
Training
Certification
Performance Foundation
HR Compliance
Employee Self-Service
Manager Self-Service
HR Analytics
```

It does NOT own:

```text
Hospital Patient
Clinical Encounter
Clinical Diagnosis
Clinical Prescription
Billing Invoice
Patient Payment
Inventory
Procurement
Laboratory Result
Radiology Report
Blood Inventory
Clinical Nursing Documentation
```

---

# 5. Enterprise HR Hierarchy

Support:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
Department
    ↓
Unit / Section
    ↓
Position
    ↓
Employee
```

The architecture should also support:

```text
Organization
 ├── Hospital A
 │    ├── Medicine
 │    ├── Surgery
 │    ├── Nursing
 │    └── Administration
 │
 └── Hospital B
      ├── Medicine
      ├── Laboratory
      └── Pharmacy
```

Employees may have:

- Primary organization
- Primary hospital
- Primary branch
- Primary department
- Secondary assignment
- Temporary assignment
- Multiple concurrent positions where policy permits

---

# 6. Employee Master

The Employee Master is the central HR identity.

Suggested fields:

```text
id
organization_id
employee_number
user_id nullable
person_reference
first_name
middle_name
last_name
display_name
gender
date_of_birth
nationality
national_id_reference
passport_reference
personal_email
official_email
mobile
alternate_mobile
present_address
permanent_address
emergency_contact
blood_group
marital_status
joining_date
employment_status
employment_type
employee_category
department_id
branch_id
hospital_id
position_id
designation_id
manager_employee_id
employment_end_date
termination_date
status
photo_file_id
created_at
updated_at
```

Sensitive information must have additional access restrictions.

---

# 7. Employee Numbering

Employee IDs must be:

- Unique
- Immutable
- Organization-aware
- Concurrency-safe

Example:

```text
EMP-2026-00001234
```

Never generate identifiers using:

```text
COUNT(*) + 1
```

Use a dedicated sequence/number-generation mechanism.

---

# 8. Employee Lifecycle

Support:

```text
Applicant
↓
Selected
↓
Offer
↓
Pre-Employment
↓
Onboarding
↓
Active
↓
Probation
↓
Confirmed
↓
Transferred
↓
Promoted
↓
Suspended
↓
Resigned
↓
Terminated
↓
Retired
```

The exact lifecycle must be configurable.

---

# 9. Employment Types

Configurable employment types:

- Permanent
- Probationary
- Contract
- Temporary
- Part-Time
- Consultant
- Visiting
- Intern
- Apprentice
- Outsourced
- Daily Worker
- Hourly Worker

Do not hardcode country-specific employment rules.

---

# 10. Employee Categories

Examples:

- Medical
- Nursing
- Allied Health
- Pharmacy
- Laboratory
- Radiology
- Administration
- Finance
- IT
- Engineering
- Security
- Support
- Housekeeping
- Transport
- Management

Configurable by organization.

---

# 11. Job & Position Management

Separate:

```text
Job
Position
Employee
```

Example:

```text
Job:
Senior Nurse

Position:
Senior Nurse - ICU - Hospital A

Employee:
EMP-000125
```

A job describes the role.

A position describes an organizational seat.

---

# 12. Job Master

Suggested fields:

```text
job_code
job_title
job_family
job_category
description
grade
minimum_salary
maximum_salary
required_qualification
required_experience
required_certifications
required_skills
is_active
```

---

# 13. Position Management

Support:

- Position creation
- Position approval
- Position allocation
- Position vacancy
- Position occupancy
- Position transfer
- Position closure

Position status:

```text
Draft
Approved
Vacant
Occupied
Frozen
Closed
```

---

# 14. Employee Organizational Assignment

Employees may have:

- Department
- Unit
- Position
- Designation
- Reporting manager
- Cost center
- Work location
- Hospital
- Branch
- Shift group

Historical changes must be retained.

Never overwrite employment history without recording the effective-dated change.

---

# 15. Employment History

Maintain:

```text
Joining
Confirmation
Promotion
Transfer
Department Change
Designation Change
Salary Revision
Manager Change
Branch Transfer
Hospital Transfer
Contract Renewal
Suspension
Reactivation
Resignation
Termination
Retirement
```

Every event should contain:

```text
effective_date
previous_value
new_value
reason
approved_by
approved_at
created_by
```

---

# 16. Employee Contracts

Support:

- Contract creation
- Contract versioning
- Start/end dates
- Renewal
- Probation
- Notice period
- Salary terms
- Working hours
- Benefits
- Allowances
- Contract documents
- Approval

Expiry alerts must be generated before contract expiry.

---

# 17. Employee Documents

Use the existing Phase 0 File Management system.

Do not create another file-storage engine.

Document categories:

- Appointment letter
- Contract
- National ID
- Passport
- Academic certificate
- Professional license
- Medical certificate
- Training certificate
- Experience certificate
- Tax document
- Bank information document
- Performance document
- Warning letter
- Promotion letter
- Transfer letter
- Resignation letter
- Termination document

Sensitive documents require restricted permissions.

---

# 18. Credential & License Management

Important for healthcare employees.

Support:

- Medical license
- Nursing license
- Pharmacy license
- Laboratory certification
- Radiology certification
- Professional registration
- ACLS/BLS
- Specialty certification
- Other configurable credentials

Each credential should support:

```text
employee_id
credential_type
credential_number
issuing_authority
issue_date
expiry_date
status
document_id
verified_by
verified_at
```

Statuses:

```text
Pending
Verified
Expiring
Expired
Suspended
Revoked
```

Automatic expiry alerts are required.

---

# 19. Recruitment Foundation

Phase 16 may include a lightweight recruitment workflow.

Support:

```text
Requisition
→ Approval
→ Vacancy
→ Applicant
→ Screening
→ Interview
→ Selection
→ Offer
→ Joining
```

Full enterprise ATS functionality is optional and should not be implemented unless specifically requested.

---

# 20. Onboarding

Onboarding checklist:

- Employee creation
- Documents
- User account
- Email
- Department assignment
- Position
- Manager
- ID card
- Access permissions
- Training
- Orientation
- Equipment assignment
- Payroll setup
- Bank information
- Benefits
- Compliance documents

Integrate equipment assignment with Phase 15 where appropriate.

---

# 21. Offboarding

Workflow:

```text
Resignation / Termination / Retirement
        ↓
Approval
        ↓
Notice Period
        ↓
Clearance
        ↓
Asset Return
        ↓
Access Revocation
        ↓
Final Attendance
        ↓
Final Payroll
        ↓
Benefits Settlement
        ↓
Exit Interview
        ↓
Employee Closed
```

Phase 15 should handle physical inventory/asset return.

IT/account access should use existing identity/security mechanisms.

---

# 22. Workforce Management

Phase 16 owns workforce planning.

Support:

- Workforce requirements
- Staffing levels
- Department staffing
- Position vacancies
- Shift requirements
- Staffing coverage
- Overtime monitoring
- Absence monitoring
- Workforce utilization

Healthcare staffing should support:

```text
Department
Unit
Skill
Position
Shift
Required Headcount
Assigned Headcount
Available Headcount
Shortage
```

---

# 23. Shift Management

Support configurable shifts:

```text
Morning
Evening
Night
General
Split
24 Hour
On Call
Custom
```

Shift fields:

```text
shift_code
name
start_time
end_time
break_duration
grace_minutes
working_minutes
crosses_midnight
is_active
```

Do not assume all shifts fit within one calendar day.

---

# 24. Shift Groups

Employees may belong to:

```text
Shift Group
Roster Pattern
Department Schedule
```

Examples:

```text
ICU Nursing A
Emergency Nursing B
Laboratory Night Team
Security Team C
```

---

# 25. Roster Management

Support:

- Daily roster
- Weekly roster
- Monthly roster
- Rotating roster
- Fixed roster
- On-call roster
- Weekend roster
- Holiday roster

Roster statuses:

```text
Draft
Submitted
Approved
Published
Locked
Cancelled
```

Once published, changes must be audited.

---

# 26. Roster Assignment

Suggested information:

```text
employee_id
department_id
shift_id
work_date
start_at
end_at
roster_type
assignment_type
status
approved_by
```

Validate:

- Double booking
- Shift overlap
- Rest-period policy
- Leave conflict
- Employee status
- Credential requirements
- Department staffing rules

---

# 27. Attendance Management

Support multiple attendance sources:

```text
Biometric Device
Mobile App
Web
Manual Entry
API
Imported File
```

The system should support integrations with devices such as:

- ZKTeco
- Hikvision
- Other biometric systems

Use an adapter/interface architecture rather than hardcoding one vendor.

Example:

```php
interface AttendanceProviderInterface
{
    public function fetchAttendance(...);

    public function normalizeAttendance(...);

    public function syncAttendance(...);
}
```

---

# 28. Attendance Data Architecture

Separate:

```text
Raw Attendance
        ↓
Normalized Attendance
        ↓
Attendance Calculation
        ↓
Daily Attendance
        ↓
Payroll Input
```

Never overwrite raw biometric/device records.

---

# 29. Attendance Record

Suggested fields:

```text
employee_id
attendance_date
first_in
last_out
worked_minutes
late_minutes
early_exit_minutes
overtime_minutes
status
source
device_reference
calculation_version
```

Daily attendance may have multiple punches.

---

# 30. Attendance States

Configurable:

```text
Present
Absent
Late
Early Exit
Half Day
Leave
Holiday
Weekend
Off Day
On Duty
Work From Home
Business Travel
Overtime
Missing Punch
Incomplete
```

---

# 31. Attendance Rules

Support:

- Grace period
- Late threshold
- Early exit
- Minimum working hours
- Break rules
- Cross-midnight shifts
- Overnight shifts
- Multiple punches
- Missing punch
- Manual correction
- Attendance regularization
- Department-specific rules

Rules must be configurable.

---

# 32. Attendance Regularization

Employee submits:

```text
Date
Missing Punch
Reason
Corrected Time
Evidence
```

Workflow:

```text
Employee
→ Manager
→ HR
→ Approved/Rejected
```

Approved corrections must be audited.

---

# 33. Leave Management

Support:

- Annual leave
- Sick leave
- Casual leave
- Maternity/paternity
- Medical leave
- Unpaid leave
- Emergency leave
- Study leave
- Compassionate leave
- Other configurable leave types

Do not hardcode statutory rules.

---

# 34. Leave Policy Engine

Leave policies should support:

```text
Eligibility
Accrual
Carry Forward
Encashment
Maximum Balance
Minimum Days
Maximum Consecutive Days
Notice Period
Approval Hierarchy
Document Requirement
Probation Rules
Service Length Rules
```

---

# 35. Leave Balance

Maintain transaction-based balances.

Do not only store:

```text
leave_balance = 15
```

Instead maintain:

```text
Opening
Accrual
Adjustment
Leave Taken
Cancellation
Carry Forward
Encashment
Expiry
```

This provides a complete audit trail.

---

# 36. Leave Workflow

```text
Employee
→ Leave Request
→ Validation
→ Manager Approval
→ HR Approval if required
→ Leave Approved
→ Attendance Updated
```

Rejected/cancelled requests must remain historically visible.

---

# 37. Holiday Calendar

Support:

- Organization holidays
- Hospital holidays
- Branch holidays
- Department holidays
- Religious holidays
- National holidays
- Custom holidays

Calendar should support multiple locations.

---

# 38. Overtime

Support:

- Regular overtime
- Holiday overtime
- Weekend overtime
- Night overtime
- Emergency overtime
- On-call compensation

Workflow:

```text
Roster / Attendance
→ Overtime Detection
→ Employee/Manager Request
→ Approval
→ Calculation
→ Payroll
```

---

# 39. Overtime Rules

Configurable:

```text
Minimum overtime minutes
Rounding
Rate multiplier
Holiday multiplier
Weekend multiplier
Night multiplier
Maximum monthly hours
Approval requirements
```

Do not hardcode jurisdiction-specific rates.

---

# 40. Compensation Management

Support:

- Basic salary
- Grade
- Salary structure
- Allowances
- Benefits
- Deductions
- Incentives
- Bonus
- Overtime
- Commission where applicable
- Arrears
- Adjustments

---

# 41. Salary Structure

Example:

```text
Basic
House Rent
Medical Allowance
Transport Allowance
Food Allowance
Communication Allowance
Other Allowance
Gross Salary
```

Do not assume this structure is universal.

All components must be configurable.

---

# 42. Salary Components

Each component should support:

```text
component_code
name
type
calculation_method
taxable
pensionable
recurring
effective_from
effective_to
priority
```

Types:

```text
Earning
Deduction
Benefit
Employer Contribution
Reimbursement
Adjustment
```

---

# 43. Salary Calculation Methods

Support:

```text
Fixed Amount
Percentage of Basic
Percentage of Gross
Per Day
Per Hour
Per Shift
Attendance Based
Overtime Based
Formula Based
```

A controlled formula engine may be introduced.

Never allow arbitrary executable code from administrators.

---

# 44. Payroll Period

Support:

```text
Monthly
Biweekly
Weekly
Custom
```

Payroll period states:

```text
Draft
Calculation
Reviewed
Submitted
Approved
Processed
Paid
Locked
Reopened
Cancelled
```

---

# 45. Payroll Processing

Canonical workflow:

```text
Payroll Period
    ↓
Employee Eligibility
    ↓
Attendance
    ↓
Leave
    ↓
Overtime
    ↓
Salary
    ↓
Allowances
    ↓
Benefits
    ↓
Deductions
    ↓
Loans/Advances
    ↓
Tax/Statutory Rules
    ↓
Adjustments
    ↓
Gross Pay
    ↓
Net Pay
    ↓
Review
    ↓
Approval
    ↓
Payroll Lock
    ↓
Payslip
    ↓
Accounting Export
```

---

# 46. Payroll Calculation

Conceptually:

```text
Gross Earnings
+
Overtime
+
Bonus
+
Allowances
+
Arrears
-
Employee Deductions
-
Tax
-
Loan/Advance
-
Other Deductions
=
Net Pay
```

Actual calculation must be driven by configurable payroll rules.

---

# 47. Payroll Calculation Engine

Use a deterministic calculation service.

Example:

```text
PayrollCalculationService
```

Responsibilities:

- Load employee compensation
- Load attendance
- Load approved leave
- Load overtime
- Load bonuses
- Load deductions
- Apply payroll rules
- Calculate gross
- Calculate deductions
- Calculate net
- Produce calculation breakdown
- Record calculation version

Payroll calculations must be reproducible.

---

# 48. Payroll Calculation Versioning

Every payroll run should record:

```text
rule_version
calculation_version
processed_at
processed_by
```

If rules change later, historical payroll must remain unchanged.

---

# 49. Payroll Locking

Once payroll is approved/locked:

- Do not silently modify.
- Do not silently recalculate.
- Do not delete payroll records.

Corrections require:

```text
Payroll Adjustment
```

or controlled:

```text
Payroll Reopen
```

with approval and audit trail.

---

# 50. Payslip

Payslip should include:

```text
Employee
Employee Number
Position
Department
Payroll Period
Basic Salary
Allowances
Overtime
Bonus
Gross
Deductions
Tax
Loans
Net Salary
Employer Contributions where applicable
Payment Information
```

Generate through the existing File Management/document infrastructure.

---

# 51. Payroll Payment

Phase 16 should create payment/export instructions.

Actual accounting/payment ownership depends on the Finance/Accounting integration.

Support:

- Bank payment file
- Payroll payment register
- Cash payment where allowed
- Payment status
- Payment reference

Do not implement a separate banking ledger.

---

# 52. Loans & Advances

Support:

- Employee advance
- Salary advance
- Employee loan
- Loan approval
- Principal
- Interest where applicable
- Installment schedule
- Deduction schedule
- Settlement
- Outstanding balance

Example lifecycle:

```text
Requested
→ Approved
→ Disbursed
→ Repayment
→ Completed
```

---

# 53. Benefits

Configurable employee benefits:

- Medical
- Insurance
- Transport
- Housing
- Meal
- Mobile
- Retirement/pension
- Other benefits

Support:

```text
Employee
→ Benefit Enrollment
→ Eligibility
→ Effective Date
→ Employer Contribution
→ Employee Contribution
```

---

# 54. Employee Dependents

Support:

```text
Employee
→ Dependent
```

Dependent fields:

```text
name
relationship
date_of_birth
gender
national_id/reference
insurance eligibility
status
```

Do not create a second patient identity system.

Where healthcare treatment is required, integrate with Phase 1 MPI.

---

# 55. Performance Management Foundation

Provide a lightweight performance framework:

```text
Performance Cycle
→ Goals
→ KPI
→ Employee Self Review
→ Manager Review
→ Final Review
→ Rating
→ Development Plan
```

Do not hardcode a universal rating methodology.

---

# 56. Training & Development

Support:

- Training catalog
- Training request
- Training approval
- Training session
- Attendance
- Completion
- Certification
- Expiry
- Training history

Healthcare organizations can use this for:

- BLS
- ACLS
- Infection control
- Fire safety
- Clinical training
- Compliance training

---

# 57. Employee Skills

Maintain:

```text
Skill
Skill Level
Certification
Years of Experience
Verification
Expiry
```

Useful for workforce planning and scheduling.

---

# 58. Employee Self-Service

Employees should be able to:

- View profile
- Request leave
- View attendance
- Request attendance correction
- View roster
- View payslip
- View salary history where permitted
- Submit overtime
- Submit expense/claim integration if implemented
- View documents
- Update permitted personal information
- Submit training requests
- View benefits
- Submit resignation

Sensitive HR data must remain restricted.

---

# 59. Manager Self-Service

Managers should be able to:

- View team
- Approve leave
- Approve attendance corrections
- Approve overtime
- View roster
- Review staffing
- Approve HR requests
- Review employee performance
- View team attendance
- View workforce alerts

Managers must only see employees within their authorized scope.

---

# 60. HR Administration

HR administrators can manage:

- Employee master
- Organization assignments
- Jobs
- Positions
- Contracts
- Salary structures
- Payroll rules
- Leave policies
- Attendance policies
- Shifts
- Rosters
- Benefits
- Training
- Credentials
- HR documents

High-risk payroll actions require additional approval.

---

# 61. Payroll Approval

Recommended separation:

```text
HR Operator
    ↓
Payroll Processor
    ↓
Payroll Reviewer
    ↓
Payroll Approver
    ↓
Finance/Accounting
```

The same user should not automatically perform all stages.

---

# 62. Attendance Integration Architecture

Use adapters:

```text
AttendanceProviderInterface
```

Possible implementations:

```text
ZKTecoAttendanceProvider
HikvisionAttendanceProvider
MobileAttendanceProvider
WebAttendanceProvider
CSVAttendanceProvider
APIAttendanceProvider
```

Device synchronization should use:

```text
Device
→ Raw Punch
→ Normalization
→ Employee Mapping
→ Attendance Calculation
```

---

# 63. Biometric Device Master

Suggested fields:

```text
id
organization_id
hospital_id
branch_id
device_code
device_name
vendor
model
serial_number
ip_address
port
location
timezone
sync_method
last_sync_at
status
```

Credentials/secrets must be encrypted.

Never expose device passwords through API responses.

---

# 64. Attendance Sync

Scheduled jobs:

```text
SyncAttendanceDevices
ProcessRawAttendance
CalculateDailyAttendance
DetectMissingPunches
GenerateAttendanceExceptions
```

Use queues for large data volumes.

Sync operations must be idempotent.

---

# 65. Payroll Integration With Attendance

Payroll should consume finalized attendance.

Do not calculate salary directly from raw biometric punches.

Canonical flow:

```text
Raw Punch
→ Attendance Processing
→ Attendance Approval/Finalization
→ Payroll Input
→ Payroll Calculation
```

---

# 66. Finance / Accounting Integration

Payroll should expose an accounting integration interface.

Example:

```text
Payroll
→ Payroll Journal
→ Accounting System
```

Potential entries:

```text
Salary Expense
Allowance Expense
Overtime Expense
Employer Contribution
Tax Payable
Employee Deduction Payable
Net Salary Payable
```

Actual chart-of-accounts mapping must remain configurable.

---

# 67. Chargeable / Financial Boundary

Phase 16 does not use the patient Billing Engine for payroll.

Payroll is an enterprise financial process.

However:

```text
Payroll
→ Accounting Integration
```

not:

```text
Payroll
→ Patient Billing
```

---

# 68. HR Notifications

Examples:

### Employee

- Leave approved
- Leave rejected
- Roster published
- Payslip available
- Contract expiring
- Credential expiring
- Training due
- Payroll processed

### Manager

- Leave pending
- Attendance exception
- Overtime approval
- Staffing shortage
- Credential expiry

### HR

- Contract expiry
- Credential expiry
- Employee probation due
- Missing documents
- Attendance anomalies
- Payroll exception

### Finance

- Payroll approval
- Payroll journal ready
- Payment file ready

---

# 69. Workflow Integration

Reuse Phase 0 Workflow.

Examples:

```text
Leave Approval
Attendance Regularization
Overtime Approval
Salary Revision
Promotion
Transfer
Contract Approval
Payroll Approval
Payroll Reopen
Employee Termination
Employee Advance
Loan Approval
```

Do not build a second approval engine.

---

# 70. Audit Requirements

Audit all sensitive actions:

```text
Employee Created
Employee Updated
Employee Transferred
Employee Promoted
Salary Changed
Leave Approved
Attendance Modified
Roster Published
Overtime Approved
Payroll Calculated
Payroll Approved
Payroll Locked
Payroll Reopened
Payslip Generated
Bank File Generated
Loan Approved
Employee Terminated
Credential Verified
Credential Revoked
```

Payroll audit must record:

```text
old_value
new_value
reason
user
timestamp
request_id
IP
```

---

# 71. Security

HR contains highly sensitive personal and financial information.

Implement:

- RBAC
- Organization scope
- Hospital scope
- Branch scope
- Department scope
- Manager scope
- Employee self scope
- Field-level authorization where appropriate
- Encryption for sensitive fields
- Secure document storage
- Audit logging
- Session controls
- Export restrictions
- Payroll approval separation

---

# 72. Access Scope

Examples:

### HR Corporate

Can access all organizations.

### Hospital HR

Can access only assigned hospital.

### Department Manager

Can access employees in authorized departments.

### Employee

Can access own permitted information.

### Payroll Officer

Can access payroll information within assigned scope.

### Finance

Can access approved payroll financial outputs without automatically accessing all HR personal information.

---

# 73. Multi-Hospital Security Test

Mandatory:

```text
Hospital A HR user
        ↓
Attempt to access
Hospital B employee
        ↓
ACCESS DENIED
```

Test:

- UI
- API
- Direct ID manipulation
- Export
- Reports
- Search
- Bulk actions
- Payroll
- Documents

---

# 74. Suggested Database Tables

The exact schema MUST be adapted to the existing repository.

Potential tables:

### Employee

```text
hr_employees
hr_employee_contacts
hr_employee_addresses
hr_employee_emergency_contacts
hr_employee_dependents
hr_employee_identifiers
hr_employee_assignments
hr_employee_history
hr_employee_status_history
hr_employee_documents
```

### Organization Structure

```text
hr_job_families
hr_jobs
hr_positions
hr_position_history
hr_designations
hr_grades
hr_cost_centers
```

### Employment

```text
hr_employment_contracts
hr_contract_versions
hr_probation_records
hr_promotions
hr_transfers
hr_resignations
hr_terminations
hr_retirements
```

### Credentials

```text
hr_credential_types
hr_employee_credentials
hr_credential_verifications
```

### Recruitment

```text
hr_requisitions
hr_requisition_items
hr_candidates
hr_candidate_documents
hr_interviews
hr_offers
```

### Onboarding / Offboarding

```text
hr_onboarding_checklists
hr_onboarding_tasks
hr_offboarding_checklists
hr_offboarding_tasks
hr_exit_interviews
hr_clearances
```

### Workforce

```text
hr_workforce_requirements
hr_staffing_plans
hr_skills
hr_employee_skills
```

### Shifts

```text
hr_shifts
hr_shift_groups
hr_shift_rules
hr_roster_patterns
hr_rosters
hr_roster_assignments
```

### Attendance

```text
hr_attendance_devices
hr_attendance_device_mappings
hr_attendance_raw_punches
hr_attendance_records
hr_attendance_exceptions
hr_attendance_regularizations
hr_attendance_calculation_runs
```

### Leave

```text
hr_leave_types
hr_leave_policies
hr_leave_policy_rules
hr_leave_balances
hr_leave_transactions
hr_leave_requests
hr_leave_request_events
hr_holiday_calendars
hr_holidays
```

### Overtime

```text
hr_overtime_rules
hr_overtime_requests
hr_overtime_records
```

### Compensation

```text
hr_salary_structures
hr_salary_structure_items
hr_employee_salary_assignments
hr_salary_revisions
hr_salary_components
hr_employee_allowances
hr_employee_deductions
hr_employee_benefits
```

### Payroll

```text
hr_payroll_periods
hr_payroll_runs
hr_payroll_employees
hr_payroll_items
hr_payroll_calculations
hr_payroll_adjustments
hr_payroll_approvals
hr_payroll_locks
hr_payslips
hr_payroll_payments
hr_payroll_payment_items
hr_payroll_accounting_entries
```

### Loans / Advances

```text
hr_employee_advances
hr_employee_loans
hr_loan_installments
hr_loan_transactions
```

### Performance

```text
hr_performance_cycles
hr_performance_goals
hr_performance_reviews
hr_performance_review_items
hr_performance_development_plans
```

### Training

```text
hr_training_categories
hr_training_courses
hr_training_sessions
hr_training_enrollments
hr_training_attendance
hr_training_certificates
```

### Benefits

```text
hr_benefit_types
hr_benefit_plans
hr_employee_benefits
hr_benefit_transactions
```

### Compliance

```text
hr_compliance_requirements
hr_employee_compliance
hr_compliance_events
```

---

# 75. Important Database Design Rules

Use:

- Foreign keys
- Composite indexes
- Unique constraints
- Check constraints where supported
- Soft deletion only where appropriate
- Effective-dated records
- Immutable historical transactions
- Decimal types for monetary amounts
- UTC timestamps
- Organization/hospital/branch scope

Never use floating-point types for money.

Use appropriate decimal precision, for example:

```text
DECIMAL(18,4)
```

or repository-standard equivalent.

---

# 76. Payroll Transaction Integrity

Payroll must be transaction-safe.

A payroll run should use database transactions for critical state changes.

Example:

```text
BEGIN TRANSACTION

Create Payroll Run
Calculate Employees
Create Payroll Items
Create Deductions
Create Net Pay
Create Approval Record
Lock Payroll

COMMIT
```

On failure:

```text
ROLLBACK
```

No partially completed payroll should be treated as finalized.

---

# 77. Concurrency Protection

Protect:

- Payroll processing
- Payroll approval
- Payroll locking
- Payroll reopening
- Leave balance updates
- Attendance finalization
- Roster publication
- Overtime approval
- Loan repayment
- Salary revision
- Employee status transition

Use:

- Database transactions
- Row locking
- Unique constraints
- Idempotency keys
- State-transition validation

Two payroll processors must not finalize the same payroll run concurrently.

---

# 78. Idempotency

Required for:

- Attendance synchronization
- Payroll calculation
- Bank file generation
- Accounting export
- Employee imports
- Device synchronization
- API requests

Example:

```text
source + device_id + punch_reference
```

must uniquely identify a raw attendance event.

---

# 79. Payroll Import / Export

Support controlled:

- Employee import
- Salary import
- Attendance import
- Bank export
- Accounting export
- Payroll report export

All bulk imports must provide:

- Validation
- Preview
- Error report
- Transactional processing
- Duplicate detection
- Audit trail

---

# 80. Reporting

### HR

- Employee master
- Headcount
- New hires
- Resignations
- Terminations
- Transfers
- Promotions
- Employee turnover
- Department headcount
- Position vacancies
- Contract expiry
- Credential expiry

### Attendance

- Daily attendance
- Late arrival
- Early departure
- Absenteeism
- Missing punches
- Attendance exceptions
- Overtime

### Leave

- Leave utilization
- Leave balance
- Department leave
- Pending approvals
- Leave trends

### Payroll

- Payroll register
- Gross payroll
- Net payroll
- Allowance report
- Deduction report
- Overtime cost
- Tax/statutory report
- Employer contribution
- Department payroll
- Hospital payroll
- Cost-center payroll

### Workforce

- Staffing requirement
- Staffing shortage
- Shift coverage
- Overtime utilization
- Workforce utilization

---

# 81. KPI Dashboard

Recommended KPIs:

```text
Total Employees
Active Employees
New Employees
Exits
Turnover
Vacancies
Attendance %
Absenteeism %
Late %
Overtime Hours
Leave Utilization
Payroll Cost
Average Payroll Cost
Department Headcount
Staffing Gap
Credential Expiry
Contract Expiry
Training Compliance
```

---

# 82. Workforce Dashboard

Provide:

```text
Headcount
Required Headcount
Available Headcount
Rostered Headcount
Present Headcount
Absent Headcount
Overtime
Leave
Open Positions
Critical Staffing Shortage
```

Healthcare-specific views:

```text
Doctor Coverage
Nursing Coverage
ICU Coverage
Emergency Coverage
OT Coverage
Night Shift Coverage
```

---

# 83. API

Base paths:

```text
/api/v1/hr
/api/v1/workforce
/api/v1/attendance
/api/v1/leave
/api/v1/payroll
```

Examples:

```http
GET    /api/v1/hr/employees
POST   /api/v1/hr/employees
GET    /api/v1/hr/employees/{id}
PUT    /api/v1/hr/employees/{id}

GET    /api/v1/workforce/rosters
POST   /api/v1/workforce/rosters

GET    /api/v1/attendance
POST   /api/v1/attendance/regularizations

GET    /api/v1/leave/requests
POST   /api/v1/leave/requests
POST   /api/v1/leave/requests/{id}/approve

GET    /api/v1/payroll/periods
POST   /api/v1/payroll/runs
POST   /api/v1/payroll/runs/{id}/calculate
POST   /api/v1/payroll/runs/{id}/approve
POST   /api/v1/payroll/runs/{id}/lock
```

Use the existing API response conventions.

---

# 84. Standard API Response

Success:

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": {},
  "meta": {}
}
```

Validation failure:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "employee_id": [
      "The employee field is required."
    ]
  }
}
```

Never expose:

- SQL errors
- Stack traces
- File paths
- Passwords
- Device credentials
- Internal secrets

---

# 85. Events

Potential domain events:

```text
EmployeeCreated
EmployeeActivated
EmployeeTransferred
EmployeePromoted
EmployeeSuspended
EmployeeTerminated
EmployeeResigned
ContractCreated
ContractExpiring
CredentialExpiring
CredentialExpired

RosterCreated
RosterPublished
RosterChanged

AttendanceReceived
AttendanceProcessed
AttendanceExceptionCreated
AttendanceRegularized

LeaveRequested
LeaveApproved
LeaveRejected
LeaveCancelled

OvertimeRequested
OvertimeApproved

SalaryRevised
BenefitEnrolled

PayrollCalculationStarted
PayrollCalculated
PayrollSubmitted
PayrollApproved
PayrollLocked
PayrollReopened
PayslipGenerated

LoanApproved
AdvanceApproved

TrainingCompleted
PerformanceReviewCompleted
```

---

# 86. Scheduled Jobs

Examples:

```text
SyncAttendanceDevices
ProcessRawAttendance
CalculateDailyAttendance
DetectAttendanceExceptions
GenerateAttendanceSummary

UpdateLeaveBalances
ExpireLeaveBalances
GenerateLeaveExpiryAlerts

GenerateRosterAlerts
DetectRosterConflicts

GenerateContractExpiryAlerts
GenerateCredentialExpiryAlerts
GenerateProbationAlerts

CalculatePayroll
GeneratePayrollExceptions
GeneratePayslips
GeneratePayrollReports

GenerateLoanDueAlerts
GenerateTrainingExpiryAlerts
```

Use Laravel Scheduler + Queue.

---

# 87. Notifications

Support:

- Database
- Email
- SMS adapter where configured
- Push notification adapter where configured

Do not hardcode one notification provider.

---

# 88. Role & Permission Model

Suggested permissions:

```text
hr.dashboard.view

hr.employee.view
hr.employee.create
hr.employee.update
hr.employee.activate
hr.employee.suspend
hr.employee.terminate

hr.employee.document.view
hr.employee.document.manage

hr.employee.history.view

hr.job.view
hr.job.create
hr.job.update

hr.position.view
hr.position.create
hr.position.update
hr.position.approve

hr.contract.view
hr.contract.create
hr.contract.update
hr.contract.approve

hr.credential.view
hr.credential.verify
hr.credential.manage

hr.recruitment.view
hr.recruitment.manage

hr.onboarding.view
hr.onboarding.manage

hr.offboarding.view
hr.offboarding.manage

workforce.dashboard.view
workforce.staffing.view
workforce.roster.view
workforce.roster.create
workforce.roster.update
workforce.roster.publish
workforce.roster.approve

attendance.view
attendance.manage
attendance.regularization.create
attendance.regularization.approve
attendance.device.manage
attendance.sync

leave.view
leave.request
leave.approve
leave.policy.manage
leave.balance.manage

overtime.view
overtime.request
overtime.approve
overtime.rule.manage

compensation.view
compensation.manage
compensation.salary.approve

payroll.view
payroll.calculate
payroll.review
payroll.approve
payroll.lock
payroll.reopen
payroll.export

payroll.payslip.view
payroll.payslip.generate

hr.loan.view
hr.loan.create
hr.loan.approve

hr.performance.view
hr.performance.manage

hr.training.view
hr.training.manage

hr.reports.view
hr.reports.export

hr.audit.view
hr.settings.manage
```

---

# 89. Suggested Roles

```text
HR Administrator
HR Manager
HR Officer
Recruitment Officer
Payroll Officer
Payroll Manager
Payroll Reviewer
Payroll Approver
Attendance Officer
Workforce Manager
Roster Coordinator
Department Manager
Line Manager
Employee
Finance Payroll User
HR Auditor
System Administrator
```

---

# 90. UI Structure

```text
HR & Workforce
├── Dashboard
├── Employees
│   ├── Employee List
│   ├── Employee Profile
│   ├── Documents
│   ├── Employment History
│   ├── Credentials
│   └── Dependents
│
├── Organization
│   ├── Jobs
│   ├── Positions
│   ├── Designations
│   ├── Grades
│   └── Cost Centers
│
├── Recruitment
├── Onboarding
├── Offboarding
│
├── Workforce
│   ├── Staffing
│   ├── Shifts
│   ├── Shift Groups
│   ├── Rosters
│   └── Coverage
│
├── Attendance
│   ├── Dashboard
│   ├── Raw Punches
│   ├── Daily Attendance
│   ├── Exceptions
│   ├── Regularization
│   └── Devices
│
├── Leave
│   ├── Requests
│   ├── Calendar
│   ├── Balances
│   ├── Policies
│   └── Holidays
│
├── Overtime
│   ├── Requests
│   ├── Approvals
│   └── Rules
│
├── Compensation
│   ├── Salary Structures
│   ├── Salary Revisions
│   ├── Allowances
│   ├── Deductions
│   └── Benefits
│
├── Payroll
│   ├── Periods
│   ├── Payroll Runs
│   ├── Review
│   ├── Approval
│   ├── Payslips
│   ├── Payments
│   └── Accounting Export
│
├── Loans & Advances
├── Performance
├── Training
├── Reports
└── Settings
```

---

# 91. Employee 360

Employee profile should provide:

```text
Overview
Personal Information
Employment
Organization
Position
Manager
Attendance
Roster
Leave
Overtime
Payroll
Benefits
Loans
Performance
Training
Credentials
Documents
Assets
History
Audit
```

The employee 360 page should aggregate information from existing modules without duplicating ownership.

---

# 92. Integration With Hospital Workforce

Healthcare-specific integrations should allow:

```text
HR Employee
      ↓
Provider / Staff Identity
      ↓
Clinical Module
```

Examples:

```text
Employee → Doctor → Appointment
Employee → Nurse → Nursing
Employee → Pharmacist → Pharmacy
Employee → Lab Technologist → LIS
Employee → Radiology Technologist → RIS
Employee → Surgeon → OT
Employee → Blood Bank Technologist → Blood Bank
Employee → ICU Nurse → ICU
Employee → ED Nurse → Emergency
```

The exact provider model must be reused if already implemented.

---

# 93. Integration With Phase 15 Inventory

During onboarding/offboarding:

```text
Employee
   ↓
Asset Assignment
   ↓
Phase 15 Inventory/Asset Process
```

During offboarding:

```text
Employee Exit
   ↓
Asset Clearance
   ↓
Inventory Return
   ↓
HR Clearance
```

Do not duplicate asset inventory inside HR.

---

# 94. Accounting Integration

Expose an interface such as:

```php
interface PayrollAccountingServiceInterface
{
    public function prepareJournal(int $payrollRunId);

    public function exportJournal(int $payrollRunId);

    public function reconcile(int $payrollRunId);
}
```

Do not hardcode a specific accounting package.

---

# 95. AI Readiness

AI should be an advisory capability.

Potential use cases:

### Workforce Forecasting

Predict staffing demand using:

- Historical workload
- Department activity
- Shift patterns
- Leave
- Absence
- Seasonal patterns
- Overtime
- Patient volume where legitimately available

### Attendance Anomaly Detection

Detect:

- Unusual punch patterns
- Repeated missing punches
- Duplicate punches
- Unusual overtime
- Device anomalies
- Attendance deviations

AI must flag anomalies, not automatically accuse employees of misconduct.

### Workforce Optimization

Suggest:

- Staffing gaps
- Shift coverage issues
- Overtime risks
- Excess staffing
- Upcoming shortage

### Leave Forecasting

Predict:

- High leave periods
- Staffing impact
- Coverage requirements

### Payroll Anomaly Detection

Detect:

- Unexpected salary changes
- Duplicate payments
- Abnormal allowances
- Unusual deductions
- Large payroll variance
- Unexpected overtime

AI findings require human review.

---

# 96. AI Payroll Safety

AI MUST NOT autonomously:

- Change salary
- Approve payroll
- Reject payroll
- Terminate employee
- Deny leave
- Approve overtime
- Change tax rules
- Modify attendance
- Modify employee identity
- Modify employment status
- Create unauthorized deductions
- Generate payment without authorization

AI may:

```text
Detect
Summarize
Forecast
Explain
Recommend
Flag
```

Human users remain responsible for decisions.

---

# 97. AI HR Assistant

Potential capabilities:

```text
"Show departments with staffing shortages."

"Which employee contracts expire within 60 days?"

"Summarize attendance exceptions for this department."

"Why did payroll increase this month?"

"Show unusual overtime patterns."

"Summarize the payroll variance."

"Which credentials are expiring?"

"Forecast next month's staffing requirement."
```

Responses must respect the user's access scope.

An HR manager must never receive data from unauthorized hospitals/departments simply because an AI query was used.

---

# 98. AI Explainability

Every AI-generated recommendation should contain:

```text
Recommendation
Reason
Data Period
Key Factors
Confidence / Uncertainty where appropriate
Source Data
Generated At
Model/Rule Version
```

AI recommendations should be auditable.

---

# 99. Privacy

Employee information may contain highly sensitive personal and financial data.

Implement:

- Minimum necessary access
- Encryption
- Restricted exports
- Audit logging
- Data masking where appropriate
- Secure documents
- Access expiration where appropriate
- Strong authorization
- Separation of HR and payroll privileges

---

# 100. Compliance Architecture

The system must be configurable for the jurisdiction in which it operates.

Do not hardcode:

- Tax rates
- Social security rates
- Pension rules
- Minimum wage
- Statutory leave
- Overtime multipliers
- Retirement age
- Notice period

Instead create configuration/rule structures that can be changed with effective dates.

---

# 101. Effective-Dated Configuration

Rules should support:

```text
effective_from
effective_to
version
status
approved_by
approved_at
```

This applies to:

- Salary structures
- Tax rules
- Leave policies
- Overtime rules
- Benefits
- Payroll components
- Attendance rules

Historical payroll must use the rules applicable to that payroll period.

---

# 102. Data Migration

Provide migration tools for:

- Existing employees
- Departments
- Positions
- Salary
- Attendance history
- Leave balances
- Payroll history
- Credentials
- Documents

Migration must support:

```text
Import
→ Validate
→ Preview
→ Correct
→ Approve
→ Commit
```

Never directly insert production data without validation.

---

# 103. Performance

Design for potentially:

```text
10,000+ employees
Millions of attendance punches
Thousands of payroll transactions
Large document collections
Multiple hospitals
```

Use:

- Indexing
- Pagination
- Chunk processing
- Queues
- Batch inserts
- Batch calculations
- Lazy loading where appropriate
- Cached configuration
- Aggregation tables where justified

Do not load millions of attendance records into memory.

---

# 104. Testing Strategy

Implement:

### Unit Tests

- Payroll calculations
- Leave calculations
- Overtime
- Attendance rules
- Salary formulas
- Loan repayment
- Benefit calculations

### Feature Tests

- Employee lifecycle
- Leave workflow
- Attendance regularization
- Roster
- Payroll processing
- Payroll approval
- Payslip
- Employee self-service

### Integration Tests

- Biometric provider
- Accounting integration
- Phase 15 asset integration
- Hospital provider integration

### Security Tests

- Organization isolation
- Hospital isolation
- Department scope
- Employee self-access
- Payroll restrictions
- Document authorization
- API authorization
- Export authorization

---

# 105. Critical Negative Tests

The system must reject:

```text
Duplicate employee number
Duplicate attendance punch
Unauthorized employee access
Unauthorized salary change
Unauthorized payroll approval
Payroll approval before calculation
Payroll lock before approval
Payroll modification after lock
Unauthorized payroll reopen
Leave exceeding policy
Leave without required approval
Overlapping roster
Overlapping shifts
Invalid overtime
Expired credential assignment where prohibited
Invalid salary component
Duplicate bank payment
Unauthorized export
Hospital A → Hospital B access
```

---

# 106. Payroll Reconciliation

Before locking payroll, validate:

```text
Employee Count
Gross Salary
Total Deductions
Net Salary
Employer Contributions
Loan Deductions
Tax
Overtime
Allowances
Adjustments
```

Provide reconciliation totals.

Example:

```text
Gross Payroll
+ Employer Contributions
= Total Employer Cost
```

and:

```text
Gross
- Employee Deductions
= Net Payroll
```

---

# 107. Payroll Exception Dashboard

Before approval, show:

```text
Employees with zero salary
Employees with unusually high salary change
Employees with duplicate bank account
Employees with missing attendance
Employees with excessive overtime
Employees with negative net pay
Employees with missing deductions
Employees with inactive status but payroll
Employees missing required data
Employees with large month-on-month variance
```

These should be exception flags, not automatic rejection unless a configured hard validation requires it.

---

# 108. Data Retention

Retention must be configurable by organization and jurisdiction.

Do not physically delete legally/operationally required payroll or employment history.

Use archival strategies for old data where appropriate.

---

# 109. Non-Goals

Phase 16 should NOT implement:

- Full hospital patient management
- Clinical EMR
- Patient billing
- Patient payment
- Inventory
- Procurement
- Pharmacy stock
- Blood inventory
- Laboratory management
- Radiology/PACS
- Full accounting ledger
- Full banking platform
- Full enterprise ATS unless separately scoped
- Autonomous HR decision-making

---

# 110. Recommended Implementation Order

Implement in this sequence:

```text
1. Inspect existing architecture
2. Inspect Phases 0–15
3. Map integrations and existing models
4. Employee master
5. Organization/job/position
6. Employment lifecycle
7. Contracts
8. Documents
9. Credentials
10. Recruitment foundation
11. Onboarding/offboarding
12. Workforce/staffing
13. Shift management
14. Roster
15. Attendance architecture
16. Biometric integrations
17. Attendance calculation
18. Attendance regularization
19. Holiday calendar
20. Leave policy
21. Leave balances
22. Leave workflow
23. Overtime
24. Compensation
25. Salary structures
26. Benefits
27. Loans/advances
28. Payroll engine
29. Payroll approval
30. Payroll locking
31. Payslips
32. Payroll payment/export
33. Accounting integration
34. Employee self-service
35. Manager self-service
36. Performance foundation
37. Training/certification
38. Compliance
39. Dashboards
40. Reports
41. API
42. Notifications
43. Audit
44. Security
45. AI extension points
46. Tests
47. Performance optimization
48. Documentation
```

---

# 111. Definition of Done

Phase 16 is complete only when:

### HR

- Employee master implemented
- Employee lifecycle implemented
- Job/position management implemented
- Employment history implemented
- Contract management implemented
- Credential management implemented
- Employee documents implemented
- Recruitment foundation implemented
- Onboarding implemented
- Offboarding implemented

### Workforce

- Workforce planning implemented
- Shift management implemented
- Roster implemented
- Staffing coverage implemented
- Conflict detection implemented

### Attendance

- Device abstraction implemented
- Raw attendance storage implemented
- Attendance normalization implemented
- Daily calculation implemented
- Exception management implemented
- Regularization implemented
- Device synchronization implemented

### Leave

- Leave policies implemented
- Leave balances implemented
- Leave transactions implemented
- Leave workflow implemented
- Holiday calendar implemented

### Overtime

- Overtime rules implemented
- Overtime requests implemented
- Approval implemented
- Payroll integration implemented

### Payroll

- Salary structures implemented
- Salary components implemented
- Benefits implemented
- Deductions implemented
- Loans/advances implemented
- Payroll calculation engine implemented
- Payroll approval implemented
- Payroll locking implemented
- Payslips implemented
- Payment export implemented
- Accounting integration interface implemented
- Payroll reconciliation implemented

### Employee Experience

- Employee self-service implemented
- Manager self-service implemented

### Healthcare Integration

- Doctor/provider integration
- Nursing integration
- Pharmacy integration
- Laboratory integration
- Radiology integration
- OT integration
- ICU integration
- Emergency integration
- Blood Bank integration

### Security

- RBAC implemented
- Organization isolation implemented
- Hospital isolation implemented
- Department scope implemented
- Payroll security implemented
- Document security implemented
- Audit logging implemented

### Reliability

- Transactions
- Concurrency controls
- Idempotency
- State validation
- Error handling
- Queue processing
- Scheduled jobs

### AI

- AI extension architecture implemented
- Workforce analytics extension points implemented
- Payroll anomaly detection extension points implemented
- Human approval controls implemented
- AI auditability implemented

### Testing

- Unit tests
- Feature tests
- Integration tests
- Security tests
- Negative tests
- Concurrency tests
- Payroll calculation tests

### Documentation

- Database documentation
- API documentation
- Workflow documentation
- Permission matrix
- Payroll calculation documentation
- Attendance integration documentation
- Deployment documentation
- Configuration documentation

---

# 112. FINAL AI IMPLEMENTATION PROMPT

Use the following prompt with an AI coding agent.

---

## AI CODING AGENT PROMPT — PHASE 16

You are a senior Laravel enterprise architect and software engineer.

Implement **Phase 16 — HR / Workforce / Payroll** in the existing Hospital Management System.

### FIRST: INSPECT THE REPOSITORY

Before writing code:

1. Inspect the complete repository structure.
2. Identify the existing Laravel version and PHP version.
3. Inspect the existing modular architecture.
4. Inspect Phases 0–15.
5. Inspect:
   - Models
   - Migrations
   - Services
   - Actions
   - Repositories if present
   - Controllers
   - Form Requests
   - Policies
   - Gates
   - Events
   - Listeners
   - Jobs
   - Notifications
   - Workflows
   - Audit
   - File Management
   - API architecture
   - Tests
   - UI conventions
6. Identify existing equivalents before creating anything.
7. Produce an architecture/dependency map before implementation.

### CRITICAL RULE

The modular architecture already exists.

**DO NOT create another modular framework.**

Reuse the existing architecture and conventions.

---

## IMPLEMENT PHASE 16

Implement:

```text
HR
Workforce
Employee Lifecycle
Jobs
Positions
Contracts
Credentials
Recruitment Foundation
Onboarding
Offboarding
Shift Management
Roster
Attendance
Biometric Integration
Leave
Overtime
Compensation
Benefits
Loans
Payroll
Payslips
Employee Self-Service
Manager Self-Service
Performance Foundation
Training
Compliance
Reports
Dashboards
API
Notifications
Audit
AI Extension Points
```

---

## ARCHITECTURAL BOUNDARIES

Do NOT duplicate:

```text
Organization
Hospital
Branch
Department
Users
Roles
Permissions
Workflow
Notifications
Audit
Files
Patient/MPI
Appointments
Clinical Encounter
Billing
Laboratory
Radiology
Pharmacy
IPD
Nursing
OT
ICU
Emergency
Blood Bank
Inventory
Procurement
```

Reuse existing implementations.

---

## EMPLOYEE ARCHITECTURE

Implement:

```text
Employee
Employment
Assignment
Job
Position
Designation
Grade
Contract
Credential
Manager
Department
Hospital
Branch
Cost Center
```

Use effective-dated history.

Do not silently overwrite employment history.

---

## ATTENDANCE

Implement:

```text
Device
Raw Punch
Normalization
Employee Mapping
Daily Calculation
Exceptions
Regularization
Finalization
Payroll Input
```

Implement provider abstraction:

```php
AttendanceProviderInterface
```

Do not hardcode one biometric vendor.

Attendance synchronization must be idempotent.

Never modify raw attendance records silently.

---

## SHIFT AND ROSTER

Implement:

```text
Shift
Shift Rule
Shift Group
Roster Pattern
Roster
Roster Assignment
Staffing Requirement
Coverage
```

Prevent:

- overlapping shifts
- duplicate assignment
- unauthorized roster publication
- leave conflicts

Use transactions and concurrency controls.

---

## LEAVE

Implement:

```text
Leave Type
Leave Policy
Leave Balance
Leave Transaction
Leave Request
Leave Approval
Holiday Calendar
```

Leave balances must be transaction-based.

Never simply overwrite a leave balance.

---

## PAYROLL

Implement a deterministic payroll engine.

Workflow:

```text
Payroll Period
→ Employee Eligibility
→ Attendance
→ Leave
→ Overtime
→ Salary
→ Allowances
→ Benefits
→ Deductions
→ Loans
→ Tax/Statutory Rules
→ Adjustments
→ Gross
→ Net
→ Review
→ Approval
→ Lock
→ Payslip
→ Accounting Export
```

Payroll calculations must be reproducible.

Store calculation/rule versions.

Use database transactions.

Protect payroll processing from concurrent execution.

---

## PAYROLL SECURITY

Implement strict authorization for:

```text
Salary
Payroll
Bank Information
Deductions
Tax
Loans
Payslips
Payroll Export
Payroll Approval
Payroll Reopen
```

A normal HR user must not automatically have payroll approval rights.

---

## PAYROLL LOCKING

After payroll is locked:

DO NOT silently modify it.

Corrections must use:

```text
Adjustment
```

or controlled:

```text
Reopen
```

with authorization and audit.

---

## HEALTHCARE INTEGRATION

Integrate employee/provider records with:

```text
OPD
Nursing
Pharmacy
LIS
Radiology
OT
ICU
Emergency
Blood Bank
IPD
```

Reuse existing provider/user architecture.

Do not create duplicate healthcare identities.

---

## INVENTORY INTEGRATION

Employee onboarding/offboarding may integrate with Phase 15.

For example:

```text
Employee Onboarding
→ Asset Assignment

Employee Offboarding
→ Asset Return
→ Clearance
```

Do not create an HR asset-management system.

---

## ACCOUNTING INTEGRATION

Implement an abstraction:

```php
PayrollAccountingServiceInterface
```

Support:

```text
Prepare Journal
Export Journal
Reconcile
```

Do not create a second accounting ledger.

---

## API

Implement:

```text
/api/v1/hr
/api/v1/workforce
/api/v1/attendance
/api/v1/leave
/api/v1/payroll
```

Use the existing API architecture.

Use the existing response format.

---

## EVENTS

Implement relevant events including:

```text
EmployeeCreated
EmployeeTransferred
EmployeePromoted
EmployeeTerminated

AttendanceReceived
AttendanceProcessed
AttendanceRegularized

LeaveRequested
LeaveApproved
LeaveRejected

RosterPublished

OvertimeApproved

SalaryRevised

PayrollCalculated
PayrollApproved
PayrollLocked
PayrollReopened

PayslipGenerated
```

Use existing event conventions.

---

## QUEUES / SCHEDULER

Implement jobs for:

```text
Attendance Synchronization
Attendance Processing
Attendance Calculation
Attendance Exceptions
Leave Balance Processing
Contract Expiry
Credential Expiry
Roster Alerts
Payroll Processing
Payslip Generation
Payroll Reports
Loan Alerts
Training Expiry
```

Use queues for large processing workloads.

---

## SECURITY

Implement server-side authorization.

Do not rely on UI hiding.

Test:

```text
Hospital A → Hospital B
Department A → Department B
Manager → Unauthorized Employee
Employee → Other Employee
HR → Restricted Payroll
Payroll Officer → Unauthorized Hospital
API ID Manipulation
Export Scope Bypass
```

All must fail appropriately.

---

## AI

Prepare extension points for:

```text
Workforce Forecasting
Staffing Optimization
Attendance Anomaly Detection
Leave Forecasting
Payroll Anomaly Detection
Payroll Variance Explanation
Credential Expiry Prediction
Overtime Analysis
```

AI must remain advisory.

AI MUST NOT:

```text
Terminate employees
Approve payroll
Reject leave
Change salary
Modify attendance
Create deductions
Approve overtime
Change employment status
Generate unauthorized payments
```

All AI recommendations must respect RBAC and organizational scope.

---

## TESTING

Create:

```text
Unit Tests
Feature Tests
Integration Tests
Security Tests
Negative Tests
Concurrency Tests
Payroll Calculation Tests
Attendance Tests
Leave Tests
API Tests
```

Critical tests:

```text
Two users cannot process the same payroll simultaneously.

Two users cannot finalize the same payroll simultaneously.

A locked payroll cannot be silently modified.

A user cannot access another hospital's employees.

A manager cannot access unauthorized departments.

Duplicate attendance punches are rejected/idempotent.

Leave balances remain transactionally correct.

Overlapping roster assignments are rejected.

Unauthorized salary changes are rejected.

Unauthorized payroll reopening is rejected.

Sensitive employee documents cannot be accessed by unauthorized users.
```

---

## DATABASE

Before creating migrations:

1. Inspect existing tables.
2. Reuse equivalent structures.
3. Follow existing naming conventions.
4. Avoid duplicate tables.
5. Add indexes based on real query patterns.
6. Add foreign keys.
7. Add unique constraints.
8. Use decimal types for money.
9. Use effective dates.
10. Preserve historical records.

---

## PERFORMANCE

Design for:

```text
10,000+ employees
Millions of attendance records
Large payroll runs
Multiple hospitals
Large HR document collections
```

Use:

```text
Indexes
Pagination
Chunking
Queues
Batch Processing
Caching
Aggregations where justified
```

Never load millions of attendance records into memory.

---

## UI

Build the complete menu:

```text
HR & Workforce
├── Dashboard
├── Employees
├── Organization
├── Recruitment
├── Onboarding
├── Offboarding
├── Workforce
├── Attendance
├── Leave
├── Overtime
├── Compensation
├── Payroll
├── Loans & Advances
├── Performance
├── Training
├── Reports
└── Settings
```

Follow the existing Blade/AdminLTE conventions.

---

## DOCUMENTATION

Create/update:

```text
Phase 16 architecture documentation
Database documentation
API documentation
Permission matrix
Workflow documentation
Payroll calculation documentation
Attendance integration documentation
Deployment instructions
Configuration documentation
Testing documentation
```

---

## FINAL VERIFICATION

Before reporting completion:

1. Run the relevant tests.
2. Run migrations in a test environment.
3. Check route registration.
4. Check authorization.
5. Check API validation.
6. Check database constraints.
7. Check queue jobs.
8. Check scheduled jobs.
9. Check payroll calculations.
10. Check attendance processing.
11. Check multi-hospital isolation.
12. Check concurrency-sensitive operations.
13. Check integration boundaries.
14. Check for duplicate architecture/components.

**Never claim tests passed unless they were actually executed.**

---

## FINAL RESPONSE FROM THE CODING AGENT

Return:

```text
1. Implementation Summary
2. Architecture Reused
3. New Components
4. Database/Migrations
5. HR Workflows
6. Attendance Integration
7. Payroll Engine
8. API Endpoints
9. Permissions/Roles
10. Events/Jobs/Notifications
11. Integrations
12. AI Extension Points
13. Tests Executed
14. Test Results
15. Security Verification
16. Performance Considerations
17. Documentation Added
18. Known Limitations
19. Recommended Next Steps
```

Do not claim completion of anything that was not actually implemented and tested.

---

# END OF PHASE 16