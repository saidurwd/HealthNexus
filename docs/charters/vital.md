# Births, Deaths and Police Cases — Module Charter

_Updated in every slice (guideline 7.4). Phase 26 (Bangladesh pack) slice B2 is built. Every period and threshold is an example for the hospital to confirm against the rules it works under._

- Phase / owner: 26 / **owner to be named by the hospital** (the records manager, with the medical superintendent for deaths and police cases)
- Status: built · switchable per organization / hospital (`vital`, depends on core and patient)
- Purpose: the hospital's own paperwork for a birth, a death and a case the police must be told about. It **notifies** a birth, **certifies** the cause of a death and **keeps** the police-case record. The government registrar (not this system) registers the birth and issues the national birth certificate.
- Permission prefix: `vital` (14 permissions). Table prefix: `vr_`. Staff screens `/vital/*`. No API in this slice.
- Owns: birth notifications (`vr_birth_notifications`), death certifications (`vr_death_certifications`), medico-legal cases (`vr_medicolegal_cases`).
- Does NOT own: patients (Patients), the mortuary and release of bodies (Facility — this module only sets the police hold on, and lifts it from, a mortuary case), the review of deaths for quality (Quality), places (the address master, B3).
- Rules:
  - **Two people at every issue.** A birth notification is written by one person and checked and issued by another. A death certificate is written by a clerk or doctor, its cause **certified by a doctor**, and then the family member is checked and the certificate issued by a person who is not the certifying doctor.
  - **Never edited once it counts.** A certified or issued certificate and an issued notification are cancelled with a reason and, if needed, written again. A patient has one live death certificate.
  - **The cause of death is a chain** (WHO form): line a is the direct cause; a line below may be given only if the one above is.
  - **Police matters.** A death that is not natural (accident, suicide, homicide, undetermined, pending investigation) opens a police case with the certificate; the certificate is **not issued to the family until the case is closed**. A case registered for a body in the mortuary puts a police hold on it; closing the case lifts the hold.
  - **A police case** is registered, the police are told (station and General Diary number needed), a post-mortem may be asked for (the case cannot be closed until it is recorded as done), then closed with a reason. A case the police are not told about within the hours the hospital allows is raised on the dashboard.
  - **Privacy.** What happened and the injuries are only for people with `vital.medicolegal.view`. Identity numbers (a family member's, a father's) are stored encrypted.
- Roles: `records_officer` (births and deaths: write, issue, record the registrar's number), `records_manager` (the same, and cancel), `medicolegal_officer` (police cases), doctors (`doctor`, `surgeon`, `intensivist`, `icu_physician`: write and certify a death, notify a birth, register a police case), `hospital_director` (see only).
- Status machines: birth draft → issued → registered (or cancelled); death draft → certified → issued (or cancelled); police case open → police_informed → postmortem_requested → closed.
- Settings keys: `vital.*` override `config/vital.php` (hours to tell the police, days before an unregistered birth is followed up, the week a stillbirth is counted from).
- Reports: the overview (deaths with no certificate, certified but not given, held by the police, police not told in time, births to follow up, births and deaths in the period, hours from death to certificate, deaths by manner).
- Known limitations & deferred items: see docs/decisions/D-VITAL-01-births-deaths-police-cases.md.

## Tables
`vr_birth_notifications`, `vr_death_certifications`, `vr_medicolegal_cases`.
