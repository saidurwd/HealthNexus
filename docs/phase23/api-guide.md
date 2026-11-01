# Quality and safety — guide for the quality team and for IT

## Start
1. `php artisan quality:install` adds the standard indicators and, for each hospital, three committees (patient safety, infection prevention and control, morbidity and mortality), an example privilege list and an **example** checklist. Edit them: nothing here is an accreditor's text. Idempotent.
2. `php artisan quality:check` (scheduled daily at 06:00) sweeps events other modules recorded into the incident list, opens a review for each death, works out the indicators and warns about what is late. Run it by hand with `--days=N` to look back only N days.
3. Give people the roles: every staff member can already report an incident and read documents; `quality_officer`, `infection_control_officer`, `clinical_auditor`, `medical_director`, `credentialing_officer` and `quality_manager` are ready-made. Only `quality_manager` (and the super user) may show a reporter's name.

## Every day
- **Overview**: open and late incidents and actions, serious events in 90 days, outbreaks, deaths waiting for review, documents waiting for approval, licences due, and the indicators against their targets.
- **Incidents**: review (harm after review, owner, due date) → investigate (serious ones) → actions → close. Corrections are amendments. **My reports** lists the reports a person sent *with* their name.
- **Actions**: owners finish theirs (say what was done); a second person checks it worked.
- **Infection control**: record infections, confirm or rule out; isolation precautions; weekly hand-hygiene rounds; an outbreak opens by itself at the set number of cases on one ward.
- **Documents**: write → send for review → approve (someone else) → assign who must read → see who has read.
- **Committees**: schedule, add cases to the agenda, hold, record the outcome of each (an action if needed), write the minutes.

## Read-only API
`GET /api/v1/quality/{overview|indicators|incidents|actions}` with a staff token (`Authorization: Bearer ...`) and the header `X-Organization-Id: <hospital id>` (the user must belong to it). Answer: `{success, message, data, meta}`. Permissions: `quality.dashboard.view`, `quality.indicator.view`, `quality.incident.view`, `quality.action.view`. The incident list shows number, kind, category, harm, status, dates and whether it was anonymous — **never a reporter**. Lists follow the user's branches unless they hold `quality.incident.triage` or `quality.dashboard.view`.

## Commands
`quality:install [--organization=ID]` · `quality:check [--days=N]` · `quality:indicators [--months=N]`.
