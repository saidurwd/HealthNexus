# D-P20 — AI and Clinical Intelligence: state and decisions

Status at the end of the build: see `docs/phase20/gate-f2-report.md`. The guideline's section 10.10 and the Phase 20 slices were the brief. Every threshold, pattern and forbidden phrase is an example for the hospital's AI governance committee to confirm.

## Decisions
1. **One door.** `AiGateway` is the only way to a model. Nothing else calls a provider. It refuses before it calls (switch, function, permission, provider, data class, injection) and filters after (`OutputGuard`). Refusals and failures are logged too.
2. **AI never writes to a record.** The result is an `ai_outputs` draft with sources, model, version, inputs and, later, the reviewer. Accepting copies nothing anywhere; the clinician copies what they want. The generated fields cannot be changed (trigger) and a draft cannot be deleted.
3. **Every line cites facts.** A line with no source, a foreign source, an unsupported number, or an instruction to act is removed and counted. If nothing is left the answer says "No supported answer".
4. **Untrusted text is data.** Notes, results and questions may contain instructions aimed at the AI; they are scored, neutralised and quoted as data, and a high score blocks the request and opens an incident.
5. **Start with a local engine.** The first provider is a local rules engine: no outside service, no model, it only re-states facts. It is evaluated (English and Bangla) before anything is switched on. An outside provider can be listed but is off, and a provider abroad gets de-identified data only unless a legal approval reference is recorded by someone with `ai.provider.legal`. Keys are never stored: only the name of the environment variable.
6. **A model is approved by an evaluation.** Thresholds are in `config/ai.php`. Both languages must be covered. A retired model pauses the functions that used it.
7. **Governance.** A function above low risk needs a second approver; a planned (unbuilt) function can never be switched on; `pause` is the kill switch for one function, the master switch for all. Prompt changes are versions a second person activates; each request records its prompt version.
8. **Wrong-patient protection.** Context is built for one patient and checked against the typed patient number when a draft is accepted; a mismatch is refused and opens a `wrong_patient` incident.
9. **Knowledge from approved documents only.** Answers quote approved Quality document versions and approved knowledge items, ranked by weighted word coverage (with plain English endings removed). A draft document is never used. If nothing matches the answer says so.
10. **Forecasts show their error.** The inventory forecast uses the weekly pattern of issues and dispensing and shows its range and how wrong the same method was on recent days. It orders nothing.
11. **Natural-language questions go through Analytics.** The query is shown; the result follows the user's own access rules, and small numbers may be hidden.
12. **Monitoring.** Acceptance rate and drift are computed from reviews and feedback; a sharp fall opens an incident. The red-team pack (`ai:redteam`) is part of the gate.

## Phase 20b: four functions above low risk
13. **Reuse, never duplicate.** The claim check calls `InsuranceClaimService::scrub` (read, never saved) and states only the rule messages; the medicine sheet restates Pharmacy's own safety alerts and finds no interactions itself; the reading order reads the Radiology tables and changes nothing; the vital signs come through `ObservationReader` (Clinical's one reader of every vitals store).
14. **A function above low risk is switched on only by two people and only after its own evaluation.** Each has a starter set (English and Bangla, with a hidden-instruction case) that the local model passes when it is set up; `enable()` refuses without a passing run of that function's own set. Setting up the local engine switches on low-risk functions only (before this it would have tried to switch on every built function).
15. **Facts, not advice.** Nothing says what to start, stop, order, assign, submit or do about a score. The forbidden-phrase list was extended (escalate, rapid response, submit/reject/deny the claim, assign the, change priority, discontinue) and the red-team pack grew with it. A free-text field carrying three or more instruction signs (a medicine name, a procedure name, a payer's query) refuses the request and opens an incident — now also when the request is refused, not only when it completes.
16. **The early-warning screen is NEWS2, SpO2 scale 1, written out in code and in the output.** Level of consciousness and supplemental oxygen are not stored with the vital signs, so they are never scored and the total says "at least". A vital older than 24 hours is not scored; fewer than three recent parameters means no screen. It is a screen, not a diagnosis; the output is a draft, accepted with the patient number typed, and starts no alert (alerts stay with Nursing/ICU).
17. **Radiology ranking rule:** stat, urgent, routine, then longest wait, shown with the reason; the radiologist decides. The list does not know the clinical question.
18. **A defect from Phase 20 fixed on the way:** the record read-back and note draft read vital signs only from `encounter_vitals`, which the OPD and ward flows no longer write; they now read `vital_signs` as well.

## Open for the hospital
- Who owns AI governance (a committee and its named lead), the real risk classification of each function, and the review window.
- Whether any outside provider will ever be used; if so, the legal opinion on data leaving the country and the contract.
- Native review of the Bangla wording (about 360 new strings (276 + 80), including the use-case names and the NEWS2 wording).
- Whether the NEWS2 screen is wanted at all, which wards would use it, and who answers a high band: the system only shows it. The same for the reading order (radiology lead) and the claim check (claims manager).
- Clinician-written evaluation cases for the four functions above low risk: the starter sets are examples that prove the guards, not clinical validation.
- The policy that clinicians must follow when an AI draft is wrong, and who reads the incident list.

## Deferred / known limitations
- **No embeddings or vector search**: retrieval is word coverage over approved documents. Good enough for short policies; a slice if the library grows.
- **No streaming** of answers.
- **Four functions are still planned and cannot be switched on:** laboratory interpretation, ICU early warning, emergency triage support, patient portal assistant. They need a real, evaluated model (rules can only restate facts), and the portal assistant needs injection tests on patient free text first.
- **No interaction checking of its own.** The medicine sheet shows only the alerts Pharmacy raised; a missing alert is not stated to mean safe.
- **NEWS2 is partial** (no consciousness, no extra oxygen, scale 1 only) until those are stored with the vitals by Nursing/ICU.
- **Streaming and vector search stay deferred** until a real outside provider exists.
- **The HTTP provider is a thin adapter** (no tools sent, JSON lines expected); no real outside service has been connected or evaluated.
- **Forecasts are advice only**; a link to purchase suggestions is a separate slice with Inventory/Procurement.
- The retrieval fixes made here (plain endings removed and the document title searched) were found on the six-month history, not on the small test fixtures.
