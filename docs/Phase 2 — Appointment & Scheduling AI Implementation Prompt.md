# Phase 2 — Appointment & Scheduling Implementation Prompt

## Role

Act as a senior:

- Laravel Architect
- Healthcare Software Architect
- Hospital ERP Developer
- Scheduling Engine Architect
- Database Architect
- API Architect
- Security Engineer
- QA/Test Engineer

You are implementing:

# Phase 2 — Appointment & Scheduling

for an enterprise-grade Hospital Management System.

The implementation must build directly on:

```text
Phase 0 — Foundation
Phase 1 — Patient Core
```

Do not duplicate functionality already provided by those phases.

---

# 1. Existing Architecture

The existing system uses:

- PHP 8.4+
- Laravel
- MySQL/PostgreSQL
- Redis
- Laravel Queue
- Laravel Scheduler
- Blade
- AdminLTE
- REST API
- Modular Monolith
- RBAC
- Multi-organization
- Multi-hospital
- Multi-branch
- Department access scope
- Audit logging
- Activity logging
- Security events
- File management
- Notifications
- Workflow
- Master data
- Localization

Before coding:

1. Inspect the existing project.
2. Understand Phase 0.
3. Understand Phase 1.
4. Reuse existing patient model.
5. Reuse existing authorization.
6. Reuse existing access-scope mechanism.
7. Reuse existing audit system.
8. Reuse existing notifications.
9. Reuse existing file management.
10. Reuse existing master-data system.
11. Reuse existing workflow system.
12. Reuse existing API response structure.

Do not overwrite unrelated code.

---

# 2. Primary Objective

Build a production-ready appointment and scheduling engine that supports:

```text
Patient
   ↓
Appointment
   ↓
Provider Schedule
   ↓
Slot
   ↓
Confirmation
   ↓
Reminder
   ↓
Check-In
   ↓
Queue
   ↓
Future Clinical Encounter
```

The clinical encounter itself belongs to Phase 3.

---

# 3. Scope

Implement:

1. Appointment Dashboard
2. Appointment Types
3. Providers
4. Specialties
5. Departments
6. Clinic Sessions
7. Schedule Templates
8. Recurring Schedules
9. Schedule Generation
10. Slot Generation
11. Provider Availability
12. Provider Unavailability
13. Hospital Holidays
14. Schedule Blocking
15. Room Scheduling Reference
16. Appointment Booking
17. Appointment Confirmation
18. Appointment Rescheduling
19. Appointment Cancellation
20. No-Show
21. Walk-In
22. Follow-Up
23. Referral
24. Check-In
25. Waiting Queue
26. Token Management
27. Appointment Reminders
28. Appointment Notes
29. Appointment Documents
30. Appointment History
31. Appointment Calendar
32. Patient Appointment History
33. Provider Schedule
34. Department Schedule
35. Overbooking
36. Double-booking prevention
37. Reports
38. API
39. Audit
40. Security
41. Automated tests
42. Patient Portal readiness
43. Telemedicine readiness

---

# 4. Do NOT Implement

Do not implement:

- clinical encounters
- OPD consultation
- diagnosis
- prescription
- clinical notes
- nursing
- laboratory
- radiology
- pharmacy
- billing
- insurance
- inpatient admission
- bed management
- surgery
- ICU

Only create clean integration points for Phase 3 and later modules.

---

# 5. Critical Domain Rule

Never create a new patient record during appointment booking.

The appointment MUST reference the Phase 1 Patient entity:

```php
$appointment->patient_id
```

Patient registration belongs to Phase 1.

If the patient does not exist:

```text
Search
↓
Register Patient
↓
Return Patient
↓
Book Appointment
```

---

# 6. Appointment Model

Implement:

```text
appointments
```

with approximately:

