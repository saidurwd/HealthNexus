
# Phase 17 — Patient Portal / Mobile / CRM
## Production-Grade Modular Architecture & AI Implementation Specification

---

# 1. Purpose

Phase 17 introduces the **patient-facing digital engagement and relationship-management layer** of the Hospital Management System.

It provides a unified experience through:

- Patient Web Portal
- Mobile Application APIs
- Patient Account
- Appointment access
- Clinical information access
- Prescription access
- Laboratory results
- Radiology reports
- Billing and payment information
- Insurance information
- Medication information
- Documents
- Notifications
- Secure messaging
- Service requests
- Feedback
- Complaints
- CRM
- Patient communication
- Patient engagement
- Health reminders
- Follow-up reminders
- Family/dependent management
- Consent management
- Telehealth integration foundation
- Digital forms
- Patient journey
- Patient 360 engagement view
- Analytics
- AI-assisted patient engagement

The module must be designed as a **digital experience layer**, not as a replacement for the hospital's clinical backend.

---

# 2. Core Architectural Principle

The existing Laravel modular architecture is already implemented.

The AI implementation agent MUST:

1. Inspect the repository.
2. Inspect Phases 0–16.
3. Identify existing architecture and conventions.
4. Reuse existing modules.
5. Extend the existing modular system.
6. Never create another modular framework.
7. Never duplicate existing business domains.

The portal/mobile layer should consume existing APIs/services where appropriate.

---

# 3. Fundamental Ownership Boundary

Phase 17 owns:

```text
Patient Digital Identity
Patient Portal
Mobile Experience
Patient Sessions
Patient Preferences
Patient Communication
Patient Notifications
Patient Engagement
Patient Service Requests
Patient Feedback
Patient CRM
Patient Campaigns
Patient Segmentation
Patient Journey
Patient Support
Patient Digital Documents Access
Portal Audit
```

Phase 17 does NOT own:

```text
Patient Master Record
Clinical Encounter
Diagnosis
Prescription
Laboratory Result
Radiology Report
Pharmacy Stock
Admission
Bed Management
Nursing Records
Surgery
ICU
Emergency
Blood Bank
Inventory
Procurement
Billing Ledger
Payroll
Insurance Adjudication
```

Those remain owned by earlier phases.

---

# 4. Source-of-Truth Principle

The patient portal must never create a competing clinical database.

Canonical architecture:

```text
Patient Portal
      ↓
Existing HMS Services / APIs
      ↓
Patient / Clinical / Financial Modules
```

For example:

```text
Portal
  ↓
Appointment Service
  ↓
Phase 2 Appointment
```

```text
Portal
  ↓
Prescription Service
  ↓
Phase 3 Clinical / Phase 7 Pharmacy
```

```text
Portal
  ↓
Lab Result Service
  ↓
Phase 5 LIS
```

```text
Portal
  ↓
Radiology Service
  ↓
Phase 6 RIS/PACS
```

```text
Portal
  ↓
Billing Service
  ↓
Phase 4 Billing
```

---

# 5. Patient Digital Identity

Phase 17 must provide a secure patient-facing identity layer.

A patient may have:

```text
Patient
↓
Portal Account
↓
Login Credentials
↓
Verified Contact
↓
Authorized Devices
↓
Sessions
```

The portal account should reference the existing Phase 1 patient.

Do not create a duplicate patient record.

---

# 6. Patient Account

Suggested conceptual fields:

```text
id
patient_id
organization_id
portal_status
email
mobile
email_verified_at
mobile_verified_at
last_login_at
preferred_language
preferred_timezone
status
created_at
updated_at
```

Do not duplicate demographic information already owned by Patient/MPI.

---

# 7. Portal Account Status

Support:

```text
Pending Verification
Active
Locked
Suspended
Deactivated
Deleted/Anonymized where legally permitted
```

Account state is separate from patient clinical status.

A patient may be clinically active while the portal account is locked.

---

# 8. Registration

Possible registration mechanisms:

- Mobile number
- Email
- Patient/MRN reference
- Invitation
- Hospital registration
- Staff-assisted activation
- QR/activation code where appropriate

Registration must require patient identity verification before linking an account to an existing patient.

Never allow:

```text
Patient A
→ Guess Patient B MRN
→ Create portal access to Patient B
```

---

# 9. Identity Verification

Support configurable verification methods:

```text
OTP
Email Verification
Mobile Verification
Patient Identifier
Date of Birth
Existing Hospital Verification
Staff-Assisted Verification
Identity Provider
```

Do not hardcode one national identity-verification system.

---

# 10. Multi-Factor Authentication

Support:

- Password
- OTP
- Authenticator application
- Passkey/WebAuthn where supported
- Device verification

MFA policy should be configurable.

High-risk actions should require step-up authentication.

Examples:

- Change mobile number
- Change email
- Add dependent
- Download sensitive records
- Change payment information
- Access highly sensitive documents

---

# 11. Device Management

Patients may use:

- Web browser
- Android
- iOS
- Tablet

Support:

```text
Device Registration
Device Name
Platform
Application Version
Last Active
Push Token
Trusted Status
Created At
Revoked At
```

Patients should be able to revoke sessions/devices.

---

# 12. Session Security

Implement:

- Session expiration
- Refresh-token rotation where applicable
- Device binding where appropriate
- Session revocation
- Suspicious login detection
- Rate limiting
- Brute-force protection
- Login audit
- Password reset
- OTP expiry

Never store passwords in plaintext.

---

# 13. Patient Portal Dashboard

The dashboard should provide:

```text
Welcome
Upcoming Appointment
Recent Visits
Outstanding Balance
Recent Prescriptions
Recent Lab Results
Recent Radiology Reports
Medication Reminders
Notifications
Pending Requests
Health Documents
Insurance Status
Messages
```

Only information authorized for portal display should appear.

---

# 14. Patient 360 Digital View

Portal-level Patient 360 should aggregate:

```text
Profile
Appointments
Encounters
Prescriptions
Laboratory
Radiology
Admissions
Procedures
Medications
Billing
Insurance
Documents
Messages
Requests
Feedback
```

The portal does not own these records.

It presents authorized views of the underlying systems.

---

# 15. Appointment Management

Integrate with Phase 2.

Patients should be able to:

- Search providers
- Search departments
- View availability
- Book appointment
- Reschedule
- Cancel
- View appointment details
- Receive reminders
- Check appointment status
- View appointment history
- Join supported digital/telehealth sessions

Do not implement a second appointment engine.

