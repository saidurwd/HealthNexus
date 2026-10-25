# HealthNexus HMS — rules for AI developers

Read first: `docs/HMS_Unified_Architecture_and_Phase_Guideline.md` (HMS-GUIDE-001). If a phase spec disagrees with it, the guideline wins.
If you find a conflict or missing information, STOP and ask — never invent. Decisions that differ from the guideline are recorded in `docs/decisions/`.

## Stack
PHP 8.4+, Laravel 13, MySQL 8 (SQLite only for the fast test run), Blade + AdminLTE (Vue only for board islands), Sanctum, Pest, Pint. No package is added without asking.

## Always
1. Inspect before coding. Report the models/services/tables you will reuse. One owner per concept (guideline Appendix B). Never create a second Patient/Encounter/Billing/Observation/Alert/Stock/Provider concept.
2. One slice at a time; end with a report. Never claim tests passed unless you ran them — paste the command and result.
3. Tenancy: organization → hospital → branch → department; the column is `organization_id`. Every query scoped, every action authorised by a Policy, scope ids from the server, never from the client.
4. Business rules live in Actions/Services (`DB::transaction`, `lockForUpdate`, events after commit), not in controllers, Blade or JS.
5. Money: DECIMAL, only Billing creates charges (publish a `ChargeEventData` through `ChargeEventPublisher`, key `MODULE:visit:service:source`). Stock moves only through the Stock Ledger (`StockLedger::post`). Vitals only through `ObservationService`. Alerts only through `AlertService`. Numbers from `NumberSeriesService`. Codes from the Code System Registry.
6. Never delete or overwrite clinical or financial records: amend, reverse, version. Ledgers are append-only (a database trigger enforces it on `stock_ledger`).
7. Store UTC, display Asia/Dhaka.
8. Permissions `module.resource.action`; the prefix must be registered in `config/modules.php` (CI fails otherwise: `php artisan registry:export --check`). Export and print are separate permissions.
9. New modules: ULID `public_id` in URLs (`HasPublicId`), table prefix from the registry, a charter in `docs/charters/`, a module key in `config/modules.php`, routes behind `module:<key>`, a forward-integration gateway with a null adapter for anything not built yet.
10. UI: the seven templates (`docs/ux/screen-templates-and-acceptance.md`), `<x-patient-banner>` on clinical pages, `<x-ui.status-badge>` for statuses, text through `__('English text')` with a Bangla value in `lang/bn.json` (`php artisan lang:extract` helps; the parity test fails otherwise), plain-language labels, 1366×768 first.
11. API: `/api/v1/<module>`, envelope `{success,message,data,meta}`, 409 on state conflicts.
12. Tests with the code: scope, authorization, state machine, concurrency, idempotency, audit — as relevant.
13. Secrets never in code, logs or prompts. No real patient data in seeders, fixtures or demo data (`php artisan demo:build` makes fake data).
14. Do not edit merged migrations (except to fix a defect that stops a fresh MySQL install); add new ones. Index names over 64 characters fail on MySQL: give long ones an explicit name.
15. Do not touch other modules' tables/code; propose a separate slice instead.

## Commands
- test (fast, SQLite): `php artisan test`  ·  style: `./vendor/bin/pint` (CI runs `pint --test`)  ·  registry gate: `php artisan registry:export --check`
- MySQL check: `php artisan migrate --force` on a scratch MySQL database; CI job `mysql-migrate` does it.
- ops: `php artisan ops:check` · `php artisan pilot:readiness` · `php artisan backup:run` · `php artisan backup:restore-drill`
- data: `php artisan demo:build` · Administration → Data import · `php artisan search:benchmark`

## Test-suite state
The whole suite is green on a clean checkout. The raw-connection lock tests (`*ConcurrencyTest`) talk to the database named in `MYSQL_TEST_DATABASE` (default `healthnexus`, i.e. your dev database) and leave a few rows behind; point them at a scratch database.
