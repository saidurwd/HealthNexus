# Security baseline and penetration-test booking (pilot)

Target: OWASP ASVS Level 2 (guideline 16.3). Status is what the code does today.

| Control | Status |
|---|---|
| Injection — query builder with bindings | Yes (Eloquent/Builder everywhere; raw SQL in backups/reports uses fixed text or bindings) |
| XSS — Blade escaping | Yes. Stored document templates cannot run code or output raw HTML (only generated `*_svg` images) |
| CSRF | Laravel default for web routes; Sanctum tokens for the API |
| IDOR | Organization scope, policies, and public ULIDs for patients, encounters, appointments. **Open:** other record types still use numeric ids in URLs (decision D-E03) |
| Mass assignment | Explicit `$fillable` / guarded models |
| File upload | Type, size validation, private disk. **Open:** antivirus scan (ClamAV) |
| Secrets | `.env` only. Database password and backup passphrase are handed to child processes through the environment, never the command line |
| Dependencies | `composer audit` runs in CI. **Open:** `npm audit` job; monthly update routine |
| Security headers | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS on HTTPS. **Open:** a strict Content-Security-Policy (needs AdminLTE inline scripts reworked) |
| Logging | Request id on every response and error screen. **Open:** review that no log line contains full national IDs or clinical text |
| Errors | Generic message plus a reference id for the user; details in the log |
| Sessions / MFA | MFA exists; `pilot:readiness` fails if an administrator has no MFA; set `SESSION_SECURE_COOKIE=true` on HTTPS |
| Exports | Permission, scope, watermark, masked sensitive columns, formula-safe, expire after 24 h |
| Backups | Encrypted (AES-256) when `BACKUP_PASSPHRASE` is set; checksum verified before a restore |
| Audit | Append-only audit trail of exports, imports, prints, break-glass |

`php artisan pilot:readiness` checks the machine-checkable items above (debug off, HTTPS URL, real mail, queue not sync, secure sessions, encrypted backups, MFA for administrators).

## Penetration test — booking sheet
Book it **before** go-live; the test is on a staging copy built with `demo:build`, never on real patient data.

| Item | Fill in |
|---|---|
| Tester / company, contact | |
| Scope | Web application (all `/admin/*` screens), REST API `/api/v1/*`, login/MFA/password reset, file upload and download, exports, imports, document printing |
| Out of scope | The hospital's network, third-party gateways |
| Environment and dates | Staging URL ____ ; window ____ ; test users: one per role (create with `demo:build`) |
| Rules of engagement | No denial-of-service; stop and call if real data is found |
| What we give them | This baseline, the permission registry (`docs/registry/permissions.md`), the route list (`php artisan route:list`), one account per role |
| Report due / retest | ____ ; fixes for High/Critical before go-live, Medium within 30 days |
| Findings tracker | link / sheet |

Ask the tester to check especially: tenant isolation (an organization-A user against organization-B ids), every `Authorize`-less API route, ID guessing on records that still use numeric ids, break-glass abuse, the import/export paths, and document-template editing.
