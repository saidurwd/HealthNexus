
# Phase 14 — Insurance & Corporate Claims
## Module Specification & AI Implementation Prompt

**Architecture:** Laravel Modular Monolith  
**PHP:** 8.4+  
**Database:** MySQL / PostgreSQL  
**Frontend:** Blade + AdminLTE  
**API:** REST `/api/v1`  
**Queue/Cache:** Redis  
**Phase:** 14 of 21  
**Status:** Production-Ready Specification

---

# 1. Purpose

Phase 14 introduces a centralized **Insurance & Corporate Claims Management module** for managing:

- Insurance companies
- Corporate clients
- Payers
- Insurance plans/products
- Employer/corporate agreements
- Patient insurance policies
- Member/dependent information
- Eligibility and coverage
- Benefit limits
- Co-pay/deductibles
- Pre-authorizations
- Service authorization
- Claim preparation
- Claim submission
- Claim tracking
- Claim adjudication/status
- Claim rejection/denial
- Resubmission
- Payment/remittance tracking
- Outstanding claims
- Insurance receivables
- Corporate billing/claims
- Settlement/reconciliation
- Claim documents
- Insurance correspondence
- Claim analytics
- Audit and compliance

The module must integrate with the existing:

- Patient/MPI
- Appointment
- Clinical Encounter/EMR
- Billing & Revenue
- Laboratory
- Radiology
- Pharmacy
- IPD
- Nursing
- OT/Surgery
- ICU
- Emergency
- Blood Bank
- Organization/Hospital/Branch
- User/RBAC
- Workflow/Approval
- Notification
- Audit
- File Management

---

# 2. Core Business Principle

Insurance & Corporate Claims must **not become a second billing system**.

The financial architecture is:

```text
Patient
   ↓
Clinical / Operational Service
   ↓
Chargeable Event
   ↓
Billing Engine
   ↓
Invoice
   ↓
Insurance / Corporate Coverage
   ↓
Claim
   ↓
Claim Submission
   ↓
Payer Adjudication
   ↓
Approved / Partially Approved / Rejected / Denied
   ↓
Remittance / Settlement
   ↓
Accounts Receivable / Reconciliation
```

Phase 4 remains the financial source of truth for:

- Charges
- Invoices
- Payments
- Receipts
- Refunds
- Discounts
- Taxes
- Patient dues
- Financial transactions

Phase 14 manages the **payer relationship and claim lifecycle**.

---

# 3. Critical Architecture Rule

The existing modular architecture is already implemented.

The AI developer MUST:

1. Inspect the existing repository.
2. Inspect Phases 0–13.
3. Identify existing modules, services, models, traits, policies, workflows, events and UI components.
4. Reuse existing architecture.
5. Extend existing abstractions where appropriate.
6. Avoid creating duplicate systems.

## Never create a second implementation of:

- Patient/MPI
- Users
- Roles
- Permissions
- Organization
- Hospital
- Branch
- Department
- Provider
- Appointment
- Encounter
- EMR
- Billing
- Invoice
- Payment
- Receipt
- Laboratory
- Radiology
- Pharmacy
- Admission
- Bed Management
- Nursing
- OT
- ICU
- Emergency
- Blood Bank
- Notifications
- Audit
- File Management
- Workflow
- API infrastructure

---

# 4. Scope

## 4.1 Payer Management

Support:

- Insurance companies
- Third-party administrators (TPA)
- Corporate clients
- Government/organizational payers
- Self-funded employers
- Other configurable payer types

Each payer should support:

- Name
- Code
- Type
- Registration information
- Contact information
- Address
- Billing contact
- Claims contact
- Authorization contact
- Finance contact
- Email
- Phone
- Portal information
- Contract reference
- Effective date
- Expiry date
- Status
- Notes

---

# 5. Insurance Plan Management

A payer may have multiple plans.

Example:

```text
Insurance Company
    ├── Corporate Gold
    ├── Corporate Silver
    ├── Executive Plan
    └── Family Plan
```

Plan information:

- Plan code
- Plan name
- Payer
- Plan type
- Effective date
- Expiry date
- Coverage status
- Annual limit
- Lifetime limit
- Inpatient limit
- Outpatient limit
- Pharmacy limit
- Laboratory limit
- Radiology limit
- Surgery limit
- ICU limit
- Emergency limit
- Maternity limit
- Dental limit
- Optical limit
- Other configurable benefits

---

# 6. Corporate Client Management

Support corporate agreements such as:

```text
Duncan Brothers
       ↓
Insurance / Corporate Agreement
       ↓
Eligible Employees
       ↓
Dependents
       ↓
Hospital Services
       ↓
Corporate Claim
```

Corporate client fields:

- Corporate ID
- Organization name
- Registration details
- Contact person
- HR contact
- Finance contact
- Contract number
- Contract start/end
- Billing cycle
- Credit terms
- Coverage rules
- Employee eligibility
- Dependent rules
- Service restrictions
- Discount rules
- Approval requirements
- Claim submission rules
- Status

---

# 7. Provider Contract / Agreement

Support payer/provider contracts.

Conceptually:

```text
Payer
   ↓
Provider Contract
   ↓
Hospital / Branch
   ↓
Plan
   ↓
Pricing / Coverage Rules
```

Contract should support:

- Contract number
- Payer
- Hospital
- Branch
- Effective date
- Expiry date
- Payment terms
- Claim submission deadline
- Settlement terms
- Service coverage
- Exclusions
- Tariffs
- Discounts
- Co-pay
- Deductible
- Claim requirements
- Authorization requirements
- Documentation requirements
- Contract status

---

# 8. Patient Insurance Policy

A patient may have multiple policies.

Example:

```text
Patient
 ├── Primary Insurance
 ├── Secondary Insurance
 └── Corporate Coverage
```

Policy information:

- Policy number
- Member ID
- Patient ID
- Payer
- Plan
- Corporate client
- Subscriber
- Relationship to subscriber
- Effective date
- Expiry date
- Coverage status
- Card number
- Group number
- Employee ID
- Network
- Priority
- Eligibility status
- Coverage percentage
- Co-pay
- Deductible
- Annual limit
- Remaining limit
- Notes

---

# 9. Subscriber and Dependents

Support:

- Employee/subscriber
- Spouse
- Child
- Parent
- Other configurable relationships

Store:

- Subscriber ID
- Employee ID
- Member ID
- Relationship
- Dependent sequence
- Eligibility dates
- Verification status

Do not duplicate the Patient/MPI identity system.

---

# 10. Insurance Eligibility Verification

Eligibility must be checked before claimable services where required.

Workflow:

```text
Patient
 ↓
Insurance Policy
 ↓
Eligibility Verification
 ↓
Coverage Check
 ↓
Benefit Check
 ↓
Authorization Requirement
 ↓
Proceed / Patient Pay / Authorization Required
```

Eligibility verification should support:

- Manual verification
- Portal/API integration
- Batch verification
- Verification date/time
- Verified by
- Eligibility response
- Coverage status
- Benefit status
- Limit information
- Reference number
- Evidence/document

---

# 11. Coverage Rules

Coverage rules must be configurable.

Examples:

```text
Consultation → 80%
Laboratory → 90%
Radiology → 80%
Medicine → 70%
Hospitalization → 90%
ICU → 80%
Surgery → Authorization Required
```

Rules may depend on:

- Payer
- Plan
- Corporate contract
- Service category
- Service
- Department
- Diagnosis
- Age
- Gender
- Patient type
- Inpatient/outpatient
- Annual utilization
- Remaining limit
- Authorization
- Network
- Date range

