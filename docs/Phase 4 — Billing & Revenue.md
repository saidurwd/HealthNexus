Phase 4 — Billing & Revenue
1. Purpose
Phase 4 establishes the HMS financial transaction layer for:
- OPD billing
- Consultation fees
- Procedures
- Diagnostic orders/charges
- Medicines/other chargeable items
- Admission-related charges prepared for future IPD
- Discounts
- Taxes/VAT
- Insurance
- Corporate billing
- Payments
- Receipts
- Refunds
- Credit/dues
- Cashier operations
- Revenue reporting
- Financial audit
It should become the financial foundation for later:
- IPD
- Laboratory
- Radiology
- Pharmacy
- OT
- ICU
- Insurance
- Corporate billing
1. Architecture
The overall HMS flow becomes:
Phase 1
Patient / MPI
       │
       ▼
Phase 2
Appointment / Scheduling
       │
       ▼
Phase 3
Clinical Encounter / EMR
       │
       ├──────────────┐
       ▼              ▼
Clinical Service   Prescription
       │
       ▼
Phase 4
Billing & Revenue
       │
       ├── Charge
       ├── Pricing
       ├── Invoice
       ├── Discount
       ├── Tax
       ├── Payment
       ├── Receipt
       ├── Refund
       └── Revenue
Future modules connect to billing:
Laboratory ───────┐
Radiology ────────┤
Pharmacy ──────────┤
IPD ───────────────┤
OT ────────────────┤
ICU ───────────────┤
Consultation ──────┤
Procedures ────────┤
                   ▼
             Billing Engine
                   │
          ┌────────┼────────┐
          ▼        ▼        ▼
       Invoice   Payment   Refund
          │
          ▼
       Revenue
2. Critical Design Principles
Billing must be independent from clinical documentation
For example:
Doctor creates a consultation → billing creates a charge.

The doctor should not directly edit the invoice.
Invoice ≠ Payment
An invoice represents an amount due.
A payment represents money received against that obligation.
One invoice can have:
- One payment
- Multiple payments
- Partial payment
- Multiple payment methods
Charge ≠ Invoice
A charge represents a billable service/event.
Multiple charges can be consolidated into one invoice.
Receipt ≠ Invoice
An invoice says:
You owe BDT 5,000.

A receipt says:
We received BDT 3,000.

3. Phase 4 Scope
Implement:
1. Billing Dashboard
2. Chargeable Items
3. Service Catalog
4. Service Categories
5. Price Lists
6. Price List Versions
7. Hospital/Branch-specific Pricing
8. Provider-specific Pricing where required
9. Patient Billing
10. Charge Generation
11. Invoice Generation
12. Invoice Lifecycle
13. Invoice Items
14. Discounts
15. Taxes/VAT
16. Payment Methods
17. Payment Collection
18. Partial Payments
19. Advance Payments
20. Receipts
21. Refunds
22. Credit/Dues
23. Cashier Sessions
24. Cash Drawer Management
25. Corporate Billing Foundation
26. Insurance Billing Foundation
27. Financial Adjustments
28. Revenue Reporting
29. Billing Audit
30. Financial Document Generation
31. Billing API
32. Role-based access
33. Multi-hospital financial scope
34. Accounting integration foundation
4. Service Catalog
Create a central catalog of billable services/products.
Examples:
- Doctor Consultation
- Follow-up Consultation
- Registration Fee
- ECG
- X-Ray
- Ultrasound
- CBC
- MRI
- CT Scan
- Procedure
- Dressing
- Room Charge
- Nursing Charge
- Ambulance
- Medical Certificate
- Other Services
Future modules should be able to register their chargeable services.
Suggested:
billing_items
Fields:
id
organization_id
item_code
item_type
category_id
name
description
unit
is_taxable
tax_category_id
is_active
effective_from
effective_to
created_by
updated_by
created_at
updated_at
Item types:
- Service
- Product
- Procedure
- Consultation
- Diagnostic
- Room
- Other
Do not hardcode billing items into controllers.
5. Service Categories
Examples:
Consultation
Diagnostics
Laboratory
Radiology
Procedures
Pharmacy
Room
Nursing
Administrative
Other
Use configurable categories.
6. Pricing Engine
Pricing should be separated from the service catalog.
Example:
Consultation
    ↓
Price List
    ↓