---

# 16. Appointment Booking Rules

The portal must respect:

- Provider schedule
- Hospital
- Branch
- Department
- Specialty
- Slot availability
- Booking restrictions
- Cancellation policy
- Patient eligibility
- Appointment type
- Insurance rules where applicable

Final availability must come from Phase 2.

---

# 17. Digital Queue / Check-In

Where supported:

```text
Appointment
↓
Digital Check-In
↓
Queue
↓
Waiting
↓
Provider
```

Possible capabilities:

- Confirm arrival
- QR check-in
- Digital queue token
- Waiting-time display
- Queue notification

Do not bypass hospital registration rules.

---

# 18. Clinical Record Access

Patients may access authorized information such as:

- Encounter summaries
- Diagnoses where released
- Clinical notes where permitted
- Prescriptions
- Instructions
- Follow-up
- Allergies
- Medical history
- Problem list
- Discharge summaries

The portal must support a configurable **clinical information release policy**.

Not every internal clinical record should automatically become patient-visible.

---

# 19. Clinical Record Release

Use explicit states:

```text
Not Released
Released
Restricted
Withdrawn
Amended
```

A record may be clinically finalized but not yet released to the patient.

---

# 20. Laboratory Results

Integrate with Phase 5 LIS.

Patients should be able to view:

- Test name
- Result
- Unit
- Reference range
- Result status
- Collection date
- Report date
- Laboratory
- Final report
- Authorized comments where applicable

The portal must not reinterpret laboratory results as medical advice.

---

# 21. Critical Result Handling

Critical laboratory results should follow the clinical organization's existing workflow.

The portal must not independently diagnose or interpret them.

Potential flow:

```text
LIS
→ Clinical Review / Release Policy
→ Patient Notification
```

The release policy should be configurable.

---

# 22. Radiology Results

Integrate with Phase 6.

Patients may access:

- Radiology report
- Exam details
- Study date
- Modality
- Referring provider
- Final report
- Images where the organization permits it

Images should remain in PACS/DICOM infrastructure.

Do not copy large imaging datasets into the portal database.

---

# 23. DICOM/PACS Viewer Integration

Support an integration boundary for:

- PACS viewer
- DICOMweb
- Secure image links
- Time-limited access tokens

The portal should not become a PACS.

---

# 24. Prescription Access

Integrate with Phase 3.

Patients may view:

- Medication
- Dose
- Route
- Frequency
- Duration
- Instructions
- Prescribing provider
- Prescription date
- Status

Do not create a second prescription engine.

---

# 25. Pharmacy Integration

Integrate with Phase 7.

Possible features:

- Prescription status
- Dispensing status
- Medication history
- Refill request where clinically permitted
- Pharmacy pickup information
- Medication reminders

The portal cannot independently authorize prescription refills.

Refill requests must go through configured clinical/pharmacy workflow.

---

# 26. Medication Safety

Do not allow patients to:

- Change dose
- Change frequency
- Change medication
- Override contraindications
- Modify prescription

The portal may submit a request to the clinical team.

---

# 27. Billing Integration

Integrate with Phase 4.

Patients may view:

- Invoice
- Invoice status
- Outstanding amount
- Payment history
- Receipt
- Refund status
- Advance balance
- Financial summary

Do not create a second billing system.

---

# 28. Online Payment Integration

Provide an abstraction:

```php
interface PatientPaymentProviderInterface
{
    public function createPayment(...);

    public function verifyPayment(...);

    public function refundPayment(...);
}
```

Support configurable payment providers.

Never store raw card data unless the organization has an explicitly compliant architecture requiring it.

Prefer hosted/tokenized payment flows.

---

# 29. Payment Workflow

```text
Portal
↓
Invoice
↓
Payment Request
↓
Payment Gateway
↓
Gateway Callback/Webhook
↓
Payment Verification
↓
Phase 4 Payment
↓
Receipt
↓
Portal Notification
```

Payment gateway callbacks must be idempotent.

Never mark a payment successful solely because the browser returned successfully.

---

# 30. Insurance Integration

Integrate with Phase 14.

Patients may view:

- Insurance provider
- Policy
- Member ID
- Coverage
- Authorization status
- Claim status where permitted
- Outstanding patient responsibility

Do not implement claim adjudication in Phase 17.

---

# 31. Admission / Discharge Information

Integrate with Phase 8.

Patients may view:

- Admission
- Hospital
- Ward
- Room/bed information where appropriate
- Admission date
- Discharge date
- Discharge summary
- Follow-up instructions

Do not allow portal users to modify bed allocation.

---

# 32. Emergency Information

Emergency information may include:

- Emergency contact
- Hospital emergency instructions
- Emergency department information
- Current emergency visit status where appropriate

Do not expose restricted clinical information simply because the patient is logged in.

---

# 33. Patient Documents

Patients may access authorized:

- Reports
- Prescriptions
- Discharge summaries
- Certificates
- Invoices
- Receipts
- Consent copies
- Medical documents

Use Phase 0 File Management.

Do not build a second document-storage engine.

---

# 34. Secure Document Delivery

Documents should be served through authorization-controlled access.

Avoid permanent public URLs.

Use:

```text
Authenticated Request
→ Authorization
→ Time-Limited Download/Stream
```

Every sensitive download should be auditable.

---

# 35. Consent Management

Patients should be able to manage supported consents.

Examples:

- Portal terms
- Communication consent
- Marketing consent
- Telehealth consent
- Data-sharing consent
- Research consent where supported

Clinical consent remains under appropriate clinical modules.

Do not assume portal consent equals clinical consent.

---

# 36. Communication Preferences

Patients may configure:

```text
Email
SMS
Push
WhatsApp adapter where legally/configurably supported
Phone
```

Preferences:

- Appointment reminders
- Billing notifications
- Lab report notifications
- Prescription notifications
- Marketing
- Health education
- Service updates

Transactional communications must remain distinguishable from marketing communications.

---

# 37. Notification Center

Portal notification types:

```text
Appointment
Queue
Lab
Radiology
Prescription
Pharmacy
Billing
Payment
Insurance
Admission
Discharge
Document
Service Request
Message
Security
```

Features:

- Read/unread
- Archive
- Preferences
- Deep link
- Timestamp
- Priority

Use the existing Phase 0 notification infrastructure.

---

# 38. Patient Messaging

Provide secure asynchronous communication.

Possible conversations:

