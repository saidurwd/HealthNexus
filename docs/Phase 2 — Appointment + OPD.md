Phase 2 — Appointment & Scheduling
1. Purpose
Phase 2 provides a centralized scheduling engine for the hospital.
It must support:
- outpatient appointments
- doctor schedules
- department schedules
- clinic sessions
- recurring schedules
- appointment types
- slot generation
- appointment booking
- rescheduling
- cancellation
- no-show management
- walk-ins
- waiting queues
- token management
- follow-ups
- reminders
- provider availability
- leave/blocking
- overbooking rules
- referral appointments
- appointment history
- calendar views
- API integration
The architecture should support future:
- OPD
- Emergency
- IPD
- Telemedicine
- Diagnostic appointments
- Surgery scheduling
- Health packages
- Patient portal
2. Core Principle
The appointment module must not create another patient identity.
Every appointment must reference the Phase 1 patient:
Organization
    │
    └── Hospital
          │
          └── Branch
                │
                ├── Department
                │
                └── Patient
                       │
                       └── Appointment
                              │
                              ├── Provider
                              ├── Schedule
                              ├── Slot
                              ├── Queue
                              └── Future Encounter
The appointment is a scheduling/business transaction.
The future clinical encounter will be a separate Phase 3 entity.
3. Scope
Included
1. Appointment Dashboard
2. Appointment Types
3. Provider/Doctor Scheduling
4. Department Scheduling
5. Clinic Sessions
6. Schedule Templates
7. Recurring Schedules
8. Slot Generation
9. Appointment Booking
10. Appointment Rescheduling
11. Appointment Cancellation
12. Appointment Confirmation
13. Appointment Check-in
14. Walk-in Registration
15. Waiting Queue
16. Token Management
17. No-show Management
18. Follow-up Appointments
19. Referral Appointments
20. Appointment Reminders
21. Provider Availability
22. Provider Leave/Unavailable Time
23. Schedule Blocking
24. Holiday Management
25. Overbooking Rules
26. Double-booking Prevention
27. Appointment Status Management
28. Calendar Views
29. Patient Appointment History
30. Provider Appointment History
31. Department Appointment History
32. Appointment Notes
33. Referral Information
34. Appointment Documents
35. Appointment Audit
36. Appointment Reports
37. API
38. Notifications
39. Patient Portal readiness
40. Telemedicine readiness
4. Explicitly Out of Scope
Do not implement the actual clinical workflow yet:
- OPD consultation
- diagnosis
- clinical notes
- prescription
- clinical procedures
- vital signs
- nursing
- laboratory orders
- radiology orders
- pharmacy dispensing
- billing
- insurance claims
- inpatient admission
- bed assignment
However, the appointment must be capable of creating a future clinical encounter.
5. Appointment Lifecycle
Recommended lifecycle:
                    ┌──────────────┐
                    │   Requested  │
                    └──────┬───────┘
                           ↓
                    ┌──────────────┐
                    │   Scheduled  │
                    └──────┬───────┘
                           ↓
                    ┌──────────────┐
                    │  Confirmed   │
                    └──────┬───────┘
                           ↓
                    ┌──────────────┐
                    │  Checked-In  │
                    └──────┬───────┘
                           ↓
                    ┌──────────────┐
                    │   Queued     │
                    └──────┬───────┘
                           ↓
                    ┌──────────────┐
                    │ Encounter    │
                    │   Ready      │
                    └──────────────┘
Alternative exits:
Scheduled
   ├── Cancelled
   ├── No Show
   ├── Rescheduled
   └── Expired
The actual Encounter belongs to Phase 3.
6. Appointment Types
Support configurable appointment types.
Examples:
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
Appointment types should be configurable rather than hardcoded.
7. Provider Model
The appointment engine should support providers beyond doctors.
Examples:
- Consultant
- Specialist
- General Physician
- Dentist
- Physiotherapist
- Psychologist
- Dietitian
- Nurse
- Technician
- Other healthcare provider
Use a generic:
Provider
concept.
Future integration:
Provider
   ↓
Employee / User
   ↓
