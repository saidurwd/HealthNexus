# AI and clinical intelligence — guide for clinicians, the governance team and IT

## Start
1. `php artisan ai:install` registers the thirteen functions (all planned) and a starter evaluation set in English and Bangla. Idempotent.
2. **AI → AI assistant → Set up the local engine** sets up the local rules provider, runs the starter evaluation, approves the model if it passes, and switches on the five low-risk functions. Nothing goes outside the hospital.
3. AI is **off for the whole hospital** until someone with the right switches it on. The master switch stops every function at once.
4. Roles ready to use: `ai_governance_lead`, `ai_clinical_reviewer`, `ai_safety_officer`. `ai.use` is also given to nursing, pharmacy, store and finance roles.

## Every day
- **Ask the assistant** (`/ai/ask`): read a patient's record back, draft a note for a visit, ask about hospital policy, ask about the hospital's figures, forecast the use of an item. Each answer is a draft marked "AI-generated — needs review" with its sources.
- **Review a draft** (`/ai/outputs`): type the patient number again, optionally edit, then accept or reject (a reason is needed). Accepting writes **nothing** into the record: copy what you want yourself. A draft lapses after 72 hours.
- **Tell us how it did**: useful, not useful, or something was wrong. A report of a harmful or wrong answer opens an incident.

## The four functions above low risk (off until two people switch them on)
- **Early-warning screen** (`/ai/early-warning`): the latest stored vital signs scored on the NEWS2 chart with every input and its points. Consciousness and extra oxygen are not stored, so the total is "at least". A screen, not a diagnosis.
- **Medicines for review** (`/ai/medicines`): allergies, active medicines, Pharmacy's own safety alerts and counts side by side. It never says to start, stop or change anything.
- **Check a claim** (`/ai/claim-check`): the Insurance module's own checks, documents, payer queries and deadline. It never submits or decides a claim.
- **Suggest a reading order** (`/ai/reading-order`): finished studies by priority then waiting time. The real worklist is not changed.
To switch one on: **AI → AI functions**: the owner sets it up (provider and model), then a **second person** switches it on. It needs the model to have passed that function's own evaluation (**AI → Evaluations**; the starter sets are created by `ai:install` and run when the local engine is set up). The tiles appear on the AI home only for functions that are on.

## Governance
- **AI functions**: owner, intended use, risk class, model. Planned functions cannot be switched on; above low risk a second person approves; anyone with the kill-switch right can pause one.
- **Providers and models**: no key is stored, only the name of the environment variable. A model is a candidate until an evaluation passes. A provider abroad is sent de-identified facts only unless a legal approval reference is recorded.
- **Prompts**: a change is a draft; a second person activates it.
- **Knowledge**: approved Quality documents plus items approved here.
- **Evaluations, incidents, monitoring, audit** show the register of sets and runs, the incident list, acceptance and drift, and the request log.

## Read-only API
`GET /api/v1/ai/use-cases`, `/api/v1/ai/outputs`, `/api/v1/ai/metrics` with a staff token (`Authorization: Bearer ...`), the `X-Organization-Id` header, and the right. It lists what exists and what happened; it cannot ask the AI anything or change anything.

## Commands
`ai:install [--organization=ID]` · `ai:check` (daily at 06:45) · `ai:redteam`.
