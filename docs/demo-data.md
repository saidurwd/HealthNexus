# Demo data: six months of history and test users for every module

Owner instruction (2026-10-26, AI-RULES 16): every module can be tested end to end with six months of history, and with every level of access. This page is how to build it and how each phase adds to it.

## Build it
| Goal | Command |
|---|---|
| History + test users on the seeded organization | `php artisan migrate:fresh --seed` then `php artisan demo:history` |
| Same, in one go | `SEED_DEMO_HISTORY=true php artisan migrate:fresh --seed` (or `php artisan db:seed --class=DemoDataSeeder`) |
| A separate training hospital with everything | `php artisan demo:build --history=6` |
| Smaller / faster | `php artisan demo:history --months=1 --visits-per-day=2` |
| Only some modules | `php artisan demo:history --only=LabHistory --only=PharmacyHistory` |

Defaults: 6 months, 8 outpatient visits per working day (Fridays off), which brings roughly 1,200 consultations, a few hundred lab and pharmacy orders, ~200 admissions, ED arrivals every day, and the invoices and payments for all of it. Every run is reproducible (same seed). It refuses `APP_ENV=production`, and refuses to build twice on the same organization (use `--force`, or a new training organization).

Everything is created **through the modules' own services** with the application clock stepped day by day, so charges, stock movements, alerts, audit rows and numbers are real, not hand-inserted. Names, phone numbers and e-mail addresses are made up (`*.example.invalid`).

## Test users
`demo:history` prints a table of logins and one password (shown once; or pass `--password=`). The e-mail domain is `<organization code>.example.invalid`.

| Login | Who | Access |
|---|---|---|
| `superuser@…` | Super user | role `super_admin`: every permission |
| `<module>.manager@…` | One per module in `config/modules.php` | every permission of that module only (`module_<key>_manager`) |
| `<module>.viewer@…` | One per module | only view / list permissions of that module (`module_<key>_viewer`) |
| `role.<job>@…` | One per business role (doctor, staff-nurse, cashier, pharmacist, lab-technician, surgeon, charge-nurse …) | exactly what that job gets from the PermissionSeeder |
| `noaccess@…` | A member with no permission | to see what a refusal looks like |

Module users are built from the registry, so a new module gets its manager and viewer automatically as soon as its prefix is in `config/modules.php`.

## What each module gets
Patients and staff · catalogues and monthly stock · price list · appointments, queue tokens and OPD consultations (vitals, diagnoses, prescriptions, lab and imaging orders; cancellations and no-shows) · emergency arrivals (all triage levels, unknown patients, resuscitation, protocols, consults, observation, every disposition) · laboratory (collection to final report, rejections, abnormal results) · radiology (scheduling to final report, critical findings) · pharmacy (FEFO dispensing, partial dispensing, verification, returns) · IPD (admissions into free beds only, transfers, daily bed charges, discharge with clearances) · nursing (vitals three times a day, medicine rounds including held and refused doses, tasks, fluid balance, pain scores, notes) · OT (scheduling, pre-op, consent, WHO checklist, counts, operative notes, recovery; postponements) · ICU (observations every 4 hours, scores, ventilation, infusions, fluid balance, daily notes and charges, discharge) · billing (invoices, partial and full payments across methods, receipts, refunds, one cashier session a day).

The most recent days are left unfinished on purpose (orders waiting, patients still in the ED, unpaid invoices), as on a real day.

## Adding your module (every phase)
1. Write `App\Services\Demo\History\Modules\<Name>History implements DemoHistoryModule` — build the history with the module's services, inside `$context->at($moment, fn …)`. Take what earlier modules left from `$context->shared()`, share what later ones need.
2. Register it in `config/demo_history.php` in dependency order (billing stays last: it bills what the others charged).
3. Add price-list items in `BillingCatalogHistory` if the module charges.
4. Add factories for new models (`database/factories/<Module>/`).
5. Add assertions to `tests/Feature/Demo/DemoHistoryTest.php` (rows exist; no double bookings; totals add up) and list the module in the phase gate report.
6. The per-module users appear by themselves; check them in `DemoAccessTest` if the module has unusual permission names.
