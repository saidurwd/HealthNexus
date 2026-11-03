# HealthNexus HMS

Hospital Management System for hospitals in Bangladesh — Laravel, MySQL, Blade + AdminLTE, English and Bangla.
The architecture and the phase plan are in `docs/HMS_Unified_Architecture_and_Phase_Guideline.md`; rules for people and AI assistants in `docs/AI-RULES.md`.

## Fresh install (development)

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# create an empty MySQL 8 database, then set DB_CONNECTION=mysql, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan migrate --force          # about 300 tables; takes a few minutes the first time
php artisan db:seed --force          # permissions, roles, settings, code systems, master data
php artisan demo:build               # a fake training hospital; prints a one-time sign-in
npm run build && php artisan serve
```

Background work (needs the scheduler and a queue worker): `* * * * * php artisan schedule:run` in cron, and `php artisan queue:work`.

## Checks before you push
```bash
./vendor/bin/pint --test             # style
php artisan registry:export --check  # every permission/table belongs to a module in config/modules.php
php artisan test                     # fast run on SQLite
```
CI (`.github/workflows/ci.yml`) also migrates from scratch on MySQL, builds the training organization and runs a backup + restore drill.

## Going live
`php artisan pilot:readiness` (technical go/no-go), `docs/pilot/` (runbook, security baseline and penetration-test booking, go/no-go sheet, support sheet, manual skeleton), `php artisan ops:check` for monitoring.

## Where things are
| | |
|---|---|
| Modules, switches, dependencies | `config/modules.php`, generated `docs/registry/`, `docs/charters/`, `docs/events.md` |
| Decisions that differ from the guideline | `docs/decisions/D-E*.md` |
| Shared services | numbering, observations, alerts, charge events, stock ledger, code systems, documents/print, exports, imports, search, backup/ops under `app/Services/*` |
| UX standard | `docs/ux/screen-templates-and-acceptance.md`, `config/ux.php` |