```text
id
organization_id
hospital_id
branch_id

appointment_number

patient_id

provider_id
department_id
specialty_id

schedule_id
slot_id

appointment_type_id

appointment_date
start_time
end_time

status
priority

source

reason

referral_source
referred_by

notes

is_walk_in
is_follow_up
is_telemedicine

previous_appointment_id

booked_at
booked_by

confirmed_at
confirmed_by

checked_in_at
checked_in_by

cancelled_at
cancelled_by
cancellation_reason

no_show_at
completed_at

created_at
updated_at
```

Adjust according to the existing project's conventions.

---

# 7. Appointment Number

Implement a safe appointment-number generator.

Example:

```text
APT-2026-00001234
```

Requirements:

- unique
- immutable
- concurrency-safe
- transaction-safe
- searchable

Never use:

```php
Appointment::count()
```

to generate appointment numbers.

---

# 8. Appointment Types

Implement configurable appointment types:

```text
New Consultation
Follow-up
Review
Second Opinion
Procedure
Health Checkup
Referral
Telemedicine
Vaccination
Diagnostic
Pre-operative
Post-operative
Corporate
Package
Other
```

Do not hardcode these values into business logic.

---

# 9. Provider

Implement a provider abstraction.

Provider may be:

- doctor
- consultant
- specialist
- nurse
- physiotherapist
- dietitian
- psychologist
- technician
- other healthcare professional

A provider may optionally be linked to:

```text
user_id
employee_id
```

Do not build a complete HR system.

---

# 10. Provider Profile

If the Foundation/HR layer does not already contain this:

Create:

```text
providers
```

with:

```text
id
organization_id
hospital_id
branch_id
user_id nullable
employee_id nullable
provider_code
name
provider_type
department_id
specialty_id
license_number
status
created_at
updated_at
```

Keep this model minimal and scheduling-focused.

---

# 11. Specialty

Implement a configurable specialty model or reuse the existing master-data architecture.

Examples:

```text
Medicine
Cardiology
Neurology
Orthopedics
Pediatrics
Gynecology
Dermatology
ENT
Ophthalmology
Surgery
Dentistry
Psychiatry
Radiology
Pathology
Physiotherapy
```

---

# 12. Clinic Session

Implement clinic sessions.

Example:

```text
Provider:
Dr. Ahmed

Department:
Cardiology

Day:
Monday

Time:
09:00–13:00

Room:
301

Capacity:
20
```

A session must be capable of generating appointment slots.

---

# 13. Schedule Templates

Implement recurring schedule templates.

Example:

```text
Monday     09:00–13:00
Wednesday  09:00–13:00
Saturday   15:00–19:00
```

Support:

- effective_from
- effective_to
- days of week
- start time
- end time
- slot duration
- break duration
- capacity
- provider
- department
- specialty
- room
- appointment type restrictions

---

# 14. Schedule Generation

Create a reliable schedule-generation service.

Example:

```text
Schedule Template
      ↓
Generate Date Sessions
      ↓
Generate Slots
```

Support:

- future dates
- regeneration
- cancellation
- blocked dates
- holidays
- provider leave

Do not generate duplicate sessions.

---

# 15. Slots

Implement slots.

Example:

```text
09:00
09:20
09:40
10:00
10:20
```

Support:

```text
slot duration
buffer
capacity
breaks
appointment type
provider
room
```

Slots should have availability state calculated safely.

---

# 16. Capacity

Support:

```text
1 patient / slot
```

and:

```text
multiple patients / slot
```

Example:

```text
10:00
Capacity = 2
Booked = 1
Remaining = 1
```

Do not assume every appointment slot has capacity 1.

---

# 17. Concurrency

This is mandatory.

Two users may attempt to book the same final available slot simultaneously.

Implement safe allocation using:

- transactions
- appropriate row locking
- atomic counters or allocation records
- database constraints where appropriate

Do not rely only on application-level existence checks.

Write an automated concurrency test.

---

# 18. Overbooking

Implement configurable overbooking.

Example:

```text
Normal Capacity = 20
Overbooking = 2
Maximum = 22
```

Rules can depend on:

```text
Hospital
Branch
Department
Provider
Clinic
Appointment Type
```

Overbooking must require appropriate permission.

---

# 19. Provider Availability

Availability must consider:

```text
Schedule
Appointments
Leave
Unavailability
Blocked Time
Holiday
Room availability
```

Create:

```text
ProviderAvailabilityService
```

The service must return actual bookable slots.

---

# 20. Provider Unavailability

Implement:

```text
provider_unavailability
```

Examples:

```text
Leave
Training
Meeting
Conference
Personal
Emergency
Other
```

A blocked period must prevent booking unless an authorized override is used.

---

# 21. Hospital Holidays

Implement configurable hospital/branch holiday calendars.

Do not assume every national holiday is automatically a hospital closure.

---

# 22. Schedule Blocking

Implement:

```text
Block Schedule
```

Examples:

```text
Doctor Meeting
Training
Hospital Event
Room Maintenance
Emergency Closure
Other
```

Blocking must affect slot availability.

---

# 23. Appointment Booking Service

Create:

```text
AppointmentBookingService
```

It should:

1. Validate patient.
2. Validate provider.
3. Validate schedule.
4. Validate slot.
5. Validate appointment type.
6. Validate provider availability.
7. Validate holiday/blocking.
8. Validate capacity.
9. Apply overbooking rules.
10. Lock the relevant scheduling resource.
11. Create appointment.
12. Generate appointment number.
13. Create audit event.
14. Dispatch notification job.
15. Commit transaction.

Do not send external notifications before the transaction is safely committed.

---

# 24. Appointment Confirmation

Implement:

```text
AppointmentConfirmationService
```

Support:

```text
Scheduled
→ Confirmed
```

Record:

```text
confirmed_at
confirmed_by
```

---

# 25. Rescheduling

Never silently overwrite the original appointment history.

Implement:

```text
AppointmentRescheduleService
```

Record:

```text
old date
old time
old provider
old schedule
new date
new time
new provider
new schedule
reason
user
timestamp
```

Preserve a complete history.

---

# 26. Cancellation

Implement:

```text
AppointmentCancellationService
```

Require:

```text
reason
cancelled_by
cancelled_at
```

Do not delete the appointment.

---

# 27. No-Show

Implement:

```text
AppointmentNoShowService
```

Record:

```text
marked_at
marked_by
reason
```

Do not delete the appointment.

---

# 28. Walk-In

Walk-in workflow:

```text
Patient
↓
Select Clinic
↓
Check Availability
↓
Create Appointment
↓
Mark Walk-In
↓
Generate Token
↓
Queue
```

A walk-in must still create an appointment record.

---

# 29. Follow-Up

Support:

```text
previous_appointment_id
is_follow_up
```

A follow-up must reference the previous appointment.

Do not duplicate patient identity.

---

# 30. Referral

Support:

```text
referral_source
referred_by
referral_organization
referral_reference
referral_document
```

Keep the architecture ready for a future full referral-management module.

---

# 31. Check-In

Implement:

```text
AppointmentCheckInService
```

Workflow:

```text
Confirmed
   ↓
Checked-In
   ↓
Queue
```

Check-in should record:

```text
checked_in_at
checked_in_by
```

---

# 32. Queue

Implement queue management.

Queue states:

```text
Waiting
Called
Serving
Skipped
Completed
Cancelled
```

Implement:

```text
QueueService
```

with operations:

```text
Add
Call Next
Recall
Skip
Start
Complete
Transfer
Cancel
```

---

# 33. Token

Implement configurable token generation.

Example:

```text
C001
C002
C003
```

Support reset rules by:

```text
date
clinic
department
provider
shift
```

Token generation must be concurrency-safe.

---

# 34. Queue Priority

Support configurable priorities.

Example:

```text
Emergency
Priority
VIP
Regular
Follow-up
```

