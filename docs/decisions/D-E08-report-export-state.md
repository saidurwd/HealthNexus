# D-E08 — Report & Export Service: state (H-07)

Done: `ReportDefinition` contract + `config/reports.php` registry, `ReportExportService` (permission, format and filter
checks, queue, audit EXPORT_REQUESTED / COMPLETED / DOWNLOADED, owner-or-`reporting.export` download, 410 after expiry,
`exports:prune` hourly), `GenerateReportExport` job (re-establishes the requester's user and organization scope; streams
rows in chunks), `CsvExportWriter` (UTF-8 BOM for Bangla in Excel, watermark lines with user and time, sensitive columns
masked unless the definition's sensitive permission is held, spreadsheet-formula neutralising), API
`POST /api/v1/exports`, `GET /api/v1/exports/{id}`, `GET /api/v1/exports/{id}/download` (other organization = 404).
First report: `patients.list` (permission `patients.export`, unmask with `patients.export.sensitive`).

Not done:
- XLSX and PDF. They need packages (approval required by the packages policy) and, for PDF, the Bangla template work of H-08.
- No "Export" buttons or "My exports" screen yet.
- Existing list screens (appointments, invoices, lab orders, pharmacy stock, ...) have no export; add one ReportDefinition
  each — the guideline's "every list screen goes through it" is therefore not yet met.
- The downloaded file is stored on the `local` disk by default; set `REPORT_EXPORT_DISK` to an object-storage disk in production.
