# AI IMPLEMENTATION PROMPT
## Phase 4 — Billing & Revenue

You are a senior healthcare software architect and Laravel engineer.

Implement **Phase 4 — Billing & Revenue** in the existing enterprise Hospital Management System.

The project already has:

- Phase 0 — Foundation
- Phase 1 — Patient / Master Patient Index
- Phase 2 — Appointment & Scheduling
- Phase 3 — OPD / Clinical Encounter / EMR Foundation
- Modular Architecture

**IMPORTANT:** Modular Architecture has already been implemented.

Do NOT create another modular architecture, module framework, module loader, or replacement directory structure.

Your responsibility is to implement the **Billing & Revenue business module inside the existing modular architecture**.

---

# 1. PRIMARY OBJECTIVE

Build a production-grade healthcare billing engine supporting:

Patient
→ Chargeable Event
→ Charge
→ Invoice
→ Payment
→ Receipt

with support for:

- Pricing
- Discounts
- Taxes
- Partial payment
- Advance payment
- Refund
- Credit
- Corporate billing
- Insurance billing foundation
- Cashier sessions
- Reconciliation
- Revenue reporting
- Financial audit
- Accounting integration foundation

The system must integrate with Phase 3 clinical encounters without coupling clinical and financial responsibilities.

---

# 2. FIRST TASK — INSPECT THE EXISTING PROJECT

Before writing code, inspect the existing repository.

Determine:

- Laravel version
- PHP version
- Database
- Existing modular architecture
- Existing modules
- Module namespace conventions
- Existing Patient module
- Existing Appointment module
- Existing Clinical module
- Authentication
- Authorization
- Organization hierarchy
- Hospital
- Branch
- Department
- User
- Role
- Permission
- Audit
- Activity logging
- File management
- Notifications
- API architecture
- Reporting architecture
- Settings
- Master data
- Queue
- Scheduler
- Existing financial/accounting code if any

Do not assume directory names.

Do not create duplicate infrastructure.

---

# 3. MODULE ARCHITECTURE

Use the project's existing module architecture.

Create a Billing/Revenue module according to the existing convention.

For example, if the project uses:

Modules/Billing

then use that architecture.

If it uses another structure, follow it.

Do NOT introduce:

- a new modular framework
- a second module loader
- a second service container architecture
- a new permission framework
- a second audit framework

Reuse the existing platform.

---

# 4. CORE BILLING MODEL

Implement this conceptual flow:

```text
Clinical Event
      ↓
Charge
      ↓
Invoice
      ↓
Payment
      ↓
Receipt
```

Keep these concepts separate.

### Charge

A billable event.

### Invoice

A formal amount due.

### Payment

Money received.

### Receipt

Proof of payment.

Never collapse these into one database record.

---

# 5. BILLING SERVICE CATALOG

Implement configurable billable services.

Create/adapt:

- Billing Category
- Billing Item
- Price List
- Price List Item
- Tax Category

Examples:

- Consultation
- Follow-up
- Registration
- Laboratory
- Radiology
- Procedure
- Room
- Nursing
- Pharmacy
- Ambulance
- Administrative service

Do not hardcode services.

---

# 6. DATABASE

Create migrations for appropriate tables, adapting to the existing schema.

Recommended:

```text
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
```

Only create tables that do not already exist.

Use existing organization/hospital/branch structures.

---

# 7. BILLING ITEMS

Billing item fields should support:

- organization
- code
- category
- name
- description
- item type
- unit
- taxability
- tax category
- active status
- effective dates

Item types may include:

```text
Service
Product
Consultation
Procedure
Diagnostic
Room
Other
```

Use configuration rather than hardcoded enums where the existing architecture supports configurable master data.

---

# 8. PRICE LIST

Implement:

- Price List
- Price List Items
- Effective dates
- Currency
- Hospital scope
- Branch scope
- Patient category
- Corporate pricing
- Insurance pricing

Historical pricing is critical.

When an invoice is finalized:

> Preserve the actual unit price used by the invoice.

Changing today's price must never alter historical invoices.

---

# 9. PRICE RESOLUTION SERVICE

Create a dedicated pricing service, for example:

```text
BillingPricingService
```

It should resolve price using applicable rules such as:

1. Hospital
2. Branch
3. Patient category
4. Corporate/insurance contract
5. Provider/specialty where applicable
6. Price list priority
7. Effective date

