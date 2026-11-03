# D-MCH-01 — Mothers and babies (Phase 26, B4)

**Status:** built 2026-11-03. Guideline Phase 24 pack items: antenatal visit schedule, delivery record, newborn record and immunisation, and the paediatrics immunisation schedule (EPI).

## Decisions
1. **A module of its own** (`mch`, tables `mch_`), reading Patients and writing into Births, deaths and police cases only through its public service (`BirthService::draft`).
2. **One source of the facts.** Recording a delivery is the one place the clinician writes the birth: the baby's patient record and the draft birth notification are made from it. The notification still needs a second person (B2's rule).
3. **The vaccine schedule is NOT the official one.** `config/mch.php` holds a commonly used routine schedule written from general knowledge, flagged in the file as an example. **The national immunisation programme (EPI, DGHS) must supply and confirm every dose, age and interval before this is used for real children.** Doses, ages and the grace period are settings.
4. **Alerts are prompts, not diagnoses.** Blood pressure, haemoglobin, heart-rate and urine-protein thresholds are examples for the obstetrics head to set; an alert is shown on the visit and counts the pregnancy as high-risk, nothing more.
5. **Weeks of pregnancy** are counted back from the expected date (40 weeks before it), so a corrected date corrects the weeks.
6. **Twins:** the delivery form takes one baby; the service takes up to four. The remaining babies are added from the births list in this slice (a multi-baby form is deferred).
7. **Demo history draws no random numbers**, so adding it does not disturb the other modules' history (a lesson from B2).

## Open for the hospital
- The real vaccine schedule and who vaccinates (hospital or upazila health complex); the antenatal visit plan; the high-risk list; the thresholds; which wards record deliveries.
- Whether a baby born in the hospital is registered as a patient at birth (it is, here) or only on first visit.
- Native review of the Bangla wording.

## Deferred / known limitations
- No partograph, no caesarean pathway, no postnatal visits and no mother-to-baby feeding record yet.
- No SMS or portal reminder for a due antenatal visit or vaccine (the due list is the work list).
- No vaccine stock link: the batch is typed. No national immunisation register export (the DGHS FHIR immunisation profile) yet.
- A pregnancy cannot be reopened; a mistaken delivery is not reversible in this slice (it needs a correction rule agreed with the head of obstetrics).
- Growth charts and child development checks are not part of this slice.
