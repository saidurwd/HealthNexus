# Phase 20 — AI / Clinical Intelligence
## Modular Architecture Specification & AI Implementation Prompt

**Document Version:** 1.0  
**Target Architecture:** Existing Laravel Modular Monolith  
**PHP:** 8.4+  
**Database:** Existing HMS operational database + Phase 19 analytical platform  
**Cache/Queue:** Redis  
**UI:** Blade + AdminLTE  
**API:** REST `/api/v1`  
**AI Architecture:** Provider-agnostic, model-agnostic, human-in-the-loop  
**Primary Purpose:** Clinical decision support, predictive analytics, intelligent automation, clinical summarization, patient assistance, operational intelligence and AI governance

---

# 1. Purpose

Phase 20 establishes the Hospital Management System's centralized **AI and Clinical Intelligence Platform**.

It provides controlled AI capabilities across:

- Clinical decision support
- Clinical summarization
- Patient timeline summarization
- Diagnosis support
- Medication safety assistance
- Laboratory intelligence
- Radiology intelligence integration
- Pharmacy intelligence
- Admission/bed forecasting
- ICU intelligence
- Emergency intelligence
- OT intelligence
- Blood Bank intelligence
- Insurance intelligence
- Inventory forecasting
- Workforce analytics
- Patient portal AI
- Administrative automation
- Natural-language analytics
- Documentation assistance
- Risk stratification
- Predictive analytics
- Anomaly detection
- Population health analytics
- AI governance
- Model management
- AI audit
- Human review
- Explainability
- AI safety

The central principle is:

> **AI assists humans; AI does not silently replace clinical judgment or modify authoritative records.**

---

# 2. Critical Architectural Principle

The existing modular architecture is **already implemented**.

The AI coding agent MUST:

1. Inspect the existing repository.
2. Inspect Phases 0–19.
3. Reuse existing modules.
4. Reuse existing models/services/events/jobs/policies.
5. Reuse existing authentication/RBAC.
6. Reuse Phase 18 interoperability.
7. Reuse Phase 19 analytics/data warehouse.
8. NOT create a second modular framework.
9. NOT create duplicate clinical systems.
10. NOT move ownership of clinical records into the AI module.

Phase 20 is an **intelligence layer**, not a replacement for the HMS.

---

# 3. AI Architecture

Recommended:

```text id="3x8kz9"
                    HMS Operational Modules
                             │
                             ▼
                     Phase 19 Analytics
                             │
                    Governed Data Layer
                             │
                             ▼
                  ┌──────────────────────┐
                  │   AI Intelligence    │
                  │                      │
                  │ Context Engine       │
                  │ Feature Engine       │
                  │ Model Registry       │
                  │ Prompt Registry      │
                  │ AI Gateway           │
                  │ Policy Engine        │
                  │ Safety Engine        │
                  │ Evaluation Engine    │
                  │ Audit                │
                  └──────────┬───────────┘
                             │
          ┌──────────────────┼──────────────────┐
          ▼                  ▼                  ▼
       LLM Models        ML Models        Rules/Engines
          │                  │                  │
          └──────────────────┼──────────────────┘
                             ▼
                    Human Review Layer
                             │
                             ▼
                   HMS User / Clinician
```

---

# 4. AI Gateway

All AI requests should pass through a centralized AI Gateway.

Example:

```text id="h4apzu"
Application
    ↓
AI Gateway
    ↓
Policy Check
    ↓
Context Authorization
    ↓
Prompt/Model Selection
    ↓
AI Provider
    ↓
Safety Validation
    ↓
Human Review
    ↓
Response
```

Do not allow individual modules to call AI providers directly.

---

# 5. AI Provider Abstraction

Use a provider-independent interface.

Example:

```php id="6fby5q"
interface AiProviderInterface
{
    public function generate(AiRequest $request): AiResponse;

    public function stream(AiRequest $request): AiStream;

    public function embeddings(AiEmbeddingRequest $request): AiEmbeddingResponse;
}
```

Potential adapters:

```text id="1b49m4"
OpenAIAdapter
AzureOpenAIAdapter
AnthropicAdapter
GoogleAdapter
LocalModelAdapter
OllamaAdapter
CustomModelAdapter
```

The exact providers must be configurable.

Do not hard-code one vendor into clinical modules.

---

# 6. Model Types

Support:

### Large Language Models

For:

- Summarization
- Documentation assistance
- Conversational interfaces
- Natural-language analytics

### Classical ML

For:

- Forecasting
- Risk scoring
- Classification
- Anomaly detection

### Embedding Models

For:

- Semantic search
- Document retrieval
- Clinical knowledge retrieval

### Vision Models

Potentially for:

- Medical image assistance
- Document extraction
- Imaging support

However, medical-image interpretation must remain a separately governed capability.

---

# 7. AI Use-Case Categories

Divide AI capabilities into:

```text id="gfhjpu"
1. Administrative AI
2. Operational AI
3. Clinical Documentation AI
4. Clinical Decision Support
5. Predictive Analytics
6. Population Health AI
7. Patient-facing AI
8. Knowledge Retrieval
9. Data Quality AI
10. Security/Anomaly AI
```

Each use case must have its own risk classification.

---

# 8. AI Risk Classification

Every AI feature should be classified.

### Level 0 — Informational

Example:

> Explain this hospital policy.

### Level 1 — Administrative Assistance

Example:

> Summarize today's appointments.

### Level 2 — Clinical Documentation Assistance

Example:

> Draft an encounter summary from documented information.

### Level 3 — Clinical Decision Support

Example:

> Identify potentially relevant documented clinical considerations.

### Level 4 — High-Risk Predictive Support

Example:

> Predict risk of clinical deterioration.

### Level 5 — Autonomous Clinical Action

This level should **not be permitted by default**.

No AI should autonomously:

- Diagnose
- Prescribe
- Change medication
- Approve surgery
- Order blood
- Discharge patients
- Change clinical records
- Override clinicians

---

# 9. Human-in-the-Loop

Clinical AI workflow:

```text id="f3y5tc"
Clinical Data
     ↓
AI Analysis
     ↓
AI Result
     ↓
Clinician Review
     ↓
Accept / Modify / Reject
     ↓
Authorized Action
```

AI suggestions must remain suggestions until an authorized human acts.

---