Do not hardcode business rules into controllers.

Use a configurable rules/coverage engine.

---

# 12. Benefit Limits

Support:

- Annual limit
- Lifetime limit
- Per-visit limit
- Per-service limit
- Per-admission limit
- Per-day limit
- Per-member limit
- Per-family limit
- Category limit

Track:

```text
Limit
Used
Reserved
Remaining
```

Example:

```text
Annual Hospitalization Limit
----------------------------
Limit:       BDT 500,000
Used:        BDT 175,000
Reserved:    BDT 50,000
Remaining:   BDT 275,000
```

Limits must be calculated transactionally.

---

# 13. Deductible and Co-Payment

Support:

- Fixed deductible
- Percentage deductible
- Fixed co-pay
- Percentage co-pay
- Maximum co-pay
- Service-specific co-pay
- Annual deductible
- Family deductible

Example:

```text
Invoice = BDT 100,000

Insurance = 80%
Patient Co-pay = 20%

Insurance Liability = BDT 80,000
Patient Liability = BDT 20,000
```

Actual calculations must use configurable contract/plan rules.

---

# 14. Exclusions

Support configurable exclusions:

- Non-covered services
- Cosmetic procedures
- Experimental treatment
- Non-covered medicines
- Specific diagnoses
- Specific providers
- Specific facilities
- Out-of-network services
- Expired policy
- Waiting-period services

Every exclusion decision must be traceable.

---

# 15. Pre-Authorization

Pre-authorization is a critical workflow.

Example:

```text
Doctor / Clinical Service
       ↓
Authorization Required
       ↓
Authorization Request
       ↓
Payer / TPA
       ↓
Approved / Partially Approved / Rejected
       ↓
Service
```

Support:

- Authorization number
- Request number
- Patient
- Policy
- Payer
- Service
- Diagnosis
- Requested amount
- Approved amount
- Requested units
- Approved units
- Requested date
- Approved date
- Expiry date
- Clinical justification
- Supporting documents
- Status
- Payer response
- Remarks

---

# 16. Authorization Status

Support:

```text
Draft
Submitted
Under Review
Approved
Partially Approved
Rejected
Expired
Cancelled
Consumed
Closed
```

Authorization must be linked to:

- Patient
- Policy
- Encounter
- Admission where applicable
- Invoice/claimable services
- Claim

---

# 17. Authorization Utilization

Track:

```text
Approved Amount
Consumed Amount
Remaining Amount
```

Example:

```text
Authorization:
BDT 300,000

Consumed:
BDT 220,000

Remaining:
BDT 80,000
```

Prevent unauthorized over-consumption unless explicitly approved.

---

# 18. Claim Generation

Claims should normally originate from finalized Phase 4 billing transactions.

Workflow:

```text
Invoice
 ↓
Insurance Coverage
 ↓
Claimable Items
 ↓
Claim Validation
 ↓
Claim Creation
 ↓
Claim Review
 ↓
Claim Approval
 ↓
Claim Submission
```

Claims should support:

- OPD claims
- IPD claims
- Emergency claims
- Surgery claims
- ICU claims
- Laboratory claims
- Radiology claims
- Pharmacy claims
- Blood bank claims
- Corporate claims
- Package claims

---

# 19. Claim Header

Conceptual fields:

```text
id
organization_id
hospital_id
branch_id
claim_number
payer_id
plan_id
corporate_client_id
patient_id
policy_id
encounter_id
admission_id
authorization_id
invoice_id
claim_type
claim_date
service_from
service_to
submission_deadline
claimed_amount
approved_amount
rejected_amount
patient_amount
status
submitted_at
settled_at
created_by
created_at
updated_at
```

Actual implementation must follow existing repository conventions.

---

# 20. Claim Line Items

Each claim may contain multiple lines.

Example:

```text
Claim
 ├── Consultation
 ├── CBC
 ├── X-Ray
 ├── Medicine
 ├── Bed Charge
 ├── Surgery
 └── Doctor Fee
```

Each claim line should reference the source financial transaction rather than duplicating financial truth.

Fields conceptually:

- Claim
- Invoice
- Invoice line
- Charge
- Service
- Service category
- Quantity
- Unit price
- Gross amount
- Eligible amount
- Covered amount
- Patient amount
- Discount
- Tax
- Deductible
- Co-pay
- Claimed amount
- Approved amount
- Rejected amount
- Denial reason
- Authorization reference

---

# 21. Claim Lifecycle

Recommended lifecycle:

```text
Draft
 ↓
Validated
 ↓
Ready for Submission
 ↓
Submitted
 ↓
Acknowledged
 ↓
Under Review
 ↓
Partially Approved / Approved / Rejected
 ↓
Resubmission / Appeal
 ↓
Settled
 ↓
Closed
```

Alternative statuses:

- Cancelled
- On Hold
- Documentation Required
- Query Received
- Disputed
- Written Off

---

# 22. Claim Validation

Before submission, validate:

- Patient identity
- Policy validity
- Member ID
- Eligibility
- Authorization
- Service coverage
- Diagnosis
- Provider
- Dates
- Invoice status
- Duplicate claim
- Required documents
- Required clinical information
- Claim amount
- Coverage limit
- Co-pay
- Deductible
- Submission deadline
- Contract requirements

Claims failing validation should not silently proceed.

---

# 23. Claim Scrubbing

Implement a claim-scrubbing framework.

Example checks:

```text
Missing member ID
Invalid policy
Expired policy
Missing authorization
Authorization exceeded
Missing diagnosis
Missing service code
Invalid service date
Duplicate invoice
Duplicate claim
Missing attachment
Coverage exceeded
Contract expired
Invalid provider
Missing discharge summary
```

Return structured validation errors.

---

# 24. Claim Submission

Support multiple submission methods:

1. Manual submission
2. Email
3. Payer portal
4. API
5. Batch file
6. Configurable electronic claim format

The architecture must use an adapter pattern.

```php
interface ClaimSubmissionProviderInterface
{
    public function submit(Claim $claim): SubmissionResult;

    public function checkStatus(string $reference): ClaimStatusResult;
}
```

Never hardcode a single insurance provider into the core module.

---

# 25. Submission Tracking

Store:

- Submission reference
- External claim ID
- Submission timestamp
- Submission method
- Payload version
- Response
- Acknowledgement
- Error
- Retry count
- Last retry
- Status

Sensitive payloads must not be logged in plaintext unnecessarily.

---

# 26. Claim Documents

Support documents such as:

- Invoice
- Discharge summary
- Prescription
- Investigation report
- Laboratory report
- Radiology report
- Operative note
- Authorization letter
- Insurance card
- Patient identification
- Clinical notes where contractually required
- Referral
- Other supporting documents

Use the existing File Management module.

Do not create a second file storage engine.

---

# 27. Document Checklist

Each payer/plan can define required documents.

Example:

```text
IPD Claim

✓ Insurance Card
✓ Authorization
✓ Final Invoice
✓ Discharge Summary
✓ Investigation Reports
✓ Prescription
✓ Operative Note
□ Implant Invoice
```

Claim cannot be submitted when mandatory documentation is incomplete unless authorized.

---

# 28. Claim Queries

Payers may request additional information.

Support:

```text
Claim
 ↓
Query
 ↓
Information Requested
 ↓
Internal Assignment
 ↓
Response
 ↓
Documents
 ↓
Resubmission
```

Track:

- Query number
- Claim
- Requested date
- Payer question
- Response
- Responsible user
- Due date
- Documents
- Status

---

# 29. Claim Rejection

Support structured rejection reasons.

