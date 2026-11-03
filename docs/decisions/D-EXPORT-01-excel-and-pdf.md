# D-EXPORT-01 — Excel and PDF from every screen

**Status:** built. **Packages:** openspout (Excel) and Browsershot (PDF), both already approved.

## What it does
Every staff screen that shows a table has an **Export** button beside Print: **Excel (.xlsx)** or **PDF**. It is the same address with `?export=xlsx` or `?export=pdf`.

## How
- `ExportPage` middleware runs the screen as usual (same rights, tenant, filters), so nobody can export what they cannot see. It then reads the tables from the page (`PageTables`) and builds the file (`PageExporter`).
- A paged list is followed page by page up to `export.max_pages` (40) and `export.max_rows` (20,000), set in `config/export.php`.
- Excel: one sheet per table, named after its heading; header row in bold with a fill and frozen; numbers are number cells, but codes, phone numbers and anything with a leading zero stay text, and text is never a formula. The top rows say hospital, title, who exported and when, the filters used, and the confidentiality line.
- PDF: A4, landscape when a table has more than 7 columns, repeating table headings, page numbers, same header lines. Chromium prints it, so Bangla is shaped as on screen.
- Every export is written to the audit trail (`EXPORT`: screen, format, rows, filters). Twenty exports a minute per person.
- Not exported: screens for sign-in secrets, tokens and passwords (`export.except`).

## Not done / for the owner
- **No separate "may export" right.** Anyone who can see a screen can export it. A dedicated permission is a small step if the hospital wants it (it would go through the role registry).
- Only tables are exported. Tile numbers and charts on dashboards are not; a dashboard's detail tables are.
- The server needs Chromium and `puppeteer` for PDF (as for receipts). Excel needs nothing extra.
- Screens that already offer their own CSV/Excel report keep them.