# 10. AI Context Engine

The Context Engine retrieves only authorized information.

Example:

```text id="e9d4k7"
Current Encounter
      +
Relevant History
      +
Allergies
      +
Medications
      +
Lab Results
      +
Radiology
      +
Previous Encounters
      ↓
AI Context
```

Do not automatically send the complete Patient 360 record to every AI request.

---

# 11. Minimum Necessary Context

AI requests should contain only information required for the use case.

Example:

For:

> "Summarize this encounter"

provide:

- Encounter data
- Relevant diagnosis
- Orders
- Results
- Notes

Do not unnecessarily include:

- Entire billing history
- Unrelated family information
- Unrelated financial data
- Unrelated CRM interactions

---

# 12. Context Authorization

Before constructing AI context:

```text id="7ggr4w"
User
 ↓
Role
 ↓
Organization
 ↓
Hospital
 ↓
Patient
 ↓
Encounter
 ↓
Resource
 ↓
AI Use Case
```

All permissions must be checked server-side.

---

# 13. Clinical AI Safety Boundary

AI must NEVER silently:

- Create a diagnosis
- Finalize a diagnosis
- Change a diagnosis
- Prescribe medication
- Change dosage
- Discontinue medication
- Override allergy alerts
- Order laboratory tests
- Order radiology
- Order blood
- Approve surgery
- Change clinical notes
- Finalize a laboratory report
- Finalize a radiology report
- Approve a discharge
- Modify patient demographics
- Change billing
- Change insurance claims
- Change blood compatibility decisions

AI may generate suggestions or drafts subject to explicit authorized review.

---

# 14. Clinical Summarization

Provide:

### Patient Timeline Summary

```text id="m80hpf"
Patient
 ↓
Past encounters
 ↓
Diagnoses
 ↓
Medications
 ↓
Labs
 ↓
Radiology
 ↓
Admissions
 ↓
Procedures
 ↓
Current encounter
 ↓
AI Summary
```

### Encounter Summary

Summarize:

- Chief complaint
- History
- Examination
- Vitals
- Assessment
- Diagnoses
- Orders
- Prescription
- Follow-up

AI must distinguish documented facts from inferred content.

---

# 15. Clinical Note Assistance

Provide optional drafting:

- HPI draft
- Progress note draft
- Discharge summary draft
- Referral letter draft
- Operative note draft
- Clinical handover draft
- Patient instruction draft

Every generated document must clearly be a draft until accepted/finalized by an authorized clinician.

---

# 16. AI Provenance

Every AI-generated output should track:

```text id="jy3w0z"
ai_request_id
user_id
use_case
model
provider
model_version
prompt_version
context_version
timestamp
source_records
output
confidence/uncertainty where applicable
review_status
reviewed_by
reviewed_at
accepted/modified/rejected
```

---

# 17. AI Output Status

Suggested lifecycle:

```text id="z1m2fb"
Generated
Presented
Under Review
Accepted
Modified
Rejected
Expired
Superseded
```

Never silently replace the original source data.

---

# 18. Clinical Decision Support

Clinical decision support may identify:

- Missing documentation
- Potential inconsistencies
- Potentially relevant documented allergies
- Potential medication duplication
- Potential contraindication signals
- Potential follow-up gaps
- Abnormal-result review reminders
- Care-plan gaps

The system must clearly distinguish:

```text id="kn72up"
Documented fact
Potential issue
AI suggestion
Clinical decision
```

---

# 19. Medication Intelligence

Integrate with Phase 7.

Potential features:

- Medication reconciliation assistance
- Duplicate medication detection
- Allergy cross-check assistance
- Interaction information retrieval
- Adherence analysis
- Prescription summarization
- Medication history summarization

AI must not independently prescribe or modify medication.

Existing Pharmacy/clinical safety rules remain authoritative.

---

# 20. Laboratory Intelligence

Integrate with Phase 5.

Potential features:

- Result summarization
- Trend detection
- Critical result notification assistance
- Longitudinal lab trend visualization
- Missing-result detection
- Abnormal-pattern flagging

Example:

```text id="u6eg7n"
Lab Results
 ↓
Trend Engine
 ↓
Observed trend
 ↓
AI explanation
 ↓
Clinician review
```

AI must not independently diagnose from laboratory data.

---

# 21. Radiology Intelligence

Integrate with Phase 6 and Phase 18.

Potential capabilities:

- Report summarization
- Structured report assistance
- Prior-study comparison assistance
- Critical finding workflow assistance
- Radiology report quality checks

Medical image interpretation requires a separately validated and governed model.

If an external radiology AI is used:

```text id="9es7jj"
PACS
 ↓
External AI
 ↓
AI Result
 ↓
Radiologist Review
 ↓
Final Report
```

The radiologist remains responsible for the final report.

---

# 22. ICU Intelligence

Potential:

- Vital-sign trend detection
- Deterioration-risk alerts
- Monitoring summaries
- Care-plan summaries
- Ventilator data analysis foundation
- ICU workload forecasting

AI alerts must be:

- Explainable
- Auditable
- Configurable
- Clinician-reviewable

Avoid alert overload.

---

# 23. Emergency AI

Potential:

- Triage support
- Queue forecasting
- Resource demand forecasting
- Patient-flow prediction
- Documentation assistance
- Risk flags

AI triage recommendations must never replace approved clinical triage procedures without appropriate validation and governance.

---

# 24. OT Intelligence

Potential:

- Operating-list optimization
- Duration prediction
- Delay prediction
- Utilization forecasting
- Documentation assistance
- Cancellation analysis

AI cannot autonomously schedule or cancel surgery.

---

# 25. Blood Bank Intelligence

Potential:

- Demand forecasting
- Inventory forecasting
- Expiry-risk detection
- Rare-group demand monitoring
- Stock anomaly detection

Compatibility and transfusion decisions remain controlled by Phase 13.

AI cannot override compatibility rules.

---

# 26. Inventory Intelligence

Integrate with Phase 15.

Potential:

- Demand forecasting
- Reorder prediction
- Expiry prediction
- Overstock detection
- Stock-out risk
- Supplier anomaly detection
- Procurement forecasting

AI recommendations require authorized human approval.

---

# 27. Workforce Intelligence

Integrate with Phase 16.

Potential:

