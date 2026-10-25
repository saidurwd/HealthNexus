# D-E03 — Public IDs (H-12): state

**Done (steps 1–3).** `public_id` (ULID, unique, back-filled) on `patients`, `encounters`, `appointments` and 68 further record types:
patient sub-records (alerts, allergies, amendments, consents, documents, histories, duplicate candidates), prescriptions, encounter
amendments, insurance policies, appointment documents, billing (invoices, payments, receipts, refunds, adjustments, charges,
cashier sessions), laboratory (orders, items, results, specimens, reports, critical alerts), radiology (orders, items, examinations,
reports, studies, critical findings), pharmacy (orders, dispensings, safety alerts), IPD (admissions, requests, movements,
discharge requests, leaves), nursing (episodes, assessments, care plans, discharge checklists, escalations, handovers, medication
administrations, notes, tasks, assignments, devices), OT (surgeries, requests, operative notes, pre-op assessment and checklists,
recovery, safety checklists, surgical team) and ICU (requests, assessments, critical alerts and events, devices, discharge summaries,
episodes, infusions, progress notes).

- Trait `App\Models\Concerns\HasPublicId`: assigns the ULID on create, makes it the route key, **hides the numeric `id` from JSON**, and
  gives a row that was written by raw SQL a public id the first time it is linked to.
- Migrations `2026_10_26_000006`, `2026_10_27_000001`, `2026_10_27_000002` (nullable → back-fill → unique).
- CI gate `tests/Feature/Security/PublicIdCoverageTest.php`: any model bound in a route whose table has a `patient_id` column must use
  the trait. A new module fails the build until it does. Phase 12+ tables get `public_id` from their first migration.

**Deliberately not converted:** configuration and master data (wards, rooms, beds, price lists, billing items, tests, procedures,
medications catalogue, roles, permissions, users, organizations, branches, departments) — they are not records about a person.

**Still open**
- Foreign-key columns inside JSON bodies and forms (`patient_id`, `encounter_id`, …) are numeric; form selects still post numeric ids.
  Replacing them needs API Resource classes per record plus lookups by public id in every FormRequest.
- The numeric `id` is hidden from JSON, but views and PHP code still use it internally (that is fine; it never reaches a URL).
