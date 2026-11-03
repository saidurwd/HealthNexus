# Phase 20 — S00 Inspect and map (AI and clinical intelligence)

Guideline 26.9 and 10.10. **The boundary: AI may inform, summarise, draft, suggest and forecast. It may never diagnose, prescribe, change doses, order, assign final triage, discharge, transfer, override alerts, finalise notes or reports, change demographics, bill or claim.** Every output is labelled "AI-generated — needs review", stores model, version, inputs and reviewer, and is a *suggestion object*, not a record.

## What exists (reused, never copied)
| Need | Existing | Use |
|---|---|---|
| Patient facts to summarise | `encounters`, `diagnoses`, `patient_allergies`, `encounter_vitals`, `lab_results` (via `lab_order_items`/`lab_orders`), `prescriptions`, `ipd_admissions`, `encounter_notes` | **read only**, in one place (`ContextEngine`) |
| Knowledge for answers | Phase 23 document control: approved, current document versions | the knowledge base; answers cite document code and version |
| Numbers and questions | Phase 19 `QuestionService` / semantic layer; `stock_ledger` | natural-language analytics shows its query; inventory forecast reads consumption |
| Alerts, audit, numbering, settings | `AlertService`, `AuditLogger`, `SettingsService` | same conventions |
| Tenancy and rights | organisation → branch, Spatie permissions | context is built only for a patient the user may see, in the user's hospital |

Nothing in any clinical module is written to. The AI code has **no dependency on any service that writes a clinical, billing, pharmacy or stock record** (a test checks this).

## Design
- **Engines compute facts; providers only phrase them; the guard checks every line.** A use case collects facts (each with a source reference like `lab:123`) and, when it needs numbers, computes them itself (the forecast). The provider turns facts into readable lines and must give each line its references. `OutputGuard` drops any line that has no valid reference, refers to a fact outside this patient's context, or contains an instruction to act (in English or Bangla). What is left is a draft. If nothing supported is left, the answer is "no supported answer" — never a guess.
- **Providers are a policy.** `fake` (scripted, for tests), `rules` (a local extractive engine, no model, no network — the default and what the demo uses), and `http` (an OpenAI-compatible endpoint; **off until configured**). Each provider has a location (on premise / Bangladesh / international). An international provider receives **de-identified, minimum-necessary** context only, unless a legal approval (reference, who, when) is recorded — and the secret (API key) is read from the environment at call time, never stored in the database or logged.
- **A model cannot be used until it is evaluated.** Models are candidates until a run of an evaluation set (which must include Bangla and mixed Bangla-English cases) meets the thresholds (recall of expected facts, zero unsupported lines, zero forbidden content). The gateway refuses a model that is not approved; changing the model version means a new candidate.
- **Use cases are registered before they run**: owner, intended use, risk class, data level, provider and model, kill switch. Low-risk ones (timeline summary, note draft, knowledge answer, inventory forecast, natural-language analytics) are built. The medium- and high-risk ones the guideline names (medication, laboratory, ICU/ED/OT, radiology, insurance, portal, risk scores) are **registered as planned and cannot be enabled** — the guideline says they come after the evaluation framework has proved itself and "risk scores last".
- **Human in the loop.** An output is a draft until a clinician accepts it. Acceptance needs the right, confirms the patient's number (wrong-patient protection), may edit the text (the original is kept), and is recorded. Drafts expire. A rejected or expired output never reaches a record. Accepted text is available to the clinician to copy; **the AI never writes it into a note**.
- **Untrusted text is data.** Free text from patients, notes or documents is wrapped as quoted data, checked for instruction-like content (English and Bangla), and flagged; the model is told it is data. The AI has **no tools**: it cannot call any action, so an injected "do X" has nothing to call. Anything it proposes is a line of text for a human.
- **Everything is audited.** Every call — allowed, blocked or failed — is an immutable request row (who, what use case, model, prompt version, the redacted inputs it was given, outcome); the audit log records acceptances and changes.
- **Monitoring and incidents.** Acceptance rate, rejections, error reports, blocked requests, suppressed lines, latency; drift (acceptance drops, evaluation regressions) raises an incident; incidents are managed with owner and closure.

## Slice plan
S01 gateway, providers, model and use-case registries · S02 policy, data classes, de-identification, external-provider controls · S03 context engine · S04 audit, provenance, output status, acceptance, boundary · S05 prompts, knowledge base, citations · S06 timeline summary, note draft · S07 evaluation, feedback, monitoring, incidents, drift · S08 inventory forecast, natural-language analytics · S09–S11 planned use cases (registered, disabled), score card format · S12 security (injection, no tools), kill switch, retirement, API, red-team pack, gate.

## Not in this phase (recorded)
Embedding/vector search (the knowledge search is keyword ranking; vectors need a provider decision) · streaming responses · medication/lab/ICU/ED/OT/radiology/insurance/portal AI and risk scores (registered, disabled) · any real provider contract or legal approval (the hospital's decision; nothing is sent anywhere by default).