- Staffing demand forecasting
- Shift demand prediction
- Absence patterns
- Workforce capacity
- Overtime analytics

Do not use AI to make opaque employment decisions without appropriate governance.

---

# 28. Insurance Intelligence

Potential:

- Claim anomaly detection
- Missing documentation detection
- Claim summarization
- Denial-pattern analysis
- Claim-aging prediction

AI should assist claim preparation but must not fabricate clinical documentation.

---

# 29. Patient Portal AI

Integrate with Phase 17.

Possible capabilities:

### Administrative Assistant

Can answer:

- Appointment information
- Hospital services
- Operating hours
- Billing process
- Document navigation
- Portal navigation

### Clinical Information

For patient-facing clinical questions, use carefully governed knowledge and appropriate escalation.

The assistant must not present itself as a replacement for a clinician.

---

# 30. Patient Safety Escalation

When a patient asks about potentially urgent symptoms, the AI should follow configured safety workflows.

Example:

```text id="z1unq4"
Patient Message
 ↓
Safety Classifier
 ↓
Potential Urgency
 ↓
Emergency/Clinical Escalation
```

Do not allow the chatbot to simply continue normal conversation when an escalation policy is triggered.

---

# 31. RAG / Knowledge Retrieval

Implement a Retrieval-Augmented Generation architecture for approved knowledge.

Sources may include:

- Hospital policies
- Clinical guidelines
- Patient education materials
- Internal SOPs
- Approved medical references
- Drug information sources
- Operational documentation

Architecture:

```text id="th7cz7"
User Question
 ↓
Authorization
 ↓
Retriever
 ↓
Approved Knowledge
 ↓
Context
 ↓
LLM
 ↓
Answer + Sources
```

---

# 32. Knowledge Base

Suggested:

```text id="c5vpgm"
ai_knowledge_sources
ai_knowledge_documents
ai_knowledge_versions
ai_knowledge_chunks
ai_knowledge_embeddings
ai_knowledge_permissions
ai_knowledge_ingestion_runs
```

Documents should support:

- Versioning
- Ownership
- Effective date
- Expiry date
- Approval
- Source
- Sensitivity

---

# 33. Vector Search

Provide an abstraction for:

- Vector database
- PostgreSQL vector extension where available
- External vector database
- Managed embedding service

Do not tightly couple the HMS to one vector database.

---

# 34. Prompt Management

Prompts must not be scattered throughout controllers.

Create:

```text id="m5zt47"
ai_prompt_templates
ai_prompt_versions
ai_prompt_variables
ai_prompt_test_cases
ai_prompt_approvals
```

Every production prompt should be versioned.

---

# 35. Prompt Versioning

Track:

```text id="x1kz52"
Prompt
Version
Author
Reviewer
Effective Date
Change Reason
Status
```

Example:

```text id="ojl0ed"
Clinical Summary Prompt v1
Clinical Summary Prompt v2
```

AI audit must identify which version generated an output.

---

# 36. Model Registry

Suggested:

```text id="p3w9r4"
ai_models
ai_model_versions
ai_model_providers
ai_model_capabilities
ai_model_evaluations
ai_model_deployments
```

Track:

- Model
- Provider
- Version
- Context size
- Capabilities
- Cost
- Latency
- Approved use cases
- Risk level
- Status

---

# 37. Use-Case Registry

Every AI capability should be registered.

Example:

```text id="c0e8sh"
ai_use_cases
```

Fields:

```text id
code
name
description
category
risk_level
allowed_roles
allowed_data
model
prompt
requires_human_review
active
```

---

# 38. AI Policy Engine

Before an AI request:

```text id="s7g8yk"
User
 ↓
Use Case
 ↓
Data Classification
 ↓
Role
 ↓
Hospital
 ↓
Patient
 ↓
Consent
 ↓
Model
 ↓
Policy
 ↓
Allow / Deny / Require Review
```

---

# 39. AI Data Classification

Classify data:

```text id="8cc9n8"
Public
Internal
Confidential
Sensitive
Highly Sensitive Clinical
Restricted
```

AI providers/models must be mapped to permitted data classifications.

Example:

```text id="1bn9hs"
Public knowledge
→ external LLM allowed

Highly sensitive clinical data
→ only approved secure AI provider/model
```

---

# 40. External AI Provider Controls

For every provider configure:

- Data residency
- Retention behavior
- Training policy
- Encryption
- Contract status
- Approved data classification
- Allowed use cases

Do not send sensitive patient data to an unapproved AI provider.

---

# 41. PHI/PII Protection

Before external AI transmission, where applicable:

- Remove unnecessary identifiers
- Tokenize identifiers
- Mask sensitive fields
- Minimize context
- Apply provider policy

Example:

```text id="0avb9r"
Patient:
MRN → tokenized
Name → removed if unnecessary
DOB → age where sufficient
```

---

# 42. AI Audit

Audit:

- AI request
- User
- Use case
- Patient/resource scope
- Model
- Provider
- Prompt version
- Context sources
- Output
- Review status
- Final action
- Rejection
- Modification

Audit must not unnecessarily store complete sensitive AI payloads if metadata is sufficient.

---

# 43. AI Feedback

Allow authorized users to:

- Accept
- Reject
- Modify
- Report incorrect
- Report unsafe
- Report irrelevant
- Report missing context

Feedback should feed the evaluation system.

---

# 44. AI Evaluation Framework

Implement evaluation datasets.

Evaluate:

### Accuracy

Does the output correctly reflect source information?

### Completeness

Does it omit important information?

### Hallucination

Does it introduce unsupported information?

### Safety

Could the output create clinical risk?

### Bias

Does performance vary significantly across relevant populations?

### Consistency

Does the same input produce acceptable outputs?

### Latency

Is response time acceptable?

---

# 45. Clinical AI Evaluation

Before production deployment of a clinical use case:

```text id="gwhvzs"
Prototype
 ↓
Offline Evaluation
 ↓
Clinical Review
 ↓
Safety Review
 ↓
Pilot
 ↓
Monitoring
 ↓
Production Approval
```

Do not move directly from prototype to autonomous clinical use.

---

# 46. AI Evaluation Tables

Suggested:

```text id="5c4nmb"
ai_evaluation_datasets
ai_evaluation_cases
ai_evaluation_runs
ai_evaluation_results
ai_safety_reviews
ai_clinical_reviews
ai_approval_records
```

