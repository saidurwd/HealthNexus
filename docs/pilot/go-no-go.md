# Pilot go / no-go sheet (Appendix F-4)

Run `php artisan pilot:readiness` first. Anything marked **auto** is checked by that command; everything else needs a person to tick it and sign.

```text
PEOPLE   [ ] Super-users named and trained            [ ] Role cards printed (Administration → Quick-start cards)
         [ ] Support contacts + escalation sheet (docs/pilot/support-and-escalation.md)
DATA     [ ] Masters loaded through Data import (departments, services/prices, drugs, tests, users, beds)
         [ ] Legacy import reconciled with the error file  [ ] Opening stock and balances signed  [ ] Number series set
         [ ] Print templates approved — Bangla check by a native reader
TECH     [ ] Hardware tested: PCs, printers (A4/A5/80 mm/labels), scanners, UPS   [ ] Network and Wi-Fi coverage
         [auto] Debug off · production environment · HTTPS · real mail · queue not "sync" · secure sessions
         [auto] Backups encrypted, last backup < 26 h  [auto] Restore drill passed AND signed off within 35 days
         [auto] Scheduler heartbeat  [auto] Failed jobs = 0  [auto] Disk space  [auto] Administrators use MFA
         [auto] Migrations up to date  [auto] A training organization exists
         [ ] Monitoring alerts reach a person's phone  [ ] Penetration test done or booked (docs/pilot/security-baseline.md)
         [ ] Production module flags set (Phases beyond 11 off)
SAFETY   [ ] Downtime forms printed and drilled   [ ] Downtime census tested   [ ] Break-glass log reviewed
         [ ] Critical alert path tested end to end (lab critical → notification → acknowledge)
LEGAL    [ ] Consent texts approved  [ ] Privacy notice displayed  [ ] Data-protection checklist reviewed by counsel
GO/NO-GO [ ] Management sign-off  [ ] Rollback plan (paper + export)  [ ] Hypercare roster for the first two weeks
```

Decision: GO / NO-GO     Date: ________     Signed: ______________ (management)  ______________ (clinical lead)  ______________ (IT)