General Physician = BDT 1,000
Specialist = BDT 1,500
Professor = BDT 2,000
Support:
- Organization pricing
- Hospital pricing
- Branch pricing
- Department pricing
- Provider pricing
- Patient category
- Corporate pricing
- Insurance pricing
- Effective dates
7. Price Lists
Suggested:
billing_price_lists
id
organization_id
hospital_id
branch_id
name
code
currency
status
effective_from
effective_to
priority
created_by
created_at
updated_at
Price list items:
billing_price_list_items
id
price_list_id
billing_item_id
unit_price
minimum_price
maximum_price
tax_included
effective_from
effective_to
created_at
updated_at
Never overwrite historical prices used by existing invoices.
Use price versions/effective dates.
8. Patient Billing Categories
Support configurable patient categories such as:
- General
- Corporate
- Insurance
- Employee
- Dependent
- VIP
- Government
- Charity
- Other
This should influence pricing and payment responsibility.
9. Charge Generation
Charges can originate from:
Phase 3
- Consultation
- Procedure
- Clinical order
Future modules
- Laboratory
- Radiology
- Pharmacy
- IPD
- Room
- Nursing
- OT
- ICU
Create:
billing_charges
Suggested fields:
id
organization_id
hospital_id
branch_id
patient_id
encounter_id
billing_item_id
source_type
source_id
provider_id
department_id
quantity
unit_price
gross_amount
discount_amount
tax_amount
net_amount
status
charged_at
created_by
created_at
updated_at
Possible statuses:
- Pending
- Billed
- Cancelled
- Refunded
- Adjusted
10. Source Traceability
Every charge should know where it came from.
Example:
source_type = Encounter
source_id = 100234
or:
source_type = ClinicalOrder
source_id = 88452
This is critical for auditing.
A billing administrator should be able to answer:
Why was this patient charged BDT 1,500?

The system should trace:
Invoice
→ Invoice Item
→ Charge
→ Clinical Service/Encounter
→ Provider
11. Invoice
Create:
billing_invoices
Suggested fields:
id
organization_id
hospital_id
branch_id
invoice_number
patient_id
encounter_id
invoice_type
status
currency
subtotal
discount_amount
tax_amount
rounding_amount
grand_total
paid_amount
due_amount
invoice_date
due_date
billing_party_type
billing_party_id
notes
created_by
finalized_by
finalized_at
created_at
updated_at
Invoice types:
- OPD
- IPD
- Diagnostic
- Pharmacy
- Procedure
- Corporate
- Insurance
- Advance
- Other
12. Invoice Number
Use a concurrency-safe sequence.
Example:
INV-2026-00000125
Never generate using:
Invoice::count() + 1
Invoice numbers must:
- Be unique
- Never be reused
- Never change after finalization
13. Invoice Items
Create:
billing_invoice_items
id
invoice_id
billing_item_id
charge_id
description
quantity
unit_price
gross_amount
discount_amount
tax_amount
net_amount
created_at
updated_at
The invoice should preserve the price used at the time of billing.
Do not dynamically recalculate old invoices from today's price list.
14. Invoice Lifecycle
Recommended:
Draft
  ↓
Pending
  ↓
Finalized
  ↓
Partially Paid
  ↓
Paid
Other states:
Cancelled
Refunded
Written Off
Only authorized users can:
- Finalize
- Cancel
- Adjust
- Refund
- Write off
A finalized invoice should not be freely editable.
15. Discounts
Support:
- Percentage discount
- Fixed amount
- Item-level discount
- Invoice-level discount
- Promotional discount
- Corporate discount
- Patient-category discount
- Authorized manual discount
Discount approval may depend on:
- User
- Role
- Amount
- Percentage
Example:
0–5%       → Cashier
5–15%      → Billing Supervisor
>15%       → Finance Manager
These thresholds should be configurable.
16. Discount Audit
Every manual discount must capture:
- User
- Reason
- Original amount
- Discount amount
- Percentage
- Approval if required
- Timestamp
Never allow a cashier to silently reduce a bill.
17. Taxes / VAT
Design the billing engine to support configurable taxes.
Example:
VAT
Tax percentage
Tax category
Tax-inclusive / tax-exclusive
Effective date
Do not hardcode a tax rate into application logic.
Because Bangladesh tax rules can change, tax configuration must be data-driven.
18. Payment Methods
Support:
- Cash
- Card
- Bank Transfer
- Mobile Financial Service
- Online Payment
- Cheque
- Insurance
- Corporate Credit
- Advance
- Other
Payment method configuration should be extensible.
19. Payments
Create:
billing_payments
Suggested:
id
organization_id
hospital_id
branch_id
payment_number
patient_id
invoice_id
payment_method_id
amount
currency
transaction_reference
payment_date
status
received_by
cashier_session_id
notes
created_at
updated_at
Statuses:
- Pending
- Completed
- Failed
- Cancelled
- Refunded
20. Partial Payment
Example:
Invoice:
BDT 10,000
Patient pays:
BDT 4,000
Invoice becomes:
Paid: BDT 4,000
Due: BDT 6,000
Status: Partially Paid
Then:
Second payment: BDT 6,000
Invoice:
Paid
Due: BDT 0
Never modify the original payment to represent subsequent payments.
21. Receipt
Every successful payment should generate a receipt.
Example:
RCT-2026-00000452
Receipt should contain:
- Receipt number
- Patient
- MRN
- Invoice
- Amount
- Payment method
- Transaction reference
- Cashier
- Hospital/branch
- Date/time
Receipt must be traceable back to payment and invoice.
22. Advance Payments
Support patient advances.
Example:
Patient deposits:
BDT 20,000
Later:
Invoice = BDT 8,000
Advance applied = BDT 8,000
Remaining advance = BDT 12,000
Create an advance ledger rather than simply changing a balance field.
23. Refunds
Create:
billing_refunds
Support:
- Full refund
- Partial refund
Fields:
id
refund_number
payment_id
invoice_id
patient_id
amount
reason
status
requested_by
approved_by
processed_by
requested_at
approved_at
processed_at
Refund workflow:
Requested
   ↓