Professional Profile
Do not assume every provider is a normal system user.
8. Provider Profile
If Phase 0/HR does not already provide this, create a minimal provider reference.
Example:
providers
Fields:
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
Do not build a complete HR employee module here.
9. Specialty
Support configurable specialties.
Examples:
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
Use master data or a dedicated specialty table depending on the Foundation architecture.
10. Department Schedule
A department may have multiple clinics.
Example:
Cardiology
├── Morning Clinic
├── Evening Clinic
└── Follow-up Clinic
Each clinic can have its own:
- provider
- room
- schedule
- appointment duration
- capacity
- token range
11. Clinic Session
Introduce the concept of:
Clinic Session
Example:
Cardiology
Dr. Ahmed
Monday
09:00–13:00
Room 301
20 slots
A session represents a provider's availability for a particular service/clinic on a particular date or recurring schedule.
12. Schedule Template
Create recurring schedule templates.
Example:
Doctor: Dr. Ahmed
Department: Cardiology

Monday:
09:00–13:00

Wednesday:
09:00–13:00

Saturday:
15:00–19:00
Possible table:
schedule_templates
and:
schedule_template_slots
13. Schedule Generation
The system should generate actual daily schedules from templates.
Example:
Template
   ↓
September 2026
   ↓
Generate Sessions
   ↓
September 22
September 23
September 24
...
Do not dynamically calculate everything at page-load time.
Persist generated sessions where appropriate.
This makes booking and concurrency management safer.
14. Slot Generation
Example:
Clinic:
09:00–13:00

Appointment Duration:
20 minutes

Slots:
09:00
09:20
09:40
10:00
10:20
...
12:40
Support:
- duration
- buffer
- capacity
- break
- lunch
- provider availability
- room availability
15. Slot Types
Support:
Regular
Priority
Follow-up
Emergency Reserved
VIP
Telemedicine
Walk-in
The exact categories should be configurable.
16. Appointment Capacity
A schedule may allow:
Maximum appointments = 20
or:
Maximum patients per slot = 2
The engine must support both.
Example:
09:00
Capacity: 2
Booked: 1
Available: 1

17. Overbooking
Overbooking must be explicitly controlled.
Example:
Normal capacity: 20
Allowed overbooking: 2
Maximum: 22

Rules should be configurable by:
- hospital
- branch
- department
- provider
- clinic
- appointment type
Overbooking requires appropriate permission.
18. Double-Booking Prevention
The system must prevent accidental duplicate bookings.
For example:
Provider: Dr. Ahmed
Date: 22 Sep
Time: 10:00
Two simultaneous requests must not both succeed if capacity is already full.
Use:
- database transaction
- row locking / appropriate concurrency strategy
- database constraints where possible
Do not rely only on:
if (!$existingAppointment) {
    create();
}
because concurrent requests can bypass that check.
19. Appointment Entity
Recommended:
appointments
Fields:
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
Do not use completed_at to indicate that the clinical consultation itself was completed unless Phase 3 explicitly defines that relationship.
20. Appointment Number
Generate a unique appointment number.
Example:
APT-2026-00001234
It must be:
- unique
- immutable
- searchable
- concurrency-safe
21. Appointment Status
Recommended:
Requested
Scheduled
Confirmed
Checked-In
Queued
In Consultation
Completed
Cancelled
No Show
Rescheduled
Expired
Rejected
However, avoid allowing arbitrary status changes.
Use a state transition service.
22. State Transition Rules
Example:
Scheduled
   ↓
Confirmed
   ↓
Checked-In
   ↓
Queued
   ↓
In Consultation
   ↓
Completed
Cancellation:
Scheduled → Cancelled
Confirmed → Cancelled
No-show:
Confirmed → No Show
Reschedule:
Scheduled → Rescheduled
Do not permit:
Completed → Scheduled
without a controlled correction/reversal mechanism.
23. Booking Sources
Support:
Reception
Call Center
Patient Portal
Doctor
Mobile App
Website
API
Referral
Corporate
Walk-in
Other
Store the source for analytics.
24. Booking Workflow
Recommended:
Search Patient
      ↓
Select Department
      ↓
Select Specialty
      ↓
Select Provider
      ↓
Select Date
      ↓
Select Available Slot
      ↓
Appointment Type
      ↓
Reason / Referral
      ↓
Confirm
      ↓
Appointment Created
      ↓
