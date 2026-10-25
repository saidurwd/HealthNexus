# D-E10 — Global Search and Barcode: state (H-09)

Done:
- `patient_search_index` (term per row: name, sound-alike, phone, NID/identifier, MRN) kept in step by observers on Patient,
  PatientIdentifier, PatientContact and PatientBranchRegistration; back-filled by the migration. Lookups are indexed exact or
  prefix matches only — no `%term%` scans.
- `GlobalSearchService`: MRN (any punctuation or just the trailing number), mobile in any format incl. Bangla digits (`01712…`,
  `+88017…`, `১৭১২…`) and while typing a prefix, NID, name in any word order (Bangla or Latin, every word must match),
  Latin sound-alike (metaphone: Mohammad ~ Muhammad). Scoped to the active organization; needs `patients.view`.
- `ScanResolver`: wristband (MRN or public id), lab specimen accession/barcode, radiology accession, drug-pack label
  `PHB|<medication code>|<batch number>`, invoice, admission, encounter numbers; each only for users holding that module's view permission.
- `GET /api/v1/search?q=`, `GET /api/v1/scan?code=`, page `GET /admin/search?q=` (a scanned code naming one record redirects straight to it),
  top-bar box with Ctrl+K / F3.
- Benchmark: `php artisan search:benchmark 100000` (scratch DB only). On MySQL with 100,000 patients / 744,172 index rows the
  worst p95 over nine query types is 48 ms (target 500 ms). That is service time, not a full HTTP round trip.

Not done:
- Bangla phonetic matching (sound-alike works for Latin names only; Bangla needs exact/prefix on the written form).
- Search of non-patient work items by free text (appointments, orders): only exact scan of their numbers.
- Confidential/VIP patients (guideline 6.6) are not yet hidden from search results.
- Scanning a real wristband, specimen label and drug pack with a physical scanner (acceptance test on site).
- The drug-pack label text `PHB|…` is a convention defined here; nothing prints it yet.
- Several scan targets still open by numeric id (specimen, order, invoice, admission) until H-12 steps 2-3.