Approved
   ↓
Processed
Refund permissions must be stricter than normal payment collection.
24. Credit / Due Management
Support:
- Patient credit
- Corporate credit
- Insurance receivable
Track:
Invoice
Payment
Adjustment
Write-off
Refund
Outstanding balance
Do not rely only on a mutable due_amount field.
The financial ledger should remain reconstructable.
25. Cashier Session
Introduce:
cashier_sessions
A cashier should have a controlled session:
Open
 ↓
Transactions
 ↓
Close
Track:
- Opening balance
- Cash received
- Cash refunds
- Other payment types
- Expected closing balance
- Actual closing balance
- Variance
- Opened by
- Closed by
- Opening/closing timestamps
26. Cash Drawer
Support reconciliation:
Opening Cash
+ Cash Collections
- Cash Refunds
= Expected Cash
Then:
Expected Cash
vs
Actual Cash
Calculate:
Variance
Variance must be audited.
27. Corporate Billing
Prepare a corporate billing foundation.
Support:
- Corporate customer
- Contract
- Contract price list
- Credit limit
- Billing cycle
- Payment terms
- Employee/member mapping
Examples:
ABC Corporation
Monthly billing
30-day credit
Do not implement a complex CRM in Phase 4.
28. Insurance Billing Foundation
Support:
- Insurance company
- Policy number
- Member number
- Coverage information
- Authorization/reference
- Claim-ready billing records
Future insurance module can expand this into full claim processing.
29. Financial Adjustments
Support controlled:
- Discount adjustment
- Price adjustment
- Credit adjustment
- Debit adjustment
- Write-off
Every adjustment requires:
- Reason
- User
- Timestamp
- Original value
- New value
- Approval where required
30. Accounting Integration Foundation
Do not implement a complete accounting system.
However, prepare integration interfaces for:
Billing
   ↓
Revenue Posting
   ↓
Accounting / ERP
Possible future integration:
- General Ledger
- Accounts Receivable
- Cash
- Bank
- Tax
- Revenue accounts
Use interfaces/events rather than hardcoding an accounting vendor.
31. Revenue Recognition
The system should distinguish:
- Charge
- Invoice
- Payment
- Revenue
Do not assume:
Payment = Revenue

