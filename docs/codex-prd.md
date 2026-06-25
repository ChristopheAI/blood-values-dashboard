# Codex PRD: Private Blood Values Follow-Up App

Date: 2026-06-21
Audience: Codex agents and senior developers building this Laravel project.
Status: product requirements baseline for phased implementation.

## 1. Product Sentence

The user has multiple blood draws and wants one private place where each blood
test keeps the original PDF, confirmed values, units/ranges, context, and
differences from previous tests, so the user can understand what changed and
prepare compactly for a doctor conversation without the app making medical
claims or encouraging extra testing.

Short version:

```text
PDF in -> blood test -> review -> confirmed values -> timeline/detail ->
comparison -> context -> consult/export.
```

## 2. What We Are Building

Build a private Laravel/Livewire application for personal blood-value follow-up.
The app organizes lab-result PDFs, reviewed or confidence-confirmed biomarker
values, context notes, trends, comparisons, source documents, reminders, privacy
controls, and consult preparation.

The app is not a diagnosis machine. It does not prescribe, advise, optimize,
score health, recommend supplements, encourage additional testing, or replace a
doctor.

The core job is singular:

```text
Turn scattered blood-test PDFs and notes into a private, owner-scoped,
confirmed-only timeline that is easy to review and discuss with a doctor.
```

## 3. Product-To-System Check

### 1. Wat probeer ik te bouwen?

Een private Laravel-app voor bloedwaarden-opvolging: originele labo-PDFs,
bevestigde biomarkerwaarden, context, trends, verschillen en consultvoorbereiding
op een plek, zonder medische conclusies.

### 2. Hoe moet dit systeem werken?

De gebruiker uploadt een lab-result PDF. The app stores it privately, creates a
blood test, extracts or receives draft values, and only lets values flow
downstream after explicit confirmation or ADR-0011 confidence-gated deterministic
auto-confirm. Downstream means dashboard, status, history, compare, consult, and
export.

### 3. Welke componenten heb ik nodig?

Auth, private PDF storage, blood tests, source documents, extraction runs,
biomarker catalog, biomarker results, review/confirmation UI, status calculation,
dashboard summaries, blood-test detail pages, history, compare, context notes,
pins, reminders, consult/export, privacy export/delete, tests, browser QA, and
repo control-plane docs.

### 4. Waar moet deze logica leven?

Domain rules live in `app/Domain`. Owner and confirmed-only query contracts live
in models and domain builders. Controllers and Livewire components re-authorize
all browser/request input. Blade renders authorized data only. Migrations and
factories preserve the schema contracts that make privacy and confirmed-only
behavior possible.

### 5. Waarom breekt dit ding?

It breaks if drafts leak into downstream surfaces, if owner scoping is enforced
only in the UI, if PDFs or biomarker data leave the local/private boundary, if
AI/OCR/external services become runtime processors without a new ADR, if status
copy becomes medical advice, or if the app becomes a generic health cockpit
instead of a blood-test follow-up system.

### 6. Verdict: bouwen

Build, but only through small tested slices. Do not broaden product scope without
a spec, ADR, privacy review, and validation plan.

## 4. Non-Negotiable Guardrails

- Private lab PDFs, biomarker values, context notes, consult exports, account
  data, symptoms, medication notes, and source snippets must not be sent to AI
  tools, Exa, Firecrawl, OCR services, external APIs, logs, screenshots, or
  public URLs.
- Exa and Firecrawl are public research tools only. They are not runtime
  processors for private user data.
- Confirmed-only downstream remains hard: dashboard, status, history, compare,
  consult, export, and trends use only rows with a valid trust gate.
- The trust gate is `confirmed_at`: set by explicit user review or by
  deterministic ADR-0011 confidence-gated auto-confirm.
- Below-threshold rows, ambiguous catalog matches, conflicts, missing-unit rows,
  uncertain parses, and same-unit duplicate trusted-CMA rows stay out of
  downstream surfaces until reviewed.
- Owner scoping must be enforced server-side for every route, Livewire action,
  download, export, deletion, selected ID list, and query builder.
- Source documents and structured biomarker values remain separate.
- Use `unknown` when range, unit, value, or comparison basis is not trustworthy.
- Copy may say personal tracking, range-based status, context, change, source
  document, and doctor discussion. Copy must not say diagnosis, treatment,
  advice, risk prediction, personal optimum, supplement recommendation, or
  health score.