```text
Patient
↔ Hospital Support

Patient
↔ Appointment Desk

Patient
↔ Billing

Patient
↔ Pharmacy

Patient
↔ Clinical Team
```

Clinical messaging must have explicit routing and role restrictions.

Do not automatically give every hospital employee access to patient messages.

---

# 39. Messaging Architecture

Suggested entities:

```text
Conversation
Conversation Participant
Message
Message Attachment
Message Status
Message Assignment
Message Escalation
```

Messages should support:

- Sent
- Delivered
- Read
- Archived
- Escalated
- Closed

---

# 40. Clinical Messaging Boundary

Patient messages are not automatically clinical documentation.

If a message becomes part of the medical record, the appropriate clinical workflow must explicitly convert/reference it.

Do not silently insert portal conversations into the EMR.

---

# 41. Patient Service Requests

Support:

- Appointment request
- Medical record request
- Certificate request
- Billing inquiry
- Refund inquiry
- Insurance inquiry
- Pharmacy inquiry
- Report request
- Document request
- Complaint
- Feedback
- General support

Workflow:

```text
Submitted
→ Assigned
→ In Progress
→ Pending Patient
→ Resolved
→ Closed
```

---

# 42. CRM

The CRM layer should focus on the **patient relationship**, not clinical ownership.

CRM capabilities:

- Patient interaction history
- Communication history
- Service requests
- Feedback
- Complaints
- Campaigns
- Segmentation
- Patient engagement
- Follow-up reminders
- Satisfaction tracking
- Outreach
- Referral source
- Retention analytics

---

# 43. Patient Journey

Support configurable journeys such as:

```text
New Patient
↓
Registration
↓
Appointment
↓
Visit
↓
Investigation
↓
Treatment
↓
Follow-up
↓
Medication
↓
Review
```

Or:

```text
Admission
↓
Treatment
↓
Discharge
↓
Follow-up
↓
Recovery
```

Journey stages should be configurable.

---

# 44. CRM Interaction Timeline

The timeline may include:

```text
Appointment
Visit
Message
Phone interaction
Service request
Complaint
Feedback
Payment
Lab report release
Prescription
Notification
Campaign interaction
```

Clinical records should be linked rather than duplicated.

---

# 45. Patient Segmentation

Support configurable segments:

```text
New Patients
Returning Patients
Inactive Patients
Frequent Visitors
Chronic Care Cohort
Pediatric Cohort
Maternity Cohort
Corporate Patients
Insurance Patients
High Service Utilization
```

Segments must be based on authorized data and configurable rules.

Avoid sensitive profiling beyond legitimate healthcare/operational purposes.

---

# 46. Marketing Campaigns

If marketing functionality is enabled, support:

- Campaign
- Audience
- Message
- Channel
- Schedule
- Consent
- Delivery
- Engagement
- Unsubscribe

Marketing must respect communication consent.

Do not send marketing messages to patients who have opted out.

---

# 47. Patient Feedback

Support:

- General feedback
- Appointment feedback
- Department feedback
- Service feedback
- Provider feedback
- Facility feedback

Feedback states:

```text
Submitted
Reviewed
Assigned
Investigating
Responded
Resolved
Closed
```

---

# 48. Complaints

Complaint management should support:

```text
Complaint
Category
Priority
Description
Attachments
Patient
Hospital
Department
Assigned Officer
Investigation
Response
Resolution
Closure
```

High-priority complaints should trigger configurable escalation.

---

# 49. Patient Satisfaction

Support configurable surveys:

- Appointment satisfaction
- OPD experience
- Admission experience
- Discharge experience
- Pharmacy
- Laboratory
- Radiology
- Emergency
- Overall hospital experience

Do not hardcode one survey methodology.

---

# 50. Digital Forms

Support configurable patient-facing forms.

Examples:

- Registration
- Pre-visit questionnaire
- Medical history
- Consent
- Feedback
- Insurance information
- Contact information
- Service request

Forms should support:

- Versioning
- Validation
- Draft
- Submission
- Amendment
- Audit

---

# 51. Telehealth Foundation

Phase 17 should provide integration points for telemedicine.

Possible flow:

```text
Appointment
↓
Telehealth Eligibility
↓
Consent
↓
Secure Session
↓
Provider
↓
Encounter
```

Phase 17 owns digital access/session orchestration.

The actual clinical encounter remains owned by Phase 3.

---

# 52. Telehealth Provider Abstraction

Example:

```php
interface TelehealthProviderInterface
{
    public function createSession(...);

    public function getSession(...);

    public function terminateSession(...);
}
```

Do not hardcode a particular video provider.

---

# 53. Family / Dependent Access

Support authorized family management.

Example:

```text
Primary Patient Account
 ├── Self
 ├── Child
 ├── Dependent
 └── Authorized Family Member
```

Access must be explicitly authorized.

Do not allow unrestricted access to another patient's clinical records.

---

# 54. Proxy Access

Support:

```text
Patient A
→ Grants Access
→ Patient B / Caregiver
```

Permissions may be:

```text
Appointments
Clinical Records
Billing
Pharmacy
Documents
Messages
```

Proxy access should have:

- Start date
- Expiry date
- Scope
- Revocation
- Audit

---

# 55. Minor/Dependent Access

Where applicable, support configurable guardian relationships.

Do not hardcode legal age rules.

The access model must support jurisdiction-specific policy.

---

# 56. Patient Preferences

Support:

```text
Language
Timezone
Notification Preferences
Communication Channel
Accessibility Preferences
Appointment Preferences
Hospital Preferences
Privacy Preferences
```

Localization should reuse Phase 0.

Support:

- English
- Bangla
- Arabic
- Other configured languages

---

# 57. Mobile API Architecture

The mobile application should consume:

```text
/api/v1/portal
/api/v1/mobile
```

The mobile application must not directly access the database.

---

# 58. API Authentication

Support appropriate token/session mechanisms already used by the platform.

Potential architecture:

```text
Mobile App
↓
Authentication
↓
Access Token
↓
API Gateway / Laravel API
↓
Authorization
↓
Existing Domain Services
```

Tokens must support:

- Expiration
- Revocation
- Rotation
- Device/session tracking

---

# 59. Standard API Response

Use the existing standard:

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
    "field": [
      "The field is required."
    ]
  }
}
```

Never expose:

- SQL errors
- Stack traces
- Internal paths
- Secrets
- Access tokens
- Other patients' identifiers

---

# 60. API Examples

```http
POST /api/v1/portal/auth/register
POST /api/v1/portal/auth/verify
POST /api/v1/portal/auth/login
POST /api/v1/portal/auth/logout

