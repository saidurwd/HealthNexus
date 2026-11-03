# D-E12 — Import Service and demo data: state (H-11)

## Done
- Staged import: upload → validate → preview → commit → error file → rollback of a whole batch
  (`import_batches`, `import_rows`; screens at Administration → Data import; queued jobs `ValidateImportBatch`, `CommitImportBatch`).
- Seven importers (CSV, UTF-8, comma / semicolon / tab, Bangla digits and `d/m/Y` or `Y-m-d` dates accepted): patients, services & prices,
  drugs, lab tests, beds, staff accounts, opening pharmacy stock. Template download for each.
- Duplicate detection: inside the file (same national ID, or same name + birth date + mobile; same code for catalogues) and against existing
  records. Duplicates are skipped, never merged silently, and listed in the downloadable error file with the reason.
- Per-row failures during commit do not stop the batch. Nothing is written before the commit step.
- Rollback: unused records are removed; records already in use are deactivated or kept (patients with appointments/encounters/prescriptions/invoices
  are kept); opening stock is taken back out through the stock service (ledger row), the batch record stays for history.
- Staff accounts are created INACTIVE with an unknown random password; no credential is written anywhere.
- `php artisan demo:build [--patients=300] [--password=…] [--retire-old]` creates a NEW training organization (never deletes), with fake
  Bangla/English patients, price list, drugs with stock, lab tests, wards/rooms/beds and inactive staff, all through the importers.
  Refuses to run in production unless `--allow-production`.
- Measured on SQLite in the test run: 5,040-row patient file (5,000 + 40 repeats, 100 missing national IDs, 20 bad phones) validated and
  imported in about 118 s (about 23 ms/row), 4,980 imported, 40 duplicates, 20 errors. Run with `IMPORT_LOAD_ROWS=5000`.

## Not done
- XLSX files (needs a package; same decision as H-07). CSV only.
- Import of price lists per branch/insurer, departments, providers and the other catalogues of guideline H-11's list beyond the seven above.
- Merging a duplicate into the existing record from the import screen (use the Patient duplicate-review queue afterwards).
- The upload is processed by the queue worker; the screen needs a manual refresh (no live progress bar).
- Speed: about 2 minutes per 5,000 patients is acceptable for a queued job, but patient creation does a lot per row (number series,
  timeline, search index, events). Batch the search-index writes if hospitals import 50,000+ patients.
- Fake phone numbers in demo data are ordinary-looking Bangladeshi mobile numbers (there is no reserved test range). Do not send SMS from a
  training organization (training mode is H-15).