Examples:

- Invalid policy
- Eligibility failure
- Missing authorization
- Service excluded
- Limit exceeded
- Duplicate claim
- Documentation incomplete
- Coding issue
- Contract issue
- Late submission
- Incorrect patient/member
- Incorrect service
- Other

Do not allow arbitrary unstructured-only rejection reasons.

---

# 30. Claim Denial Management

Distinguish:

**Rejection**

Technical/administrative failure preventing successful adjudication.

**Denial**

Payer adjudicated the claim or line and refused payment.

Track:

- Denial category
- Denial reason
- Denied amount
- Root cause
- Corrective action
- Appeal eligibility
- Appeal deadline
- Resubmission status

---

# 31. Claim Resubmission

A rejected/denied claim may be resubmitted where allowed.

Never overwrite the original submission.

Use versioning:

```text
Claim
 ├── Submission #1
 ├── Rejection
 ├── Correction
 ├── Submission #2
 └── Final Settlement
```

Maintain full history.

---

# 32. Appeals

Support:

- Appeal creation
- Appeal reason
- Supporting documents
- Appeal deadline
- Assigned staff
- Submission
- Payer response
- Appeal outcome

Statuses:

```text
Draft
Prepared
Submitted
Under Review
Approved
Partially Approved
Rejected
Closed
```

---

# 33. Remittance / Settlement

The module must track payer settlement information.

Example:

```text
Claimed       BDT 500,000
Approved      BDT 450,000
Rejected      BDT 50,000
Paid          BDT 450,000
Outstanding   BDT 0
```

Support:

- Remittance reference
- Payer
- Claim
- Payment date
- Payment amount
- Bank/reference
- Adjustment
- Short payment
- Deduction
- Settlement status

Actual financial posting remains integrated with Phase 4.

---

# 34. Payment Reconciliation

Support:

```text
Payer Payment
 ↓
Remittance
 ↓
Claim Matching
 ↓
Allocation
 ↓
Reconciliation
 ↓
Outstanding
```

Matching methods:

- Claim number
- External claim ID
- Invoice
- Patient/member
- Amount
- Payer reference

Support manual reconciliation where automatic matching fails.

---

# 35. Partial Settlement

Example:

```text
Claimed: BDT 100,000
Approved: BDT 80,000
Paid: BDT 60,000
Outstanding: BDT 20,000
```

The system must distinguish:

- Claimed
- Approved
- Paid
- Outstanding
- Disputed
- Written-off

---

# 36. Corporate Credit Billing

Corporate patients may receive services under contractual credit arrangements.

Example:

```text
Corporate Agreement
       ↓
Employee
       ↓
Hospital Service
       ↓
Invoice
       ↓
Corporate Claim
       ↓
Corporate Settlement
```

Support:

- Credit limit
- Credit period
- Monthly billing
- Service authorization
- Employee eligibility
- Department restrictions
- Cost center
- Purchase/order reference
- Corporate invoice
- Corporate statement

---

# 37. Corporate Claim Batches

Support batch claims:

```text
Corporate Client
 ↓
Billing Period
 ↓
Eligible Invoices
 ↓
Claim Batch
 ↓
Validation
 ↓
Submission
```

Example:

```text
Corporate:
ABC Ltd.

Period:
01–30 September

Invoices:
125

Total:
BDT 4,850,000
```

---

# 38. Claim Batch Management

Support:

- Batch number
- Payer
- Corporate
- Period
- Number of claims
- Total amount
- Validation status
- Submission status
- Settlement status
- Created by
- Approved by

---

# 39. Patient Responsibility

After insurance adjudication, calculate remaining patient responsibility.

Example:

```text
Invoice                 100,000
Insurance approved       75,000
Co-pay                    15,000
Non-covered               10,000
-------------------------------
Patient responsibility    25,000
```

The final patient financial position must remain consistent with Phase 4.

---

# 40. Coordination of Benefits

Provide a foundation for multiple payers:

```text
Primary Insurance
       ↓
Secondary Insurance
       ↓
Patient
```

Track:

- Primary payer
- Secondary payer
- Claim sequence
- Amount paid by primary
- Remaining eligible amount
- Secondary claim

Do not duplicate billing transactions.

---

# 41. Claim Coding

Create configurable mapping between:

- Hospital service code
- Billing service
- Insurance claim code
- Payer-specific code
- Diagnosis code
- Procedure code
- Revenue/category code

Do not hardcode payer-specific mappings into controllers.

Use configurable mapping tables.

---

# 42. Diagnosis / Procedure Integration

Claims may require:

- ICD-10
- ICD-11
- CPT
- HCPCS
- Local procedure codes
- Payer-specific codes

The system should support code mappings without replacing the clinical coding source.

Clinical diagnosis remains owned by the clinical/EMR modules.

---

# 43. Package Billing

Support insurance/corporate packages.

Examples:

```text
Health Checkup Package
Maternity Package
Surgery Package
Corporate Annual Package
```

Package rules should integrate with Phase 4 Billing rather than creating another invoice engine.

---

# 44. Admission / IPD Integration

For inpatient claims:

```text
Admission
 ↓
Insurance Verification
 ↓
Preauthorization
 ↓
Daily / Interim Charges
 ↓
Clinical Documentation
 ↓
Discharge
 ↓
Final Invoice
 ↓
Claim
```

Support:

- Admission authorization
- Extension authorization
- Room category
- Length-of-stay limit
- Daily benefit
- Final claim

---

# 45. Emergency Integration

Emergency treatment may begin before insurance authorization.

Support:

- Emergency authorization
- Retroactive authorization where permitted
- Emergency claim flag
- Authorization exception reason
- Audit trail

Never automatically assume emergency services are covered.

---

# 46. Surgery Integration

Integrate with Phase 10:

```text
Surgery Request
 ↓
Authorization
 ↓
Procedure
 ↓
Charges
 ↓
Claim
```

Support authorization for:

- Surgery
- Implant
- OT
- Anesthesia
- Surgeon fee
- Assistant fee
- Blood products

---

# 47. Pharmacy Integration

Integrate with Phase 7.

Claim pharmacy services based on:

- Prescription
- Dispensing
- Medication
- Quantity
- Coverage
- Formulary
- Authorization

Do not duplicate medication or dispensing records.

---

# 48. Laboratory / Radiology Integration

Use Phase 5 and Phase 6 records.

Example:

```text
Lab Order
 ↓
Lab Result
 ↓
Billing Charge
 ↓
Insurance Claim Line
```

Do not duplicate clinical test/result data inside insurance claims.

---

# 49. Blood Bank Integration

Blood-related charges may originate from Phase 13.

Insurance module may claim:

- Blood component
- Crossmatch
- Processing
- Transfusion service

Phase 13 remains the clinical blood-bank source of truth.

---

# 50. Claim Documents and Privacy

Insurance documents may contain sensitive clinical information.

Requirements:

- Private storage
- Authorization-based access
- Download auditing
- Export auditing
- Encryption where appropriate
- Signed URLs or equivalent controlled access
- No public URLs
- Document access by claim/patient/payer scope

---

# 51. Suggested Database Tables

Actual tables must be consolidated with existing repository structures.

Suggested tables:

```text
insurance_payers
insurance_payer_contacts
insurance_plans
insurance_plan_benefits
insurance_plan_limits
insurance_plan_exclusions
insurance_contracts
insurance_contract_services
insurance_contract_rates
insurance_contract_rules

corporate_clients
corporate_contacts
corporate_contracts
corporate_contract_services
corporate_employees
corporate_dependents

patient_insurance_policies
patient_insurance_policy_members
insurance_eligibility_checks
insurance_eligibility_results

insurance_authorizations
insurance_authorization_items
insurance_authorization_events

insurance_coverage_rules
insurance_coverage_rule_conditions
insurance_coverage_calculations

insurance_claims
insurance_claim_items
insurance_claim_events
insurance_claim_submissions
insurance_claim_submission_attempts

insurance_claim_documents
insurance_claim_queries
insurance_claim_query_responses

insurance_claim_rejections
insurance_claim_denials
insurance_claim_resubmissions
insurance_claim_appeals

insurance_remittances
insurance_remittance_items
insurance_claim_settlements
insurance_claim_adjustments

insurance_claim_batches
insurance_claim_batch_items

insurance_payer_mappings
insurance_service_mappings
insurance_diagnosis_mappings
insurance_procedure_mappings

insurance_package_rules
insurance_cob_records

insurance_chargeable_events
```

The AI must first inspect the existing schema and reuse/consolidate equivalent tables.

---

# 52. Multi-Tenant / Multi-Hospital Data Model

All relevant entities should carry appropriate organizational scope.

Typical:

```text
organization_id
hospital_id
branch_id
```

Where appropriate also:

```text
department_id
```

Payer-level records may be organization-wide.

Hospital-specific contracts must remain hospital-scoped.

---

# 53. Security

Critical rule:

> A user authorized for Hospital A must never access Hospital B insurance policies, claims, contracts, or financial data unless explicitly assigned access.

Test:

```text
Hospital A user
       ↓
Claim ID belonging to Hospital B
       ↓
403 / Not Found according to application policy
```

Never rely solely on UI filtering.

Enforce access server-side through:

- Policies
- Gates
- Query scopes
- Service-layer authorization
- Hospital/branch scope

---

# 54. Permissions

Suggested permissions:

```text
insurance.dashboard.view

insurance.payer.view
insurance.payer.create
insurance.payer.update
insurance.payer.manage

insurance.plan.view
insurance.plan.create
insurance.plan.update
insurance.plan.manage

insurance.contract.view
insurance.contract.create
insurance.contract.update
insurance.contract.approve

insurance.corporate.view
insurance.corporate.create
insurance.corporate.update
insurance.corporate.manage

insurance.policy.view
insurance.policy.create
insurance.policy.update
insurance.policy.verify

insurance.eligibility.view
insurance.eligibility.verify

insurance.authorization.view
insurance.authorization.create
insurance.authorization.submit
insurance.authorization.approve
insurance.authorization.cancel

insurance.claim.view
insurance.claim.create
insurance.claim.validate
insurance.claim.approve
insurance.claim.submit
insurance.claim.cancel

insurance.claim.query
insurance.claim.resubmit
insurance.claim.appeal

insurance.remittance.view
insurance.remittance.create
insurance.remittance.allocate
insurance.remittance.reconcile

insurance.report.view
insurance.report.export

insurance.audit.view
insurance.settings.manage
```

---

# 55. Suggested Roles

```text
Insurance Receptionist
Insurance Verification Officer
Insurance Coordinator
Corporate Billing Officer
Claims Officer
Senior Claims Officer
Insurance Manager
Corporate Accounts Officer
Payer Relations Officer
Medical Claims Reviewer
Insurance Administrator
Finance Reconciliation Officer
Hospital Administrator
```

Do not create separate user authentication.

Reuse the existing user/RBAC system.

---

# 56. Claim Review Workflow

A claim should pass through controlled workflow:

```text
Draft
 ↓
Validation
 ↓
Medical / Administrative Review
 ↓
Financial Review
 ↓
Approval
 ↓
Submission
```

Approval requirements should be configurable.

High-value claims may require additional approval.

---

# 57. Workflow / Approval Integration

Reuse the existing Workflow/Approval module.

Examples:

```text
Claim > BDT 100,000
      ↓
Claims Manager Approval

Claim > BDT 500,000
      ↓
Senior Management Approval
```

Do not hardcode approval thresholds.

---

# 58. Audit Trail

Audit:

- Payer creation/change
- Plan changes
- Contract changes
- Policy changes
- Eligibility verification
- Authorization
- Claim creation
- Claim modification
- Claim validation
- Claim approval
- Claim submission
- Claim rejection
- Claim denial
- Resubmission
- Appeal
- Remittance
- Settlement
- Reconciliation
- Document access
- Document export
- Manual adjustment
- Write-off
- Break-glass access

Critical records must support historical versions.

---

# 59. Financial Integrity

The insurance module must never directly modify financial totals without controlled integration with Phase 4.

Examples:

```text
Invoice Total
Insurance Eligible
Insurance Claimed
Insurance Approved
Insurance Paid
Patient Responsibility
Outstanding
```

These values must reconcile with the billing ledger.

---

# 60. Idempotency

Claim submission and financial events must be idempotent.

Example key:

```text
claim:{claim_id}:submission:{submission_version}
```

or an equivalent repository-standard key.

Repeated processing must not create duplicate:

- Claims
- Submission records
- Chargeable events
- Settlements
- Payments

---

# 61. Concurrency Protection

Use database transactions and row locking where required.

Protect:

- Benefit limit consumption
- Authorization utilization
- Claim generation
- Duplicate claim prevention
- Batch generation
- Remittance allocation
- Settlement
- Reconciliation
- Write-offs

Example:

Two users must not consume the same remaining authorization amount simultaneously.

---

# 62. Claim Number Generation

Use a concurrency-safe generator.

Example:

```text
CLM-2026-00001234
```

Never:

```php
Claim::count() + 1
```

Use a proper sequence/generator.

---

# 63. Corporate Invoice/Claim Number

Example:

```text
CORP-CLM-2026-00000125
```

Again, generation must be concurrency-safe.

---

# 64. API

Base:

```text
/api/v1/insurance
```

Suggested endpoints:

```text
GET    /payers
POST   /payers
GET    /payers/{id}
PUT    /payers/{id}

GET    /plans
POST   /plans

GET    /contracts
POST   /contracts

GET    /corporate-clients
POST   /corporate-clients

GET    /policies
POST   /policies
GET    /policies/{id}

POST   /eligibility/check
GET    /eligibility/{id}

GET    /authorizations
POST   /authorizations
POST   /authorizations/{id}/submit
POST   /authorizations/{id}/approve
POST   /authorizations/{id}/cancel

GET    /claims
POST   /claims
GET    /claims/{id}
POST   /claims/{id}/validate
POST   /claims/{id}/approve
POST   /claims/{id}/submit
POST   /claims/{id}/resubmit
POST   /claims/{id}/appeal

GET    /claim-batches
POST   /claim-batches

GET    /queries
POST   /claims/{id}/queries

GET    /remittances
POST   /remittances
POST   /remittances/{id}/allocate
POST   /remittances/{id}/reconcile

GET    /reports/claims
GET    /reports/aging
GET    /reports/rejections
GET    /reports/denials
GET    /reports/settlements
```

Use the existing API response structure:

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
  "errors": {
    "policy_number": [
      "The policy number is required."
    ]
  }
}
```

Never expose:

- SQL errors
- Stack traces
- File paths
- Secrets
- Internal credentials

---

# 65. Events

Suggested domain events:

```text
InsurancePayerCreated
InsurancePlanCreated
InsuranceContractActivated
InsurancePolicyCreated
InsuranceEligibilityChecked

InsuranceAuthorizationCreated
InsuranceAuthorizationSubmitted
InsuranceAuthorizationApproved
InsuranceAuthorizationRejected

