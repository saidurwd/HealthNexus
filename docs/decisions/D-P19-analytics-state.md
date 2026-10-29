# D-P19 — BI / Analytics: state and decisions

Status at the end of the build: see `docs/phase19/gate-f2-report.md`. The guideline's Phase 19 section and slices S00–S12 were the brief.

## Decisions
1. **A warehouse of its own tables, read only by dashboards.** `dim_*` and `fact_*` tables are created on `config('analytics.connection')` (empty = the application's own database). Loaders read operational tables and write the warehouse in chunks; they never join across the two connections. Load bookkeeping stays in the operational database so the warehouse can be dropped and rebuilt.
2. **Incremental, safe loads.** The watermark moves only after a successful run, with a two-hour overlap; writes are upserts, so a repeated run changes nothing. Snapshot loaders (bed census, daily metrics, stock, ledger) rebuild a window each run.
3. **The patient is a pseudonym.** HMAC of the patient id with the application key (or `ANALYTICS_PSEUDONYM_KEY`). No identifying column exists in any dim/fact table (tested).
4. **Queries are compiled, never written.** The semantic layer accepts only catalogue facts, dimensions, measures and operators; values are bound; the compiler adds the scope's organization and branch. Small counts are hidden without `analytics.sensitive.view`.
5. **KPIs are versioned data.** A change makes a new version with a reason; a hospital may set targets or define its own KPIs; only standard KPIs are benchmarked.
6. **Research data is de-identified and approved by a second person.** Per-dataset reference salt, dates reduced to the month, k-anonymity suppression, file kept on the private disk.
7. **Benchmarking never names a hospital**: opt-in, at least 3 peers, quartiles only.
8. **Forecasts are advisory**: a weekly-pattern average with a back-test error shown next to it. No ordering, pricing or staffing is decided from it.
9. **Questions in plain English** are a keyword mapper into the same query format, shown to the user to check; it reads only.

## Open decisions
- **Replica or same server?** The warehouse shares the hospital database until the hospital decides to point `ANALYTICS_DB_CONNECTION` at a reporting replica. Nothing else changes.
- **National codes**: the DHIS2 data-element and organisation-unit codes are empty until onboarding with the national system; the export refuses to run without them.

## Known limitations / deferred
- A user limited to one branch does not see org-level (branch 0) daily metrics.
- Bed totals in the census are today's count of active beds (the operational tables keep no history of bed numbers).
- The question service is keyword based, not a language model (Phase 20 may replace it behind the same query format).
- Bangla wording of the new strings needs native review.