Do not scatter pricing logic across controllers.

---

# 10. CHARGES

Create:

```text
billing_charges
```

Support:

- organization
- hospital
- branch
- patient
- encounter
- billing item
- source type
- source ID
- provider
- department
- quantity
- unit price
- gross amount
- discount
- tax
- net amount
- status
- charged_at
- created_by

Charge statuses:

```text
Pending
Billed
Cancelled
Refunded
Adjusted
```

---

# 11. SOURCE TRACEABILITY

Every charge must identify its source.

Examples:

```text
source_type = Encounter
source_id = 1234
```

or:

```text
source_type = ClinicalOrder
source_id = 5678
```

The system must support:

```text
Invoice
 ↓
Invoice Item
 ↓
Charge
 ↓
Clinical Source
```

This traceability is mandatory.

---

# 12. PHASE 3 INTEGRATION

Implement billing integration with Phase 3.

Examples:

### Consultation

```text
Encounter created
      ↓
Consultation charge
      ↓
Invoice
```

### Procedure

```text
Procedure recorded
      ↓
Procedure charge
```

### Clinical Order

```text
Clinical Order
      ↓
Chargeable event
```

Do not make clinical controllers responsible for financial calculations.

Use a service/event-based integration.

For example:

```text
Clinical event
      ↓
Domain event
      ↓
Billing listener/service
      ↓
Charge
```

Adapt this to the project's existing event architecture.

---

# 13. INVOICE

Implement:

```text
billing_invoices
```

Required concepts:

- Invoice number
- Patient
- Encounter
- Hospital
- Branch
- Invoice type
- Currency
- Subtotal
- Discount
- Tax
- Rounding
- Grand total
- Paid amount
- Due amount
- Status
- Invoice date
- Due date
- Billing party
- Created by
- Finalized by

Invoice types:

```text
OPD
IPD
Diagnostic
Pharmacy
Procedure
Corporate
Insurance
Advance
Other
```

---

# 14. INVOICE NUMBER

Generate concurrency-safe invoice numbers.

Example:

```text
INV-2026-00000125
```

Never use:

```php
Invoice::count() + 1
```

Invoice numbers must be:

- Unique
- Immutable
- Never reused

Use the existing numbering/sequence architecture if one exists.

---

# 15. INVOICE ITEMS

Implement:

```text
billing_invoice_items
```

Store the actual billed:

- Description
- Quantity
- Unit price
- Gross
- Discount
- Tax
- Net
- Source charge

Never dynamically recalculate historical invoices using the current price catalog.

---

# 16. INVOICE LIFECYCLE

Implement controlled transitions:

```text
Draft
 ↓
Pending
 ↓
Finalized
 ↓
Partially Paid
 ↓
Paid
```

Alternative states:

```text
Cancelled
Refunded
Written Off
```

Implement:

```text
InvoiceLifecycleService
```

or equivalent according to project architecture.

Do not allow arbitrary status updates.

---

# 17. DISCOUNTS

Support:

- Percentage
- Fixed amount
- Item-level
- Invoice-level
- Corporate
- Patient category
- Promotional
- Manual

Manual discounts require:

- User
- Reason
- Original amount
- Discount
- Timestamp
- Approval if required

Make approval thresholds configurable.

---

# 18. TAXES

Implement configurable:

- Tax category
- Rate
- Effective date
- Inclusive/exclusive mode

Never hardcode tax rates.

Tax calculations should be centralized in a billing calculation service.

For example:

```text
BillingCalculationService
```

---

# 19. FINANCIAL CALCULATION RULE

Use a consistent calculation model.

For example:

```text
Gross
- Discount
= Taxable Amount

Tax
= Taxable Amount × Tax Rate

Net
= Taxable Amount + Tax
```

However, support tax-inclusive pricing where configured.

Use decimal-safe monetary calculations.

Do not use floating-point arithmetic for financial amounts.

Use appropriate database decimal types.

---

# 20. PAYMENTS

Create:

```text
billing_payments
```

Support:

- Cash
- Card
- Bank transfer
- MFS
- Online payment
- Cheque
- Insurance
- Corporate credit
- Advance

Support:

- Full payment
- Partial payment
- Multiple payments

Never modify previous payments to represent new transactions.

---

# 21. PAYMENT NUMBER

Generate unique payment numbers.