GET /api/v1/portal/me
GET /api/v1/portal/dashboard

GET /api/v1/portal/appointments
POST /api/v1/portal/appointments
POST /api/v1/portal/appointments/{id}/cancel
POST /api/v1/portal/appointments/{id}/reschedule

GET /api/v1/portal/encounters
GET /api/v1/portal/prescriptions
GET /api/v1/portal/laboratory-results
GET /api/v1/portal/radiology-reports

GET /api/v1/portal/billing
GET /api/v1/portal/invoices
POST /api/v1/portal/payments

GET /api/v1/portal/documents
GET /api/v1/portal/documents/{id}

GET /api/v1/portal/messages
POST /api/v1/portal/messages

GET /api/v1/portal/service-requests
POST /api/v1/portal/service-requests

GET /api/v1/portal/feedback
POST /api/v1/portal/feedback

GET /api/v1/portal/notifications
GET /api/v1/portal/preferences
PUT /api/v1/portal/preferences
```

---

# 61. Suggested Database Tables

Exact tables must be adapted to the existing repository.

## Portal Identity

```text
portal_accounts
portal_account_verifications
portal_account_devices
portal_account_sessions
portal_login_attempts
portal_password_resets
portal_mfa_methods
portal_mfa_challenges
```

## Patient Preferences

```text
portal_patient_preferences
portal_notification_preferences
portal_communication_preferences
portal_privacy_preferences
```

## Proxy / Family

```text
portal_family_relationships
portal_proxy_access
portal_proxy_access_permissions
portal_proxy_access_events
```

## Messaging

```text
portal_conversations
portal_conversation_participants
portal_messages
portal_message_attachments
portal_message_events
```

## Service Requests

```text
portal_service_requests
portal_service_request_categories
portal_service_request_events
portal_service_request_assignments
```

## CRM

```text
crm_patient_interactions
crm_interaction_types
crm_patient_segments
crm_patient_segment_members
crm_campaigns
crm_campaign_audiences
crm_campaign_messages
crm_campaign_deliveries
crm_campaign_events
```

## Feedback

```text
crm_feedback
crm_complaints
crm_complaint_events
crm_surveys
crm_survey_questions
crm_survey_responses
```

## Digital Forms

```text
portal_form_templates
portal_form_versions
portal_form_submissions
portal_form_submission_values
portal_form_events
```

## Consent

```text
portal_consents
portal_consent_versions
portal_consent_events
```

## Telehealth

```text
portal_telehealth_sessions
portal_telehealth_events
```

## Payments

Only portal-specific transaction references should exist.

Do not duplicate Phase 4 payment records.

Potential:

```text
portal_payment_attempts
portal_payment_provider_events
```

These should reference the canonical billing/payment transaction.

---

# 62. Portal Dashboard Architecture

Dashboard data should be assembled from domain services.

Example:

```text
PortalDashboardService
    ↓
AppointmentService
BillingService
ClinicalSummaryService
PrescriptionService
NotificationService
CRMService
```

Do not create duplicate copies of appointments, invoices, or clinical records.

---

# 63. Portal Clinical Data Projection

For performance, read-optimized projections/caches may be used.

However:

```text
Projection ≠ Source of Truth
```

The canonical clinical record remains in its owning module.

---

# 64. Caching

Potentially cache:

- Provider directory
- Hospital directory
- Department directory
- Public appointment metadata
- Non-sensitive configuration

Do not cache sensitive patient information without appropriate controls.

---

# 65. Search

Patients should be able to search their own:

- Appointments
- Reports
- Documents
- Prescriptions
- Messages
- Service requests
- Billing records

Do not expose internal administrative search.

---

# 66. Patient Directory

If the hospital has public provider discovery, expose controlled information:

```text
Doctor Name
Specialty
Department
Hospital
Branch
Qualifications where approved
Languages
Availability
```

Do not expose private employee information.

---

# 67. QR Code Support

Optional QR use cases:

- Appointment check-in
- Patient identification
- Prescription pickup
- Report retrieval
- Secure document access

QR tokens must be:

- Short-lived
- Signed
- Non-guessable
- Revocable where necessary

Never encode sensitive clinical data directly into a QR code.

---

# 68. Notifications Architecture

Use existing notification service.

Events may trigger:

```text
AppointmentBooked
AppointmentChanged
AppointmentCancelled
AppointmentReminder
QueueTurnApproaching
LabReportReleased
RadiologyReportReleased
PrescriptionIssued
PaymentReceived
InvoiceGenerated
AdmissionCreated
DischargeCompleted
DocumentReleased
ServiceRequestUpdated
MessageReceived
```

---

# 69. Notification Jobs

Potential scheduled jobs:

```text
SendAppointmentReminders
SendFollowUpReminders
SendMedicationReminderNotifications
SendOutstandingPaymentNotifications
SendDocumentAvailabilityNotifications
ProcessCampaignMessages
ExpirePortalSessions
ExpireProxyAccess
CleanupExpiredTokens
```

Use queues.

---

# 70. CRM Automation

Configurable automation examples:

```text
Appointment reminder
Follow-up reminder
Missed appointment reminder
Post-discharge follow-up
Feedback request
Report availability
Medication reminder
Annual health reminder
Inactive patient outreach
```

Every automation must respect consent and communication preferences.

---

# 71. Patient Journey Automation

Example:

```text
Discharge Completed
        ↓
Follow-Up Rule
        ↓
Reminder
        ↓
Patient Opens Portal
        ↓
