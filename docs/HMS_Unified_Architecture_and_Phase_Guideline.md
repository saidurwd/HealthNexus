# HealthNexus HMS — Unified Architecture Guideline & Phase-wise Development Plan

| | |
|---|---|
| **Document** | HMS-GUIDE-001 — the single master guideline |
| **Version** | 1.0 — 25 October 2026 |
| **Product** | HealthNexus Hospital Management System (HMS) for hospitals in Bangladesh |
| **Replaces** | `HMS_International_Standard_Master_Development_Specification.md`, `HMS_Laravel_SaaS_AI_Development_Specification.md`, and the phase list inside `Hospital Management System — Complete Feature List.md` (the feature list stays valid as a *what* list) |
| **Works together with** | The 20 detailed phase specifications (`Phase 0 …` to `Phase 20 …`). They stay as the *detailed spec* of each phase. This document decides *how everything fits together* and wins when the two disagree. |
| **Status of the project** | Phases 0–11 built. Phases 12–20 specified, not built. Five extra phases (H, 21–25) proposed here. |
| **Stack** | PHP 8.4+ · Laravel · MySQL · Redis · Blade + AdminLTE · REST API · Modular Monolith |

> **Honest note on scope.** This guideline was written from the documents only. I did not see your source code. Everything about phases 0–11 is therefore based on their specifications and on what you told me ("phases 0–11 are built"). Chapter 24 and chapter 25 give you an audit to confirm the code matches this guideline.

---

## TL;DR — read this first (2 minutes)

**Where you are.** You have a very strong set of 20 phase specifications. They share the same rules (Organization → Hospital → Branch tenancy, `module.resource.action` permissions, "Billing owns the money", "reuse, never duplicate", AI is advisory only). Phases 0–11 (Foundation → ICU) are built.

**What was missing to make it "international standard + easy for Bangladeshi users":**

1. **One set of final decisions.** The older master specs disagree with the phase specs on tenancy words (`company` vs `organization`), phase numbers, folder structure, database engine, API tokens, front-end library and permission style. Chapter 3 settles every one of them.
2. **Four shared contracts that stop duplication** before it spreads into Phases 12–20: one *Observation* (vital-signs) service, one *Alert* escalation service, one *Charge Event* contract to Billing, one *Stock Ledger* contract. Chapters 10–14.
3. **A Bangladesh pack**: BDT and VAT, Bangla language and Bangla PDF printing, NID/Birth-registration numbers, DGHS national FHIR profiles, the new data-protection law, DGDA drug registry, mobile payments, weekends, load-shedding. Chapter 18.
4. **A user-friendliness standard** for the people who will actually use the software: seven standard screen types, role-based home screens, patient banner, plain-language labels, print formats, training mode. Chapter 19.
5. **A Standardization Sprint (Phase H)** before you continue: a short, controlled clean-up so Phases 12–20 are built on the same rails as 0–11. Chapter 24.
6. **A phase-by-phase plan**: every remaining phase cut into small *slices* that an AI coding tool can finish and test safely. Chapters 23 and 26–27.
7. **Five phases the original plan does not have but a real Bangladeshi hospital needs**: Finance & Accounting (strongly recommended), Facility & Support Services, Quality & Compliance, Specialty Care & Telemedicine, and optional SaaS Administration. Chapter 27.

**What to do next (in this order):**

```text
1. Put this file in docs/ and add the short AI rules (Appendix E) to your repo's CLAUDE.md / AGENTS.md
2. Run Phase H (Standardization Sprint)  ← about 1–2 weeks of focused AI-assisted work
3. Start a pilot at one hospital with Phases 0–11 while you build on
4. Build Phase 12 (Emergency) → 13 (Blood Bank) → 14 (Insurance) → 15 (Inventory/Procurement)
   → 21 (Finance) → 16 (HR/Payroll) → 17 (Portal) → 18 (Interop) → 19 (BI) → 20 (AI)
5. Add 22–25 according to what your hospitals ask for
```

---

## How to use this document

| You are… | Read… |
|---|---|
| The owner / decision maker | TL;DR, chapters 1–3, 23, 27 |
| The person prompting an AI coding tool | Chapters 7–15 (the rules), chapter 22 (how to prompt), the phase chapter you are on (24–27), Appendix D (prompt pack) |
| A reviewer / tester | Chapters 10–16, 20, Appendix F (checklists) |
| A hospital IT / implementation lead | Chapters 18, 19, 21, 23 |

**Document precedence (when two documents disagree):**

```text
1. This guideline (HMS-GUIDE-001)                ← wins
2. The detailed phase specification of that phase
3. The Feature List (what to build, not how)
4. Older master specs (superseded — read for history only)
```

**Rule of the "two documents":** for every phase you give the AI **this guideline + that phase's own spec file**. This guideline tells the AI *how the whole system works*; the phase file tells it *what this module contains*. Never give the AI all 20 phase files at once.

---

