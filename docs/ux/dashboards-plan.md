# Dashboards — inventory and plan to build them professionally

_Written 2026-11-02 from the code as it stands (routes, controllers, views, the Analytics module). Nothing here is built yet; this is the plan. Open decisions are at the end._

## 1. What exists today

### 1.1 Operational dashboards (25)
Counted from the routes. "Links" = clickable drill-through in the view. "Charts" = any chart. None has a chart, only the Emergency board refreshes itself, and only a few take a period.

| # | Dashboard | Route | Today's content | Links | Period | Auto-refresh | Charts |
|---|---|---|---|---|---|---|---|
| 1 | **Main dashboard** | `/dashboard` | 4 admin widgets (users, organizations, branches, system health) from `DashboardWidgetRegistry`; role users are sent to a worklist instead (`config/ux.php` role_homes) | 1 | – | – | – |
| 2 | Billing and cashier | `/billing` | today and cashier-session cards | 0 | – | – | – |
| 3 | Laboratory | `/lab` | order and result counts | 0 | – | – | – |
| 4 | Radiology | `/radiology` | orders, studies, reports counts | 0 | – | – | – |
| 5 | Pharmacy | `/pharmacy` | orders, dispensing, stock alerts | 0 | – | – | – |
| 6 | IPD (Indoor) | `/ipd` | beds, admissions, discharges | 0 | – | – | – |
| 7 | Nursing | `/nursing` | ward patients, tasks, escalations | 1 | – | – | – |
| 8 | OT / Surgery | `/ot` | cases, theatres | 1 | days | – | – |
| 9 | ICU | `/icu` | beds, alerts, ventilated | 1 | – | – | – |
| 10 | Emergency board | `/emergency` | live tracking board | 1 | – | **yes** | – |
| 11 | Emergency performance | `/emergency/dashboard` | door-to-doctor, LWBS, etc. | 0 | **yes** | – | – |
| 12 | Blood bank | `/blood-bank` | stock by group, requests | 2 | – | – | – |
| 13 | Insurance and corporate | `/insurance` | claims, denials, aging | 1 | days | – | – |
| 14 | Stores and supplies | `/inventory` | stock, expiry, requests | 1 | days | – | – |
| 15 | Purchasing (reorder) | `/procurement/reorder` | what to buy next (a list, not a dashboard) | – | – | – | – |
| 16 | HR / People | `/hr` | headcount, leave, attendance | 2 | days | – | – |
| 17 | Patient portal | `/portal` | logins, requests, payments | 1 | days | – | – |
| 18 | Patient relations (CRM) | `/crm` | contacts, complaints, ratings | 1 | days | – | – |
| 19 | Reports and analytics | `/analytics` | list of the BI dashboards below | 3 | – | – | in BI |
| 20 | Quality and safety | `/quality` | incidents, actions, indicators | 15 | days | – | – (uses the KPI tile) |
| 21 | Facility and support | `/facility` | equipment, work orders, beds to clean… | 13 | days | – | – (uses the KPI tile) |
| 22 | AI assistant | `/ai` | functions and drafts | 1 | days | – | – |
| 23 | Finance | `/finance` | receivables, payables, cash | 3 | – | – | – |
| 24 | Connections (interoperability) | `/interop` | messages, failures | 2 | days | – | – |
| 25 | Patient welfare | `/welfare` | funds, given, waiting | 6 | days | – | – |

Also: **role homes** (reception, doctor, nurse, pharmacist, cashier, lab and others land on a worklist, `config/ux.php`), and the read-only `/api/v1/*/overview` endpoints for Facility, Quality, Welfare, ICU, IPD, Nursing and OT.

### 1.2 BI dashboards (Analytics module, built in Phase 19)
Five **system role dashboards** over the semantic layer, each of KPI tiles + charts + tables, with target-aware KPIs, drill into *Explore*, scheduled reports, and small-number suppression:
**Chief executive · Medical director · Finance · Nursing and wards · Pharmacy and stores.** Hospitals can add their own (`/analytics/dashboards/new`). A real SVG chart component (`x-analytics.chart`, bar and line) and a KPI tile (`x-analytics.kpi-tile`) already exist.

## 2. What is wrong (so the plan fixes the right things)
1. **The front door is a placeholder.** `/dashboard` shows four administrator numbers. A director, a ward manager or a cashier gets nothing useful there.
2. **24 dashboards are cards of counts**: no trend, no comparison with yesterday or last month, no target, no chart. A number without context cannot be judged.
3. **Numbers rarely lead anywhere.** 9 of 25 views have no link at all; a figure should open the list it counts.
4. **No standard.** Each module invented its own cards, period box and wording; only Quality and Facility use a KPI tile. The user learns 24 layouts.
5. **No "as of" time and no refresh** except the Emergency board: a screen left open on a ward wall goes stale silently.
6. **Two worlds that do not meet.** Live operational figures come from module services; trends and targets live in Analytics. Neither borrows from the other.
7. **No Bangladesh lens**: nothing for the national return (DHIS2/HMIS), outbreaks, mothers and children, welfare, or the weekly rhythm (Friday), and no Bangla digits.

