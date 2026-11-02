# Searchable dropdowns and pickers

Owner instruction (2026-11-02): every dropdown is searchable, so a person finds the item by typing. Engine: **Tom Select 2.3.1** (Apache-2.0), copied into `public/vendor/tom-select/` so it works on a hospital LAN with no internet (hashes recorded in `tests/Feature/Ux/SearchableSelectTest.php`). Chosen over Select2 (needs jQuery, which the app does not use) and a home-made script.

## What happens by itself
`public/js/searchable-select.js` enhances **every `<select>`** on every page: staff pages and sign-in (AdminLTE plugin `TomSelect` in `config/adminlte.php`) and the patient portal (its own layout loads the same files). Nothing to add to a form.
- The first empty option (`value=""`) is the prompt; a dropdown that is not required also gets a clear (×) button.
- Typing filters instantly (Bangla text too). "No matches found" / "কোনো মিল পাওয়া যায়নি".
- The original `<select>` stays in step: `change` handlers, inline `onchange`, `select.value = …`, form reset, `disabled`, and options another script adds or removes are all followed.
- A required dropdown left empty stops the form and gets the red outline.
- Dropdowns added later (a modal, a new table row) are enhanced; many on one page are prepared a few at a time.
- Opt out of one dropdown with `data-plain` (or a wrapper with it). **Today none does**; a test fails if one appears without being listed there with a reason.

## Long lists: ask the server (`data-remote`)
A dropdown cannot hold every patient. Add `data-remote="/url"` and it asks the server as the person types (`?q=`), twenty at a time; the server answers `[{"value": "...", "text": "..."}]`. `data-remote-depends="[name=patient_id]"` narrows it to what another dropdown holds (a patient's visits) and keeps it shut until that one is chosen.

## Use the pickers, never a typed ID
Nobody remembers an internal ID. Use:
```blade
<x-ui.patient-select name="patient_id" :value="$patient?->id" required class="form-control" />
<x-ui.encounter-select name="encounter_id" depends-on="[name=patient_id]" class="form-control" />   {{-- the patient's visits --}}
<x-ui.admission-select name="admission_id" depends-on="[name=patient_id]" class="form-control" />   {{-- the patient's admissions --}}
```
They send the same numeric ID as before, so the controller is unchanged; the earlier answer (after a validation error) comes back by name. The lookups (`/admin/lookup/patients|encounters|admissions`) return only the person's own hospital, need a right on a screen that picks one (`config/ux.php` `lookup_permissions`), treat `%` and `_` as plain letters, and find by name, number, phone or national ID. `LookupTest` fails if any form asks for a raw `patient_id`, `encounter_id` or `admission_id` again.

## Tests
- PHP: `tests/Feature/Ux/SearchableSelectTest.php` (files unchanged, nothing from the internet, every page type loads them), `LookupTest.php` (the three searches and the forms).
- Real browser (headless Chrome through puppeteer): `npm run test:browser` runs `tests/Js/searchable-select.mjs` (30 checks: typing, Bangla, choosing, server search, dependent dropdowns, required, scripted changes, late and bulk dropdowns). Uses `BROWSERSHOT_CHROME_PATH` or an installed Chrome.

## Upgrading Tom Select
Replace the two files in `public/vendor/tom-select/`, update the hashes in the PHP test and `README.txt`, run both test sets.
