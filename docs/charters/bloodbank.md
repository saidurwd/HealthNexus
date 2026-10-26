# Blood Bank and Transfusion — Module Charter

_Updated in every slice (guideline 7.4). Phase 13 slices S00–S13 are built; every clinical number is an example awaiting the blood-bank medical officer's approval (P-24, OD-13)._

- Phase / owner: 13 / **owner to be named by the hospital** (blood-bank medical officer)
- Status: built · switchable per organization / hospital (`bloodbank`, depends on patient)
- Purpose: one traceable chain from donor to recipient — donor, donation, components, tests, quarantine, storage, request, sample, cross-match, reservation, issue, bedside check, transfusion, reaction — answerable in both directions in under two seconds.
- Permission prefix: `bloodbank` (see docs/registry/permissions.md). Table prefix: `blood_`. Routes: `/admin/blood-bank/*`. API: none yet.
- Owns: donors, screenings, donations, donor adverse events, blood units and their append-only event trail, unit test outcomes, storage locations and temperature logs, requests, samples, cross-matches, reservations, issues, transfusions, reactions, incidents, recalls, discards.
- Does NOT own: the person (MPI `patients` — a donor is a patient row), the screening and grouping **results** (Laboratory: the tests are lab orders on a "blood donation" encounter, results are read from `lab_results` and never retyped), prices (Billing), alerts (Alert Service), transfusion vital signs (Observation Service, context `transfusion`). Blood is not stock: nothing touches the Stock Ledger.
- Events consumed: `LabReportFinalized` (reads a donation's results). Gateway: `BloodRequestGateway` is bound to `BloodBankRequestGateway` (answers "not enabled" when the module is off).
- Charge events (module BLD): `BLD-XM` cross-match, `BLD-PRBC` / `BLD-FFP` / `BLD-PLT` / `BLD-CRYO` / `BLD-WB` on issue, reversed when an issued unit is taken back.
- Alert categories: blood_unit_blocked, blood_temperature_excursion, blood_expiry_soon, blood_request_urgent, blood_transfusion_reaction, blood_incident, blood_recall_recipient. Housekeeping `blood:check` runs hourly (expiry, lapsed reservations, near-expiry warning).
- Status machines: unit quarantined → available → reserved → issued → transfused (or blocked / expired / discarded; whole blood → processed); request requested → sample_received → ready → partially_issued → issued → completed (or cancelled); transfusion in_progress → completed | stopped_reaction. No expired unit can be reserved, issued or started under any role; a unit leaves the bank once at a time.
- Safety rules enforced in code: a positive screen blocks every bag of the donation and defers the donor for good; a group that disagrees with the donor's file holds the bags; a wrong-patient or discordant sample is rejected and logged; an unknown group needs two separate agreeing samples; an incompatible bag is recorded and cannot be reserved; returns are accepted only inside the time and temperature rule; bedside check needs two different people, the wristband and the bag, and an override needs a reason and opens an incident; a reaction stops the transfusion, alerts blood bank and doctor, opens an incident and holds sibling bags when serious; emergency release only of the universal group, with an authoriser and an incident, and a cross-match is owed afterwards.
- Settings keys: `blood.*` override `config/blood.php` (eligibility, screening tests, components and shelf life, reservation hours, return rule, MTP pack, low-stock levels, alert roles).
- Reports: stock by group and component, low stock, expiring soon, utilization and wastage by component and reason, lookback by donor or bag, recall history, open incidents.
- Known limitations & deferred items: see docs/decisions/D-P13-bloodbank-state.md.

## Tables


