# Owner decisions — 25 Oct 2026

| Question | Decision | Consequence |
|---|---|---|
| OD-01 / OD-04 Deployment and pilot | **Cloud**; pilot hospital **Premier Health Center**; **all modules** switched on | Cloud hosting: backups must leave the VM (object storage), `BACKUP_PASSPHRASE`, HTTPS, MySQL managed or on a VM with off-site copy; set `BACKUP_DISK` to an object-storage disk. All Phases 0–11 modules on; Phases 12+ appear as they are built. Residency/data-protection advice (OD-02) still matters for the choice of cloud region. |
| Packages | **XLSX, Chromium PDF, Larastan approved** | Added `openspout/openspout` (XLSX exports), `spatie/browsershot` + `puppeteer` (PDF through Chromium — Chromium/Chrome must be installed on the server), `larastan/larastan` (level 5, baseline of 730 existing findings) |
| Hospital layer | **One default hospital per organization for now** | No change to the structure built in Phase H |
| Next | **Finish H-12 (public IDs) first, then Phase 12 (Emergency)** | In progress |

Open from the guideline (not answered yet): OD-02 data-protection duties, OD-03 VAT, OD-05 finance build vs integrate, OD-06 payment aggregator, OD-07 SMS gateway, OD-08 PACS, OD-09 AI hosting, OD-10 national exchange, OD-11 commercial model, OD-12 triage scale and clinical content owners.
