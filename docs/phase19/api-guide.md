# Analytics — guide for the analyst and for IT

## Start
1. `php artisan analytics:install` adds the 25 standard KPIs, 5 dashboards and the data-quality rules (idempotent).
2. `php artisan analytics:load` (scheduled every 15 minutes) fills the warehouse; first load covers `analytics.load.first_load_months`. **Analytics → Warehouse loads** shows every run; *Rebuild* empties and reloads.
3. Optional: set `ANALYTICS_DB_CONNECTION` to a reporting replica so dashboards stay off the operational database.

## Every day
- **Dashboards** per role; **KPIs and targets** to set the hospital's own targets; **Explore** to cut any figure by dimension and save it as a report; **Reports** to schedule e-mail to staff (CSV or PDF); **Data quality** lists failing rules; **Ask a question** turns plain English into a query you can check.
- **Research datasets**: request → a second person approves → the file is made. **Compare with other hospitals** needs opt-in. **National pack**: set the national codes first.

## Read-only API
`GET /api/v1/analytics/catalog`, `GET /api/v1/analytics/kpis`, `POST /api/v1/analytics/query` with a staff token and header `X-Organization-Id` (the user must belong to it). Answer: `{success, message, data, meta}`; `meta` carries the compiled SQL and whether small counts were hidden. Permissions: `analytics.query.run` for the query, `analytics.kpi.view` for KPIs.

## Commands
`analytics:install` · `analytics:load [--only=] [--days=]` · `analytics:rebuild` · `analytics:check` · `analytics:run-schedules` · `analytics:forecast` · `analytics:benchmark`.
