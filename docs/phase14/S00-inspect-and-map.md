# P14-S00 — Inspect & Map: Insurance and Corporate Claims

Read-only report (guideline 26.3). Built on the owner's standing "go" (as for Phases 12 and 13): the 100-section `Phase 14 — Insurance & Corporate Claims` spec is not in the repository, so payer rules, denial codes and scrub rules below are **configurable examples** in `config/insurance.php` (overridable by `insurance.*` settings) for the billing and insurance officers to confirm (OD-14).

## What already exists (reused, not copied)
| Need | Owner | Note |
|---|---|---|
| Insurer master, patient policy, corporate client, contract, member | Phase 4 `billing_insurance_providers`, `billing_insurance_policies`, `billing_corporates`, `billing_corporate_contracts`, `billing_corporate_members` | Kept. Phase 14 adds `insurance_payers` as the **single payer register** (private, TPA, corporate, government, NGO, charity, panel — P-17) that links to those rows, plus plans, rules and rates |
| Simple co-pay % and limit | `InsuranceBillingService::applyCopay` | Superseded for claims by the Phase 14 split engine; the old method stays for Billing's pricing path |
| Invoice and lines | `billing_invoices` (`insurance_policy_id`, `corporate_id`, `billing_party_*`), `billing_invoice_items` | Claim lines are built from finalised invoice lines; Insurance never re-prices (override 1) |
| Payments | `PaymentService::collect` | A payer's remittance is posted as a payment on the invoice with payment method `INS` |
| Receivable posting | `FinanceGateway::postReceivable` (null adapter) | Called on claim acceptance and on settlement; aging works without Finance |
| Submission | `PayerGateway` (null) | A manual adapter (file export + mark submitted) ships first (override 4) |
| Diagnoses and procedures | Phase 3 `diagnoses` (ICD-10 code), `encounter_procedures` | |
| Documents | File Service | Claim document rows hold a checklist and, when present, a file id |
| Numbers, public ids, alerts, module flag | `NumberSeriesService`, `HasPublicId`, `AlertService`, `config/modules.php` key `insurance` (tables `insurance_*` / `corporate_*`, permissions `insurance.*`) | |

## Decisions (see D-P14)
- One payer register, linked to the Phase 4 masters. The Phase 4 tables are extended with new nullable columns (plan, payer, subscriber), never rewritten.
- Money is DECIMAL; **payer share + patient share = line net amount to the paisa**, with rounding remainder assigned to the patient share. Claim lines are immutable after submission; a change is a new claim version.
- Corporate credit billing reuses `billing_corporates` (credit limit, terms) and adds batches/statements under the `corporate_` prefix.

## Questions for the owner (not blocking)
1. Which payers/TPAs does Premier Health Center actually bill, and do any offer an API? (The manual export adapter is used until one does.)
2. Denial/rejection reason codes and claim form layout per payer (the example list is generic).
3. Filing deadlines and whether write-offs above a threshold need a second approver (example: any write-off needs approval).
