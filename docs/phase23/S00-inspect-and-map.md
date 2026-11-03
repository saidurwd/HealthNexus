# Phase 23 — S00 Inspect and map (Quality, Safety and Compliance)

Guideline 27.3. Rule of the phase: **incidents are append-only with amendments; the reporter's identity is protected; access is limited to quality roles; nothing in the tool is punitive.**

## What exists (reused, never copied)
| Need | Existing | Use |
|---|---|---|
| Blood-bank safety events | `blood_incidents` (type, severity, status), `blood_transfusion_reactions`, `blood_recalls` | read by the sweep; each becomes **one** quality incident that points back (`source_type/source_id`, unique) |
| Medicine safety | `pharmacy_safety_alerts` where `is_overridden`, `pharmacy_recalls`, `inventory_recalls` | overridden severe alerts and recalls are swept as medication-safety incidents |
| Theatre | `ot_surgical_safety_checklists` (not completed), postponements/cancellations, returns to theatre | sweep: a skipped checklist phase; the **return-to-OT rate** indicator |
| Falls, pressure injury | `nursing_risk_assessments` (fall, pressure risk), `nursing_wound_assessments`, `nursing_escalations` | these are *assessments*, not events: an actual fall or injury is reported by staff as an incident of that category; the risk data feeds the indicators |
| Deaths | `ipd_admissions.status = deceased`, ED disposition `death` | every death opens a **mortality review** (once) |
| Complaints | `crm_complaints` (Phase 17) | linked, never copied: **raise as incident** keeps the complaint number only |
| Staff credentials | `hr_credentials` (type, number, expiry, status) | credentialing screen reads them; privileges are new |
| Facts | Phase 19 warehouse: `fact_admissions`, `fact_surgeries`, `fact_encounters`, `fact_daily_metrics` | quality indicators read volumes (admissions, bed-days, dispensings) through the `Warehouse` service and events from this module's own tables; return-to-theatre reads the operational operations table (the warehouse keeps no patient on operations) |
| Alerts, audit, numbering, settings, files, PDF | `AlertService`, `AuditLogger`, `NumberSeriesService`, `SettingsService`, `FileService`, `PdfRenderer` | same conventions |

Nothing in IPD, Nursing, OT, ED, Blood Bank, Pharmacy or CRM is changed (rule 15). Their events are *read*; the sweep is idempotent.

## Design
- **Table prefix `qa_`, permission prefix `quality`, module key `quality`, API `/api/v1/quality`.**
- **Incident = a frozen report + an amendment trail.** `qa_incidents` holds what was reported (kind, category, when/where, what happened, harm level, optional patient). Those fields never change after saving — the model refuses it and **database triggers** refuse it on SQLite and MySQL. Management fields (status, owner, classification after review, linked items) are separate columns that change through the workflow. Anything a person wants to correct is an **amendment** (`qa_incident_amendments`: field, old, new, reason, who, when).
- **Reporter protection.** The reporter is **not** on the incident row. `qa_incident_reporters` holds `user_id` only when the reporter chose to be named, and only users with `quality.incident.reveal` can read it — every reveal is audited. An anonymous report has no row at all, so there is nothing to reveal. The reporter always sees their own reports ("My reports").
- **Harm classification** (not blame): `none` (near miss), `minor`, `moderate`, `major`, `catastrophic`; `kind` = `incident | near_miss | adverse_event | sentinel`. Sentinel events (death or permanent harm) raise an alert to the quality managers at once.
- **No punitive use:** no field for "person at fault"; contributing factors are *systems* categories (communication, equipment, process, staffing, environment, training, patient factors, other). Reports cannot be exported with the reporter.
- **Investigation → CAPA.** One investigation per incident (lead, method, findings, root causes by category). Actions (`qa_actions`) have an owner, a due date and a type (corrective / preventive); marking done needs evidence text, and **verification of effectiveness is by a second person**. Overdue actions are alerted by the daily check. Actions also serve committees, audits and accreditation gaps (`source_type/source_id`).
- **Infection control.** `qa_infection_cases` (HAI type, organism, onset, patient, ward, device), `qa_isolations` (precaution type, start/end — a flag any module can ask `IsolationGateway` about), `qa_hand_hygiene_audits` (ward, moments observed, moments compliant, by role), `qa_outbreaks` (opened by the daily check when ≥ N cases share a ward or organism within the window; closed by a person).
- **Indicators.** `qa_indicators` (definition: source and formula key, direction, target) and `qa_indicator_values` (month, numerator, denominator, value) computed from warehouse facts and quality tables: 30-day readmission, return to theatre, falls per 1,000 bed-days, pressure injuries per 1,000 bed-days, medication errors per 1,000 dispensings, in-hospital mortality, mortality reviewed within 30 days, HAI per 1,000 bed-days, hand-hygiene compliance, incidents closed on time, CAPA on time. **Clinical audits** (`qa_audits`, `qa_audit_findings`) record a topic, criteria, sample and compliance, and raise actions for gaps.
- **Document control.** `qa_documents` (code, title, category, owner, review interval) and `qa_document_versions` (draft → in review → approved → retired; approval by someone other than the author; effective date; only one approved version at a time). Staff are assigned by role or department and **acknowledge** the version they were given (`qa_acknowledgements`, unique per user and version); a new version resets the ask.
- **Credentialing and privileging.** `qa_privileges` (procedures a person may perform: granted by decision of a second person, with an expiry; a privilege needs a valid `hr_credentials` licence at grant time and lapses when it expires), `qa_compliance_items` (licences, inspections, certificates with authority, due date, owner, status → a calendar), **accreditation checklists** (`qa_standards` → `qa_standard_items` → `qa_assessments` with `qa_assessment_results`: compliant / partial / not / not applicable, evidence, owner, due; gaps become actions). The starter standards are generic examples for the hospital to replace — no accreditor's text or trademark is bundled.
- **Committees.** `qa_committees`, `qa_meetings` (agenda, attendance, minutes; status planned → held → minuted), `qa_meeting_items` (an incident, a mortality review, a case for morbidity and mortality discussion, with outcome and the resulting action). Mortality reviews (`qa_mortality_reviews`) are completed by a reviewer, discussed at the M&M meeting, and classified (expected, potentially preventable, preventable) with learning points.
- **Complaints.** `qa_complaint_links` ties a CRM complaint to an incident or action; the complaint number is shown, the text is not copied.
- **Tenancy and access.** Every row has `organization_id` (branch where it matters); a user limited to branches sees only those; the reporter sees their own reports; all else needs a `quality.*` right. Exports are a separate right.
- **Appears for staff who are not quality roles:** only "Report an incident" and "My reports" (and the documents they must acknowledge).

## Slice plan
S01 incidents (report, amend, triage, sweep of existing sources, reporter protection) · S02 investigation and CAPA · S03 infection control · S04 indicators, clinical audits, mortality reviews · S05 document control · S06 privileging, compliance calendar, accreditation · S07 committees and M&M · S08 complaint linkage · S09 dashboards, reports, API, commands, demo history, gate.

## Out of this phase (recorded as deferred)
Staff disciplinary processes (HR, never this tool) · external reporting to the regulator (hospital-specific; the national pack of Phase 19 covers aggregate counts) · a patient-facing incident disclosure letter · risk register beyond what the accreditation gaps and actions give.