InsuranceClaimCreated
InsuranceClaimValidated
InsuranceClaimApproved
InsuranceClaimSubmitted
InsuranceClaimAcknowledged
InsuranceClaimRejected
InsuranceClaimDenied
InsuranceClaimResubmitted
InsuranceClaimAppealed

InsuranceClaimQueryCreated
InsuranceClaimQueryResolved

InsuranceRemittanceCreated
InsuranceRemittanceAllocated
InsuranceClaimSettled

CorporateClaimBatchCreated
CorporateClaimBatchSubmitted

InsuranceCoverageLimitConsumed
InsuranceCoverageLimitExceeded
```

---

# 66. Background Jobs

Suggested jobs:

```text
CheckPolicyExpiry
CheckAuthorizationExpiry
CheckClaimSubmissionDeadlines
GenerateClaimSubmissionQueue
ValidatePendingClaims
GenerateCorporateClaimBatches
GenerateInsuranceAgingReport
GenerateClaimDenialReport
ProcessPayerSubmissionStatus
ProcessClaimSubmissionRetries
ProcessRemittanceMatching
DetectOutstandingClaims
DetectUnreconciledPayments
GenerateCoverageLimitAlerts
```

All jobs must be:

- Retry-safe
- Idempotent
- Observable
- Logged
- Failure-aware

---

# 67. Notifications

Notify relevant users for:

- Policy expiring
- Authorization expiring
- Authorization approved
- Authorization rejected
- Claim ready
- Claim validation failure
- Submission failure
- Claim rejected
- Claim denied
- Query received
- Appeal deadline
- Claim payment received
- Settlement completed
- Outstanding claim aging
- Corporate billing deadline

Use the existing Notification system.

---

# 68. Claim Aging

Provide aging buckets:

```text
0–30 days
31–60 days
61–90 days
91–120 days
121–180 days
180+ days
```

Support configurable aging periods.

---

# 69. Dashboard

Insurance dashboard:

```text
Active Payers
Active Plans
Active Policies
Pending Eligibility Checks

Pending Authorizations
Approved Authorizations
Expiring Authorizations

Draft Claims
Claims Ready
Submitted Claims
Pending Claims

Rejected Claims
Denied Claims
Appeals

Claimed Amount
Approved Amount
Paid Amount
Outstanding Amount

Corporate Receivables
Insurance Receivables

Aging
```

Charts:

- Claims by payer
- Claims by hospital
- Claims by department
- Claim value
- Approval rate
- Rejection reasons
- Denial reasons
- Outstanding aging
- Settlement trend
- Corporate utilization

Avoid exposing sensitive patient data on broad dashboards unless authorized.

---

# 70. Reports

Required reports:

## Payer Reports

- Payer list
- Plan utilization
- Contract status
- Contract expiry

## Policy Reports

- Active policies
- Expiring policies
- Eligibility failures
- Corporate members

## Authorization Reports

- Pending
- Approved
- Rejected
- Expiring
- Utilization

## Claim Reports

- Claims by payer
- Claims by hospital
- Claims by department
- Claims by service
- Claim volume
- Claim value
- Approval
- Rejection
- Denial

## Financial Reports

- Claimed amount
- Approved amount
- Paid amount
- Outstanding
- Aging
- Settlement
- Short payment
- Adjustments

## Corporate Reports

- Employee utilization
- Dependent utilization
- Corporate claim value
- Monthly statement
- Outstanding corporate receivables

---

# 71. Traceability

A claim must be traceable back to its origin:

```text
Claim
 ↓
Claim Line
 ↓
Invoice Line
 ↓
Charge
 ↓
Clinical / Operational Event
 ↓
Encounter / Admission
 ↓
Patient
```

Never create disconnected claim transactions.

---

# 72. Claim Timeline

Each claim should provide:

```text
Claim Created
↓
Validation
↓
Review
↓
Approval
↓
Submission
↓
Acknowledgement
↓
Payer Query
↓
Response
↓
Adjudication
↓
Settlement
```

Each event should show:

- Date/time
- User/system
- Status
- Action
- Reference
- Notes

---

# 73. File Management

Use existing File Management.

Claim documents must support:

- Versioning
- Document type
- Upload user
- Timestamp
- Related claim
- Access control
- Download audit
- Expiry where appropriate

---

# 74. Data Privacy

Insurance claims may contain:

- Patient identity
- Medical information
- Diagnosis
- Procedures
- Medication
- Financial data

Implement:

- Least privilege
- Hospital scope
- Department scope where needed
- Payer access isolation
- Audit logging
- Controlled export
- Secure file access

Do not expose clinical details to users who only need financial claim information.

---

# 75. Break-Glass Access

Where exceptional access is required:

```text
Normal access denied
       ↓
Break-glass reason
       ↓
Temporary access
       ↓
Audit
       ↓
Security monitoring
```

Break-glass should not silently bypass authorization.

---

# 76. FHIR Readiness

Prepare mappings for:

```text
Patient
Coverage
InsurancePlan
Organization
Practitioner
PractitionerRole
Encounter
Account
Claim
ClaimResponse
ExplanationOfBenefit
CoverageEligibilityRequest
CoverageEligibilityResponse
ServiceRequest
Procedure
Condition
Observation
DiagnosticReport
DocumentReference
Consent
```

FHIR implementation must be integration-ready without forcing an immediate external FHIR server.

---

# 77. HL7 Readiness

Prepare for:

```text
ADT
DFT
ORM
ORU
```

Where appropriate, support future insurance/financial integration messages.

---

# 78. External Payer Integration

Use adapter interfaces.

Examples:

```php
interface EligibilityProviderInterface
{
    public function check(EligibilityRequest $request): EligibilityResult;
}
```

```php
interface AuthorizationProviderInterface
{
    public function submit(AuthorizationRequest $request): AuthorizationResult;
}
```

```php
interface ClaimSubmissionProviderInterface
{
    public function submit(Claim $claim): SubmissionResult;

    public function checkStatus(string $reference): ClaimStatusResult;
}
```

```php
interface RemittanceProviderInterface
{
    public function import($source): RemittanceResult;
}
```

Provider-specific implementations must stay outside core domain logic.

---

# 79. AI Readiness

AI may assist with:

## Claim Validation

Identify:

- Missing information
- Missing documents
- Possible duplicate claims
- Coverage inconsistencies
- Authorization mismatch

## Claim Denial Prediction

Identify claims with patterns associated with previous rejection/denial.

AI output must be:

```text
Prediction
Confidence
Reasons
Supporting data
```

It must not automatically reject a claim.

## Documentation Assistance

AI may identify missing documentation.

## Claim Summarization

Generate:

- Claim summary
- Clinical/service summary
- Financial summary
- Payer communication summary

## Coding Assistance

Suggest:

- Service-code mappings
- Diagnosis-code mappings
- Payer-specific mappings

Human review remains mandatory.

## Revenue / Receivable Forecasting

Forecast:

- Claim settlement
- Outstanding receivables
- Aging
- Cash collection

Forecasts are advisory.

## Corporate Utilization Analytics

Identify:

- Utilization patterns
- High-cost service categories
- Frequency trends
- Unusual billing patterns

Use privacy-aware aggregation.

---

# 80. AI Safety Rules

AI MUST NEVER autonomously:

- Approve insurance coverage
- Reject a patient claim
- Deny a claim
- Approve medical authorization
- Determine medical necessity as final authority
- Change patient responsibility
- Modify invoice totals
- Modify insurance policy
- Modify clinical diagnosis
- Modify prescription
- Submit an irreversible claim without configured human authorization
- Approve a financial write-off
- Alter a payer contract
- Override coverage limits

AI is advisory.

Final decisions belong to authorized human users.

---

# 81. AI Explainability

Every AI recommendation should provide, where technically feasible:

```text
Recommendation
Confidence
Key factors
Source records
Timestamp
AI model/version
Reviewer
Final decision
```

AI-generated output must be distinguishable from human-entered information.

---

# 82. AI Audit

Record:

```text
AI request
AI model/version
Input reference
Output
Confidence
User
Timestamp
Human decision
```

Never store unnecessary sensitive patient data in AI prompts or logs.

---

# 83. AI Privacy

Before sending data to external AI systems:

- Minimize data
- Remove unnecessary identifiers
- Apply organization-approved privacy controls
- Do not send sensitive clinical data unless explicitly authorized
- Maintain audit trail
- Respect data-retention policy

Prefer internal/private AI infrastructure where appropriate.

---

# 84. Service Layer

Use services/actions rather than large controllers.

Examples:

```text
CreateInsurancePolicyAction
VerifyEligibilityAction
CalculateCoverageAction
CreateAuthorizationAction
SubmitAuthorizationAction
GenerateClaimAction
ValidateClaimAction
SubmitClaimAction
ProcessClaimResponseAction
CreateClaimQueryAction
ResubmitClaimAction
CreateAppealAction
CreateRemittanceAction
AllocateRemittanceAction
ReconcileSettlementAction
GenerateCorporateClaimBatchAction
```

---

# 85. Claim Calculation Engine

Use a dedicated service/rule layer:

```text
Coverage Calculation
        ↓