Do not hardcode clinical priority rules.

---

# 35. Patient Appointment History

Add appointment history to the Phase 1 Patient Profile.

Display:

```text
Appointment No.
Date
Department
Provider
Type
Status
Source
```

Do not duplicate appointment data into the patient table.

---

# 36. Calendar

Implement:

### Day

Provider schedule and appointments.

### Week

Weekly schedule.

### Month

Appointment overview.

### Provider

Provider-specific schedule.

### Department

Department-wide schedule.

### Room

Room utilization.

---

# 37. Reminders

Use Phase 0 notification and queue infrastructure.

Support:

```text
SMS
Email
WhatsApp
Push
```

Do not implement provider-specific integrations inside the scheduling domain.

Create notification jobs/events.

---

# 38. Reminder Configuration

Support configurable rules:

```text
Hospital
Branch
Department
Provider
Appointment Type
Channel
Offset
```

Examples:

```text
24 hours before
2 hours before
30 minutes before
```

Do not hardcode reminder timing.

---

# 39. Documents

Reuse Phase 1/Phase 0 file management.

Appointment documents may include:

```text
Referral Letter
Booking Document
External Medical Record
Supporting Document
```

All access must be authorized.

---

# 40. Appointment Notes

Allow only administrative notes.

Examples:

```text
Patient requested afternoon slot.
Referral document pending.
Call patient before appointment.
```

Do not store clinical notes here.

---

# 41. Appointment Status Machine

Create a state transition mechanism.

Allowed examples:

```text
Requested → Scheduled
Scheduled → Confirmed
Confirmed → Checked-In
Checked-In → Queued
Queued → In Consultation
In Consultation → Completed

Scheduled → Cancelled
Confirmed → Cancelled

Scheduled → No Show
Confirmed → No Show

Scheduled → Rescheduled
Confirmed → Rescheduled
```

Do not permit arbitrary status manipulation.

---

# 42. Authorization

Implement permissions:

```text
appointment.view
appointment.create
appointment.update
appointment.confirm
appointment.checkin
appointment.cancel
appointment.reschedule
appointment.no_show
appointment.queue
appointment.token
appointment.override
appointment.export
appointment.print
appointment.manage_schedule
appointment.manage_provider
appointment.manage_holiday
appointment.manage_block
appointment.manage_overbooking
```

Use existing Phase 0 RBAC and policies.

---

# 43. Access Scope

Every appointment query must respect:

```text
Organization
Hospital
Branch
Department
```

A user must never access appointments outside their authorization scope.

Do not depend on UI menu visibility.

---

# 44. API

Implement:

```http
GET    /api/v1/appointments
POST   /api/v1/appointments
GET    /api/v1/appointments/{appointment}
PUT    /api/v1/appointments/{appointment}

GET    /api/v1/appointments/search
GET    /api/v1/appointments/availability
GET    /api/v1/appointments/calendar

POST   /api/v1/appointments/{appointment}/confirm
POST   /api/v1/appointments/{appointment}/check-in
POST   /api/v1/appointments/{appointment}/cancel
POST   /api/v1/appointments/{appointment}/reschedule
POST   /api/v1/appointments/{appointment}/no-show
POST   /api/v1/appointments/{appointment}/queue

GET    /api/v1/providers
GET    /api/v1/providers/{provider}/schedule
GET    /api/v1/providers/{provider}/availability
```

Use the existing Phase 0 API response format.

---

# 45. Suggested Database Tables

Implement or adapt:

```text
appointment_types
providers
specialties
clinic_sessions
schedule_templates
schedule_template_days
schedule_slots
provider_unavailability
hospital_holidays
appointment_rooms
appointments
appointment_status_history
appointment_reschedules
appointment_cancellations
appointment_queue
appointment_queue_events
appointment_reminders
appointment_notes
appointment_documents
appointment_referrals
```

Do not create duplicate tables if equivalent functionality already exists.

