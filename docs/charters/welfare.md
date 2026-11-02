# Patient Welfare Fund — Module Charter

_Updated in every slice (guideline 7.4). Phase 26 (Bangladesh pack) slice B1 is built. Every threshold, category, circumstance list and validity period is an example awaiting the hospital's welfare committee._

- Phase / owner: 26 / **owner to be named by the hospital** (the welfare officer in charge, with a committee that decides)
- Status: built · switchable per organization / hospital (`welfare`, depends on core; it uses Billing's adjustment service)
- Purpose: help patients who cannot pay. Money given to the hospital (zakat, donations, government or CSR grants, the hospital's own charity budget) is kept in funds; a welfare officer assesses a patient's means; a committee approves a grant against a fund; the waiver reaches the bill as a Billing discount adjustment.
- Permission prefix: `welfare` (13 permissions). Table prefix: `wf_`. Staff screens `/admin/welfare/*`; read-only API `/api/v1/welfare/{overview|funds}` (totals and fund balances only — no patient, income or circumstance).
- Owns: funds, donors, the fund ledger (`wf_fund_entries`, never changed — a database trigger refuses it), assessments, grants, and the record of each discount asked of Billing.
- Does NOT own: invoices and discounts (Billing — Welfare asks `AdjustmentService::request` for a discount and Billing's own approver decides), posting to the ledger (Finance listens to `AdjustmentApproved` as for any discount), patient identity (Patients), payers that reimburse the hospital (Insurance — charity, NGO and government schemes that pay the hospital stay there as payers; this module is for money the hospital holds and spends itself).
- Rules: a fund's balance is the sum of its entries; "set aside" is what approved, unexpired grants may still take; a grant can only be approved for what is free. The assessor cannot decide their own assessment; the requester cannot approve their own grant. Billing's approval is what charges the fund, once (unique per adjustment), with `welfare:check` as a safety net if the event is missed. A mistake in the ledger is put right with a reversal that says why. Sensitive data (income, household, circumstances) is shown only to those who hold `welfare.assessment.view`.
- Means test (examples): income per person per month under ৳3,000 → free (100%), under ৳6,000 → subsidised (50%); circumstances add points and two points move a person up one category. The result is a **suggestion** to the committee.
- Hooks: `welfare:check` daily at 06:40 (lapse old assessments and grants, charge funds for discounts Billing approved if the event was missed, alert on what waits too long and on funds running low); `welfare:install` (three example funds).
- Roles: `welfare_officer` (assess, ask for grants, apply them to bills), `welfare_committee_member` (decide assessments, approve grants, reports), `welfare_manager` (funds, donations, reports, settings).
- Status machines: assessment draft → submitted → approved | rejected → expired; grant requested → approved → used | expired | cancelled (or rejected); discount asked of Billing requested → applied.
- Settings keys: `welfare.*` override `config/welfare.php`.
- Reports: overview (free money, given, received, patients helped, what waits), monthly received and given, by fund, by category of need.
- Known limitations & deferred items: see docs/decisions/D-P26-bangladesh-pack.md.

## Tables
`wf_funds`, `wf_donors`, `wf_assessments`, `wf_grants`, `wf_applications`, `wf_fund_entries`.
