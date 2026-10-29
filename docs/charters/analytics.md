# BI / Analytics — Module Charter

_Updated in every slice (guideline 7.4). Phase 19 slices S00–S12 are built. Targets, small-count threshold, k-anonymity and the national codes are examples awaiting the hospital's decisions._

- Phase / owner: 19 / **owner to be named by the hospital** (the medical director's office with the head of IT)
- Status: built · switchable per organization / hospital (`analytics`, depends on core); the warehouse is loaded by the scheduler
- Purpose: one place for the hospital's figures — a warehouse of dimensions and facts loaded from the operational tables, a semantic layer people query without SQL, a KPI catalogue with targets, role dashboards, reports with schedules, de-identified research datasets, anonymous benchmarking, a national reporting pack and advisory forecasts.
- Permission prefix: `analytics` (26 permissions). Table prefixes: `analytics_` (operational database), `dim_` / `fact_` (warehouse). Staff screens `/admin/analytics/*`; read-only API `/api/v1/analytics/*`.
- Owns: the warehouse tables, load sources and runs, KPI definitions/versions/targets, dashboards, saved reports, schedules and deliveries, data-quality rules and issues, research datasets, forecasts, benchmark opt-ins.
- Does NOT own: any operational record. Loaders only read other modules' tables; nothing here changes clinical, billing or stock data.
- Rules: the patient is a pseudonym (HMAC) in the warehouse — no name, phone, id, address or date of birth; queries are compiled from catalogue names only with bound values and the user's organization and branch applied by the compiler; counts under the small-cell threshold are hidden without `analytics.sensitive.view`; a research dataset needs a second approver and drops groups smaller than k; benchmarking needs opt-in and at least 3 peers and never names a hospital; scheduled mail goes to staff only and uses the owner's rights at run time; forecasts are advisory.
- Loading: incremental with a watermark moved only after a successful run (two-hour overlap), snapshot loaders rebuilt each run; a failed source does not stop the others; `analytics:rebuild` empties and reloads.
- Hooks: `analytics:load` every 15 minutes, `analytics:check` hourly, `analytics:run-schedules` every 5 minutes, `analytics:forecast` daily 05:30. Also `analytics:install`, `analytics:benchmark`.
- Settings keys: `analytics.*` override `config/analytics.php`.
- Reports / KPIs: 25 standard KPIs, 5 standard dashboards (CEO, medical director, finance, nursing, pharmacy), 13 data-quality rules, CSV and PDF reports, DHIS2 `dataValueSet` export once codes are set.
- Known limitations & deferred items: see docs/decisions/D-P19-analytics-state.md.

## Tables


