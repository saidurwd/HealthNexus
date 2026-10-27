# D-P14 — Insurance and Corporate Claims: state at the end of the Phase 14 build

Date: 2026-11-05. Slices S01–S13 of guideline §26.3 are built and tested. This is not a sign-off by the billing / insurance officers.

## What is built
Payer register for every kind of payer; plans with co-pay, deductible, annual and visit limits; coverage rules (exclude / cover / limit, with their own co-pay and pre-authorization flag); effective-dated contract rates; policies with dependents and coverage order; eligibility (manual + gateway, never "eligible" from the null adapter); pre-authorization (request, decide, hard cap, consume, release, expire); claim generation from finalised invoices, scrub with fix hints, readiness, versions; submission as an exported batch file plus "mark submitted" (and the gateway path); queries, rejections, denials with reason codes and a worklist, appeals; remittances allocated to claims and posted as payments on the invoice; write-offs with a second approver; coordination of benefits (second policy); corporate credit limit and monthly statements; aging, dashboard; the invoice hook; screens, menu, Bangla; factories; six months of demo history and test users.

## Decisions made here
- **One payer register linked to the Phase 4 masters** (not a rewrite): `insurance_payers` points at `billing_insurance_providers` / `billing_corporates`; the Phase 4 policy table gained nullable columns.
- **Contractual adjustment is explicit.** When a contract rate is below the price, the difference is neither the payer's nor the patient's share but a stated adjustment, so the invariant is payer + patient + adjustment = bill (and = bill exactly when no contract rate applies). Insurance never changes a price; Billing decides whether to discount.
- **Patient share is what the cashier collects.** `InsuranceRemittanceService::patientDue()` gives the amount to ask the patient for once a claim exists; the payer's part stays due on the invoice until the remittance posts.
- **Draft only.** The invoice hook drafts claims but never sends one; a person marks it ready after the scrub and sends the batch.
- **Dates are compared with `whereDate`** so a date stored with a time part (SQLite) compares like MySQL's DATE.

## Needs the billing / insurance officers before use (OD-14)
Real payers, plans, co-pay / deductible / limits, coverage exclusions, contract rates; filing deadlines; denial reason codes and fix hints per payer; document checklists per claim type; the pre-authorization threshold and validity; who may approve write-offs and above what amount.

## Deferred
- Payer-specific claim form layouts and EDI / HL7 / FHIR (Claim, Coverage, ClaimResponse) mapping; real adapters for the PayerGateway.
- Package billing (surgery / delivery / health-check packages, P-23) as claim lines; per-diem and DRG-style payment.
- A patient insurance view in the portal (Phase 17); ledger posting (Phase 21) beyond the null receivable call.
- Overpayment and payer refunds; remittance import from a payer file; automatic matching suggestions.
- Pre-authorization consumption per service line (it is per claim for the lines flagged by rules); claim document upload to the File Service (the checklist records received / not received).
- Insurance API endpoints (`/api/v1/insurance`); real-user and load testing; native Bangla review.
