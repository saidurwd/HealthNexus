# P16-S00 — Inspect & Map: HR, Workforce and Payroll

Read-only report (guideline 26.5). Built on the owner's standing instruction to continue phase by phase: the 112-section `Phase 16 — HR - Workforce - Payroll` spec is not in the repository, so every payroll rate, tax slab, leave entitlement, overtime rule and cap below is a **configurable example** in `config/hr.php` (overridable by `hr.*` settings, and by effective-dated rows for tax slabs and salary structures) for the HR manager, accountant and a lawyer to confirm (OD-16).

## What already exists (reused, not copied)
| Need | Owner | Note |
|---|---|---|
| Users, roles, permissions, organizations, branches, departments | core | Referenced by id; `hr_employees.user_id` links a login (unique), `users` is not touched |
| Providers | Phase 2 `providers` | **Had no `employee_id` column** (the guideline said it would); HR adds it (nullable, plain column) and the review screen back-fills it. `Provider::$fillable` gained `employee_id` |
| Holiday calendar | core `hospital_holidays` | Read by `WorkCalendar` (branch / department scoped, recurring) |
| Shifts | Phase 9 `nursing_shifts` | Left alone: it serves nursing rounds. HR has its own `hr_shifts` with night shifts, breaks and grace |
| Alerts, numbers, settings, audit, public ids | `AlertService`, `NumberSeriesService`, `SettingsService`, `AuditLogger`, `HasPublicId` | |
| Journals | `FinanceGateway::postJournal` (null) | Called after approval; the balanced journal is kept on the run |
| Biometric devices | none | New `BiometricGateway` contract + null adapter, registered in `config/integrations.php` |
| Module flag | `config/modules.php` key `hr` (tables `hr_*`, permissions `hr`, `attendance`, `leave`, `payroll`, `compensation`) | Registered earlier as unbuilt, with 5 legacy two-part permissions (`hr.view` …) kept |

## Decisions (see D-P16)
- The employee is the master; a provider links to it only through a human-confirmed review.
- Raw punches are stored untouched and idempotent; attendance days are derived, can be recomputed, and are locked by payroll approval.
- Leave balances are the sum of an append-only ledger; accrual, carry-forward and lapse are re-runnable jobs.
- Salary structures and contracts are versions, never edits.

## Questions for the owner (not blocking)
1. Leave entitlements and rules per category of staff; who may approve what.
2. Overtime rates, daily and monthly caps; which categories are eligible.
3. Provident fund, festival bonus, tax slabs and rebates; the accountant's treatment of arrears and final settlement.
4. Which attendance devices are used and how they export (to write the real `BiometricGateway` adapter).
5. Whether a lapsed licence should stop duty scheduling (the policy exists and is off).