---

# 47. AI Monitoring

Monitor:

- Usage
- Latency
- Token consumption where applicable
- Cost
- Errors
- Hallucination reports
- Safety reports
- User feedback
- Acceptance rate
- Rejection rate
- Model drift
- Data drift
- Alert volume

---

# 48. AI Incident Management

Provide AI incident workflow:

```text id="g4gqgp"
AI Incident
 ↓
Report
 ↓
Classify
 ↓
Contain
 ↓
Investigate
 ↓
Review
 ↓
Correct
 ↓
Retest
 ↓
Close
```

Examples:

- Unsafe recommendation
- Hallucinated information
- Privacy leakage
- Unauthorized data exposure
- Wrong patient context
- Incorrect model routing

---

# 49. Wrong-Patient Protection

This is mandatory.

Before clinical AI processing:

```text id="8gcqf8"
Patient ID
+
Encounter ID
+
Context
+
Authorization
```

must be validated.

The AI context engine must prevent accidental mixing of two patients.

---

# 50. Wrong-Context Protection

AI must identify context boundaries.

Example:

```text id="wgr6qv"
Patient A
Encounter A1
```

must never be mixed with:

```text id="2kh0js"
Patient B
Encounter B1
```

without explicit authorized workflow.

---

# 51. AI Clinical Timeline

Provide an AI-assisted timeline:

```text id="9u5y0h"
2019
Diagnosis
 ↓
2021
Procedure
 ↓
2024
Admission
 ↓
2025
Medication
 ↓
2026
Current Encounter
```

AI should summarize rather than rewrite source history.

---

# 52. Clinical Risk Scoring

Support configurable risk-model architecture.

Examples:

- Readmission risk
- Deterioration risk
- No-show risk
- Sepsis-related research/decision-support models where clinically validated
- Medication-risk signals
- Claim anomaly risk

Risk scores must include:

- Model version
- Input timestamp
- Feature version
- Score
- Threshold
- Explanation where available
- Review status

---

# 53. Risk Score Safety

Never display a risk score as a diagnosis.

Use:

```text id="d2b6gy"
"Model-estimated risk"
```

not:

```text id="l4d0zv"
"Patient has condition X"
```

unless that is an actual clinician-documented diagnosis.

---

# 54. Explainability

Where feasible provide:

- Important contributing factors
- Data timestamp
- Model version
- Confidence/uncertainty
- Limitations

Avoid fabricated explanations for models that do not support explainability.

---

# 55. AI Alert Management

AI alerts should support:

- Severity
- Confidence
- Reason
- Source
- Timestamp
- Acknowledgement
- Snooze
- Escalation
- Resolution

Prevent duplicate alerts.

---

# 56. Alert Fatigue Protection

Implement:

- Deduplication
- Suppression rules
- Cooldown periods
- Escalation thresholds
- User preferences where clinically appropriate
- Alert grouping

Clinical safety rules must take precedence over convenience.

---

# 57. AI Search

Provide semantic search over authorized information.

Example:

> "Find previous admissions related to this condition."

Flow:

```text id="mlj2q7"
Question
 ↓
Authorization
 ↓
Semantic Retrieval
 ↓
Relevant Records
 ↓
Evidence
 ↓
Answer
```

Every answer should link to the source records where practical.

---

# 58. AI Evidence

For clinical AI, provide evidence references:

```text id="v4u5ed"
AI Statement
 ↓
Source
 ↓
Encounter
 ↓
Document
 ↓
Result
```

The AI must distinguish:

- Directly documented
- Derived from structured data
- Retrieved knowledge
- Model-generated interpretation

---

# 59. Clinical Knowledge Sources

Knowledge may include:

- Hospital-approved guidelines
- Approved clinical protocols
- Drug references
- Clinical pathways
- Patient education
- Internal SOPs

Every knowledge source must have:

- Owner
- Version
- Approval
- Effective date
- Review date

---

# 60. AI API

Base:

```text id="xkz58w"
/api/v1/ai
```

Possible endpoints:

```text id="fx1n5h"
POST /chat
POST /summarize
POST /clinical-summary
POST /draft-note
POST /explain
POST /search
POST /risk-score
POST /anomaly-detection

GET  /models
GET  /use-cases
GET  /prompts
GET  /ai-requests/{id}
GET  /ai-audit
```

Actual endpoints must follow existing API architecture.

---

# 61. AI Streaming

For conversational features, support streaming where appropriate.

Streaming must still:

- Respect authorization
- Apply data policies
- Log request metadata
- Apply safety controls

---

# 62. AI Jobs

Examples:

```text id="0rqx3e"
GenerateClinicalSummary
GenerateDischargeSummaryDraft
GenerateOperativeNoteDraft
GenerateReferralDraft
AnalyzeLabTrend
AnalyzeMedicationHistory
GeneratePatientTimeline
RunRiskModel
DetectAnomaly
GenerateDashboardNarrative
ProcessKnowledgeDocument
GenerateEmbeddings
EvaluateAiOutput
RunAiMonitoring
```

Critical clinical transactions must not depend on AI jobs completing.

---

# 63. AI Events

Examples:

```text id="3gc0ry"
AiRequestCreated
AiRequestCompleted
AiRequestFailed
AiOutputGenerated
AiOutputAccepted
AiOutputRejected
AiOutputModified

AiSafetyIssueReported
AiIncidentCreated
AiIncidentResolved

AiModelChanged
AiPromptVersionChanged
AiUseCaseApproved
AiUseCaseDisabled

AiRiskScoreGenerated
AiAlertCreated
AiAlertAcknowledged
```

---

# 64. Suggested Database Tables

## AI Core

```text id="rb7j3d"
ai_providers
ai_models
ai_model_versions
ai_model_capabilities
ai_model_deployments
ai_use_cases
ai_policies
ai_policy_rules
```

## Prompt Management

```text id="n9t2gl"
ai_prompt_templates
ai_prompt_versions
ai_prompt_variables
ai_prompt_test_cases
ai_prompt_approvals
```

## Requests and Outputs

```text id="9d4g5w"
ai_requests
ai_request_contexts
ai_request_sources
ai_outputs
ai_output_reviews
ai_output_feedback
```

## Knowledge/RAG

