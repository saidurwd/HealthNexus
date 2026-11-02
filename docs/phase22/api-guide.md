# Facility and support services — guide for the facility team and for IT

## Start
1. `php artisan facility:install` adds example diet types, linen types, two mortuary chambers and a few instrument sets. Edit them. Idempotent.
2. Register the equipment (**Facility → Equipment → Add equipment**). Mark which items are **high risk** (ventilators, defibrillators, infusion pumps, monitors, X-ray units): these get calibration blocks. Add maintenance plans and calibration plans with their intervals, and **print each label** — the QR code on it opens the item for any staff member to report a fault.
3. `php artisan facility:check` (scheduled daily at 06:30) opens work orders for plans that fall due, warns about overdue calibrations and ending warranties, withdraws sterile packs past their date, ends diets and passes of patients who went home, and opens a mortuary case for each death.
4. Roles ready to use: `facility_manager`, `biomedical_engineer`, `housekeeping_supervisor`, `housekeeper`, `cssd_technician`, `dietitian`, `kitchen_staff`, `ambulance_dispatcher`, `mortuary_attendant`, `security_guard`, `reception_gate_officer`. Doctors and nurses can order diets and ask for an ambulance; every staff member can report a fault.

## Every day
- **Overview** shows equipment that is down, high-risk equipment that must not be used, work orders and downtime, bed turnaround against the target, sterile batches waiting for release, linen below its level, waste, ambulances, mortuary places, and people inside the hospital.
- **Cleaning**: a released bed appears by itself; the cleaner works through the checklist; after isolation a second person inspects; then the bed becomes available again.
- **Sterile services**: load → start → record the indicator → a **second person** releases → give packs to operations. If a batch is recalled, the list of operations that used it is shown.
- **Mortuary**: a body is received with a second person checking identity; to release it, one person records who takes the body and the papers shown and **another person authorises**.
- **The gate**: type or scan the pass number; the screen says whether the person may enter and why not.

## Read-only API
`GET /api/v1/facility/{overview|work-orders|beds-to-clean}` and `GET /api/v1/facility/assets/{tag}` (**is this equipment usable, and why not**) with a staff token (`Authorization: Bearer ...`) and the header `X-Organization-Id: <hospital id>` (the user must belong to it). Answer: `{success, message, data, meta}`. Permissions: `facility.dashboard.view`, `facility.workorder.view`, `facility.housekeeping.view`, `facility.asset.view`.

## Commands
`facility:install [--organization=ID]` · `facility:check`.
