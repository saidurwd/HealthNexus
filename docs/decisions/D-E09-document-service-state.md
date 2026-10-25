# D-E09 — Document & Print Service: state (H-08)

Done: `DocumentService::render()` (permission check, org-scoped lookup, 404 for another organization, language `en` / `bn` /
`en+bn` through `lang/{en,bn}/documents.php`, hospital letter-head, QR (simple-qrcode, SVG) and Code 128-B barcode (own encoder),
optional Bangla numerals via setting `documents.bangla_numerals`, reprints of financial documents need a reason and print
"DUPLICATE COPY #n", every print written to `document_prints` + audit `PRINT`), `SafeTemplate` (admin-editable templates
with no code execution; raw output only for `*_svg` variables), versioned DB overrides (`saveTemplate`), shipped templates
for receipt (80 mm), prescription (A5) and patient wristband (label), web route `GET /admin/documents/{type}/{reference}`.

Output is HTML with `@page` rules, printed by the browser. NOT done:
- Server-side PDF and the "Bangla PDF test passes on all templates" criterion — blocked on the PDF engine decision
  (Browsershot needs Chromium; Gotenberg is a separate service). Open question to the owner.
- Self-hosted Noto Sans Bengali font file (no network access when this was built); the font stack falls back to system fonts.
- Templates for invoice (A4), lab report, radiology report, consent forms, discharge summary, payslip; only the three above exist.
- Admin screen to edit templates (the API/service exists), print buttons on the existing screens, QR verification URL.
- Code 128 output was checked against the published symbol table and checksum rule, not yet with a physical scanner
  (H-09 acceptance).
- Bangla wording in `lang/bn/documents.php` needs review by a native speaker.
