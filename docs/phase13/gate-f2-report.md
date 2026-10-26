# Phase 13 gate report (Gate F-2) — Blood Bank and Transfusion

Date: 2026-11-01. Status: **built and automatically tested; NOT signed off.**

| Check | Result |
|---|---|
| Slices S01–S13 | built |
| Unit cannot leave quarantine before every test passed | tested |
| Positive screen blocks every bag and defers the donor for good | tested |
| Group disagreeing with the donor's file holds the bags | tested |
| Results come from the Laboratory (no retyping) | tested end to end through the lab services |
| Temperature excursion alerts and blocks the affected bags | tested |
| Wrong-sample rejection; two-sample rule; discordant samples | tested |
| Incompatible bag recorded, never reserved; reservation lapses | tested |
| Unit cannot be issued twice; an expired unit never | tested |
| Return rule (time and temperature) and charge reversal | tested |
| Two-person bedside check; override needs reason + incident | tested |
| Reaction stops transfusion, alerts blood bank and doctor, incident, sibling bags held | tested |
| Lookback donor → recipients and unit → donor under 2 s; recall | tested |
| Emergency release and massive transfusion protocol | tested |
| Gateway real adapter; "not enabled" when module off | tested |
| Cross-organization access (404/403), no-permission refusal on every record route | tested |
| Demo history (6 months) and per-module users | tested (DemoHistoryTest, DemoAccessTest) |
| Bangla parity | enforced by BanglaParityTest; wording needs native review |

## Not done / needs people
See D-P13 (clinical sign-off of every example value, deferred items, scanner hardware, real-user tests, pen test).
