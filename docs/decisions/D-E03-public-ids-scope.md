# D-E03 — Public IDs: what H-12 covers so far

Done (migration `2026_10_26_000006`): `public_id` (ULID, unique, back-filled) on `patients`, `encounters`,
`appointments`. Trait `App\Models\Concerns\HasPublicId` assigns it on create and makes it the route key, so every
`/admin/...` and `/api/v1/...` path for these three records uses the ULID; the numeric id returns 404.

Still to do, in this order (add the trait + a migration like the one above, then fix tests that build URLs):
1. Records reached by path from a patient/encounter: patient documents, alerts, allergies, history, identifiers;
   encounter notes, prescriptions, orders, amendments.
2. Admission (`ipd_admissions`), surgery (`ot_surgeries`), ICU episode, lab order, radiology order, invoice, payment.
3. Numeric ids still appear in JSON bodies (`id`, `patient_id`) and in form/query inputs such as
   `?patient_id=`. Hiding them needs API Resource classes per record; do it module by module.

New modules (Phase 12+) must create `public_id` from the start.