Appointment Booking
```

The automation must create/request an appointment through Phase 2 rather than directly inserting appointment records.

---

# 72. AI Patient Engagement

AI can assist with:

- FAQ
- Navigation
- Appointment discovery
- Patient-service routing
- Message classification
- Feedback summarization
- CRM segmentation assistance
- Reminder optimization
- Communication personalization
- Patient journey summarization
- Non-diagnostic health education
- Administrative question answering

---

# 73. AI Clinical Safety

AI must NOT:

- Diagnose disease
- Prescribe medication
- Change medication
- Interpret critical laboratory results as a clinician
- Modify clinical records
- Modify appointments without authorization
- Approve clinical procedures
- Override clinician instructions
- Provide false medical certainty
- Represent itself as a doctor

For clinical questions, the system should use an appropriate clinical-information policy and escalate to healthcare professionals when necessary.

---

# 74. AI Chatbot Architecture

Use a controlled architecture:

```text
Patient
↓
AI Assistant
↓
Intent Detection
↓
Authorization
↓
Approved Knowledge / Domain Services
↓
Response
```

Examples of safe administrative requests:

```text
"What time is my appointment?"
"Where is the radiology department?"
"Show my upcoming appointments."
"How much is my outstanding invoice?"
"Has my report been released?"
"How do I cancel my appointment?"
```

For clinical questions:

```text
Patient
→ AI
→ Safety Classification
→ Approved information / clinician escalation
```

---

# 75. AI Access Control

AI must use the same authorization model as normal portal APIs.

Never allow:

```text
Patient asks AI:
"Show me another patient's records."
```

The AI must be denied by the underlying authorization layer.

Do not rely on prompt instructions alone.

---

# 76. AI Audit

Record:

```text
AI Request
User
Patient Context
Intent
Data Sources Used
Response Type
Escalation
Generated At
Model/Prompt Version
```

Do not store unnecessary sensitive conversation content.

Retention should be configurable.

---

# 77. AI Hallucination Controls

For transactional questions, AI should query authoritative systems.

For example:

```text
Appointment time
→ Appointment Service
```

not:

```text
AI memory
```

Similarly:

```text
Invoice balance
→ Billing Service
```

```text
Lab result
→ LIS
```

```text
Prescription
→ Clinical/Pharmacy Service
```

---

# 78. Security Model

Security must exist at multiple levels:

```text
Authentication
Authorization
Patient Ownership
Proxy Authorization
Organization Scope
Hospital Scope
Resource Scope
Field Scope
Document Scope
API Scope
```

Every portal endpoint must verify that the requested resource belongs to the authenticated patient or an explicitly authorized proxy.

---

# 79. Critical Security Test

Mandatory:

```text
Patient A
↓
GET /api/v1/portal/laboratory-results/Patient-B-result
↓
ACCESS DENIED
```

Test this across:

- Appointments
- Encounters
- Lab results
- Radiology
- Prescriptions
- Billing
- Documents
- Messages
- Service requests
- Family members

---

# 80. Object-Level Authorization

Never rely only on:

```text
auth()->check()
```

Always validate ownership/scope.

Example:

```text
Authenticated Patient
→ Resource
→ Resource.patient_id
→ Authorized Patient?
```

If not:

```text
403 Forbidden
```

---

# 81. Rate Limiting

Apply rate limits to:

- Login
- OTP
- Password reset
- Registration
- Appointment booking
- Messaging
- Payment creation
- Document access
- AI requests
- API endpoints

Use different limits for sensitive operations.

---

# 82. Fraud Protection

Monitor:

- Repeated OTP requests
- Multiple failed logins
- Unusual device changes
- Rapid document downloads
- Excessive appointment cancellation
- Payment anomalies
- Suspicious account linking

Create security events rather than automatically blocking legitimate patients without policy.

---

# 83. Privacy

Support:

- Data minimization
- Consent
- Access control
- Audit
- Data export where required
- Account closure
- Data retention
- Communication opt-out
- Proxy access management

Privacy rules must be configurable for applicable jurisdictions.

---

# 84. CRM Analytics

Recommended KPIs:

```text
Registered Portal Users
Active Portal Users
Mobile Users
Appointment Bookings
Online Cancellations
Digital Check-ins
Online Payments
Report Views
Document Downloads
Service Requests
Average Resolution Time
Patient Satisfaction
Complaint Rate
Message Response Time
Notification Delivery
Campaign Engagement
Inactive Patients
```

---

# 85. Patient Engagement Dashboard

Show:

```text
Total Registered Patients
Portal Activation Rate
Monthly Active Patients
Appointment Conversion
Online Payment Rate
Digital Check-in Rate
Report Engagement
Patient Satisfaction
Service Requests
Complaint Volume
```

---

# 86. Hospital CRM Dashboard

Allow filtering by:

- Organization
- Hospital
- Branch
- Department
- Specialty
- Date range
- Patient segment
- Channel

Access must respect user scope.

---

# 87. API Versioning

Use:

```text
/api/v1/portal
/api/v1/mobile
```

Future breaking changes should use:

```text
/api/v2/
```

Do not silently break mobile clients.

---

# 88. Mobile Application Compatibility

API contracts must support:

- Android
- iOS
- Web portal

Do not put presentation-specific business rules inside API controllers.

Use:

```text
Controller
→ Request Validation
→ Service/Action
→ Domain
→ Resource/Transformer
```

---

# 89. Offline Mobile Support

If mobile offline capabilities are implemented, only non-sensitive data should be cached by default.

Examples:

- Hospital directory
- Provider directory
- Public information

Sensitive clinical records should use encrypted device storage and explicit policy.

---

# 90. Push Notifications

Support a provider abstraction:

```php
interface PushNotificationProviderInterface
{
    public function send(...);

    public function registerDevice(...);

    public function revokeDevice(...);
}
```

Do not hardcode one mobile push provider.

---

# 91. Email / SMS Provider

Similarly use:

```php
interface CommunicationProviderInterface
{
    public function sendEmail(...);

    public function sendSms(...);
}
```

Use existing notification architecture if already available.

---

# 92. Suggested Events

```text
PortalAccountCreated
PortalAccountVerified
PortalLoginSucceeded
PortalLoginFailed
PortalDeviceRegistered
PortalDeviceRevoked

AppointmentViewed
AppointmentBooked
AppointmentCancelled
AppointmentRescheduled
DigitalCheckInCompleted

ClinicalRecordReleased
LabReportReleased
RadiologyReportReleased
PrescriptionReleased
DocumentReleased

PaymentInitiated
PaymentVerified

MessageCreated
MessageReceived
MessageEscalated

ServiceRequestCreated
ServiceRequestAssigned
ServiceRequestResolved

FeedbackSubmitted
ComplaintSubmitted
ComplaintResolved

ProxyAccessGranted
ProxyAccessRevoked

ConsentGranted
ConsentRevoked

CampaignCreated
CampaignMessageSent
CampaignUnsubscribed
```

---

# 93. Suggested Jobs

```text
SendPortalAppointmentReminders
SendFollowUpReminders
SendLabReportNotifications
SendRadiologyNotifications
SendPaymentNotifications
ProcessPortalCampaigns
ExpirePortalSessions
ExpireProxyAccess
CleanupExpiredTokens
GeneratePatientEngagementMetrics
ClassifyCRMFeedback
GenerateCRMReports
```

---

# 94. Roles

Suggested internal roles:

```text
Patient
Caregiver / Authorized Proxy
Patient Support Officer
CRM Officer
CRM Manager
Patient Relations Officer
Appointment Desk
Billing Support Officer
Pharmacy Support Officer
Clinical Communication Officer
Portal Administrator
Mobile/API Administrator
Marketing Officer
Patient Experience Manager
```

Internal roles must use existing RBAC.

The patient is not a normal internal administrative role.

---

# 95. Permissions

Suggested permissions:

```text
portal.dashboard.view

