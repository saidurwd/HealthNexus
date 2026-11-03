# Disease Watch — Module Charter

_Updated in every slice (guideline 7.4). Phase 26 (Bangladesh pack) slice B5 is built. The list of conditions and every number are examples for the hospital to confirm with the health authority._

- Phase / owner: 26 / **owner to be named by the hospital** (the infection-control or public-health officer)
- Status: built · switchable per organization / hospital (`watch`, depends on core and patient)
- Purpose: notice, from the diagnoses doctors already write, when cases of a notifiable disease are rising or clustering in one district, so a person can look into it and report to the health authority. It is **community disease surveillance**; hospital-acquired infection clusters on a ward stay in Quality (infection control).
- Permission prefix: `watch` (5 permissions). Table prefix: `sv_`. Staff screens `/watch/*`. Command `watch:scan` daily 06:50, `watch:install`.
- Owns: the conditions watched (`sv_conditions`) and the signals raised (`sv_signals`).
- Does NOT own: diagnoses (Clinical / OPD), the place of residence (the address master, B3), hospital-acquired outbreaks (Quality).
- Rules:
  - A **case** is a distinct patient with a diagnosis (confirmed or provisional, not ruled out) whose code begins with one of the condition's prefixes, in a window of days ending on a Dhaka day. A patient with two such diagnoses in the window is one case.
  - A **signal** is raised when the cases reach the condition's minimum AND are at least the "factor" times the usual (the average of the previous 8 windows), or, when the usual is zero, at the minimum ("first cases"). A **cluster** signal is raised separately when one district (the permanent address) sends at least the cluster number. One signal per condition, district and week; scanning again never repeats it, and a signal still new grows if more cases arrive.
  - A signal is a **prompt, not a finding.** An officer takes it up (investigating), then confirms or dismisses it with a reason. Only a confirmed signal can be reported to the health authority, once (who, when and their reference are kept).
  - Privacy: no screen names a patient; the officer goes to the clinical record. Counts are totals only.
- Roles: `surveillance_officer` (see, take up, decide, report), `surveillance_manager` (the same and edit the conditions), `hospital_director` (see).
- Settings keys: `watch.*` override `config/watch.php` (earlier windows used for the usual; who the authorities are). Each condition's own numbers are edited on the Conditions screen.
- Reports: overview (signals new / being looked into / confirmed not reported, patients with a watched condition and signals in the period, patients by condition, by home district).
- Known limitations & deferred items: docs/decisions/D-WATCH-01-disease-watch.md.

## Tables
`sv_conditions`, `sv_signals`.
