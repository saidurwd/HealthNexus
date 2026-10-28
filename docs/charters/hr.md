# HR / Payroll — Module Charter

_Updated in every slice (guideline 7.4). Phase 16 slices S00–S12 are built; every rate, slab, cap, entitlement and rule is an example awaiting the HR manager, the accountant and a lawyer (OD-16)._

- Phase / owner: 16 / **owner to be named by the hospital** (HR manager and payroll manager)
- Status: built · switchable per organization / hospital (`hr`, depends on core)
- Purpose: keep the register of people employed, their jobs, contracts and licences; plan duty and record attendance from devices; manage leave and overtime; and pay people correctly, reproducibly and with a second pair of eyes — payslips, bank files and the accounting journal.
- Permission prefixes: `hr`, `attendance`, `leave`, `payroll`, `compensation` (see docs/registry/permissions.md). Table prefix: `hr_`. Routes: `/admin/hr/*`. API: none yet.
- Owns: the employee master and its history, jobs and positions, contracts, documents, licences (credentials), onboarding / offboarding checklists, recruitment (vacancies, candidates), the provider↔employee link review, shifts, the roster and staffing needs, devices and raw punches, attendance days and corrections, leave types / ledger / requests, overtime, pay components, salary structures (versions), loans, dependents, tax slabs, payroll periods / runs / lines / adjustments, payment batches, appraisals and training.
- Does NOT own: users and departments (core — referenced by id); providers' availability (Appointment — `providers.employee_id` is the only column HR adds, and HR is master for name, licence and status); the holiday calendar (core `hospital_holidays`, read here); the general ledger (the payroll journal goes through `FinanceGateway::postJournal`, null adapter until Phase 21); Nursing's own shift table (Phase 9).
- Privacy (class C3/C4): national id, tax id, bank account and wallet number are stored encrypted, hidden from JSON, shown masked and only to `hr.employee.sensitive`; salary, structures, payroll and payslips only to the `payroll.*` / `compensation.*` permissions; payment files decrypt account numbers behind `payroll.payment.file` and every download is audited; an employee reaches only their own payslips (self-service is tied to the login).
- Provider link (D-12): proposed by login, licence number and name; never automatic; ambiguous cases are marked; confirming one is a person's decision on a review screen.
- Payroll rules: pay is the structure in force on the first day of employment in the month, divided over the calendar days; days not employed, absent days, unpaid leave and half of a half-day are not paid; PF is a share of basic; overtime is basic / 208 per hour times a multiplier; tax is the monthly share of the annual tax on the month's taxable earnings from effective-dated slabs. All of it is configuration. A run is calculated as often as needed while a draft (same inputs → same fingerprint), approved by a different person, and then LOCKS attendance, overtime, loans and adjustments; later corrections are adjustments paid through a supplementary run.
- Reconciliation: lines = run totals, items = line totals, gross − deductions − tax = net, the journal balances, payment files = payable net — checked on every run screen and in tests.
- Hooks: `hr:check` nightly (01:30) pulls devices through `BiometricGateway` (null adapter), works out yesterday's attendance, accrues leave, marks and warns about lapsing licences.
- Settings keys: `hr.*` override `config/hr.php` (weekly off, licence policy, roster limits, attendance rules, leave types, overtime caps and multipliers, provident fund, tax slabs, journal accounts).
- Reports: dashboard, headcount, joiners and leavers, absence, licence compliance, overtime, payroll cost; CSV export with formula-safe cells.
- Known limitations & deferred items: see docs/decisions/D-P16-hr-state.md.

## Tables


