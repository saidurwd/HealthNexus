# Gate H report — Standardization Sprint (guideline 24.3)

Written 2026-10-25 at the end of the build. "Done" means built, tested in the fast suite, and (where it matters) tried on MySQL.
"Partly" says exactly what is missing. Nothing here has been used by real hospital staff yet.

Test suite: **all green** (`php artisan test`). `pint --test` passes. `registry:export --check` passes.

## Gate checklist
| Gate item | Status |
|---|---|
| Audit closed, no Blocker open | **Partly.** Closed: tenancy naming (organization + hospital layer), unguarded API routes, MySQL index-name defect, test failures. The four duplicate stores (vitals, alerts, charges, stock) each have the shared service plus read-through/adapters, but the old writers are not switched over (decisions D-E04…D-E07). Public IDs cover only patients, encounters and appointments (D-E03). |
| H-01…H-15 done-when conditions | See table below. |
| The 15 critical journeys pass in CI | **Not done.** Individual pieces are tested (registration, booking, OPD, billing, lab, radiology, pharmacy, IPD, nursing, OT, ICU, refund) but there is no end-to-end journey suite, and the Emergency journey cannot exist before Phase 12. |
| Scope pack P1 and authorization matrix P2 for ALL routes | **Partly.** `RouteAuthorizationMatrixTest`: no parameterless route answers 200 to a signed-out visitor or to a member with no permission (294 assertions). Routes with `{ids}` rely on the per-module tenant-isolation tests; there is no automatic matrix for them. |
| Fresh install from README on an empty machine | **Partly.** README steps written; CI job `mysql-migrate` runs migrate + seed + `demo:build` + backup/restore on MySQL 8. The workflow has not been run on GitHub yet. |
| Restore drill performed; downtime forms printed | **Partly.** Restore drill passed on a MariaDB scratch database (311 tables, encrypted, scratch DB dropped) and is logged in `backup_runs`. Not done on the hospital's server hardware, and nobody has signed a real runbook entry. Forms exist in Bangla/English; printing and drilling them is a people task. |
| CLAUDE.md contains the AI rules; module charters exist for Phases 0–11 | **Partly.** `CLAUDE.md` points to `docs/AI-RULES.md`. Charters exist as generated skeletons in `docs/charters/` with the facts filled in; owners must complete the TODO lines (purpose, owned entities, contracts, state machines, settings, KPIs). |
| Guideline version recorded; OD-01…OD-12 reviewed by the owner | **Owner action.** See "Decisions I need from you". |

## H tasks
| Task | Status | Main gap |
|---|---|---|
| A Conformance audit | Done | `docs/audit/phase-0-11-conformance.md` (progress log at the bottom) |
| H-01 Registries, module flags, stubs | Done | no screen to switch modules on/off; charters are skeletons |
| H-02 Numbering Service | Done for Billing | ten legacy generators remain (D-E02); no parallel 50-connection test |
| H-13 Code System Registry | Done (structure) | ICD/LOINC/ATC/DGDA lists must be loaded from official files; pickers not rewired |
| H-12 Public IDs | **Partly** | 3 of ~20 record types; JSON bodies and query strings still carry numeric ids |
| H-03 Observation Service | Step 1 | writers not switched; free-text nursing/ICU types need aliases |
| H-04 Alert Service | Step 1 | radiology/pharmacy not wired; no inbox/bell; in-app channel only |
| H-05 Charge Event Publisher | Step 1 | modules still call Billing directly (logged by adapter); whole-number quantities only |
| H-06 Stock Ledger | Done for Pharmacy | no reservations, in-transit state or approvals |
| H-07 Report/Export Service | Partly | CSV only (no XLSX/PDF — package decision), one report, no screens |
| H-08 Document/Print Service | Partly | HTML print only (PDF engine undecided), 3 templates, no editor screen, font not bundled |
| H-09 Global Search and Barcode | Done | p95 48 ms on 100k patients (MySQL); real scanners untested; patients only |
| H-10 Bangla localization | Mostly | AI-written wording needs native review; ~100 mixed headings and controller error texts still English |
| H-11 Import + demo data | Done (CSV) | no XLSX; seven importers only |
| H-14 Role homes and UX conformance | Partly | user task-time tests (3 users × 5 screens) not run; axe/device checks not run |
| H-15 Pilot kit | Done in code | needs hardware, people and a real restore on the target server |

## What stands between you and a pilot
1. **People tasks no code can do:** Bangla wording review, role training, printing and drilling the downtime forms, the 3-user task-time tests, hardware checks, penetration test, legal review (see `docs/pilot/go-no-go.md`).
2. **`php artisan pilot:readiness` must be green on the production server** (HTTPS, real mail, encrypted backups, signed restore drill, MFA for admins, queue worker, cron).
3. **Load the real code lists** (ICD-10/11, LOINC, ATC, DGDA) with `codes:import` and the hospital's masters with Data import.
4. **Switch the writers over** (or accept the read-through) before building Phase 12: ED must write vitals via `ObservationService`, alerts via `AlertService`, charges via `ChargeEventPublisher`, stock via `StockLedger`, and use `public_id` — the rails exist.

## Decisions I need from you (guideline 28.6 and open items)
| | Question |
|---|---|
| OD-01 | Where will the pilot hospital's server live (on-premises LAN, a Bangladesh data centre, cloud)? It decides backup storage, monitoring and the restore drill location. |
| OD-02/03 | Counsel's answer on data-protection duties; the accountant's answer on VAT treatment. |
| OD-04 | Which hospital, which modules and departments are in the pilot? Which modules should be switched OFF for them (use the module flags)? |
| OD-07 | SMS gateway and sender ID — nothing sends SMS yet; critical alerts only appear in-app. |
| Packages | May I add an XLSX package (suggest `openspout/openspout`), a PDF engine (Browsershot + Chromium, or Gotenberg), and Larastan for static analysis? |
| Hospital layer | Keep one default hospital per organization for now, or model your real group structure (several hospitals under one organization) before go-live? |
| Next phase | Start Phase 12 (Emergency) now, or first finish H-12 (public IDs everywhere) and switch the old writers over? I recommend finishing H-12 steps 2–3 first, because Phase 12 will copy whatever patterns exist. |
