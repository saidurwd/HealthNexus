# D-E06 — Charge Event Publisher: state (H-05)

Done: `ChargeEventPublisher` contract (`App\Contracts\Billing`), `ChargeEventData` (key `MODULE:visit_ref:service_code:source_event_id`),
`BillingChargeEventPublisher` (service code → active `billing_items.item_code`; whole-number quantity only because
`billing_charges.quantity` is an integer), `charge_event_log` (accepted / rejected / not_chargeable / reversed /
reversal_needs_credit_note, `publish_count` shows repeats), `ChargeReversalRequested` event + listener,
`ChargeReconciliationReport` (events without charge, charges without event).

Adapter: existing modules (OPD, LAB, RAD, PHM, IPD/BED, NUR, OT, ICU) still call `ChargeService::createFromClinicalEvent`
with their `clinical_event_type/key` mapping. Every such charge is mirrored into `charge_event_log` by `ChargeEventLogger`
under the contract key. Their own `*_charge_events` tables (e.g. `icu_charge_events`, `ipd_bed_charge_events`) remain as
module outbox. Pharmacy dispensing uses `createManual`, which has no source record and is therefore not logged.

Not done: switching those modules to call the publisher directly (Phase 12+ must); decimal quantities; automatic credit
note when an already-invoiced charge is reversed (flagged for manual handling); reconciliation screen/export.