```text id="f8u4wz"
ai_knowledge_sources
ai_knowledge_documents
ai_knowledge_versions
ai_knowledge_chunks
ai_knowledge_embeddings
ai_knowledge_permissions
ai_knowledge_ingestion_runs
```

## Clinical Intelligence

```text id="x4o8qa"
ai_clinical_summaries
ai_clinical_drafts
ai_risk_models
ai_risk_model_versions
ai_risk_scores
ai_clinical_alerts
ai_alert_events
```

## Evaluation

```text id="9w2k3c"
ai_evaluation_datasets
ai_evaluation_cases
ai_evaluation_runs
ai_evaluation_results
ai_safety_reviews
ai_clinical_reviews
ai_approval_records
```

## Governance

```text id="7m1q2r"
ai_incidents
ai_incident_events
ai_ai_audits
ai_model_monitoring
ai_usage_metrics
ai_cost_metrics
```

Use names consistent with the existing application's conventions.

---

# 65. Database Design Rules

AI tables should reference existing entities.

For example:

```text
patient_id
encounter_id
organization_id
hospital_id
user_id
```

where required.

Do not copy complete patient records into AI tables.

Use references to authoritative records wherever possible.

---

# 66. Multi-Hospital AI

Support:

```text id="7q3f2v"
Organization
 ├── Hospital A
 │    └── AI Policies
 │
 └── Hospital B
      └── AI Policies
```

AI model/provider policies may differ by hospital.

Example:

- Hospital A permits Provider X.
- Hospital B permits Provider Y.

The AI Gateway must enforce these policies.

---

# 67. Permissions

Examples:

```text id="3g5tqv"
ai.dashboard.view

ai.chat.use
ai.search.use

ai.clinical_summary.generate
ai.clinical_summary.review

ai.note_draft.generate
ai.note_draft.accept
ai.note_draft.reject

ai.risk_score.view
ai.risk_score.generate

ai.model.view
ai.model.manage
ai.model.approve

ai.prompt.view
ai.prompt.manage
ai.prompt.approve

ai.use_case.view
ai.use_case.manage
ai.use_case.approve

ai.knowledge.view
ai.knowledge.manage
ai.knowledge.approve

ai.evaluation.view
ai.evaluation.run

ai.incident.view
ai.incident.manage

ai.audit.view
```

High-risk clinical AI permissions should be restricted.

---

# 68. Recommended Roles

### AI Administrator

AI platform configuration.

### AI Engineer

Models, integrations and evaluation.

### Data Scientist

ML models and analytics.

### Clinical AI Reviewer

Clinical validation.

### Clinical Informatics Lead

Clinical AI governance.

### Physician Reviewer

Clinical output review.

### AI Safety Officer

Safety and incidents.

### Knowledge Administrator

RAG knowledge management.

### BI/Analytics User

Analytics AI.

### Patient AI User

Restricted patient-facing assistant.

---

# 69. Dashboard

AI Dashboard:

```text id="kr1y4z"
AI Overview
├── Active Use Cases
├── AI Requests
├── Success Rate
├── Average Latency
├── Cost
├── Model Usage
├── User Feedback
├── Rejection Rate
├── Safety Incidents
├── Model Drift
├── Data Drift
└── High-Risk Alerts
```

---

# 70. Clinical AI Dashboard

Authorized clinicians may see:

```text id="6x8b0k"
Clinical Intelligence
├── Patient Summaries
├── Documentation Assistance
├── Relevant Alerts
├── Risk Scores
├── Trend Analysis
├── Medication Intelligence
├── Lab Intelligence
└── Follow-up Signals
```

AI outputs must be clearly marked.

---

# 71. Patient-Facing AI Dashboard

Phase 17 may consume AI services for:

- FAQ
- Appointment assistance
- Navigation
- Administrative questions
- Patient education

Clinical advice must follow the configured safety policy.

---

# 72. AI Cost Management

Track:

- Requests
- Model usage
- Tokens where applicable
- Provider costs
- Cost by use case
- Cost by hospital
- Cost by department

Provide budget controls.

---

# 73. Rate Limits

Implement:

- User limits
- Role limits
- Hospital limits
- Provider limits
- Use-case limits
- Patient-facing limits

Protect against:

- abuse
- runaway jobs
- accidental loops
- excessive provider costs

---

# 74. AI Availability

AI must be treated as an enhancement, not a dependency for core clinical operations.

If AI is unavailable:

```text id="0ib1n5"
AI unavailable
      ↓
Clinical system continues normally
```

Do not block:

- Patient registration
- Encounter completion
- Lab reporting
- Radiology reporting
- Prescription
- Dispensing
- Admission
- Surgery
- Billing
- Discharge

because an AI provider is unavailable.

---

# 75. AI Failure Handling

Example:

```text id="2g5kqf"
AI Request
 ↓
Provider Timeout
 ↓
Retry if safe
 ↓
Fallback model if approved
 ↓
Failure message
 ↓
Clinical workflow continues
```

Never silently substitute an unapproved AI model for a high-risk clinical use case.

---

# 76. AI Fallback Policy

Fallback models must be explicitly configured.

Example:

```text id="h6m8a1"
Clinical Summary
Primary → Approved Model A
Fallback → Approved Model B
```

But:

```text id="3x1l5k"
High-risk clinical prediction
Primary → Model A
Fallback → NONE
```

may be appropriate depending on governance.

---

# 77. Model Drift

Monitor:

- Input distribution
- Output distribution
- Accuracy where labels become available
- Performance changes
- Missing features
- Data-source changes

If significant degradation is detected:

```text id="6a7xw1"
Detect
 ↓
Review
 ↓
Alert
 ↓
Restrict/disable if required
 ↓
Re-evaluate
```

---

# 78. AI Governance Lifecycle

Every clinical AI use case should follow:

```text id="1a3f8x"
Idea
 ↓
Risk Classification
 ↓
Data Assessment
 ↓
Prototype
 ↓
Evaluation
 ↓
Clinical Review
 ↓
Security Review
 ↓
Privacy Review
 ↓
Pilot
 ↓
Monitoring
 ↓
Production Approval
 ↓
Periodic Reassessment
```

---

# 79. AI Change Management

Changes to:

- Model
- Prompt
- Data source
- Knowledge base
- Threshold
- Risk logic
- Provider

