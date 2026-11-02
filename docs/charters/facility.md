# Facility & Support Services — Module Charter

_Updated in every slice (guideline 7.4). Phase 22 slices S00–S11 are built. Intervals, thresholds, checklists, visiting hours and shelf lives are examples awaiting the hospital's facility manager._

- Phase / owner: 22 / **owner to be named by the hospital** (the facility manager, with the biomedical engineer and the housekeeping supervisor)
- Status: built · switchable per organization / hospital (`facility`, depends on core); every staff member can report a broken device, the rest needs a facility right
- Purpose: run the non-clinical services that make a hospital safe and licensable — the equipment register with QR labels, maintenance and calibration, cleaning and bed turnaround, sterile services, linen, biomedical waste, patient diets and the kitchen, ambulances, the mortuary, and visitors and the gate.
- Permission prefix: `facility` (33 permissions). Table prefix: `fac_`. Staff screens `/admin/facility/*`; read-only API `/api/v1/facility/*`.
- Owns: equipment, maintenance plans, work orders and parts, calibrations; cleaning tasks and audits; instrument sets, sterilisation batches and packs; linen types and the movement ledger; waste records and manifests; diet types, orders, meal lists and trays; ambulances, trips and fuel; mortuary units and cases; visitor passes, blocks and the gate log.
- Does NOT own: beds and their status (IPD — Facility calls IPD's own service to make a cleaned bed available), operations (OT — a pack points at the operation by id), fixed-asset money and depreciation (Finance — a link only), stock and purchasing (Inventory — spare parts and kitchen supplies are recorded, not posted), death records (ED, IPD — read), patient banners and bed boards.
- Rules: tasks are made from IPD's `BedReleased` event, never by editing IPD tables; `AssetService::usable()` is the one answer to "may this device be used?" — false when out of service or retired, or when a high-risk device's calibration is overdue or failed; a failed calibration keeps the device out until a passing one; repairs of high-risk equipment and calibrations are checked by a second person; a sterile batch is released only after a passing indicator and by someone other than the operator, and only released, unexpired packs go to an operation; a clean after isolation is inspected by a second person before the bed is released; a manifest equals the records in it; a body is received with a second person's identity check and released only with identity papers and a second person's authorisation (police cases held until cleared); a patient has one active diet and a fasting patient is not served; a visitor is let in only within visiting hours and within the limit, an attendant needs papers, a blocked person is refused; the linen ledger and the gate log only grow (database triggers).
- Hooks: `facility:check` daily at 06:30 (work orders for plans that fall due, calibration and warranty warnings, packs past their date, ended diets and passes, mortuary cases, late cleaning); `facility:install`.
- Status machines: equipment in_service → under_maintenance | out_of_service → in_service | retired; work order open → in_progress → waiting_parts → done → verified | cancelled; cleaning pending → in_progress → done → inspected; batch loading → running → passed | failed → released → recalled; manifest prepared → handed_over → confirmed; trip requested → dispatched → arrived → completed | cancelled; mortuary case pending → stored → release_pending → released; pass active → expired | revoked.
- Settings keys: `facility.*` override `config/facility.php`.
- Reports / KPIs: overview (equipment down, blocked high-risk devices, work orders and downtime, bed turnaround against target, batches to release, linen below level, waste, diets, ambulances, mortuary places, people inside, gate refusals) and the read-only API.
- Known limitations & deferred items: see docs/decisions/D-P22-facility-state.md.

## Tables