- No new package touching auth, private files, exports, jobs, logs, external
  APIs, or health data without explicit fit, privacy, maintenance, and
  validation review.

## 5. Primary User

V1 serves one authenticated personal user.

The user:

- uploads lab-result PDFs as the source of each blood test;
- wants to find old blood draws quickly;
- wants to see confirmed values and original documents together;
- wants to compare multiple blood draws over time;
- wants context notes near the relevant blood test or date;
- wants a compact consult view for a doctor conversation;
- wants export/delete controls for sensitive data.

Not V1 users:

- doctors managing patients;
- coaches managing clients;
- families with multiple profiles;
- public SaaS tenants;
- commercial health optimization users.

## 6. Core Data Model

### User

Owns all health data. V1 has no doctor, admin, coach, family, or shared-access
role.

### Blood Test

Represents one blood draw or lab-result event. It belongs to one user and has a
date, title, lab/source name, notes, status, source documents, biomarker results,
context notes, and extraction runs.

### Blood Test Document

Represents the original lab-result PDF. It stores sanitized original filename
metadata and generated private storage references. It never exposes raw storage
paths to the browser.

### Extraction Run

Records local deterministic extraction attempts, status, and candidate counts.
It is telemetry for local processing, not an external processing log.

### Biomarker Category

Groups biomarkers for navigation only. Categories must not imply diagnosis.

### Biomarker

Owner-scoped catalog item with name, optional short name, default unit, optional
reference range defaults, active flag, and category.

### Biomarker Result

One measured value in one blood test. Stores value, unit, reference range,
reference unit, status, entry source, optional note, optional extraction
metadata, and `confirmed_at`.

Rules:

- no duplicate confirmed result for the same biomarker in the same blood test
  unless a future spec introduces repeat measurements;
- range/unit evidence is stored with the result so later catalog edits do not
  rewrite history;
- `confirmed_at` controls downstream eligibility;
- status is `low`, `normal`, `high`, or `unknown`;
- null biomarker links are allowed for extracted drafts and privacy cleanup
  paths, not for confirmed downstream values.

### Pinned Biomarker

Owner-specific follow-up marker. Pinning means "I want to follow this", not
"this is medically important".

### Context Note

User-authored observation around a date or blood test. Categories may include
sleep, food, training, supplement, medication, complaint, stress, or other.
Notes are descriptive context, not advice.

### Reminder

Personal planning aid for follow-up blood tests. V1 can be in-app only.

### Export / Consult Pack

Derived owner-scoped output from confirmed values, source-document metadata,
context, questions, and selected blood tests. It must remain descriptive and
print/export-friendly.

## 7. Main User Workflows

### Upload Blood Test

1. User opens the dashboard or blood-tests page.
2. User uploads a PDF.
3. System stores the file privately under the authenticated user.
4. System creates a blood test and source-document record.
5. System runs local deterministic extraction when available.
6. System lands the user on the blood-test detail/review page.

Acceptance:

- source document is linked to the blood test;
- another user cannot access the blood test or document;
- upload path introduces no external processor;
- browser QA proves the actual route, not only tests.

### Review Or Confirm Values

1. User opens a blood test.
2. System shows patient-friendly overview when confirmed values exist.
3. System keeps management/review tools below the overview.
4. User reviews drafts, confirms values, edits confirmed values, deletes wrong
   rows, or manually adds missing values.
5. System recalculates status and blood-test status.

Acceptance:

- confirmed rows appear in confirmed table and downstream surfaces;
- drafts stay visibly review-only;
- public Livewire state is re-authorized server-side;
- old blood-test detail pages show their own overview, not latest upload data.

### View Blood-Test Detail

The blood-test detail page is the canonical workspace for one blood draw.

It should show:

- original source document links;
- confirmed values;
- value statuses;
- changes versus previous owned tests where comparable;
- remaining drafts;
- manual fallback;
- context notes linked to that blood test;
- edit/delete paths.

Acceptance:

- one blood test can be understood without jumping back to dashboard;
- changes are descriptive only;
- unit mismatches show not comparable;
- source-document download/delete routes are owner-authorized.

### View History / Trend

User opens a biomarker and sees confirmed values over time.

Acceptance:

- ordered by date and stable tie-breaker;
- owner-scoped;
- confirmed-only;
- unit mismatches are not silently plotted as one line.

### Compare Blood Tests

User selects two owned blood tests and sees old value, new value, unit, status,
and delta where comparable.

Acceptance:

- both selected tests are owned by the user;
- missing values are shown as not measured;
- different units show not comparable;
- no medical conclusion is generated.

### Add Context

User adds notes around a date or blood test.

Acceptance:

- notes are owner-scoped;
- notes may appear near blood tests and consult views;
- notes are never converted into medical advice.

### Prepare Consult Pack

User selects one or more blood tests and produces a compact doctor-preparation
view.

The consult pack should include:

- attention section for abnormal or unknown confirmed values;
- compact normal confirmed values;
- trends/changes where available and comparable;
- selected source-document metadata/links;
- context notes and user questions when included;
- print/export-friendly layout.

Acceptance:

- confirmed-only;
- owner-scoped;
- no diagnoses, recommendations, urgency ranking, or treatment language;
- no sensitive free text in GET query strings;
- export does not expose raw storage paths.

### Export And Delete

User can export structured owner data and delete health data.

Acceptance:

- export uses owner-scoped builders;
- biomarker values in export are confirmed-only;
- documents are metadata unless a later spec adds file bundle export;
- delete-all handles database rows and stored private files;
- corrupted cross-owner links do not leak or delete foreign data.

## 8. Product Phases

### Phase 0: Requirements, Intent Layer, And Control Plane

Goal:

- make product intent, guardrails, and codebase boundaries explicit before
  further feature work.

Build:

- maintain `AGENTS.md` hierarchy;
- keep project brief, V1 spec, ADRs, source index, validation protocol, and
  handoffs aligned with real branch state;
- use GitHub issues as developer todos for product/build work;
- keep Intent Layer work as local repo infrastructure, not product issues.

Done when:

- any Codex run can read `AGENTS.md` plus this PRD and understand the product
  boundary;
- there is no root `CLAUDE.md`;
- docs do not contradict tests or live browser behavior.

### Phase 1: PDF-First Intake And Confirmed Values

Goal:

- prove the core loop from private PDF upload to confirmed structured values.

Build:

- authenticated app shell;
- PDF upload-first dashboard/intake;
- private document storage;
- blood-test creation;
- local deterministic extraction where supported;
- confidence-gated auto-confirm for trusted deterministic rows;
- compact review for uncertain drafts;
- manual fallback;
- status calculation;
- confirmed-only model/domain scopes;
- owner access tests and browser QA.

Done when:

- user can upload a fresh PDF and land on the result page;
- confirmed count and draft count match parser behavior;
- source document is visible through owner-authorized links;
- drafts cannot feed dashboard/history/compare/consult/export;
- `sh scripts/validate.sh` passes;
- browser QA proves dashboard and blood-test detail route.

### Phase 2: Canonical Blood-Test Detail Workspace

Goal:

- make each blood draw understandable in one place.

Build:

- patient-friendly "blood results" overview reused on dashboard and detail
  pages;
- confirmed values table;
- source documents;
- draft review strip;
- manual correction path;
- context notes attached to blood test;
- per-row previous-value/change labels where available;
- robust old-upload behavior.

Done when:

- older owned blood tests show their own overview;
- latest upload data does not bleed into older details;
- management layer remains available below the overview;
- edit/delete/reconfirm paths stay owner-scoped and status-aware.

### Phase 3: Longitudinal Understanding

Goal:

- help the user understand change across multiple blood draws without turning
  the app into advice.

Build:

- biomarker history pages;
- compare two blood tests;
- pinned biomarkers;
- better normal/unknown/not-comparable states;
- context-note surfaces around dates and blood tests;
- dashboard blocks for recent tests, pinned biomarkers, review needs, and next
  reminder.

Done when:

- at least two owned blood tests with overlapping confirmed biomarkers can be
  compared;
- unit mismatches and missing values are honest;
- pinned markers are visible without implying medical importance;
- dashboard remains workflow-first, not a wellness cockpit.

### Phase 4: Consult Pack

Goal:

- move from "I can see my values" to "I can discuss this clearly with my
  doctor".

Build:

- select one or more owned blood tests;
- include attention values at top;
- include normal values compactly;
- include trends/changes where comparable;
- include source-document metadata and owner-authorized links;
- include context notes and user questions when selected;
- printable view;
- CSV or another simple export where useful.

Done when:

- consult pack is confirmed-only and owner-scoped;
- selected foreign IDs reveal nothing;
- no sensitive free text is moved through GET URLs;
- print/export view contains no medical claims or advice;
- feature tests cover privacy, confirmed-only, and copy boundaries.