portal.patient_profile.view
portal.patient_profile.update

portal.appointment.view
portal.appointment.book
portal.appointment.cancel
portal.appointment.reschedule

portal.clinical.view
portal.prescription.view
portal.lab.view
portal.radiology.view
portal.admission.view

portal.billing.view
portal.payment.create
portal.receipt.view

portal.document.view
portal.document.download

portal.message.view
portal.message.create
portal.message.manage

portal.request.view
portal.request.create
portal.request.manage

portal.feedback.create
portal.complaint.create
portal.complaint.manage

portal.proxy.view
portal.proxy.manage

portal.consent.view
portal.consent.manage

crm.patient.view
crm.interaction.view
crm.interaction.create
crm.segment.manage
crm.campaign.manage
crm.feedback.manage
crm.complaint.manage
crm.report.view
crm.report.export

portal.settings.manage
portal.audit.view
```

---

# 96. Portal UI

Recommended web portal structure:

```text
Patient Portal
├── Dashboard
├── My Profile
├── Appointments
├── Visits
├── Prescriptions
├── Laboratory
├── Radiology
├── Medications
├── Admissions
├── Billing & Payments
├── Insurance
├── Documents
├── Messages
├── Service Requests
├── Feedback & Complaints
├── Family & Dependents
├── Consents
├── Notifications
├── Preferences
└── Security
```

---

# 97. Mobile Application Navigation

Suggested:

```text
Home
Appointments
Health Records
Prescriptions
Reports
Billing
Messages
Notifications
Profile
More
```

Keep mobile UI separate from internal AdminLTE interfaces.

---

# 98. CRM UI

Internal CRM:

```text
CRM
├── Dashboard
├── Patient Search
├── Patient 360
├── Interactions
├── Service Requests
├── Feedback
├── Complaints
├── Surveys
├── Segments
├── Campaigns
├── Communications
├── Patient Journeys
└── Reports
```

---

# 99. Patient 360 Internal View

Authorized CRM staff may see:

```text
Patient Profile
Appointments
Visits
Service Requests
Communications
Feedback
Complaints
Billing Summary
Engagement
Campaign History
Preferences
Consent
```

Clinical details should remain restricted unless the user has appropriate clinical permissions.

---

# 100. Service-Level Targets

Support configurable SLA:

```text
Service Request Category
Priority
First Response Time
Resolution Time
Escalation Level
Assigned Team
```

Example:

```text
Billing Inquiry
→ 4 hours

General Complaint
→ 24 hours

Urgent Patient Support
→ Configurable
```

Do not hardcode these as universal requirements.

---

# 101. CRM Assignment

Requests may be routed by:

```text
Hospital
Department
Category
Priority
Patient Type
Language
Staff Availability
```

Assignment should use configurable rules.

---

# 102. Communication Templates

Use versioned templates:

```text
Appointment Confirmation
Appointment Reminder
Cancellation
Lab Result Available
Radiology Report Available
Payment Confirmation
Invoice Reminder
Prescription Available
Follow-Up Reminder
Feedback Request
Complaint Response
```

Templates should support localization.

---

# 103. Localization

Patient-facing content must support:

```text
English
Bangla
Arabic
Other configured languages
```

Do not hardcode text directly in controllers.

Use localization resources.

---

# 104. Accessibility

Portal/mobile should support:

- Keyboard navigation
- Screen readers
- Appropriate contrast
- Adjustable text
- Clear error messages
- Accessible forms
- Semantic HTML
- Accessible authentication

---

# 105. Audit

Audit:

```text
Portal Registration
Login
Logout
Account Linking
Patient Profile Update
Appointment Booking
Appointment Cancellation
Record View
Record Download
Payment
Message
Service Request
Proxy Access
Consent
Communication Preference
Document Access
AI Interaction where configured
```

Sensitive record access should be auditable.

---

# 106. Data Export

Where permitted, patients may request:

```text
Clinical Summary
Laboratory Reports
Radiology Reports
Prescriptions
Billing Records
Documents
```

Exports must:

- Verify identity
- Check authorization
- Generate securely
- Expire download links
- Log access
- Avoid exposing unrelated records

---

# 107. Account Closure

Support controlled account deactivation.

Account closure must not automatically delete the patient's medical record.

Conceptually:

```text
Portal Account
→ Deactivated

