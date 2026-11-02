# D-DASH-01 — Dashboard foundation (slice D0)

Owner instruction (2026-11-02): "go for the dashboard" after the plan in `docs/ux/dashboards-plan.md`. The plan's open decisions were not answered, so the recommended choice was taken for each and is recorded here; any can be changed later.

## Decisions taken
1. **No chart package.** The existing inline-SVG chart was promoted to `x-ui.chart` (adds `hbar`, `spark`, dashed second series, a text title and a table fallback). It works offline on a LAN, prints, and is accessible. `x-analytics.chart` now delegates to it, so the BI screens are unchanged.
2. **One tile.** The shared `x-ui.kpi-tile` (already used by Quality and Facility) was extended, not replaced: with only a label and a value it renders as before (tested). `x-analytics.kpi-tile` stays for the KPI catalog and moves over in D6.
3. **One number writer.** `App\Support\Dashboard\Numbers` writes every figure; `AnalyticsFormat` delegates to it. Defaults are unchanged (international grouping, English digits). A hospital chooses South Asian grouping (12,34,567 — how taka are usually written) and Bangla digits in Settings. **Recommendation for the pilot:** switch grouping to `south_asian`; Bangla digits are the hospital's call (D5 in the plan).
4. **Targets are the hospital's.** None ships (`dashboard.target.<code>`); a figure without a target has no good/bad colour. Status is always colour + icon + word.
5. **Refresh rhythm.** Live boards 60 s, management pages 300 s (`config/ux.php`), a board never reloads while someone is typing or the tab is hidden. Pages show "as of" and a Refresh button. (D3 in the plan: change in `config/ux.php`.)
6. **Cache** (60 s) keyed by hospital, branch, period and the viewer's rights, so one scope never sees another's figures; the time worked out is kept and shown.
7. **Periods** are Dhaka days (today, 7, 30, 90 days, this month, last month, custom up to 366 days); queries on UTC columns use `fromUtc()/toUtc()`; "against the period before" is the equal stretch before (the whole previous month for month periods).
8. **Reference build:** the Welfare dashboard, with two new list pages (gifts, discounts) that each figure opens and whose count and total a test compares with the figure.

## Not done in D0 (next slices)
Role-aware Home and the Director's "Today" (D1); moving each module's dashboard onto the standard (D2–D5); the BI role dashboards (D6). Per-user Bangla-digit preference (today an organization setting).

## Open for the owner
The director's 6–8 "Today" figures (D1); which KPIs get targets and their values; whether South Asian grouping and Bangla digits are on for the pilot.
