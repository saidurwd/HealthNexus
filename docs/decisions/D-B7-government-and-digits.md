# D-B7 — Government-hospital flows and Bangla digits (Phase 26, B7)

**Status:** partly built 2026-11-03. Two of the three parts are built; the third waits for the owner.

## Built
1. **Bangla digits as a person's own setting (P-02).** `users.bangla_digits` (null follows the hospital's setting `ux.bangla_digits`, true always Bangla digits, false always Latin digits). It applies only when the interface is in Bangla. A page "My display settings" (`/preferences`, any signed-in person) sets it, with the language. **Where it applies:** every figure and date written through `Numbers` (the dashboards and the rate cards). **Not yet:** the older screens that print numbers directly (tables of records, invoices, receipts, prescriptions); converting those is a screen-by-screen job, and a printed invoice or receipt should stay Latin unless the hospital decides otherwise.
2. **OPD ticket and fees (government-hospital flow).** `/rates/opd` (right `rates.opd.view`) shows the registration, consultation and follow-up fees (the codes are a setting, `rates.opd_item_codes`) for the standard patient and for every patient category a price list prices separately; a price of zero reads "Free". It reads Billing and changes nothing. How the poor, the elderly, children, freedom fighters or government employees pay is a matter of categories and price lists in Billing, which the hospital sets up.

## Not built - needs the owner
3. **DGHS returns beyond the national pack.** The Analytics module already has a monthly national return (indicators, codes set after onboarding with DHIS2). Government hospitals also file other returns (for example the monthly hospital statistics forms, bed and OPD/IPD registers, disease returns). **Which returns the pilot hospital actually files, in what format (paper form, spreadsheet, DHIS2 upload), and who signs them is not known**, and building a form from memory would risk a return the Ministry does not accept. Please say which return to start with, and share the form.

## Other notes
- A true "ticket counter" (register a walk-in, take the flat fee, print a numbered ticket, all in one step) is not built: today the registration fee and the consultation fee are Billing charges raised through the existing flow. If the pilot is a government outpatient department with a numbered ticket, that is the next piece to specify with the OPD in-charge.
