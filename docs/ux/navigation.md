# Left navigation — how it is organised and why

The sidebar follows the working day of a hospital, not the order in which the modules were built. Source of truth: `config/adminlte.php` (`menu`). Guideline 19.6 gives the top-level list; this page is how it is applied. A section header shows only while the person can use something under it (`can`) and at least one of its modules is on (`modules`, handled by `App\Support\ModuleMenuFilter`).

## Sections, in order
| Section | Modules | Who mostly uses it |
|---|---|---|
| (top) Dashboard · My work | personal landing, own roster and leave | everyone |
| **Front desk** | Patients · Appointments | reception, appointment desk |
| **Clinical care** | OPD (Outdoor) · Emergency · IPD (Indoor) · Nursing · OT / Surgery · ICU | doctors, nurses, ward and theatre staff |
| **Diagnostics and pharmacy** | Laboratory (Pathology) · Radiology and imaging · Pharmacy · Blood bank | lab, imaging, pharmacy, blood bank |
| **Billing and insurance** | Billing and cashier · Insurance and corporate · Patient welfare | cashiers, accounts, claims |
| **Supply chain** | Stores and supplies · Purchasing | store, purchase |
| **People and payroll** | Staff records · Duty roster and leave · Payroll | HR |
| **Finance** | Finance | accounts, finance manager |
| **Hospital services** | Facility and support · Quality and safety · Patient relations · Patient portal | facility, quality, patient relations |
| **Insights and connections** | Reports and analytics · AI assistant · Connections | management, analysts, IT |
| **Administration** | Hospital set-up · Master data · Administration · Security and audit · System | administrators |
| **Help** | Help and support (quick-start cards, downtime kit) | everyone (the downtime kit needs its right) |

## Words used in Bangladesh
Staff say **Outdoor** for OPD and **Indoor** for IPD, and **Pathology** for the laboratory, so the menu shows both: *OPD (Outdoor)*, *IPD (Indoor)*, *Laboratory (Pathology)*. The Bangla menu uses the everyday words (বহির্বিভাগ/আউটডোর, অন্তর্বিভাগ/ইনডোর, প্যাথলজি). The two national screens are named for the bodies people know: *National reporting (DGHS / DHIS2)* and *National exchange (DGHS)* (they stay empty until the hospital completes national onboarding). Section names are plain nouns for the work, not module codes.

## Rules for new modules
1. Put the module in the section where its users work; do not add a new top-level group without changing this page.
2. Two levels only (module → page). Deeper navigation is tabs on the module's own page. `NavigationTest` fixes the list of modules that still go one level deeper and fails if a new one does.
3. Every link is a real page (no `#`); the test walks all links against the routes.
4. Give the module `module` and `can`; the section header derives from them. A module not in any section fails `NavigationTest`.
5. Text is English in the menu and has a Bangla entry in `lang/bn.json`; the parity test enforces it.
6. Menu hiding is not security: every route still checks its permission.

## Known deviations (to flatten in a later slice)
Billing and cashier, Laboratory, Radiology and imaging, Pharmacy, IPD, Nursing, OT and ICU still group some pages one level deeper (reports, catalogue, orders). They work and are tested; flattening means giving each a tabbed landing page.

## Bangladesh-specific needs the system does not yet cover (candidates for separate slices)
Not built, so deliberately **not** in the menu (no dead links):
- ~~Patient welfare / charity fund and waivers~~ — built in Phase 26 slice B1 (*Patient welfare*, under Billing and insurance).
- **Birth and death notification and certificates**, with the police (medico-legal) case flow beyond the flag on the mortuary case.
- **Maternity and newborn** (antenatal register, labour room, delivery, newborn) and **immunisation (EPI)**.
- **Outbreak watch** (dengue and other seasonal diseases): daily counts by upazila/union and a line list.
- **Cabin and bed-class price lists** as a hospital-friendly view over the billing price lists.
- **Divisions, districts and upazilas** as the address master (the master data has generic countries and states).
- **Government hospital flows:** OPD ticket (token) fees and the DGHS monthly returns beyond the national pack.
- **Bangla digits and Bangla-first forms/prints** as a user setting.
Already present and reachable from the menu: NID / birth-certificate / passport identifiers, bKash / Nagad / card payments (patient portal), BMDC / BNMC credentials (HR), DHIS2 data sets and DGHS exchange (analytics and connections), BDT and Asia/Dhaka time.