Patient Clinical Record
→ Retained according to policy
```

---

# 108. Data Retention

Portal-specific records should have configurable retention:

- Sessions
- OTP records
- Security events
- Messages
- CRM interactions
- Campaign records
- AI interactions
- Service requests

Clinical and financial retention remains governed by their owning modules.

---

# 109. Performance Architecture

The system should support:

```text
Large Patient Population
High Mobile Traffic
High Notification Volume
Large Document Access
Concurrent Appointment Booking
Concurrent Payment Requests
```

Use:

- Redis
- Queues
- Caching
- Pagination
- API resources
- Database indexes
- Rate limiting
- Background jobs
- Idempotency

---

# 110. Appointment Concurrency

Critical requirement:

Two patients must not successfully book the same slot when only one slot exists.

The portal must rely on Phase 2's concurrency-safe booking mechanism.

Do not implement:

```text
check availability
→ insert appointment
```

without transactional protection.

---

# 111. Payment Concurrency

Payment callbacks must be idempotent.

Example:

```text
Gateway Callback
→ Payment Reference
→ Existing Transaction?
→ Yes: Ignore duplicate
→ No: Process
```

Never create duplicate payments from repeated callbacks.

---

# 112. Messaging Security

Messages must enforce:

- Participant authorization
- Attachment authorization
- Patient scope
- Staff scope
- Conversation ownership
- Rate limiting
- Audit

Attachments should use secure file storage.

---

# 113. Document Security

A document URL must never be sufficient by itself.

Every request should validate:

```text
Authenticated User
+
Patient Relationship
+
Document Ownership
+
Document Permission
+
Document Status
```

---

# 114. CRM Data Protection

Marketing/CRM users should not automatically see:

- Full clinical notes
- Sensitive diagnoses
- Clinical documents
- Medication details
- Restricted laboratory information

Use purpose-based access.

---

# 115. AI Privacy

AI systems must receive only the minimum necessary information.

For example:

A question about appointment time should not send the patient's complete medical record to the AI model.

Use:

```text
Intent
→ Required Data
→ Authorized Data
→ AI
```

not:

```text
Entire Patient Record
→ AI
```

---

# 116. Recommended Implementation Order

Implement in this order:

```text
1. Inspect repository
2. Inspect Phases 0–16
3. Map existing patient/domain services
4. Portal architecture
5. Patient portal account
6. Authentication/MFA
7. Device/session management
8. Patient dashboard
9. Appointment integration
10. Clinical information access
11. Prescription access
12. Laboratory integration
13. Radiology integration
14. Admission/discharge integration
15. Billing/payment integration
16. Insurance integration
17. Document access
18. Notifications
19. Messaging
20. Service requests
21. Feedback
22. Complaints
23. CRM
24. Patient segmentation
25. Campaigns
26. Patient journey
27. Proxy/family access
28. Consent
29. Digital forms
30. Telehealth integration
31. Mobile API
32. Push notifications
33. AI extension points
34. Dashboards
35. Reports
36. Security
37. Audit
38. Performance
39. Testing
40. Documentation
```

---

# 117. Definition of Done

Phase 17 is complete when:

### Portal

- Patient registration implemented
- Account verification implemented
- Login/MFA implemented
- Device/session management implemented
- Patient dashboard implemented
- Profile access implemented

### Appointments

- Search implemented
- Booking implemented
- Cancellation implemented
- Rescheduling implemented
- Digital check-in foundation implemented

### Clinical

- Encounter access implemented
- Prescription access implemented
- Lab result access implemented
- Radiology report access implemented
- Admission/discharge access implemented
- Clinical release controls implemented

### Financial

- Invoice access implemented
- Payment integration implemented
- Receipt access implemented
- Payment idempotency implemented

### Communication

- Notification center implemented
- Secure messaging implemented
- Communication preferences implemented
- Email/SMS/push adapters implemented

### CRM

- Patient 360 implemented
- Interaction timeline implemented
- Service requests implemented
- Feedback implemented
- Complaints implemented
- Surveys implemented
- Segmentation implemented
- Campaign foundation implemented
- Patient journeys implemented

### Family

- Dependents implemented
- Proxy access implemented
- Scope-based authorization implemented

### Documents

- Secure document access implemented
- Secure downloads implemented
- Audit implemented

### Telehealth

- Provider abstraction implemented
- Session integration foundation implemented
- Consent integration implemented

### Mobile

- Mobile API implemented
- Authentication implemented
- API versioning implemented
- Push notification foundation implemented

### Security

- Object-level authorization
- Patient ownership validation
- Proxy authorization
- Rate limiting
- Session security
- MFA
- Document security
- Payment security
- API security
- Audit

### AI

- AI service abstraction
- Authorized data retrieval
- Administrative assistant
- CRM analytics extension points
- AI audit
- Human/clinical escalation
- No autonomous clinical decision-making

### Testing

- Unit tests
- Feature tests
- API tests
- Integration tests
- Security tests
- Authorization tests
- Concurrency tests
- Payment idempotency tests
- Appointment booking concurrency tests
- Mobile authentication tests

---

# 118. FINAL AI IMPLEMENTATION PROMPT

## AI CODING AGENT — IMPLEMENT PHASE 17

You are a senior Laravel enterprise architect and healthcare software engineer.

Implement **Phase 17 — Patient Portal / Mobile / CRM** inside the existing Hospital Management System.

---

## STEP 1 — INSPECT BEFORE CODING

Before making changes:

1. Inspect the complete repository.
2. Inspect the existing modular architecture.
3. Inspect Phases 0–16.
4. Identify existing:
   - Patient/MPI
   - Users
   - Authentication
   - Roles
   - Permissions
   - API
   - Appointments
   - Encounters
   - Billing
   - Laboratory
   - Radiology
   - Pharmacy
   - IPD
   - Nursing
   - OT
   - ICU
   - Emergency
   - Blood Bank
   - Insurance
   - Inventory
   - HR
   - Notifications
   - Workflow
   - Audit
   - File Management
5. Map dependencies.
6. Identify reusable services and APIs.
7. Identify existing database tables that must not be duplicated.

### CRITICAL

The modular architecture already exists.

**Do not create another modular architecture.**

---

# IMPLEMENT PHASE 17

Implement:

```text
Patient Portal
Mobile API
Patient Account
Authentication
MFA
Device Management
Session Management
Patient Dashboard
Appointment Integration
Clinical Record Access
Prescription Access
Lab Integration
Radiology Integration
Admission Integration
Billing Integration
Payment Gateway Integration
Insurance Integration
Document Access
Notifications
Messaging
Service Requests
Feedback
Complaints
CRM
Patient 360
Patient Segmentation
Campaign Foundation
Patient Journeys
Family/Proxy Access
Consent
Digital Forms
Telehealth Foundation
AI Extension Points
Analytics
Reports
Security
Audit
```

---

# CRITICAL DOMAIN RULE

Phase 17 is a **digital experience layer**.

Do NOT create duplicate:

```text
Patient
Appointment
Encounter
Prescription
Lab
Radiology
Billing
Insurance
Admission
Pharmacy
Clinical
Document
Notification
Workflow
```

Use existing modules.

---

# PATIENT IDENTITY

Implement a portal account linked to the existing Patient/MPI record.

Never create a second patient identity.

Prevent unauthorized account linking.

---

# SECURITY

Every patient-facing resource must use object-level authorization.

For every request verify:

```text
Authenticated Account
↓
Linked Patient
↓
Requested Resource
↓
Resource Patient
↓
Authorized?
```

If not:

```text
403 Forbidden
```

Test direct ID manipulation.

---

# APPOINTMENTS

Do not implement a second appointment engine.

Use Phase 2.

Patient booking must use the existing concurrency-safe booking mechanism.

Two patients must never successfully reserve the same exclusive slot.

---

# CLINICAL RECORDS

Do not copy clinical records into a separate portal database unless a justified read projection is required.

If projections are created:

```text
Projection != Source of Truth
```

Clinical information release must be controlled.

Not every internal clinical record should automatically be patient-visible.

---

# LABORATORY

Integrate with Phase 5.

Only display authorized/released results.

Do not invent clinical interpretations.

---

# RADIOLOGY

Integrate with Phase 6.

Do not store PACS images in the portal database.

Use secure PACS/DICOMweb integration where applicable.

---

# PRESCRIPTIONS

Integrate with Phase 3 and Phase 7.

Never allow the patient to directly modify:

```text
Medication
Dose
Route
Frequency
Duration
```

Refill requests must go through approved clinical/pharmacy workflow.

---

# BILLING

Integrate with Phase 4.

Do not create duplicate invoices or payments.

Implement payment provider abstraction.

Payment callbacks must be idempotent.

Never trust browser redirect success as proof of payment.

---

# DOCUMENTS

Use existing File Management.

Documents must use authorization-controlled access.

Never create permanent public URLs for sensitive patient documents.

---

# FAMILY / PROXY ACCESS

Implement explicit:

```text
Grant
Scope
Start
Expiry
Revoke
Audit
```

A caregiver must only access resources explicitly authorized.

---

# MESSAGING

Implement secure conversations.

Patient messages must not automatically become clinical documentation.

If clinical documentation is required, use an explicit clinical workflow.

---

# CRM

Implement:

```text
Patient 360
Interaction Timeline
Service Requests
Feedback
Complaints
Surveys
Segments
Campaign Foundation
Patient Journey
```

CRM users must not automatically receive unrestricted clinical data.

---

# COMMUNICATION

Implement:

```text
Email
SMS Adapter
Push Adapter
Notification Center
Communication Preferences
```

Respect marketing consent and unsubscribe preferences.

---

# MOBILE API

Implement:

```text
/api/v1/portal
/api/v1/mobile
```

Use the existing API conventions.

Do not expose database access directly to mobile clients.

---

# AUTHENTICATION

Implement secure:

```text
Registration
Verification
Login
Logout
Password Reset
MFA
Device Management
Session Revocation
Rate Limiting
```

Support appropriate token/session architecture already present in the repository.

---

# AI

Implement an AI abstraction capable of:

```text
Administrative Patient Assistant
Appointment Navigation
Service Request Routing
FAQ
Patient Journey Summarization
Feedback Summarization
CRM Analytics
Engagement Optimization
```

AI must NOT:

```text
Diagnose
Prescribe
Change Medication
Modify Clinical Records
Override Clinician Decisions
Approve Clinical Procedures
Interpret Critical Results as a Clinician
```

For transactional information, query authoritative systems.

Examples:

```text
Appointment time
→ Appointment Service

