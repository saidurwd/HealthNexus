# Phase 19 — BI / Analytics / Data Warehouse
## Modular Architecture Specification & AI Implementation Prompt

**Document Version:** 1.0  
**Target Architecture:** Existing Laravel Modular Monolith  
**PHP:** 8.4+  
**Database:** MySQL/PostgreSQL for operational HMS + analytical warehouse  
**Cache/Queue:** Redis  
**UI:** Blade + AdminLTE  
**API:** REST `/api/v1`  
**Analytics Model:** Dimensional / Star Schema  
**Primary Purpose:** Enterprise BI, reporting, analytics, historical data warehouse and decision-support foundation

---

# 1. Purpose

Phase 19 establishes the Hospital Management System's centralized:

- Business Intelligence platform
- Analytics engine
- Data warehouse
- KPI framework
- Reporting platform
- Dashboard framework
- Historical analytics layer
- Data-quality analytics
- Executive reporting
- Operational reporting
- Self-service analytics foundation
- Data export framework
- Analytics API
- Phase 20 AI data foundation

The fundamental architecture is:

```text
Operational HMS
       │
       ├── Patient
       ├── Appointment
       ├── Clinical
       ├── Billing
       ├── LIS
       ├── Radiology
       ├── Pharmacy
       ├── IPD
       ├── Nursing
       ├── OT
       ├── ICU
       ├── Emergency
       ├── Blood Bank
       ├── Insurance
       ├── Inventory
       ├── HR
       └── Portal/CRM
       │
       ▼
Phase 18 Interoperability / Integration
       │
       ▼
Analytics Data Pipeline
       │
       ▼
Data Warehouse
       │
       ├── Dimensions
       ├── Facts
       ├── Aggregates
       └── Historical Snapshots
       │
       ▼
BI / Analytics
       │
       ├── Dashboards
       ├── KPIs
       ├── Reports
       ├── Trends
       ├── Drill-down
       ├── Exports
       └── Analytics API
       │
       ▼
Phase 20 AI / Clinical Intelligence
```

---

# 2. Critical Architectural Principle

The existing HMS modular architecture is **already implemented**.

The AI coding agent MUST:

1. Inspect the existing repository.
2. Inspect Phases 0–18.
3. Reuse the existing modular architecture.
4. Reuse existing domain models and services.
5. Reuse existing authentication, RBAC, organization/hospital hierarchy, audit, files, notifications, queues and APIs.
6. NOT create a second modular framework.
7. NOT duplicate operational modules.
8. NOT turn the analytics module into a second clinical system.

Phase 19 is primarily a **read/aggregation/analytics layer**.

---

# 3. Operational Database vs Data Warehouse

A fundamental separation must be maintained.

## Operational HMS

Used for:

- Transactions
- Clinical care
- Registration
- Billing
- Pharmacy
- Laboratory
- Radiology
- Admission
- Inventory
- HR
- etc.

## Data Warehouse

Used for:

- Historical analysis
- Aggregation
- Trends
- KPIs
- Cross-module reporting
- Executive dashboards
- Comparative analysis
- Forecasting datasets
- BI

Do not execute heavy analytical queries directly against critical clinical transaction tables when a warehouse/aggregate layer is appropriate.

---

# 4. Source-of-Truth Principle

The operational modules remain the source of truth.

```text
Patient → Phase 1
Appointment → Phase 2
Clinical → Phase 3
Billing → Phase 4
Laboratory → Phase 5
Radiology → Phase 6
Pharmacy → Phase 7
Admission → Phase 8
Nursing → Phase 9
Surgery → Phase 10
ICU → Phase 11
Emergency → Phase 12
Blood Bank → Phase 13
Insurance → Phase 14
Inventory → Phase 15
HR → Phase 16
Portal/CRM → Phase 17
Interoperability → Phase 18
Analytics → Phase 19
```

Phase 19 should never become the authoritative source for operational clinical records.

---

# 5. Scope

Phase 19 includes:

1. Analytics architecture
2. Data warehouse
3. ETL/ELT pipeline
4. Data ingestion
5. Data transformation
6. Data quality
7. Dimension management
8. Fact tables
9. Slowly Changing Dimensions
10. Historical snapshots
11. KPI framework
12. Dashboard framework
13. Operational reports
14. Executive dashboards
15. Clinical analytics
16. Financial analytics
17. Laboratory analytics
18. Radiology analytics
19. Pharmacy analytics
20. IPD analytics
21. OT analytics
22. ICU analytics
23. Emergency analytics
24. Blood Bank analytics
25. Insurance analytics
26. Inventory analytics
27. HR/workforce analytics
28. Patient portal/CRM analytics
29. Cross-hospital analytics
30. Drill-down
31. Filtering
32. Data exports
33. Scheduled reports
34. Analytics API
35. Data governance
36. Row-level security
37. Data masking
38. Audit
39. Analytics data catalog
40. Phase 20 AI data foundation

---

# 6. Non-Goals

Phase 19 must NOT become:

- A second EMR
- A second patient database
- A second billing system
- A second LIS
- A second pharmacy system
- A second inventory system
- A second HR system
- A transaction processing engine
- A clinical decision engine
- An autonomous AI system

Analytics should not directly modify operational clinical records.

---

# 7. Analytics Architecture

Recommended:

