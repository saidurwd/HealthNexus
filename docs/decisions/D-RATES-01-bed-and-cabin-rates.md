# D-RATES-01 — Bed and cabin rates (Phase 26, B6)

**Status:** built 2026-11-03. Guideline P-23 (cabin and bed-class price lists as a view over Billing's price lists).

## Decisions
1. **A view, not a second source of prices.** No tables and no editing. The prices live in Billing's price lists and items; the card calls Billing's own pricing service, so the card and the bill cannot disagree.
2. **What decides a bed's price is its bed type** (the item key), not the room. The room's typed "rate category" label has never priced anything; the card lists such rooms so nobody believes it does. Pricing by room class or VIP flag would be a change to the ward and billing services (a separate, owner-approved slice).
3. **Columns are the patient categories** that some price list in force actually prices separately, plus "Standard". A category with no row falls back to the standard price in Billing, which the card shows as it is.
4. **Gaps are shown loudly**, because the failure is silent: a night in a bed type with no billing item makes no charge at all.
5. **No per-day bed charges for the very short stay or the half day** are modelled; Billing charges a whole day per night as today.

## Open for the hospital
- The rate categories (corporate, staff, government employee, freedom fighter ...) and the discounts that apply; the cabin classes and their prices; whether a cabin should be priced by room rather than bed type.
- Native review of the Bangla wording.

## Deferred / known limitations
- Room-class pricing, deposits by bed class, and package rates for a stay are not shown.
- Taxes (VAT) on room charges are shown as Billing returns them in the unit price; the card does not break them out.
- The estimate counts the bed only.