Notification
25. Appointment Booking from Patient Profile
The Patient 360 profile should provide:
[Book Appointment]
This should open the appointment booking interface with:
Patient = preselected
The user must still be able to change the patient only with explicit action.
26. Walk-In
Walk-in patients should be supported.
Workflow:
Patient Search/Register
        ↓
Walk-In
        ↓
Select Clinic
        ↓
Check Availability
        ↓
Create Appointment
        ↓
Generate Token
        ↓
Queue
A walk-in should still create an appointment record.
Do not create a separate disconnected walk-in entity unless justified.
27. Token Management
Support token numbers.
Example:
C-001
C-002
C-003
Token sequence can reset by:
- clinic
- department
- provider
- date
- shift
Configuration should determine behavior.
Example:
Morning Cardiology:
C001–C030

Evening Cardiology:
E001–E020
28. Queue Management
Create:
appointment_queue
or an equivalent queue representation.
Queue states:
Waiting
Called
Serving
Skipped
Completed
Cancelled
The queue must preserve ordering.
Possible priority:
Emergency
Priority
VIP
Regular
Follow-up
But priority should be configurable and permission-controlled.
29. Queue Calling
Provide actions:
Call Next
Recall
Skip
Start
Complete
Transfer
Cancel
Future Phase 3 will connect:
Queue → Clinical Encounter
30. Provider Availability
Availability must consider:
Regular Schedule
Leave
Holiday
Blocked Time
Training
Meeting
Emergency Closure
Room Availability
Other Appointments
A provider's schedule should not simply be interpreted as:
Monday = available
Availability is a calculated result.
31. Schedule Blocking
Support:
Block Schedule
Examples:
Doctor meeting
Training
Conference
Leave
Maintenance
Hospital event
Emergency
A blocked period must prevent new bookings unless explicitly overridden.
32. Leave
The appointment module should consume HR leave information when available.
If HR is not yet implemented:
Provider Unavailability
can act as the temporary mechanism.
Future integration:
HR Leave
    ↓
Scheduling Engine
    ↓
Provider Unavailable
33. Holidays
Support hospital/branch holiday calendars.
Example:
Hospital Holiday
Branch Holiday
Department Holiday
Do not assume national holidays are automatically hospital closures.
34. Rooms
Appointments may optionally require:
Room
Consultation Room
Procedure Room
Clinic Room
Telemedicine Room
If room management is not yet a full module, implement a lightweight scheduling reference.
Future Assets/Facilities can own the complete room model.
35. Rescheduling
Rescheduling must preserve history.
Do not simply overwrite:
appointment_date
start_time
Create an appointment history/event.
Example:
22 Sep 10:00
       ↓
Rescheduled
       ↓
24 Sep 11:00
Record:
old date
old time
new date
new time
reason
user
timestamp
36. Cancellation
Cancellation requires:
Cancellation Reason
Cancelled By
Cancelled At
Reasons should be configurable:
Patient Request
Provider Unavailable
Hospital Closure
Duplicate Booking
Wrong Department
Other
Do not delete the appointment.
37. No-Show
Support:
No Show
Track:
marked_at
marked_by
reason
No-show analytics should be available later.
38. Follow-Up
Support follow-up appointments.
Appointment should reference:
previous_appointment_id
Example:
Initial Consultation
       ↓