must create a new version and be auditable.

Do not silently replace production AI components.

---

# 80. AI Documentation

For every production AI use case maintain:

```text id="3o4p8d"
Purpose
Owner
Risk Level
Model
Version
Prompt
Data Sources
Input Fields
Output
Known Limitations
Validation
Approval
Monitoring
Escalation
Retirement Criteria
```

---

# 81. AI Retirement

Support:

```text id="9u6d1r"
Active
Pilot
Suspended
Deprecated
Retired
```

Retired models remain historically identifiable for audit.

---

# 82. Clinical AI Transparency

The UI should indicate:

> AI-generated suggestion

and where applicable:

> Reviewed by Dr. [authorized user]

Never make AI-generated text visually indistinguishable from clinician-authored content.

---

# 83. AI Acceptance Workflow

For clinical documentation:

```text id="u4e3b6"
AI Draft
 ↓
Clinician Review
 ↓
Edit
 ↓
Accept
 ↓
Clinical Record
```

The final clinical record belongs to the clinician/clinical module, not AI.

---

# 84. AI Clinical Record Boundary

Example:

```text id="m7s9l2"
AI generates HPI draft
       ↓
Doctor reviews
       ↓
Doctor modifies
       ↓
Doctor accepts
       ↓
Phase 3 Encounter Note
```

Phase 20 does not become the owner of the encounter note.

---

# 85. AI Interoperability

Integrate with Phase 18 for:

- FHIR retrieval
- HL7 analysis
- DICOM AI integrations
- External clinical AI services

All external AI integrations must pass through the AI Gateway and security policies.

---

# 86. AI Analytics

Integrate with Phase 19.

Use approved warehouse datasets for:

- Forecasting
- Trend analysis
- Anomaly detection
- Natural-language BI
- Executive summaries
- Capacity planning

AI should not query production clinical databases for broad analytical questions when Phase 19 already provides governed datasets.

---

# 87. AI + FHIR

Support future AI services using:

- Patient
- Encounter
- Observation
- Condition
- Procedure
- MedicationRequest
- MedicationDispense
- DiagnosticReport
- CarePlan
- ServiceRequest

FHIR resources must pass authorization before AI use.

---

# 88. AI + Knowledge Graph Foundation

Prepare for future relationships:

```text id="x0v7s5"
Patient
 ├── Encounter
 ├── Condition
 ├── Medication
 ├── Observation
 ├── Procedure
 ├── DiagnosticReport
 └── CarePlan
```

Do not require a graph database in the first implementation.

Provide abstraction for future graph capabilities.

---

# 89. AI API Security

AI APIs must have:

- Authentication
- Authorization
- Rate limiting
- Request validation
- Context validation
- Patient-scope validation
- Audit
- Abuse prevention

Never allow arbitrary prompt + arbitrary database query execution.

---

# 90. AI Prompt Injection Protection

Patient-generated text, uploaded documents, external messages and retrieved content must be treated as **untrusted input**.

Implement protections against:

- Prompt injection
- Instruction hijacking
- Data exfiltration
- Tool abuse
- Cross-patient context manipulation

Retrieved content must never automatically override system/developer/application policies.

---

# 91. AI Tool-Use Security

If AI can call tools:

```text id="b5m1kr"
AI
 ↓
Tool Request
 ↓
Authorization
 ↓
Policy Check
 ↓
Tool
 ↓
Result
```

The model must never directly execute arbitrary SQL or shell commands.

Critical actions require explicit user confirmation and appropriate permissions.

---

# 92. AI Clinical Action Confirmation

For any action that could change clinical or financial state:

```text id="4x5q6n"
AI Suggestion
 ↓
User Confirmation
 ↓
Authorization
 ↓
Existing Domain Service
 ↓
Transaction
 ↓
Audit
```

The AI must never directly bypass the domain service.

---

# 93. Suggested AI Action Boundary

Safe:

```text id="e2m7a9"
"Draft prescription instructions."
```

Unsafe:

```text id="r9y1e3"
AI automatically creates prescription.
```

Safe:

```text id="h5p4q2"
"Suggest potentially relevant follow-up."
```

Unsafe:

```text id="k2m6r8"
AI automatically books follow-up appointment.
```

Unless a future explicitly approved automation policy permits it.

---

# 94. Testing Strategy

## Unit Tests

Test:

- AI provider adapters
- Policy engine
- Context engine
- Prompt versioning
- Model routing
- Output validation
- Risk classification
- PII masking

## Feature Tests

Test:

- AI chat
- Summarization
- Clinical drafts
- AI search
- Risk scores
- Dashboards
- Feedback
- Approval

## Security Tests

Mandatory:

```text id="k1q5r7"
Hospital A AI user
 ↓
Hospital B patient context
 ↓
DENIED
```

Also:

```text id="p4v8x2"
AI
 ↓
attempt direct SQL
 ↓
DENIED
```

and:

```text id="n3r6t1"
Patient-generated prompt
 ↓
attempt to retrieve another patient's data
 ↓
DENIED
```

## Safety Tests

Test:

- hallucination detection
- wrong-patient protection
- wrong-encounter protection
- prompt injection
- unsafe recommendations
- prohibited actions
- missing context
- stale knowledge
- model failure

## Evaluation Tests

Test approved clinical AI datasets.

Never use production patient data for testing unless the data-use policy explicitly permits it.

---

# 95. Definition of Done

Phase 20 is complete only when:

## Platform

- AI Gateway implemented.
- Provider abstraction implemented.
- Model registry implemented.
- Use-case registry implemented.
- Prompt registry implemented.
- AI policy engine implemented.
- AI audit implemented.

## Clinical Intelligence

- Patient summarization.
- Clinical timeline.
- Documentation assistance.
- Medication intelligence.
- Laboratory intelligence.
- Radiology integration foundation.
- ICU intelligence foundation.
- Emergency intelligence foundation.
- OT intelligence.
- Blood Bank forecasting.
- Clinical risk-model foundation.

## Operational AI

- Inventory forecasting.
- Workforce forecasting.
- Insurance analytics.
- Appointment forecasting.
- Bed forecasting.
- Patient-flow analytics.

## RAG

- Knowledge sources.
- Versioning.
- Embeddings.
- Retrieval.
- Authorization.
- Source citations.