## Contents

  - [TL;DR — read this first (2 minutes)](#tldr--read-this-first-2-minutes)
  - [How to use this document](#how-to-use-this-document)
- [PART A — ARCHITECTURE GUIDELINE](#part-a--architecture-guideline)
  - [1. Vision and Principles](#1-vision-and-principles)
  - [2. Where We Are](#2-where-we-are)
  - [3. Decision Log — conflicts found and the final decision](#3-decision-log--conflicts-found-and-the-final-decision)
  - [4. Big-Picture Architecture](#4-big-picture-architecture)
  - [5. Technology Standard](#5-technology-standard)
  - [6. Tenancy and Access Scope](#6-tenancy-and-access-scope)
  - [7. Module Anatomy and Code Structure](#7-module-anatomy-and-code-structure)
  - [8. Naming and Numbering Standards](#8-naming-and-numbering-standards)
  - [9. Data Standards](#9-data-standards)
  - [10. Clinical Safety and Record Integrity](#10-clinical-safety-and-record-integrity)
  - [11. Financial Integrity and the Billing Contract](#11-financial-integrity-and-the-billing-contract)
  - [12. Stock and Inventory Contract](#12-stock-and-inventory-contract)
  - [13. Cross-Module Integration](#13-cross-module-integration)
  - [14. Shared Platform Services](#14-shared-platform-services)
  - [15. API Standard](#15-api-standard)
  - [16. Security and Privacy Standard](#16-security-and-privacy-standard)
  - [17. Interoperability and Coding Standards](#17-interoperability-and-coding-standards)
  - [18. Bangladesh Localization and Compliance Pack](#18-bangladesh-localization-and-compliance-pack)
  - [19. UX Standard — "Easy for the busiest person in the building"](#19-ux-standard--easy-for-the-busiest-person-in-the-building)
  - [20. Quality Engineering](#20-quality-engineering)
  - [21. DevOps, Operations and Go-Live](#21-devops-operations-and-go-live)
  - [22. AI-Assisted Development Protocol](#22-ai-assisted-development-protocol)
- [PART B — PHASE-WISE DEVELOPMENT PLAN](#part-b--phase-wise-development-plan)
  - [23. Roadmap and Sequencing](#23-roadmap-and-sequencing)
  - [24. Phase H — Standardization Sprint and Pilot Readiness](#24-phase-h--standardization-sprint-and-pilot-readiness)
  - [25. Conformance Audit of Phases 0–11](#25-conformance-audit-of-phases-011)
  - [26. Phases 12–20: Slice Plans](#26-phases-1220-slice-plans)
  - [27. New Phases 21–25 (proposed)](#27-new-phases-2125-proposed)
  - [28. Governance: Gates, Risks, Decisions and Progress](#28-governance-gates-risks-decisions-and-progress)
- [PART C — APPENDICES](#part-c--appendices)
  - [Appendix A — Document Map, File Naming and Repository Layout](#appendix-a--document-map-file-naming-and-repository-layout)
  - [Appendix B — Data Ownership Matrix](#appendix-b--data-ownership-matrix)
  - [Appendix C — Role Catalogue and Permission Pattern](#appendix-c--role-catalogue-and-permission-pattern)
  - [Appendix D — Prompt Pack for the AI Developer](#appendix-d--prompt-pack-for-the-ai-developer)
  - [Appendix E — `CLAUDE.md` / `AGENTS.md` Rules (copy to the repository root)](#appendix-e--claudemd--agentsmd-rules-copy-to-the-repository-root)
  - [Appendix F — Checklists](#appendix-f--checklists)
  - [Appendix G — Plain-Language Glossary (English → Bangla UI wording)](#appendix-g--plain-language-glossary-english--bangla-ui-wording)
  - [Appendix H — Sources and "Verify Before Relying" List](#appendix-h--sources-and-verify-before-relying-list)

---

# PART A — ARCHITECTURE GUIDELINE

---

## 1. Vision and Principles

### 1.1 Vision

HealthNexus is a complete, secure, bilingual (Bangla + English) Hospital Management System for Bangladeshi hospitals — from a 30-bed clinic to a multi-branch hospital group — built to international interoperability and safety practice, and ready to connect to the national health information systems.

It must serve the **whole patient journey**:

```text
Register → Appoint → Consult → Order tests → Diagnose → Prescribe → Pay
        → Admit → Nurse → Operate → Intensive care → Emergency → Transfuse
        → Claim insurance → Discharge → Follow up → Analyse → Learn
```

### 1.2 Who it is for (the four sizes we design for)

| Size | Typical Bangladeshi setting | Modules needed | Deployment |
|---|---|---|---|
| **S** | Diagnostic centre / small clinic (OPD, lab, imaging, pharmacy) | Phases 0–7 | One server or small cloud VM |
| **M** | 50–150-bed private hospital | S + 8–12, 15, 21 (+13 if own blood bank) | On-premises server with LAN, nightly off-site backup |
| **L** | 250+ bed or multi-hospital group | Everything | Private cloud / data centre, hot standby |
| **XL** | Group selling the software to other hospitals (SaaS) | Everything + Phase 25 | Hosted, multi-organization |

Every module can be **switched on or off per organization** (chapter 14, "Module enablement"). A small clinic never sees ICU menus.

### 1.3 The twelve principles (plain-language)

| # | Principle | What it means in practice |
|---|---|---|
| 1 | **One patient, one record** | There is exactly one Master Patient Index. No module creates its own patient table. |
| 2 | **Every fact has one owner** | Each piece of data belongs to one module. Others read it through a contract; they never write it. (Ownership matrix: Appendix B) |
| 3 | **Billing owns money** | Clinical modules only *announce a chargeable event*. Only Billing creates invoices, payments, refunds. |
| 4 | **Never delete, always amend** | Clinical and financial records are corrected by amendment, reversal or credit note — never by overwriting or deleting. |
| 5 | **Scope is enforced by the server** | The server decides which hospital/branch/department data a user may see. The browser is never trusted. |
| 6 | **Safe by default** | Least-privilege access, full audit trail, no sensitive data in logs or URLs. |
| 7 | **Humans decide, AI assists** | AI can draft and suggest. It can never diagnose, prescribe, order, discharge or change a record by itself. |
| 8 | **Reuse before you build** | Before writing code the AI must find and reuse what exists. A second "mini-billing" or "mini-patient" inside a module is a defect. |
| 9 | **Small slices, always tested** | Work is cut into slices that finish and pass tests on their own. |
| 10 | **Bilingual and local from day one** | Bangla UI, Bangla print, BDT, VAT, Dhaka time, local ID formats are configuration, not afterthoughts. |
| 11 | **Easy for the busy nurse at 3 a.m.** | Few clicks, big clear status, no jargon, safe confirmations, works on a 1366×768 screen. |
| 12 | **Standards at the edges** | Inside, we use our own clean model. At the boundary we speak FHIR, HL7, DICOM and national code systems through adapters. |

---

## 2. Where We Are

### 2.1 Phase status

| Phase | Module | Status | Spec file prefix |
|---|---|---|---|
| 0 | Foundation | **Built** | `Phase 0 —` |
| 1 | Patient Core / MPI | **Built** | `Phase 1 —` |
| 2 | Appointment & Scheduling | **Built** | `Phase 2 —` |
| 3 | OPD / Clinical Encounter / EMR | **Built** | `Phase 3 —` |
| 4 | Billing & Revenue | **Built** | `Phase 4 —` |
| 5 | Laboratory (LIS) | **Built** | `Phase 5 —` |
| 6 | Radiology (RIS/PACS) | **Built** | `Phase 6 —` |
| 7 | Pharmacy & Medication | **Built** | `Phase 7 —` |
| 8 | IPD / Admission / Beds | **Built** | `Phase 8 —` |
| 9 | Nursing & MAR | **Built** | `Phase 9 —` |
| 10 | OT / Surgery | **Built** | `Phase 10 —` |
| 11 | ICU / Critical Care | **Built** | `Phase 11 —` |
| 12 | Emergency Department | Specified | `Phase 12 —` |
| 13 | Blood Bank & Transfusion | Specified | `Phase 13 —` |
| 14 | Insurance & Corporate Claims | Specified | `Phase 14 —` |
| 15 | Inventory / Procurement / Supply Chain | Specified | `Phase 15 —` |
| 16 | HR / Workforce / Payroll | Specified | `Phase 16 —` |
| 17 | Patient Portal / Mobile / CRM | Specified | `Phase 17 —` |
| 18 | Interoperability (FHIR/HL7/DICOM) | Specified | `Phase 18 —` |
| 19 | BI / Analytics / Data Warehouse | Specified | `Phase 19 —` |
| 20 | AI / Clinical Intelligence | Specified | `Phase 20 —` |
| H | Standardization Sprint & Pilot Readiness | **New (this guideline)** | chapter 24 |
| 21 | Finance & Accounting | **New — strongly recommended** | chapter 27 |
| 22 | Facility & Support Services | New — recommended | chapter 27 |
| 23 | Quality, Safety & Compliance | New — recommended | chapter 27 |
| 24 | Specialty Care & Telemedicine | New — optional | chapter 27 |
| 25 | Platform & SaaS Administration | New — optional (only if you sell hosted to many hospitals) | chapter 27 |

### 2.2 What the document set looks like (and the problems in it)

You have 31 files. Problems found:

1. **Two numbering systems.** The master spec lists its own phases (e.g. "Phase 3 — Billing", "Phase 8 — Emergency"). The phase files use 0–20. **The 0–20 numbering is final.**
2. **An empty file:** `Phase 6 Radiology : PACS.md` (0 bytes). It is a leftover; the real Phase 6 spec is the long Radiology file.
3. **Phase 2 is named "Appointment + OPD"** in one file but only contains Appointment; OPD is Phase 3.
4. **File names contain `:` and `—`**, which break on Windows, Git on some systems, and some AI tools. Recommended naming is in Appendix A.
5. **Specs for phases 0–5 are split** into "spec" and "AI prompt"; phases 6–20 are combined into one file. Both styles work; Appendix A tells you which file to give the AI.
6. **Most phase files are 40–60 KB** with a lot of repeated boilerplate ("Never duplicate Patient/MPI…"). That repetition is now captured *once* in this guideline (chapters 7–16), so the AI context can be smaller.

---

## 3. Decision Log — conflicts found and the final decision

> These decisions are **final**. If an older document says otherwise, this table wins. "Impact" tells you if existing code (Phases 0–11) probably needs a small change. Items marked **verify** must be checked against your repository (see chapter 24).

| ID | Topic | Conflict found | **Final decision** | Impact on existing code |
|---|---|---|---|---|
| D-01 | **Phase numbering** | Master spec: 18 phases in a different order. Phase files: 0–20. | Phase files **0–20 are authoritative**. Extra phases are H, 21–25. | None |
| D-02 | **Tenancy words** | SaaS spec: `Company → Branch(hospital)`, `company_id`. Phase 0: `Organization → Hospital → Branch → Department`. | **Organization → Hospital → Branch → Department.** *Organization* = the customer (tenant). *Hospital* = a licensed facility. *Branch* = a physical site/campus. A one-hospital customer has 1 organization, 1 hospital, 1 branch ("Main"). Column name is always `organization_id`, never `company_id`. | None if Phase 0 followed its spec — **verify** |
| D-03 | **Code location** | Master spec: `app/Domain/…`. SaaS spec: `app/Modules/…`. | **Use whichever root your Phase 0–11 code already uses** and never mix. Inside a module, the anatomy in chapter 7 is mandatory. In this document `<ModuleRoot>` means that root. | **Verify** |
| D-04 | **Database** | Master: "MySQL". Phase prompts: "MySQL/PostgreSQL". | **MySQL 8.x (current LTS) is the one supported engine.** Keep SQL portable, but test on MySQL only. Heavy analytics goes to the Phase 19 warehouse/replica, not to a second OLTP engine. | None |
| D-05 | **API authentication** | Phase 0 left "Sanctum or Passport" open. | **Laravel Sanctum**: session cookie for the web UI, personal-access tokens for mobile and integrations. Passport/OAuth2 is added **only if** Phase 18 must act as an OAuth server for third-party clients. Never run both for the same purpose. | **Verify** which one Phase 0 installed; keep it |
| D-06 | **Permission naming** | Master: `patient.view`. SaaS: `patients.view`, `lab.orders.create`. Phase 0 and later: `module.resource.action`. Phase 5: `lab.critical_result.view`. | **`module.resource.action`**, lowercase, singular resource, snake_case inside a segment, max 3 segments. Existing 2-segment names (`patient.view`, `appointment.create`) stay, registered as legacy aliases. Plural forms are never used. | Create the **Permission Registry** (H-01) |
| D-07 | **API shape** | Consistent already (`{success,message,data,meta}`). Phase 18 puts FHIR under `/api/v1/fhir/*` and says FHIR must return native FHIR responses. | Keep the envelope for all business APIs under `/api/v1/<module>`. **Exceptions (Phase 18 only):** `/api/v1/fhir/*` returns native FHIR JSON (`OperationOutcome` for errors) and `/api/v1/hl7/*` returns HL7 ACK/NACK. Nothing else is exempt. | None |
| D-08 | **Vital signs / observations** | Phase 3 `encounter_vitals`, Phase 9 `nursing_vitals`/`nursing_observations`, Phase 11 `icu_vital_signs`/`icu_observations`, Phase 12 `ed_monitoring_records` + triage vitals. Four stores for one concept. | **One Observation Service** is the only way to record or read vital signs, scores, I/O measurements. Modules keep their context tables (e.g. `icu_flowsheets`) but measurements flow through the service. Details: chapter 10.4. | H-03 (read-model first, no big-bang rewrite) |
| D-09 | **Critical alerts** | Separate alert tables in Nursing, ICU, ED, Lab (critical value), Radiology (critical finding). | One **Alert Service** provides raise → deliver → acknowledge → escalate → close. Modules keep their own alert *types* and may keep their tables, but acknowledgement and escalation run through the service. Chapter 10.5. | H-04 |
| D-10 | **Chargeable events** | Each module has its own `*_chargeable_events` table with different key styles. | One **Charge Event Contract** with one idempotency-key format. Chapter 11. | H-05 |
| D-11 | **Stock** | Phase 7 `pharmacy_stock`, Phase 15 `inventory_stock`, Phase 13 `blood_units`. | Three stores are allowed (pharmacy dispensing, enterprise stores, blood units) **but all post to one Stock Ledger Contract** and Phase 15 provides consolidated stock reports. Blood units are never ordinary stock. Chapter 12. | H-06 |
| D-12 | **Providers vs employees** | Phase 2 creates `providers`; Phase 16 creates `hr_employees`. | A provider is *a role a person plays*; an employee is *a person employed*. `providers.employee_id` links them. Phase 16 back-fills the link; HR is the master for name, licence, status. Chapter 13.5. | Plan in Phase 16 |
| D-13 | **Forward integrations** | Earlier phases mention later modules (Nursing→Blood Bank, ED→Blood Bank, Phase 14↔Phase 21). | **Interface + null adapter**: define the contract now, ship a "not enabled" adapter, swap in the real one when the module is built. Chapter 13.4. | Small |
| D-14 | **Money and time** | Amounts appear as `DECIMAL(18,2)` and `DECIMAL(18,4)`. Time zones loosely described. | Money totals `DECIMAL(18,2)`; unit prices, rates, quantities, costs `DECIMAL(18,4)`; default currency BDT; every money header has `currency_id`. Store UTC; show `Asia/Dhaka` by default. Chapter 9. | **Verify** |
| D-15 | **Front-end** | Master: Blade + AdminLTE + Bootstrap. SaaS spec: Blade + Tailwind + SPA modules. | **Blade + AdminLTE (Bootstrap) + Alpine.js.** Vue "islands" only for heavy real-time boards (ED tracking board, bed board, OT scheduler, ICU flowsheet). **No Tailwind, no full SPA** — one design system only. | None |
| D-16 | **Queue / messaging** | SaaS spec suggests RabbitMQ and Kafka. | **Redis + Laravel Horizon.** RabbitMQ is considered only if Phase 18 measurements show Redis is not enough. Kafka is out of scope. | None |
| D-17 | **Search** | SaaS spec: Elasticsearch/OpenSearch "later". | **MySQL indexed search first.** Add Meilisearch/OpenSearch only after a measured slow query (patient search > 500 ms at your real data size). | None |
| D-18 | **Clinical coding** | Phase docs mention ICD-10 and ICD-11, LOINC, SNOMED CT, ATC. | Store `code_system` + `code` + `display` for every coded field. Support **ICD-10 and ICD-11 side by side** (national FHIR profiles use ICD-11 MMS; most Bangladeshi practice and reports still use ICD-10). LOINC for lab/observations, ATC + DGDA registry for drugs, UCUM for units, SNOMED CT optional. Chapter 17. | **Verify** Phase 3 diagnosis table has `code_system` |
| D-19 | **Languages** | Phase 0: English, Bangla, Arabic. | **English + Bangla required for v1** (UI, SMS, print). Arabic deferred; but use RTL-safe CSS (logical properties) so adding it later is cheap. | Bangla lang files (H-10) |
| D-20 | **SaaS features** | SaaS spec has plans, subscriptions, usage metering. | Not core. Organization-level **module flags** are in Foundation. Full subscription/commercial tooling is Phase 25 (optional). | None |
| D-21 | **Reporting before BI** | Master spec wants a reusable reporting service; Phase 19 builds BI. | Until Phase 19, every module's operational reports use the **shared Report/Export Service** (chapter 14). No cross-module heavy analytics on the live database. | H-07 |
| D-22 | **Document file naming** | Colons, dashes, mixed styles. | `NN-name.md` style, no special characters (Appendix A). | Rename later (optional) |

---

## 4. Big-Picture Architecture

### 4.1 The layers

```text
┌──────────────────────────────────────────────────────────────────────┐
│  USERS & CHANNELS                                                    │
│  Reception · Doctor · Nurse · Lab · Radiology · Pharmacy · Cashier   │
│  Management · Patient Portal · Mobile App · External Systems         │
└───────────────────────────────┬──────────────────────────────────────┘
                                │  Blade/AdminLTE UI  ·  /api/v1  ·  /api/v1/fhir
┌───────────────────────────────▼──────────────────────────────────────┐
│  DOMAIN MODULES (modular monolith — one codebase, strict boundaries) │
│                                                                      │
│  Clinical:   Patient · Appointment · OPD/EMR · IPD · Nursing ·       │
│              OT · ICU · Emergency · Blood Bank                       │
│  Diagnostic: Laboratory · Radiology                                  │
│  Pharmacy & Supply: Pharmacy · Inventory/Procurement                 │
│  Business:   Billing · Insurance/Corporate · Finance · HR/Payroll    │
│  Experience: Patient Portal/CRM                                      │
│  Intelligence: BI/Warehouse · AI                                     │
│  Extensions: Facility · Quality · Specialty · SaaS Admin             │
└───────────────────────────────┬──────────────────────────────────────┘
                                │  Contracts (interfaces) + Domain Events
┌───────────────────────────────▼──────────────────────────────────────┐
│  SHARED PLATFORM SERVICES (Phase 0 + Phase H)                        │
│  Auth · RBAC/Scope · Audit · Settings · Master Data · Workflow ·     │
│  Notifications · Files · Numbering · Observation · Alerts ·          │
│  Charge-event publisher · Stock-ledger contract · Documents/Print ·  │
│  Reports/Export · Search · Barcode · Import                          │
└───────────────────────────────┬──────────────────────────────────────┘
                                │
┌───────────────────────────────▼──────────────────────────────────────┐
│  INTEGRATION LAYER (Phase 18) — adapters only, no business logic     │
│  FHIR R4 · HL7 v2 · DICOM/DICOMweb · SMS · Email · WhatsApp ·        │
│  Payment gateways (cards, bKash, Nagad…) · Biometric · Analyzers ·   │
│  PACS · Payer APIs · National systems (DGHS)                         │
└───────────────────────────────┬──────────────────────────────────────┘
                                │
┌───────────────────────────────▼──────────────────────────────────────┐
│  DATA: MySQL (OLTP) · Redis (cache/queue) · Object storage (files)   │
│        Reporting replica / Warehouse (Phase 19) · Search (if needed) │
└──────────────────────────────────────────────────────────────────────┘
```

### 4.2 Module groups and the allowed direction of dependency

```text
Platform (Phase 0, H)
     ▲
Patient (1) ◄── Appointment (2) ◄── OPD/EMR (3) ◄── all clinical modules
     ▲                                   ▲
Billing (4) ◄───────── chargeable events from every service-delivering module
     ▲
Insurance (14) · Finance (21) ◄── Inventory (15) · HR (16)
Portal (17) · Interop (18) · BI (19) · AI (20)  ← read from everything, own nothing clinical
```

**Dependency rules** (the AI must obey, reviewers must check):

1. A module may depend on **lower** modules (to the left/top above), never the reverse. Example: Billing never imports Laboratory classes; Laboratory *publishes an event* and Billing *listens*.
2. **Cross-module calls** use only (a) the other module's `Contracts/` interface, or (b) a domain event. Never another module's Eloquent model for writing, never its tables in raw SQL.
3. **Cross-module reads** for screens (e.g. "show lab results in the encounter") use the owner module's `Queries/` class or API resource.
4. **No circular dependency.** If two modules need each other, one of them publishes an event instead.
5. **Later phases (17–20) are read-mostly.** They never become a second source of truth.

### 4.3 Module map (21 domain modules + platform)

| # | Module | Phase | One-line responsibility |
|---|---|---|---|
| — | Core (Foundation) | 0 | Org structure, users, RBAC, settings, audit, files, workflow, notifications |
| 1 | Patient / MPI | 1 | Identity, MRN, demographics, consents, duplicates, timeline |
| 2 | Appointment | 2 | Schedules, slots, bookings, queue tokens, reminders |
| 3 | OPD / EMR | 3 | Encounters, notes, diagnoses, orders, prescriptions |
| 4 | Billing | 4 | Price lists, charges, invoices, payments, refunds, cashier |
| 5 | Laboratory | 5 | Orders, specimens, results, validation, critical values |
| 6 | Radiology | 6 | Imaging orders, scheduling, DICOM/PACS links, reports |
| 7 | Pharmacy | 7 | Drug master, dispensing, pharmacy stock, safety checks |
| 8 | IPD | 8 | Admission, beds, transfers, discharge |
| 9 | Nursing | 9 | Assessments, care plans, tasks, MAR, handover |
| 10 | OT | 10 | Surgery requests, scheduling, WHO checklist, operative notes |
| 11 | ICU | 11 | ICU episodes, flowsheets, ventilators, scores |
| 12 | Emergency | 12 | Triage, ED queue, resuscitation, disposition |
| 13 | Blood Bank | 13 | Donors, units, crossmatch, issue, transfusion, reactions |
| 14 | Insurance / Corporate | 14 | Payers, policies, pre-auth, claims, settlement |
| 15 | Inventory / Procurement | 15 | Items, suppliers, PO, GRN, stock, transfers |
| 16 | HR / Payroll | 16 | Employees, attendance, leave, payroll |
| 17 | Portal / CRM | 17 | Patient web/mobile access, messaging, campaigns |
| 18 | Interoperability | 18 | FHIR/HL7/DICOM, routing, mapping, monitoring |
| 19 | BI / Warehouse | 19 | Facts/dimensions, KPIs, dashboards |
| 20 | AI | 20 | Gateway, policies, summaries, risk models |
| 21 | Finance | 21 | Chart of accounts, GL, AR/AP, bank, budget, VAT |
| 22–25 | Facility, Quality, Specialty, SaaS Admin | 22–25 | See chapter 27 |

---

## 5. Technology Standard

| Layer | Standard | Notes |
|---|---|---|
| Language | **PHP 8.4+**, `declare(strict_types=1)` in new files | Typed properties and return types everywhere |
| Framework | **Laravel** (current supported stable release; upgrade within one minor of release) | Do not upgrade the framework in the middle of a phase |
| Database | **MySQL 8.x LTS**, InnoDB, `utf8mb4` | Foreign keys on. Collation `utf8mb4_0900_ai_ci` or equivalent so Bangla sorts and searches correctly |
| Cache / queue / lock | **Redis** + **Laravel Horizon** | Separate Redis databases for cache, queue, sessions |
| Web server | Nginx + PHP-FPM, Supervisor for workers, cron for the scheduler | Docker images for dev and production parity |
| Front-end | **Blade + AdminLTE + Bootstrap + Alpine.js** | Vue "islands" for boards only (D-15). Bangla web font: Noto Sans Bengali (self-hosted, no CDN at runtime) |
| API auth | **Sanctum** (D-05) | Rate limiting per user and per IP |
| RBAC | `spatie/laravel-permission` or the Phase 0 equivalent **already installed** | Do not install a second RBAC package |
| PDF / print | Chromium-based rendering (Browsershot or Gotenberg) **or** a library proven to render Bangla conjunct letters | **Test Bangla conjuncts on the first receipt template before standardising** (see 14.6) |
| Files | S3-compatible object storage; MinIO on-premises | Private by default; signed temporary URLs |
| Tests | **Pest or PHPUnit** (whichever Phase 0 installed), Laravel feature tests, Playwright or Dusk for critical end-to-end flows | |
| Quality tools | **Laravel Pint** (PSR-12), **Larastan/PHPStan** (level 6 minimum for new code), `composer audit`, `npm audit` | CI must fail on errors |
| Observability | Laravel logs (JSON) + Sentry-compatible error tracking + Prometheus/Grafana or equivalent; correlation ID on every request/job | |
| Packages policy | Add a package only if it saves real work, is maintained, and is licensed compatibly; record it in `docs/decisions/packages.md` | The AI must ask before adding a dependency |

**Version policy.** Pin exact versions in `composer.lock`/`package-lock.json`. Review security advisories monthly and before each release.

---

## 6. Tenancy and Access Scope

### 6.1 The hierarchy

```text
Organization            ← the customer (tenant). Owns the data.
 └── Hospital           ← a licensed facility (one DGHS licence)
      └── Branch        ← a physical site / campus
           └── Department (can nest: Clinical → Medicine → Cardiology)
```

* A small clinic: 1 organization · 1 hospital · 1 branch ("Main").
* A group with two hospitals and a diagnostic centre: 1 organization · 3 hospitals.
* Never use the words *company* or *tenant* in code. The column is `organization_id`.

### 6.2 Five table classes (decide the class before writing a migration)

| Class | Scope columns | Examples | Rule |
|---|---|---|---|
| **P — Platform/global** | none | countries, currencies, ICD/LOINC code tables, permission definitions | Read-only for tenants; changed only by seeders/platform admin |
| **O — Organization-wide** | `organization_id` | patients (MPI), users, roles, suppliers, drug master, service catalogue, payers | Shared by all hospitals of the organization |
| **H — Hospital-level** | `organization_id`, `hospital_id` | price lists, formulary choices, hospital settings, numbering series | Differs per hospital |
| **B — Branch transactional** | `organization_id`, `hospital_id`, `branch_id` (+ `department_id` if the row belongs to a department) | appointments, encounters, invoices, admissions, lab orders, stock movements | Volume tables. Scope columns are **denormalised on purpose** so list screens and reports can filter and index without joins |
| **C — Child rows** | none (reach the scope through the parent FK) | invoice items, prescription items, result values | Add scope columns only when the child is queried directly |

> Do not blindly add all columns to every table (master spec §24). Choose the class, then add exactly its columns.

### 6.3 How scope is enforced (all seven layers are required)

```text
Request
  1. Authenticate (Sanctum session/token)
  2. ScopeContext middleware → resolves the user's *active* hospital/branch (one at a time)
  3. Route middleware: permission check (module.resource.action)
  4. FormRequest: validates input; never trusts organization_id / hospital_id / branch_id from the client
  5. Policy: "can this user touch THIS record?" (scope + permission + patient-level rules)
  6. Eloquent global scope (trait HasOrganizationScope / HasBranchScope): every query is auto-filtered
  7. Action/Service: sets scope columns itself from ScopeContext when creating rows
```

Extra rules:

* **Raw SQL, report queries and exports** must go through `ScopeResolver` (returns the allowed hospital/branch ids). No unscoped `DB::table()` in application code.
* **Queued jobs, listeners and scheduled tasks** carry `organization_id` (and hospital/branch if relevant) in their payload and **re-establish** the scope. A job with no scope must refuse to run.
* **Cache keys, file paths, search indexes and broadcast channels** include the scope: `org:{id}:hosp:{id}:…`.
* **Route model binding** uses the scoped query, so `/patients/123` of another hospital returns **404**, not 403 (do not confirm that the record exists).
* **Menu hiding is not security.** Every route, controller action, API endpoint and export checks permission and scope.

### 6.4 Access levels for a user

```text
Organization-wide  >  Hospital-level  >  Branch-level  >  Department-level
```

A user may hold several grants (e.g. "Dr. Rahman: Hospital A / Branch A1 / Cardiology"). The active context is chosen at login (a visible **context switcher** in the top bar). Records outside the grants are invisible.

### 6.5 Record-sharing mode (important for hospital groups)

Patient *identity* (MPI) is organization-wide. Whether the *clinical* record is shared between the hospitals of one organization is a setting:

| Mode | Meaning | Use when |
|---|---|---|
| `isolated` (**default**) | Hospital B cannot open Hospital A's clinical data. Only identity is shared (to avoid duplicate patients). | Hospitals are separate legal entities or privacy-sensitive |
| `shared_within_organization` | Staff with the right permission can open the patient's clinical summary across hospitals of the same organization, every access is logged | One legal group, referral between own hospitals |

Under both modes: **organization A can never see organization B.**

### 6.6 Patient-level restrictions

* `confidential`/VIP patients: visible only to staff with `patient.confidential.view` or via break-glass (10.8).
* Staff who are also patients ("employee patient"): their record is automatically `confidential`.
* Minors and dependants: guardian rules from Phase 1.

### 6.7 Mandatory scope tests (every module, every phase)

```text
T-S1  User of Hospital A requests a Hospital B record by URL            → 404
T-S2  Same via API id manipulation                                       → 404
T-S3  Same via search, list, export, report, print, notification link    → no leakage
T-S4  User of Branch A without grant requests Branch B                    → 404
T-S5  Role without permission calls the endpoint                          → 403
T-S6  POST with a forged hospital_id / branch_id in the body              → ignored or 422
T-S7  Queued job without scope                                            → refuses
T-S8  Organization A user requests Organization B anything                → 404
```

---

## 7. Module Anatomy and Code Structure

### 7.1 Layout of one module

```text
<ModuleRoot>/<Module>/            e.g. Emergency, BloodBank
├── README.md                     ← Module Charter (template in 7.4) — mandatory
├── Actions/                      ← one use case = one class (AdmitPatient, IssueBloodUnit)
├── Services/                     ← domain logic used by several actions
├── Models/                       ← Eloquent models (relationships, casts, scopes only)
├── Enums/                        ← backed enums for statuses/types (string-backed)
├── DTOs/                         ← immutable data carriers between layers
├── Contracts/                    ← interfaces OTHER modules may call (the public face)
├── Queries/                      ← read models for screens, reports, other modules
├── Events/  Listeners/  Jobs/  Notifications/
├── Policies/                     ← authorisation (permission + scope + rules)
├── Http/
│   ├── Controllers/Web/          ← Blade screens (thin)
│   ├── Controllers/Api/          ← JSON (thin)
│   ├── Requests/                 ← FormRequests = validation
│   └── Resources/                ← API output shape
├── Database/
│   ├── Migrations/  Seeders/  Factories/
├── Resources/
│   ├── views/                    ← Blade (namespaced: emergency::triage.index)
│   └── lang/en  lang/bn          ← all labels, both languages
├── Routes/web.php  Routes/api.php
├── Config/<module>.php           ← module settings defaults (never secrets)
└── Tests/Unit  Tests/Feature  Tests/Security  Tests/Integration
```

### 7.2 Layer rules (who may call whom)

```text
Route → Middleware → Controller → FormRequest → Action → Service → Model
                                         └→ Policy        └→ Event (after commit)
```

| Layer | Allowed | Forbidden |
|---|---|---|
| Controller | Authorise, call **one** Action, return a view/resource | Business rules, queries beyond fetching a single page, transactions |
| FormRequest | Validate shape and simple rules | Database writes |
| Action | One use case; opens the **transaction**; calls services; dispatches events | UI code |
| Service | Reusable domain logic, calculations, state machines | HTTP types (`Request`) |
| Model | Relationships, casts, scopes, simple accessors | Cross-module writes, heavy logic |
| Blade | Display only | Queries, business rules |
| Listener/Job | React to events; idempotent; re-establish scope | Assuming order or exactly-once delivery |

### 7.3 The standard Action (copy this pattern)

```php
final class IssueBloodUnit
{
    public function __construct(
        private readonly BloodUnitRepository $units,
        private readonly CrossmatchGuard $crossmatch,
    ) {}

    public function handle(IssueBloodUnitData $data, User $actor): BloodIssue
    {
        return DB::transaction(function () use ($data, $actor) {
            $unit = $this->units->lockForIssue($data->unitId, $actor);   // lockForUpdate, scoped
            $this->crossmatch->assertCompatible($unit, $data->requestId); // domain rule, throws
            $issue = BloodIssue::create([...]);                           // scope set by trait
            $unit->markIssued($issue);                                    // state machine
            BloodUnitIssued::dispatch($issue->id, correlationId());       // after commit
            return $issue;
        });
    }
}
```

Rules shown: transaction in the Action · row lock before changing shared state · domain rule throws a **domain exception** (`ExpiredBatchException`, `IncompatibleBloodException`) · event carries **ids**, not models · event is dispatched **after commit** (`ShouldDispatchAfterCommit`).

### 7.4 Module Charter (the README every module must have)

```markdown
# <Module> — Module Charter
- Phase / owner:
- Purpose (2 lines):
- Owns (entities):                Does NOT own:
- Table prefix:                   Permission prefix:        API base:
- Depends on (contracts used):
- Events published:               Events consumed:
- Public contracts (Contracts/):
- Status machines (diagram or list):
- Settings keys:
- Reports / KPIs:
- Known limitations & deferred items:
```

The AI **updates the charter in every slice**. It is how the next phase learns what exists.

### 7.5 Reuse protocol (the rule behind most defects)

Before creating any table, model, service or screen the AI must answer in writing:

1. "Which existing module owns this concept?" (look at Appendix B and the code)
2. "What is its public contract or event?"
3. "Can I use it unchanged? If not, what is the smallest extension?"

If an equivalent exists, **extend or call it**. A new table that duplicates a concept (a second patient, a second prescription, a second invoice, a second observation store, a second bed) is a **blocking review finding**.

---

## 8. Naming and Numbering Standards

### 8.1 Naming rules

| Thing | Rule | Example |
|---|---|---|
| Table | `snake_case`, plural noun, **module prefix** (registry below) | `ed_triage_records`, `icu_flowsheets` |
| Column | `snake_case`, singular; FK = `<entity>_id` | `patient_id`, `triaged_by` |
| Booleans | `is_`/`has_` prefix | `is_active`, `has_allergy_alert` |
| Timestamps | `<verb>_at` | `arrived_at`, `finalized_at` |
| Actor columns | `<verb>_by` (user id) | `created_by`, `verified_by` |
| Status | column `status`, string, lowercase snake_case values from an Enum | `waiting`, `in_treatment` |
| Index | `idx_<table>_<cols>` ; unique `uq_<table>_<cols>` ; FK `fk_<table>_<col>` | `uq_ed_arrivals_hospital_number` |
| Model | Singular PascalCase | `TriageRecord` |
| Enum | `<Thing>Status`, `<Thing>Type` | `TriageLevel` |
| Permission | `module.resource.action` | `ed.triage.reassess` |
| Event | PascalCase, past tense, `<Entity><Verb>` | `TriageCompleted`, `BloodUnitIssued` |
| Job | Imperative PascalCase | `EscalateUnacknowledgedAlerts` |
| Route (web) | `<module>.<resource>.<action>` | `ed.triage.index` |
| API | `/api/v1/<module-path>/<resource>` , kebab-case plural | `/api/v1/emergency/triage` |
| JSON fields | `snake_case` | `arrival_mode` |
| Lang key | `<module>::<area>.<key>` | `ed::triage.level.immediate` |
| Migration | `YYYY_MM_DD_HHMMSS_<module>_<verb>_<table>` | `2026_11_02_090000_ed_create_triage_records_table` |
| Branch (git) | `feature/p12-s03-triage` | phase 12, slice 03 |
| Commit | Conventional Commits with scope | `feat(emergency): add triage reassessment` |

**Standard actions** in permissions: `view`, `create`, `update`, `delete` (rare), `approve`, `reject`, `cancel`, `finalize`, `amend`, `export`, `print`, `import`, `manage`, `configure`, `override`, `acknowledge`. Add a new verb only if none fits.

**Event naming note.** Existing specs use short module prefixes on some events (`EDPatientArrived`, `ICUChargeableEventCreated`) and none on others (`DonorRegistered`). Both exist. Rule going forward: use `<Entity><Verb>` (no prefix) **unless** the plain name could collide with another module (then prefix with the module). Namespaces keep events apart.

### 8.2 Module Registry (single source of truth for prefixes)

| Module | Phase | Permission prefix | Table prefix | API base `/api/v1/…` | Notes |
|---|---|---|---|---|---|
| Core | 0 | `core` | *(none)* | `auth, organizations, hospitals, branches, departments, users, roles, permissions, settings, notifications` | |
| Patient | 1 | `patient` | `patients`, `patient_*` | `patients` | 2-segment legacy permissions kept as aliases |
| Appointment | 2 | `appointment`, `provider` | `appointment_*`, `providers`, `schedule_*`, `clinic_sessions` | `appointments`, `providers` | |
| OPD / EMR | 3 | `clinical`, `encounter` | `encounters`, `encounter_*`, `clinical_*`, `prescriptions`, `prescription_items`, `patient_problems` | `encounters` | Owns prescription and clinical order |
| Billing | 4 | `billing` | `billing_*` | `billing` | |
| Laboratory | 5 | `lab` | `lab_*` | `lab` | |
| Radiology | 6 | `radiology` | `radiology_*` | `radiology` | |
| Pharmacy | 7 | `pharmacy` | `pharmacy_*`, `medications` | `pharmacy` | |
| IPD | 8 | `ipd` | `ipd_*` | `ipd` | |
| Nursing | 9 | `nursing` | `nursing_*` | `nursing` | |
| OT | 10 | `ot` | `ot_*` | `ot` | |
| ICU | 11 | `icu` | `icu_*` | `icu` | |
| Emergency | 12 | `ed` | `ed_*` | `emergency` | Prefix differs from API path (accepted) |
| Blood Bank | 13 | `bloodbank` | `blood_*` | `blood-bank` | Prefix differs from table prefix (accepted) |
| Insurance | 14 | `insurance` | `insurance_*`, `corporate_*` | `insurance` | |
| Inventory | 15 | `inventory` | `inventory_*` | `inventory` | |
| Procurement | 15 | `procurement` | `procurement_*` | `procurement` | |
| HR | 16 | `hr`, `attendance`, `leave`, `payroll`, `compensation` | `hr_*` | `hr, payroll, leave` | Several permission prefixes (accepted) |
| Portal / CRM | 17 | `portal`, `crm` | `portal_*`, `crm_*` | `portal, mobile` | |
| Interoperability | 18 | `interoperability` | `integration_*`, `fhir_*`, `hl7_*`, `dicom_*`, `terminology_*` | `interoperability, fhir, hl7, dicom` | |
| Analytics | 19 | `analytics` | `fact_*`, `dim_*`, `analytics_*` | `analytics` | Warehouse schema |
| AI | 20 | `ai` | `ai_*` | `ai` | |
| Finance | 21 | `finance` | `fin_*` | `finance` | proposed |
| Facility | 22 | `facility` | `fac_*` | `facility` | proposed |
| Quality | 23 | `quality` | `qa_*` | `quality` | proposed |
| Specialty | 24 | `specialty` (per sub-module) | `mat_*`, `dial_*`, `dent_*`, `physio_*`, `tele_*` | per sub-module | proposed |
| Platform / SaaS | 25 | `platform` | `plat_*` | `platform` | proposed |

> **H-01** creates `docs/registry/modules.md` from this table and a **Permission Registry** generated from the code. A CI test fails if a permission or table prefix is not in the registry.

### 8.3 Number series (human-readable numbers)

All of these come from the **Numbering Service** (14.2). Formats are settings per hospital; these are the defaults.

| Series | Default format | Notes |
|---|---|---|
| MRN | `{HOSP}-{8 digits}` e.g. `DHC-00000123` | Never reused, never changed, no personal data inside. Printed as Code128 + QR |
| Appointment | `APT-{YYYY}-{6}` | |
| Encounter | `ENC-{YYYY}-{8}` | |
| Prescription | `RX-{YYYY}-{8}` | |
| Invoice / Receipt / Refund | `INV-`, `RCT-`, `RFD-` `{YYYY}-{8}` | Fiscal-year reset optional |
| Lab accession | `LAB-{YYMMDD}-{5}` | Daily reset, barcode |
| Radiology accession | `RAD-{YYMMDD}-{5}` | Also used as DICOM Accession Number |
| Admission | `ADM-{YYYY}-{6}` | |
| Surgery | `OTS-{YYYY}-{6}` | |
| ICU episode / ED visit | `ICU-{YYYY}-{6}`, `EDV-{YYYY}-{8}` | Unknown patient: `UNKNOWN-{YYYY}-{6}` |
| Blood donation / unit | `DON-`, `BU-` | Unit numbers follow the blood bag label standard chosen by the blood bank |
| Claim / PO / GRN | `CLM-`, `PO-`, `GRN-` `{YYYY}-{6}` | |
| Employee / Payroll run | `EMP-{6}`, `PAY-{YYYY}{MM}` | |

---

## 9. Data Standards

### 9.1 Keys and identifiers

* Primary key: `BIGINT UNSIGNED AUTO_INCREMENT` named `id`.
* **Public identifier** for anything exposed in URLs/APIs that identifies a person or a clinical event: add `public_id CHAR(26)` (ULID) with a unique index and use it in routes. This removes guessable numeric ids (IDOR hardening). **New modules (12+) must do this**; older modules adopt it when touched (H-12).
* Business numbers (MRN, invoice no.) are separate columns with unique constraints that **include the scope** (`UNIQUE(hospital_id, invoice_number)`).

### 9.2 Time

* Store **UTC**. `config('app.timezone') = 'UTC'`.
* Display in the hospital's timezone (default `Asia/Dhaka`, UTC+06:00; Bangladesh currently observes no daylight saving, but never hard-code the offset — always use the timezone name).
* API dates are ISO-8601 with offset (`2026-10-25T12:49:00+06:00`).
* Clinical documents show date **and** time with timezone abbreviation where legally relevant.
* Working week is configurable (default for Bangladesh: Friday–Saturday weekend; the calendar service reads it from settings).

### 9.3 Money and quantities

| Item | Type |
|---|---|
| Document totals, paid, due, refund | `DECIMAL(18,2)` |
| Unit price, rate, discount %, tax %, unit cost, quantity, dose strength | `DECIMAL(18,4)` |
| Currency | `currency_id` on every money header; default BDT; `exchange_rate DECIMAL(18,8)` when not BDT |
| Rounding | Billing's `rounding_amount` line; rule configurable (e.g. nearest taka) |
| Units | UCUM codes in `unit_code`; display text from lang files |

Never store money as float. Never compute money in JavaScript for storage.

### 9.4 Status vocabulary (shared words)

Stored values are **lowercase snake_case strings** backed by PHP enums. Display text is translated from lang files (so "Waiting" in English, "অপেক্ষমাণ" in Bangla).

| Concept | Standard values |
|---|---|
| Master data | `active`, `inactive`, `archived` |
| Workflow documents | `draft`, `submitted`, `pending_approval`, `approved`, `rejected`, `returned`, `cancelled`, `completed`, `closed` |
| Clinical result/note | `draft`, `entered`, `verified`, `final`, `amended`, `entered_in_error` |
| Orders | `ordered`, `accepted`, `in_progress`, `completed`, `cancelled`, `rejected` |
| Money documents | `draft`, `finalized`, `partially_paid`, `paid`, `cancelled`, `written_off`, `reversed` |
| Integration messages | `received`, `queued`, `processing`, `delivered`, `failed`, `dead_lettered` |

A module may add its own specific states (e.g. `left_without_being_seen`) but must not invent synonyms for the shared ones (no `Enabled`/`ACTIVE`/`Running` for `active`).

### 9.5 Soft delete, history, immutability

| Table kind | Delete rule |
|---|---|
| Master/config data | Soft delete allowed (`deleted_at`) or use `inactive` |
| Draft working data | Soft delete allowed |
| **Clinical records** | **Never deleted.** `entered_in_error` + reason + audit |
| **Financial records** (invoice, payment, refund, journal, claim settlement) | **Never deleted.** Reversal / credit note / cancellation with reason |
| **Ledgers** (stock, account, alert acknowledgements, audit) | **Append-only.** No update, no delete. Corrections are new rows |
| Security/audit logs | Append-only; only retention job (after the legal period) may purge |

Versioned records (final lab report, radiology report, discharge summary): header table + `*_versions` table; the current pointer moves, old versions stay readable.

### 9.6 Indexing, constraints, migrations

* Foreign keys always on; default `ON DELETE RESTRICT`.
* Composite indexes **start with the scope columns that filter first**, then the status/date columns used by the screen, e.g. `(hospital_id, branch_id, status, arrived_at)`.
* Unique business keys include scope.
* Every list screen gets an index review (`EXPLAIN`) with realistic data (≥ 100 000 rows in the test seeder for volume tables).
* **Migrations:** never edit a migration that has run in any shared environment; add a new one. For big tables: *add nullable column → backfill in a job → add constraint*. Every migration has a working `down()` or an explicit "irreversible, backup first" note. Seeders are idempotent (`updateOrCreate`).
* Collation `utf8mb4` so Bangla (and later Arabic) text compares and sorts correctly.

### 9.7 Data classification (drives masking, logging and export rules)

| Class | Examples | Controls |
|---|---|---|
| **C1 Public** | Hospital name, doctor schedule | none |
| **C2 Internal** | Settings, stock levels, rosters | login required |
| **C3 Confidential** | Patient demographics, bills, employee data, payroll | permission + scope; masked in logs; export audited |
| **C4 Restricted** | Clinical notes, results, diagnoses, HIV / mental-health / reproductive-health data, NID/passport numbers, biometrics, genomic data, AI prompts containing patient data | Permission + scope + purpose logging; encrypted at rest (field-level for identifiers); never in URLs, logs or analytics exports unmasked; break-glass for exceptions |

---

## 10. Clinical Safety and Record Integrity

### 10.1 Record lifecycle

```text
draft ──► entered ──► verified ──► final ──► amended (new version)
                                     └────► entered_in_error (never deleted)
```

* Only the author edits a draft. `final` cannot be edited — only **amended** (reason, who, when, previous version kept) or **addended**.
* Each final record shows author, verifier, timestamps and version.
* Printing/exporting a clinical document is audited.

### 10.2 Encounter locking

Encounters lock after completion (configurable grace period). Later changes need `…amend` permission and a reason. (Phase 3 §40–41.)

### 10.3 Patient identification (wrong-patient prevention)

1. Any order, specimen, medication, blood, procedure or image must be linked to the patient by **two identifiers** (MRN + name/DOB) shown on screen.
2. A **Patient Banner** is shown on every clinical screen: name, age/sex, MRN, photo (if available), allergies, active alerts, location, isolation flag.
3. Barcode/QR scanning is available for MRN wristbands, specimen labels, medication packs and blood bags.
4. **Unknown patient pattern** (from Phase 12): temporary identity `UNKNOWN-…`, never auto-merged, later *linked* to the real MPI record by an authorised, audited, reversible action.

### 10.4 Observation Service (D-08)

**Problem.** Vital signs and measurements are stored in four places (Phase 3, 9, 11, 12). That makes trends, alerts and reports wrong.

**Solution — one service, one store, many views.**

```text
Contract: ObservationService
  record(ObservationData): Observation          // one measurement
  recordPanel(PanelData): Observation[]         // BP = systolic + diastolic, etc.
  latest(patientId, code, context?): Observation
  series(patientId, code, from, to): Observation[]
  markEnteredInError(id, reason, actor)
Event: ObservationRecorded(observationId, patientId, code, contextType, contextId)
```

Core table `clinical_observations` (owner: Phase 3 / Core Clinical):

```text
id, public_id, organization_id, hospital_id, branch_id,
patient_id, encounter_id, context_type, context_id        -- 'opd','ipd','icu','ed','nursing','ot'
code_system, code, display                                -- LOINC for vitals/labs
value_number, value_text, unit_code, value_boolean
effective_at, recorded_at, recorded_by, device_id NULL
status                                                    -- preliminary|final|amended|entered_in_error
interpretation NULL, reference_low NULL, reference_high NULL
supersedes_id NULL, source_module, source_record_id
```

**Rules**
* Early-warning and ICU scores, charts, FHIR `Observation` mapping and AI all read from here.
* A module keeps *context* tables (ICU flowsheet, nursing round, ED triage) that **reference** observation ids; the numbers themselves live once.
* Nobody deletes an observation; "wrong entry" = `entered_in_error`.

**Safe migration plan (no big-bang):**
1. *Build* the service and table (H-03).
2. *Expose a read view* `v_patient_observation_feed` that unions today's `encounter_vitals`, `nursing_vitals/observations`, `icu_vital_signs/observations`. Charts and alerts use the view immediately.
3. **New code (Phase 12 ED, Phase 13 transfusion monitoring) writes only through the service.**
4. Later, switch Phase 9/11 writes to the service with a short dual-write period, then back-fill history. Do this only when stable (H-03b, optional).

### 10.5 Alert Service (D-09)

One engine for *raise → deliver → acknowledge → escalate → resolve*.

```text
Contract: AlertService
  raise(AlertRequest): Alert       // patient, scope, source module/record, severity, category, message_key, ack_required, escalation_policy
  acknowledge(alertId, actor, note?, readBack?: bool)
  resolve(alertId, actor, note?)
  override(alertId, actor, reason)          // e.g. allergy warning override — reason mandatory, audited
Events: AlertRaised, AlertAcknowledged, AlertEscalated, AlertResolved
Job: EscalateUnacknowledgedAlerts (every minute)
```

* Severities: `info`, `warning`, `critical`.
* **Escalation policy** (settings, never hard-coded): e.g. critical lab value → ordering doctor (0 min) → ward nurse in charge (10 min) → lab supervisor + duty doctor (20 min) → medical director (30 min). Values are examples, set by the hospital's clinical governance.
* **Alert fatigue controls:** de-duplication window, severity downgrade rules need clinical approval, daily override report.
* Modules keep their own alert *types* (lab critical value, ICU deterioration, ED sepsis flag) and may keep their own tables, but acknowledgement and escalation records live in the service so there is **one** place to ask "what is still unacknowledged?".

### 10.6 Closed-loop critical results

```text
Result flagged critical → AlertService.raise → notify ordering clinician
   → clinician acknowledges (with read-back checkbox) → documented action
   → if no acknowledgement in N minutes → escalate → ... → report of all delays
```

Applies to Laboratory (Phase 5), Radiology critical findings (Phase 6), Blood Bank reactions (Phase 13), and ED/ICU alerts (Phases 11–12).

### 10.7 Medication safety (Phases 3, 7, 9)

Allergy check at prescribing **and** dispensing **and** administration · interaction check (foundation, then external drug database) · high-alert medicines need second verification · controlled drugs use the controlled register with witness · MAR "rights" check by barcode where available · partial dispensing and substitution are recorded, never silent · override requires reason and is reported.

### 10.8 Break-glass

```text
Denied → "Emergency access" → reason (mandatory, free text + category) → time-boxed access (default 4 h)
       → security event + notification to Privacy Officer → entry in Break-glass Review queue → review within 24–72 h
```

Break-glass never turns audit off and never exposes C4 data in exports.

### 10.9 Four-eyes (dual approval) actions

Controlled-drug adjustment · stock write-off above threshold · blood issue (second check at bedside) · refund/discount above threshold · amending a final result · payroll lock · merging patients · deleting master data in use. Thresholds are settings; the workflow engine (Phase 0) runs the approval.

### 10.10 AI boundary (Level 0–5, from Phase 20)

AI may *inform*, *summarise*, *draft*, *suggest*, *forecast*. AI may **never** diagnose, prescribe, change doses, order tests/blood/surgery/ICU, assign final triage, discharge, transfer, override alerts, finalize notes/reports, change demographics, billing or claims. Every AI output is labelled "AI-generated — needs review", stores model/version/inputs/reviewer, and is a *suggestion object*, not a record.

### 10.11 Special cases

* **Death:** one hospital-wide death documentation flow (owned by IPD/Phase 8 with ED/ICU entry points). No module creates its own death register.
* **Minors/dependants:** consent by guardian (Phase 1).
* **Mortuary and medico-legal cases** (police case, accident, assault): flagged `medico_legal`; restricted viewing; special documentation retained (Phase 12 + Phase 22).

---

## 11. Financial Integrity and the Billing Contract

### 11.1 The one rule

> Clinical and operational modules **announce** what was done. **Only Billing** prices it, invoices it, collects payment and refunds it. **Only Finance** (Phase 21) posts it to the ledger.

```text
Service delivered ─► ChargeEvent ─► Billing (price, tax, discount, invoice) ─► Payment/Refund
                                              │
                                              └─► InvoiceFinalized / PaymentReceived ─► Finance posting (Phase 21)
Insurance/Corporate (Phase 14) works on top of Billing invoices: coverage split, claim, settlement.
```

### 11.2 Charge Event Contract (D-10)

```text
Contract: ChargeEventPublisher::publish(ChargeEventData): void

ChargeEventData
  idempotency_key   string   "<MODULE>:<visit_ref>:<service_code>:<source_event_id>"
  organization_id, hospital_id, branch_id
  patient_id, encounter_id, admission_id NULL
  service_code      string   (from Billing service catalogue; never a price)
  quantity          decimal(18,4)
  performed_at      datetime
  performed_by      user_id / provider_id
  department_id     NULL
  source_type, source_id     (traceability: "Why was the patient charged?")
  billing_party_hint  patient|insurance|corporate  (Billing decides finally)
  is_chargeable     bool  + reason when false (no-charge/waiver cases)
```

* `<MODULE>` = `OPD, LAB, RAD, PHM, IPD, BED, NUR, OT, ICU, ED, BLD, INS, INV, …`
* `<visit_ref>` = encounter id (or admission id for bed days).
* **Unique index** on `idempotency_key` inside Billing → sending the same event twice never double-charges.
* **Reversal:** `ChargeReversalRequested(idempotency_key, reason)` — Billing creates a negative charge/credit; the original stays.
* Module-local tables like `ed_chargeable_events`, `ipd_chargeable_events` remain as **outbox/traceability**: they record what the module published and the result (`accepted|rejected|reversed`). They never hold prices.
* Daily jobs that generate recurring charges (bed days, ventilator hours) are idempotent by key `BED:{admission_id}:{service}:{YYYYMMDD}`.

### 11.3 Billing safeguards (already in Phase 4 — confirm in code)

* Invoice numbers from the Numbering Service, never `count()+1`.
* Finalized invoices are immutable; correction by credit note / adjustment with approval.
* Price used is **frozen on the invoice item**; old invoices never re-price.
* Discount, refund, write-off need approval above thresholds (settings).
* Cashier sessions with opening/closing balance, cash count, variance report.
* Every money change writes `billing_audit_logs` (who, when, before/after, reason).
* All posting operations run in one DB transaction with row locks.

### 11.4 Bangladesh billing specifics (see chapter 18)

BDT default · VAT per service category (rate is configuration) · VAT-invoice layout · amount in words (English and Bangla) · mobile-wallet and card payments via gateway adapters · consultant-fee settlement (Phase 21) · package pricing for surgery/delivery/health checks · IPD advance (deposit) and discharge clearance · charity/waiver fund with approval.

---

## 12. Stock and Inventory Contract

### 12.1 Who holds what (D-11)

| Store | Owner | What | Why separate |
|---|---|---|---|
| `pharmacy_stock`, `pharmacy_batches` | Phase 7 | Dispensing-level medicine stock | Clinical dispensing rules (FEFO, controlled, substitution) |
| `inventory_stock`, `inventory_batches` | Phase 15 | Enterprise stores: consumables, supplies, reagents, general items | Procurement, transfers, valuation |
| `blood_units` | Phase 13 | Blood and components | Traceability, cold chain, not "items" |

### 12.2 Stock Ledger Contract

```text
Contract: StockLedger::post(LedgerEntry): void        // append-only
LedgerEntry: store_id, item_id, batch_id NULL, serial_id NULL, quantity (+/-), unit_cost NULL,
             txn_type, reference_type/id, performed_by, performed_at, idempotency_key
txn_type: opening_balance | purchase_receipt | transfer_out | transfer_in | issue | dispense | return |
          wastage | expiry_writeoff | adjustment_gain | adjustment_loss | quarantine_in | quarantine_out | recall
```

Rules for **all** three stores:

1. No stock change without a ledger row. Balance tables are projections that can be rebuilt from the ledger.
2. No negative stock unless the store's setting allows it (and then a daily exception report).
3. Batch + expiry required for medicines; expired batches are blocked from issue/dispense.
4. FEFO default for medicines/consumables; override needs permission + reason.
5. Adjustments, write-offs and recalls go through four-eyes approval (10.9).
6. Transfers are two ledger rows (out/in) with an in-transit state.
7. Reservations reduce *available*, not *on-hand*.

### 12.3 Procurement → stock flow

```text
Requisition → Approval → RFQ/Quotation → Purchase Order → Goods Receipt (GRN) → Quality check
   → put-away: Inventory store  OR  Pharmacy store (via PharmacyReceivingAdapter)  → Ledger
   → Supplier invoice (three-way match) → Accounts payable (Phase 21)
```

Until Phase 15 exists, Phase 7 keeps its simple receiving screen. When Phase 15 is built, the pharmacy receiving screen is switched to the adapter (a small change, not a rewrite).

### 12.4 Consolidated views

Phase 15 provides read-only database views/queries: `v_stock_on_hand_all_stores`, `v_expiry_risk`, `v_stock_valuation`. Management reports use these views — nobody joins three stores by hand.

### 12.5 Bangladesh notes

Medicines carry a printed MRP (maximum retail price) → pharmacy pricing rule `price ≤ MRP` and discount rules; strips/loose-tablet selling needs unit conversion (Phase 7 §36–37); many local products have no GS1 barcode → internal barcode labels are generated on receiving; narcotics/psychotropics follow the controlled register; DGDA registration number stored on the drug master.

---

## 13. Cross-Module Integration

### 13.1 Ownership in one table (full matrix in Appendix B)

| Data | Owner | Others may |
|---|---|---|
| Patient identity, MRN, consents | Patient (1) | read via `PatientLookup` contract; never write |
| Provider schedule, appointment | Appointment (2) | read; request booking via contract |
| Encounter, diagnosis, clinical order, prescription | OPD/EMR (3) | create orders via `ClinicalOrderService`; read |
| Price, invoice, payment | Billing (4) | publish charge events |
| Lab order/specimen/result | Laboratory (5) | read results; request tests via clinical order |
| Imaging order/study/report | Radiology (6) | read reports; view links |
| Medicine master, dispensing | Pharmacy (7) | request dispensing; read |
| Admission, bed, location, discharge, death | IPD (8) | request admission/transfer |
| Nursing record, MAR | Nursing (9) | read; MAR consumes prescriptions |
| Surgical case | OT (10) | request surgery |
| ICU episode | ICU (11) | request ICU |
| ED visit, triage | Emergency (12) | hand over via events |
| Blood unit, transfusion | Blood Bank (13) | request blood; read status |
| Claim, policy | Insurance (14) | read coverage |
| Stock (enterprise), supplier, PO | Inventory/Procurement (15) | request items |
| Employee, payroll | HR (16) | read provider/employee data |
| Ledger, accounts | Finance (21) | post via events |

### 13.2 Events (the glue)

* An event is a **fact in the past**: `PrescriptionSigned`, `LabResultFinalized`, `AdmissionDischarged`.
* Payload = ids + scope + `correlation_id` + `occurred_at` + `actor_id`. **No full models, no PHI text.**
* Dispatch **after commit**. Listeners are **idempotent** (they may run twice) and **do not assume order**.
* A failing listener never rolls back the clinical transaction. Failures land in `failed_jobs` and the integration/queue monitor; a dashboard shows failed and delayed listeners.
* Anything financial or safety-critical (charge events, critical alerts) uses the **outbox pattern**: write the event row in the same transaction as the business change, a worker publishes it; retries are safe thanks to idempotency keys.
* Maintain `docs/events.md` (generated list: event, producer, consumers, payload). H-01 produces the first version.

### 13.3 Synchronous or asynchronous?

| Use | When |
|---|---|
| **Synchronous contract call** | The caller cannot continue without the answer and it is fast & local: eligibility check, "is bed free", allergy check, patient lookup |
| **Domain event (async)** | Something happened and others react: charge creation, notification, analytics, FHIR export |
| **Queued job** | Slow or external: PDFs, SMS, payment gateway, PACS, bulk export |

### 13.4 Forward-integration rule (D-13)

When a phase must talk to a module that does not exist yet:

```text
1. Define the interface in the CALLER'S or a shared Contracts namespace
   (e.g. BloodRequestGateway::request(BloodRequestData): BloodRequestResult)
2. Ship a NullBloodRequestGateway that returns "module not enabled" and logs it
3. Hide the UI button when the module flag is off
4. Phase 13 later registers the real implementation in the service provider
5. Add a contract test that both implementations satisfy
```

This is how Phase 9 (Nursing) and Phase 12 (ED) can be complete before Phase 13 (Blood Bank).

### 13.5 Identity links between modules

| Link | Rule |
|---|---|
| `providers.employee_id` ↔ `hr_employees.id` | Phase 2 created `providers` with nullable `employee_id`. Phase 16 back-fills it. HR is master for name/licence/status. Provider availability stays in Appointment |
| `users.employee_id` | A user may be linked to an employee; the link is optional (visiting consultants may not be employees) |
| `patient_portal_accounts` (Phase 1) ↔ Phase 17 portal account | Phase 1 holds the *foundation link*; Phase 17 extends it (verification, devices, sessions). Do not create a second account table; extend/rename in a migration |
| Employee who is also a patient | Linked through MPI; record flagged `confidential` |

### 13.6 Reference journeys

**A. OPD visit**

```text
Appointment booked ─► checked-in ─► queue/token ─► Encounter started (Phase 3)
   ├─ Observation(s) recorded (Observation Service)
   ├─ Clinical orders: lab / radiology / medication
   │     ├─► LabOrderPlaced ─► specimen ─► result FINAL ─► (critical? Alert) ─► result visible in encounter
   │     └─► ImagingOrderPlaced ─► study ─► report FINAL ─► visible in encounter
   ├─ Prescription signed ─► PharmacyOrderCreated ─► dispensed ─► ChargeEvent (PHM)
   ├─ ChargeEvent (OPD consultation, LAB, RAD) ─► Billing invoice ─► payment ─► receipt
   └─ Encounter completed/locked ─► follow-up appointment ─► Portal visibility
```

**B. Admission**

```text
Admission request (from OPD/ED) ─► approval ─► bed allocated ─► patient moved ─► Nursing episode opens
   ├─ daily: bed-day ChargeEvent (idempotent)   ├─ MAR from prescriptions   ├─ OT / ICU requests
   └─ Discharge request ─► clearance (billing, pharmacy returns, nursing) ─► discharge summary ─► bed release ─► final invoice
```

**C. Emergency**

```text
Arrival ─► registration (or UNKNOWN id) ─► triage ─► priority queue ─► ED encounter ─► rapid assessment
   ├─ STAT orders (lab/radiology/medication/blood)  ├─ resuscitation / code blue (shared with ICU)
   └─ disposition: discharge | LAMA | admit (IPD) | ICU | OT | transfer | death
```

**D. Order → result → bill (any diagnostic)**

```text
Clinical order ─► diagnostic module accepts ─► ChargeEvent ─► (payment rules) ─► work done ─► result FINAL ─► notify ─► visible in EMR/Portal
```

### 13.7 Correlation

Every request gets a `request_id`; every business flow a `correlation_id` carried through events and jobs (e.g. `COR-20261025-9F3A21`). Audit rows, logs and error pages show it so support can trace "Order → Charge → Specimen → Result → Notification".

---

## 14. Shared Platform Services

Every service below has **one** implementation, one contract, and is used by all modules. "H" = to be added or confirmed in Phase H.

| # | Service | Status | Purpose |
|---|---|---|---|
| 1 | Auth, RBAC, Scope | Phase 0 | Login, MFA-ready, roles, permissions, scope context |
| 2 | Audit / Activity / Security events | Phase 0 | Who did what, when, from where, before/after |
| 3 | Settings | Phase 0 | Business settings in DB (typed), `.env` only for deployment |
| 4 | Master data | Phase 0 | Generic lookup framework (blood groups, document types, …) |
| 5 | Workflow / Approval | Phase 0 | Sequential/parallel, role/user/amount based, escalation, delegation |
| 6 | Notifications | Phase 0 (+ Bangla templates H) | In-app, email, SMS, push, WhatsApp-ready; templates en/bn |
| 7 | File service | Phase 0 | Private storage, MIME check, versions, signed URLs |
| 8 | Dashboard & Menu framework | Phase 0 | Permission-aware widgets and menu |
| 9 | System health | Phase 0 | DB, Redis, queue, storage, mail, scheduler checks |
| 10 | **Numbering Service** | **H-02** | Concurrency-safe sequences per scope & period |
| 11 | **Observation Service** | **H-03** | One store for measurements (10.4) |
| 12 | **Alert Service** | **H-04** | Raise/ack/escalate (10.5) |
| 13 | **Charge Event Publisher** | **H-05** | Billing contract (11.2) |
| 14 | **Stock Ledger Contract** | **H-06** | Append-only stock postings (12.2) |
| 15 | **Document & Print Service** | **H-08** | Templates for receipts, prescriptions, reports; bilingual; QR |
| 16 | **Report & Export Service** | **H-07** | Filtered, paged, queued exports (PDF/Excel/CSV) with audit |
| 17 | **Global Search** | H-09 | One top-bar search: MRN, phone, name, NID, barcode |
| 18 | **Barcode/QR Service** | H-09 | Code128/QR generation and scan resolution |
| 19 | **Import Service** | H-11 | Staged import with preview & error report (patients, drugs, prices, stock) |
| 20 | **Module Enablement** | H-01 | Per-organization/hospital flags for each module |
| 21 | **Integration Stub Registry** | H-01 | Null adapters for forward integrations (13.4) |

### 14.1 Module enablement

`settings` keys `modules.<key>.enabled` per organization (and optionally per hospital). When off: menu hidden, routes return 404, API returns 404, scheduled jobs skip, permissions are not assignable. Dependencies are checked (can't enable ICU without IPD + Nursing).

### 14.2 Numbering Service

```text
NumberSequence::next(series, scope, date): string
table number_sequences(id, organization_id, hospital_id NULL, series, period_key, last_value, format, reset_policy)
UNIQUE(organization_id, hospital_id, series, period_key)
```
Implementation: inside the caller's transaction, `SELECT … FOR UPDATE` on the sequence row (or `INSERT … ON DUPLICATE KEY UPDATE last_value = LAST_INSERT_ID(last_value+1)`), then format. **Never** `max(id)+1` or `count()+1`. Gaps are acceptable for rolled-back transactions only if the hospital's fiscal rules allow; for invoices configure "gap-free" (sequence taken at finalize time).

### 14.3 Document & Print Service

* Template per document type (receipt, invoice, prescription, lab report, radiology report, discharge summary, consent, payslip, claim form) × language (`en`, `bn`, `en+bn`) × paper (A4, A5, **80 mm thermal**).
* Header from hospital settings (logo, licence number, VAT registration number, address in Bangla and English).
* Footer: page x/y, printed-by, printed-at (hospital timezone), **QR code to a verification URL** for reports (patient/third party can confirm authenticity without seeing PHI).
* Prescription template shows doctor name, specialty, **BMDC registration number**, generic + brand names, Bangla advice lines.
* Versioned: an amended report prints "AMENDED — version 2" and references version 1.
* Every print/export writes an audit row.

### 14.4 Report & Export Service

Declarative report definition (filters, columns, scope-aware query, permissions) → HTML table, queued PDF/Excel/CSV. Large exports run as jobs and are delivered through the File service (expiring link). Every export is audited with filters used. C4 columns are masked unless the user has the export permission for that class.

### 14.5 Global search and barcode

Top-bar search (Ctrl+K or F3): MRN, mobile (any format: `01712345678`, `+8801712345678`), name (Bangla or English, phonetic-tolerant), NID, barcode scan. Results are scoped; selecting opens the Patient 360 or the relevant work item.

### 14.6 Bangla in PDFs — decide early

Bangla uses conjunct letters (juktakkhor) that many PHP PDF libraries render incorrectly. In Phase H, build **one** test receipt in Bangla containing hard cases (e.g. "ক্ষ", "জ্ঞ", "শ্রী", vowel signs, Bangla digits) and confirm it renders correctly in your chosen engine **before** every module builds its own templates. A Chromium-based renderer is the safest option; verify with your own fonts.

---

## 15. API Standard

### 15.1 Shape

| Item | Standard |
|---|---|
| Base | `/api/v1/<module-path>/<resource>` (kebab-case, plural) |
| Success | `{ "success": true, "message": "…", "data": {…}, "meta": {…} }` |
| Error | `{ "success": false, "message": "…", "errors": { "field": ["…"] }, "meta": { "request_id": "REQ-…" } }` |
| Lists | `meta.pagination = { page, per_page, total, last_page }`; large feeds use cursor pagination (`meta.next_cursor`) |
| Filtering | `?filter[status]=waiting&filter[from]=2026-10-01` — only whitelisted filters; never arbitrary column filtering |
| Sorting | `?sort=-created_at,name` — whitelisted |
| Includes | `?include=items,patient` — whitelisted, to prevent N+1 and over-exposure |
| Dates | ISO-8601 with offset |
| Money | strings with 2 or 4 decimals (`"1500.00"`), never floats |
| Fields | `snake_case` |
| IDs in URLs | `public_id` (ULID) for patient-identifying resources (9.1) |
| Docs | OpenAPI 3 generated from code (for example Scramble or Scribe); published at `/docs/api` for permitted users; CI fails if routes are undocumented |

### 15.2 Status codes

`200` ok · `201` created · `202` accepted (queued) · `204` no content · `400` malformed · `401` not authenticated · `403` no permission · `404` not found **or out of scope** · `409` state conflict (illegal transition, version clash) · `422` validation · `423` locked record · `429` rate limited · `500` unexpected (generic message + request id, details only in logs).

### 15.3 Safe writes

* `POST` that creates clinical or financial things, and every integration endpoint, accepts an **`Idempotency-Key`** header; same key + same body = same result, not a duplicate.
* Updates to finalized/versioned records use `If-Match: <version>` (or a `version` field) → `409` on mismatch.
* State changes are explicit verbs, not field edits: `POST /encounters/{id}/complete`, `POST /invoices/{id}/finalize`.
* Bulk endpoints are limited (default 100 items) and return per-item results.

### 15.4 Auth and limits

Sanctum tokens with expiry, device name and revocation. Rate limits per user + per IP, stricter on login, OTP, search and export endpoints. Webhooks are signed (HMAC) and replay-protected (timestamp + nonce).

### 15.5 Version and deprecation

Never break `/api/v1`. Additive changes only. Breaking change → `/api/v2` side by side, with a published sunset date and a deprecation header.

---

## 16. Security and Privacy Standard

### 16.1 Identity and sessions

* Passwords hashed with Argon2id (or bcrypt cost ≥ 12 if the platform lacks Argon2). Minimum length 12; breached/common password check; no forced periodic rotation by default (setting available — Phase 0 `security.password_expiry`).
* **MFA** (TOTP; SMS/OTP as fallback) required for: administrators, finance and payroll roles, remote/VPN access, break-glass approvers. Optional for others.
* Idle timeout default 15 minutes on clinical screens with **quick re-unlock by PIN/password** so ward workstations stay safe without losing the user's work. "Fast user switching" for shared ward PCs.
* Account lockout and throttling on failures; all logins and failures stored in login history; unusual-location/time alerts for admins.
* One user = one person. Shared logins are forbidden; the system makes this easy with fast switching.

### 16.2 Authorization

RBAC + scope + patient-level rules (chapter 6). Role catalogue in Appendix C. Permission changes are audited and take effect on the next request (cache invalidated). Quarterly **access review** report: who holds which role and when they last used it.

### 16.3 Application security (OWASP ASVS Level 2 as the target)

| Risk | Standard control |
|---|---|
| Injection | Eloquent/query builder with bindings; raw SQL only with bindings and review |
| XSS | Blade `{{ }}` escaping; sanitised rich text; Content-Security-Policy |
| CSRF | Laravel CSRF for web; tokens for API |
| IDOR | Scope policies + route model binding through scoped queries + public ULIDs |
| Mass assignment | Explicit `$fillable`; never `->update($request->all())` |
| File upload | MIME + extension + size validation, random names, private disk, antivirus scan (ClamAV) for uploads from patients/portal, no execution paths |
| SSRF (integrations) | Allow-listed hosts, no user-supplied URLs fetched by the server, block private IP ranges |
| Secrets | Environment/secret manager; never in code, logs, tickets or AI prompts |
| Dependencies | `composer audit` / `npm audit` in CI; update monthly |
| Headers | HSTS, X-Content-Type-Options, Referrer-Policy, frame protection |
| Logging | Never log passwords, tokens, full clinical text, full NID, card data. Mask identifiers |
| Errors | Generic message + request id to users; details in logs only |

### 16.4 Encryption

* TLS 1.2+ everywhere (also inside the LAN for DB and Redis if they are on different hosts).
* Disk/volume encryption for DB and file storage; encrypted backups.
* **Field-level encryption** (Laravel encrypted casts or application-level with blind index for lookup) for NID, passport, biometric templates, and similar C4 identifiers. Plan search with a hashed "blind index" column.
* Key management: `APP_KEY` rotation procedure documented; production keys in a secret manager / vault.

### 16.5 Audit

* Audit rows are append-only; application DB user has `INSERT`/`SELECT` only on audit tables.
* Audit what matters (Phase 0 §23–25): login/logout/failures, patient view & change, clinical record changes, prescription changes, result approval, billing changes, discount/refund, user/role/permission changes, settings changes, exports/prints, API access, break-glass, AI use.
* Optional **tamper evidence**: each audit row stores a hash of the previous row (hash chain) and a daily checkpoint hash is exported off-system.
* Patient-access report: "who viewed my record" (supports the data-protection right of access).

### 16.6 Privacy and the Bangladesh data-protection law

*(Legal status must be confirmed by your counsel — see chapter 18, item P-20.)*

Design the system so that the following are **possible and demonstrable**:

1. **Lawful basis and consent** captured per purpose (treatment, billing, research, marketing, portal messaging); consent can be withdrawn; marketing and CRM messages need separate consent.
2. **Sensitive data** (health) processed only for medical duties, emergencies, or with specific consent; every non-treatment access (reports, exports, research) records the purpose.
3. **Children (under 18)**: guardian consent flows (Phase 1 guardian model).
4. **Data subject rights**: access (patient copy of record, access log), correction (Phase 1 amendment workflow), restriction, portability (FHIR/PDF export), erasure *subject to medical-record retention duties* (so: anonymise non-mandatory data, keep mandatory records).
5. **Retention schedule** as settings per record class; a retention job proposes deletion/anonymisation for approval — never silent deletion.
6. **Breach response**: runbook, severity matrix, notification templates, evidence preservation; notification timelines set from the regulation once confirmed.
7. **Cross-border transfer control**: a registry of every external service that receives personal data (cloud, SMS, email, AI provider, support tools) with location, purpose and legal basis. AI providers outside Bangladesh receive **de-identified** data only unless legal approval is documented (Phase 20 §40–41). Prefer hosting production data in Bangladesh or in a legally approved location.
8. **Data protection impact assessment** template for new modules that process C4 data (Phase 17 CRM, Phase 19 research datasets, Phase 20 AI).
9. A named **Privacy Officer** role with access to privacy dashboards (break-glass reviews, export log, consent log).

### 16.7 Security operations

Penetration test before first production go-live and annually; vulnerability triage SLA (critical 7 days, high 30 days — suggested); security events dashboard (Phase 0); incident response runbook (Appendix F); staff security awareness at onboarding.

---

## 17. Interoperability and Coding Standards

### 17.1 Standards we align with (alignment targets, **not certifications**)

| Area | Standard | Where used |
|---|---|---|
| Exchange | **HL7 FHIR R4 (4.0.1)** | Phase 18 FHIR API, Portal, national exchange. National profiles: **Bangladesh Core FHIR Implementation Guide** published by DGHS (profiles for patient identity, health facility registry, immunisation, laboratory, conditions; FHIR R4) |
| Messaging | **HL7 v2.x** (ADT, ORM, ORU, MDM, ACK) | Analyzers, external labs, legacy systems |
| Imaging | **DICOM**, Modality Worklist, DICOMweb (QIDO/WADO/STOW) | Phase 6/18 |
| Workflows | IHE-style patterns (scheduled workflow, patient identity cross-referencing) | Design reference only |
| Diagnoses | **ICD-10** and **ICD-11 (MMS)** side by side, with mapping | Phase 3, 14, 19 |
| Lab & observations | **LOINC** | Phase 3/5, Observation Service |
| Clinical terms | **SNOMED CT** (optional licence/permitted use to be confirmed) | Later |
| Drugs | **ATC**, **DGDA drug registry**, EDQM dose forms (as used by the BD Core FHIR IG), generic names | Phase 7 |
| Units | **UCUM** | All numeric observations |
| Security | ISO/IEC 27001 controls, ISO 27799 (health informatics security), OWASP ASVS | Chapter 16 |
| Quality | JCI-style patient-safety goals (identification, medication safety, falls, surgical safety), WHO Surgical Safety Checklist (Phase 10), WHO haemovigilance practices (Phase 13) | Phases 9–13, 23 |
| AI as medical software | IEC 62304 / ISO 14971 mindset if AI features could be classed as medical-device software | Phase 20 |

### 17.2 Code system registry (platform tables, class P)

Create once in Phase H (H-13) and use everywhere:

```text
code_systems   (id, uri, name, version, publisher, status)
code_concepts  (id, code_system_id, code, display_en, display_bn NULL, parent_code NULL, status, valid_from, valid_to)
code_maps      (id, from_system, from_code, to_system, to_code, relationship)   -- ICD-10 ↔ ICD-11, local ↔ LOINC
```

Every coded field in clinical tables stores `code_system` + `code` + `display` (denormalised display, so history survives code retirement). **Load code systems from the official files; never invent codes.**

### 17.3 Identifiers

`patient_identifiers(system, value, type, is_primary, verified_at)` with `system` as a URI-style string:

| Identifier | Note |
|---|---|
| Local MRN | Hospital-issued |
| National ID (NID) | Smart-card 10-digit and older 13/17-digit formats exist — validate by configurable pattern, encrypt (C4) |
| Birth registration number (BRN) | 17-digit; essential for children |
| Passport / other | Foreigners, medical tourists |
| UHID | National unique health ID where issued (listed in the DGHS FHIR guide) |
| Insurance member no., employee no. | Phase 14, 16 |

### 17.4 Internal model → FHIR mapping (owned by Phase 18, prepared by every phase)

| Internal | FHIR R4 resource |
|---|---|
| Organization / Hospital / Branch | Organization, Location |
| Provider / Employee | Practitioner, PractitionerRole |
| Patient | Patient |
| Appointment | Appointment, Slot, Schedule |
| Encounter / Admission / ED visit / ICU episode | Encounter (class: AMB, IMP, EMER) |
| Diagnosis / Problem | Condition |
| Observation | Observation |
| Clinical order | ServiceRequest |
| Prescription / dispense / MAR | MedicationRequest, MedicationDispense, MedicationAdministration |
| Lab specimen / result / report | Specimen, Observation, DiagnosticReport |
| Imaging | ImagingStudy, DiagnosticReport |
| Procedure / surgery | Procedure |
| Allergy | AllergyIntolerance |
| Consent | Consent |
| Charge / invoice / claim / coverage | ChargeItem, Invoice, Claim, ClaimResponse, Coverage |
| Blood unit / transfusion | BiologicallyDerivedProduct, Procedure/Observation (until a dedicated profile is chosen) |
| Documents | DocumentReference |

**Rule:** the internal model is never bent to look like FHIR. Mapping lives in Phase 18 mappers (Domain → DTO → FHIR). Each phase only guarantees that the data needed for the mapping exists and is coded.

### 17.5 Adapter rule

```text
Controller/Action → Contract (interface) → Vendor Adapter → External system
```

Every external dependency has an interface (`SmsGateway`, `PaymentGateway`, `PacsClient`, `AnalyzerGateway`, `PayerGateway`, `BiometricGateway`, `AiProvider`, `EmailProvider`, `WhatsAppProvider`, `NationalHealthExchange`) and at least a **fake** implementation for tests. Vendor code never leaks into domain modules.

---

## 18. Bangladesh Localization and Compliance Pack

> **How to read the "Basis" column**
> **V** = verified in this session from a published source (listed in Appendix H).
> **P** = widely practised in Bangladeshi hospitals — confirm with your own hospital users.
> **C** = legal/regulatory — **confirm with your lawyer/tax adviser/DGHS** before relying on it.
> The pack is delivered as *configuration, master data and templates*, so a wrong assumption is a settings change, not a code change.

| # | Area | What to deliver | Phase | Basis |
|---|---|---|---|---|
| P-01 | **Currency** | BDT (৳) default; amount-in-words in English and Bangla (taka/paisa); rounding rule setting | H, 4 | P |
| P-02 | **Language** | English + Bangla for UI, SMS, email, print. Bangla numerals as a user preference. All labels in lang files. Self-hosted Bangla web font | H, all | P |
| P-03 | **Time & calendar** | `Asia/Dhaka`; configurable weekend (default Fri–Sat for office-type schedules; 24×7 for clinical units); yearly holiday calendar (national days, Eids, festivals) as master data; Ramadan clinic timetable templates | H, 2, 16 | P |
| P-04 | **Address** | Division → District → Upazila/Thana → Union/Ward → Village/Area/Road; city-corporation areas; postal code. Load official geo-code lists as master data (not typed lists) | H, 1 | P |
| P-05 | **Patient identifiers** | NID (10/13/17-digit patterns), BRN (17-digit), passport, UHID system URI, mobile as primary contact; all validated by configurable regex; NID/BRN encrypted | 1 | V (UHID/NID/BRN appear in the DGHS FHIR guide); P (digit formats) |
| P-06 | **Mobile numbers** | Accept `01XXXXXXXXX`, `8801XXXXXXXXX`, `+8801XXXXXXXXX`; normalise to E.164; duplicate-patient detection uses the normalised value | 1 | P |
| P-07 | **Names** | Name in English and Bangla; father's/mother's/husband's name fields (commonly requested on forms); mononyms allowed; phonetic/transliteration-tolerant search | 1 | P |
| P-08 | **Unknown date of birth** | Estimated age (years/months) + `dob_estimated` flag; age shown everywhere | 1 | P |
| P-09 | **Sensitive demographics** | Religion, marital status: optional, hospital-configurable, class C3/C4; default off | 1 | P |
| P-10 | **Professional registration** | BMDC number (doctors/dentists), BNMC number (nurses/midwives), pharmacy council registration (pharmacists) with expiry; displayed on prescriptions and reports; expiry alerts (Phase 16) | 3, 16 | P |
| P-11 | **Medicines** | DGDA registered-brand master with generic, strength, dosage form, manufacturer; generic-first prescribing; MRP and price rule; strip/loose unit conversion; narcotics & psychotropic register | 7 | V (DGDA drug registry is used by the DGHS FHIR guide); P |
| P-12 | **Diagnosis coding** | ICD-10 and ICD-11 both supported with crosswalk (D-18) | 3, 14, 19 | V (ICD-11 MMS in guide); P (ICD-10 in practice) |
| P-13 | **Lab coding** | LOINC mapping for tests; send-out (reference lab) workflow; home-collection | 5 | V (LOINC in guide); P |
| P-14 | **Tax** | Tax categories and rates are settings; VAT standard rate currently 15% (rate varies by sector; **medical-service treatment is C**); VAT invoice layout and registration number on documents; AIT/TDS on supplier payments and salary tables as Finance/HR settings | 4, 21, 16 | V (15% standard rate); C (health-service treatment, invoice format, AIT/TDS) |
| P-15 | **Payments** | Cash, card, bank transfer, cheque, mobile wallets (e.g. bKash, Nagad, Rocket, Upay) through a payment-aggregator adapter; settlement reconciliation; gateway fees posted as expense | 4, 17, 21 | P |
| P-16 | **SMS** | Local SMS gateway adapter; Unicode Bangla SMS (segment limits), sender masking, OTP templates, delivery reports | 0/H, 2, 17 | P / C (telecom regulator rules) |
| P-17 | **Insurance & corporate** | Private insurers (group/individual), corporate credit accounts, TPAs, panel/discount cards, charity and government/NGO scheme funding as *payers* | 14 | P |
| P-18 | **National reporting & exchange** | Align Phase 18 FHIR profiles with the **BD Core FHIR IG (DGHS, FHIR R4)**; aggregate HMIS export (DHIS2-compatible) as a Phase 19 report pack; notifiable-disease and birth/death notification forms | 18, 19, 8 | V (IG exists); C (which private facilities must report, endpoints, onboarding) |
| P-19 | **Facility licence** | DGHS licence number, hospital registration and VAT registration shown on bills and reports; effective-dated price lists so regulatory price guidance can be adopted and audited | 0, 4 | C |
| P-20 | **Data protection** | Per publicly available summaries, Bangladesh's Personal Data Protection Act, 2026 replaced the 2025 Ordinance and covers: consent that is specific and revocable; sensitive-data rules with exceptions for medical duties and emergencies; parental/guardian consent under 18; breach notification to the authority; restrictions/notifications for large-scale cross-border transfer of sensitive data; fines. **Effective dates, breach timelines and regulations were not found in the source — confirm with counsel.** The design in 16.6 is built so that either outcome can be supported | 16.6, all | V (existence, outline — vendor summary); C (details) |
| P-21 | **Cyber-security law** | A national cyber-security framework and National Cyber Security Agency exist; confirm incident-reporting and critical-infrastructure duties for hospitals | 16.7 | C |
| P-22 | **Infrastructure reality** | UPS/generator-aware design; LAN-first deployment that works without internet; offline-tolerant drafts (autosave in browser, retry queue for non-critical writes); downtime forms; SMS/payment features degrade gracefully; portal pages light for low bandwidth; target desktop 1366×768, thermal 80 mm receipt printers, label printers, barcode scanners, biometric attendance devices | 21, all | P |
| P-23 | **Hospital practices** | Patient attendant (bystander) passes and visiting hours · cabin/bed categories (general ward, cabins, VIP) with category-based bed charges · IPD advance deposit and discharge clearance · surgery/delivery/health-check **packages** · replacement blood-donor tracking · consultant-fee settlement and sharing · token/serial systems at chambers | 4, 8, 10, 13, 21 | P |
| P-24 | **Blood safety** | Mandatory screening panel as a configurable test list (policy-driven, includes the transfusion-transmissible infections required by national policy); donor eligibility rules as settings | 13 | C |
| P-25 | **Death & birth documentation** | Hospital birth notification and death certification forms as Document-Service templates | 8, 22 | C |

---

## 19. UX Standard — "Easy for the busiest person in the building"

### 19.1 Who we design for

| Role | Reality | Design answer |
|---|---|---|
| Receptionist / counter staff | Long queues, phone ringing, patients speak Bangla, dialects | Search-first home, giant "Register" and "Book" buttons, Bangla UI, barcode/QR |
| Doctor | 3–5 minutes per OPD patient, writes prescriptions all day | Doctor workspace in one screen, templates, favourite drugs, Bangla advice phrases, autosave |
| Nurse | Moving between beds at night, tablet or ward PC | "My patients" list, tasks due now, large touch targets, barcode scanning, minimal typing |
| Lab / radiology technician | Worklists, barcodes | Worklist-first, keyboard shortcuts, batch result entry |
| Pharmacist / cashier | Speed and money accuracy | POS-like screens, scan-and-go, clear totals, cash drawer summary |
| Manager | Wants numbers, not tables | Executive dashboard, drill-down, 1-click export |
| Patient / family | Anxious, uses a phone | Plain language, big buttons, Bangla, low-data pages |

### 19.2 Twelve UX rules

1. **Three-click rule.** Any frequent task from the role's home screen in ≤ 3 clicks.
2. **Role-based home screen.** After login each role lands on *their* worklist (receptionist → today's queue + Register; doctor → my queue; nurse → my patients + due tasks; pharmacist → pending prescriptions; cashier → billing counter; lab → worklist; manager → dashboard).
3. **Search-first.** One top-bar search (Ctrl+K / F3) for patient by MRN, mobile, name (Bangla or English), NID, barcode.
4. **Patient banner always visible** on clinical screens (name, age/sex, MRN, allergies, alerts, location). Never hide allergies.
5. **Plain-language labels.** "Admit patient", not "Create admission episode". Same word for the same thing everywhere (glossary: Appendix G). No internal terms (`encounter_id`) on screen.
6. **Status = colour + icon + text** (never colour alone). One status palette for the whole product.
7. **Safe confirmations.** Irreversible actions ask a specific question ("Cancel invoice INV-2026-00001245 for Rahim Uddin?") and require a reason when audit needs one. Routine actions do **not** ask "Are you sure?".
8. **Forgiving forms.** Autosave drafts for clinical notes; keep input when validation fails; inline, specific error text in Bangla/English next to the field; required fields marked; sensible defaults; paste-friendly.
9. **Speed is a feature.** Lists load < 2 s on LAN; search < 1 s; no full-page reload for common actions; heavy boards update by polling (or websockets) every 5–15 s without losing the user's place.
10. **Keyboard and scanner friendly.** Tab order matches the paper form; `Enter` moves forward; scanner input never needs a mouse click; documented shortcuts (`?` shows them).
11. **Honest empty/loading/error states.** Say what's missing and what to do next; show a reference id for errors; never a blank screen.
12. **Same pattern everywhere.** Seven screen templates (19.3) — if a screen doesn't fit one, redesign it.

### 19.3 The seven standard screen templates

| # | Template | Used for | Must contain |
|---|---|---|---|
| T1 | **Dashboard** | Role/module overview | 4–8 KPI tiles with trend, 2–4 lists of "needs attention", date/branch filter, drill-down links; no more than one screen of content at 1366×768 |
| T2 | **Worklist / Queue** | Appointment queue, lab worklist, pharmacy orders, nursing tasks, ED queue | Sorted by priority/time, status chips, assignee, wait time, filters as saved views, bulk actions, auto-refresh |
| T3 | **List + Filter** | Master and transaction lists | Search box, 3–5 filters, saved filters, column chooser, pagination, export (permission-gated), row actions menu, empty state |
| T4 | **Form / Wizard** | Registration, admission, orders | Sections or steps (max 7), progress, inline validation, autosave on long clinical forms, summary before submit |
| T5 | **Record 360 (detail)** | Patient 360, Admission, Invoice, Claim | Header with identity + status, tabs for areas, timeline, quick actions top-right, audit/history tab |
| T6 | **Board** | Bed board, ED tracking board, OT schedule, ICU board | Spatial/columnar layout, colour-coded status, drag/click to act with confirm, real-time refresh, legend |
| T7 | **Print / Document preview** | Receipts, prescriptions, reports, consents | Exact print layout, language/paper selector, print & PDF, audit on print |

### 19.4 Language and microcopy

* Every user-visible string lives in `lang/en` and `lang/bn`. A CI check fails when a key exists in one language and not the other.
* Short sentences, active voice, no abbreviations unless universal in Bangladeshi practice (BP, ECG, OT, ICU, NID, MRN).
* Error messages say **what happened, why, and what to do**: "Mobile number must be 11 digits starting with 01. Example: 01712345678."
* Dates default `dd-MMM-yyyy` (and `dd/MM/yyyy` as option); times 12-hour with AM/PM as user setting (24-hour for clinical charts by default).
* Medical abbreviations on prescriptions follow the hospital's approved list.

### 19.5 Devices and layout

* Design for **1366×768** first (still common in hospital PCs), scale up to 1920×1080; nurse/doctor tablets (10″) in landscape; reception portrait-capable.
* Touch targets ≥ 44 px on clinical tablets; font ≥ 14 px; Bangla font size slightly larger than Latin at the same visual weight.
* Printing: A4 reports, A5 prescriptions, 80 mm thermal receipts; labels for specimens/wristbands/blood bags via label printer.
* Accessibility: WCAG 2.2 AA target — contrast, focus outlines, labels, error association, no keyboard traps. Run an automated check (axe) in CI on key screens.

### 19.6 Menu (what the user sees)

Top-level menu items (hidden when the module is off or the user lacks permission):

```text
Dashboard
Patients · Appointments
Clinical (OPD · Emergency · Inpatient · Nursing · OT · ICU · e-Prescription)
Diagnostics (Laboratory · Radiology)
Pharmacy · Blood Bank
Billing & Revenue · Insurance & Corporate
Supply Chain (Procurement · Inventory) · Finance · HR & Payroll
Facilities & Assets · Quality & Safety
Reports & Analytics · Integrations · AI Assistant · Portals
Administration · Security & Audit · Help & Support
```

(Source: master spec "Recommended HMS ERP Menu Structure", simplified.) Each module defines its own sub-menu in its charter; **max two levels** in the sidebar, deeper navigation by tabs.

### 19.7 Help, onboarding and training

* **Context help** (“?” on each screen) linking to a 1-page Bangla/English guide.
* **Role quick-start cards** (1 page each: Receptionist, Doctor, Nurse, Lab, Radiology, Pharmacist, Cashier, Store, HR, Manager).
* **Training mode:** a sandbox organization with demo data (Bangla names, fake NIDs) so staff can practise without touching real patients; clearly coloured banner "TRAINING".
* **Release notes in-app** ("What's new") in plain language.
* **Super-user programme:** 1–2 trained champions per department.

### 19.8 UX acceptance (every module, every slice)

```text
[ ] Fits one of the 7 templates       [ ] Works at 1366×768 and on a 10" tablet
[ ] Bangla and English complete       [ ] Keyboard-only path works
[ ] Status colour + icon + text       [ ] Loading, empty and error states present
[ ] Patient banner on clinical pages  [ ] Print/PDF layout approved (A4/A5/80 mm as needed)
[ ] Task time target met in a test with 3 real users of that role
```

Suggested task-time targets (calibrate in pilot): register a returning patient ≤ 30 s · new patient ≤ 90 s · book an appointment ≤ 30 s · simple OPD prescription ≤ 60 s · dispense a simple prescription ≤ 45 s · collect a cash payment ≤ 30 s · record a set of vitals ≤ 40 s.

---

## 20. Quality Engineering

### 20.1 Test layers

| Layer | Tool | What it proves |
|---|---|---|
| Unit | Pest/PHPUnit | Calculations, state machines, rules, value objects |
| Feature | Laravel HTTP tests | Routes, validation, policies, workflows with DB |
| Integration | Feature tests with fakes/contracts | Cross-module contracts and events |
| Security | Dedicated `Tests/Security` | Scope, authorization, IDOR, uploads |
| Concurrency | Parallel/locked scenarios | No double booking, double dispensing, double charging |
| API contract | Schema assertions / OpenAPI | Envelope, status codes, pagination |
| End-to-end | Playwright or Dusk | The ~15 critical journeys (below) |
| Performance | Query-count assertions, load scripts (k6/Artillery) | N+1 free, budgets met |
| Accessibility/print | axe in CI, PDF snapshot tests (incl. Bangla) | UI & print quality |

### 20.2 Mandatory test packs (generated from the registry where possible)

| Pack | Rule | Example |
|---|---|---|
| **P1 Scope** | T-S1…T-S8 from 6.7 | Hospital A user cannot open Hospital B encounter |
| **P2 Authorization matrix** | Auto-test: every route/API action denies a user lacking its permission | Generated from Permission Registry |
| **P3 State machine** | Every illegal transition is rejected (409) | Cannot finalize an already finalized invoice |
| **P4 Concurrency** | Two simultaneous actions → one winner | Two nurses allocate the same bed |
| **P5 Idempotency** | Same event/request twice → one effect | ChargeEvent published twice → one charge |
| **P6 Immutability** | Final records cannot be edited/deleted; amendments keep history | Final lab result |
| **P7 Audit** | Each sensitive action writes an audit row with who/when/before/after | Discount approval |
| **P8 Money** | Golden-file invoices; rounding; tax; refunds; cashier close | Invoice with VAT and discount |
| **P9 Ledger** | Stock/account ledgers balance; no update/delete on ledger tables | Dispense 10 → stock −10 → ledger row → charge |
| **P10 Migration** | Fresh migrate + seed; rollback of last batch; data migration on sample data | |
| **P11 Localization** | Every lang key exists in `en` and `bn` | |
| **P12 Print** | PDF renders (A4/A5/80 mm), Bangla conjuncts correct (snapshot) | Receipt |
| **P13 No N+1** | `Model::preventLazyLoading()` in tests; query count budgets for list screens | |

### 20.3 The 15 critical end-to-end journeys (run in CI nightly and before every release)

1 Register new patient → 2 Book appointment → 3 Check-in & token → 4 OPD consultation with prescription → 5 Pay invoice & print receipt → 6 Lab order → specimen → result → critical alert → 7 Radiology order → report → 8 Pharmacy dispense (FEFO, stock, charge) → 9 Admission → bed allocation → 10 Nursing round & MAR → 11 Surgery request → schedule → WHO checklist → 12 ICU admission → flowsheet → 13 Discharge → final bill → 14 Emergency arrival → triage → disposition → 15 Refund with approval.

### 20.4 CI gates (a merge to `main` is blocked unless all pass)

```text
composer validate · Pint (style) · Larastan level ≥ 6 (new code level 8 goal) · unit+feature+security tests
· migrations fresh + rollback · composer audit / npm audit · lang parity · OpenAPI generation · build assets
· coverage report (informational at first; target ≥ 80 % lines in Actions/Services)
```

### 20.5 Performance budgets (initial, tune after pilot)

| Item | Budget (p95, LAN, typical server) |
|---|---|
| Patient search at 1 M patients | ≤ 500 ms |
| List screen with filters | ≤ 800 ms |
| Create/update transaction | ≤ 800 ms |
| Dashboard (cached) | ≤ 1.5 s; refresh in background |
| Report < 10 000 rows | ≤ 5 s; larger → queued |
| Queue latency for notifications | ≤ 30 s |
| Critical alert delivery | ≤ 10 s to in-app, ≤ 60 s to SMS |

Seeders provide **realistic volume** (≥ 100 000 patients, 1 M encounters/orders for load tests) so indexes are tested honestly.

### 20.6 Code review checklist (human + AI reviewer)

See Appendix F-1. Any *blocking* item (scope, authorization, money, duplicate domain, destructive migration, missing tests) stops the merge.

### 20.7 UAT

Each release train has UAT scripts per role, executed by the hospital's own staff in the **UAT** environment with sign-off. Defects are classified: *Blocker* (patient-safety/money/privacy) · *Major* · *Minor* · *Cosmetic*; no release with open Blockers.

---

## 21. DevOps, Operations and Go-Live

### 21.1 Environments

```text
Local ─► Dev ─► QA/Test ─► UAT (hospital users) ─► Staging (prod clone) ─► Production
```
Never develop on production. Production data is never copied to dev/QA unmasked; use the demo-data seeder or a **masked** clone.

### 21.2 Git and release

* `main` (always releasable, protected) · `feature/p12-s03-triage` short-lived branches · `hotfix/*`. PR required; CI green; one reviewer (the owner) for AI-generated code.
* Version tags: `v<major>.<phase>.<patch>` — e.g. `v1.12.0` is "Phase 12 included". Each tag has release notes in plain language.
* New modules ship **behind module flags** (14.1), enabled per hospital after UAT.

### 21.3 Reference deployments

**Size S/M (single hospital)**
```text
[Staff PCs/tablets] ── LAN/Wi-Fi ── [Nginx + PHP-FPM + Horizon] ── [MySQL] 
                                          │                       [Redis]
                                          └── [MinIO/files]       [Backup NAS + off-site encrypted copy]
UPS + generator on server room · optional second small server as warm standby
```
**Size L/XL**
```text
Load balancer ─► 2+ app nodes ─► MySQL primary + replica (reports) ─► Redis (HA) ─► object storage cluster
Separate worker nodes (Horizon), integration node (Phase 18), analytics warehouse (Phase 19)
Off-site DR site with replicated backups; monitoring stack; WAF
```

### 21.4 Backup and recovery (suggested targets — hospital policy decides)

| Size | RPO (max data loss) | RTO (max downtime) | Method |
|---|---|---|---|
| S | 24 h | 24 h | Nightly encrypted dump + file sync off-site |
| M | ≤ 15 min | ≤ 4 h | Nightly full + continuous binary-log shipping; file sync; warm standby |
| L/XL | ≤ 5 min | ≤ 1 h | Replica + binlog shipping + DR site; automated failover drills |

**A backup that was never restored is not a backup.** Restore test monthly (S/M) or weekly (L), signed off in the runbook.

### 21.5 Monitoring and alerts

Application errors (with request id) · failed jobs and queue depth · slow queries · DB/Redis/disk/CPU/memory · scheduler heartbeat · SMS/payment/PACS/integration failures · login failures and security events · backup success · SSL expiry · **critical-alert delivery latency**. Alerts go to an on-call channel (SMS/WhatsApp/phone call for critical).

### 21.6 Downtime procedure (power cut or network loss)

1. LAN-hosted system keeps running during internet loss; SMS/payment gateways queue and retry.
2. If the server is down: switch to **paper downtime forms** (printable from the system ahead of time: ED triage, admission, MAR, prescriptions, requisitions, receipts).
3. **Downtime census**: scheduled job prints a read-only list of current inpatients with allergies and active medications every N hours to the ward printer (and PDF to a protected folder).
4. After recovery: back-entry with `entered_late` flag and original time; reconcile numbers.

### 21.7 Go-live playbook (per hospital)

```text
T-8 w   Scope & module bundle agreed · hospital super-users named · network/hardware check
T-6 w   Master data loaded via Import Service: departments, services & prices, drugs, tests, users/roles, beds
T-4 w   Training (role-based 2–3 h sessions) in TRAINING org · UAT scripts executed · fix Blockers
T-2 w   Legacy patient import (if any) with duplicate detection · opening stock · opening balances · print templates approved
T-1 w   Dress rehearsal in Staging with real hardware (printers, scanners) · backup/restore drill
T-0     Cutover (preferably a low-volume period); parallel paper backup for first 48 h; floor-walkers from vendor & super-users
T+2 w   Hypercare: daily stand-up, defect triage, performance watch · T+4 w: review & sign-off
```
Each hospital also gets: user manual (Bangla/English), role cards, SLA/support contacts, escalation list, and the downtime kit.

### 21.8 Multi-hospital onboarding

Create organization → hospital(s) → branch(es) → departments → module flags → roles → users → numbering series → price lists → templates → integrations. Use an **onboarding wizard** (Phase H/25) so this is a checklist, not a developer task.

---

## 22. AI-Assisted Development Protocol

This chapter is for **you** and the AI coding tool.

### 22.1 The method in one picture

```text
        ┌─────────────────────────────── per PHASE ───────────────────────────────┐
        │ 1 Context Pack → 2 Inspect & Map (AI reports, you approve) → 3 Slice plan │
        └───────────────┬──────────────────────────────────────────────────────────┘
                        ▼   repeat for each SLICE (8–12 per phase)
   ┌────────────────────────────────────────────────────────────────────────────────┐
   │ 4 Implement slice on its own branch                                            │
   │ 5 AI self-review against Appendix F-1       6 Run tests + Pint + Larastan      │
   │ 7 AI report (files, migrations, routes, permissions, tests ACTUALLY run)       │
   │ 8 You review → merge → update Module Charter                                   │
   └────────────────────────────────────────────────────────────────────────────────┘
                        ▼
        9 Phase gate (Appendix F-2): all tests, scope pack, UAT scripts, docs → tag release
```

### 22.2 The Context Pack (what you give the AI for a phase)

```text
1. This guideline (HMS-GUIDE-001) — or chapters 3, 6–16, 20 + the phase chapter
2. The phase's own spec file(s) (Appendix A)
3. The Module Charters of the modules it integrates with (small files — not their specs)
4. docs/registry/modules.md, docs/events.md
5. The slice prompt (Appendix D-2)
```

**Do not** give the AI all 20 phase files at once. It will skim and duplicate things. If the tool has a context limit, give section ranges of the phase spec (the phase chapters in Part B tell you which sections matter for each slice).

### 22.3 Ten commandments for the AI developer (paste into `CLAUDE.md` — Appendix E)

1. **Inspect before you code.** Report what exists (modules, tables, services, routes, permissions, events) before proposing anything.
2. **Reuse or extend; never duplicate.** A second patient/billing/observation/bed/prescription concept is a defect.
3. **One slice at a time.** Stop at the end of the slice and report.
4. **Scope on every query, policy on every action**, server-side. Never trust client-supplied scope ids.
5. **Business rules live in Actions/Services**, not controllers, Blade or JavaScript.
6. **Never delete or silently overwrite** clinical or financial data. Amend, reverse, version.
7. **All money via Billing, all stock via the Ledger, all vitals via Observation, all alerts via Alert Service.**
8. **Events after commit, listeners idempotent, charge events with idempotency keys.**
9. **Tests with the code** (Pack P1–P13 where relevant). **Never claim tests passed unless you ran them** and show the command and result.
10. **Ask when unsure.** Missing information is a question to the owner, not an invention.

### 22.4 Slice rules

* A slice delivers something **testable end-to-end** (e.g. "Triage record create + reassess: migration, model, action, policy, API, screen, tests") — not "all migrations".
* Typical size: 1 migration group (≤ 8 tables), ≤ 25 files, 1–2 screens, tests included.
* Order inside a slice: migration → model → enums → policy → action/service → request → controller → routes → view → API resource → lang (en+bn) → tests → charter update.
* A slice never edits another module's tables. If it needs a change in another module, it is a **separate, explicitly approved slice** in that module.

### 22.5 What AI tools get wrong in this domain (review these first)

| Failure mode | How to spot it |
|---|---|
| Creates a new table that already exists elsewhere | Table not in the registry; "Why not use X?" has no answer |
| Forgets scope | Query without the scope trait; `Model::withoutGlobalScopes()` anywhere |
| Business logic in controller / Blade | Controllers longer than ~60 lines |
| Float money; `count()+1` numbers | grep `float`, `count() + 1` |
| Edits an old migration | Git diff shows an already-merged migration changed |
| "Tests pass" without running | No command output in the report |
| Tests that assert nothing | Assertions only on status 200 |
| Hard-coded strings / currency / hospital names | grep for literals in Blade and PHP |
| Real-looking patient data in seeders | Seeder contains real names/phones/NIDs |
| N+1 queries | Query counts in list tests |
| Silent catch blocks | `catch (\Throwable) {}` |
| Over-engineering | Repositories/interfaces for a single-use class; new packages without approval |
| Ignores the spec's "Non-goals" | Reimplements something another phase owns |

### 22.6 Report format the AI must end every slice with

```text
SLICE REPORT — P<phase>-S<nn> <title>
1. Existing code inspected / reused (names)
2. Files created / modified
3. Migrations (tables, indexes, FK) + rollback note
4. Routes + permissions added (registry updated: yes/no)
5. Events published / consumed
6. Screens (template T1–T7) + lang keys en/bn
7. Tests written (by pack) + EXACT commands run + results (paste output summary)
8. Security & scope review (what was checked)
9. Known limitations / deferred
10. Module Charter updated: yes/no
```

---

# PART B — PHASE-WISE DEVELOPMENT PLAN

## 23. Roadmap and Sequencing

### 23.1 The order, and why

```text
 DONE                      NOW                  NEXT (clinical completeness)      THEN (money chain)       THEN (people & reach)      LAST (platform)         ANYTIME (as asked)
┌────────────┐          ┌──────────┐         ┌──────┐   ┌──────┐                ┌──────┐ ┌──────┐ ┌──────┐   ┌──────┐ ┌──────┐         ┌──────┐ ┌──────┐ ┌──────┐   ┌─────────────┐
│ Phase 0–11 │ ───────► │ Phase H  │ ──────► │  12  │──►│  13  │ ─────────────► │  14  │►│  15  │►│  21  │──►│  16  │►│  17  │ ───────►│  18  │►│  19  │►│  20  │   │ 22 23 24 25 │
│ (built)    │          │ + PILOT  │         │  ED  │   │Blood │                │Insur.│ │Supply│ │Fin.  │   │ HR   │ │Portal│         │Interop│ │ BI  │ │ AI  │   │             │
└────────────┘          └──────────┘         └──────┘   └──────┘                └──────┘ └──────┘ └──────┘   └──────┘ └──────┘         └──────┘ └──────┘ └──────┘   └─────────────┘
```

| Step | Why here |
|---|---|
| **H first** | Phases 12–20 will copy whatever patterns exist. Fixing the rails once (Observation, Alert, Charge, Stock, Registry, Numbering, Bangla) is cheaper than fixing nine more modules later. |
| **Pilot after H** | Phases 0–11 already cover a complete OPD + IPD + diagnostics + pharmacy + billing hospital. Real users find problems that no specification can. **Start the pilot while you build Phase 12.** |
| **12 Emergency, then 13 Blood Bank** | ED is the busiest door of a hospital and feeds IPD/ICU/OT; Blood Bank is required by ED (massive transfusion), OT and ICU, and the earlier phases already carry "interface + null adapter" hooks for it. |
| **14 Insurance, 15 Supply Chain, 21 Finance** | These three form one chain: claims and supplier invoices become receivables and payables. Building them together lets one accounting model absorb both. 21 follows 15 because three-way matching produces the first Accounts-Payable documents. |
| **16 HR/Payroll after Finance** | Payroll posts journals to Finance; the provider↔employee link (D-12) is made here. |
| **17 Portal after the money and people chain** | The portal shows invoices, claims, appointments and results — all must exist and be stable. |
| **18 → 19 → 20 last, in that order** | Interoperability needs stable domain events; BI needs all facts (and Finance); AI needs governed data and the Phase 19 datasets. Each is *read-mostly* and must never become a source of truth. |
| **22–25 on demand** | Take them when a hospital asks, a licence inspection needs them, or you decide to sell hosted. |

**Allowed parallel work** (only if you have a second developer or a second AI session on a separate branch):

| Pair | Condition |
|---|---|
| 12 ∥ 14 | Different tables; agree the `ChargeEvent` and `Coverage` contract first. |
| 13 ∥ 15 | Blood units never use ordinary stock, so there is no overlap. |
| 15 ∥ 16 | After H-06 (Stock Ledger) and D-12 (provider link) are fixed. |
| 18 ∥ 19 | After 17; 19 reads *events/tables*, 18 reads *services*. |

Never run two phases that both change Billing, Observation, Alert or Patient contracts at the same time.

### 23.2 Milestones

| Milestone | Contents | You can say… |
|---|---|---|
| **M0 — Guideline adopted** | This file in `docs/`, `CLAUDE.md` updated, registries started | "Everyone builds to one set of rules." |
| **M1 — Standardized core** | Phase H complete, audit of 0–11 closed | "Phases 0–11 are consistent and pilot-ready." |
| **M2 — Pilot live** | One hospital using 0–11 (see 21.7); weekly feedback review | "A hospital runs on it." |
| **M3 — Clinical complete** | 12 + 13 | "Every patient door and the blood chain are covered." |
| **M4 — Money chain complete** | 14 + 15 + 21 | "Revenue, claims, purchasing, payables and books are connected." |
| **M5 — Workforce and reach** | 16 + 17 | "Staff are paid from the system; patients self-serve." |
| **M6 — Platform** | 18 + 19 + 20 | "National exchange, executive analytics, governed AI." |
| **M7 — Extended hospital** | 22–25 as demanded | "Facility, quality, specialty care, multi-customer SaaS." |

### 23.3 How big is the work

Slice counts in this plan, **not counting S00 Inspect & Map** (a *slice* is one testable AI-sized unit — chapter 22.4):

| Phase | H | 12 | 13 | 14 | 15 | 21 | 16 | 17 | 18 | 19 | 20 | 22–25 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Slices | 15 tasks | 12 | 13 | 13 | 13 | 12 | 12 | 13 | 12 | 12 | 12 | 6–10 each |

> **Estimate method.** I cannot see your team's real speed, so I give no calendar dates. Use this instead: *measure your own velocity during Phase H* (slices finished per week, including review and fixes), then multiply. As a rule of thumb for planning, allow **2–4 working days per slice** for one developer who reviews AI output carefully, and **add 25 % for the phase gate (testing, UAT scripts, documentation)**. Write your measured figure in the status board (28.4); re-forecast after every phase.

### 23.4 Rules of the road

1. **One active phase at a time** (plus the allowed parallel pairs above).
2. **A phase is not "done" until its gate (Appendix F-2) passes.** A half-finished phase is worse than a missing one — unfinished integration hooks rot.
3. **Every phase starts with an Inspect & Map slice** (S00) and ends with a Hardening slice. Both are in every table below.
4. **Pilot feedback outranks the roadmap.** A patient-safety, money or privacy defect found in the pilot interrupts the current phase.
5. **Do not skip ahead** to Phase 20 (AI) because it is exciting. It has the least value without clean data from 12–19.

---

## 24. Phase H — Standardization Sprint and Pilot Readiness

**Goal.** Put the shared rails under Phases 0–11 and make the product pilot-ready, *without changing what users already do*. Mostly additive: new tables, new services, new tests, new lang files; existing screens change only where a standard requires it.

**Duration.** Two to four weeks of focused AI-assisted work (calibrate with 23.3). **Exit:** Gate H (24.3).

**Ground rules for Phase H**

* Additive first; **no big-bang rewrites**. Where a service replaces old behaviour (Observation, Alert, Charge), use *read-through first*, *dual-write second*, *switch third*, *back-fill last* — and only the first step is mandatory in Phase H.
* Every task ends with a test pack and a note in the module charter.
* Do the **audit (chapter 25) first**; it tells you what H-03…H-06 actually need to do in *your* code.

### 24.1 Task list

Recommended order: **A (audit) → H-01 → H-02 → H-13 → H-12 → H-03 → H-04 → H-05 → H-06 → H-07 → H-08 → H-09 → H-10 → H-11 → H-14 → H-15 → Gate H.**

| ID | Task | What it delivers | Done when |
|---|---|---|---|
| **A** | **Conformance audit of Phases 0–11** (chapter 25) | `docs/audit/phase-0-11-conformance.md` with every deviation ranked Blocker / Fix-when-touched / Accept | You have read it and decided each item |
| **H-01** | **Registries and module enablement** | `docs/registry/modules.md`, generated Permission Registry, table-prefix registry, `docs/events.md` (event, producer, consumers, payload), per-organization/hospital **module flags**, **Integration Stub Registry** with null adapters for forward hooks (Blood Bank, Finance, Payer gateway, National exchange, AI provider) | CI test fails when a route permission, table or event is not registered; flags hide menu items and block routes of disabled modules |
| **H-02** | **Numbering Service** | `NumberSeries` (scope: organization/hospital/branch · period: none/year/month · prefix/suffix · padding), `lockForUpdate` allocation inside the caller's transaction, `number_series` + `number_allocations` (audit), gap policy documented (gaps allowed for cancelled drafts; never reuse) | Concurrency test: 50 parallel requests → 50 unique, ordered numbers; all existing documents (MRN, invoice, receipt, lab order, admission, etc.) read their numbers from it or are listed as exceptions |
| **H-13** | **Code System Registry** | `code_systems`, `code_concepts`, `code_maps` (17.2); loaders for ICD-10, ICD-11 MMS, LOINC, ATC, UCUM, DGDA drug list; `display` denormalised on clinical rows | Diagnosis/lab/drug pickers search through the registry; sample ICD-10↔ICD-11 map loads; no invented codes |
| **H-12** | **Public IDs** | `public_id CHAR(26)` (ULID) added to patient-identifying and clinical-event tables of Phases 0–11 (nullable → back-filled → unique), routes/API resources use `public_id` | No numeric id is exposed in any URL or API for those resources; old numeric URLs redirect or are retired |
| **H-03** | **Observation Service** | `clinical_observations` (+ `observation_sets` for panels), `RecordObservation` action, `ObservationReader` query service, unit/range validation by UCUM, "latest vital signs" and "trend" read models. **Step 1 only:** new writes by Phases 12+ and read-through views over `encounter_vitals`, `nursing_vitals`, `icu_vital_signs` | Phase 12 can call the service; a trend chart shows OPD, ward and ICU values from one query; no data moved yet |
| **H-04** | **Alert Service** | `clinical_alerts`, `alert_recipients`, `alert_escalations`; raise → deliver (in-app, SMS, optional call) → acknowledge → escalate → close; escalation ladders as settings; shared alert inbox + bell | Lab critical value, ICU alarm and nursing early-warning alert can be raised through it; acknowledgement latency report exists |
| **H-05** | **Charge Event Publisher** | `ChargeEvent` DTO, key `<MODULE>:<visit_ref>:<service_code>:<source_event_id>`, publisher + Billing listener (idempotent), `charge_event_log` for observability, reconciliation report "events without charge / charges without event" | Phases 3, 5, 6, 7, 8, 9, 10, 11 emit through it (or have a documented adapter); duplicate publish creates one charge |
| **H-06** | **Stock Ledger Contract** | `StockPosting` DTO, append-only `stock_ledger` entries posted by pharmacy and ready for Phase 15; consolidated stock view; reversal-only corrections | Pharmacy dispensing posts a ledger row per movement; ledger sums equal on-hand for every pharmacy batch; no `UPDATE`/`DELETE` on the ledger (DB grant + test) |
| **H-07** | **Report and Export Service** | Filtered, paged, **queued** exports (CSV/XLSX/PDF) with permission check, scope, audit, watermark with user + time, expiry of generated files | Every list screen's Export goes through it; large exports do not block the web request |
| **H-08** | **Document and Print Service** | Template engine for receipt (80 mm), invoice (A4), prescription (A5), lab/radiology report, consent forms, wristbands/labels; English + Bangla; QR/barcode; letterhead per hospital; print audit | **Bangla PDF test passes** (conjunct letters, numerals, long names) on all templates; templates editable by admin without code deploy where safe |
| **H-09** | **Global Search and Barcode** | Top-bar search (Ctrl+K/F3) over MRN, phone (normalised), name (Bangla/English), NID, barcode; Code128/QR generation and scan resolver | Search p95 ≤ 500 ms on 100 k seeded patients; scanning a wristband, specimen label or drug pack resolves to the right record |
| **H-10** | **Bangla localization** | Complete `lang/bn` for all screens of Phases 0–11, language switch per user, Bangla numerals option, Bangla SMS templates, `bn` parity test in CI, self-hosted Bangla font | Parity test green; a Bangla-only user can complete the 15 critical journeys |
| **H-11** | **Import Service and demo data** | Staged import (upload → validate → preview → commit → error file → rollback of a batch) for patients, services & prices, drugs, tests, users, beds, opening stock; **demo data seeder** with Bangla names (clearly fake) | A 5 000-row patient file imports with duplicate detection and a downloadable error report; training organization can be rebuilt in one command |
| **H-14** | **Role home screens and UX conformance** | Role-based landing worklists (19.2 rule 2), patient banner component, standard status badges, seven screen templates as Blade components (T1–T7), the keyboard/scanner behaviours, empty/loading/error states | The five busiest screens (registration, appointment queue, OPD consultation, billing counter, pharmacy dispense) pass the UX acceptance list (19.8) with 3 real users each |
| **H-15** | **Pilot kit** | Training mode org + banner, role quick-start cards (Bangla/English), downtime forms and **census print job**, backup + **restore drill** documented and performed, monitoring + alerting baseline (21.5), security baseline checklist (16.3) and penetration-test booking, user manual skeleton, support/escalation sheet | Restore drill signed; downtime forms printed and tested once; pilot go/no-go checklist (Appendix F-4) all green |

### 24.2 How to prompt H tasks

Use the **Phase H task prompt** in Appendix D-6. The shape is always: *inspect → show me the gap list → wait for my approval → build the additive service → add tests → adapter for one existing module only → report.* Never ask the AI to "refactor everything to use the new service" in one go.

### 24.3 Gate H (exit criteria)

```text
[ ] Audit (chapter 25) closed: no Blocker remains open
[ ] H-01…H-15 done-when conditions met
[ ] The 15 critical journeys (20.3) pass in CI on seeded data
[ ] Scope test pack P1 and authorization matrix P2 pass for ALL routes of Phases 0–11
[ ] A fresh install (migrate + seed + demo data) works from an empty machine using the README only
[ ] Restore drill performed; downtime forms printed
[ ] CLAUDE.md contains Appendix E rules; module charters exist for Phases 0–11
[ ] Guideline version recorded; open decisions OD-01…OD-12 (28.6) reviewed by the owner
```

---

## 25. Conformance Audit of Phases 0–11

You told me phases 0–11 are built; I have not seen the code. This chapter lets an AI tool (read-only!) find out how close the code is to this guideline in a few hours.

### 25.1 Method

1. Run the **audit prompt** (Appendix D-1). It must not change files. It writes `docs/audit/phase-0-11-conformance.md`.
2. For every finding the AI records: *rule → evidence (file/line, table) → severity → suggested fix → effort*.
3. You triage with this table:

| Severity | Meaning | Action |
|---|---|---|
| **Blocker** | Patient-safety, money, privacy, tenancy leak, or a duplicate store that Phase 12+ would copy | Fix in Phase H before the pilot |
| **Fix when touched** | Naming, structure, missing tests, cosmetic inconsistencies | Fix the next time the module is edited; recorded in the module charter |
| **Accept** | Different but harmless | Record as an **exception** (`D-E01`, …) in `docs/decisions/` with the reason |

### 25.2 Checklist the audit covers

| # | Rule (chapter) | How to check | Typical evidence |
|---|---|---|---|
| 1 | Tenancy columns on every table class (6) | Compare all tables to the P/O/H/B/C classes; list tables without `organization_id` that should have it | Migration list |
| 2 | Global scope + policy on every model/action (6.3–6.5) | grep models without the scope trait; `withoutGlobalScopes` usage | Code |
| 3 | Scope tests T-S1…T-S8 exist (6.7) | Search `Tests/Security` | Tests |
| 4 | Permission naming and registry (D-06, 8.1–8.2) | `php artisan permission:show`/seeders vs routes; routes with no `can:` middleware | Seeders, routes |
| 5 | Table and class naming per module registry (8) | List tables by prefix | DB |
| 6 | Module anatomy (7) | Folder inspection; controllers > 60 lines; business logic in controllers/Blade | Code |
| 7 | Actions with `DB::transaction` + after-commit events (7.3) | Inspect action classes of billing, pharmacy, IPD | Code |
| 8 | Money types and rounding (9.3) | Columns typed `float/double`; rounding code | Migrations |
| 9 | Numbering via sequences (8.3) | grep `count() + 1`, `max(id)`, `Str::random` numbers | Code |
| 10 | Time storage UTC / display Asia/Dhaka (9.2) | `config/app.php`, casts, any `now()` string formatting | Config |
| 11 | Immutability of final records (10.1) | UPDATE/DELETE paths on final lab results, signed notes, finalised invoices | Code, tests |
| 12 | Soft delete vs append-only (9.5) | Clinical/financial tables with `deleted_at` that are really deleted by users | Models |
| 13 | Audit coverage (16.5) | Compare audit event list to sensitive actions | Audit table |
| 14 | Vital sign stores (D-08) | Count tables storing vitals | DB |
| 15 | Alert stores (D-09) | Alert tables in Lab, Radiology, Nursing, ICU | DB |
| 16 | Chargeable events (D-10) | `*_chargeable_events` tables, key formats, idempotency | DB |
| 17 | Stock stores (D-11) | Pharmacy stock vs ledger | DB |
| 18 | Provider vs employee (D-12) | `providers` columns; link field | DB |
| 19 | API envelope, status codes, idempotency (15) | Route list; controller responses | Routes |
| 20 | Auth stack (D-05) | `composer.json` (Sanctum/Passport) | Composer |
| 21 | Public IDs (9.1) | Routes using numeric ids for patient resources | Routes |
| 22 | Language files and hard-coded strings (19.4) | Missing `bn`; literals in Blade | Lang |
| 23 | Tests: counts per module, security tests, concurrency tests (20) | Test inventory | Tests |
| 24 | Static analysis / style in CI (20.4) | CI config | CI |
| 25 | Backup/restore, monitoring (21) | Ops docs | Ops |
| 26 | Seeders contain no real personal data | Seeder review | Seeders |

### 25.3 Where deviations most likely hide (from the specs)

| Phase | Check specially |
|---|---|
| 0 Foundation | Sanctum vs Passport; permission naming style; audit table immutability; settings for password/MFA/session; module flags exist? |
| 1 Patient | `public_id`; identifier table has `system`; NID/BRN encryption; duplicate rules for normalised mobile; guardian model; unknown-patient (temporary MRN) support needed by Phase 12 |
| 2 Appointment | `providers` table vs HR; token/queue number series; double-booking lock; reminders via Notification Service |
| 3 OPD | Vitals store; diagnosis `code_system`/ICD-11; prescription ownership vs Phase 7; order → lab/radiology/pharmacy contracts; note immutability and addendum |
| 4 Billing | Charge-event intake and idempotency; `currency_id`; refund/discount approvals; cashier close; VAT/tax fields; number series; payer-split fields ready for Phase 14 |
| 5 Laboratory | Critical value alert path; result amendment vs overwrite; LOINC; analyzer adapter interface; specimen barcode |
| 6 Radiology | Critical finding alert; DICOM/PACS adapter interface; report amendment history; empty spec file (ignore) |
| 7 Pharmacy | Stock ledger append-only; FEFO; narcotics register; prescription verification; returns/reversals; brand-vs-generic data; DGDA fields |
| 8 IPD | Bed state machine concurrency; admission charges (bed day) via charge events; discharge clearance hooks; bed categories (ward/cabin) |
| 9 Nursing | Vitals store; MAR with five rights and barcode; handover; alerts; forward hook to blood transfusion monitoring |
| 10 OT | WHO checklist immutability; consumables posting; implant traceability; blood request hook |
| 11 ICU | Vitals/I-O store; alarms → Alert Service; scores calculated server-side; ventilator data as observations |

### 25.4 Output of the audit that drives Phase H

* A list of **Blocker** items → become sub-tasks of H-01…H-12.
* A list of existing table names to enter into the registry (H-01).
* A list of **exceptions** → `docs/decisions/D-Exx.md`.
* Real numbers for the status board: tables, routes, permissions, tests per module.

---

## 26. Phases 12–20: Slice Plans

### 26.0 How to read the slice tables

* **Spec §** points into that phase's own specification file, so you can give the AI *only those sections* plus the guideline chapters in "Also apply".
* The numbers use each file's own section numbering (for example `Phase 12 §9–11`).
* **S00** is always "Inspect & Map" (read-only report, you approve) and the last slice is always "Hardening & Gate".
* "Done when" is the **acceptance test you run**, not the AI's claim.
* **Guideline overrides** list where this document differs from, or tightens, the phase spec. Where they disagree, the guideline wins (precedence rule).
* Every phase also includes: Module Charter, permission + route + event registry updates, `en` + `bn` language keys, UX acceptance (19.8) for its screens, and the standard test packs (20.2).

---

### 26.1 Phase 12 — Emergency Department

**Spec:** `Phase 12 — Emergency Department …` (80 sections) · **Depends on:** 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 · **Hand-offs:** 13 (blood), 14 (claims), 17 (ED info), 19 (ED facts) · **Also apply:** ch. 10 (clinical safety), 11 (billing), 13, 19 (T6 Board)

**Guideline overrides**

1. Triage vitals and ED monitoring are written through the **Observation Service** (H-03) — no `ed_monitoring_records` copy of vital values; the ED table keeps only *context* (who monitored, which zone, review status).
2. Critical alerts (deterioration, critical lab/radiology, door-to-doctor breach) use the **Alert Service** (H-04).
3. Every billable ED action publishes a **ChargeEvent** with module code `ED` (H-05).
4. Blood requests and "emergency blood release" go through a **BloodBankGateway interface** with a null adapter until Phase 13.
5. New tables use `public_id` ULID.
6. ED tracking board is the first **Vue island** (D-15) — keep it a single component with a JSON polling endpoint (5–10 s).
7. Unknown patient: temporary identity is created in Patient/MPI (Phase 1) as `identity_status = temporary`; merge happens only through the Phase 1 merge workflow. ED never creates its own patient table.
8. Triage scale (ESI, MTS, CTAS, or a local 5-level) is a **setting**, not code.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P12-S00 | Inspect & Map report | 78 steps 1–2 | Report approved |
| P12-S01 | ED configuration: zones, bays, triage scale setting, ED roles/permissions, module flag, menu | 3–5, 53, 54, 77 | Zones/bays configurable; permissions in registry |
| P12-S02 | Arrival, rapid registration, **unknown patient**, patient identification, wristband | 6–8, 70, 71 | Unknown male registered in < 60 s; later merged to a known patient with no data lost |
| P12-S03 | Triage assessment + reassessment | 9–11, 52 | Triage level set; vitals recorded via service; re-triage timer alerts |
| P12-S04 | Queue, priority rules, **tracking board** (T6) | 12, 13, 46 | Board refreshes ≤ 10 s; priority order obeys rules; no flicker losing selection |
| P12-S05 | ED encounter, rapid assessment, team assignment | 14, 15, 18, 51 | Encounter linked to Phase 3 encounter model (no duplicate); team shown on board |
| P12-S06 | Resuscitation record, **Code Blue** link, monitoring, critical alerts | 16, 17, 31, 32 | Resus timeline captured with times; Code Blue notifies the team; alert ack tracked |
| P12-S07 | Orders and integrations: lab, radiology, pharmacy, ED medication administration, procedures | 19–24 | Orders go through existing Phase 5/6/7 contracts; results return to ED view |
| P12-S08 | Trauma (body map), stroke, ACS, sepsis foundations; mass-casualty foundation | 25–29, 69 | Protocol timers and checklists run; MCI mode raises triage counts, no new patient table |
| P12-S09 | Consultations + SLA, observation unit | 30, 33, 34 | Consult SLA breach alert; observation stay billed per rule |
| P12-S10 | Dispositions: surgery, ICU, IPD admit, referral/transfer, ambulance, LWBS, LAMA, discharge, **death** | 35–43 | Each disposition closes the ED episode correctly and triggers the receiving module; death follows Phase 8/10 death-documentation rules |
| P12-S11 | Billing events, dashboards, workspaces (nurse, physician, patient), reports | 44, 45, 47–49, 72 | ED visit produces correct charges once; KPIs (door-to-triage, door-to-doctor, LOS, LWBS %) shown |
| P12-S12 | Hardening & Gate: break-glass, concurrency, security, FHIR/HL7/AI readiness, tests, docs | 59–68, 74–76, 79 | Gate F-2 passed; 15 journeys + ED journey 14 pass |

**ED-specific acceptance:** two staff cannot take the same bay; a patient can never be "lost" between zones (every active patient is on the board); a dispositioned patient leaves the active board exactly once.

---

### 26.2 Phase 13 — Blood Bank and Transfusion

**Spec:** `Phase 13 — Blood Bank …` (100 sections) · **Depends on:** 1, 3, 4, 5, 8, 9, 10, 11, 12 · **Hand-offs:** 14 (billing), 15 (reports only), 19 · **Also apply:** ch. 10, 11, 12.1 (blood units are not stock)

**Guideline overrides**

1. **Traceability is the product.** Every blood unit has a unique identity (ISBT-128-style donation number where available, otherwise a hospital series), and the chain *donor → donation → components → tests → storage → request → crossmatch → issue → transfusion → outcome* is queryable in both directions (lookback).
2. Blood units live in `blood_units`; they **never** appear in ordinary stock reports, but quantity summaries post to the consolidated stock *view* only (D-11).
3. Infectious-disease and grouping tests are **LIS orders** (Phase 5) with results flowing back — no second result store. Mandatory screening list is a **configurable policy** (P-24, verify with national blood policy).
4. Bedside verification uses **barcode of patient wristband + blood bag** and a **two-person check**; manual override requires reason and creates a safety event.
5. Temperature monitoring accepts device readings through the Observation Service (`code_system = LOINC/local`) and alerts via the Alert Service.
6. Replace the placeholder hooks from Phases 9–12 (null adapter → real adapter) in slice S12.
7. Replacement-donor practice (P-23) is supported as a workflow flag on the blood request, not a different module.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P13-S00 | Inspect & Map | 98 steps 1–2 | Approved |
| P13-S01 | Configuration and master data: blood groups, components, storage locations, policies | 4–7, 30, 73, 74 | Masters seeded; permissions registered |
| P13-S02 | Donor registry, identification, consent, eligibility screening, deferral | 8–13 | Deferred donor cannot proceed; rule set editable |
| P13-S03 | Donation, bag identification, collection, post-donation observation, donor adverse events | 14–19 | Unique donation number via Numbering Service; adverse event recorded |
| P13-S04 | Component processing, lifecycle, **quarantine** | 20–22, 28 | Unit stays quarantined until tests released it |
| P13-S05 | Testing integration: infectious disease, grouping, verification | 23–26 | Positive screen → unit blocked; discordant group → hold; results come from LIS |
| P13-S06 | Inventory, status, storage, temperature monitoring + excursion, expiry | 27, 29, 31–33, 55, 56 | Excursion alert fires and affected units flagged; FEFO view |
| P13-S07 | Blood requests, priority, emergency request, patient sample | 34–36, 39, 58 | Request linked to encounter; wrong-sample rejection |
| P13-S08 | Compatibility testing, crossmatch, reservation | 37, 38, 40 | Incompatible unit cannot be reserved; reservation expires and releases |
| P13-S09 | Issue, traceability, return, discard/wastage | 41, 42, 51, 52 | Unit cannot be issued twice; return rules (temperature/time) enforced |
| P13-S10 | Transfusion: bedside verification, record, monitoring, reactions, incident and near-miss | 43–50 | Reaction stops transfusion workflow and notifies blood bank + doctor; incident report created |
| P13-S11 | Recall and **lookback**; utilization and wastage reports; massive transfusion; emergency release | 53, 54, 57, 59, 60, 89 | Lookback from a donor lists every recipient; MTP pack issue flow |
| P13-S12 | Integrations (ED, ICU, OT, IPD, Nursing, LIS, billing, insurance) replacing null adapters | 61–68 | Each hook proven by an integration test |
| P13-S13 | Dashboard, workspaces, hardening & Gate | 79–88, 90–99 | Gate F-2 passed |

**Blood-specific acceptance:** you can answer "who received units from donor X?" and "which donors gave unit Y?" in < 2 s each; a unit past expiry cannot be issued under any role.

---

### 26.3 Phase 14 — Insurance and Corporate Claims

**Spec:** `Phase 14 — Insurance & Corporate Claims …` (100 sections) · **Depends on:** 1, 3, 4, 7, 8, 10, 12, 13 · **Hand-offs:** 21 (receivables), 17 (patient insurance view), 19 · **Also apply:** ch. 11, 17.5 (PayerGateway adapter)

**Guideline overrides**

1. **Billing owns charges and prices; Insurance never re-prices.** Claim lines are built from finalised Billing lines; co-pay/deductible split is calculated by the Phase 14 engine and returned to Billing as *patient-share vs payer-share* postings.
2. "Payer" covers **private insurers, corporate accounts, TPAs, government/NGO/charity schemes, hospital panels** (P-17). All use the same claim state machine.
3. Receivable posting to the General Ledger is a **FinanceGateway interface with null adapter** until Phase 21; claim aging reports work without Finance.
4. External submission uses a **PayerGateway adapter** (portal upload, email, API, EDI later); a manual "file export + mark submitted" adapter ships first, because most local payers do not offer APIs (P).
5. Diagnosis and procedure codes come from Phase 3/10 coded fields (ICD-10/ICD-11 per payer requirement).
6. Claim documents live in the shared File Service with C3 classification and break-glass rules.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P14-S00 | Inspect & Map (billing payer fields, existing insurance flags) | 97 steps 1–4 | Approved |
| P14-S01 | Payer, plan, corporate client masters; provider contracts | 5–7, 52 | Payer with contract effective-dated rates |
| P14-S02 | Patient policy, subscriber, dependents | 8, 9 | Policy attached to patient with validity and member numbers |
| P14-S03 | Coverage rules, benefit limits, exclusions, deductible/co-pay engine | 11–14, 85 | Golden-file tests of split calculations |
| P14-S04 | Eligibility verification (manual + adapter) | 10 | Visit shows eligibility status and who verified |
| P14-S05 | Pre-authorization, status, utilization | 15–17 | Authorized amount consumed by charges and capped |
| P14-S06 | Claim generation: header, lines, coding, package billing | 18–20, 41–43, 62 | Claim from a finalised visit; lines tie to invoice lines |
| P14-S07 | Claim state machine, validation, scrubbing, review workflow | 21–23, 56, 57, 86 | Illegal transitions rejected; scrub errors listed with fix hints |
| P14-S08 | Submission adapters, tracking, claim documents + checklist | 24–27, 50, 78 | Batch file exported; submission recorded with reference |
| P14-S09 | Queries, rejections, denials, resubmission, appeals | 28–32 | Denial reason codes drive worklist; resubmission versions kept |
| P14-S10 | Remittance, reconciliation, partial settlement, patient responsibility, COB | 33–35, 39, 40 | Payment matched to claims; write-offs need approval |
| P14-S11 | Corporate credit billing and batches | 36–38, 63 | Corporate monthly statement from batch; credit limit blocks |
| P14-S12 | Module integrations (IPD, ED, OT, pharmacy, lab/radiology, blood), aging, dashboard, reports | 44–49, 68–72 | Aging buckets; claim timeline complete |
| P14-S13 | Hardening & Gate: idempotency, concurrency, privacy, FHIR/HL7/AI readiness | 53, 58–61, 73–77, 79–83, 88–93 | Gate F-2 passed |

**Claim-specific acceptance:** payer share + patient share = invoice total to the paisa, always; a claim cannot be edited after submission except via a new version.

---

### 26.4 Phase 15 — Inventory, Procurement, Supply Chain

**Spec:** `Phase 15 — Inventory - Procurement - Supply Chain …` (91 sections) · **Depends on:** 0, 4, 7, 14 · **Hand-offs:** 21 (payables, budgets), 16, 19 · **Also apply:** ch. 12 (Stock Ledger), 11

**Guideline overrides**

1. **Specialized stores stay specialized**: pharmacy keeps dispensing stock (Phase 7), Blood Bank keeps blood units (Phase 13). Phase 15 owns *enterprise* stores (general, surgical consumables, linen, stationery, housekeeping, equipment spares). All post to the **Stock Ledger Contract**; Phase 15 gives consolidated read-only stock/valuation reports, and may *supply* pharmacy through stock transfers.
2. The Item Master here is for non-drug supplies; **drugs remain in the Phase 7 drug master** linked by `item_id` where pharmacy purchasing flows through procurement (use a shared `procurement_items` bridge, no copy of drug master).
3. Three-way matching (PO ↔ GRN ↔ supplier invoice) is done here; the resulting payable is handed to Finance through `FinanceGateway` (null adapter until 21).
4. Consumption by patient care (e.g. OT consumables, dressings) uses the **Consumption + ChargeEvent** path (spec §80–81) — do not post prices from inventory.
5. Supplier documents (trade licence, drug licence, VAT/BIN certificate) with expiry alerts (P-14, C).
6. Emergency procurement is a controlled workflow with after-the-fact approval, never a bypass.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P15-S00 | Inspect & Map | 88 steps 1–4 | Approved |
| P15-S01 | Item master, classification, UOM, batch/lot, serial | 6–10 | Item with UOM conversions; batch/expiry rules |
| P15-S02 | Suppliers: qualification, documents, evaluation | 11–14 | Disqualified supplier blocked from PO |
| P15-S03 | Warehouses, locations, stock model, **ledger**, balances | 29–32, 62, 63 | Balance equals ledger sum (test) |
| P15-S04 | Requisition, status, approval workflow | 15–17, 64, 71 | Multi-level approval by amount |
| P15-S05 | RFQ, quotations, comparison | 18–20 | Winning quote converts to PO |
| P15-S06 | Purchase order and lines, approval, numbering | 21–23, 65 | PO issued; amendments versioned |
| P15-S07 | Goods receipt, partial receiving, quality inspection, rejected goods | 24–28, 66 | Partial GRNs close PO lines correctly; rejects quarantined |
| P15-S08 | Internal requisition, issue, transfer, returns | 33–37 | Transfer is atomic (out + in); in-transit state |
| P15-S09 | Expiry, FEFO/FIFO, counts, cycle counts, adjustments, quarantine, recall | 38–42, 78, 79 | Count variance posts adjustment only with approval |
| P15-S10 | Valuation, procurement cost, supplier returns, contracts, three-way matching, budget check | 43–49, 67 | Matching tolerance configurable; mismatches block payment |
| P15-S11 | Emergency procurement, consignment, VMI, min/max, reorder suggestions | 51–55 | Reorder list generated from consumption |
| P15-S12 | Consumption integration + chargeable events; dashboard, reports, analytics | 57–60, 80, 81, 87 | Consumption by department report; charge event idempotent |
| P15-S13 | Hardening & Gate: reservation, concurrency, idempotency, security, tests | 72–77, 82–86, 88–90 | Gate F-2 passed |

---

### 26.5 Phase 16 — HR, Workforce and Payroll

**Spec:** `Phase 16 — HR - Workforce - Payroll …` (112 sections) · **Depends on:** 0, 2, 15 (shared warehouse of assets optional), 21 (journals — through `FinanceGateway`) · **Hand-offs:** 17 (ESS), 19, 21 · **Also apply:** ch. 16 (privacy), 18 (P-10, P-14, P-22)

**Guideline overrides**

1. **Provider link (D-12):** create `employees` as the master, then add `providers.employee_id` and back-fill by matching name + registration number with a **review screen** (never auto-merge ambiguous matches).
2. **Payroll rules are configuration** — pay components, tax slabs (AIT/TDS), provident fund, festival bonus, overtime rate, leave encashment, are **effective-dated settings** (spec §101) and are flagged "verify with accountant/lawyer" (C). No rate or slab is hard-coded.
3. Payslips and salary data are **C4**; salary visible only to HR-payroll roles; auditors get masked data.
4. Biometric devices connect through a `BiometricGateway` adapter with raw punch storage + processing job (spec §62–65); the system must keep working when devices are offline and sync later (P-22).
5. Payroll is **locked** after approval; corrections are made as supplementary payroll runs, never edits (spec §49, 76).
6. Credential expiry (BMDC/BNMC/pharmacy council, BLS/ACLS) alerts and **blocks scheduling** to clinical duties after expiry only if the hospital sets that policy.
7. Employee self-service uses the staff login; patient portal accounts are separate (chapter 14).

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P16-S00 | Inspect & Map | 110 steps 1–3 | Approved |
| P16-S01 | Employee master, numbering, lifecycle, categories, org assignment, history + **provider link** | 6–10, 14, 15 | Provider↔employee review screen done |
| P16-S02 | Jobs, positions, contracts, documents, credentials | 11–13, 16–18, 108 | Expiry alerts for licences |
| P16-S03 | Recruitment foundation, onboarding, offboarding | 19–21 | Offboarding checklist disables access |
| P16-S04 | Shifts, shift groups, roster, holiday calendar, staffing | 22–26, 37 | Roster conflict detection |
| P16-S05 | Attendance architecture, biometrics, calculation, regularization | 27–32, 62–65 | Offline device punches sync and recompute |
| P16-S06 | Leave policy engine, balances, workflow | 33–36 | Balance accrual tests; approval chain |
| P16-S07 | Overtime and rules | 38, 39 | OT hours feed payroll with caps |
| P16-S08 | Compensation: structures, components, calculation methods, benefits, loans/advances, dependents | 40–43, 52–54 | Structure versioning by effective date |
| P16-S09 | Payroll engine: period, processing, calculation, versioning, approval, locking | 44–49, 61, 76, 106, 107 | Reproducible results; exception dashboard |
| P16-S10 | Payslips, payment files (bank/mobile wallet), accounting hand-off | 50, 51, 66, 79, 94 | Bank advice file; journal via FinanceGateway |
| P16-S11 | Self-service (employee/manager), performance, training, skills | 55–59 | Employee sees own payslip only |
| P16-S12 | Dashboards, reports, compliance, data migration, hardening & Gate | 80–82, 100–105, 111 | Gate F-2 passed; payroll reconciliation test (106) |

---

### 26.6 Phase 17 — Patient Portal, Mobile and CRM

**Spec:** `Phase 17 — Patient Portal - Mobile - CRM …` (118 sections) · **Depends on:** 1–4, 5–8, 12, 14, 15 (none strictly), 21 (online payments reconcile) · **Hand-offs:** 19, 20 · **Also apply:** ch. 15, 16.6, 19, 18 (P-15, P-16)

**Guideline overrides**

1. The portal **never owns clinical or billing data** — it reads **projections** (spec §63) and calls existing Actions for appointments and payments. No portal-side copy of results.
2. **Clinical release rules** (spec §19): results go to the patient only after the hospital's release policy (immediate, after clinician review, or delayed for sensitive tests). Policy is configurable per test/category.
3. Patient accounts are a **separate authentication guard** from staff; Sanctum tokens issued for the mobile app (D-05). OTP via SMS in Bangla, with rate limits and fraud controls (spec §81–82).
4. Online payments go through the **PaymentGateway adapter** (bKash/Nagad/card aggregator) and post to Billing; reconciliation report vs settlement file.
5. **Telehealth** is a provider abstraction only here (spec §51–52); the real workflow belongs to Phase 24.
6. Low-bandwidth design: server-rendered light pages, small images, no heavy JS bundles; the mobile API supports pagination and ETag caching.
7. CRM messaging obeys **consent per channel and purpose** (16.6) and quiet hours; marketing campaigns never use clinical diagnoses as segments unless a documented consent exists (C4).
8. AI chatbot is a *later* capability (Phase 20); here only the extension point.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P17-S00 | Inspect & Map | 116 steps 1–3 | Approved |
| P17-S01 | Portal architecture, patient account, registration, identity verification | 2–9, 66, 67 | Self-registration matches existing patient only after OTP + data match; no patient enumeration |
| P17-S02 | MFA, devices, sessions, rate limiting, fraud protection | 10–12, 78–82 | Lockout and device list work |
| P17-S03 | Dashboard, Patient 360 digital view, appointments, digital queue | 13–17, 62, 110 | Booking uses the same slot lock as staff |
| P17-S04 | Clinical records, release, lab and radiology results, critical-result handling, PACS viewer link | 18–23 | Withheld results invisible until release; critical result never silently auto-released |
| P17-S05 | Prescriptions, pharmacy, medication safety | 24–26 | Refill request goes to staff review |
| P17-S06 | Billing, online payment, payment workflow, insurance | 27–30, 111 | Payment idempotent; receipt printable |
| P17-S07 | Admission/discharge and ED info, documents, secure delivery | 31–34, 113 | Document links expire; downloads audited |
| P17-S08 | Consents, communication preferences, notification center, push | 35–37, 56, 68, 69, 90, 91, 102 | Opt-out respected on every channel |
| P17-S09 | Messaging and clinical-messaging boundary, service requests | 38–41, 112 | Urgent-symptom disclaimer and routing; messages not clinical orders |
| P17-S10 | Family, proxy, minor/dependent access | 53–55 | Proxy sees only what guardian rules allow; audited |
| P17-S11 | CRM core: interactions, journey, segmentation, campaigns, automation, assignments, feedback, complaints, satisfaction, digital forms | 42–50, 70, 71, 84, 101, 114 | Campaign respects consent and quiet hours |
| P17-S12 | Mobile API versioning, offline support, localization, accessibility, export/closure/retention, dashboards, hardening & Gate | 57–60, 85–89, 92–100, 103–109, 117 | Gate F-2 passed; critical security test (79) passes |
| P17-S13 | Telehealth abstraction, AI extension points, documentation | 51, 52, 72–77, 115 | Interfaces and fakes only |

---

### 26.7 Phase 18 — Interoperability (FHIR / HL7 / DICOM)

**Spec:** `Phase 18 — Interoperability …` (76 sections) · **Depends on:** all domain modules; **Hand-offs:** 17 (FHIR for apps), 19 · **Also apply:** ch. 17 (the standards table and mapping), 15, 16.6

**Guideline overrides**

1. FHIR responses at `/api/v1/fhir/*` are native FHIR with `OperationOutcome` errors; HL7 listener answers with ACK/NACK (D-07).
2. **Profiles:** validate resources against the **Bangladesh Core FHIR IG** (DGHS) where it defines a profile; otherwise base R4. Keep a `profiles` setting per exchange partner.
3. Terminology service uses the **Code System Registry (H-13)** — no second terminology store.
4. FHIR is an **adapter over domain services**, not a second database. Mappers live in `Interop/Mappers/<Resource>` and are unit-tested with golden JSON.
5. If SMART-on-FHIR/OAuth2 for third-party apps is required, add it here as a scoped decision (D-05); otherwise use Sanctum client tokens with scopes.
6. The **National exchange adapter** (HIE/shared record) ships as a null adapter + test client; real onboarding depends on DGHS processes (C, P-18).
7. HL7 v2 over MLLP is a **separate long-running worker** process with its own supervisor, never inside the web request cycle.
8. Everything is idempotent, retryable, and monitored with a dead-letter queue and replay.

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P18-S00 | Inspect & Map events and services | 73 steps 1–3, 3 | Approved |
| P18-S01 | External system registry, endpoints, credentials, certificates | 6–8, 37, 38 | Credentials encrypted; rotation without redeploy |
| P18-S02 | Message lifecycle, queues, workers, retry, DLQ, idempotency, monitoring | 28–32, 54–56 | Failed message retried, then dead-lettered, then replayable |
| P18-S03 | Routing engine, adapter pattern, transformation, provenance | 26, 27, 43, 44 | Rule-based routing test |
| P18-S04 | Identifier mapping and terminology mapping | 23–25 | Local↔external IDs resolved both ways |
| P18-S05 | FHIR foundation + Patient, Organization, Practitioner | 9, 10, 12–14 | `GET /fhir/Patient/{id}` validates against profile |
| P18-S06 | FHIR Encounter, Appointment, clinical resources (Condition, Observation, AllergyIntolerance, Procedure) | 10, 11, 13 | Bundles for a visit |
| P18-S07 | FHIR lab/radiology (ServiceRequest, DiagnosticReport, ImagingStudy), medication, billing/insurance resources | 10, 11 | Round-trip tests on golden samples |
| P18-S08 | HL7 v2 parser/generator, ADT, ORM, ORU, ACK, storage | 15–18 | ADT^A01 in → patient/encounter created via domain Actions |
| P18-S09 | DICOM Modality Worklist, DICOMweb, identifiers | 19–22 | MWL entry created from radiology order |
| P18-S10 | Webhooks, domain-event integration, integration workflows | 39–42 | Signed webhook with replay protection |
| P18-S11 | Security, authorization, consent, patient-data protection; admin UI, reports | 33–36, 45, 49–53 | Consent blocks exchange; monitoring screen |
| P18-S12 | Multi-hospital, retention, localization, tests, hardening & Gate | 66, 67, 69–72, 74 | Gate F-2 passed; load test (67) |

---

### 26.8 Phase 19 — BI, Analytics, Data Warehouse

**Spec:** `Phase 19 — BI - Analytics - Data Warehouse …` (83 sections) · **Depends on:** 0–18, 21 · **Hand-offs:** 20 (AI datasets) · **Also apply:** ch. 9, 16.6, 18 (P-18)

**Guideline overrides**

1. **Start small.** Deliver the 25 KPIs a hospital manager and a clinical director actually look at before building every fact table (a KPI catalogue is in the spec §39–40; choose by pilot feedback).
2. The warehouse is a **separate schema/database fed from a replica** (D-04); the operational database is never queried by dashboards for history.
3. Dashboards are built on the **semantic layer** (spec §41); no raw SQL in the dashboard builder.
4. Row-level security follows the same scope as operations (ch. 6) — a branch manager sees only their branch.
5. De-identification for research datasets is a pipeline, and the **re-identification key is never in the warehouse**.
6. Include a **national reporting pack** (HMIS-style aggregates; DHIS2-compatible export) as a report family (P-18, verify requirements).
7. Finance facts use Phase 21 balances; do not recompute accounting.
8. "Natural-language analytics" is last and advisory (shows its SQL/semantic query; cannot write).

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P19-S00 | Inspect & Map; analytics ownership | 82 steps 1–5 | Approved |
| P19-S01 | Architecture: source registry, catalog, staging, warehouse connection, load tracking, failure handling | 3–10, 52, 53, 58–61 | One table loads incrementally with tracked runs |
| P19-S02 | Dimensions: date, time, organization, hospital, patient (SCD), provider, department | 12–16 | Dimensions load and are queryable |
| P19-S03 | Core facts: encounters, appointments, billing and revenue | 17, 18, 20, 21, 31, 32 | Revenue fact reconciles to Billing totals |
| P19-S04 | Diagnostics and pharmacy facts | 22–24 | Turn-around-time KPIs |
| P19-S05 | IPD, OT, ICU, ED facts | 25–28 | Occupancy, LOS, door-to-doctor |
| P19-S06 | Blood bank, insurance, inventory/procurement, HR, portal/CRM, interoperability facts | 29, 30, 33–37 | Claim aging, stock-out rate, attendance rate |
| P19-S07 | Data quality framework, rules, dashboard | 49–51 | Failing rule creates a visible issue |
| P19-S08 | KPI engine and semantic layer | 39–41 | KPI definitions versioned and documented |
| P19-S09 | Dashboards, builder, widgets, drill-down, executive dashboard | 38, 42–44 | 5 role dashboards (CEO, medical director, finance, nursing, pharmacy) |
| P19-S10 | Report builder, scheduled reports, exports, analytics API | 54–57 | Scheduled PDF to email, audited |
| P19-S11 | Row-level security, sensitive analytics, de-identification, research datasets, cross-hospital benchmarking | 45–48, 70–72 | Branch manager cannot see other branch (test) |
| P19-S12 | Forecasting foundation, natural-language analytics, AI boundary, DR, monitoring, performance, tests & Gate | 62–68, 74–81 | Gate F-2 passed |

---

### 26.9 Phase 20 — AI and Clinical Intelligence

**Spec:** `Phase 20 — AI - Clinical Intelligence …` (97 sections) · **Depends on:** 0–19 · **Also apply:** ch. 16.6 (cross-border), 10 (clinical safety), 22 (your own use of AI is a different matter)

**Guideline overrides**

1. **AI never decides, orders, signs, prescribes, discharges, bills or closes anything.** It drafts, summarizes, flags and explains. Every output has a status (draft / accepted / rejected / expired) and a clinician acceptance step (spec §9, 83).
2. **Provider choice is a policy**: a local/self-hosted model, a Bangladesh-hosted service, or an international provider. International providers receive **de-identified or minimum-necessary** context only unless legal approval is recorded (16.6 item 7).
3. Begin with **low-risk, high-value** use cases: (1) patient timeline summarization, (2) clinical documentation assistance, (3) inventory demand forecasting, (4) natural-language report search. Add medication/lab/ICU/ED intelligence **after** the evaluation framework works. **Risk scores last.**
4. Bangla and mixed Bangla-English clinical text must be part of every evaluation set.
5. Every use case needs: owner, intended use, risk class, evaluation results, human oversight, kill switch, rollback and monitoring (spec §36–38, 78–81).
6. Prompt-injection and tool-use protection are tested **before** any AI feature can read free text from patients or external documents (spec §90–92).
7. If an AI feature could be classed as software that influences clinical decisions, treat it with a medical-device-software mindset (IEC 62304 / ISO 14971) and keep the evidence (17.1).

| Slice | Delivers | Spec § | Done when |
|---|---|---|---|
| P20-S00 | Inspect & Map; governance decisions (owner, committee, providers) | 96 steps 1–4 | Approved |
| P20-S01 | AI Gateway, provider abstraction, model registry, use-case registry, risk classification | 3–8, 36, 37 | Fake provider works end-to-end |
| P20-S02 | Policy engine, data classification, PHI/PII protection, external provider controls | 38–41, 72, 73 | Request with disallowed data is blocked and logged |
| P20-S03 | Context engine: minimum necessary, authorization, wrong-patient and wrong-context protection | 10–12, 49, 50 | Context contains only authorised, in-scope data (test) |
| P20-S04 | Audit, provenance, output status, human-in-the-loop, acceptance workflow, clinical-record boundary | 9, 16, 17, 42, 83, 84 | AI text can never enter the legal record without clinician acceptance |
| P20-S05 | Prompt registry/versioning; RAG, knowledge base, vector search, evidence | 31–35, 57–59 | Answers cite sources; unsupported claims suppressed |
| P20-S06 | **Use case 1–2:** timeline summarization, note assistance | 14, 15, 51 | Clinician acceptance rate and error report captured |
| P20-S07 | Evaluation framework (clinical and general), feedback, monitoring, incident management, drift | 43–48, 74–77 | Model change blocked until evaluation passes |
| P20-S08 | **Use case 3–4:** inventory forecasting, workforce intelligence, natural-language analytics | 26, 27 | Forecast error tracked; NL queries show semantic query |
| P20-S09 | Clinical decision support: medication and laboratory intelligence | 18–20 | Suggestions explainable; alert-fatigue controls |
| P20-S10 | ICU, ED, OT, blood bank, radiology, insurance, portal AI | 21–25, 28, 29 | Each use case registered with owner and risk class |
| P20-S11 | Risk scoring framework, explainability, alert management, patient safety escalation | 30, 52–56 | Scores show inputs and uncertainty |
| P20-S12 | Security: prompt injection, tool use, action confirmation; governance docs, retirement, API, streaming, tests & Gate | 60, 61, 78–82, 85–95 | Gate F-2 passed; red-team test pack passes |

---

## 27. New Phases 21–25 (proposed)

> These phases do not exist in your specification set. They are written as *short phase charters*; each becomes a full specification by running the **Phase Specification Prompt** (Appendix D-7), which uses the same section style as your existing phase files, so the AI developer sees a familiar format.

### 27.1 Phase 21 — Finance and Accounting  *(strongly recommended)*

**Why.** Your phases already produce invoices, receipts, refunds, claims, supplier invoices, payroll and stock valuation, but nothing records them in **books of account**. A hospital that pays suppliers, banks cash and files VAT returns needs a ledger. Without it every hospital will buy a separate accounting product and re-key your data.

**Decision to take first (OD-05):** *build* the accounting core (this phase) *or* *integrate* with an existing ERP/accounting package through the `FinanceGateway`. Build if you want a single product; integrate if the hospitals already run an accounting system you must keep. The slice plan works for both — the integration variant replaces S02–S08 by posting adapters.

**Scope**

| Area | Content |
|---|---|
| Chart of accounts | Hospital-configurable, hierarchical, with account types, cost centers and profit centers (department/branch) |
| General ledger | Double-entry journals, periods, opening balances, closing, reversal (never delete), multi-currency basics (BDT first) |
| Posting engine | Rule-based posting from events: invoice finalised, payment received, refund, claim settled, GRN accepted, supplier invoice approved, payroll approved, stock adjustment, depreciation |
| Receivables | Patient, corporate, insurer ledgers, ageing, write-off with approval, statements |
| Payables | Supplier invoices, three-way match from Phase 15, payments, ageing, AIT/VAT withholding as configured |
| Cash & bank | Cashier summaries → bank deposits, bank accounts, reconciliation against statements/gateway settlements (bKash/Nagad/cards) |
| Tax | VAT output/input tracking, VAT register, return worksheet, AIT/TDS ledgers — **rates and rules as settings, verify with accountant (C)** |
| Budget | Annual budget by cost center, commitments from POs, variance |
| Fixed assets (basic) | Register, depreciation schedule; physical tracking in Phase 22 |
| Reports | Trial balance, income statement, balance sheet, cash flow, department profitability, doctor-wise revenue and fee-share statements |
| Controls | Maker-checker on journals, period lock, audit trail, no deletion, segregation of duties |

**Guideline rules**

1. **Billing is the source of truth for patient charges; Finance is the source of truth for accounting.** Finance consumes events (`invoice.finalized`, `payment.received`, `refund.completed`, …) and creates journals idempotently with a `source_type/source_id/event_id` unique key.
2. All previous `FinanceGateway` null adapters are replaced here.
3. Consultant fee sharing (P-23) is calculated in Billing/Finance from configurable rules — never hand-calculated spreadsheets.

| Slice | Delivers | Done when |
|---|---|---|
| P21-S00 | Inspect & Map existing financial data, null adapters, tax fields; decision OD-05 | Approved |
| P21-S01 | Chart of accounts, cost centers, fiscal years/periods, settings | Hierarchy and period lock work |
| P21-S02 | Journal entries, ledger, reversal, maker-checker | Unbalanced journal impossible; trial balance correct |
| P21-S03 | Posting engine and rule set; billing events → journals | Each Billing event posts once; reconciliation report Billing vs GL = 0 |
| P21-S04 | Receivables: patient/corporate/insurer ledgers, ageing, write-offs | Claim settlement clears receivable |
| P21-S05 | Payables: supplier invoices, matching hand-off, payments, ageing | PO→GRN→invoice→payment chain posts correctly |
| P21-S06 | Cash, bank, mobile-wallet settlement reconciliation | Bank statement import and matching |
| P21-S07 | Tax: VAT register, withholding ledgers, return worksheet | Report ties to postings; rates from settings |
| P21-S08 | Payroll and stock valuation postings; inventory adjustments | Payroll and stock journals balance |
| P21-S09 | Budgets and variance; PO commitments | Budget check blocks/ warns on PO |
| P21-S10 | Fixed assets basics and depreciation | Monthly depreciation journals |
| P21-S11 | Financial statements, doctor-wise revenue and fee sharing, department profitability | Statements balance; export |
| P21-S12 | Hardening & Gate (period close drill, security, performance) | Gate F-2 passed |

### 27.2 Phase 22 — Facility and Support Services  *(recommended)*

**Why.** Much of what makes a hospital run (and what inspectors look for) is not clinical: equipment that is maintained and calibrated, clean linen, safe waste handling, food for patients, ambulances, mortuary, CSSD. These have patient-safety and licensing implications.

| Slice | Content |
|---|---|
| S00 | Inspect & Map |
| S01 | Biomedical **asset register** (equipment, location, supplier, warranty, depreciation link) and **QR labels** |
| S02 | Preventive maintenance schedules, work orders, breakdown tickets, calibration records, spare parts (via Phase 15 stock) |
| S03 | Housekeeping and bed turnover tasks (linked to IPD bed status), cleaning checklists, infection-control audits |
| S04 | **CSSD**: sterilisation batches, instrument sets, traceability to OT cases (Phase 10) |
| S05 | Linen and laundry tracking |
| S06 | **Biomedical waste** segregation, collection, disposal manifests |
| S07 | Diet and kitchen: dietary orders from IPD/Nursing, meal schedules, kitchen requisitions |
| S08 | Ambulance fleet and trips (extends ED/ambulance data of Phase 12 rather than duplicating) |
| S09 | Mortuary management (links to death records of Phases 8/10/12) |
| S10 | Visitor and attendant pass management (P-23), gate security log |
| S11 | Dashboards, reports, hardening & Gate |

Rules: tasks are created from **events** (e.g., `bed.discharged` → cleaning task), never by editing IPD tables; devices that influence patient care get calibration blocks.

### 27.3 Phase 23 — Quality, Safety and Compliance  *(recommended)*

**Why.** Phase 13 (transfusion incidents), Phase 10 (WHO checklist), Phase 9 (falls), Phase 12 (door times) all create safety data without one place to manage it. Accreditation (JCI-style, national licensing audits) needs it consolidated.

| Slice | Content |
|---|---|
| S00 | Inspect & Map existing incident-like tables (blood, nursing, OT, ED) |
| S01 | **Incident and near-miss reporting** (any staff, Bangla/English, anonymous option), severity classification |
| S02 | Investigation workflow: root cause analysis, corrective and preventive actions (CAPA) with owners and due dates |
| S03 | **Infection control surveillance**: HAI tracking, isolation flags, hand-hygiene audits, outbreak alerts |
| S04 | Clinical audit and quality indicators (readmission, return-to-OT, falls, pressure injuries, medication errors, mortality review) fed from Phase 19 facts |
| S05 | **Document control**: SOPs, policies, versions, acknowledgement by staff |
| S06 | Credentialing and privileging (extends Phase 16 credentials), license/inspection calendar, **accreditation checklists** |
| S07 | Patient safety committees: agenda, minutes, action tracking; morbidity & mortality meeting records |
| S08 | Patient complaints → quality linkage (from Phase 17 CRM) |
| S09 | Dashboards, reports, hardening & Gate |

Rule: incidents are **append-only with amendments**; reporter identity protection; access limited to quality roles; no punitive use built into the tool.

### 27.4 Phase 24 — Specialty Care and Telemedicine  *(optional; take by hospital demand)*

The clinical foundation in Phases 3 and 8–12 is generic. Many Bangladeshi hospitals live on a few specialties. Build only the ones your hospitals need, each as a **thin module on top of Encounter, Orders, Observation and Billing** (never a copy):

| Pack | Content |
|---|---|
| **Maternity & neonatal** | Antenatal visit schedule, partograph, delivery record, newborn record and immunisation, birth notification (P-25), caesarean pathway |
| **Dialysis** | Machine/session scheduling, per-session record, consumables, recurring billing packages |
| **Oncology** | Protocol/cycle management, chemotherapy orders with double-check, radiotherapy schedule hooks |
| **Physiotherapy & rehab** | Session plans, progress, package billing |
| **Dental** | Tooth chart, procedures, lab orders |
| **Ophthalmology, ENT, dermatology** | Specialty templates in OPD (structured forms), device result attachments |
| **Pediatrics** | Growth charts, weight-based dosing hooks, immunisation schedule (EPI) |
| **Health check / executive packages** | Package builder (P-23), multi-department day itinerary, consolidated report |
| **Telemedicine** | Implements the abstraction from Phase 17: scheduling, secure video room, e-prescription, payment, recording policy, consent |

Rule: each pack is delivered as a **template pack + extra tables only when structured data cannot use existing models**, and gets its own module flag.

### 27.5 Phase 25 — Platform and SaaS Administration  *(optional)*

Only if you will **host many hospitals as customers** (D-20).

| Slice | Content |
|---|---|
| S01 | Customer (organization) onboarding wizard; sandbox/demo tenants; offboarding and data export |
| S02 | Subscription plans, module bundles, limits, usage metering (patients, users, storage, SMS) |
| S03 | Billing of customers (invoices to hospitals), payment follow-up, suspension rules |
| S04 | Platform admin console: tenant health, version/rollout rings, feature flags, support impersonation with consent and audit |
| S05 | Tenant-level backups/restore, data residency settings, tenant isolation tests at scale |
| S06 | Support desk and status page; release notes distribution |
| S07 | Hardening & Gate |

Rule: **platform admins never see patient data by default**; impersonation requires customer consent, time-box and audit (16.2, 16.6).

---

## 28. Governance: Gates, Risks, Decisions and Progress

### 28.1 Three levels of "done"

| Level | Meaning | Checked by |
|---|---|---|
| **Slice done** | Slice report (22.6) delivered, tests run and shown, code review checklist F-1 passed, charter updated | You |
| **Phase done (Gate)** | Appendix F-2 passed: all slices, test packs P1–P13 relevant, journey tests, UAT scripts executed by users, docs, registry, release tag | You + department champions |
| **Release done** | Gate + go-live readiness (F-4) for the hospital where it is switched on | You + hospital management |

### 28.2 Top risks and what to do

| # | Risk | Likelihood / impact | Mitigation |
|---|---|---|---|
| R1 | **AI-generated duplication** (second patient/billing/observation concept) | High / High | Reuse protocol, registry CI check, review checklist F-1, S00 inspection per phase |
| R2 | **Specs too big for one AI session** | High / Medium | Slices; give section ranges (chapter 26) not whole files |
| R3 | **Tests claimed but not run** | Medium / High | Mandatory command output in slice report (22.6) |
| R4 | **Patient-safety defect** (wrong patient, wrong drug, lost alert) | Low / Very high | Chapter 10, scope tests, barcode checks, pilot with supervision, incident process |
| R5 | **Money mismatches** (billing vs ledger vs cash) | Medium / High | Contract 11, golden-file tests, daily reconciliation reports |
| R6 | **Privacy breach / legal exposure** (new data-protection law, sensitive data) | Medium / Very high | 16.6, field encryption, audit, counsel review (OD-02), de-identified AI |
| R7 | **Users reject the system** (slow, confusing, English-only) | Medium / High | Chapter 19, Bangla (H-10), pilot, super-users, task-time targets |
| R8 | **Infrastructure fragility** (power, network, hardware) | High / Medium | 21.3–21.6, downtime kit, UPS/generator, restore drills |
| R9 | **Scope creep** (adding 22–25 early, over-customising per hospital) | High / Medium | Module flags, settings not forks, phase gate rule 23.4 |
| R10 | **Single-person dependency** (you, as owner/reviewer) | Medium / High | Charters, registry, runbooks, decision log, second reviewer when possible |
| R11 | **Regulatory change** (tax, DGHS, exchange standards) | Medium / Medium | Rules in settings and effective-dated tables; section 18 "verify" items; subscribe to official notices |
| R12 | **Performance collapse at scale** | Medium / Medium | Budgets 20.5, seeders with realistic volume, query-count tests, warehouse separation |
| R13 | **Vendor/model lock-in** (SMS, payment, AI, PACS) | Medium / Medium | Adapter rule 17.5; fakes |
| R14 | **Data migration errors** from legacy systems | Medium / High | Import Service (H-11), dry-runs, duplicate detection, signed reconciliation |

### 28.3 Changing this guideline

* The guideline has a **version** (front matter). Every change gets an entry in the change log (Appendix A.3) and, if it reverses a decision, a new row in the Decision Log (D-xx) with the reason.
* Use a short **ADR** (architecture decision record) for any new cross-cutting decision: *context → options → decision → consequences*. Store in `docs/decisions/`.
* Phase specs may add detail; they may **not** contradict a chapter without an ADR that updates this guideline.
* When the AI finds a conflict, it must **stop and report** it (rule 10, 22.3) — not choose silently.

### 28.4 Progress status board (copy to `docs/STATUS.md`)

```text
Phase | Status        | Slices done/total | Gate | Pilot? | Last updated | Notes
0–11  | Built         | n/a               | audit | pilot  |              | audit findings: __ blockers, __ fix-when-touched, __ accepted
H     | In progress   | 0/15              | —    | —      |              |
12    | Not started   | 0/12              | —    | —      |              |
…
Measured velocity: __ slices/week (average of last 3 weeks)    Forecast finish of current phase: __
```

### 28.5 People and roles you need around the AI tool

| Role | Who | What they do |
|---|---|---|
| **Product owner** | You | Priorities, decisions (28.6), final review |
| **AI developer** | AI tool (with your supervision) | Implements slices |
| **Reviewer** | You, ideally a second senior developer part-time | Review checklist F-1, merges |
| **Clinical champion(s)** | A senior doctor and a senior nurse | Validate clinical flows, order sets, alerts, reports; sign off Blood Bank, ED, ICU, OT, AI |
| **Department super-users** | 1–2 per department | UAT, training, feedback |
| **Finance reviewer** | Accountant | Validates Billing, Insurance, Finance, Payroll, tax settings |
| **Privacy/security owner** | Named person (can be you at first) | Chapter 16 duties, access reviews, incidents |
| **Counsel** | External | Law-dependent items marked **C** in chapter 18 |

### 28.6 Open decisions for the owner

> These cannot be settled from documents. Record each answer as a Decision Log entry.

| ID | Decision | Options | Recommended default |
|---|---|---|---|
| OD-01 | **Deployment model** per hospital | On-premises LAN server · hosted in a Bangladesh data centre · public cloud | On-premises or Bangladesh-hosted for pilot (internet-independent, data stays in country) |
| OD-02 | **Data-protection obligations** | Confirm with counsel: effective date, breach-notification window, cross-border rules, hospital registration duties | Build to 16.6 now; adjust settings after advice |
| OD-03 | **VAT / tax treatment** of hospital services and pharmacy sales | Confirm with accountant/NBR rules | Tax categories as settings; no hard-coded rate |
| OD-04 | **Pilot hospital and scope** | Which hospital, which modules, which departments first | Single hospital; OPD, pharmacy, lab, billing, IPD first |
| OD-05 | **Finance:** build Phase 21 or integrate with an existing accounting product | Build · integrate | Build if you own the whole stack; otherwise integrate through `FinanceGateway` |
| OD-06 | **Payment aggregator(s)** for mobile wallets and cards | Local aggregator vendors | Choose one with sandbox, settlement reports, refunds |
| OD-07 | **SMS gateway and sender ID** | Local providers | One with delivery reports and Unicode Bangla |
| OD-08 | **PACS/modality vendor stance** | Built-in lightweight viewer · integrate a PACS (e.g., open-source) | Integrate; keep Phase 6 as RIS |
| OD-09 | **AI hosting policy** | Local model · Bangladesh-hosted · international (de-identified) | Local or de-identified international; clinical features only after evaluation |
| OD-10 | **National exchange** | Participate in DGHS exchange / HMIS reporting? | Prepare adapters; onboard when official process is confirmed |
| OD-11 | **Commercial model** | Sell licences to hospitals · host as SaaS | Decide before Phase 25; does not change Phases 12–21 |
| OD-12 | **Triage scale and clinical content owners** | ESI/MTS/CTAS/local; who approves order sets and alert rules | Clinical champions approve; stored as settings |

---

# PART C — APPENDICES

## Appendix A — Document Map, File Naming and Repository Layout

### A.1 What to do with your 31 files

| Current file (in `docs/`) | Status | Recommended new name | Give to AI? |
|---|---|---|---|
| *(this file)* | **Master** | `00-guideline/HMS-GUIDE-001.md` | Always (whole, or chapters per 22.2) |
| `HMS_International_Standard_Master_Development_Specification.md` | Superseded; keep for history | `archive/master-spec-2025.md` | No |
| `HMS_Laravel_SaaS_AI_Development_Specification.md` | Superseded; keep for history | `archive/saas-ai-spec-2025.md` | No |
| `Hospital Management System — Complete Feature List.md` | Valid as *what* list | `00-guideline/feature-list.md` | Only when planning scope |
| `Phase 0 — Foundation.md` + `… AI Implementation Prompt.md` | Built | `phases/p00-foundation-spec.md`, `p00-foundation-prompt.md` | Only for fixes |
| `Phase 1 … Patient Core` (spec + prompt) | Built | `p01-patient-core-spec.md`, `-prompt.md` | Only for fixes |
| `Phase 2 — Appointment + OPD.md` + prompt | Built (title says OPD; content is Appointment) | `p02-appointment-spec.md`, `-prompt.md` | Only for fixes |
| `Phase 3 — OPD : Clinical Encounter.md` + prompt | Built | `p03-opd-emr-spec.md`, `-prompt.md` | Only for fixes |
| `Phase 4 — Billing & Revenue.md` + prompt | Built | `p04-billing-spec.md`, `-prompt.md` | Only for fixes |
| `Phase 5 — Laboratory.md` + prompt | Built | `p05-laboratory-spec.md`, `-prompt.md` | Only for fixes |
| `Phase 6 Radiology : PACS.md` (0 bytes) | **Empty leftover** | delete | No |
| `Phase 6 — Radiology … .md` | Built | `p06-radiology-spec.md` | Only for fixes |
| `Phase 7 … Pharmacy …`, `Phase 8 … IPD …`, `Phase 9 … Nursing …`, `Phase 10 … OT …`, `Phase 11 … ICU …` | Built | `p07-pharmacy-spec.md` … `p11-icu-spec.md` | Only for fixes |
| `Phase 12 … Emergency …` | **Next** | `p12-emergency-spec.md` | **Yes**, with guideline §26.1 |
| `Phase 13 … Blood Bank …` | Planned | `p13-blood-bank-spec.md` | Yes, with §26.2 |
| `Phase 14 … Insurance …` | Planned | `p14-insurance-spec.md` | Yes, with §26.3 |
| `Phase 15 … Inventory …` | Planned | `p15-inventory-procurement-spec.md` | Yes, with §26.4 |
| `Phase 16 … HR …` | Planned | `p16-hr-payroll-spec.md` | Yes, with §26.5 |
| `Phase 17 … Portal …` | Planned | `p17-portal-crm-spec.md` | Yes, with §26.6 |
| `Phase 18 … Interoperability …` | Planned | `p18-interoperability-spec.md` | Yes, with §26.7 |
| `Phase 19 … BI …` | Planned | `p19-bi-warehouse-spec.md` | Yes, with §26.8 |
| `Phase 20 … AI …` | Planned | `p20-ai-spec.md` | Yes, with §26.9 |

The phase files contain, at their end, a "Final AI Implementation Prompt". **Do not paste it as-is** — it tells the AI to do the whole phase in one go. Use the slice prompt (Appendix D-2) instead and take the *rules* from the final prompt only when a slice needs them.

### A.2 Repository layout (docs and registry)

```text
docs/
  00-guideline/HMS-GUIDE-001.md      ← this file
  00-guideline/feature-list.md
  phases/pNN-*-spec.md               ← detailed phase specs (renamed)
  registry/modules.md                ← module, prefix, permission prefix, API base, owner  (H-01)
  registry/permissions.md            ← generated
  registry/tables.md                 ← generated
  events.md                          ← generated: event, producer, consumers, payload
  decisions/D-Exx-*.md  ADR-nnn-*.md ← exceptions and new decisions
  audit/phase-0-11-conformance.md    ← chapter 25 output
  charters/<module>.md               ← Module Charters (7.4)
  runbooks/                          ← backup, restore, downtime, incident, release
  training/                          ← role quick-start cards, manuals (bn/en)
  STATUS.md                          ← status board (28.4)
CLAUDE.md                            ← Appendix E (repo root)
```

### A.3 Change log of this guideline

| Version | Date | Change |
|---|---|---|
| 1.0 | 25 Oct 2026 | First unified edition (supersedes the two master specs; adds Phase H and Phases 21–25; Bangladesh pack) |

---

## Appendix B — Data Ownership Matrix

**Rule:** exactly one module **writes**. Others **read** through the named contract (service, query class or event), never by touching the owner's tables.

| Concept | Owner (writes) | Phase | Others read via |
|---|---|---|---|
| Organization, hospital, branch, department, users, roles, permissions, settings, audit, files, notifications, workflow | Core | 0 | Core services; Settings API; Audit service |
| Patient identity, identifiers, demographics, guardians, consents (registration/treatment), merge | Patient / MPI | 1 | `PatientQueries`, events `patient.*` |
| Appointment, schedule, slot, queue token | Appointment | 2 | `AppointmentQueries`, events `appointment.*` |
| Provider (clinical role) | Appointment (schema) · linked to Employee | 2 / 16 | `ProviderQueries` |
| Clinical encounter, notes, diagnoses, problems, allergies, orders (header), prescription (header) | OPD / EMR | 3 | `EncounterQueries`, `OrderContract`, events `encounter.*` |
| **Vital signs, scores, I/O, device readings** | **Observation Service** | H-03 | `ObservationReader` |
| **Clinical alerts and escalation** | **Alert Service** | H-04 | `AlertContract` |
| Price lists, charges, invoices, payments, refunds, deposits, cashier sessions, credit notes | Billing | 4 | `BillingQueries`; events `invoice.*`, `payment.*` |
| **Charge events (intake)** | **Billing** (contract published by H-05) | 4 / H-05 | Producers call `ChargeEventPublisher` |
| Lab orders, specimens, results, validation, reference ranges | Laboratory | 5 | `LabQueries`, `lab.result.*` |
| Imaging orders, studies, reports | Radiology | 6 | `ImagingQueries`, `radiology.report.*` |
| Drug master, pharmacy stock (dispensing), dispensing, medication verification | Pharmacy | 7 | `PharmacyQueries`, `medication.*` |
| Admission, bed, ward, transfer, discharge, bed state | IPD | 8 | `AdmissionQueries`, `BedQueries`, `admission.*` |
| Care plans, nursing tasks, MAR, handover | Nursing | 9 | `NursingQueries`, `mar.*` |
| Surgery request, OT schedule, checklist, operative notes, implants | OT | 10 | `SurgeryQueries`, `surgery.*` |
| ICU episode, flowsheet context, ventilator settings, ICU scores | ICU | 11 | `IcuQueries` |
| ED visit, triage, ED board, ED disposition | Emergency | 12 | `EmergencyQueries`, `ed.*` |
| Donors, donations, blood units, crossmatch, issue, transfusion, reactions | Blood Bank | 13 | `BloodBankGateway`, `bloodbank.*` |
| Payers, plans, policies, authorizations, claims, remittances, corporate accounts | Insurance | 14 | `ClaimQueries`, `claim.*` |
| Enterprise items, suppliers, requisitions, POs, GRN, enterprise stock, transfers | Inventory | 15 | `InventoryQueries`, `StockLedgerReader` |
| **Stock ledger entries** | **Stock Ledger Contract** | H-06 | Append-only; written via `StockPosting` |
| Employees, contracts, attendance, leave, shifts, payroll, payslips | HR | 16 | `EmployeeQueries`, `payroll.*` |
| Portal account, device, session, messages, CRM interactions, campaigns, feedback | Portal / CRM | 17 | `PortalQueries` |
| Integration endpoints, messages, mappings, terminology mapping | Interoperability | 18 | `InteropGateway` |
| Warehouse facts and dimensions, KPIs, dashboards | BI | 19 | Analytics API / semantic layer |
| AI use cases, models, prompts, outputs, evaluations | AI | 20 | `AiGateway` |
| Accounts, journals, ledgers, tax, bank, budgets, assets | Finance | 21 | `FinanceGateway`, `finance.*` |
| Equipment, maintenance, CSSD, waste, linen, diet, mortuary | Facility | 22 | `FacilityQueries` |
| Incidents, CAPA, controlled documents, indicators | Quality | 23 | `QualityQueries` |
| Specialty packs, telemedicine sessions | Specialty | 24 | Module APIs |
| Customers, plans, usage, platform admin | Platform | 25 | Platform API |
| Code systems, concepts, mappings | Core (terminology) | H-13 | `TerminologyService` |
| Number series | Core (numbering) | H-02 | `NumberingService` |

---

## Appendix C — Role Catalogue and Permission Pattern

### C.1 Permission verbs (use these words only)

`view` · `list` · `create` · `update` · `delete`(rare; soft/void only) · `approve` · `reject` · `cancel` · `finalize` · `reverse` · `export` · `print` · `assign` · `verify` · `release` · `override` · `manage`(configuration) · `break_glass`.

Pattern: `module.resource.action` — e.g. `billing.invoice.finalize`, `lab.result.approve`, `lab.critical_result.view`, `pharmacy.dispense.override`, `ed.triage.create`.
A permission to **export** or **print** is always separate from **view**.

### C.2 Roles (starting set; hospitals can clone and adjust)

| Role | Home screen | Typical permission groups |
|---|---|---|
| Super Admin (platform) | Platform console | all `core.*`; no clinical data by default |
| Organization Admin | Admin dashboard | `core.*`, settings, users within org |
| Hospital Manager / CEO | Executive dashboard | read-only analytics, approvals of high-level exceptions |
| Medical Director | Clinical quality dashboard | clinical read, quality, approvals |
| Receptionist / Front Desk | Today's queue + Register | `patient.*` (limited), `appointment.*`, `billing.payment.create` (if cashiering) |
| Cashier | Billing counter | `billing.invoice.*`, `billing.payment.*`, cashier session |
| Billing Supervisor | Exceptions & approvals | `billing.discount.approve`, `billing.refund.approve`, `billing.invoice.reverse` |
| Doctor / Consultant | My queue | `encounter.*`, `order.*`, `prescription.*`, results view |
| Resident / Intern | My queue (supervised) | same as doctor with `cosign` required |
| Nurse (OPD/Ward) | My patients + due tasks | `nursing.*`, observations, MAR administer |
| Charge Nurse / Head Nurse | Ward board | nurse + assignments + handover approvals |
| ED Triage Nurse | ED board | `ed.triage.*` |
| ED Physician | ED board | `ed.*` |
| OT Coordinator / OT Nurse | OT schedule | `ot.*` |
| ICU Nurse / Intensivist | ICU board | `icu.*` |
| Lab Technician | Lab worklist | `lab.specimen.*`, `lab.result.enter` |
| Lab Pathologist / Supervisor | Validation queue | `lab.result.approve`, `lab.critical_result.*` |
| Radiology Technologist | Modality worklist | `radiology.study.*` |
| Radiologist | Reporting worklist | `radiology.report.*` |
| Pharmacist | Pending prescriptions | `pharmacy.dispense.*`, `pharmacy.stock.view` |
| Pharmacy Store Keeper | Stock | `pharmacy.stock.*` |
| Blood Bank Technologist | Blood bank board | `bloodbank.*` |
| Blood Bank Medical Officer | Approvals | `bloodbank.release`, `bloodbank.issue.approve` |
| Insurance / TPA Officer | Claims worklist | `insurance.*` |
| Store Keeper / Procurement Officer | Requisitions | `inventory.*`, `procurement.*` |
| Purchase Manager / Approver | Approvals | `procurement.po.approve` |
| Accountant / Finance Manager | Finance dashboard | `finance.*` |
| HR Officer / Payroll Officer | HR dashboard | `hr.*`, `payroll.*` |
| Department Manager | Team dashboard | `hr.leave.approve` (own team), reports |
| Medical Records Officer | Records requests | chart release, `patient.merge` |
| Quality Officer | Quality dashboard | `quality.*` |
| Privacy Officer | Privacy dashboard | audit, break-glass review, consent log |
| IT Support | Support console | user support without clinical data |
| Auditor (read-only) | Audit dashboard | `audit.*` view, masked clinical data |
| Patient / Guardian | Portal | own data only (portal guard) |

**Principle:** roles carry *permissions*, not logic. No `if ($user->role == 'doctor')` in code — always `can('encounter.note.sign')`.

---

## Appendix D — Prompt Pack for the AI Developer

> Replace the `<angle brackets>`. Keep prompts short: the rules are in `CLAUDE.md` (Appendix E) and this guideline. The AI should **read** the guideline chapters named; do not paste them.

### D-1 Conformance audit (read-only)

```text
ROLE: You are auditing an existing Laravel HMS codebase. DO NOT modify any file except creating
docs/audit/phase-0-11-conformance.md.

READ: docs/00-guideline/HMS-GUIDE-001.md chapters 3, 6–16 and 25 (checklist 25.2 and 25.3).

TASK: For each of the 26 checks in 25.2, inspect the repository and report:
  rule | PASS / PARTIAL / FAIL | evidence (file path + line or table name) | severity
  (Blocker / Fix-when-touched / Accept) | suggested fix | effort (S/M/L)

ALSO PRODUCE:
 1. Inventory: modules (folder), tables (by prefix), routes count, permissions count, events,
    tests per module. 2. List of tables that store vital signs, alerts, charge events, stock.
 3. The list of exceptions you think should be accepted, with reasons.
Do not guess: if you cannot verify a check, mark UNKNOWN and say what you need.
End with the commands you ran.
```

### D-2 Slice prompt (use for every slice)

```text
CONTEXT: HealthNexus HMS, Laravel modular monolith. Rules: CLAUDE.md and
docs/00-guideline/HMS-GUIDE-001.md (chapters 6–16, 19, 20, 22 apply to every slice).
PHASE: <12 Emergency> — SLICE: <P12-S03 Triage assessment and reassessment>
SPEC TO READ (only these): docs/phases/p12-emergency-spec.md sections <9, 10, 11, 52>
GUIDELINE OVERRIDES for this phase: guideline §26.1 (read it; it wins over the spec).
MODULE CHARTERS TO READ: patient, encounter, observation-service, alert-service, billing.

STEP 1 (no code): inspect and report what already exists that this slice must reuse
  (models, services, routes, permissions, tables). List the tables/classes you will create and
  confirm none duplicates something existing. WAIT for my approval.
STEP 2 (after approval): implement the slice in this order: migration → model/enums → policy →
  action/service → request → controller → routes → view (template T<n>) → API resource →
  lang en+bn → tests → charter + registry updates.
BOUNDARIES: do not edit other modules' tables or code; if you need a change elsewhere, stop and tell me.
TESTS: write tests for packs <P1 scope, P2 authorization, P3 state machine, P4 concurrency,
  P7 audit, P11 localization>. RUN them and Pint/Larastan; paste the exact commands and results.
FINISH WITH the SLICE REPORT format in guideline 22.6. Do not claim anything you did not run.
```

### D-3 Phase kick-off (S00 Inspect & Map)

```text
Read docs/00-guideline/HMS-GUIDE-001.md chapters 4, 13, 14 and §26.<n> (phase <N>), then the
phase spec sections 1–5. DO NOT write code.
Produce docs/charters/phase-<N>-map.md containing:
 1. Existing modules/tables/services/events this phase will integrate with (names + file paths)
 2. For each concept in the phase spec: REUSE / EXTEND / NEW, with the owner module (Appendix B)
 3. Forward hooks needed (interface + null adapter list)
 4. Conflicts between the spec and the guideline (list them; the guideline wins)
 5. Proposed table list with prefixes (check the registry) and permission list
 6. Risks and questions for me
Stop and wait.
```

### D-4 Review prompt (second AI as reviewer)

```text
Review the diff of branch <branch> against main using guideline Appendix F-1 as the checklist.
Report only: BLOCKING issues (scope/authorization/money/duplicate domain/destructive migration/
missing tests/claimed-but-not-run tests), then IMPORTANT, then NITS. For each give file:line and a
concrete fix. Do not rewrite the code. If something is fine, do not mention it.
```

### D-5 Bug-fix prompt

```text
BUG: <what happened, expected, steps, role, hospital, request id>
1. Reproduce with a failing automated test first (show it failing).
2. Find the root cause (explain in 3–5 lines).
3. Fix with the smallest change; do not refactor unrelated code.
4. Test passes; also run the module's full test suite and scope pack.
5. Add a line to the module charter "Known issues / history" if it reveals a rule gap.
```

### D-6 Phase H task prompt (example H-03 Observation Service)

```text
Task H-03 — Observation Service (guideline chapter 10.4, D-08, §24.1).
STEP 1 (no code): list every table that stores vital signs, scores or I/O today (Phases 3, 9, 11),
 with columns and row semantics; propose the clinical_observations schema (class H table, with
 patient_id, encounter_id, code_system/code/display (LOINC/local), value_numeric/value_text, unit
 (UCUM), observed_at, recorded_by, source_module, source_ref, device_id NULL, status
 (preliminary/final/amended/entered_in_error), amended_of NULL, public_id). Wait for approval.
STEP 2: build only the service + table + read-through views. DO NOT change any existing module's
 writes in this task. Provide ObservationReader::latest(), ::trend(), ::forEncounter().
STEP 3: switch ONE writer (OPD vitals) to the service behind a feature flag and prove equality of
 old and new reads with tests. Report. The other writers follow in later, separate tasks.
```

### D-7 Phase Specification Prompt (for new Phases 21–25)

```text
Write the full specification for Phase <21 Finance and Accounting> in the same section style as the
existing phase files (Module Purpose, Critical Architecture Rule, Scope, Architecture, domain
sections, Integration sections, Database tables, Permissions, Roles, API, Events, Jobs,
Notifications, Audit, Clinical/Financial Integrity, Security, Concurrency, Performance, Testing,
UI Menu, Implementation Order, Non-Goals, Definition of Done).
Inputs: guideline chapters 6–16 and 27.<n> (the charter and slice list), Appendix B ownership matrix,
and the existing Billing/Insurance/Inventory/HR specs for integration points.
Rules: do not duplicate an owned concept; every integration uses an interface + event; every
Bangladesh-specific legal rule is a setting marked "verify". Output as docs/phases/p<NN>-<name>-spec.md.
Do NOT write code.
```

### D-8 Session hand-over (when the AI tool's context is full)

```text
Write docs/handover/<date>-<slice>.md for the next session: slice goal; files changed; decisions
made and why; what is finished/unfinished; failing tests and exact commands; next 3 steps;
open questions for the owner. Keep under 80 lines.
```

---

## Appendix E — `CLAUDE.md` / `AGENTS.md` Rules (copy to the repository root)

```markdown
# HealthNexus HMS — rules for AI developers

Read first: docs/00-guideline/HMS-GUIDE-001.md. If a phase spec disagrees with it, the guideline wins.
If you find a conflict or missing information, STOP and ask — never invent.

## Stack
PHP 8.4+, Laravel (current LTS), MySQL 8, Redis + Horizon, Blade + AdminLTE + Alpine.js (Vue only for
board islands), Sanctum, Pest/PHPUnit, Pint, Larastan.

## Always
1. Inspect before coding. Report existing models/services/tables you will reuse. One owner per concept
   (docs/00-guideline Appendix B). Never create a second Patient/Encounter/Billing/Observation/Alert/Stock/Provider concept.
2. One slice at a time; end with the SLICE REPORT (guideline 22.6). Never claim tests passed unless you ran
   them — paste commands and results.
3. Tenancy: organization → hospital → branch → department. Column is organization_id (never company_id).
   Every query is scoped; every action authorised by a Policy; scope ids come from the server, never the client.
4. Business rules live in Actions/Services (DB::transaction, lockForUpdate where needed, events AFTER COMMIT),
   not in controllers, Blade, or JS. Controllers stay thin.
5. Money = DECIMAL (18,2 totals / 18,4 rates & quantities), currency_id on headers, only Billing creates
   charges (publish ChargeEvent with idempotency key). Stock moves only through the Stock Ledger.
   Vitals only through Observation Service. Alerts only through Alert Service.
6. Never delete or overwrite clinical or financial records: amend, reverse, version. Ledgers are append-only.
7. Store UTC, display Asia/Dhaka. Numbers from the Numbering Service (no count()+1).
8. Permissions: module.resource.action, registered in docs/registry. Export/print are separate permissions.
9. New modules: ULID public_id in URLs; table prefix from the module registry; Module Charter updated.
10. UI: use the seven templates (T1–T7), patient banner on clinical pages, English + Bangla (lang parity),
    plain-language labels, 1366×768 first.
11. API: /api/v1/<module>, envelope {success,message,data,meta}, Idempotency-Key on critical POSTs, 409 on state conflicts.
12. Tests with the code: scope, authorization, state machine, concurrency, idempotency, audit as relevant.
13. Secrets never in code/logs/prompts. No real patient data in seeders or fixtures.
14. Do not edit merged migrations; add new ones. Do not add packages without asking.
15. Do not touch other modules' tables/code; propose a separate slice instead.

## Commands (fill in)
test: php artisan test | style: vendor/bin/pint | static: vendor/bin/phpstan analyse | migrate: php artisan migrate
```

---

## Appendix F — Checklists

### F-1 Code review checklist (human or AI reviewer)

**BLOCKING (any "no" stops the merge)**

```text
[ ] Every query/route scoped (organization/hospital/branch); Policy on every action; no client-supplied scope trust
[ ] No duplicate concept (patient, billing, observation, alert, stock, provider, order…) — registry/charter checked
[ ] Money: DECIMAL, rounding rule, currency_id; charges only via ChargeEvent; idempotency key present
[ ] No UPDATE/DELETE on final clinical or financial rows; amendments/reversals implemented
[ ] Transactions + locks where concurrent (beds, stock, numbers, payments); events after commit; listeners idempotent
[ ] Migrations: new file, reversible, indexes/FK, no destructive change without a data plan
[ ] Tests exist AND were run (command + output shown); relevant packs P1–P13 covered
[ ] No secrets, no real personal data, no PHI in logs
[ ] Sensitive actions audited; export/print permissions separate
```

**IMPORTANT**

```text
[ ] Controller thin; logic in Action/Service; no raw SQL without bindings; no N+1 (query-count test)
[ ] Validation by FormRequest with messages in en+bn; error format per chapter 15
[ ] Pagination/filters whitelisted; API resource hides internal ids; public_id for new modules
[ ] UI uses template T1–T7, patient banner, status colour+icon+text, loading/empty/error states
[ ] Lang keys complete en+bn; no hard-coded strings/currency/hospital names
[ ] Registry updated (permissions, tables, events); Module Charter updated
```

**NITS** — naming, comments, dead code, small duplication.

### F-2 Phase gate checklist

```text
FUNCTIONAL
[ ] All slices done; each slice report archived
[ ] Phase spec "Definition of Done" section satisfied (list items ticked)
[ ] Integration hooks to earlier modules proven by integration tests; null adapters documented for later modules

QUALITY
[ ] Test packs P1–P13 relevant to the phase pass; scope pack green for every new route
[ ] The phase's critical journey(s) (20.3) pass in CI
[ ] Performance budgets (20.5) checked on seeded volume data
[ ] Security review: authorization matrix auto-test, upload tests, secrets scan, dependency audit
[ ] Accessibility/print: axe on key screens; PDFs (incl. Bangla) approved

PRODUCT
[ ] UX acceptance (19.8) with 3 real users per role; task-time targets measured
[ ] UAT scripts executed by department users; no open Blocker/Major defects
[ ] Bangla/English complete; training material + quick-start cards updated

OPERATIONS
[ ] Migrations tested on a production-size copy; rollback plan written
[ ] Monitoring/alerts added for new jobs/integrations; runbook updated
[ ] Backup/restore unaffected (restore test if new storage added)
[ ] Module flag default OFF in production until go-live approval

GOVERNANCE
[ ] Registry, events list, module charter, decision log, STATUS.md updated
[ ] Release notes in plain language; version tag created
```

### F-3 Incident response runbook (security or patient-data incident)

```text
1  DETECT & REPORT   any staff → IT support / Privacy Officer immediately; record time and source
2  TRIAGE (≤ 1 h)    severity: S1 data exposed or patient safety at risk · S2 attempted/contained · S3 minor
3  CONTAIN           disable accounts/tokens, block IPs, isolate server, rotate secrets; DO NOT wipe logs
4  PRESERVE          snapshot disks/DB, export audit and web logs, hash and store evidence
5  ASSESS            which data (C1–C4), which patients, which period, who accessed (audit trail), root cause
6  NOTIFY            internal management; legal counsel; the regulator and affected people within the
                     legally required window (CONFIRM the window with counsel — OD-02); use prepared templates
7  RECOVER           patch/fix, restore from clean backup if needed, verify with tests, monitor closely for 14 days
8  LEARN             blameless post-mortem within 7 days; actions with owners; update this guideline/controls
Keep contact list: IT lead, Privacy Officer, hospital management, counsel, hosting provider, SMS/payment vendors.
```

### F-4 Pilot / go-live readiness

```text
PEOPLE   [ ] Super-users named and trained  [ ] Role cards printed  [ ] Support contacts + escalation sheet
DATA     [ ] Masters loaded (departments, services/prices, drugs, tests, users, beds)  [ ] Legacy import reconciled
         [ ] Opening stock and balances signed  [ ] Number series set  [ ] Print templates approved (Bangla check)
TECH     [ ] Hardware tested: PCs, printers (A4/A5/80 mm/labels), scanners, UPS  [ ] Network and Wi-Fi coverage
         [ ] Backups running AND restore drill passed  [ ] Monitoring and alert contacts  [ ] SSL/security baseline
         [ ] Penetration test done or scheduled with fixes plan  [ ] Production module flags set
SAFETY   [ ] Downtime forms printed and drilled  [ ] Downtime census job tested  [ ] Break-glass reviewed
         [ ] Critical alert path tested end-to-end (lab critical → SMS → acknowledge)
LEGAL    [ ] Consent texts approved  [ ] Privacy notice displayed  [ ] Data-protection checklist (16.6) reviewed by counsel
GO/NO-GO [ ] Management sign-off  [ ] Rollback plan (paper + export)  [ ] Hypercare roster for first 2 weeks
```

---

## Appendix G — Plain-Language Glossary (English → Bangla UI wording)

> Use **one** word per concept everywhere (screen, report, SMS). *The Bangla column is a starting list; have two senior clinicians and receptionists confirm the final wording before it is locked.*

| Use in English UI | Do not use | বাংলা (suggested) |
|---|---|---|
| Patient | Client, case | রোগী |
| Patient ID (MRN) | Registration index | রোগীর আইডি (এমআরএন) |
| Doctor | Provider (on screen) | চিকিৎসক / ডাক্তার |
| Appointment | Booking slot | অ্যাপয়েন্টমেন্ট |
| Visit | Encounter | ভিজিট |
| Queue / Token | Serial | সিরিয়াল / টোকেন |
| Register patient | Create patient entity | রোগী নিবন্ধন |
| Admit patient | Create admission episode | রোগী ভর্তি |
| Discharge | Close admission | ছাড়পত্র / ডিসচার্জ |
| Bed / Ward / Cabin | Location resource | শয্যা (বেড) / ওয়ার্ড / কেবিন |
| Prescription | Medication order document | প্রেসক্রিপশন |
| Medicine | Product | ওষুধ |
| Test / Lab report | Investigation result | পরীক্ষা / ল্যাব রিপোর্ট |
| Imaging (X-ray, CT, MRI, Ultrasound) | Radiology study | ইমেজিং (এক্স-রে, সিটি, এমআরআই, আল্ট্রাসাউন্ড) |
| Bill / Invoice | Charge document | বিল / ইনভয়েস |
| Payment / Receipt | Settlement | পরিশোধ / রসিদ |
| Due | Outstanding balance | বাকি |
| Discount / Refund | Adjustment / reversal | ছাড় / ফেরত |
| Emergency | Casualty (unless hospital prefers) | জরুরি বিভাগ |
| Operation theatre (OT) | Surgical suite | অপারেশন থিয়েটার |
| Intensive care (ICU) | Critical care unit | আইসিইউ |
| Blood bank | — | ব্লাড ব্যাংক |
| Pharmacy | — | ফার্মেসি |
| Insurance claim | Payer submission | বীমা দাবি (ক্লেইম) |
| Report | Output | রিপোর্ট / প্রতিবেদন |
| Save / Cancel / Submit / Approve / Reject | OK / Dismiss | সংরক্ষণ / বাতিল / জমা দিন / অনুমোদন / প্রত্যাখ্যান |
| Allergy | Adverse reaction record | অ্যালার্জি |
| Critical result | Panic value | জরুরি ফলাফল |

Short forms that **stay** as-is in both languages (staff already use them): BP, ECG, OT, ICU, NID, MRN, USG, CT, MRI.

---

## Appendix H — Sources and "Verify Before Relying" List

### H.1 Sources consulted for the Bangladesh pack (retrieved while writing this guideline)

* Bangladesh Core FHIR Implementation Guide (DGHS) — background and profiles: <https://fhir.dghs.gov.bd/core/background.html>
* Overview of Bangladesh's Personal Data Protection Act (vendor summary, not the legal text): <https://securiti.ai/bangladesh-personal-data-protection-act-overview/>
* Bangladesh VAT overview (vendor summary, not NBR text): <https://trykintsugi.com/sales-tax-guides/apac/bangladesh>

Additional web searches covered DGHS exchange, private-hospital regulation and hospital VAT; they did not return an authoritative primary source, so those topics are treated as **C** (confirm).

### H.2 What I did NOT verify (confirm before relying)

| # | Item | Who confirms |
|---|---|---|
| 1 | Whether, when and how the Personal Data Protection Act applies to hospitals; breach-notification window; cross-border rules; registration duties | Counsel |
| 2 | VAT treatment of hospital services, pharmacy, diagnostic and cabin charges; VAT invoice format; electronic fiscal device duties; AIT/TDS rules | Accountant / NBR |
| 3 | DGHS licensing conditions for private hospitals and any mandatory reporting (HMIS/DHIS2, notifiable diseases, births/deaths) | DGHS / counsel |
| 4 | Medical price-regulation guidance (if any) for services in your category of facility | Hospital management / DGHS |
| 5 | Blood-screening policy list and donor eligibility rules | National blood transfusion authority / your blood bank medical officer |
| 6 | Bangladesh Core FHIR IG details you must implement (profiles, value sets, identifier systems, onboarding for exchange) | DGHS / IG documentation |
| 7 | Telecom regulator rules on SMS (sender ID, promotional SMS, quiet hours) | SMS vendor / regulator |
| 8 | Narcotics/psychotropic register format and retention | DGDA / pharmacy head |
| 9 | Official formats of NID, birth registration number and the national health ID | Official issuing authority |
| 10 | Code system licences (SNOMED CT usage, LOINC and ICD-11 terms) | Your legal/licensing contact |
| 11 | All "suggested" numbers in this document: targets for RPO/RTO, performance budgets, task times, retention periods | You, after the pilot |

### H.3 What I could not see

* Your **source code, database and tests** for Phases 0–11 — chapter 25 exists to close this gap.
* Your hospitals' **actual workflows, forms and price lists** — pilot and UAT will correct assumptions marked **P** (practised) in chapter 18.
* Any **contracts or vendor choices** (SMS, payment, PACS, hosting) — see open decisions OD-01…OD-12.
