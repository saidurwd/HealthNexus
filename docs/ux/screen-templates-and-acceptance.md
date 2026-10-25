# UX standard in practice (H-14)

Source: guideline chapter 19. This page says what exists in code, which template each current screen follows, and how to run the
acceptance test with real users. Components live in `resources/views/components` (`<x-patient-banner>`, `<x-ui.status-badge>`,
`<x-ui.empty-state>`, `<x-ui.kpi-tile>`); the status palette and role homes are in `config/ux.php`.

## The seven templates and where they apply today
| Template | Current screens |
|---|---|
| T1 Dashboard | `/dashboard`, `/admin/{billing,lab,radiology,pharmacy,ipd,nursing,ot,icu}` module dashboards |
| T2 Worklist / queue | `/admin/queue`, `/admin/lab/worklist`, `/admin/radiology/worklist`, `/admin/pharmacy/orders`, `/admin/nursing/tasks` |
| T3 List + filter | patients, appointments, encounters, invoices, payments, catalogues (master data) |
| T4 Form / wizard | patient registration, appointment booking, admission request, billing item |
| T5 Record 360 | patient profile, encounter, admission, invoice, ICU episode, surgery |
| T6 Board | IPD bed board (ED tracking board and OT schedule board arrive with their phases) |
| T7 Print preview | `/admin/documents/{type}/{reference}` (receipt, prescription, wristband) |

## What H-14 put in place
- Role home: login lands on the user's worklist (`RoleHome`): receptionist/doctor → queue, nurse → my patients, pharmacist → prescriptions,
  cashier → cashier session, lab → worklist, radiology → radiology dashboard, admission officer → IPD, OT staff → OT; managers keep the dashboard.
  A role with no permission or whose module is off falls back to the dashboard.
- Patient banner on 12 clinical/operational record screens (consultation, encounter, clinical workspace, admission, nursing, ICU, OT,
  pharmacy order, lab order, radiology order, invoice, appointment): name, age/sex, MRN, blood group, **every active allergy (severe first)**,
  every active alert, current bed.
- One status badge (colour + icon + text) replacing 88 hand-written badges; unknown statuses degrade to a neutral badge with their own name.
- `?` shows the keyboard shortcuts; `/`, `Ctrl+K`, `F3` jump to search; forms marked `data-enter-next` (patient registration) move on with Enter.
- Real error screens (404, 403, 500, 419 …) with a reference id and "tell support this reference"; request ids now exist for every request,
  including 404s.

## Not yet done
- Not every list/worklist uses the badge: about 70 badges are not status-driven (counts, flags) and were left alone.
- Auto-refresh of worklists, saved filters, column chooser, "What's new", context help: not built.
- axe accessibility run in CI, 1366×768 / 10-inch tablet visual check, touch-target sizes: not run.
- **Task-time test with 3 real users per role for the five busiest screens** (registration, appointment queue, OPD consultation,
  billing counter, pharmacy dispensing) — this needs people, not code. Use the sheet below.

## Acceptance sheet (copy per screen and per test day)
```
Screen: ______________  Role: __________  Date: ________  Facilitator: ________
User 1 / 2 / 3     Task                                   Target     Time     Clicks   Help needed?   Errors
                   register a returning patient           ≤ 30 s
                   register a new patient                 ≤ 90 s
                   book an appointment                    ≤ 30 s
                   simple OPD prescription                ≤ 60 s
                   dispense a simple prescription         ≤ 45 s
                   collect a cash payment                 ≤ 30 s
                   record a set of vitals                 ≤ 40 s
[ ] Fits one template   [ ] 1366×768 and 10" tablet   [ ] Bangla and English complete   [ ] Keyboard-only path works
[ ] Status colour + icon + text   [ ] Loading / empty / error states   [ ] Patient banner on clinical pages   [ ] Print layout approved
Notes / confusing words (write the word the user used instead): ______________________________
```