---

# 46. Database Indexes

Ensure efficient indexes for:

```text
appointments.patient_id
appointments.provider_id
appointments.department_id
appointments.appointment_date
appointments.status
appointments.appointment_number
```

Composite indexes should be designed around actual queries.

Examples:

```text
provider_id + appointment_date + status
department_id + appointment_date + status
patient_id + appointment_date
```

Do not blindly index every field.

---

# 47. Testing

Implement:

## Unit Tests

Test:

- appointment-number generation
- slot generation
- schedule generation
- availability
- overbooking
- token generation
- status transitions
- reminder calculation

## Feature Tests

Test:

- appointment booking
- confirmation
- check-in
- cancellation
- rescheduling
- no-show
- walk-in
- follow-up
- referral
- queue
- calendar
- provider schedule

## Security Tests

Test:

- unauthorized appointment access
- cross-hospital access
- cross-branch access
- unauthorized cancellation
- unauthorized rescheduling
- unauthorized schedule changes
- unauthorized overbooking
- unauthorized queue manipulation

## Concurrency Tests

Mandatory:

```text
Two users attempt to book the final available slot simultaneously.
```

Expected:

```text
Only valid capacity is consumed.
```

---

# 48. Integration Tests

Verify:

```text
Phase 1 Patient
        ↓
Appointment
```

Test:

- valid patient can book
- invalid patient cannot book
- inactive patient handling follows defined business rule
- patient access scope is enforced
- patient appointment history appears correctly

Prepare the interface:

```text
Appointment
        ↓
Future Encounter
```

but do not implement Phase 3 clinical encounters.

---

# 49. Security

Implement:

- authorization policies
- access scopes
- CSRF protection
- rate limiting where appropriate
- secure API authentication
- audit logging
- transaction protection
- concurrency protection
- safe error handling
- no sensitive data in ordinary logs

Do not expose SQL errors or stack traces in production.

---

# 50. UI

Use:

```text
Blade
AdminLTE
Reusable Components
```

Build reusable components:

```text
Appointment Search
Appointment Booking Form
Provider Selector
Department Selector
Specialty Selector
Date Selector
Availability Calendar
Slot Selector
Appointment Status Badge
Appointment Timeline
Queue Display
Token Display
Provider Calendar
Schedule Editor
Schedule Blocker
Reschedule Dialog
Cancellation Dialog
```

---

# 51. Appointment Booking UX

The booking screen should guide the user:

```text
1. Patient
2. Department
3. Specialty
4. Provider
5. Appointment Type
6. Date
7. Available Slot
8. Reason
9. Referral
10. Confirmation
```

The user should not be presented with unavailable slots.

Availability should be calculated server-side.

---

# 52. Dashboard

Create widgets:

```text
Today's Appointments
Confirmed
Checked-In
Waiting
In Consultation
Completed
Cancelled
No Show
Available Slots
```

Charts:

```text
Appointments by Department
Appointments by Provider
Appointment Types
No-Show Rate
Cancellation Rate
Daily Trend
```

Respect access scope.

---

# 53. Reports

Implement:

```text
Daily Appointment Report
Provider Schedule
Department Schedule
Appointment Utilization
Cancellation Report
No-Show Report
Rescheduling Report
Walk-In Report
Follow-Up Report
Appointment Source Report
Provider Workload
Slot Utilization
Waiting Time
Patient Appointment History
```

Exports must respect:

- permissions
- organization scope
- hospital scope
- branch scope
- department scope

---

# 54. Audit

Audit:

```text
Appointment Created
Appointment Updated
Appointment Confirmed
Appointment Checked-In
Appointment Queued
Appointment Called
Appointment Rescheduled
Appointment Cancelled
Appointment Marked No-Show
Appointment Completed
Appointment Exported
Appointment Printed
Schedule Created
Schedule Updated
Schedule Blocked
Provider Availability Changed
Overbooking Override
Token Generated
Queue Action
Reminder Sent
```

