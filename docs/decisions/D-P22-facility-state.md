# D-P22 — Facility and Support Services: state and decisions

Status at the end of the build: see `docs/phase22/gate-f2-report.md`. The guideline's section 27.2 and the slice table (S00–S11) were the brief. Every interval, threshold, checklist, visiting-hour window and shelf life is an example for the hospital's facility manager to confirm.

## Decisions
1. **One question, one answer: may this device be used?** `AssetService::usable()` (and `reason()`) — false when the device is out of service, under maintenance or retired, or when a high-risk device's calibration is overdue or the last one failed. The steriliser check in CSSD and the ambulance dispatch call it; the read-only API returns it. Other modules ask it; they do not read the table.
2. **Maintenance follows plans.** A plan (preventive, calibration, safety test) with an interval opens one work order a week before it falls due (the daily check); finishing the order moves the plan on. A breakdown takes the device out of use at once and measures downtime. Breakdown and calibration work on **high-risk** equipment is checked by someone other than the engineer before the device returns. A failed calibration takes the device out and opens an urgent order; only a passing calibration (and a checked repair) restores it.
3. **Tasks come from events.** A released bed that IPD puts in `cleaning` makes one cleaning task (unique per allocation) through a listener; Facility never edits IPD tables. When the checklist is done Facility asks IPD's own `IpdBedStatusService` to make the bed available (only if it is still in cleaning). A clean after isolation needs a second person's inspection first. Nothing happens for a hospital that has switched Facility off.
4. **Sterile services are traceable.** A batch is released only after a passing indicator and by someone other than the operator; the packs get a use-by date by method; only a released, unexpired, unused pack can be issued, and issuing records the operation. A recall withdraws unused packs and lists the operations (and patients) that already used one.
5. **Linen is a ledger.** Movements are rows that never change (triggers); where the linen is now is derived; a movement that would take more than is in a place is refused; clean stock under its level warns.
6. **Waste is reconciled.** A manifest collects the unmanifested records up to its date; hand-over refuses a total that differs from its records; confirmation needs the disposal certificate.
7. **Diets.** One active diet per admission (a new order stops the old); the meal list for a ward and meal is made from the active orders and can be asked for again safely; a fasting patient's tray is held and cannot be served; a meal is closed only when every tray is dealt with; orders end when the patient goes home.
8. **Ambulances.** A vehicle that is on a trip, in maintenance or retired cannot be dispatched; a trip's odometer cannot go back; response time is request-to-departure.
9. **Mortuary.** One case per person who died (found from emergency dispositions and the wards, once). Two different staff check identity at receipt; a unit cannot exceed its capacity; release is two steps — the attendant records who takes the body and the papers shown (the number is **stored encrypted** and shown masked), a **second person authorises**; a police case cannot be released until cleared.
10. **Visitors.** Passes are for admitted patients only (judged by the clock, so history can be written up as it was); visitors enter only in the set visiting hours and up to a limit at once; an attendant needs identity papers and only the allowed number stay with a patient; blocked people are refused; passes end with the stay and with the day (the visitor's pass ends at midnight Dhaka time). The gate log records entries, exits **and refusals** and only grows.
11. **QR labels carry a random token, not data.** Scanning opens the device after a staff sign-in; the token reveals nothing about a patient or the hospital.

## Open for the hospital
- Who owns each area (the charter names the roles); the real maintenance intervals, which equipment is high-risk, and the calibration vendors.
- Visiting hours, limits, and the policy for attendants; the contractor and certificate process for waste; the cold-chamber layout and release rules the mortuary actually follows.
- Native review of the Bangla wording (about 440 new strings).

## Deferred / known limitations
- **Spare parts and kitchen supplies are recorded, not posted to the stock ledger** (rule 15 and 5: stock moves only through the ledger; the hookup is a slice with Inventory). Work-order parts can name a stores item id for that later.
- **No billing of ambulance trips or diets** — it needs a Billing catalogue decision; the trip and the diet order carry what Billing would need.
- **Isolation and "diet/NPO" flags are not shown on other modules' screens** (patient banner, ward board): separate slices in IPD/Nursing.
- Depreciation stays in Finance; the register only keeps `fin_asset_id` as a link (nothing sets it automatically).
- Equipment is registered one by one; a CSV import and procurement-to-register hand-off are not built.
- A cleaned bed that IPD already made available by other means is not changed (the task is still finished and its turnaround kept).
- Vehicle tracking (GPS) and the ED→trip automatic link are not built; a trip may point at an ED visit by id.