```text
                   OPERATIONAL HMS
                         │
                         ▼
                 Domain Events / CDC
                         │
                         ▼
                 Ingestion Layer
                         │
                         ▼
                Staging / Raw Layer
                         │
                         ▼
               Transformation Layer
                         │
                         ▼
                  Data Warehouse
                         │
              ┌──────────┼──────────┐
              │          │          │
              ▼          ▼          ▼
           Facts     Dimensions   Aggregates
              │          │          │
              └──────────┼──────────┘
                         ▼
                  Semantic Layer
                         │
                         ▼
                BI / Dashboard Layer
                         │
               ┌─────────┼─────────┐
               ▼         ▼         ▼
           Reports     KPIs      Analytics API
                         │
                         ▼
                    Phase 20 AI
```

---

# 8. Data Pipeline

Use:

```text
Extract
   ↓
Validate
   ↓
Stage
   ↓
Transform
   ↓
Dimension Load
   ↓
Fact Load
   ↓
Aggregate
   ↓
Quality Check
   ↓
Publish
```

Pipeline must be observable and restartable.

---

# 9. ETL / ELT Strategy

Support multiple ingestion methods.

### Event-driven

```text
Operational Event
       ↓
Queue
       ↓
Analytics Event Processor
```

### Scheduled

```text
Scheduler
   ↓
Incremental extraction
   ↓
Warehouse
```

### Batch

Used for:

- Historical migration
- Large source systems
- External data

### Full Refresh

Allowed only for controlled datasets where necessary.

Prefer incremental processing for production.

---

# 10. Data Layers

Recommended logical layers:

## Raw

Original extracted representation.

## Staging

Validated and normalized data.

## Warehouse

Conformed dimensional model.

## Semantic

Business-friendly analytical definitions.

## Presentation

Dashboards/reports.

---

# 11. Data Warehouse

Use dimensional modeling.

Example:

```text
                 dim_patient
                     │
                     │
dim_date ───── fact_encounter ───── dim_provider
                     │
                     │
               dim_department
```

A fact table should generally contain:

- Foreign keys to dimensions
- Numeric measures
- Degenerate dimensions where required
- Audit/load metadata

---

# 12. Core Dimensions

Suggested dimensions:

```text
dim_date
dim_time
dim_patient
dim_gender
dim_age_group
dim_organization
dim_hospital
dim_branch
dim_department
dim_specialty
dim_provider
dim_encounter_type
dim_diagnosis
dim_procedure
dim_service
dim_medication
dim_lab_test
dim_radiology_procedure
dim_pharmacy
dim_ward
dim_room
dim_bed
dim_theatre
dim_blood_component
dim_insurance_payer
dim_corporate
dim_supplier
dim_inventory_item
dim_employee
dim_job
dim_shift
dim_payment_method
dim_claim_status
dim_source_system
dim_campaign
dim_channel
```

Use only dimensions that are supported by actual source data.

---

# 13. Patient Dimension

`dim_patient` should support analytical identity while minimizing unnecessary sensitive information.

Possible fields:

```text
patient_key
source_patient_id
mrn_hash/reference
organization_key
hospital_key
gender_key
birth_year
age_band
city/region where appropriate
registration_date
patient_status
source_system
effective_from
effective_to
is_current
```

Avoid storing unnecessary clinical details in dimensional tables.

---

# 14. Slowly Changing Dimensions

Support SCD where historical reporting requires it.

Example:

```text
Patient Department Assignment
2026-01 → Cardiology
2026-06 → Medicine
```

Historical reports must retain the appropriate historical context.

Recommended:

- Type 1 for corrections where history is irrelevant.
- Type 2 where historical state matters.

Example:

```text
dim_provider
dim_department
dim_hospital
dim_insurance
dim_supplier
dim_employee
```

---

# 15. Date Dimension

Create a comprehensive date dimension.

Fields:

```text
date_key
calendar_date
day
day_name
week
month
month_name
quarter
year
financial_year
financial_month
is_weekend
is_holiday
holiday_name
```

Support configurable fiscal years.

---

# 16. Time Dimension

Where required:

```text
time_key
hour
minute
hour_bucket
shift
```

Useful for:

- Emergency arrival patterns
- OPD load
- Laboratory workload
- Pharmacy workload
- ICU activity
- Theatre utilization

---

# 17. Core Fact Tables

Suggested:

```text
fact_patient_registration
fact_appointments
fact_encounters
fact_vitals
fact_diagnoses
fact_procedures
fact_clinical_orders
fact_prescriptions
fact_lab_orders
fact_lab_results
fact_radiology_orders
fact_radiology_exams
fact_pharmacy_dispensing
fact_admissions
fact_bed_occupancy
fact_nursing_events
fact_surgeries
fact_ot_utilization
fact_icu_events
fact_emergency_visits
fact_blood_donations
fact_blood_inventory
fact_blood_transfusions
fact_claims
fact_invoices
fact_payments
fact_refunds
fact_inventory_transactions
fact_procurement
fact_employee_attendance
fact_leave
fact_payroll
fact_portal_activity
fact_crm_interactions
fact_notifications
fact_interoperability_messages
```

---

# 18. Fact Design Principle

Each fact must have a clearly defined **grain**.

Example:

```text
fact_appointments
Grain = one appointment
```

```text
fact_lab_results
Grain = one reported lab result for one analyte/test item
```

```text
fact_pharmacy_dispensing
Grain = one dispensed medication line
```

```text
fact_payments
Grain = one payment transaction
```

Never mix different grains in one fact table without explicit modeling.

---

# 19. Clinical Analytics

Support:

### Patient volume

- New patients
- Returning patients
- Unique patients
- Visits
- Encounter volume

### Diagnosis

- Diagnosis frequency
- Primary diagnosis trends
- Specialty distribution
- Age/gender distribution
- Hospital comparison