## 3. The standard every dashboard must meet
A dashboard is professional when a busy person can answer **one stated question in five seconds** and act on it.

1. **One question and one audience** written at the top (for example "Where are my patients and what is waiting?" for a ward manager). If it needs two audiences it is two dashboards.
2. **Five to eight headline figures at most**, each a KPI tile: value, unit, change against the previous period, target (if the hospital set one), and a status shown as colour **plus** icon **plus** word (never colour alone).
3. **Every figure opens the list it counts** (same filter, same count; a test proves the two agree).
4. **A chart only where it answers something**: a trend (line), a ranking (bar), a distribution. No decoration, no pie/3-D. Axis, units and a text alternative on every chart.
5. **Standard controls**: period (today, 7, 30, 90 days, this month, custom), branch/department where the person may see more than one, and a visible **"as of hh:mm"** stamp with a refresh button. Live boards refresh by themselves and say so.
6. **Honest states**: loading, empty ("nothing yet" with what to do), error ("could not be worked out" — never a silent zero), and "small numbers hidden" where privacy needs it.
7. **Privacy by default**: management dashboards carry totals, not patients; a patient name appears only on a worklist the person is allowed to open. Menu hiding is not security: the figures are computed under the viewer's own scope (organization, branch, permission).
8. **Fast and light**: first paint under one second on the hospital PC at 1366×768; heavy trends read from the warehouse or a cached snapshot, never a live scan of transactions.
9. **Bangla first-class**: every label translated, Bangla digits as a user setting, dates in Dhaka time, taka formatting, Friday-aware weekly views.
10. **Printable and exportable** where a director needs it (A4, permission-gated, same figures as the screen).
11. **Tested**: scope (another hospital's data never appears), permission, the figure equals the list, period maths, empty and error states, and the demo history gives the charts six months of believable data.

## 4. Architecture — reuse, do not rebuild
Two layers that already exist and need joining:

* **Live layer (today)** — the module's own service (`BillingDashboard…`, `FacilityOverview`, `QualityOverview`, `WelfareOverview`…). Real time, current counts, drill to worklists. Keep.
* **Trend layer (history)** — Analytics warehouse + semantic layer + KPI catalog (targets, definitions, suppression). Use it for every trend, comparison and target; do not re-aggregate transactions in a module.

New shared pieces (one place, used by all):
1. `x-ui.kpi` — promoted from `x-analytics.kpi-tile` (value, unit, delta, target, status, optional sparkline, link). Quality and Facility already point at the tile; they move over without a visual break.
2. `x-ui.chart` — promoted from `x-analytics.chart` (adds sparkline, stacked bar, horizontal ranking bar, data table fallback, "download as image/CSV" where permitted). **Inline SVG, no new package** (see decision D1).
3. `x-ui.dashboard` layout — header (question, audience), filter bar (period, branch), "as of" + refresh, responsive grid (12-col, collapses at 1024/768), print stylesheet.
4. `DashboardDefinition` contract — a module registers its dashboard (tiles with query + drill route + permission) in `DashboardWidgetRegistry`; the registry already filters by permission. Live tiles read module services; trend tiles read the semantic layer. The page itself holds no query logic.
5. `DashboardCache` — short cache (30–120 s) keyed by organization + branch + period + permission-set for the live layer; snapshot rows for the heavy trends. Cache never crosses a scope.
6. A **dashboard test kit** (`DashboardAcceptance` trait): scope, permission, number-equals-list, states, Bangla parity, query-count budget (guard against N+1).

## 5. The dashboards to build
Priority: **P0** = needed by every hospital on day one; **P1** = needed in the first months; **P2** = later / by demand.

### 5.1 The front door
| Dashboard | Audience and question | Headline figures | Notes |
|---|---|---|---|
| **Home (role-aware)** `/dashboard` — P0 | Everyone: "What needs me now?" | Role-specific: a worklist strip (items waiting for *me*), 4–6 KPIs for the role, alerts, shortcuts | Replaces the 4 admin widgets. Role homes today redirect to a worklist; instead the home page opens with that worklist *and* the day's figures. Admin trivia moves to System. |
| **Director's "Today in the hospital"** — P0 (new, composed) | Director/CEO: "Is today going well?" | Patients seen (OPD, ED), inpatients and bed occupancy, ED waiting, theatre cases, revenue today vs average, collections, critical alerts, pending approvals | Built from the live layer; one screen, printable as the morning report. Links into each module board. |

### 5.2 Clinical operations
| Dashboard | Question | Headline figures (examples) | Charts | Priority |
|---|---|---|---|---|
| OPD / reception queue (new, from Appointments + Clinical) | "How long are people waiting and who is free?" | waiting, longest wait, seen today, no-shows, doctors on duty | queue by hour; wait distribution | P0 |
| Emergency board | live: who is where, who waits | by triage level, longest wait, bays free | – (board) | P0 (exists; add targets + as-of) |
| Emergency performance | "Are we meeting our targets?" | door-to-doctor, LWBS, length of stay, left against advice | trend by day; by hour of day | P1 |
| IPD (Indoor) | "Where are the beds and what is leaving?" | occupancy by ward, admissions, expected discharges today, long stays | occupancy trend; by ward bar | P0 |
| Nursing | ward manager: "What is overdue on my ward?" | tasks overdue, medication due, escalations, handover pending | tasks by hour | P0 |
| ICU | "Who is critical and what is free?" | beds, ventilated, alerts, device days | alerts by day | P1 |
| OT / Surgery | "Is the theatre list on time?" | cases today, delays, cancellations, turnover | utilisation by theatre; delays by cause | P1 |
| Blood bank | "Do we have blood for the groups we need?" | stock by group (with minimum), expiring in 7 days, requests waiting | stock by group bar | P0 |

### 5.3 Diagnostics and pharmacy
| Dashboard | Question | Headline figures (examples) | Charts | Priority |
|---|---|---|---|---|
| Laboratory | "What is waiting and what is late?" | orders waiting, TAT vs target, critical results unacknowledged, QC status | TAT trend; volume by hour | P0 |
| Radiology | "How long do patients wait for a report?" | studies waiting to be reported (with the new reading-order idea), TAT, critical findings | TAT trend by modality | P1 |
| Pharmacy | "Can we serve the prescriptions and is stock safe?" | orders waiting, dispensing today, near-expiry, stock-outs, controlled-drug count | stock-out trend | P0 |

### 5.4 Money
| Dashboard | Question | Headline figures (examples) | Charts | Priority |
|---|---|---|---|---|
| Billing and cashier | cashier/accounts: "What was collected and what is open?" | collected today by method (cash, card, bKash, Nagad), open bills, refunds, shift variance | collection by hour/day | P0 |
| Insurance and corporate | claims desk: "What is stuck?" | claims by stage, denial rate, aging buckets, queries due | aging bar | P1 |
| Finance | finance manager: "Where do we stand?" | cash and bank, receivables, payables, month result vs budget, failed postings | revenue/expense trend | P1 |
| Patient welfare | welfare officer/committee | free money per fund, given this month, waiting decisions, waiting for Billing | received vs given by month | P1 (exists; add trend) |

### 5.5 Supply, people, hospital services
| Dashboard | Question | Headline figures (examples) | Charts | Priority |
|---|---|---|---|---|
| Stores and supplies / Purchasing | "What will run out and what is on order?" | below reorder, expiring, open POs, goods waiting | – | P1 |
| People (HR) | HR: "Who is here and who is not?" | on duty today, absent, on leave, vacancies, credentials expiring (BMDC/BNMC) | attendance trend | P1 |
| Facility and support | facility manager | equipment down, work orders, bed turnaround, ambulance, waste | turnaround trend | P2 (exists, uses the KPI tile) |
| Quality and safety | quality officer | incidents open, late actions, indicators vs target, infections | indicator trends | P2 (exists, uses the KPI tile) |

### 5.6 Patient-facing, insight, platform
| Dashboard | Headline figures | Charts | Priority |
|---|---|---|---|
| Patient relations / Portal | complaints open and old, ratings, portal use | rating trend | P2 |
| Connections | failed messages, partners down | failures by day | P2 |
| AI assistant | drafts accepted, errors, incidents, drift | acceptance trend | P2 |
| Reports and analytics (BI) | the five system dashboards moved onto the shared tile/chart/filter standard, plus saved snapshots and the weekly emailed pack | – | P1 |

### 5.7 Bangladesh-specific (some depend on the gap slices B2–B7)
| Dashboard | Why | Depends on |
|---|---|---|
| **National return (DGHS / DHIS2)** monthly check-sheet: figures vs the data set, missing elements, "ready to send" | hospitals must report monthly; exists as a pack, needs a readable dashboard | built (Analytics national pack) |
| **Catchment map by district/upazila** (patients by area) | where patients come from; planning | B3 address master |
| **Outbreak watch** (dengue and seasonal diseases: daily counts by area, line list for the surveillance officer) | seasonal reality | B5 |
| **Mother and child** (antenatal, deliveries, caesarean rate, newborn, EPI coverage) | core service in many hospitals | B4 |
| **Cabin and bed-class mix** | private-hospital revenue lens | B6 |
| **Welfare and zakat transparency** (money in/out, patients helped, no names) | donor trust | built (extend) |

## 6. Delivery plan
Each slice: spec → build → tests (the §3 list) → demo-data check → Bangla → short gate note → commit. About a week of focused work per slice for a single developer; slices D2–D6 can run in any order after D1.

| Slice | Content | Output |
|---|---|---|
| **D0 — Foundation** *(built 2026-11-02, see `docs/ux/dashboard-standard.md`; reference: Welfare)* | `x-ui.kpi`, `x-ui.chart` (SVG, sparkline), `x-ui.dashboard` layout and filter bar, "as of" + refresh, `DashboardDefinition` + registry, `DashboardCache`, acceptance test kit, style rules (colour + icon + word; contrast; Bangla digits setting); migrate Quality and Facility to the shared tile as the proof | the standard, ready to use |
| **D1 — Front door** *(built 2026-11-02: Home, Today in the hospital, Queue board)* | Role-aware Home, Director's Today, OPD/reception queue board | people stop landing on a placeholder |
| **D2 — Clinical** *(built 2026-11-02: IPD, Nursing, ICU, OT, Emergency performance; the Emergency board was already a live board)* | IPD, Nursing, Emergency (board + performance), ICU, OT | wards and theatres on one standard |
| **D3 — Diagnostics and pharmacy** *(built 2026-11-02: Lab, Radiology, Pharmacy, Blood bank)* | Lab, Radiology, Pharmacy, Blood bank | TAT and stock safety |
| **D4 — Money** *(built 2026-11-02: Billing, Insurance, Finance; Welfare was built on the standard in Phase 26)* | Billing and cashier, Insurance, Finance, Welfare trends | collections and claims clarity |
| **D5 — Supply, people, services** *(built 2026-11-03: Stores, HR, CRM, Portal, Connections framed and cached; Facility and Quality framed; AI left as governance)* | Stores/Purchasing, HR, Facility, Quality, CRM/Portal, Connections, AI | the rest on the standard |
| **D6 — BI and reporting** *(built 2026-11-03: standard dashboards on the frame, snapshots, month-end A4 pack, national-return readiness; e-mailing the pack not built)* | five system dashboards on the standard, snapshots, scheduled A4 pack, national-return dashboard | management reporting |
| **D7 — Bangladesh dashboards** | catchment, outbreak, mother-and-child, bed-class mix — each ships with its gap slice | local needs |

Order rationale: the foundation first (so nothing is built twice), then the screens people open all day, then the money, then the long tail.

## 7. Acceptance checklist (copy into each slice's gate report)
- [ ] The question and audience are stated on the screen.
- [ ] ≤ 8 headline figures; each has unit, change, status (colour + icon + word) and, where set, a target.
- [ ] Every figure opens its list; a test shows the figure equals the list count.
- [ ] Period, branch and "as of" are present; live boards refresh and say so.
- [ ] Empty, loading and error states exist and are tested; no silent zero.
- [ ] Scope and permission tests pass (another hospital's data never appears; a person without the right gets 403).
- [ ] No patient identity on a management dashboard.
- [ ] Charts have axes, units, a text alternative and a table fallback.
- [ ] Renders well at 1366×768 and on a tablet; prints on A4 where required.
- [ ] Bangla complete; Bangla digits honoured; Dhaka time.
- [ ] Query count within budget; first paint under one second with the six-month demo data.

## 8. Open decisions for the owner
_D1, D3, D4, D5 and D6 were not answered before D0 was built; the recommended choice was taken and recorded in `docs/decisions/D-DASH-01-dashboard-foundation.md`._

* **D1 — Chart tooling.** The plan keeps the existing **inline-SVG** components (no package, works offline on a LAN, prints well, accessible). A chart package (for example ECharts or Chart.js) would give interactivity faster but adds a package, which needs your approval (rule in `docs/AI-RULES.md`). Recommendation: stay with SVG for D0–D5; revisit only if hover/zoom is truly needed.
* **D2 — Targets.** Which KPIs have targets and what are they (door-to-doctor minutes, lab TAT, occupancy band, collection rate)? The system ships none as fact; the hospital sets them.
* **D3 — Refresh rhythm.** 60 s for boards, 5 min for management pages, or by hand?
* **D4 — Director's list.** The pilot hospital's director should name the 6–8 figures for "Today"; the draft above is a starting point.
* **D5 — Bangla digits** default on or off for the Bangla interface?
* **D6 — Order.** Start with D0 then D1 (recommended), or a specific module first?

## 9. Risks
* **Numbers that disagree** across dashboards: avoided by one definition per KPI (the KPI catalog) and the number-equals-list test.
* **Slow pages** on large hospitals: avoided by the cache/snapshot layer and the query budget.
* **Over-design**: the standard caps the figures per page; resist adding "just one more".
* **Privacy drift**: management pages carry totals only; reviewed in every slice.
