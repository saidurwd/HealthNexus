# Phase 12 gate report (Gate F-2) — Emergency Department

Date: 2026-10-26. Status: **built and automatically tested; NOT signed off.**

| Check | Result |
|---|---|
| Slices S00–S12 | built |
| ED acceptance: two staff cannot take one bay | tested (EdSecurityTest, bay locking tests) |
| ED acceptance: no active patient lost from the board; a closed one leaves exactly once | tested (EdSecurityTest, EdDispositionTest) |
| Cross-organization access to every record route | 404/403 tested for all 24 record routes |
| Member with no permissions on every record route | 403 tested |
| Cross-branch read only with break-glass; writes never | tested |
| ED journey 14 (unknown patient → ICU) through the screens | tested (EdJourneyTest) |
| Charges once per source | tested (EdReportingTest) |
| Bangla parity | enforced by BanglaParityTest; wording needs native review |
| Pint, Larastan (no new findings), registry check | see CI |

## Not done / needs people
Clinical sign-off of every example value (see D-P12-ed-state.md), real-user task-time tests (unknown patient registered in < 60 s is not measured with real users), board refresh timing under load, wristband / scanner hardware, penetration test, the 15 platform journeys beyond journey 14.