Eligible Amount
        ↓
Deductible
        ↓
Co-pay
        ↓
Non-covered
        ↓
Insurance Liability
        ↓
Patient Liability
```

Calculations must be deterministic and auditable.

---

# 86. Claim State Machine

Do not allow arbitrary status changes.

Example:

```text
Draft
 ↓
Validated
 ↓
Ready for Submission
 ↓
Submitted
 ↓
Acknowledged
 ↓
Under Review
 ↓
Approved / Partially Approved / Rejected / Denied
 ↓
Resubmission / Appeal
 ↓
Settled
 ↓
Closed
```

State transitions should be implemented through a domain/service layer.

---

# 87. Clinical and Financial Separation

Maintain strict separation:

### Clinical

Owned by:

- EMR
- LIS
- RIS
- Pharmacy
- IPD
- Nursing
- OT
- ICU
- Emergency
- Blood Bank

### Financial

Owned by:

- Billing

### Payer/Claim

Owned by:

- Insurance & Corporate Claims

The insurance module consumes approved financial/clinical references and manages payer workflow.

---

# 88. Performance

Important indexes should include appropriate combinations around:

```text
payer_id
plan_id
patient_id
policy_id
corporate_client_id
claim_number
invoice_id
authorization_id
status
claim_date
submitted_at
settled_at
hospital_id
branch_id
created_at
```

Use actual query analysis to determine final indexes.

Avoid N+1 queries.

Use:

- Pagination
- Eager loading
- Query scopes
- Aggregation queries
- Queued processing
- Caching for relatively static masters

---

# 89. Testing

Implement:

- Unit tests
- Feature tests
- Integration tests
- API tests
- Authorization tests
- Multi-hospital tests
- Workflow tests
- Calculation tests
- Concurrency tests
- Submission adapter tests
- File-access tests
- AI safety tests

---

# 90. Critical Financial Tests

Test:

### Coverage

```text
Invoice = 100,000
Coverage = 80%
Expected insurance = 80,000
```

### Co-pay

```text
Invoice = 100,000
Co-pay = 20%
Expected patient = 20,000
```

### Deductible

Verify deductible is applied exactly once.

### Limit

Verify:

```text
Available limit = 50,000
Claim = 60,000
```

does not silently approve 60,000.

### Concurrent utilization

Two simultaneous claims must not consume the same remaining limit incorrectly.

---

# 91. Security Tests

Mandatory negative tests:

```text
Hospital A user → Hospital B claim
Hospital A user → Hospital B policy
Hospital A user → Hospital B contract
Unauthorized user → claim approval
Unauthorized user → claim submission
Unauthorized user → remittance allocation
Unauthorized user → financial export
Unauthorized user → claim document download
```

All must be blocked.

---

# 92. Claim Integrity Tests

Test:

- Duplicate claim
- Duplicate invoice claim
- Expired policy
- Invalid policy
- Missing authorization
- Expired authorization
- Exceeded authorization
- Exceeded benefit limit
- Missing document
- Duplicate submission
- Invalid payer mapping
- Invalid service mapping
- Late submission
- Resubmission versioning
- Partial settlement
- Duplicate remittance allocation

---

# 93. Concurrency Tests

Specifically test:

```text
Two users generate claim from same invoice
Two users consume same authorization
Two users consume same benefit limit
Two users allocate same remittance
Two users settle same claim
Two workers submit same claim
```

Use:

- Database transactions
- Row locking
- Unique constraints
- Idempotency keys
- State validation

---

# 94. UI Menu

Recommended:

```text
Insurance & Corporate
├── Dashboard
├── Payers
│   ├── Payer List
│   ├── Contacts
│   └── Settings
├── Insurance Plans
├── Contracts
├── Corporate Clients
│   ├── Companies
│   ├── Employees
│   └── Dependents
├── Patient Policies
├── Eligibility
├── Authorizations
├── Claims
│   ├── All Claims
│   ├── Draft
│   ├── Validation
│   ├── Ready for Submission
│   ├── Submitted
│   ├── Rejected
│   ├── Denied
│   ├── Appeals
│   └── Closed
├── Claim Batches
├── Claim Queries
├── Remittances
├── Reconciliation
├── Corporate Billing
├── Aging
├── Reports
├── Traceability
└── Settings
```

---

# 95. Claim Detail Screen

Recommended tabs:

```text
Overview
Patient
Policy
Eligibility
Authorization
Invoices
Claim Lines
Coverage Calculation
Documents
Submission History
Queries
Rejections
Denials
Appeals
Remittance
Settlement
Audit Timeline
```

---

# 96. Corporate Client Screen

Tabs:

```text
Overview
Contract
Employees
Dependents
Eligibility
Services
Coverage
Claims
Invoices
Statements
Payments
Outstanding
Documents
Audit
```

---

# 97. Implementation Order

Implement in this order:

```text
1. Inspect existing architecture
2. Inspect Phases 0–13
3. Map existing billing and patient structures
4. Define integration boundaries
5. Payer master
6. Insurance plan master
7. Corporate client master
8. Contracts
9. Coverage rules
10. Patient insurance policy
11. Subscriber/dependent
12. Eligibility
13. Benefit limits
14. Deductible/co-pay engine
15. Authorization
16. Claim generation
17. Claim validation/scrubbing
18. Claim workflow
19. Submission adapters
20. Claim queries
21. Rejections/denials
22. Resubmission
23. Appeals
24. Remittance
25. Reconciliation
26. Corporate claim batches
27. Aging
28. Reports
29. File/document integration
30. Notifications
31. API
32. FHIR readiness
33. HL7 readiness
34. AI assistance
35. Security testing
36. Concurrency testing
37. Performance testing
38. Documentation
```

---

# 98. Non-Goals

Phase 14 must NOT become:

- A second billing system
- A second accounting system
- A second patient registry
- A second clinical record
- A second pharmacy
- A second laboratory
- A second radiology system
- A second admission system
- A second document management system

It does not own:

- General ledger
- Accounts payable
- General patient billing
- Clinical diagnosis
- Clinical documentation
- Medication dispensing
- Laboratory results
- Radiology reports
- Bed management

---

# 99. Definition of Done

Phase 14 is complete only when:

### Payer

- Payers implemented
- Plans implemented
- Contracts implemented

### Corporate

- Corporate clients implemented
- Employees/dependents supported
- Corporate eligibility implemented

### Patient Insurance

- Policies implemented
- Eligibility implemented
- Coverage implemented
- Limits implemented
- Deductibles/co-pay implemented

### Authorization

- Authorization lifecycle implemented
- Utilization tracked
- Expiry handled

### Claims

- Claim generation implemented
- Claim validation implemented
- Claim scrubbing implemented
- Claim submission implemented
- Claim tracking implemented
- Rejection/denial implemented
- Resubmission implemented
- Appeal implemented

### Financial

- Remittance implemented
- Settlement implemented
- Reconciliation implemented
- Aging implemented
- Phase 4 integration verified

### Integration

- Patient integrated
- Encounter integrated
- Billing integrated
- IPD integrated
- Pharmacy integrated
- Lab integrated
- Radiology integrated
- OT integrated
- ICU integrated
- Emergency integrated
- Blood Bank integrated
- File Management integrated
- Notifications integrated
- Audit integrated

### Security

- Multi-hospital isolation verified
- RBAC verified
- Document access verified
- Export authorization verified
- Audit verified

### Reliability

- Concurrency protection implemented
- Idempotency implemented
- Duplicate prevention implemented
- State transitions enforced

### Interoperability

- FHIR mapping prepared
- HL7 readiness prepared
- External payer adapter architecture implemented

### AI

- AI extension points implemented
- Human review required
- AI actions audited
- AI cannot make autonomous financial/coverage decisions

### Testing

- Unit tests
- Feature tests
- API tests
- Security tests
- Integration tests
- Calculation tests
- Concurrency tests
- Negative tests

No feature should be marked complete merely because its UI exists.

---

# 100. Final AI Implementation Prompt

Use the following prompt with an AI coding agent:

```text
You are a senior Laravel enterprise architect, healthcare information-system engineer, insurance-claims architect, database engineer, security engineer, and QA engineer.

