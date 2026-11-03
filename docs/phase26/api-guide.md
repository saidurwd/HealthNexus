# Patient welfare — guide for the welfare team and for IT

## Start
1. `php artisan welfare:install` adds three example funds (Zakat fund, General donations, Hospital charity fund). Rename them, set the most one patient may get and the level at which a fund warns. Idempotent.
2. Give people their roles: `welfare_officer`, `welfare_committee_member`, `welfare_manager`. The officer and the committee must be different people.
3. Settings `welfare.*` change the thresholds (see `config/welfare.php`).

## The journey
1. **Record money received** (Welfare → Funds and donations → the fund). Every gift gets a numbered receipt. A donor can be anonymous.
2. **Assess a patient** (Welfare → Assessments): number, household income, how many people it supports, circumstances, and what was seen or checked. The screen shows income per person and a suggested category. Send it to the committee.
3. **The committee decides** (a different person from the assessor). Approved assessments are valid for 180 days.
4. **Ask for a grant**: choose a fund, the most it may give and the share of a bill. A second person approves it against what the fund has free; the money is set aside.
5. **Take it off a bill**: pick the patient's bill. This asks Billing for a discount; the bill does not change yet. **Billing's approver** approves it in Billing → Adjustments; that is when the bill falls and the fund is charged.

## Every day
`welfare:check` (06:40) lapses old assessments and grants, charges funds for any discount Billing approved whose event was missed, and raises what has waited more than 3 days and funds that are running low.

## Read-only API
`GET /api/v1/welfare/overview` and `GET /api/v1/welfare/funds` with a staff token, the `X-Organization-Id` header and `welfare.dashboard.view` / `welfare.fund.view`. Totals and balances only: no patient, income or circumstance leaves through the API.

## Commands
`welfare:install [--organization=ID]` · `welfare:check`.