### Provider

- Encounter volume
- Follow-up volume
- Service volume
- Patient load

### Clinical workflow

- Waiting time
- Encounter duration
- Order turnaround
- Follow-up rates

Do not present statistical associations as clinical causation.

---

# 20. OPD Analytics

Dashboard:

```text
OPD Dashboard
├── Total Visits
├── New Patients
├── Returning Patients
├── Visits by Department
├── Visits by Provider
├── Visits by Hour
├── Waiting Time
├── Encounter Duration
├── No-Show Rate
└── Follow-Up Rate
```

---

# 21. Appointment Analytics

Metrics:

- Appointment volume
- Booking source
- Cancellation rate
- Reschedule rate
- No-show rate
- Provider utilization
- Slot utilization
- Lead time
- Peak demand
- Walk-in ratio

All KPI definitions must be centrally defined.

---

# 22. Laboratory Analytics

Metrics:

- Orders
- Tests
- Samples
- Collection time
- Processing time
- Result turnaround time
- Critical results
- Rejected samples
- Repeat tests
- Analyzer workload
- Test volume by department
- Test volume by provider

Example TAT:

```text
Result Time - Sample Collection Time
```

The exact business definition must be configurable.

---

# 23. Radiology Analytics

Metrics:

- Exams
- Modality volume
- Procedure volume
- Report TAT
- Preliminary/final reports
- Critical findings
- Cancellation
- No-show
- Modality utilization
- Radiologist workload
- PACS integration failures

---

# 24. Pharmacy Analytics

Metrics:

- Prescriptions
- Dispensed items
- Dispensing volume
- Partial dispensing
- Returns
- Expired stock
- Near-expiry stock
- Stock value
- Fast-moving medicines
- Slow-moving medicines
- Generic/brand usage
- Controlled medication activity
- Prescription-to-dispense conversion

Inventory balances remain owned by Phase 7/15; Phase 19 analyzes them.

---

# 25. IPD Analytics

Metrics:

- Admissions
- Discharges
- Average length of stay
- Bed occupancy
- Bed turnover
- Ward occupancy
- Transfer volume
- Readmission indicators
- Discharge delays
- Admission source

Example:

```text
ALOS =
Total Inpatient Days / Number of Discharges
```

Definitions should be documented and configurable.

---

# 26. OT Analytics

Metrics:

- Scheduled surgeries
- Completed surgeries
- Cancelled surgeries
- Emergency surgeries
- Theatre utilization
- Procedure duration
- Turnaround time
- Delay reasons
- Surgeon utilization
- Anesthesia workload
- Recovery duration
- First-case-on-time rate

---

# 27. ICU Analytics

Metrics:

- ICU admissions
- ICU occupancy
- Length of stay
- Transfers
- Discharges
- Mortality indicators where appropriately authorized
- Ventilator utilization where available
- Critical-care workload
- Escalation events

Sensitive clinical metrics require strict access controls.

---

# 28. Emergency Analytics

Metrics:

- ED arrivals
- Arrival mode
- Triage category
- Waiting time
- Time to clinician
- Length of stay
- Disposition
- Admission rate
- Transfer rate
- Discharge rate
- Ambulance activity
- Peak hours

---

# 29. Blood Bank Analytics

Metrics:

- Donors
- Donations
- Components collected
- Units released
- Units discarded
- Expired units
- Inventory
- Requests
- Crossmatches
- Issues
- Transfusions
- Reactions
- Wastage

Do not use analytics to replace Blood Bank operational compatibility logic.

---

# 30. Insurance Analytics

Metrics:

- Claims
- Claim value
- Submission volume
- Approval rate
- Denial rate
- Pending claims
- Aging
- Payer performance
- Corporate customer activity
- Co-pay/deductible analysis
- Reconciliation

---

# 31. Financial Analytics

Phase 4 remains the financial source of truth.

Analytics may provide:

- Revenue
- Gross revenue
- Net revenue
- Discounts
- Taxes
- Payments
- Refunds
- Outstanding dues
- Department revenue
- Service revenue
- Payer revenue
- Cashier performance
- Daily/monthly trends

Do not recreate financial transaction logic.

---

# 32. Revenue Recognition

If accounting rules require recognized revenue to differ from invoice/payment timing, model separate analytical measures.

Do not assume:

```text
Invoice = Revenue
```

unless the organization's accounting policy defines it that way.

---

# 33. Inventory Analytics

Metrics:

- Inventory value
- Stock turnover
- Stock aging
- Expiry
- Near expiry
- Stock-outs
- Slow-moving items
- Fast-moving items
- Purchase trends
- Supplier performance
- Consumption
- Reorder patterns

---

# 34. Procurement Analytics

Metrics:

- Purchase volume
- PO value
- Supplier spend
- Price variance
- Delivery time
- PO cycle time
- Rejection rate
- Contract utilization
- Supplier performance
- Emergency procurement
- Purchase category analysis

---

# 35. HR Analytics

Phase 16 remains the HR source of truth.

Analytics:

- Headcount
- Turnover
- Attendance
- Absenteeism
- Leave
- Overtime
- Payroll cost
- Department staffing
- Workforce utilization
- Recruitment funnel
- Training
- Performance indicators

Sensitive employee analytics must use strict authorization.

---

# 36. Patient Portal / CRM Analytics

Metrics:

- Portal registrations
- Active users
- Login frequency
- Appointment bookings
- Digital check-in
- Online payments
- Document access
- Messages
- Service requests
- Feedback
- Complaints
- Campaign delivery
- Campaign engagement
- Patient journey activity

