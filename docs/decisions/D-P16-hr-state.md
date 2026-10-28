# D-P16 — HR, Workforce and Payroll: state at the end of the Phase 16 build

Date: 2026-11-20. Slices S01–S12 of guideline §26.5 are built and tested. This is not a sign-off by the HR manager, the accountant or a lawyer.

## What is built
Employee register with numbering, history and encrypted private fields; jobs and positions with headcount; contracts (superseded, never edited); documents; licences with verification, expiry sweep and alerts, and an optional policy that refuses duty to a lapsed licence; onboarding and offboarding checklists (finishing offboarding switches the login, roles, tokens and provider record off); recruitment (vacancy → candidates → hire into onboarding); the provider↔employee review screen (D-12); shifts, duty roster with conflict detection (overlap, rest, weekly hours, days in a row, leave, licence), staffing needs and shortfalls, swaps; devices, idempotent raw punches, unmapped codes, attendance days (late, early out, half day, absent, leave, holiday, weekly off, overtime) recomputed on demand, corrections decided by someone else; leave types, an append-only balance ledger, requests with manager and HR approval, accrual and year roll; overtime with daily and monthly caps; pay components, salary-structure versions, loans and advances, dependents, effective-dated tax slabs; payroll periods and runs (calculate repeatedly while a draft, second-person approval, lock, supplementary runs for corrections, exceptions dashboard, reconciliation); payslips with self-service access to one's own; bank and mobile-wallet payment files; the accounting journal through FinanceGateway; appraisals and training; dashboards and reports; the register backfill from existing logins; screens, menu, Bangla; factories; six months of demo history for ~110 people and their logins.

## Decisions made here
- **Pay uses the structure in force on the first day the person was employed in the month.** A raise that starts mid-month takes effect with the next payroll month; the difference is paid as an adjustment. This keeps a month reproducible from one structure.
- **A closed month cannot be rewritten**: a new salary structure may not start inside a month already paid, leave cannot be requested into it, attendance corrections cannot touch it.
- **Half a day absent costs half a day's pay** until a correction says otherwise; weekly off and holidays are paid.
- **Supplementary runs withhold no tax**: the arrears are taxed in the accountant's annual true-up (deferred).
- **Salary visibility is by permission, not by role name**; the employee page shows the structure only to `compensation.structure.view`, masks private numbers for everyone, and decrypts account numbers only inside the payment-file service.
- **Dates use `date:Y-m-d` casts** so SQLite and MySQL compare the same.

## Needs the HR manager / accountant / lawyer before use (OD-16)
Every rate and rule in `config/hr.php`: leave entitlements, overtime multipliers and caps, provident fund, tax slabs and rebates, festival bonus treatment, final settlement, minimum rest and weekly hours, and who approves leave, overtime, loans and payroll.

## Deferred
- A real `BiometricGateway` adapter for the hospital's devices; mobile attendance with geofence; shift bidding.
- Final settlement and gratuity; leave encashment posting (the ledger entry type exists); arrears calculators; annual tax true-up and the tax return files; provident fund fund-account statements.
- Salary structures per grade / templates; mid-month structure splits.
- Employee document upload to the File Service (documents record metadata only); e-signature of contracts; ESS profile edits with HR approval.
- 360° appraisals, skills matrix, training budgets; recruitment pipelines with scoring and interview scheduling.
- HR API endpoints (`/api/v1/hr`, `/api/v1/payroll`, `/api/v1/leave`); real-user and load testing; native Bangla review.
- The duty roster does not yet feed the Nursing module's own shifts.
