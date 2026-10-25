# D-E02 — Legacy number generators (exception to H-02)

The shared `NumberSeriesService` (tables `number_series`, `number_allocations`) is the only allocator for new
numbers. **Billing** (invoice, payment, receipt, refund, adjustment) already uses it; its old counters were
carried over by migration `2026_10_26_000004`.

These generators still use their own locked counter table. They are safe under concurrency (row lock inside a
transaction) but do not write `number_allocations`. Move each one the next time its module is touched, copying
the counter into `number_series` in the same migration so numbers continue:

| Generator | Counter table |
|---|---|
| PatientNumberGenerator (MRN) | patient_number_counters |
| AppointmentNumberGenerator | appointment_number_counters |
| ClinicalNumberGenerator, EncounterService | clinical_number_counters, encounter counters |
| LabNumberGenerator | lab_counters |
| RadiologyNumberGenerator | radiology_counters |
| PharmacyNumberGenerator | pharmacy_counters |
| IpdNumberGenerator | ipd_counters |
| OtNumberGenerator | ot_counters |
| IcuNumberGenerator | icu_counters |

New modules (Phase 12+) must use `NumberSeriesService` directly.