Invoice balance
→ Billing Service

Lab result
→ LIS

Prescription
→ Clinical/Pharmacy Service
```

Do not rely on model memory for live patient data.

---

# AI SECURITY

AI must respect the same authorization as the normal API.

A patient asking:

```text
"Show me another patient's report."
```

must be denied by the backend authorization layer.

Do not rely only on prompt instructions.

---

# AI PRIVACY

Only send the minimum necessary information to AI.

Do not send the entire patient record for a simple administrative request.

Use:

```text
Intent
→ Required Data
→ Authorization
→ Data Retrieval
→ AI
```

---

# DATABASE

Before creating migrations:

1. Inspect existing tables.
2. Reuse existing patient/domain tables.
3. Avoid duplicates.
4. Follow repository naming conventions.
5. Add proper foreign keys.
6. Add indexes.
7. Add unique constraints.
8. Use immutable audit/history where required.

---

# CONCURRENCY

Protect:

```text
Appointment Booking
Payment
Proxy Access
Account Linking
Service Request Assignment
Messaging
Campaign Delivery
```

Use:

- Transactions
- Row locks
- Unique constraints
- Idempotency keys
- State validation

---

# TESTING

Create tests for:

### Authentication

```text
Registration
Verification
Login
MFA
Password Reset
Session Revocation
```

### Authorization

```text
Patient A cannot access Patient B
Caregiver cannot access unauthorized records
Expired proxy access denied
Revoked proxy access denied
CRM user cannot access restricted clinical information
```

### Appointments

```text
Duplicate booking prevented
Concurrent booking handled
Unauthorized cancellation prevented
```

### Payments

```text
Duplicate callback ignored
Invalid payment rejected
Unauthorized invoice access denied
```

### Documents

```text
Unauthorized document download denied
Expired access link denied
```

### API

```text
Invalid token
Expired token
Wrong patient
Wrong organization
Rate limit
Validation
```

### AI

```text
Unauthorized data request blocked
Clinical diagnosis request safely handled
Live appointment data retrieved from authoritative service
AI cannot modify clinical records
```

---

# PERFORMANCE

Design for:

```text
Large patient population
High mobile traffic
High notification volume
Large document access
Concurrent appointment booking
Concurrent payment callbacks
```

Use:

```text
Redis
Queues
Caching
Pagination
Indexes
Background Jobs
API Resources
```

---

# DOCUMENTATION

Create/update:

```text
Phase 17 Architecture
Portal Authentication
Mobile API
Patient Authorization
Proxy Access
Appointment Integration
Clinical Data Release
Payment Integration
CRM
Messaging
Notification Architecture
AI Safety
Security
Testing
Deployment
Configuration
```

---

# FINAL VERIFICATION

Before reporting completion:

1. Run tests.
2. Run migrations in test environment.
3. Verify routes.
4. Verify API authentication.
5. Verify object-level authorization.
6. Verify patient isolation.
7. Verify proxy access.
8. Verify appointment concurrency.
9. Verify payment idempotency.
10. Verify document security.
11. Verify notification jobs.
12. Verify queues.
13. Verify mobile API.
14. Verify CRM permissions.
15. Verify AI access controls.
16. Verify no duplicate domain architecture was created.

Never claim a test passed unless it was actually executed.

---

# FINAL IMPLEMENTATION REPORT

Return:

```text
1. Implementation Summary
2. Existing Architecture Reused
3. Portal Components
4. Mobile API
5. Authentication/MFA
6. Patient Authorization
7. Appointment Integration
8. Clinical Integrations
9. Billing/Payment Integration
10. CRM
11. Messaging
12. Notifications
13. Proxy/Family Access
14. Telehealth Integration
15. AI Components
16. Database/Migrations
17. API Endpoints
18. Permissions/Roles
19. Events/Jobs
20. Security Tests
21. Functional Tests
22. Concurrency Tests
23. Performance Considerations
24. Documentation
25. Known Limitations
26. Recommended Next Steps
```

Do not report anything as implemented or tested unless it actually is.

# END PHASE 17