---

# 55. Events

Implement events where useful:

```text
AppointmentCreated
AppointmentConfirmed
AppointmentCheckedIn
AppointmentQueued
AppointmentRescheduled
AppointmentCancelled
AppointmentNoShow
AppointmentCompleted
AppointmentReminderDue
ScheduleChanged
```

Future modules must be able to subscribe without tightly coupling to Appointment controllers.

---

# 56. Queue Jobs

Use Redis/Laravel Queue for:

```text
SendAppointmentReminder
SendAppointmentConfirmation
SendCancellationNotification
SendRescheduleNotification
GenerateFutureSchedules
ProcessAppointmentNotification
```

Do not perform external notification delivery synchronously inside HTTP requests.

---

# 57. Localization

All UI labels must be translation-ready.

Support:

```text
English
Bangla
Arabic-ready
```

Date/time display must respect configured timezone.

Store timestamps consistently according to the Phase 0 architecture.

---

# 58. Timezone

Scheduling is highly timezone-sensitive.

Never compare appointment times using arbitrary server-local time.

Use the configured hospital/branch timezone.

Store timestamps consistently and convert for display.

Be particularly careful with:

- appointment date
- start time
- end time
- reminders
- daylight-saving rules for international deployments

---

# 59. Performance

Optimize for:

```text
Large appointment volumes
Many providers
Many branches
Many concurrent reception users
```

Avoid:

- N+1 queries
- loading entire calendars unnecessarily
- calculating every future slot on every request
- unindexed appointment searches
- synchronous notification delivery

Use caching where appropriate.

---

# 60. Documentation

Create/update:

```text
docs/PHASE-2-APPOINTMENT-SCHEDULING.md
docs/APPOINTMENT-LIFECYCLE.md
docs/SCHEDULING-ENGINE.md
docs/APPOINTMENT-API.md
docs/QUEUE-MANAGEMENT.md
```

If equivalent project documentation already exists, update it instead of creating duplicates.

---

# 61. Final Validation

Before declaring Phase 2 complete:

[ ] Appointment types work

[ ] Provider model works

[ ] Specialty works

[ ] Clinic sessions work

[ ] Schedule templates work

[ ] Recurring schedules work

[ ] Schedule generation works

[ ] Slot generation works

[ ] Provider availability works

[ ] Provider blocking works

[ ] Holiday handling works

[ ] Appointment booking works

[ ] MRN/PID integration works

[ ] Appointment number generation works

[ ] Double-booking prevention works

[ ] Capacity works

[ ] Overbooking works

[ ] Confirmation works

[ ] Check-in works

[ ] Walk-in works

[ ] Token generation works

[ ] Queue works

[ ] Rescheduling works

[ ] Cancellation works

[ ] No-show works

[ ] Follow-up works

[ ] Referral works

[ ] Appointment reminders work

[ ] Calendar works

[ ] Patient appointment history works

[ ] Provider schedule works

[ ] Department schedule works

[ ] Reports work

[ ] API works

[ ] RBAC works

[ ] Hospital scope works

[ ] Branch scope works

[ ] Audit works

[ ] Concurrency test passes

[ ] Security tests pass

[ ] No N+1 query issues exist

[ ] Migrations work on clean database

[ ] Documentation is updated

[ ] Deployment instructions are documented

Do not claim any test passed unless it was actually executed.

---

# 62. Phase Boundary

When Phase 2 is complete, the system must provide:

```text
Patient
   ↓
Appointment
   ↓
Schedule
   ↓
Provider
   ↓
Slot
   ↓
Confirmation
   ↓
Reminder
   ↓
Check-In
   ↓
Queue
   ↓
Token
   ↓
READY FOR CLINICAL ENCOUNTER
```

Do not implement the clinical encounter.

That is the responsibility of:

# Phase 3 — OPD / Clinical Encounter