Example:

```text
PAY-2026-00000532
```

Use the existing sequence service if available.

---

# 22. RECEIPTS

Create:

```text
billing_receipts
```

Every completed payment should have a receipt.

Example:

```text
RCT-2026-00000452
```

Receipt must link to:

- Payment
- Invoice
- Patient
- Cashier
- Hospital
- Branch

---

# 23. ADVANCE PAYMENTS

Implement patient advance accounts.

Example:

```text
Deposit = BDT 20,000

Invoice = BDT 8,000

Apply advance = BDT 8,000

Remaining advance = BDT 12,000
```

Use a transaction ledger.

Do not simply overwrite a balance field.

---

# 24. REFUNDS

Implement:

```text
billing_refunds
```

Support:

- Full refund
- Partial refund

Workflow:

```text
Requested
 ↓
Approved
 ↓
Processed
```

Refund must reference the original payment.

Refunds require stricter authorization than normal payments.

---

# 25. CREDIT / RECEIVABLES

Support:

- Patient dues
- Corporate dues
- Insurance receivables

Financial balances must be reconstructable from transactions.

Do not rely only on mutable balance fields.

---

# 26. CASHIER SESSION

Implement:

```text
billing_cashier_sessions
```

Workflow:

```text
Open Session
 ↓
Collect Payments
 ↓
Refunds if authorized
 ↓
Close Session
 ↓
Reconcile
```

Track:

- Opening balance
- Cash received
- Cash refunds
- Other payment methods
- Expected closing balance
- Actual closing balance
- Variance
- Opened by
- Closed by
- Timestamps

---

# 27. CASH RECONCILIATION

Calculate:

```text
Opening Cash
+ Cash Collections
- Cash Refunds
= Expected Cash
```

Then:

```text
Expected Cash
vs
Actual Cash
= Variance
```

Variance must be recorded and audited.

---

# 28. CORPORATE BILLING

Implement a foundation for:

- Corporate customer
- Contract
- Contract pricing
- Credit limit
- Billing cycle
- Payment terms
- Corporate members

Do not implement unnecessary CRM functionality.

---

# 29. INSURANCE FOUNDATION

Implement:

- Insurance provider
- Policy
- Member number
- Coverage
- Authorization/reference number
- Claim-ready billing record

Do not implement a complete insurance claims engine in Phase 4.

---

# 30. FINANCIAL ADJUSTMENTS

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

---

# 31. ACCOUNTING INTEGRATION

Do not implement a complete accounting system.

Create integration interfaces/events for future:

```text
Billing
 ↓
Revenue Posting
 ↓
Accounting / ERP
```

Potential future posting categories:

- Revenue
- Accounts Receivable
- Cash
- Bank
- Tax

Do not hardcode an accounting vendor.

---

# 32. SERVICES

Implement services according to the existing module architecture.

Likely services:

```text
BillingItemService
BillingPricingService
BillingCalculationService
ChargeService
InvoiceService
InvoiceLifecycleService
PaymentService
ReceiptService
RefundService
AdvanceService
CashierSessionService
AdjustmentService
CorporateBillingService
InsuranceBillingService
RevenueService
```

Reuse existing project services where applicable.

---

# 33. CONTROLLERS

Controllers must remain thin.

Controller flow:

```text
Request
 ↓
Authorization
 ↓
Validation
 ↓
Service
 ↓
Response
```

Do not place financial calculations inside controllers.

---

# 34. FORM REQUESTS

Create appropriate Form Requests for:

- Billing item
- Price list
- Charge
- Invoice
- Discount
- Payment
- Refund
- Adjustment
- Cashier session
- Corporate
- Insurance

Validate both input and business rules.

---

# 35. POLICIES

Implement authorization for:

- Invoice
- Payment
- Refund
- Discount
- Adjustment
- Cashier
- Pricing
- Corporate
- Insurance
- Reports

Always enforce:

```text
Organization scope
Hospital scope
Branch scope
Department scope where applicable
```

Server-side.

---

# 36. PERMISSIONS

Create/reuse permissions such as:

```text
billing.dashboard.view

billing.item.view
billing.item.create
billing.item.update

billing.price.view
billing.price.create
billing.price.update

billing.charge.view
billing.charge.create
billing.charge.cancel

billing.invoice.view
billing.invoice.create
billing.invoice.finalize
billing.invoice.cancel
billing.invoice.export

billing.payment.view
billing.payment.create
billing.payment.cancel

billing.receipt.view
billing.receipt.print

billing.refund.view
billing.refund.create
billing.refund.approve
billing.refund.process

billing.discount.create
billing.discount.approve

billing.adjustment.create
billing.adjustment.approve

billing.cashier.open
billing.cashier.close
billing.cashier.reconcile

billing.corporate.view
billing.corporate.create
billing.corporate.update

billing.insurance.view
billing.insurance.create
billing.insurance.update

billing.report.view
billing.report.export
```

Adapt naming to the existing permission convention.

---

# 37. EVENTS

Use the existing event system.

Potential events:

```text
ChargeCreated
ChargeCancelled

InvoiceCreated
InvoiceFinalized
InvoiceCancelled

PaymentCreated
PaymentCompleted
PaymentCancelled

ReceiptGenerated

RefundRequested
RefundApproved
RefundProcessed

AdvanceReceived
AdvanceApplied

CashierSessionOpened
CashierSessionClosed

FinancialAdjustmentCreated
```

---

# 38. PHASE 3 EVENT INTEGRATION

Listen for relevant clinical events.

For example:

```text
EncounterCompleted
ProcedureRecorded
ClinicalOrderCreated
```

Do not blindly create charges for every event.

Each chargeable service must have explicit billing configuration.

For example:

```text
Clinical Procedure
     ↓
Billing Item mapping
     ↓
Price resolution
     ↓
Charge
```

---

# 39. API

Create:

```text
/api/v1/billing
```

Potential endpoints:

```text
GET    /billing/items
POST   /billing/items

GET    /billing/price-lists
POST   /billing/price-lists

GET    /billing/charges
POST   /billing/charges

GET    /billing/invoices
POST   /billing/invoices
GET    /billing/invoices/{invoice}
POST   /billing/invoices/{invoice}/finalize
POST   /billing/invoices/{invoice}/cancel

GET    /billing/payments
POST   /billing/payments

GET    /billing/receipts/{receipt}

POST   /billing/refunds
POST   /billing/refunds/{refund}/approve
POST   /billing/refunds/{refund}/process

GET    /billing/outstanding

GET    /billing/reports/revenue
GET    /billing/reports/collection
GET    /billing/reports/cashier
```

Follow the existing API architecture.

---

# 40. API RESPONSE

Reuse the existing standard response.

Success:

```json
{
    "success": true,
    "message": "Operation successful.",
    "data": {},
    "meta": {}
}
```

Validation:

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {}
}
```

Never expose:

- SQL errors
- Stack traces
- Internal paths
- Secrets
- Infrastructure details

---

# 41. BILLING UI

Use the existing AdminLTE layout and existing UI components.

Implement:

### Dashboard

- Billing today
- Collection today
- Outstanding
- Refunds
- Discounts
- Cashier status

### Invoice

- Invoice list
- Invoice creation
- Invoice details
- Finalize
- Cancel
- Print
- Export

### Payment

- Collect payment
- Payment history
- Receipt

### Refund

- Request
- Approve
- Process

### Pricing

- Items
- Categories
- Price lists

### Cashier

- Open
- Current session
- Close
- Reconcile

---

# 42. INVOICE UI SAFETY

Before finalizing an invoice display:

- Patient
- MRN
- Encounter
- Services
- Quantity
- Unit price
- Discount
- Tax
- Total
- Paid
- Due

Require confirmation before finalization.

After finalization:

> Prevent ordinary editing.

---

# 43. FINANCIAL IMMUTABILITY

Financial records require transaction history.

Do not silently overwrite:

- Invoice totals
- Payment amount
- Refund amount
- Price used
- Discount
- Tax
- Cashier transactions

Use:

- Adjustment
- Cancellation
- Refund
- Amendment where applicable

instead.

---

# 44. DATABASE CONSTRAINTS

Use:

- Foreign keys
- Unique invoice numbers
- Unique payment numbers
- Unique receipt numbers
- Unique refund numbers
- Appropriate decimal fields
- Appropriate indexes
- Status constraints where supported

Do not rely only on application validation.

---

# 45. MONEY HANDLING

Use database types such as:

```text
DECIMAL(18,2)
```

or the project's established equivalent.

Never use floating-point values for financial calculations.

Use a consistent rounding policy.

Centralize monetary calculations.

---

# 46. TRANSACTIONS

Use database transactions for:

### Invoice finalization

```text
Validate invoice
→ Calculate totals
→ Finalize
→ Create audit
→ Dispatch event
```

### Payment

```text
Validate invoice
→ Validate amount
→ Create payment
→ Update invoice status
→ Create receipt
→ Audit
```

### Refund

```text
Validate payment
→ Validate refund amount
→ Approve
→ Process
→ Update financial state
→ Audit
```

### Cashier closing

```text
Calculate expected
→ Record actual
→ Calculate variance
→ Close session
→ Audit
```

---

# 47. CONCURRENCY

Protect against:

- Duplicate invoice numbers
- Duplicate payments
- Double finalization
- Double refunds
- Concurrent cashier transactions
- Race conditions in advance application

Use:

- Database unique constraints
- Transactions
- Appropriate row locks
- Idempotency where appropriate

---

# 48. AUDIT

Record:

```text
InvoiceCreated
InvoiceFinalized
InvoiceCancelled

