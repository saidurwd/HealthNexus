# Phase 19 — S00 Inspect and map (BI, Analytics, Data Warehouse)

Guideline 26.8. Principle: **start small** — about 25 KPIs a manager and a clinical director look at, on a handful of dimensions and facts, not every table.

## What exists (reused, not copied)
| Need | Existing | Use |
|---|---|---|
| Operational data | Patients, encounters, appointments, Billing (invoices, items, charges, payments), Lab, Radiology, Pharmacy, IPD (admissions, bed allocations, beds), OT, ICU, ED, Insurance claims, Inventory stock, HR attendance, Blood bank, Portal, Interop | read-only sources of the loaders |
| Money | Finance (Phase 21) `LedgerService::trialBalance` | finance facts are **balances from Finance**, never recomputed from Billing |
| Exports | `exports` API / `ReportExportController`, `exports:prune`, Chromium PDF, XLSX | scheduled and on-demand report files |
| Alerts | `AlertService` | data-quality and stale-load alerts |
| Scheduler, module registry, permissions, Bangla parity, route matrix | Phases 0–18 | same conventions |
| Tenancy | `TenantContextResolver`, user ↔ organization/branch pivots | row-level security of the semantic layer |

## Design
- **Warehouse = a separate connection.** `config('analytics.connection')` (env `ANALYTICS_DB_CONNECTION`) names it; empty means "same database, tables prefixed `dim_` / `fact_`" (development, tests, a small hospital). With a reporting replica the connection points there and the dashboards never touch the operational tables. Loaders read the operational connection and write the warehouse connection row by row in chunks — they never join across the two.
- **The warehouse cannot identify a patient.** Dimensions and facts carry a **pseudonym key** (HMAC of the patient id with a key kept outside the database) and age band, sex and district — no name, phone, national id or free text. The operational id is not stored.
- **Loads are tracked.** `analytics_sources` (what is loaded, how, the watermark) and `analytics_loads` (one row per run: rows read/written, watermark from/to, status, error) live in the operational database so the warehouse can be dropped and rebuilt (`analytics:rebuild`). A failed source does not stop the others; the next run resumes from its watermark.
- **Dimensions:** date, branch, department, provider, patient (slowly changing, type 2: age band, sex, district, status). **Facts:** encounters, appointments, invoices and revenue lines, payments, lab orders, radiology exams, dispensings, admissions, bed census, surgeries, ICU stays, ED visits, claims, stock-out snapshot, daily counts (blood bank, portal, interoperability, HR attendance), finance balances by month.
- **Semantic layer:** facts, dimensions and measures are declared once (names, labels, allowed aggregations). Every dashboard widget, KPI and report is a *query over that model* — measures, dimensions, filters, period — compiled with bound parameters; there is no raw SQL anywhere in the builder and no column outside the model can be asked for.
- **Row-level security** is applied by the compiler, not the screen: the hospital is always the signed-in user's; a user limited to some branches sees only those unless they hold the organisation-wide right. Counts below the small-number threshold are hidden for people without the sensitive-data right.
- **KPIs are versioned data**: definition, unit, which direction is good, target, owner; changing a definition makes a new version and keeps the old.
- **Data quality rules** run after each load; a failing rule opens a visible issue.
- **Research datasets** are produced by a de-identification pipeline (pseudonyms, age bands, month-level dates, small groups suppressed, second-person approval); **the re-identification key is never in the warehouse**.
- **Cross-hospital benchmarking** compares only hospitals that opted in, shows own value against the peer median and quartiles, and needs at least three peers.
- **National reporting pack:** HMIS-style aggregates with a DHIS2 `dataValueSet` export; data element and organisation-unit codes are settings that stay empty until the national onboarding.
- **Forecasting and "ask a question" are advisory**: a simple forecast with its back-test error, and a question translated to a semantic query that is shown and can only read.

## Slice plan
S01 warehouse connection, sources, loads · S02 dimensions · S03 encounters, appointments, billing and revenue · S04 lab, radiology, pharmacy · S05 IPD, OT, ICU, ED · S06 claims, stock, daily counts, finance balances · S07 data quality · S08 KPIs and semantic layer · S09 dashboards · S10 report builder, schedules, API · S11 security, de-identification, benchmarking, national pack · S12 forecasting, questions, monitoring, gate.
