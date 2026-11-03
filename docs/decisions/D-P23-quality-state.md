# D-P23 — Quality, Safety and Compliance: state and decisions

Status at the end of the build: see `docs/phase23/gate-f2-report.md`. The guideline's section 27.3 and the slice table (S00–S09) were the brief. Every number and list (harm levels, due days, thresholds, targets, the starter checklist and privilege list) is an example for the hospital's quality committee to confirm.

## Decisions
1. **An incident is a frozen report plus a trail.** What was reported never changes after saving: the model refuses it and database triggers refuse it on SQLite and MySQL (by the application or by raw SQL), and an incident cannot be deleted. Workflow fields (status, harm after review, owner, due date, closure) are separate columns. A correction is an amendment row (field, old, new, reason, who); the latest amendment of a field is what the screens show, the original stays visible underneath. Amendment, event and reporter tables only grow (triggers).
2. **The reporter is not on the incident.** `qa_incident_reporters` holds a user only when the reporter chose to be named. Showing it needs `quality.incident.reveal` (given only to the quality manager and the super user), a written reason, and is recorded on the incident's trail and in the audit log. An anonymous report has no reporter row, writes **no audit entry naming a user**, and its first event has no user. *Limit: the web server's own access log and the database's transaction time can still link a report to a session; the application does not add to that.*
3. **No punitive use is built in.** There is no "person at fault" field; contributing causes are systems categories (communication, process, equipment, staffing, environment, training, patient factors, documentation); the death review and investigation texts are labelled as being about the care system. No HR disciplinary link exists; it must not be added.
4. **Serious incidents need an investigation a second person accepts** before they can be closed (kind sentinel, or harm after review at or above the set level). Sentinel kind is set when the reported harm is catastrophic; the quality team is alerted immediately for sentinel and major harm.
5. **Actions (CAPA) serve every source** — incidents, audits, accreditation gaps, death reviews, meetings — with an owner, a due date, evidence of what was done and a check by a second person that it worked; an action that did not work returns to its owner with the reason. Overdue actions are alerted once to the owner and to the managers.
6. **Other modules are read, never changed.** A daily sweep (`quality:check`) brings blood-bank incidents (which already include transfusion reactions), severe medicine safety alerts that were overridden and surgical-safety checklist phases not completed for a finished operation into the list — one quality incident per source row (unique key), flagged as found by the system. Deaths are found from emergency dispositions and ward admissions; each gets one review.
7. **Indicators are computed, kept with numerator and denominator, and never show zero for "no data".** Volumes (admissions, bed-days, dispensings) come from the Phase 19 warehouse through the `Warehouse` service; events come from this module's tables; "repeat operation within 30 days" reads the operations table because the warehouse keeps no patient on operations (it counts staged operations too and says so). A hospital sets its own target by making its own copy; the standard indicator is never edited.
8. **Documents:** a version is drafted, sent for review and approved by someone other than its author; one approved version is in force; an approved version's text is frozen (model and trigger); staff assigned by everyone, role, department or person acknowledge the version in force and are asked again for a new one; reviews are due on a schedule and the owner is warned.
9. **Privileges:** requested by one person, granted by another (never the clinician themself), only while the required HR licence is valid, never beyond the licence's expiry; they lapse by date and are suspended if the licence stops being valid. Other screens ask `PrivilegeService::holds()`.
10. **No accrediting body's text is bundled.** The starter checklist and privilege list are plain examples written for this product; accreditation gaps must have an action before an assessment can be completed.
11. **Complaints stay in patient relations.** Raising one as an incident copies only its number (and category/patient link), never the text.

## Open for the hospital
- Who owns quality (the charter names the roles; the hospital names the people), and which harm level starts an investigation.
- Whether anonymous reporting is allowed (on by default; `quality.incident.allow_anonymous`).
- Replace the example checklist with the actual licensing / accreditation requirements; confirm the privilege list and licence types.
- Native review of the Bangla wording (about 490 new strings).

## Deferred / known limitations
- **Isolation flags are not shown on other modules' screens** (rule 15): `InfectionService::activePrecautions()` is the call; showing a banner on the patient banner, bed board and ward round is a separate slice in IPD/Nursing.
- Hospital-acquired day is counted from the admission date only (a transfer from another hospital is not known).
- The question "was this a repeat operation planned in stages?" is for a person to answer; the indicator cannot tell.
- A named reporter cannot be anonymised after the fact, and an anonymous reporter cannot amend (nobody knows who they are).
- External reporting to the regulator is hospital-specific and not built; aggregate counts are covered by the Phase 19 national pack.
- Document files (PDF/Word) are not attached: documents are text. Attachments need the file service and a virus-scan decision.
- No staff disciplinary workflow, by design.
