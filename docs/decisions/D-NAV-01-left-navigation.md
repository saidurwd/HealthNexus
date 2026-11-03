# D-NAV-01 — Left navigation reorganised

Decided on the owner's instruction (2026-11-02): "build the left nav professionally and think about Bangladesh perspectives".

## Decisions
1. **Sections by the working day** (reception → care → diagnostics/pharmacy → billing → supply chain → people → finance → services → insights → administration → help), not by build order. Detail in `docs/ux/navigation.md`.
2. **Headers hide themselves**: each carries the permissions and modules of what is under it; `ModuleMenuFilter` now understands `'modules'` (a header stays while any of them is on).
3. **Local words beside the English ones**: *OPD (Outdoor)*, *IPD (Indoor)*, *Laboratory (Pathology)*, *Billing and cashier*, *Hospital set-up*, *National reporting (DGHS / DHIS2)*, *National exchange (DGHS)*. Page titles of the two national screens follow. 22 new Bangla entries.
4. **Defects removed**: a "Hospital Dashboard" link that went to `#`; the quick-start cards and downtime kit were buried in Administration (admin-only) — they are now in *Help and support* for everyone (the downtime kit still needs its right).
5. **Guarded by a test** (`tests/Feature/Ux/NavigationTest.php`): section order, every module under a section, every link a real route, no new third level, local words present and translated, and a front-desk user sees the front desk and Help but not finance or administration.
6. **Not done on purpose**: no menu entries for features that do not exist (welfare fund, birth/death certificates, maternity, EPI, outbreak watch, upazila address master). They are listed in the navigation page as candidate slices.

## Open for the hospital
Which words your staff really use (Outdoor/Indoor, Pathology, Cabin), whether government-hospital flows (OPD ticket, DGHS returns) matter for the pilot, and the order of the candidate slices.
