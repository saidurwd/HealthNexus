# Mothers and Babies (MCH) — Module Charter

_Updated in every slice (guideline 7.4). Phase 26 (Bangladesh pack) slice B4 is built. Every figure and the vaccine schedule are examples for the hospital and the national programme to confirm._

- Phase / owner: 26 / **owner to be named by the hospital** (the head of obstetrics with the head of paediatrics)
- Status: built · switchable per organization / hospital (`mch`, depends on core and patient)
- Purpose: follow a pregnancy from booking to delivery, record the delivery and the newborns, and keep a child's routine immunisation card.
- Permission prefix: `mch` (7 permissions). Table prefix: `mch_`. Staff screens `/admin/mch/*`. No API in this slice.
- Owns: pregnancies, antenatal visits, deliveries, newborns, immunisation doses.
- Does NOT own: patients (Patients), birth notification (Births, deaths and police cases - a delivery opens a draft there), inpatient care of mother or baby (IPD / NICU), vaccine stock (Inventory / Pharmacy).
- Rules:
  - A woman has one pregnancy being followed at a time. The expected date is the last period plus 280 days unless given. "Births before" must be fewer than pregnancies.
  - **Alerts** at an antenatal visit are worked out from the readings (blood pressure, haemoglobin, baby's heart rate, protein in the urine with high pressure, any danger sign). A pregnancy is **high-risk** when a risk factor was chosen at booking or the latest visit raised an alert. Thresholds are settings.
  - A delivery ends the pregnancy. For each live birth it opens the baby's own patient record and, if the births module is on, a **draft** birth notification (a second person checks and issues it). A stillbirth opens the notification but no patient record. Nothing is written if any baby's details are wrong.
  - **Immunisation:** every dose has an age; a dose is *due* from its age and *overdue* after a grace period (birth doses after 14 days). A dose is recorded once, with the vaccine batch; a dose given elsewhere is entered "from the card" so it is not repeated.
- Roles: `mch_midwife` (book, visits, delivery, births), `immunisation_nurse` (give and see doses); `doctor`, `surgeon` (all of the above); `staff_nurse`, `senior_staff_nurse`, `charge_nurse` (visits and doses); `hospital_director` (overview).
- Status machines: pregnancy active → delivered | ended (miscarriage, abortion, ectopic, transferred, lost to follow-up).
- Settings keys: `mch.*` override `config/mch.php`.
- Reports: overview (women followed, high-risk, due in 30 days, past date, not seen 6 weeks, children with a vaccine due/overdue, deliveries, caesarean share, low-birth-weight share, antenatal visits, doses given).
- Known limitations & deferred items: docs/decisions/D-MCH-01-mothers-and-babies.md.

## Tables
`mch_pregnancies`, `mch_anc_visits`, `mch_deliveries`, `mch_newborns`, `mch_immunisations`.
