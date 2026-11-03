# D-BRAND-01 — Name, logo, favicon, contact lines and e-mail from a settings page

**Where:** Administration → Hospital set-up → *Branding, e-mail and contact* (`/settings/system`). Look: `settings.view`. Change: `settings.update`.

## What can be set
- **General:** system name (browser tab, sidebar, sign-in page, e-mails), hospital name, phone, e-mail, website, address.
- **Logo and icon:** logo (sidebar, sign-in page, letterheads and printouts) and browser icon (favicon). PNG/JPG/WebP logo, 64 px minimum, 1 MB; PNG/ICO favicon, 256 KB. **SVG is refused** — an SVG opened directly can run script on the site's address. Files are stored under a generated name in `storage/app/public/branding`; the old file is deleted when replaced.
- **Footer and support:** a footer line (e.g. registration/licence), support e-mail, phone and page.
- **E-mail:** SMTP (host, port, security, user, password) or "log only", the sender address and name, and a **Send test** button.

## How it works
Saved as settings (`brand.*`, `mail.*`, `hospital.*`) and laid over the config at start-up by `BrandingService::apply()` — so `config('app.name')`, the AdminLTE title and logo, the mail account and sender follow the saved values without any screen having to ask. If nothing is saved, the shipped `.env` values apply; if the settings table is missing (first install), nothing is applied.

## Safety
- The mail password is stored **encrypted** (`Crypt`), flagged sensitive, never shown again (a blank field keeps it; a tick removes it) and **never written to the audit trail**. The test-mail error text has the password blanked out.
- Every change is audited (`SETTINGS_UPDATE`, section and what changed).
- These keys are left off the generic "System settings" page so there is one place to edit them.

## Not done
- No colour theme, login-page picture or per-branch branding (one set for the hospital). Letterheads use the organisation's own logo if it has one, else this logo.
- Timezone, language and date format stay on the generic settings page.
- SMS/WhatsApp gateways are not here (separate set-up with their own credentials).
