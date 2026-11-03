# D-VITAL-01 — Birth notification, death certification and police cases (Phase 26, B2)

**Status:** built 2026-11-03. Guideline item P-25 (birth notification and death certification forms) plus the police-case flow beyond the flag on the mortuary case.

## Decisions
1. **A module of its own** (`vital`, tables `vr_`): the three records share the people and the privacy rules but not the owners of Patients, Facility or Quality. It reads the mortuary case and, for a police case, sets and lifts its hold - nothing else in Facility changes.
2. **We notify and certify; the registrar registers.** The system records the hospital's notification of a birth and the registrar's number once the family brings it back. It does not issue the national birth certificate.
3. **Two people.** Birth: writer ≠ checker. Death: a doctor certifies the cause; a different person checks the family member's identity and issues. Cancelling is a separate right (`records_manager`).
4. **Immutable once it counts.** There is no edit screen for a certified or issued certificate. A mistake is cancelled with a reason (kept, with who and when) and a new one is written; the patient is listed as waiting again.
5. **The cause of death follows the WHO chain** (a to d, with the time between onset and death, a free "other conditions" part, an ICD-10 code for the underlying cause). The ICD-10 code is typed, not validated against a code list in this slice.
6. **Police matters.** Manners other than natural open a police case with the certificate. The certificate is held from the family until the case is closed. A case on a body in the mortuary sets `police_case` and clears `police_cleared`; closing the case sets `police_cleared`. Facility's own release rule (a police case is held until cleared) then applies unchanged.
7. **Time to tell the police** is a setting (24 hours as an example). Whether 24 hours is the right figure is the hospital's to confirm with the law it works under.
8. **Stillbirth:** recorded with a gestation of at least 28 weeks as an example (setting). Below that the entry is refused with that reason; the hospital decides its own rule.
9. **Roles.** Doctors can write and certify a death and notify a birth; they cannot issue a certificate they certified. Seeing a police case's account of what happened needs `vital.medicolegal.view`, which doctors hold so they can document the cases they treat; clerks do not.

## Open for the hospital
- Who is the records manager and the medicolegal officer; the real hours to tell the police; the stillbirth cut-off; the follow-up period for an unregistered birth (45 days is an example).
- The wording and layout of the printed notification and certificate must be checked against the form the registrar and the Ministry accept (the print is a plain A4 page of the facts).
- Native review of the Bangla wording.

## Deferred / known limitations
- **No API** in this slice. **No link to the newborn's own patient record** (the column exists; creating the newborn's record belongs to the maternity and newborn slice, B4).
- The ICD-10 code is not checked against a code list; the cause text is free.
- A fetal death below the cut-off, a death of a person never registered as a patient (brought dead with no record), and a death certificate for someone who died outside the hospital are not covered; a brought-dead person can be registered as a patient first.
- The printed certificate carries no hospital letterhead or stamp image; a Document-Service template is the later step.
- Death and birth counts are not yet in the national-return pack (Analytics); they can be added when the return's codes are known.