Accounting treatment may depend on the organization's financial policy.
Design the module so revenue posting can later be integrated with an accounting system.
32. Billing Dashboard
Provide:
Today's Summary
- Total billed
- Total collected
- Total due
- Total refunds
- Total discounts
- Number of invoices
- Number of payments
Cashier
- Open sessions
- Cash collection
- Card collection
- MFS collection
- Bank collection
Revenue
- OPD revenue
- Diagnostic revenue
- Procedure revenue
- Pharmacy revenue
- Other revenue
Future modules can contribute additional categories.
33. Reports
Implement:
Billing
- Daily Billing
- Invoice Register
- Outstanding Invoices
- Paid Invoices
- Partially Paid Invoices
- Cancelled Invoices
- Refund Report
- Discount Report
- Adjustment Report
Payment
- Daily Collection
- Payment Method Summary
- Cashier Collection
- Cashier Reconciliation
- Payment Transaction Report
Revenue
- Revenue by Department
- Revenue by Service
- Revenue by Provider
- Revenue by Hospital
- Revenue by Branch
- Revenue by Patient Category
- Daily/Monthly Revenue
Receivables
- Patient Outstanding
- Corporate Outstanding
- Insurance Outstanding
- Aging Report
34. Audit Requirements
Audit:
- Invoice created
- Invoice finalized
- Invoice cancelled
- Discount applied
- Tax changed
- Payment created
- Payment cancelled
- Receipt generated
- Refund requested
- Refund approved
- Refund processed
- Adjustment created
- Write-off
- Cashier opened
- Cashier closed
- Cash variance
- Price changed
Financial audit records should be immutable.
35. Security
Financial permissions must be separated from clinical permissions.
Examples:
billing.invoice.view
billing.invoice.create
billing.invoice.finalize
billing.invoice.cancel
billing.payment.view
billing.payment.create
billing.payment.cancel
billing.refund.create
billing.refund.approve
billing.refund.process
billing.discount.create
billing.discount.approve
billing.adjustment.create
billing.adjustment.approve
billing.cashier.open
billing.cashier.close
billing.report.view
billing.export
36. Phase 4 Menu
Billing & Revenue
├── Dashboard
├── Billing
│   ├── New Invoice
│   ├── Invoices
│   ├── Charges
│   ├── Adjustments
│   └── Outstanding
│
├── Payments
│   ├── Collect Payment
│   ├── Payments
│   ├── Receipts
│   └── Refunds
│
├── Cashier
│   ├── My Session
│   ├── Open Session
│   ├── Close Session
│   └── Reconciliation
│
├── Pricing
│   ├── Service Catalog
│   ├── Categories
│   ├── Price Lists
│   └── Pricing Rules
│
├── Corporate
│   ├── Companies
│   ├── Contracts
│   └── Receivables
│
├── Insurance
│   ├── Providers
│   ├── Policies
│   └── Receivables
│
├── Reports
│   ├── Billing
│   ├── Collection
│   ├── Revenue
│   ├── Receivables
│   ├── Refunds
│   └── Discounts
│
└── Settings
37. Integration with Phase 3
The most important Phase 3 integration is:
Clinical Encounter
      │
      ├── Consultation
      ├── Procedure
      └── Clinical Order
              │
              ▼
       Chargeable Event
              │
              ▼
           Billing
For example:
Doctor consultation
BDT 1,500
      ↓
Charge created
      ↓
Invoice generated
      ↓
Patient pays BDT 1,500
      ↓
Receipt generated
The clinical module should not create or manipulate payment records.
38. Recommended Database Structure
A reasonable initial structure is:
billing_categories
billing_items

billing_price_lists
billing_price_list_items

billing_charges

billing_invoices
billing_invoice_items

billing_payment_methods
billing_payments
billing_receipts

billing_refunds

billing_adjustments

billing_advance_accounts
billing_advance_transactions

billing_cashier_sessions

billing_corporates
billing_corporate_contracts
billing_corporate_members

billing_insurance_providers
billing_insurance_policies

billing_tax_categories

billing_audit_logs
The exact schema should be adapted to the project's existing conventions.
39. Phase 4 Dependencies
Phase 0
Foundation
   ↓
Phase 1
Patient / MPI
   ↓
Phase 2
Appointment / Scheduling
   ↓
Phase 3
Clinical Encounter
   ↓
Phase 4
Billing & Revenue
   │
   ├──────── Laboratory
   ├──────── Radiology
   ├──────── Pharmacy
   ├──────── IPD
   ├──────── OT
   └──────── ICU
Billing should therefore expose clean interfaces for future modules.
40. Phase 4 Acceptance Criteria
Phase 4 should be considered complete when:
- Services can be configured.
- Prices can be configured.
- Historical prices remain preserved.
- Clinical events can generate charges.
- Charges can become invoices.
- Invoices can be finalized.
- Discounts can be controlled.
- Taxes can be calculated.
- Payments can be collected.
- Partial payments work.
- Receipts are generated.
- Advances work.
- Refunds work.
- Outstanding balances work.
- Cashier sessions work.
- Cash reconciliation works.
- Corporate billing foundation works.
- Insurance billing foundation works.
- Financial adjustments are audited.
- Reports work.
- Multi-hospital scope works.
- Role-based access works.
- Financial records cannot be silently altered.
- API works.
- Automated tests pass.
- Phase 0–3 functionality remains intact.