## Governance

- Risk classification.
- Human review.
- Clinical approval.
- AI safety review.
- Incident management.
- Model monitoring.
- Prompt versioning.
- Model versioning.

## Security

- Multi-hospital isolation.
- Patient-context isolation.
- Data minimization.
- PII/PHI protection.
- Provider restrictions.
- Rate limits.
- Prompt-injection protection.

## AI Safety

- No autonomous diagnosis.
- No autonomous prescribing.
- No autonomous clinical record modification.
- No autonomous surgery/blood/discharge decisions.
- No permission bypass.

## Analytics

- Phase 19 integration.
- Governed datasets.
- Natural-language analytics.
- AI explanations.
- Forecasting foundation.

## Testing

- Unit tests.
- Feature tests.
- Security tests.
- Safety tests.
- Evaluation tests.
- Prompt injection tests.
- Wrong-patient tests.
- Wrong-encounter tests.
- Provider failure tests.

## Documentation

- AI architecture.
- Model registry.
- Prompt registry.
- AI use cases.
- Risk classification.
- Clinical governance.
- Security.
- Privacy.
- Evaluation.
- Monitoring.
- Incident response.
- Deployment.
- Disaster recovery.

---

# 96. Recommended Implementation Order

Implement in this sequence:

1. Inspect repository.
2. Inspect Phases 0–19.
3. Map existing domain services/events.
4. Map Phase 19 analytical datasets.
5. Define AI governance architecture.
6. Create AI Gateway.
7. Create provider abstraction.
8. Create model registry.
9. Create use-case registry.
10. Create AI policy engine.
11. Create context engine.
12. Create prompt registry.
13. Create AI request/output framework.
14. Create AI audit.
15. Implement secure context retrieval.
16. Implement RAG foundation.
17. Implement knowledge management.
18. Implement patient timeline summarization.
19. Implement clinical summarization.
20. Implement documentation assistance.
21. Implement medication intelligence.
22. Implement laboratory intelligence.
23. Implement radiology integration.
24. Implement ICU intelligence.
25. Implement Emergency intelligence.
26. Implement OT intelligence.
27. Implement Blood Bank intelligence.
28. Implement inventory forecasting.
29. Implement workforce intelligence.
30. Implement insurance intelligence.
31. Implement patient portal AI.
32. Implement natural-language analytics.
33. Implement anomaly detection.
34. Implement risk-model framework.
35. Implement AI evaluation.
36. Implement safety/incident management.
37. Implement monitoring.
38. Implement cost management.
39. Implement model/prompt versioning.
40. Implement security testing.
41. Implement clinical safety testing.
42. Implement performance testing.
43. Complete documentation.
44. Complete deployment/runbook.

---

# 97. Final AI Coding Prompt

You are a senior healthcare AI architect, clinical informatics architect, ML engineer, data engineer and Laravel/PHP enterprise software engineer.

Implement **Phase 20 — AI / Clinical Intelligence** in the existing Hospital Management System.

## Critical Instructions

The existing HMS modular architecture is ALREADY IMPLEMENTED.

Before writing code:

1. Inspect the complete repository.
2. Inspect Phases 0–19.
3. Understand all existing modules.
4. Reuse existing architecture.
5. Reuse existing domain services.
6. Reuse existing authentication/RBAC.
7. Reuse existing audit.
8. Reuse existing notifications.
9. Reuse existing queues.
10. Reuse Phase 18 interoperability.
11. Reuse Phase 19 analytics/data warehouse.
12. DO NOT create a second modular framework.
13. DO NOT duplicate clinical domain ownership.
14. DO NOT move patient/clinical/billing ownership into AI.

---

## Objective

Build a production-grade AI platform providing:

- AI Gateway
- AI provider abstraction
- Model registry
- Prompt registry
- AI use-case registry
- AI policy engine
- Context engine
- Clinical summarization
- Documentation assistance
- Clinical decision-support foundation
- Predictive analytics
- Risk-model framework
- Anomaly detection
- RAG
- Knowledge management
- Natural-language analytics
- Patient-facing AI
- Operational AI
- AI governance
- AI evaluation
- AI safety
- AI incident management
- AI audit
- Model monitoring
- Cost monitoring

---

## Mandatory Safety Principle

AI assists humans.

AI must NOT autonomously:

- diagnose
- prescribe
- modify medication
- modify clinical records
- approve surgery
- approve blood compatibility
- approve discharge
- finalize lab reports
- finalize radiology reports
- change billing
- change insurance claims

Clinical actions must go through existing authorized domain services.

---

## Architecture

Implement:

```text id="e3j8h2"
HMS
 ↓
Phase 19 Governed Analytics / Data
 ↓
AI Context Engine
 ↓
AI Policy Engine
 ↓
AI Gateway
 ↓
Approved Model
 ↓
Safety Validation
 ↓
Human Review
 ↓
Existing Domain Service
 ↓
Audit
```

---

## AI Gateway

All AI calls must pass through the gateway.

Do not allow:

```text id="8p1j4m"
Clinical Module
 ↓
Direct LLM API
```

Use:

```text id="j3k6n9"
Clinical Module
 ↓
AI Gateway
```

---

## Provider Abstraction

Create an interface similar to:

```php id="m9q2s4"
interface AiProviderInterface
{
    public function generate(AiRequest $request): AiResponse;

    public function stream(AiRequest $request): AiStream;

    public function embeddings(AiEmbeddingRequest $request): AiEmbeddingResponse;
}
```

Create provider adapters as required.

---

## AI Governance

Every AI use case must define:

- owner
- purpose
- risk level
- allowed roles
- permitted data
- model
- prompt
- human-review requirement
- clinical approval status
- security status
- monitoring policy

---

## Context Security

Before AI receives data:

```text id="7r4w2p"
User
 ↓
Role
 ↓
Organization
 ↓
Hospital
 ↓
Patient
 ↓
Encounter
 ↓
Resource
 ↓
Use Case
```

must be authorized.

Implement wrong-patient and wrong-encounter protection.

---

## Data Minimization

Send only the minimum necessary information.

Do not automatically send the complete Patient 360 to an AI provider.

---

## AI Provider Security

Implement configurable policies for:

- provider
- model
- data classification
- retention
- training usage
- data residency
- encryption
- approved use cases