Follow-up Appointment
This is not yet a clinical follow-up decision; it is simply appointment linkage.
39. Referral
Support:
referral_source
referral_provider
referral_organization
referral_reference
referral_document
Future modules can expand this into a full referral-management system.
40. Appointment Reminders
Support configurable reminders.
Examples:
24 hours before
2 hours before
30 minutes before
Channels:
SMS
Email
WhatsApp
Push
Use Phase 0 notification infrastructure.
Reminder jobs must be asynchronous.
41. Reminder Rules
Configure:
Hospital
Branch
Department
Provider
Appointment Type
Reminder Channel
Reminder Time
Do not hardcode:
24 hours
into the application.
42. Calendar
Provide:
Day View
09:00 ─ Patient A
09:20 ─ Patient B
09:40 ─ Available
10:00 ─ Patient C
Week View
Provider schedule across the week.
Month View
High-level appointment distribution.
Provider View
One provider.
Department View
All providers in a department.
Room View
Room utilization.
43. Appointment Dashboard
Widgets:
Today's Appointments
Confirmed
Checked-In
Waiting
In Consultation
Completed
Cancelled
No Show
Available Slots
Charts:
Appointments by Department
Appointments by Provider
Appointments by Type
No-Show Rate
Cancellation Rate
Daily Trend
All widgets must respect user scope.
44. Appointment Search
Search by:
Appointment Number
MRN
Patient Name
Phone
Provider
Department
Date
Status
Appointment Type
Source
Filters:
Hospital
Branch
Department
Provider
Date Range
Status
Type
45. Patient Appointment History
Patient profile should show:
Date
Appointment No.
Department
Provider
Type
Status
Source
Future clinical data must be linked through encounters, not duplicated into appointments.
46. Appointment Notes
Allow limited administrative notes.
Examples:
Patient requested afternoon slot
Referral document pending
Call patient before appointment
Do not use this field for clinical notes.
47. Appointment Documents
Allow administrative appointment documents:
- referral letter
- booking confirmation
- external referral
- supporting documents
Reuse Phase 1/Phase 0 file management.
48. Appointment Audit
Audit:
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
Appointment Note Changed
Reminder Sent
Appointment Exported
49. Reports
Implement:
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
50. Important Metrics
Prepare for:
Booking Lead Time
Slot Utilization
Provider Utilization
Average Waiting Time
No-Show Rate
Cancellation Rate
Reschedule Rate
Walk-In Rate
Appointment Completion Rate
Do not calculate clinical KPIs in this phase.
51. Database Design
Recommended tables:
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
Depending on the implementation, some of these may be consolidated.
Avoid over-normalization if it makes the scheduling engine unnecessarily complex.
52. Important Database Constraints
Appointment number:
UNIQUE(organization_id, appointment_number)
Provider schedule:
Appropriate indexes on:
provider_id
appointment_date
start_time
status
Patient:
patient_id
appointment_date
Queue:
clinic/session/date/token
The final uniqueness constraints must reflect whether capacity is 1 or greater.
Do not enforce a universal unique constraint on:
provider_id + date + time
if the system supports multiple patients per slot.
53. Concurrency
This is a critical requirement.
Two receptionists may attempt to book:
Dr. Ahmed
10:00
at exactly the same time.
The system must guarantee that capacity is not exceeded.
Use:
Database Transaction
+
Row Locking / Atomic Allocation
+
Appropriate Constraints
Test this explicitly.
54. API
Base:
/api/v1/appointments
Examples:
GET    /api/v1/appointments
POST   /api/v1/appointments
GET    /api/v1/appointments/{appointment}
PUT    /api/v1/appointments/{appointment}

POST   /api/v1/appointments/{appointment}/confirm
POST   /api/v1/appointments/{appointment}/check-in
POST   /api/v1/appointments/{appointment}/cancel
POST   /api/v1/appointments/{appointment}/reschedule
POST   /api/v1/appointments/{appointment}/no-show

GET    /api/v1/appointments/availability
GET    /api/v1/appointments/calendar

POST   /api/v1/appointments/{appointment}/queue
POST   /api/v1/appointments/{appointment}/token
Provider:
GET /api/v1/providers
GET /api/v1/providers/{provider}/schedule
GET /api/v1/providers/{provider}/availability
55. Suggested Menu
Appointments
│
├── Dashboard
├── Calendar
├── New Appointment
├── Today's Appointments
├── Appointment List
├── Waiting Queue
├── Tokens
├── Provider Schedule
├── Department Schedule
├── Availability
├── Schedule Templates
├── Blocked Schedules
├── Holidays
├── Follow-ups
├── Referrals
├── Cancelled
├── No Shows
├── Appointment Reports
└── Settings
Administration:
Appointment Settings
Appointment Types
Reminder Rules
Queue Settings
Token Settings
Overbooking Rules
56. Recommended Workflow
The complete operational flow becomes:
Phase 1 Patient
       ↓
Appointment Booking
       ↓
Schedule/Slot
       ↓
Confirmation
       ↓
Reminder
       ↓
Patient Arrives
       ↓
Check-In
       ↓
Queue
       ↓
Token Called
       ↓
Phase 3 Clinical Encounter
This is the key integration point between Phase 2 and Phase 3.