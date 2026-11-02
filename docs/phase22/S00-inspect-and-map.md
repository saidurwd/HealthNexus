# Phase 22 — S00 Inspect and map (Facility and Support Services)

Guideline 27.2. Rule of the phase: **tasks are created from events, never by editing other modules' tables; devices that influence patient care get calibration blocks.**

## What exists (reused, never copied)
| Need | Existing | Use |
|---|---|---|
| Fixed assets (money) | Finance `fin_assets` (tag, cost, life, depreciation) | the asset register keeps a **link** (`fin_asset_id`); depreciation stays in Finance |
| Care devices | `nursing_devices`, `icu_devices` (devices on a patient) | a different thing: those are devices *attached to a patient*; the register is the *equipment* |
| Beds | `ipd_beds` (status available / cleaning / maintenance / isolation …), event `BedReleased`, `IpdBedStatusService::transitionTo()` | a released bed (status cleaning) creates a turnover task; finishing it asks IPD's own service to make the bed available |
| Operations | `ot_surgeries`, events `SurgeryCompleted` | CSSD set use points at the operation (id only) |
| Deaths | `patients.deceased_at`, ED disposition `death`, ward status `deceased` | the same sources as the Quality death review; the mortuary opens one case per death |
| Ambulance | ED `arrival_mode = ambulance` (a label only; no fleet exists) | fleet, crew and trips are new; a trip may point at an ED visit |
| Admissions | `ipd_admissions` (patient, ward, status, discharge) | diet orders and visitor/attendant passes belong to an admission; passes end at discharge |
| Stock | Phase 15 stock ledger | spare parts and kitchen supplies are *recorded*; posting them to the ledger is deferred (see decision record) |
| Alerts, audit, numbering, settings, QR | `AlertService`, `AuditLogger`, `NumberSeriesService`, `SettingsService`, simple-qrcode (installed) | same conventions |

Nothing in IPD, Nursing, OT, ED, Inventory or Finance is changed (rule 15). Their events are listened to; their services are *called* where they own the action (bed status).

## Design
- **Prefix `fac_`, permissions `facility`, module key `facility`, API `/api/v1/facility`.**
- **Assets.** Tag, category, risk class (high-risk equipment gets calibration blocks), warranty, location, QR token. The QR on the label opens `/admin/facility/scan/{token}` (staff sign-in required) — the token is random and carries no patient data. `AssetService::usable()` is the call other modules ask: false when the asset is out of service or a required calibration is overdue/failed.
- **Maintenance.** Plans (preventive, calibration, safety test) with an interval; the daily check makes a work order when one comes due. Work orders: open → in_progress → waiting_parts → done → verified (breakdown and calibration of high-risk equipment are verified by someone other than the engineer). Downtime is measured from the breakdown to completion. A failed calibration takes the asset out of service until a passing one. Spare parts are recorded on the order.
- **Housekeeping.** `BedReleased` with the bed in `cleaning` → one turnover task (unique per allocation). Starting records the time; finishing needs the checklist done, and an isolation/terminal clean needs inspection by another person; finishing then asks IPD to make the bed available (only if it is still in cleaning). Turnaround minutes are kept. Cleaning audits score an area against a checklist; a score below the set level raises a Quality action.
- **CSSD.** Instrument sets; sterilisation batches (machine, method, cycle, indicator result); a batch is **released only after a passing indicator and by a second person**; each released set has a use-by date; issuing a set to an operation records the link. A failed batch is recalled: `trace()` lists every operation/patient a batch's sets touched.
- **Linen.** A movement ledger per linen type (issue, collect soiled, send to laundry, receive clean, condemn); balances by location are derived; par levels warn.
- **Biomedical waste.** Weighed by category and ward; a manifest collects a period's records for the contractor, is handed over, and confirmed with the disposal certificate; manifest weights must equal the records in it.
- **Diet and kitchen.** Diet types; one active diet order per admission (allergies and texture); the day's meal list per ward and meal is made from active orders; trays are served, refused or held for fasting; the kitchen's day requisition is the diet counts.
- **Ambulance.** Fleet with status; trips (emergency, transfer, discharge, referral, mortuary) dispatched, arrived, completed; odometer and fuel; a vehicle on a trip or in maintenance cannot be dispatched.
- **Mortuary.** One case per death (found by the check, once); two staff verify identity at receipt; storage units with capacity; release only to an authorised person after identity papers are seen and a **second person authorises**; police cases are held until cleared.
- **Visitors.** Passes for visitors and attendants of admitted patients, within visiting hours and a limit of attendants per patient; passes end at discharge; the gate log records in and out; blocked persons are refused.
- **Access.** Staff rights per area; the gate and housekeeping roles see only their screens. Visitor ID numbers are stored encrypted and shown masked.

## Slice plan
S01 assets and QR · S02 maintenance, calibration, work orders · S03 housekeeping and cleaning audits · S04 CSSD · S05 linen · S06 waste · S07 diet and kitchen · S08 ambulance · S09 mortuary · S10 visitors and gate · S11 dashboards, API, commands, demo history, gate.

## Out of this phase
Billing of ambulance trips and diet (needs a Billing catalogue decision) · stock-ledger posting of spare parts and kitchen supplies · procurement of equipment (Phase 15) · asset depreciation (Finance) · vehicle GPS.
