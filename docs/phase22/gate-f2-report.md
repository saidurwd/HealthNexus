# Phase 22 — Facility and Support Services: Gate F-2 report

| Check | Result |
|---|---|
| Slices S00–S11 | built |
| Whole suite | 1,436 tests: 1,434 passed on the full run. One failure was this phase's own (a rounding comparison on a waste manifest that SQLite showed and MySQL did not; fixed — demo history and all facility tests then passed: 35 tests, 459 assertions). The other, `HrHistoryTest`, is **not from this phase**: it fails the same way on the committed Phase 19 baseline and depends on the calendar date. It is in HR (rule 15) and is reported separately. Facility tests: 33 in `tests/Feature/Facility/*` (equipment, services, screens and API) |
| Pint · phpstan (level 5, no baseline entries) · `registry:export --check` | clean · 0 errors · clean |
| Bangla parity | enforced by `BanglaParityTest`; 436 new strings — **wording needs native review** |
| MySQL | both migrations apply on MySQL and the six-month facility history ran there on top of the full history. One defect was found only on MySQL and fixed: the equipment status column was too narrow for `under_maintenance`; a check of every other status width found one more (mortuary `release_pending`) |

## Scratch MySQL build (six months)
62 pieces of equipment (QR tokens unique), 124 work orders (29 calibrations, 2 failed; 3 items still down), 104 sterile batches with 44 packs used in operations and 1 batch recalled after 7 had been used, 3,372 linen movements, 850 waste weighings in 26 manifests, 342 diet orders and 126 meal lists served, 334 ambulance trips (14 cancelled), 2 bodies received by two people and released by two, 101 visitor and attendant passes with the gate log and a refusal.

Integrity queries on that database, all **0**: equipment that is down with no open order · repair checked by its own engineer · high-risk repair not checked while the device is back in use · batch released by its operator · released batch without a passing indicator · pack used from an unreleased batch, after its use-by date, or before its batch was released · manifest that differs from its records · two active diets for one admission · active diet for a patient who went home · odometer going back · vehicle on a trip with no trip under way · body released by the person who authorised it · body received without a second person · police case released while not cleared · unit over capacity · two attendants for one patient · active pass for a discharged patient · visitor identity number stored in the clear.

## Behaviour that is tested
| Rule | Status |
|---|---|
| Equipment gets a tag and a random QR token; the label carries a QR code; another hospital's equipment is not found | tested |
| A breakdown takes high-risk equipment out of use; the repair is checked by someone other than the engineer before it returns; a low-risk repair needs no check; plans open an order before they fall due and move on | tested |
| A failed calibration keeps the device out of use until a passing one; an overdue calibration blocks only high-risk devices | tested |
| A released bed in cleaning makes exactly one task (from IPD's event); a finished checklist asks IPD to make the bed available; an isolation clean is inspected by a second person first; a hospital without the module gets nothing | tested |
| Sterile batch: only a passed batch released by a second person gives packs; a pack is used once, before its date, from a released batch; a failed or recalled batch is traced to the operations | tested |
| Linen ledger tracks where it is, refuses the impossible and cannot be changed by SQL; waste manifests add up and cannot be confirmed before hand-over | tested |
| One active diet per patient; a meal list is made from the orders and is safe to ask again; a fasting patient is not served; diets end at discharge | tested |
| A busy or in-maintenance ambulance cannot be sent; odometers go forward | tested |
| A death gets one mortuary case; identity is checked by two people; a unit has a limit; release needs papers, a cleared police case and a second person; the paper number is stored encrypted and shown masked | tested |
| Visitors only in visiting hours and within the limit; an attendant needs papers; blocked people are refused; refusals are logged; passes end with the stay; the gate log cannot be changed | tested |
| Screens open for the super user and are refused without rights; any staff member can report a fault; the read-only API needs a login, the right and the header and answers "may this be used?" | tested |

## Open for the hospital
Who owns each area, real maintenance and calibration intervals, which equipment is high-risk, visiting hours and limits, the waste contractor process, native review of Bangla. See `docs/decisions/D-P22-facility-state.md` (stock posting of spare parts, billing of trips and diets, and showing isolation/diet flags on other screens are separate slices).