### Phase 5: Privacy, Export, Delete, And Operational Hardening

Goal:

- make the app safe enough for real personal use.

Build:

- owner-scoped JSON data export;
- delete individual blood tests/documents/notes;
- delete all health data without deleting the auth account if supported;
- architecture tests for privacy and medical-copy boundaries;
- stronger validation ladder with Pint, PHPStan/Larastan, Pest, Vite build,
  Dusk/browser smoke, and dependency review;
- production checklist updates.

Done when:

- export excludes drafts and storage paths;
- delete behavior removes private stored files and records;
- corrupted cross-owner fixtures cannot leak data;
- validation and browser QA are repeatable.

### Phase 6: Later, Only After New ADRs

These are not active scope:

- runtime AI interpretation;
- OCR/image intake;
- provider integrations;
- Apple Health or wearable import;
- secure share links;
- doctor/coach roles;
- family profiles;
- automatic unit conversion;
- optimal ranges;
- health scores;
- recommendations;
- commercial SaaS features.

Any later expansion needs:

- updated spec;
- ADR;
- privacy review;
- threat model update;
- focused GitHub issue;
- failing tests before implementation;
- browser QA for the real workflow.

## 9. Architecture Responsibilities

### `app/Domain`

Owns status, extraction trust, dashboard summaries, compare, consult/export
builders, privacy export/delete, and reusable product rules.

### `app/Models`

Owns Eloquent relationships, casts, fillable fields, and reusable scopes such as
confirmed-for-user. Model helpers may encode ownership and trust contracts but
should not become workflow services.

### `app/Http`

Owns route/controller boundaries, request validation, selected-ID intersection,
downloads, exports, deletion routes, and POST/PATCH/DELETE transport.

### `app/Livewire`

Owns interactive server-rendered workflows. Public properties and action
parameters are browser input and must be reloaded and authorized server-side.

### `resources/views`

Owns presentation only. Blade renders authorized data and product copy. It does
not own owner checks, confirmed-only filtering, status math, or medical meaning.

### `database`

Owns schema and factories. Schema changes must preserve owner scope,
`confirmed_at`, private document references, nullable cleanup paths, and
synthetic-only test data.

### `tests`

Owns behavior proof. Tests must cover owner scope, confirmed-only downstream,
privacy boundaries, medical-copy boundaries, and real workflow regressions.

### `docs`

Owns the control plane: brief, PRD, specs, ADRs, source index, validation,
reviews, kickoff notes, and handoffs.

## 10. Codex Build Protocol

Before building any slice:

1. Read `AGENTS.md`.
2. Read the nearest child `AGENTS.md` for every directory you will touch.
3. Read this PRD.
4. Read the relevant spec/ADR for the slice.
5. Check `git status --short --branch`.
6. Do not stage unrelated files.
7. If privacy, data model, external processing, or workflow boundaries change,
   update spec/ADR before code.

During implementation:

1. Write or update a failing test for the behavior boundary.
2. Implement the smallest correct change.
3. Keep domain rules out of Blade-only or Livewire-only enforcement.
4. Use synthetic fixtures only.
5. Run focused tests.
6. Run `sh scripts/validate.sh` before handoff for code changes.
7. Use browser QA for UI/intake/workflow changes.

Before claiming done:

1. Confirm tests passed.
2. Confirm browser route behavior when user-facing.
3. Confirm no private data was sent externally.
4. Confirm drafts remain out of downstream surfaces.
5. Confirm owner-scope checks happen server-side.
6. Report exact commands and manual QA surfaces.

## 11. Success Metrics

The project is succeeding when:

- a user can upload multiple blood-test PDFs and recover each original document;
- confirmed values are easy to review per blood draw;
- older blood tests keep their own detail overview;
- changes versus previous blood draws are visible and honest;
- context can be stored near the relevant blood draw or date;
- consult preparation is compact and doctor-discussion-friendly;
- export/delete controls are available;
- validation and browser QA catch regressions;
- the app never drifts into diagnosis, advice, health scoring, or external
  processing of private data.

## 12. Source Documents For Codex

Read these before changing product behavior:

- `AGENTS.md`
- `docs/project-brief.md`
- `docs/v1-spec.md`
- `docs/product-system-check.md`
- `docs/evidence/source-index.md`
- `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
- `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
- `docs/adr/0007-use-staged-laravel-quality-ladder.md`
- `docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`
- `docs/validation-protocol.md`
- nearest directory `AGENTS.md` for touched files