---

# 37. Interoperability Analytics

From Phase 18:

- Messages sent
- Messages received
- FHIR transactions
- HL7 transactions
- DICOM transactions
- Integration failures
- Retry count
- Dead letters
- Response latency
- External-system availability

---

# 38. Enterprise Executive Dashboard

Create a configurable executive dashboard.

Example sections:

```text
Hospital Overview
────────────────────────────
Patients
Appointments
Encounters
Admissions
Occupancy
Emergency
Surgery
Laboratory
Radiology
Pharmacy
Revenue
Claims
Inventory
Workforce
Patient Satisfaction
Interoperability
```

Executives should see aggregated information appropriate to their authorization.

---

# 39. KPI Framework

Do not hardcode KPIs directly inside dashboard controllers.

Create a KPI registry.

Example:

```text
KPI:
OPD_VISIT_COUNT

Definition:
Number of completed OPD encounters within selected period.

Source:
fact_encounters

Filters:
hospital
branch
department
provider
date

Aggregation:
COUNT

Format:
integer
```

---

# 40. KPI Metadata

Suggested:

```text
analytics_kpis
```

Fields:

```text
id
organization_id
code
name
description
category
metric_type
formula
data_source
unit
format
frequency
active
visibility_scope
created_by
updated_by
```

Avoid storing arbitrary executable SQL directly in user-editable KPI fields.

Use a controlled metric/query engine.

---

# 41. Semantic Layer

Create business-friendly measures.

Example:

```text
Total OPD Visits
Unique Patients
Average Waiting Time
Bed Occupancy Rate
Average Length of Stay
Revenue
Outstanding Dues
Lab TAT
Radiology TAT
OT Utilization
Emergency Admission Rate
```

The same KPI must produce the same definition across dashboards and reports.

---

# 42. Dashboard Builder

Provide configurable dashboards.

Features:

- Widgets
- KPI cards
- Tables
- Charts
- Trend lines
- Bar charts
- Pie/donut where appropriate
- Heatmaps
- Filters
- Drill-down
- Date ranges
- Hospital filters
- Department filters
- Provider filters
- Export
- Sharing
- Scheduling

Do not allow arbitrary SQL execution from ordinary dashboard users.

---

# 43. Dashboard Widget Model

Possible:

```text
analytics_dashboards
analytics_dashboard_widgets
analytics_widget_queries
analytics_dashboard_filters
analytics_dashboard_permissions
analytics_dashboard_versions
```

Widgets should reference approved metrics/query definitions.

---

# 44. Drill-Down

Example:

```text
Revenue
 ↓
Hospital
 ↓
Department
 ↓
Service
 ↓
Invoice
```

or:

```text
Lab Tests
 ↓
Department
 ↓
Test
 ↓
Date
 ↓
Order
```

Drill-down must obey authorization at every level.

---

# 45. Row-Level Security

A user authorized for Hospital A must not see Hospital B data.

Example:

```text
Organization
    ↓
Hospital
    ↓
Branch
    ↓
Department
```

Analytics queries must apply the same access-scope model established in Phase 0.

Do not rely only on UI filters.

---

# 46. Sensitive Analytics

Some datasets require elevated permissions:

- Diagnoses
- Clinical outcomes
- Mortality
- Mental/behavioral health data where applicable
- Employee compensation
- Patient financial information
- Insurance details
- Controlled medication analytics

Support dataset-level and metric-level permissions where required.

---

# 47. De-identification

Support analytics datasets that remove or tokenize direct identifiers.

Possible techniques:

- Hashing
- Tokenization
- Aggregation
- Date shifting where appropriate
- Suppression of small groups
- Removal of direct identifiers

De-identification must be designed according to the intended use and applicable privacy requirements.

---

# 48. Research Dataset Foundation

Provide controlled dataset generation for authorized users.

Workflow:

```text
Dataset Request
      ↓
Purpose
      ↓
Scope
      ↓
Approval
      ↓
De-identification
      ↓
Dataset Generation
      ↓
Access
      ↓
Audit
```

Do not allow unrestricted clinical database exports.

---

# 49. Data Quality Framework

Track:

- Completeness
- Accuracy indicators
- Validity
- Consistency
- Uniqueness
- Timeliness

Example:

```text
Patient records missing date of birth
Lab results missing units
Diagnosis without code
Billing records without department
Appointments without provider
```

---

# 50. Data Quality Rules

Suggested:

```text
analytics_data_quality_rules
analytics_data_quality_results
analytics_data_quality_issues
```

Each rule should have:

```text
code
dataset
description
severity
threshold
active
```

---

# 51. Data Quality Dashboard

Display:

```text
Data Quality
├── Overall Score
├── Missing Data
├── Invalid Data
├── Duplicate Data
├── Mapping Gaps
├── Timeliness
└── Critical Issues
```

The score should be transparent and explainable.

---

# 52. Data Lineage

Track:

```text
Source Table/Event
       ↓
Staging
       ↓
Transformation
       ↓
Warehouse Fact
       ↓
KPI
       ↓
Dashboard
```

Users with appropriate permissions should be able to understand where a KPI came from.

---

# 53. Data Catalog

Create metadata for analytical datasets.

Example:

```text
Dataset:
OPD Encounters

Owner:
Clinical Operations

Source:
Phase 3

Grain:
One completed encounter

Refresh:
Every 15 minutes

Sensitive:
Yes

Retention:
Configured policy
```

---

# 54. Scheduled Reports

