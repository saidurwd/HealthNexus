# Patient Portal / CRM — Module Charter

_Updated in every slice (guideline 7.4). Phase 17 slices S00–S13 are built; every number (code lifetime, lock-out, release delays, quiet hours, link lifetime, complaint deadlines, retention) is an example awaiting management, the privacy officer and a lawyer (OD-17)._

- Phase / owner: 17 / **owner to be named by the hospital** (patient relations manager and the privacy officer)
- Status: built · switchable per organization / hospital (`portal`, depends on patient)
- Purpose: give patients a light, safe way to join, book, read released results, ask for refills, pay, message and rate the hospital — on web pages and a mobile API — and give the hospital a patient-relations desk (contacts, groups, campaigns, complaints, forms) that respects each patient's choices.
- Permission prefixes: `portal`, `crm`. Table prefixes: `portal_`, `crm_` (+ the Phase 1 `patient_portal_accounts`, extended, not replaced). Routes: patients `/portal/*`; staff `/admin/portal/*`, `/admin/crm/*`. API: `/api/v1/portal/*` (the mobile app), versioned by path and the `X-Api-Version` header.
- Owns: the portal login (account, one-time codes, devices, sign-in events), relatives' approvals (proxies), communication choices, the message outbox and notice centre, the access log, shared document links, result-release rules and decisions, refill requests, online payments and settlement files, message threads, service requests; CRM contacts, groups (segments), campaigns, ratings, complaints and forms.
- Does NOT own: patients, appointments, results, prescriptions, invoices, payments (read through projections or changed only by calling the owning module's action — `AppointmentBookingService`, `PaymentService::collect`); consents (the Phase 1 `patient_consents`, versioned); notifications to staff (core `NotificationService`).
- Security: a separate guard (`portal`) and Sanctum tokens whose owner is a patient account with the single ability `portal`; staff routes refuse patient tokens (`staff.only`) and patient routes refuse staff; the organization comes from the account, never from a header; registration and reset answer the same for a known and an unknown person; the one-time code goes to the phone number on the patient record; wrong passwords lock the phone number; every IDOR path returns 404 (tested for every id the API takes); mass assignment cannot move a record to another patient or hospital; responses carry no hashes, tokens or keys; pages are `Cache-Control: no-store`.
- Release rules (guideline override 2): per category — immediately, after a clinician releases, after a delay, or not online; words like HIV, pregnancy, genetic, biopsy force "after a clinician"; a critical result is never released by a timer or a policy: its alert must be acknowledged AND a clinician releases it; withholding is audited.
- Money: a payment becomes a Billing payment only after the aggregator confirms it (the browser coming back is never proof); idempotency key per request; one payment in progress per bill; paid at the counter meanwhile → flagged "to refund", never posted twice; the provider's settlement file is matched line by line.
- Consent: purposes `transactional` (cannot be switched off), `service` (on until off), `marketing` (off until the patient says yes); quiet hours hold texts and push; weekly cap on marketing; a diagnosis-based group needs a written legal basis, a second approver and the patient's clinical-marketing consent.
- Relatives (S10): requested by the relative, checked and approved by staff with scopes and an end date; a parent's approval ends at the child's 18th birthday; a teenager's results and messages are hidden from a parent; every read through a relative is logged as such.
- Hooks: `portal:check` every five minutes (release decisions for new reports, timed releases, held texts, payments in progress, ended approvals, late complaints, removal of old codes).
- Gateways (null adapters): `SmsGateway`, `PaymentGateway` (+ a sandbox adapter for development and the demo), `TelehealthGateway`; the AI assistant is only an extension point (`PortalAssistant`, Phase 20).
- Settings keys: `portal.*` override `config/portal.php`.
- Reports: portal and CRM dashboards, who looked at what, outbox, settlement matching; complaints by kind, deadlines, satisfaction; visit ratings and net promoter score.
- Known limitations & deferred items: see docs/decisions/D-P17-portal-state.md.

## Tables


