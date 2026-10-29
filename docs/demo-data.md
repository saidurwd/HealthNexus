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

**Patient logins (portal).** Staff users above open the staff screens. The patient portal has its own login, shown at the end of `demo:history`: three named patients (mobile number + the printed patient password `Portal-Demo-1`), at `/portal/login`, or `POST /api/v1/portal/auth/login` for the mobile API. The sign-in code for a new browser is in **Patient portal → Messages sent** (no text-message provider is connected, so codes wait in the outbox). Online payments use the sandbox provider, which moves no money.

## What each module gets
Patients and staff · catalogues and monthly stock · price list · appointments, queue tokens and OPD consultations (vitals, diagnoses, prescriptions, lab and imaging orders; cancellations and no-shows) · emergency arrivals (all triage levels, unknown patients, resuscitation, protocols, consults, observation, every disposition) · laboratory (collection to final report, rejections, abnormal results) · radiology (scheduling to final report, critical findings) · pharmacy (FEFO dispensing, partial dispensing, verification, returns) · IPD (admissions into free beds only, transfers, daily bed charges, discharge with clearances) · nursing (vitals three times a day, medicine rounds including held and refused doses, tasks, fluid balance, pain scores, notes) · OT (scheduling, pre-op, consent, WHO checklist, counts, operative notes, recovery; postponements) · ICU (observations every 4 hours, scores, ventilation, infusions, fluid balance, daily notes and charges, discharge) · blood bank (donors, screening, donations, testing, component bags, requests, cross-match, issue, transfusion with the bedside check, reactions, expiry, fridge excursions, a massive-transfusion protocol) · stores and purchasing (suppliers with documents and scorecards, opening requisitions, quotations, purchase orders through approval by amount, partial deliveries with inspection and rejects, supplier invoices that match and some that do not, goods sent back, department requests and issues, transfers to the ward and theatre stores, patient-linked use charged through Billing, monthly counts, expiry write-offs, one hold, one recall, two emergency orders — one approved afterwards, one still waiting) · people and payroll (the register with encrypted private details, contracts, licences, department heads who approve their team's leave, a duty roster every day, device punches with late arrivals, a forgotten punch-out, an offline device that sends its backlog, a device number nobody was matched to until HR mapped it, attendance and corrections, leave requests decided by managers and HR, overtime, hires through a vacancy, leavers through offboarding with their logins switched off, provider↔employee suggestions confirmed on the review screen, and a payroll run each month: second-person approval, payslips, bank and wallet files, the journal; one month with a festival bonus and a later correction run) · patient portal and relations (about half of the patients seen join the portal, some through an invitation code at the front desk; new reports get a release decision and a clinician releases or keeps back — a critical result waits for its acknowledged alert; messages with a few urgent ones and staff replies; refill requests; online payments of open bills through the sandbox provider with a monthly settlement file that has one amount that differs and one payment the file forgot; visit ratings, forms, complaints worked to the end with two left late; parents approved for children; contacts logged by the relations desk; groups approved by a second person and monthly campaigns that reach only those who agreed; a few visits booked online for the coming two weeks) · connections to other systems (a partner hospital, a reference laboratory, a scanner, an insurer's notices and the national test exchange; about four in ten patients agree to share; registrations, finished inpatient visits and final lab reports are queued and sent, retried through a three-day outage, some dead-lettered and half of those sent again by a person; inbound admissions and lab results, some for unknown patients, some repeated, results waiting for review; the partner hospital reads records and is refused those who did not agree; scanner worklist entries; some records shared with the sandbox national exchange) · billing (invoices, partial and full payments across methods, receipts, refunds, one cashier session a day) · insurance (payers of every kind with plans, rules and contract rates, policies with dependents, claims through submission, queries, denials, appeals, remittances, write-offs, second policies, corporate statements).

The most recent days are left unfinished on purpose (orders waiting, patients still in the ED, unpaid invoices), as on a real day.

## Adding your module (every phase)
1. Write `App\Services\Demo\History\Modules\<Name>History implements DemoHistoryModule` — build the history with the module's services, inside `$context->at($moment, fn …)`. Take what earlier modules left from `$context->shared()`, share what later ones need.
2. Register it in `config/demo_history.php` in dependency order (billing stays last: it bills what the others charged).
3. Add price-list items in `BillingCatalogHistory` if the module charges.
4. Add factories for new models (`database/factories/<Module>/`).
5. Add assertions to `tests/Feature/Demo/DemoHistoryTest.php` (rows exist; no double bookings; totals add up) and list the module in the phase gate report.
6. The per-module users appear by themselves; check them in `DemoAccessTest` if the module has unusual permission names.