Support:

- Daily
- Weekly
- Monthly
- Quarterly
- Custom

Delivery:

- In-app
- Email
- Secure file
- Dashboard

Reuse the existing notification infrastructure.

---

# 55. Report Builder

Authorized users may create reports using:

- Approved datasets
- Approved dimensions
- Approved metrics
- Filters
- Grouping
- Sorting
- Aggregation

Do not expose arbitrary SQL to ordinary users.

---

# 56. Export

Support:

- CSV
- XLSX
- PDF where appropriate
- JSON for APIs

Exports must:

- Respect authorization
- Be audited
- Apply data masking
- Have configurable size limits
- Use asynchronous jobs for large exports

---

# 57. Analytics API

Base:

```text
/api/v1/analytics
```

Possible:

```text
GET /dashboards
GET /dashboards/{id}

GET /kpis
GET /kpis/{code}

GET /reports
POST /reports/run

GET /datasets
GET /data-quality

POST /exports
GET /exports/{id}

GET /metrics/{code}
```

All endpoints must apply server-side authorization.

---

# 58. Warehouse Refresh

Support configurable schedules:

```text
Near real-time
15 minutes
30 minutes
Hourly
Daily
Weekly
```

Not every dataset requires real-time processing.

Critical operational dashboards may use near-real-time aggregates.

Strategic analytics can use daily refreshes.

---

# 59. Incremental Loading

Prefer watermark-based extraction.

Example:

```text
last_updated_at > last_successful_watermark
```

Where reliable.

For high-volume data, use:

- Event IDs
- Change tracking
- CDC
- Queue offsets

where supported.

---

# 60. Warehouse Load Tracking

Suggested:

```text
analytics_pipeline_runs
analytics_pipeline_steps
analytics_pipeline_errors
analytics_watermarks
analytics_dataset_refreshes
```

Track:

- Start time
- End time
- Records read
- Records inserted
- Records updated
- Records rejected
- Errors
- Watermark
- Status

---

# 61. Pipeline Failure Handling

If warehouse refresh fails:

```text
Operational HMS
       │
       ▼
Continues operating normally
       │
       ▼
Analytics Pipeline
       │
       ▼
Failure
       │
       ▼
Alert
       │
       ▼
Retry
```

Analytics failure must not stop patient registration, clinical care, billing, pharmacy or other critical operations.

---

# 62. Historical Snapshots

Support periodic snapshots for metrics such as:

- Bed occupancy
- Inventory
- Workforce
- Financial balances
- Patient population
- Outstanding claims

Example:

```text
fact_daily_bed_occupancy
fact_daily_inventory_snapshot
fact_daily_ar_balance
fact_daily_workforce_snapshot
```

---

# 63. Forecasting Foundation

Phase 19 should prepare datasets for Phase 20.

Potential datasets:

- Patient volume
- Appointment demand
- Emergency demand
- Lab demand
- Pharmacy consumption
- Inventory demand
- Bed occupancy
- OT demand
- Staffing demand
- Revenue trends

Forecasting models themselves should primarily belong to Phase 20.

---

# 64. AI Integration Boundary

Phase 19 provides:

```text
Clean Data
+
Historical Data
+
Features
+
Metrics
+
Data Lineage
+
Governed Access
```

Phase 20 provides:

```text
Prediction
+
Clinical Intelligence
+
AI Models
+
Recommendations
```

---

# 65. AI Features in Phase 19

AI may assist with:

### Report Generation

Generate draft narrative summaries from approved metrics.

### Dashboard Explanation

Explain:

- What changed
- Which metrics increased/decreased
- Which dimensions contributed

### Anomaly Detection

Detect unusual:

- Patient volume
- Revenue
- Lab volume
- Pharmacy usage
- Inventory consumption
- Claim behavior
- Integration traffic

### Natural Language Analytics

Allow questions such as:

> "How many OPD visits did Cardiology have last month?"

The system must translate the question into approved metrics and filters.

It must NOT generate unrestricted SQL against the production database.

---

# 66. Natural Language Analytics Safety

Flow:

```text
User Question
      ↓
Intent Detection
      ↓
Approved Dataset
      ↓
Approved Metric
      ↓
Authorization
      ↓
Query
      ↓
Result
      ↓
Explanation
```

The AI must not bypass permissions.

Example:

```text
User asks for Hospital B revenue
```

If unauthorized:

```text
Access denied
```

not:

```text
AI retrieves data anyway
```

---

# 67. AI Hallucination Prevention

AI-generated analytical explanations must reference actual query results.

Never allow:

```text
AI assumption → displayed as fact
```

Use:

```text
Database result → AI explanation
```

The UI should distinguish:

- Actual metric
- AI-generated interpretation
- Forecast
- Recommendation

---

# 68. AI Analytics Audit

Record:

```text
question
user
dataset
metrics
filters
query/reference
result timestamp
AI model/version
generated explanation
```

Do not unnecessarily store sensitive full datasets in AI logs.

---

# 69. Suggested Database Tables

## Analytics Platform

```text
analytics_data_sources
analytics_datasets
analytics_dataset_fields
analytics_dataset_permissions
analytics_data_catalog
analytics_data_lineage
analytics_data_quality_rules
analytics_data_quality_results
analytics_data_quality_issues
```

## Warehouse

```text
dim_date
dim_time
dim_patient
dim_organization
dim_hospital
dim_branch
dim_department
dim_specialty
dim_provider
dim_diagnosis
dim_procedure
dim_service
dim_medication
dim_lab_test
dim_radiology_procedure
dim_pharmacy
dim_ward
dim_bed
dim_theatre
dim_insurance_payer
dim_supplier
dim_inventory_item
dim_employee
dim_payment_method
dim_source_system
```