DiscountApplied
DiscountApproved

PaymentCreated
PaymentCancelled

ReceiptGenerated

RefundRequested
RefundApproved
RefundProcessed

AdjustmentCreated
AdjustmentApproved

CashierOpened
CashierClosed
CashVarianceRecorded

PriceCreated
PriceChanged
```

Financial audit data must be protected from unauthorized modification.

---

# 49. REPORTING

Implement:

### Billing

- Daily billing
- Invoice register
- Outstanding
- Paid
- Partial
- Cancelled
- Refund
- Discount
- Adjustment

### Collection

- Daily collection
- Payment method
- Cashier
- Cashier reconciliation

### Revenue

- Hospital
- Branch
- Department
- Provider
- Service
- Patient category
- Daily
- Monthly

### Receivable

- Patient
- Corporate
- Insurance
- Aging

Use efficient queries and indexes.

---

# 50. PDF DOCUMENTS

Where the existing project supports document generation, implement:

- Invoice PDF
- Receipt PDF
- Refund document
- Payment statement

Do not introduce a second document-generation architecture.

Documents should contain:

- Hospital identity
- Branch
- Patient
- MRN
- Invoice/payment number
- Date/time
- Services
- Amount
- Payment method
- Authorized user

---

# 51. SECURITY TESTS

Mandatory tests:

### Tenant isolation

Hospital A billing user cannot access Hospital B invoices.

### Payment authorization

Unauthorized user cannot create payment.

### Refund authorization

Cashier cannot process unauthorized refund.

### Discount authorization

User cannot bypass discount approval.

### Invoice immutability

Finalized invoice cannot be silently edited.

### Payment immutability

Completed payment cannot be silently modified.

### API security

Direct API calls cannot bypass permissions.

---

# 52. TESTING

Implement:

## Unit tests

- Price resolution
- Tax calculation
- Discount calculation
- Invoice totals
- Payment allocation
- Refund calculation
- Advance allocation
- Cashier reconciliation

## Feature tests

- Create billing item
- Create price list
- Create charge
- Create invoice
- Finalize invoice
- Collect payment
- Partial payment
- Generate receipt
- Advance payment
- Apply advance
- Refund
- Cashier open/close
- Discount approval
- Financial adjustment

## Integration tests

- Phase 3 encounter → billing charge
- Clinical procedure → billing charge
- Clinical order → billing integration
- Payment → receipt
- Invoice → revenue reporting

## Security tests

- Hospital isolation
- Role isolation
- Permission bypass
- API authorization
- Invoice immutability
- Payment immutability

Do not claim tests passed unless they are actually executed.

---

# 53. PERFORMANCE

Avoid:

- N+1 queries
- Loading all invoices
- Loading complete patient history unnecessarily
- Recalculating old invoices from current pricing

Use:

- eager loading
- pagination
- indexes
- query scopes
- aggregation queries
- caching where appropriate

Reports should use optimized aggregate queries.

---

# 54. ACCOUNTING INTEGRATION CONTRACT

Create an abstraction such as:

```text
RevenuePostingInterface
```

or the equivalent in the existing architecture.

Potential method:

```text
postInvoiceRevenue()
postPayment()
postRefund()
postAdjustment()
```

Do not connect directly to a specific accounting vendor.

Future integrations can implement the interface.

---

# 55. CONFIGURATION

Use existing settings/master-data architecture for:

- Invoice prefix
- Payment prefix
- Receipt prefix
- Refund prefix
- Currency
- Tax configuration
- Discount approval thresholds
- Payment methods
- Billing categories
- Price lists
- Rounding policy
- Invoice due terms

Do not hardcode these values.

---

# 56. IMPLEMENTATION ORDER

Implement in this order:

## Step 1

Repository/module architecture analysis.

## Step 2

Map existing:

```text
Patient
Hospital
Branch
Department
User
Encounter
Clinical Order
Procedure
Audit
Permissions
Settings
```

## Step 3

Database migrations.

## Step 4

Billing models and relationships.

## Step 5

Billing item/catalog.

## Step 6

Pricing engine.

## Step 7

Charge engine.

## Step 8

Invoice engine.

## Step 9

Payment engine.

## Step 10

Receipt generation.

## Step 11

Refunds.

## Step 12

Advance payments.

## Step 13

Cashier session/reconciliation.

## Step 14

Corporate/insurance foundation.

## Step 15

Phase 3 integration.

## Step 16

Authorization.

## Step 17

API.

## Step 18

UI.

## Step 19

Reports.

## Step 20

Tests.

## Step 21

Documentation.

---

# 57. IMPORTANT NON-GOALS

Do NOT implement full:

- General Ledger
- Accounts Payable
- Full accounting ERP
- Complete insurance claims management
- Complete laboratory billing subsystem
- Complete radiology billing subsystem
- Pharmacy inventory
- IPD billing
- OT billing
- ICU billing

Only provide the billing foundation and integration interfaces needed by future modules.

---

# 58. DEFINITION OF DONE

Phase 4 is complete only when:

1. Billing module is implemented using the existing modular architecture.
2. Existing Phase 0–3 modules remain functional.
3. Billing catalog works.
4. Pricing works.
5. Historical prices are preserved.
6. Charges work.
7. Invoice generation works.
8. Invoice finalization works.
9. Discounts work.
10. Tax calculation works.
11. Payments work.
12. Partial payments work.
13. Receipts work.
14. Advances work.
15. Refunds work.
16. Outstanding balances work.
17. Cashier sessions work.
18. Reconciliation works.
19. Corporate billing foundation works.
20. Insurance billing foundation works.
21. Financial adjustments work.
22. Reports work.
23. API works.
24. Authorization works.
25. Hospital/branch scope works.
26. Audit works.
27. Financial immutability works.
28. Automated tests are executed.
29. Security tests are executed.
30. Documentation is updated.

Do not declare completion simply because migrations or code generation succeeded.

---

# 59. FINAL IMPLEMENTATION REPORT

After implementation provide:

## A. Module Summary

What was implemented.

## B. Database

List:

- Tables
- Foreign keys
- Indexes
- Constraints

## C. Application

List:

- Models
- Services
- Actions
- Policies
- Requests
- Controllers
- Events
- Listeners
- Jobs
- Components

## D. Integration

Explain:

```text
Phase 3 → Billing
```

and future:

```text
Laboratory
Radiology
Pharmacy
IPD
OT
ICU
```

integration points.

## E. Permissions

List all billing permissions.

## F. Testing

Report:

- Tests executed
- Passed
- Failed
- Security tests
- Performance findings

Never claim tests passed unless actually run.

## G. Deployment

Provide:

- Migration commands
- Seeder commands
- Queue requirements
- Scheduler requirements
- Storage requirements
- Environment configuration

## H. Deferred Work

Clearly identify functionality reserved for later phases.

---

# 60. FINAL INSTRUCTION

This is a healthcare financial system.

Treat financial records as highly sensitive and auditable.

Do not silently modify finalized invoices or completed financial transactions.

Do not allow authorization to depend only on the UI.

Do not duplicate Phase 0–3 infrastructure.

Do not create another modular architecture.

Do not hardcode prices, tax rates, payment methods, hospital IDs, or financial approval thresholds.

Do not couple billing directly to a particular accounting vendor.

Use domain services, events, transactions, database constraints, and audit trails.

Before writing code:

**Inspect the existing project and map Phase 4 requirements to the actual existing architecture.**

Then implement incrementally.

At the end, execute the relevant test suite and provide the implementation report.

**Begin with repository analysis.**