# D-E11 — Bangla localization: approach and state (H-10)

## Approach (differs from guideline 19.4 wording)
Guideline 19.4 says "every string lives in lang/en and lang/bn with matching keys". With about 4,200 literal strings in 475
views that would mean inventing and hand-maintaining thousands of keys. Instead we use Laravel's **source-string JSON
translation**: the English text is the key (`__('Save')`), `lang/bn.json` holds the Bangla, and a missing translation shows
the English. The CI intent is kept: `tests/Feature/Localization/BanglaParityTest.php` fails when any `__('...')` literal in views,
app code, the menu or a notification template has no Bangla value. PHP group files (`lang/*/ui_core.php`, `ui_patients.php`,
`doc_labels.php`) must exist in both languages with identical keys. They are named so they can never equal a JSON key on a
case-insensitive file system (a `patients.php` group collided with the word "Patients" on macOS).

## Done
- `php artisan lang:extract [--apply]` wrapped 4,173 plain text nodes / attributes in 372 views with `__()`; all views still compile and
  all page tests pass. Run it again after adding views; it is conservative and only rewrites text that stands alone between tags.
- `lang/bn.json`: 2,406 strings translated — all screens' text, the sidebar menu (162 items), 360 success messages, 41 notification
  templates (delivered in each recipient's own language, `users.locale`), Laravel validation / auth / password messages.
- Per-user language switch already existed (`LocaleController`, `users.locale`, session); verified by tests.
- Receipts, prescriptions and wristbands print in Bangla, English or both (H-08).

## Not done
- About 100 headings of the form "Appointment {{ $x }}" / "Invoice {{ $number }}" mix text and data and were left unwrapped by
  the extractor; they stay English. Error messages written inside controllers/services (not `->with('success', …)` flashes) and
  `->withErrors(…)` texts are also still English.
- Bangla wording was written by an AI assistant: **a native Bangla speaker (preferably a nurse and a cashier) must review it**
  before go-live. Medical abbreviations (BP, ICU, OT, MRN, NID) are intentionally kept per guideline 19.4.
- Self-hosted Bangla web font file (no network when built) — the CSS uses a system font stack that includes
  Noto Sans Bengali, Kohinoor Bangla and Nirmala UI.
- "Bangla-only user can complete the 15 critical journeys" has not been run by a person; no SMS gateway exists yet, so Bangla SMS is
  covered at the template level (the same notification templates).
- Dates and numbers: the optional Bangla numerals exist only for printed documents (`documents.bangla_numerals`).
- Arabic (`lang/ar/ui_core.php`) is unchanged and not covered by the parity rule.
