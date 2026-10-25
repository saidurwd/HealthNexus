# Phase 0–11 Conformance Audit (Step A of Phase H)

Date: 2026-10-25 · Method: read-only inspection (migrations, routes, composer, tests). First pass — evidence is counts and greps, not a line-by-line review. Items marked **verify** need a deeper look before triage.

## Findings

| # | Rule (guideline ref) | Evidence | Severity | Suggested fix |
|---|---|---|---|---|
| 1 | Tenancy words: `organization_id`, Organization→Hospital→Branch (D-02, ch. 6) | Code uses `companies` table and `company_id` in 188 migrations; 0 use `organization_id`. `hospital_id` appears in 147 migrations, `branch_id` in 377. `modules/Core/Organization` exists but no `organizations` table. | **Blocker (decision needed)** | Owner decides: (a) rename `company_id` → `organization_id` (large, risky migration) or (b) record an exception `D-E01` "company == organization" and keep the column name. Phase 12+ must follow whichever is chosen. |
| 2 | Duplicate vital stores (D-08) | `encounter_vitals`, `vital_signs`, `nursing_observations`, `icu_observations` | **Blocker** | H-03 Observation Service, read-through first. |
| 3 | Duplicate alert stores (D-09) | `nursing_alerts`, `icu_critical_alerts`, `icu_alert_escalations`, `lab_critical_result_alerts`, `pharmacy_safety_alerts`, `patient_alerts` | **Blocker** | H-04 Alert Service. |
| 4 | Charge events (D-10) | `icu_charge_events`, `ipd_bed_charge_events` plus others per module; no shared key format | **Blocker** | H-05 Charge Event Publisher. |
| 5 | Stock ledger (D-11) | `pharmacy_stock`, `pharmacy_stock_transactions`; no append-only `stock_ledger` | **Blocker** | H-06. |
| 6 | Public IDs (9.1) | 0 migrations contain `public_id`; routes use numeric ids (`admin/patients/amendments/{amendment}`) | **Blocker** (privacy/IDOR) | H-12. |
| 7 | Auth stack (D-05) | Sanctum ^4 only; no Passport | OK | None. |
| 8 | RBAC package | `spatie/laravel-permission` ^6 | OK | None. |
| 9 | Permission naming (D-06) | Seeder names are 2-segment (147) or 3-segment (377 matches incl. non-permissions); plural resource forms such as `appointments.checkin` used in routes | Fix when touched | H-01 Permission Registry; add aliases for plural/legacy names. |
| 10 | Route authorisation (6.3) | 959 routes; 219 lack `Authorize` middleware (mostly `api/v1/*`, plus `/`, notifications). Authorisation may sit in controllers/policies. | **verify** → possible Blocker | Inspect API controllers; if unguarded, add `can:` or policy checks and a route-vs-permission CI test. |
| 11 | Timezone (9.2) | `config/app.php` timezone = `UTC`. Display tz setting not checked. | Accept / verify | Confirm hospital display timezone defaults to `Asia/Dhaka`. |
| 12 | Money types (9.3) | No `float`/`double` columns found in migrations (grep) | OK (verify DECIMAL scales) | Spot-check `decimal(…,2/4)`. |
| 13 | Module anatomy (ch. 7) | Code is split across `app/` (Models, Services, Policies…) and `modules/<X>/{Http,Routes}`; no module README charters; no `docs/registry`, `docs/events.md` | Fix when touched / H-01 | Choose the code root (D-03) — current layout is hybrid: record as exception or consolidate. Create charters. |
| 14 | Scope trait / global scope (6.3) | No `HasBranchScope`/`HasOrganizationScope`-style trait found in `app/Models/Concerns`; `ApplyTenantContextToInput` middleware and `TenantContextResolver` exist | **verify** → possible Blocker | Confirm how queries are scoped; if scoping is per-query manual, T-S1…T-S8 tests are mandatory. |
| 15 | Scope tests T-S1…T-S8 (6.7) | `TenantContextInputTest`, `TenantContextResolverTest` only; no dedicated security test folder | Blocker for pilot | Add scope/authorisation matrix packs (P1/P2). |
| 16 | Localization (H-10) | `lang/bn` has only `core.php`, `patients.php`; `ar` has `core.php` | Fix in H-10 | Complete `bn` for Phases 0–11. |
| 17 | CI / static analysis (20.4) | No `.github` workflows; Larastan/Pint not in composer | Fix in H-15 | Add Pint + Larastan + test CI. |
| 18 | Test inventory | Feature folders exist for most modules (Phase0/1/2, Billing, Clinical, Laboratory, Radiology, Pharmacy, Ipd, Nursing, Ot, Icu) | OK baseline | Add concurrency + security tests in Phase H. |
| 19 | Numbering (8.3) | Per-module counter tables (`appointment_number_counters`, `clinical_number_counters`, `patient_number_counters`, `icu_counters`, …); no `count()+1` / `max(id)` found | Fix when touched | H-02 Numbering Service as the shared allocator. |
| 20 | Provider vs employee (D-12) | `providers` exists; HR not built | Plan for Phase 16 | Add nullable `employee_id` when HR arrives. |

## Table inventory
282 tables created by migrations. By prefix: nursing 27, pharmacy 26, icu 26, ot 23, billing 22, lab 20, ipd 20, encounter 20, radiology 19, patient 18, appointment 10.

## Not yet checked
Audit-table immutability, final-record immutability (lab results, signed notes, invoices), soft-delete on clinical/financial tables, transactions/after-commit events in Actions, backup/restore, seeder personal data, Blade hard-coded strings.

## Decisions needed from the owner
1. `company_id` vs `organization_id` (finding 1).
2. Code root: hybrid `app/` + `modules/` accepted, or consolidate (finding 13).
3. Priority order for H-03…H-06 given the Blockers above.