Implement Phase 14 — Insurance & Corporate Claims in the existing Hospital Management System.

IMPORTANT:

The existing Laravel modular-monolith architecture is ALREADY IMPLEMENTED.

DO NOT create a second modular framework.

DO NOT replace the existing architecture.

DO NOT create duplicate implementations of Patient, MPI, User, RBAC, Organization, Hospital, Branch, Department, Appointment, Encounter, EMR, Billing, Invoice, Payment, Laboratory, Radiology, Pharmacy, IPD, Nursing, OT, ICU, Emergency, Blood Bank, Notifications, Audit, File Management, Workflow, or API infrastructure.

FIRST inspect the repository and understand the existing architecture.

Then inspect Phases 0–13 and identify:

- Existing modules
- Models
- Tables
- Relationships
- Services
- Actions
- Policies
- Permissions
- Events
- Listeners
- Jobs
- Notifications
- Workflow infrastructure
- Audit infrastructure
- File management
- API conventions
- UI conventions
- Billing architecture
- Patient/Encounter architecture

Build Phase 14 as a native extension of the existing system.

==================================================
PRIMARY OBJECTIVE
==================================================

Implement a production-grade Insurance & Corporate Claims module supporting:

Payer
→ Plan
→ Contract
→ Corporate Client
→ Patient Policy
→ Eligibility
→ Coverage
→ Benefit Limits
→ Preauthorization
→ Billing Integration
→ Claim Generation
→ Claim Validation
→ Claim Scrubbing
→ Claim Submission
→ Payer Response
→ Rejection/Denial
→ Resubmission
→ Appeal
→ Remittance
→ Settlement
→ Reconciliation
→ Aging
→ Reporting

==================================================
ARCHITECTURAL RULES
==================================================

1. Reuse the existing modular architecture.
2. Do not create a second module framework.
3. Reuse existing Patient/MPI.
4. Reuse existing Billing.
5. Reuse existing Invoice/Payment.
6. Reuse existing Encounter.
7. Reuse existing Admission.
8. Reuse existing clinical modules.
9. Reuse existing File Management.
10. Reuse existing Workflow.
11. Reuse existing Audit.
12. Reuse existing Notifications.
13. Reuse existing RBAC.
14. Reuse existing API conventions.

Insurance & Corporate Claims owns payer and claim lifecycle only.

Billing remains the financial source of truth.

Clinical systems remain the clinical source of truth.

==================================================
IMPLEMENT
==================================================

Implement:

1. Payers
2. Insurance Plans
3. Plan Benefits
4. Plan Limits
5. Exclusions
6. Provider Contracts
7. Corporate Clients
8. Corporate Employees
9. Corporate Dependents
10. Patient Insurance Policies
11. Subscriber relationships
12. Eligibility verification
13. Coverage rules
14. Deductibles
15. Co-pay
16. Benefit utilization
17. Preauthorization
18. Authorization utilization
19. Claim generation
20. Claim lines
21. Claim validation
22. Claim scrubbing
23. Claim workflow
24. Claim submission
25. Submission tracking
26. Payer response
27. Claim queries
28. Rejections
29. Denials
30. Resubmissions
31. Appeals
32. Remittances
33. Settlement
34. Reconciliation
35. Corporate claim batches
36. Claim aging
37. Reporting
38. Traceability
39. File/document integration
40. Notifications
41. API
42. FHIR readiness
43. HL7 readiness
44. External payer adapter architecture
45. AI extension points

==================================================
DATABASE
==================================================

Inspect the existing database before creating migrations.

Reuse equivalent tables.

Do not create duplicate concepts.

Where new tables are required, use existing naming conventions.

Ensure:

- Foreign keys
- Unique constraints
- Proper indexes
- Organization scope
- Hospital scope
- Branch scope
- Created/updated timestamps
- Soft deletes only where appropriate
- Immutable financial/claim history where required

Use transactions for critical operations.

==================================================
CLAIM GENERATION
==================================================

Claims must originate from finalized billing records.

Implement:

Invoice
→ Insurance Coverage
→ Claimable Lines
→ Claim
→ Validation
→ Submission

Never create an independent invoice engine.

==================================================
COVERAGE ENGINE
==================================================

Create a clean service/rule architecture for:

- Coverage percentage
- Fixed coverage
- Co-pay
- Deductible
- Service limits
- Annual limits
- Lifetime limits
- Per-admission limits
- Per-service limits
- Exclusions
- Authorization requirements

Calculations must be deterministic and auditable.

==================================================
AUTHORIZATION
==================================================

Implement:

Draft
→ Submitted
→ Under Review
→ Approved
→ Partially Approved
→ Rejected
→ Expired
→ Consumed
→ Closed

Track approved and consumed amounts.

Prevent unauthorized over-consumption.

==================================================
CLAIM WORKFLOW
==================================================

Implement controlled state transitions:

Draft
→ Validated
→ Ready for Submission
→ Submitted
→ Acknowledged
→ Under Review
→ Approved / Partially Approved / Rejected / Denied
→ Resubmission / Appeal
→ Settled
→ Closed

Do not permit arbitrary status updates.

==================================================
CLAIM SCRUBBING
==================================================

Validate:

- Patient
- Policy
- Member ID
- Eligibility
- Authorization
- Coverage
- Limits
- Dates
- Invoice
- Duplicate claim
- Required documents
- Service mappings
- Diagnosis mappings
- Submission deadline

