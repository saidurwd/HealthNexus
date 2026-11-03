# Bed and Cabin Rates — Module Charter

_Updated in every slice (guideline 7.4). Phase 26 (Bangladesh pack) slice B6 is built._

- Phase / owner: 26 / **owner to be named by the hospital** (the billing manager)
- Status: built · switchable per organization / hospital (`rates`, depends on core, billing and ipd). **Read only; no tables.**
- Purpose: show what a day in each kind of bed or cabin costs - for the admission desk, the billing office and the family who ask - and warn where a bed would not be charged at all. It is a view over Billing's price lists (guideline P-23); prices are changed there.
- Permission prefix: `rates` (1 permission: `rates.bed.view`). Screen `/admin/rates/beds`.
- Rules: the rate card asks Billing the same two questions the ward service asks when it charges a night - which item prices a day in this bed type (an active, clinically chargeable "bed day" item whose key is the bed type's code; a bed with no type is charged as "general"), and what that item costs for the patient's category on that date (Billing's own price rules: price lists in force, then the item's base price). It therefore shows the real charge.
- Warnings: a bed type that has beds but no item (nights would not be charged), beds with no type and no "general" price, a price of zero, a bed-day item for a bed type that no longer exists. A label typed on a room (`rate_category`) is listed but changes nothing.
- Roles: the rate card right is given to cashier, receptionist, IPD coordinator, charge nurse, ward manager and hospital director.
- Estimator: days × the day rate for a bed type and patient category (1 to 365 days); "bed only".
- Known limitations: docs/decisions/D-RATES-01-bed-and-cabin-rates.md.
