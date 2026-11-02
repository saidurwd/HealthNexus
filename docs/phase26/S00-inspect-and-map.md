# Phase 26 · B1 — Inspect and map (patient welfare)

What exists and is reused; what is new. Nothing in another module was edited.

| Need | Reused (owner) | New (Welfare) |
|---|---|---|
| A patient | `patients` (Patients) — found by `enterprise_patient_no`, hospital checked | — |
| A bill and its discount | `billing_invoices`, `AdjustmentService::request/approve`, event `AdjustmentApproved` (Billing) | `wf_applications` links a grant to the adjustment |
| Posting the discount to the ledger | `PostToLedger@adjustmentApproved` (Finance) | — |
| Numbers | `NumberSeriesService` (core) | series `wf.receipt`, `wf.assessment`, `wf.grant` |
| Alerts | `AlertService` (Clinical alerts) | `WelfareAlerts` (category `welfare`) |
| Audit | `AuditLogger` | every decision logged |
| Money held and decisions | — | `wf_funds`, `wf_donors`, `wf_fund_entries` (immutable), `wf_assessments`, `wf_grants`, `wf_applications` |
| Charity as a payer that reimburses | Insurance payer types `charity`, `ngo`, `government` (Insurance) — **unchanged** | — |
| Menu | `config/adminlte.php` section "Billing and insurance" | "Patient welfare" |

Billing facts that shaped the design: a discount adjustment is requested then approved (no reject path); approval applies it to the invoice and fires `AdjustmentApproved`; the posting interface is bound to a null poster while Finance listens to the event.
