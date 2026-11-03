# D-WATCH-01 — Disease watch (Phase 26, B5)

**Status:** built 2026-11-03.

## Decisions
1. **Separate from Quality's outbreaks.** Quality's outbreak is a cluster of hospital-acquired infections on a ward. Disease watch is about what the community brings in (dengue, cholera, typhoid ...). They share no tables. Escalating a watch signal into an infection-control outbreak, where a case turns out to be hospital-acquired, is left to a person (and a future link).
2. **Counted from diagnoses already written**, by ICD-10 prefix, so doctors do nothing extra. A lab-confirmed count (lab results) is not used in this slice; a diagnosis may be provisional.
3. **A signal prompts, a person decides.** The system never reports anything by itself. Reporting is a recorded act (to whom, their reference), done once, only for a confirmed signal.
4. **The usual** is the average of the previous 8 windows of the same length, so the first weeks of a new system compare with nothing and a condition never seen raises a "first cases" signal at its minimum. This is simple on purpose; seasonality (dengue peaks after the rains) is not modelled, so the hospital should expect more signals in season and may raise the factor then.
5. **Cluster by district** uses the permanent address's district (B3). A patient with no district is counted in the hospital-wide figure but in no cluster; the overview says how many have none.
6. **The list of conditions, codes and limits are examples** (`config/watch.php`). **The health authority decides which diseases must be reported and how** (civil surgeon's office, IEDCR/DGHS); the hospital must confirm the list before relying on it. Each condition's numbers are editable and a condition can be switched off.
7. Alerts go through the existing alert service to everyone holding `watch.signal.view`; a failed alert never stops a scan.

## Open for the hospital
- Which diseases must be reported, to whom, and by what route (phone, form, DHIS2); the real thresholds; who the surveillance officer is.
- Whether the authority accepts a formal notification file from us (this slice records that it was reported, it does not send anything).

## Deferred / known limitations
- No electronic sending to IEDCR/DGHS/DHIS2; no case line-list export; no map.
- Seasonality, age/sex breakdown and laboratory confirmation are not used.
- Only the permanent address's district is used; the upazila and union are stored on the address but not yet summarised.
- Death from a watched disease is not linked to the death certificate's cause (B2) in this slice.
