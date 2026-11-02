# Dashboard standard — how to build a dashboard here

Plan and reasons: `docs/ux/dashboards-plan.md`. This page is the working recipe. The Welfare dashboard (`/admin/welfare`) is the reference build for a management page; the Queue board (`/admin/queue-board`) for a live board; the Home (`/dashboard`) for a personal one.

## The parts (all built in D0)
| Part | Where | Use |
|---|---|---|
| Frame | `<x-ui.dashboard>` | states the question and audience, the period filter, "as of" with a Refresh button, and (live boards) a self-reload |
| Headline figure | `<x-ui.kpi-tile>` | value + unit, change against the period before, target with colour + icon + word, trend, hidden-small-number state; `href` opens the list it counts |
| Chart | `<x-ui.chart>` | `bar`, `line`, `hbar` (ranking), `spark`; inline SVG; title for screen readers; "Show as a table" |
| Empty state | `<x-ui.empty-state>` | say what is missing and what to do |
| Period | `App\Support\Dashboard\DashboardPeriod` | `fromRequest($request, '30d', [...allowed])`; Dhaka days; `previous()`; `fromUtc()/toUtc()` for UTC columns |
| Numbers | `App\Support\Dashboard\Numbers` | `value($v, 'taka'\|'count'\|'percent'\|'minutes'\|'hours'\|'days')`; honours the hospital's grouping (12,34,567) and Bangla digits settings |
| Change / target | `App\Support\Dashboard\Kpi` | `change()`, `status()`, `changeIsGood()` |
| Targets | `App\Services\Dashboard\Targets` | the hospital's own (`dashboard.target.<code>`); none ships |
| Cache | `App\Services\Dashboard\DashboardCache` | 60 s default; key = hospital + branch + period + the viewer's rights; keeps the time worked out |
| Test kit | `Tests\Feature\Dashboard\DashboardAcceptance` | the checks below |

Settings a hospital can change: `ux.number_grouping` (`international` default, or `south_asian`), `ux.bangla_digits` (only used when the interface is Bangla), `dashboard.target.*`.

## The recipe
1. **Write the question and the audience first** (one sentence each). If you need two audiences, make two dashboards.
2. **Controller**: `$period = DashboardPeriod::fromRequest(...)`; compute the figures in a service (not the controller or the view) as plain arrays; wrap in `DashboardCache::remember($user, 'name', $org, $branch, $period, fn () => …, fresh: $request->boolean('fresh'))`; pass `as_of` to the view. Live "right now" figures belong in the same service; trends and targets come from the Analytics warehouse, not from re-counting transactions.
3. **View**: `<x-ui.dashboard :question :audience :period :as-of [:refresh]>` around 5–8 `x-ui.kpi-tile`, then charts, then lists. A live board (ward wall, queue) passes `:refresh="config('ux.dashboard.refresh_seconds.board')"`.
4. **Every figure opens its list.** Give the tile an `href` only if the person may open that page. The list page states `data-list-count` and `data-list-total` (see the Welfare *discounts* and *gifts* pages) so a test can prove the figure and the list agree.
5. **No patient names on a management dashboard.** Names belong on worklists.
6. **Words**: English in the code through `__()`, Bangla in `lang/bn.json`; dates in Dhaka time; use `Numbers` for every figure.

## Acceptance (copy into the slice's gate report)
`use DashboardAcceptance;` in the dashboard's test and call:
- `assertDashboardStatesItself($url, $user)` — question and "as of" are on the page;
- `assertDashboardRefuses($url, $userWithoutTheRight)`;
- `assertTileEqualsList($html, 'Tile label', $user, $expectedTotal, $expectedCount)` — the figure is the list's total;
- `assertNoPatientIdentity($html, [...names])`;
plus the ones that depend on your data: empty hospital, another hospital's rows not counted, the change against the period before, a bad period falls back, a person without list rights sees the figure but no link, cache keeps then refresh works it out again. `tests/Feature/Welfare/DashboardTest.php` shows each.

## Do not
- add a chart package (decision D-DASH-01);
- compute a trend by scanning transactions on page load;
- show colour alone for a status (use the tile's icon + word);
- put more than eight headline figures on a page;
- link a figure to a page the viewer cannot open.
