# Insurance / Corporate — Module Charter

_Updated in every slice (guideline 7.4). Phase 14 slices S00–S13 are built; every payer rule is an example awaiting the billing / insurance officers' approval (OD-14)._

- Phase / owner: 14 / **owner to be named by the hospital** (insurance & claims manager)
- Status: built · switchable per organization / hospital (`insurance`, depends on billing)
- Purpose: turn finalised invoices into claims, split each bill between payer and patient by the plan's rules, send claims, follow them to payment (queries, denials, appeals, write-offs), and bill corporate clients on monthly statements with a credit limit.
- Permission prefix: `insurance` (see docs/registry/permissions.md). Table prefixes: `insurance_`, `corporate_`. Routes: `/admin/insurance/*`. API: none yet.
- Owns: the payer register (private insurers, TPAs, corporate, government, NGO, charity, panels), plans, coverage rules, effective-dated contract rates, policy dependents, eligibility checks, pre-authorizations, claims (versions, lines, timeline, documents), submission batches, queries / rejections / denials, appeals, remittances and their allocation, write-offs, corporate statements.
- Does NOT own: prices and charges (Billing — claims are built from finalised invoice lines and never re-price), invoices and payments (a remittance is posted as a payment on the invoice with payment method `INS`; a corporate statement payment with method `CORP`), the patient (MPI), diagnoses and procedures (Phase 3), the general ledger (FinanceGateway receivable posting with the null adapter until Phase 21).
- Reuses Phase 4 tables and extends them with nullable columns: `billing_insurance_providers`, `billing_insurance_policies` (payer, plan, subscriber, coverage order, annual used, deductible met), `billing_corporates`, `billing_corporate_contracts`.
- Hooks: `InvoiceFinalized` → a DRAFT claim when the invoice carries a policy with payer and plan (this is how IPD, ED, OT, pharmacy, laboratory, radiology and the blood bank reach the insurance desk — they all charge through Billing). `InvoiceLifecycleService::finalize` refuses a corporate invoice that would take the client over its credit limit. `PayerGateway` (null adapter) for eligibility, pre-authorization and API submission.
- Claim state machine: draft → ready → submitted → acknowledged/queried → accepted → partially_paid → paid → closed; rejected → (new version); denied → appealed → accepted/denied → written_off; cancelled. After submission the claim is locked: a correction is a new version (parent kept as evidence). The timeline is append-only.
- Money rules enforced in code and tests: payer share + patient share + contractual adjustment = bill, to the paisa, per line and per claim (golden-file tests of 11 split cases); pre-authorization is a hard cap and is released when the claim is cancelled; a payer is never paid beyond what it owes; a remittance is never allocated beyond what it holds; a write-off needs a different approver; the same invoice cannot be claimed twice from one payer.
- Settings keys: `insurance.*` override `config/insurance.php` (filing deadline, denial reason codes, document checklist, scrub rules, aging buckets, write-off approval, pre-authorization threshold, alert roles).
- Reports: dashboard (status counts, outstanding, denial rate, days to payment, deadlines, open queries), aging by payer, denial / rejection worklist by reason code with fix hints.
- Known limitations & deferred items: see docs/decisions/D-P14-insurance-state.md.

## Tables


