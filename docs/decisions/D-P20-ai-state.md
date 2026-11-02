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

## Open for the hospital
- Who owns AI governance (a committee and its named lead), the real risk classification of each function, and the review window.
- Whether any outside provider will ever be used; if so, the legal opinion on data leaving the country and the contract.
- Native review of the Bangla wording (about 280 new strings, including the use-case names).
- The policy that clinicians must follow when an AI draft is wrong, and who reads the incident list.

## Deferred / known limitations
- **No embeddings or vector search**: retrieval is word coverage over approved documents. Good enough for short policies; a slice if the library grows.
- **No streaming** of answers.
- **The eight planned functions are registered but not built** (medication review, lab interpretation, ICU early warning, ED triage support, radiology worklist, insurance claim pre-check, patient portal assistant, risk scores). Risk scores come last, with inputs and uncertainty shown.
- **The HTTP provider is a thin adapter** (no tools sent, JSON lines expected); no real outside service has been connected or evaluated.
- **Forecasts are advice only**; a link to purchase suggestions is a separate slice with Inventory/Procurement.
- The retrieval fixes made here (plain endings removed and the document title searched) were found on the six-month history, not on the small test fixtures.