Never send highly sensitive clinical data to an unapproved provider.

---

## RAG

Implement:

- knowledge sources
- documents
- versions
- chunks
- embeddings
- retrieval
- permissions
- source references

AI responses should cite their retrieved knowledge sources where appropriate.

---

## Clinical Summarization

Implement:

- patient timeline
- encounter summary
- discharge summary draft
- referral draft
- handover draft
- operative-note draft

All clinical drafts require human review.

---

## Clinical Decision Support

Implement a foundation for:

- medication safety
- laboratory trend analysis
- clinical documentation completeness
- risk signals
- follow-up signals
- care-plan gaps

AI recommendations must remain advisory.

---

## Phase Integration

Integrate with:

```text id="6uj7kx"
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
Phase 19 BI/Analytics
```

Respect every module's ownership.

---

## Natural-Language Analytics

Use Phase 19 approved datasets.

Flow:

```text id="7t3p9v"
Question
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

Never permit arbitrary AI-generated SQL against production clinical databases.

---

## AI Evaluation

Implement:

- evaluation datasets
- evaluation cases
- automated evaluation
- clinical review
- safety review
- hallucination testing
- consistency testing
- bias testing
- latency testing

Clinical AI must pass appropriate evaluation before production activation.

---

## AI Monitoring

Monitor:

- requests
- failures
- latency
- cost
- model usage
- user feedback
- acceptance/rejection
- safety incidents
- drift
- data quality

---

## AI Incident Management

Implement:

```text id="0u6s9f"
Report
 ↓
Classify
 ↓
Contain
 ↓
Investigate
 ↓
Correct
 ↓
Retest
 ↓
Close
```

---

## Prompt Injection

Treat all external/patient-provided/retrieved content as untrusted.

Protect against:

- prompt injection
- instruction hijacking
- data exfiltration
- unauthorized tool use
- cross-patient context attacks

AI must never be allowed to execute arbitrary SQL or shell commands.

---

## Tool Use

If AI can call application tools:

```text id="v8c4r1"
AI
 ↓
Tool Request
 ↓
Authorization
 ↓
Policy
 ↓
Tool
 ↓
Result
```

High-impact actions require explicit user confirmation and existing domain authorization.

---

## Database

Implement appropriate equivalents of:

```text id="q2m6v8"
ai_providers
ai_models
ai_model_versions
ai_model_capabilities
ai_model_deployments
ai_use_cases
ai_policies
ai_policy_rules

ai_prompt_templates
ai_prompt_versions
ai_prompt_variables
ai_prompt_test_cases
ai_prompt_approvals

ai_requests
ai_request_contexts
ai_request_sources
ai_outputs
ai_output_reviews
ai_output_feedback

ai_knowledge_sources
ai_knowledge_documents
ai_knowledge_versions
ai_knowledge_chunks
ai_knowledge_embeddings
ai_knowledge_permissions
ai_knowledge_ingestion_runs

ai_clinical_summaries
ai_clinical_drafts
ai_risk_models
ai_risk_model_versions
ai_risk_scores
ai_clinical_alerts
ai_alert_events

ai_evaluation_datasets
ai_evaluation_cases
ai_evaluation_runs
ai_evaluation_results
ai_safety_reviews
ai_clinical_reviews
ai_approval_records

ai_incidents
ai_incident_events
ai_audits
ai_model_monitoring
ai_usage_metrics
ai_cost_metrics
```

Adapt names to existing conventions.

---

## API

Use:

```text id="9k2w4c"
/api/v1/ai
```

Implement appropriate endpoints for:

- chat
- summarization
- clinical summary
- drafting
- search
- risk scores
- anomaly detection
- model management
- use-case management
- evaluation
- audit

Follow the existing API conventions.

---

## Security Tests

Mandatory:

```text id="c8m1v5"
Hospital A
 ↓
Hospital B patient
 ↓
DENIED
```

```text id="a4r7x2"
AI
 ↓
Unauthorized SQL
 ↓
DENIED
```

```text id="f9k3w6"
Patient prompt injection
 ↓
Attempt to access another patient
 ↓
DENIED
```

---

## Clinical Safety Tests

Test:

- wrong patient
- wrong encounter
- hallucination
- unsupported diagnosis
- unsupported medication recommendation
- prompt injection
- unsafe output
- missing context
- stale knowledge
- provider failure
- model failure
- prohibited autonomous action

---

## Performance

AI must never block core clinical transactions.

If AI is unavailable:

```text id="j6p2x8"
AI unavailable
 ↓
HMS continues normally
```

---

## Documentation

Create/update:

```text id="s3n8q5"
docs/ai/
```

Include:

- AI architecture
- Provider configuration
- Model registry
- Prompt management
- AI use cases
- Risk classification
- Clinical governance
- Security
- Privacy
- RAG
- Evaluation
- Monitoring
- Incident response
- Deployment
- Disaster recovery
- Troubleshooting
- Model retirement
```

---

## Final Validation

Before declaring Phase 20 complete:

1. Inspect Phases 0–19.
2. Confirm no duplicate clinical ownership.
3. Run migrations.
4. Run static analysis.
5. Run unit tests.
6. Run feature tests.
7. Run security tests.
8. Run safety tests.
9. Test wrong-patient protection.
10. Test wrong-encounter protection.
11. Test prompt injection.
12. Test unauthorized tool use.
13. Test provider failure.
14. Test AI fallback policies.
15. Test model/prompt versioning.
16. Test RAG authorization.
17. Test Phase 19 analytics integration.
18. Test Phase 18 interoperability integration.
19. Test AI audit.
20. Test AI incident workflow.

Never claim a test passed unless it was actually executed.

At completion, report:

```text
1. Files created
2. Files modified
3. Migrations
4. AI providers
5. Models
6. AI use cases
7. Prompts
8. RAG components
9. Clinical intelligence features
10. APIs
11. Events
12. Jobs
13. Permissions
14. Security controls
15. Safety controls
16. Evaluation results
17. Tests actually executed
18. Actual test results
19. Known limitations
20. Remaining TODOs
21. Deployment steps
22. Documentation
```

The final implementation must be production-oriented, provider-independent, secure, auditable, explainable where feasible, multi-hospital capable, privacy-conscious, clinician-controlled and fully consistent with the existing HMS modular architecture.