Return structured validation errors.

==================================================
EXTERNAL PAYER ADAPTERS
==================================================

Implement interfaces for:

Eligibility
Authorization
Claim Submission
Claim Status
Remittance

Use adapter pattern.

Provider-specific code must not contaminate core domain logic.

==================================================
FINANCIAL INTEGRITY
==================================================

Never directly manipulate Billing financial truth outside approved integration services.

Maintain:

Claimed
Approved
Paid
Outstanding
Patient Responsibility

Ensure reconciliation with Phase 4 Billing.

==================================================
CORPORATE CLAIMS
==================================================

Implement:

Corporate Client
→ Employees
→ Dependents
→ Eligibility
→ Services
→ Invoices
→ Claim Batch
→ Submission
→ Settlement

Support monthly/batch claims.

==================================================
SECURITY
==================================================

Implement server-side authorization.

A Hospital A user MUST NOT access Hospital B:

- Policies
- Claims
- Authorizations
- Contracts
- Corporate records
- Claim documents
- Reports

Test direct ID manipulation and API access.

Never rely only on UI restrictions.

==================================================
DOCUMENT SECURITY
==================================================

Reuse existing File Management.

Documents must be:

- Private
- Access-controlled
- Audited
- Versioned where required
- Protected from unauthorized download

==================================================
AUDIT
==================================================

Audit all critical operations.

Especially:

- Policy changes
- Eligibility
- Authorization
- Claim creation
- Claim validation
- Claim approval
- Claim submission
- Claim rejection
- Claim denial
- Resubmission
- Appeal
- Remittance
- Settlement
- Reconciliation
- Write-off
- Document download
- Export

==================================================
CONCURRENCY
==================================================

Protect:

- Benefit limit consumption
- Authorization utilization
- Duplicate claim creation
- Claim submission
- Remittance allocation
- Settlement

Use:

- Transactions
- Row locking
- Unique constraints
- Idempotency keys
- State validation

Two simultaneous workers must not create duplicate financial/claim transactions.

==================================================
NUMBER GENERATION
==================================================

Implement concurrency-safe identifiers.

Examples:

CLM-2026-00001234
CORP-CLM-2026-00000125

NEVER use:

Model::count() + 1

==================================================
API
==================================================

Implement REST APIs under:

/api/v1/insurance

Follow the existing API architecture and response format.

Do not expose:

- SQL
- stack traces
- server paths
- credentials
- internal secrets

==================================================
EVENTS
==================================================

Implement appropriate domain events for:

- Policy creation
- Eligibility
- Authorization
- Claim creation
- Claim validation
- Claim submission
- Rejection
- Denial
- Resubmission
- Appeal
- Remittance
- Settlement
- Corporate batch

Use existing event conventions.

==================================================
JOBS
==================================================

Implement background jobs for:

- Policy expiry
- Authorization expiry
- Claim deadlines
- Claim validation
- Claim batch generation
- Submission retries
- Payer status polling
- Remittance matching
- Aging
- Coverage-limit alerts

All jobs must be idempotent.

==================================================
AI
==================================================

Create AI extension points for:

- Claim validation assistance
- Missing-document detection
- Duplicate claim detection
- Denial prediction
- Claim summarization
- Coding suggestions
- Receivable forecasting
- Corporate utilization analytics

AI MUST NEVER autonomously:

- Approve coverage
- Reject claims
- Deny claims
- Change invoice amounts
- Change patient liability
- Approve medical authorization
- Modify policy rules
- Approve write-offs
- Override benefit limits

AI recommendations require human review.

Audit AI outputs.

==================================================
INTEROPERABILITY
==================================================

Prepare mappings for:

Patient
Coverage
InsurancePlan
Organization
Practitioner
Encounter
Account
Claim
ClaimResponse
ExplanationOfBenefit
CoverageEligibilityRequest
CoverageEligibilityResponse
ServiceRequest
Procedure
Condition
Observation
DiagnosticReport
DocumentReference
Consent

Prepare HL7 readiness for appropriate:

ADT
DFT
ORM
ORU

==================================================
TESTING
==================================================

Write tests for:

- Payer
- Plan
- Contract
- Policy
- Eligibility
- Coverage
- Deductible
- Co-pay
- Benefit limit
- Authorization
- Claim generation
- Claim validation
- Claim scrubbing
- Submission
- Rejection
- Denial
- Resubmission
- Appeal
- Remittance
- Settlement
- Reconciliation
- Corporate batch
- Security
- Multi-hospital isolation
- Document security
- Concurrency
- Idempotency
- API

Mandatory negative tests:

1. Expired policy
2. Invalid policy
3. Missing authorization
4. Expired authorization
5. Authorization exceeded
6. Benefit limit exceeded
7. Duplicate claim
8. Duplicate submission
9. Missing document
10. Invalid mapping
11. Late submission
12. Unauthorized approval
13. Unauthorized submission
14. Hospital A → Hospital B access
15. Duplicate remittance allocation
16. Concurrent authorization consumption
17. Concurrent benefit consumption

==================================================
QUALITY RULES
==================================================

Use:

- Laravel conventions
- PSR-12
- Thin controllers
- Form Requests
- Policies/Gates
- Services/Actions
- Dependency injection
- Events/Listeners
- Jobs
- Transactions
- Proper database constraints
- Proper indexes
- Reusable components
- Clean separation of concerns

Do not put business logic in Blade.

Do not put business logic directly in controllers.

Do not duplicate existing services.

==================================================
IMPLEMENTATION PROCESS
==================================================

Before writing code:

1. Inspect repository.
2. Inspect module architecture.
3. Inspect Phases 0–13.
4. Inspect Billing.
5. Inspect Patient/MPI.
6. Inspect Encounter.
7. Inspect Admission.
8. Inspect File Management.
9. Inspect Workflow.
10. Inspect RBAC.
11. Inspect Audit.
12. Inspect API conventions.

Then create an architecture map.

Then implement incrementally.

After each major component:

- Run relevant tests.
- Fix failures.
- Check migrations.
- Check authorization.
- Check relationships.
- Check multi-hospital isolation.

Do not claim tests passed unless they were actually executed.

==================================================
FINAL ACCEPTANCE
==================================================

The implementation is complete only when:

- Insurance payer management works.
- Plans work.
- Contracts work.
- Corporate clients work.
- Patient policies work.
- Eligibility works.
- Coverage calculation works.
- Benefit limits work.
- Authorization works.
- Claim generation works.
- Claim validation works.
- Claim submission works.
- Rejection/denial works.
- Resubmission works.
- Appeals work.
- Remittance works.
- Settlement works.
- Reconciliation works.
- Corporate batch claims work.
- Aging works.
- Reporting works.
- Billing integration works.
- Clinical integration works.
- File management works.
- Notifications work.
- Audit works.
- RBAC works.
- Multi-hospital isolation works.
- Concurrency protection works.
- Idempotency works.
- API works.
- FHIR readiness exists.
- HL7 readiness exists.
- AI extension points exist.
- AI safety rules are enforced.
- Automated tests pass for implemented functionality.

Finally provide an implementation report containing:

1. Files created
2. Files modified
3. Migrations
4. Models
5. Services/Actions
6. Controllers
7. Policies
8. Permissions
9. Events
10. Jobs
11. Notifications
12. API endpoints
13. UI pages
14. Integrations
15. Tests executed
16. Tests passed
17. Tests failed
18. Known limitations
19. Security considerations
20. Recommended next steps

Never claim a test passed unless it was actually run.
```

# End of Phase 14 Specification