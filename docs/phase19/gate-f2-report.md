# Phase 19 — BI / Analytics: Gate F-2 report

| Check | Result |
|---|---|
| Slices S00–S12 | built |
| Whole suite | 1,346 tests, all passed (6,983 assertions). Analytics tests: 38 in `tests/Feature/Analytics/*` (warehouse core, facts, semantic layer, insight, screens and API); demo-history test extended with the analytics invariants |
| Pint · phpstan (level 5, no baseline entries added) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; about 365 new strings — **wording needs native review** |
| MySQL | both migrations apply on a fresh MySQL database and the six-month history ran there. One defect was found by the SQLite demo test (not MySQL-specific) and fixed: the bed census failed for beds without a branch |

## Scratch MySQL build (six months)
Warehouse agrees with the records: 2,371 encounters = 2,371 facts; 261 admissions = 261 facts; 3,347 invoices; **billed money 21,953,323.00 in Billing and in the warehouse (difference 0.00)**; current patient rows equal patients (difference 0). 0 failed loads; 25 standard KPIs, 5 dashboards; 3 reports, 2 schedules (1 delivery sent), 1 research dataset (not self-approved), 5 forecasts; 0 open serious data-quality issues. Two warnings stay open on purpose (`provider_unknown`, `visit_without_department`): they come from earlier modules' demo data and are for the hospital to fix.

## Behaviour that is tested
| Rule | Status |
|---|---|
| No identifying column in any dim/fact table; patient pseudonym stable per key | tested |
| Watermark moves only after success; a failed source does not stop the others; reruns change nothing | tested |
| Queries use catalogue names only, values bound, organization/branch applied by the compiler; unknown names refused | tested |
| Small counts hidden without `analytics.sensitive.view` | tested |
| Research dataset: second approver, k-anonymity, month-level dates | tested |
| Benchmark needs opt-in and ≥ 3 peers; scheduled mail staff-only with owner's rights re-checked | tested |

## Open for the hospital
Replica or same server for the warehouse; national DHIS2 codes; targets; native review of Bangla. See `docs/decisions/D-P19-analytics-state.md`.
