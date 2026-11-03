# D-GEO-01 — The address master (Phase 26, B3)

**Status:** built 2026-11-03. **Owner decision used:** the `velmora` database may be read as the source of the places.

## What it is
The country's administrative geography as reference data: **division → district → upazila → union**. It is national, so it is shared by every hospital and is not scoped to an organization. Tables `geo_divisions`, `geo_districts`, `geo_upazilas`, `geo_unions` (prefix `geo`, registered under Core).

## Decisions
1. **Source and honesty about it.** 8 divisions, 64 districts and 494 upazilas, each with an English and a Bangla name, were exported once from `velmora` (read only; that database is not touched by the product) into `database/data/bd-geography.json`. The file carries no official codes and says to verify it against the BBS list. The export is installed by `geo:install` (also run by the master-data seeder) and is safe to run again: a place is found by its English name under its parent and updated, never duplicated.
2. **Unions have no source.** `velmora` has no unions. The table starts empty and the product says so on the Geography page. A hospital adds the unions its patients come from (Master data → Geography), or imports a list with `geo:import-unions file.csv` or the same form on the page (columns `division,district,upazila,union_en,union_bn[,code]`; a row whose upazila is not found is reported and skipped, never guessed). The demo adds eight unions of Savar upazila as an example. **Open for the owner:** obtain the national union list (BBS / LGD) and import it.
3. **Source spelling.** The names are imported as they came. A few English names look misspelled in the source (for example "Borhan Sddin", "Charfesson", "Doulatkhan"). They are left as found, so the file stays a faithful copy; correct them in `bd-geography.json` and run `geo:install` (it updates by name under the parent, so a renamed place would be added next to the old one - fix by editing the row in the database first, or rebuild).
4. **The address.** `patient_addresses` gains, additively, `division_id`, `district_id`, `upazila_id`, `union_id` (all nullable, set null if a place is removed) and `village_area`. The older free-text `district` and `city` stay: `district` is kept filled with the district's English name when a district is chosen, so the FHIR export, reports and imports that read it keep working. The address form now has four searchable levels, each waiting for the one above, searchable in English and in Bangla.
5. **One chain only.** The places on an address must belong together (a union in its upazila, the upazila in its district, the district in its division); a mismatch is refused. Choosing only the lowest place fills in those above it.
6. **Older addresses.** `geo:link-addresses` links addresses that only have a typed district to the district it names (English or Bangla, any capitals); text that matches no district is left alone and counted.
7. **Where patients come from.** The Geography page counts patients by **home district = the district of the permanent address** (a patient with two permanent addresses is counted under both; a present address is not used). It shows the share of patients with a home district, the districts served, and the upazilas in use that have no unions listed. Totals only; no patient is named. This is the first figure of the planned "catchment" dashboard (D7).
8. **Rights.** Seeing the page: `settings.view`. Adding or importing unions: `settings.update`. The lookup answers anyone who already holds a right on a screen that picks a place.

## Not done (next)
- The warehouse patient dimension still reads `patients.city / state` for "district"; it should read the geo district (and its change should refresh the patient row). Separate slice, because the loader watches `patients.updated_at`.
- The patient API accepts the typed district only; accepting place ids is a small follow-up.
- Official codes (BBS) for the places, and the DHIS2 organisation-unit mapping, once the national system is onboarded.
- Union names in Bangla for the manually added ones.
