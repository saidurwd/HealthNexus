# P17-S00 — Inspect & Map: Patient Portal, Mobile and CRM

Read-only report (guideline 26.6). The 118-section `Phase 17 — Patient Portal - Mobile - CRM` spec is not in the repository, so every number below (OTP length and lifetime, lock-out thresholds, release delays, quiet hours, complaint deadlines, link lifetime) is a **configurable example** in `config/portal.php`, overridable by `portal.*` settings, for the hospital's management, the privacy officer and a lawyer to confirm (OD-17).

## What already exists (reused, not copied)
| Need | Owner | Note |
|---|---|---|
| Patient identity, phone, date of birth, national id, guardians, preferences, consents | Phase 1 `patients`, `patient_guardians`, `patient_preferences`, `patient_consents` | The portal reads them; consent grant/withdraw by the patient writes `patient_consents` through the same model (new versions, never edits) |
| Portal account | Phase 1 `patient_portal_accounts` | **Extended in a new migration** (phone, password hash, status, lock-out, MFA, language, public id). No second account table (guideline B). The old `user_id` column stays, unused |
| Booking with the staff slot lock | `AppointmentBookingService::book` (+ `AppointmentLifecycleService`) | Called as-is with source `patient_portal`; a locked system user stands in for "the booker" |
| Lab / radiology results | `lab_reports`, `lab_results`, `radiology_reports` | **Read only.** Portal adds a *release decision* table; it never copies results |
| Critical results | `LabCriticalResultAlert`, `RadiologyCriticalFinding` | A critical result is held until a clinician acknowledges it; never auto-released |
| Prescriptions | `prescriptions`, `prescription_items` | Refill requests are new rows that staff review |
| Invoices and payments | `BillingInvoice`, `PaymentService::collect` | Online payment confirmed by the gateway calls `collect` with the "online" method; idempotent |
| Notifications | `NotificationService` (staff, database channel) | Staff only. Patient messaging gets `CommunicationService` with consent, opt-out, quiet hours and an outbox |
| Numbers, settings, audit, public ids | `NumberSeriesService`, `SettingsService`, `AuditLogger`, `HasPublicId` | |
| Sanctum | `User` has `HasApiTokens` | `PatientPortalAccount` gets it too; tokens carry the ability `portal` and nothing else |
| Gateways | `config/integrations.php` | New: `PaymentGateway`, `SmsGateway`, `TelehealthGateway` contracts, each with a null (or sandbox) adapter |

## Decisions (see D-P17)
- Separate guard `portal` (session, for the light web pages) and Sanctum tokens whose tokenable is the portal account (mobile). A staff token cannot reach a portal route and the reverse.
- Registration never confirms whether a patient exists: the same answer comes back for a match and a miss; the OTP goes to the phone *on file*.
- A result reaches the patient only through `portal_result_releases`. Policy per category: immediate, after clinician review, delayed N hours, or never online. Sensitive tests default to "after review".
- Proxies (parent, guardian, adult child) are approved by staff, scoped and time-limited, and every read is audited.
- CRM campaigns use only consented channels, honour quiet hours, and cannot filter on a diagnosis unless the segment records a legal basis and each patient has the clinical-marketing consent.

## Questions for the owner (not blocking)
1. Which SMS gateway, which payment aggregator (bKash, Nagad, card), and which video provider; each is one adapter class once chosen.
2. The release policy per test category (especially HIV, pregnancy, genetic, biopsy) and who may override.
3. Minimum age for an own account; how a parent hands over at 18.
4. Quiet hours, complaint response deadlines.
5. Whether a patient may cancel or reschedule on the day.