Fact tables:

```text
fact_patient_registration
fact_appointments
fact_encounters
fact_vitals
fact_diagnoses
fact_procedures
fact_clinical_orders
fact_prescriptions
fact_lab_orders
fact_lab_results
fact_radiology_orders
fact_radiology_exams
fact_pharmacy_dispensing
fact_admissions
fact_bed_occupancy
fact_nursing_events
fact_surgeries
fact_ot_utilization
fact_icu_events
fact_emergency_visits
fact_blood_donations
fact_blood_inventory
fact_blood_transfusions
fact_claims
fact_invoices
fact_payments
fact_refunds
fact_inventory_transactions
fact_procurement
fact_employee_attendance
fact_leave
fact_payroll
fact_portal_activity
fact_crm_interactions
fact_notifications
fact_interoperability_messages
```

## BI

```text
analytics_kpis
analytics_kpi_versions
analytics_dashboards
analytics_dashboard_widgets
analytics_widget_queries
analytics_dashboard_filters
analytics_dashboard_permissions
analytics_dashboard_versions
analytics_reports
analytics_report_definitions
analytics_report_runs
analytics_report_schedules
analytics_exports
```

## Pipeline

```text
analytics_pipeline_runs
analytics_pipeline_steps
analytics_pipeline_errors
analytics_watermarks
analytics_dataset_refreshes
analytics_aggregate_refreshes
```

Names should be adapted to existing project conventions.

---

# 70. Multi-Hospital Analytics

Support:

```text
Organization
   ├── Hospital A
   ├── Hospital B
   └── Hospital C
```

Dashboards may be:

- Organization-wide
- Hospital-specific
- Branch-specific
- Department-specific

But access must be enforced server-side.

---

# 71. Cross-Hospital Benchmarking

Where authorized, compare:

- Patient volume
- Appointment utilization
- Bed occupancy
- Revenue
- Lab volume
- Radiology volume
- Pharmacy activity
- OT utilization
- Emergency volume

The system should show definitions and population/time period for each comparison.

Do not hide differences in data completeness or reporting periods.

---

# 72. Analytics Permissions

Examples:

```text
analytics.dashboard.view
analytics.dashboard.create
analytics.dashboard.update
analytics.dashboard.delete

analytics.kpi.view
analytics.kpi.create
analytics.kpi.update
analytics.kpi.publish

analytics.report.view
analytics.report.create
analytics.report.run
analytics.report.export
analytics.report.schedule

analytics.dataset.view
analytics.dataset.export

analytics.data_quality.view
analytics.data_quality.manage

analytics.pipeline.view
analytics.pipeline.run
analytics.pipeline.retry

analytics.lineage.view
analytics.catalog.manage

analytics.research_dataset.create
analytics.research_dataset.approve

analytics.ai.query
analytics.ai.explanation
```

---

# 73. Recommended Roles

### BI Administrator

Full analytics administration.

### Data Engineer

Pipeline and warehouse administration.

### BI Developer

Dashboards, metrics and reports.

### Data Analyst

Analytics and approved datasets.

### Hospital Management

Management dashboards.

### Department Manager

Department-scoped analytics.

### Clinical Analyst

Authorized clinical analytics.

### Finance Analyst

Financial analytics.

### HR Analyst

Workforce analytics.

### Auditor

Read-only analytical/audit access.

### Researcher

Approved de-identified datasets only.

---

# 74. Audit

Audit:

- Dashboard creation
- Dashboard changes
- KPI changes
- Dataset access
- Data exports
- Scheduled reports
- Research dataset generation
- Permission changes
- Data masking changes
- Pipeline configuration
- AI analytics queries
- Sensitive report access

Reuse Phase 0 audit infrastructure.

---

# 75. Performance

Analytics queries must not degrade clinical transactions.

Use:

- Warehouse
- Read replicas where appropriate
- Aggregate tables
- Materialized views where supported
- Caching
- Precomputed KPIs
- Pagination
- Async exports

Avoid expensive queries against operational tables during peak clinical usage.

---

# 76. Caching

Cache:

- Dashboard KPI values
- Static dimensions
- Frequently used aggregates
- Report metadata

Do not cache highly sensitive data beyond the required scope.

Cache keys must include authorization scope where required.

---

# 77. Data Security

Implement:

- Encryption in transit
- Encryption at rest where supported
- Row-level access
- Dataset permissions
- Field masking
- Export controls
- Audit
- Secure temporary files
- Automatic export expiry

---

# 78. Disaster Recovery

Analytics should support:

- Warehouse backup
- Pipeline configuration backup
- Dashboard backup
- KPI definitions backup
- Mapping/metadata backup
- Recovery procedures
- Rebuild from source where practical

Operational HMS recovery remains independent of analytics recovery.

---

# 79. Monitoring

Monitor:

```text
Pipeline Health
Warehouse Size
Refresh Duration
Failed Loads
Data Quality
Query Performance
Dashboard Performance
Export Queue
AI Analytics Usage
```

Alert on:

- Refresh failure
- Warehouse storage threshold
- Pipeline backlog
- Query timeout
- Data-quality critical issue
- Unauthorized access attempts

---

# 80. Testing

## Unit Tests

Test:

- Transformations
- KPI calculations
- Dimension logic
- SCD logic
- Aggregations
- Data-quality rules

## Feature Tests

Test:

- Dashboards
- Reports
- Filters
- Exports
- Permissions

## Data Tests

Validate:

- Record counts
- Referential integrity
- Duplicate detection
- Null rules
- Data types
- Aggregation accuracy

## Security Tests

Mandatory:

```text
Hospital A user
    ↓
Hospital B analytics
    ↓
ACCESS DENIED
```

Also:

```text
Finance user
    ↓
Clinical restricted dataset
    ↓
ACCESS DENIED
```

## Performance Tests

Test:

- Large dashboards
- Large datasets
- Concurrent users
- Large exports
- Warehouse refresh
- Pipeline backlog

---

# 81. Definition of Done

Phase 19 is complete only when:

## Architecture

- Existing modular architecture reused.
- Analytics separated from operational transaction processing.
- Warehouse architecture implemented.

## Pipeline

- Incremental ingestion implemented.
- Staging implemented.
- Transformation implemented.
- Warehouse loading implemented.
- Refresh tracking implemented.
- Retry/failure handling implemented.

## Warehouse

- Core dimensions implemented.
- Core facts implemented.
- Correct fact grain documented.
- SCD strategy implemented where required.
- Historical snapshots supported.

## BI

- KPI framework implemented.
- Semantic layer implemented.
- Dashboard engine implemented.
- Drill-down implemented.
- Report builder implemented.
- Scheduled reports implemented.
- Export implemented.

## Analytics

- Patient analytics
- Appointment analytics
- Clinical analytics
- Billing analytics
- Laboratory analytics
- Radiology analytics
- Pharmacy analytics
- IPD analytics
- OT analytics
- ICU analytics
- Emergency analytics
- Blood Bank analytics
- Insurance analytics
- Inventory analytics
- HR analytics
- Portal/CRM analytics
- Interoperability analytics

## Security

- Multi-hospital isolation tested.
- Dataset-level authorization tested.
- Sensitive analytics protected.
- Export auditing implemented.
- De-identification foundation implemented.

## Data Governance

- Data catalog implemented.
- Data lineage implemented.
- Data-quality framework implemented.
- KPI definitions documented.

## AI

- Natural-language analytics foundation implemented.
- AI explanations use actual query results.
- AI cannot bypass permissions.
- AI cannot directly alter operational data.
- AI usage is audited.

## Testing

- Unit tests.
- Feature tests.
- Data-quality tests.
- Security tests.
- Performance tests.
- Pipeline failure tests.
- Multi-hospital tests.

## Documentation

- Architecture
- Warehouse model
- Fact grain
- Dimension definitions
- KPI catalog
- Data lineage
- ETL/ELT
- Dashboard configuration
- Security
- Data governance
- Disaster recovery
- Troubleshooting

---

# 82. Implementation Order

Implement in this sequence:

1. Inspect repository.
2. Inspect Phases 0–18.
3. Map existing domain sources.
4. Identify existing events.
5. Define analytics ownership.
6. Design analytics architecture.
7. Create data-source registry.
8. Create data catalog.
9. Create staging layer.
10. Create warehouse connection.
11. Create date/time dimensions.
12. Create organization/hospital dimensions.
13. Create patient/provider/department dimensions.
14. Create core clinical facts.
15. Create appointment facts.
16. Create billing facts.
17. Create laboratory facts.
18. Create radiology facts.
19. Create pharmacy facts.
20. Create IPD/bed facts.
21. Create OT facts.
22. Create ICU facts.
23. Create Emergency facts.
24. Create Blood Bank facts.
25. Create Insurance facts.
26. Create Inventory/Procurement facts.
27. Create HR facts.
28. Create Portal/CRM facts.
29. Create Interoperability facts.
30. Implement incremental pipelines.
31. Implement data-quality engine.
32. Implement lineage.
33. Implement KPI engine.
34. Implement semantic layer.
35. Implement dashboard framework.
36. Implement report framework.
37. Implement exports.
38. Implement scheduled reports.
39. Implement row-level security.
40. Implement de-identification.
41. Implement natural-language analytics.
42. Implement AI explanations.
43. Implement monitoring.
44. Implement performance optimization.
45. Implement comprehensive tests.
46. Complete documentation.

---

# 83. Final AI Coding Prompt

You are a senior healthcare BI architect, data engineer, analytics engineer and Laravel/PHP enterprise software engineer.

Implement **Phase 19 — BI / Analytics / Data Warehouse** in the existing HMS.

## Critical Instructions

The HMS modular architecture already exists.

Before writing code:

1. Inspect the repository.
2. Inspect Phases 0–18.
3. Identify existing models, services, events, queues, policies, APIs and database structures.
4. Reuse them.
5. Do NOT create a second modular architecture.
6. Do NOT duplicate operational domain ownership.
7. Do NOT turn analytics into a transaction system.
8. Do NOT modify clinical records through analytics.

---

## Objective

Build a production-grade enterprise analytics platform providing:

- Data warehouse
- ETL/ELT
- Incremental pipelines
- Dimensional modeling
- Historical analytics
- KPI framework
- Semantic layer
- Dashboards
- Reports
- Drill-down
- Exports
- Scheduled reports
- Data quality
- Data lineage
- Data catalog
- De-identified datasets
- Analytics API
- Natural-language analytics foundation
- AI-generated analytical explanations
- Phase 20 AI data foundation

---

## Source Modules

Integrate data from:

```text
Phase 1  Patient
Phase 2  Appointment
Phase 3  Clinical
Phase 4  Billing
Phase 5  LIS
Phase 6  Radiology
Phase 7  Pharmacy
Phase 8  IPD
Phase 9  Nursing
Phase 10 OT
Phase 11 ICU
Phase 12 Emergency
Phase 13 Blood Bank
Phase 14 Insurance
Phase 15 Inventory
Phase 16 HR
Phase 17 Portal/CRM
Phase 18 Interoperability
```

Do not create duplicate operational records.

---

## Data Warehouse

Implement dimensional modeling.

Use:

- Fact tables
- Dimension tables
- SCD Type 1
- SCD Type 2
- Historical snapshots
- Aggregate tables

Every fact must have a documented grain.

---

## Pipeline

Implement:

```text
Source
 ↓
Extract
 ↓
Validate
 ↓
Stage
 ↓
Transform
 ↓
Dimension Load
 ↓
Fact Load
 ↓
Aggregate
 ↓
Data Quality
 ↓
Publish
```

Support:

- incremental loads
- scheduled loads
- event-driven loads
- batch loads
- retries
- watermarks
- pipeline monitoring

Analytics failure must never stop the clinical HMS.

---

## KPI Engine

Do not hardcode KPI formulas inside controllers.

Create reusable KPI definitions.

Each KPI must have:

- code
- name
- description
- owner
- source dataset
- grain
- formula
- filters
- aggregation
- unit
- security scope
- version
- effective dates

Maintain KPI version history.

---

## Dashboards

Build:

- Executive dashboard
- Hospital dashboard
- OPD dashboard
- Appointment dashboard
- Clinical dashboard
- Laboratory dashboard
- Radiology dashboard
- Pharmacy dashboard
- IPD dashboard
- OT dashboard
- ICU dashboard
- Emergency dashboard
- Blood Bank dashboard
- Insurance dashboard
- Finance dashboard
- Inventory dashboard
- HR dashboard
- Patient Portal/CRM dashboard
- Interoperability dashboard

All dashboards must use the centralized KPI/semantic layer.

---

## Security

Reuse existing organization/hospital/branch/department access controls.

Every analytics query must enforce server-side authorization.

Test:

```text
Hospital A → Hospital B analytics = DENIED
```

and:

```text
Finance role → Restricted clinical analytics = DENIED
```

Never depend only on frontend filters.

---

## Data Governance

Implement:

- data catalog
- data owner
- dataset owner
- sensitivity classification
- data lineage
- data-quality rules
- data-quality issue management
- refresh status
- retention metadata

---

## Data Quality

Detect:

- missing values
- invalid values
- duplicates
- inconsistent references
- stale data
- missing mappings
- impossible dates
- invalid relationships

Provide a data-quality dashboard.

---

## Exports

Support:

- CSV
- XLSX
- PDF
- JSON

Large exports must use queues.

All exports must:

- enforce authorization
- be audited
- apply masking
- have size limits
- expire temporary files

---

## Research Data

Implement controlled de-identified dataset generation.

Workflow:

```text
Request
 ↓
Purpose
 ↓
Scope
 ↓
Approval
 ↓
De-identification
 ↓
Generation
 ↓
Access
 ↓
Audit
```

Never permit unrestricted raw clinical database export.

---

## AI Analytics

Implement AI capabilities for:

- natural-language analytics
- KPI explanations
- anomaly detection
- report summaries
- data-quality explanations

Architecture:

```text
User Question
 ↓
Intent
 ↓
Approved Dataset
 ↓
Approved KPI
 ↓
Authorization
 ↓
Query
 ↓
Actual Result
 ↓
AI Explanation
```

AI must NOT:

- generate unrestricted production SQL
- bypass authorization
- access restricted datasets
- invent numbers
- modify operational records
- present forecasts as actual results

Clearly distinguish:

```text
Actual
Forecast
AI Interpretation
Recommendation
```

---

## Performance

Use:

- warehouse
- read replicas where appropriate
- caching
- aggregate tables
- materialized views where supported
- asynchronous exports
- pagination

Do not run heavy analytics directly against production clinical tables unnecessarily.

---

## Testing

Implement and execute:

### Unit
- transformations
- KPI calculations
- SCD logic
- aggregation
- data-quality rules

### Feature
- dashboards
- reports
- exports
- permissions
- filters

### Data
- source-to-warehouse reconciliation
- fact grain
- duplicate detection
- referential integrity

### Security
- tenant isolation
- dataset permissions
- sensitive data access
- export permissions

### Performance
- dashboard load
- concurrent users
- large exports
- pipeline processing

### Reliability
- failed pipeline
- retry
- duplicate event
- partial load
- warehouse outage

Never claim tests passed unless they were actually executed.

---

## Documentation

Create/update:

```text
docs/analytics/
```

Document:

- Architecture
- Warehouse schema
- Fact grain
- Dimensions
- KPI catalog
- Semantic model
- ETL/ELT
- Data lineage
- Data-quality rules
- Dashboard configuration
- Security
- De-identification
- Research datasets
- AI analytics
- Deployment
- Monitoring
- Disaster recovery
- Troubleshooting

---

## Final Report

After implementation, provide:

1. Files created
2. Files modified
3. Database migrations
4. Warehouse tables
5. Dimensions
6. Facts
7. Pipelines
8. KPI definitions
9. Dashboards
10. Reports
11. APIs
12. Permissions
13. Security controls
14. Data-quality rules
15. AI features
16. Tests actually executed
17. Actual test results
18. Known limitations
19. Remaining TODOs
20. Deployment instructions
21. Documentation created

Do not claim functionality, tests, integrations, or performance results that were not actually implemented and verified.