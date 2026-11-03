# D-E04 — Observation Service: migration state (H-03)

Step 1 (done): `clinical_observations` + `clinical_observation_sets`, `ObservationService` (record, recordPanel,
amend, markEnteredInError), `ObservationReader` (series, latest, latestVitals), event `ObservationRecorded`,
catalogue in `config/observations.php` (LOINC codes, UCUM units, plausibility limits).

Reader covers: new store, `encounter_vitals`, `vital_signs`, `nursing_observations`, `icu_observations`.
Nursing/ICU rows are matched by their free-text `observation_type` through the `aliases` in the catalogue;
types not listed there (blood glucose, pain, ...) are not visible to the reader until added.

Writers NOT yet switched (steps 2-4 of guideline 10.4): OPD vitals, Nursing, ICU. Phase 12+ must write through
`ObservationService` only. No data has been moved.

Not verified: the reader's UNION over mixed column types has run on SQLite only. Run
`ObservationServiceTest` against MySQL before relying on it.

Open: `ed_monitoring_records` (Phase 12) must not store vital values (guideline 26.1 override 1).
