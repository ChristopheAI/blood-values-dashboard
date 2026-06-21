# Slice Tracker: Issue 14 Canonical Blood-Test Detail

Use this tracker to finish the blood-test detail page as the canonical workspace
for one blood draw. Keep it free of private health data.

## Current Objective

Finish GitHub issue #14: make an owned blood-test detail page show the original
source documents, confirmed values, comparable changes, context, drafts, and
management fallback for that exact blood test.

## Razor-Sharp Product Check

1. Wat probeer ik te bouwen?
   - One canonical workspace for one owned blood draw: `/blood-tests/{id}` must
     let the user understand that blood test without jumping back to dashboard.
2. Hoe moet dit systeem werken?
   - The route loads one owner-authorized blood test, shows its private source
     documents and management layer, and feeds downstream-looking sections only
     with confirmed values from that test plus comparable previous owned tests.
3. Welke componenten heb ik nodig?
   - `ReviewBloodTest` Livewire component, detail Blade view, source-document
     relationships/download routes, confirmed result queries, context-note
     query, shared `BuildLongitudinalChanges`, factories, and browser QA seed.
4. Waar moet deze logica leven?
   - Ownership and confirmed-only selection live in model/domain/query code.
     Livewire orchestrates authorized data. Blade renders already-authorized
     facts. Comparisons use the shared domain builder, not view math.
5. Waarom breekt dit ding?
   - It breaks if the detail page reuses latest-upload data, leaks drafts into
     downstream sections, shows foreign documents/context, exposes storage
     paths, hides the review fallback, or turns descriptive changes into advice.
6. Verdict: bouwen
   - Build as a narrow detail-page completion slice. Do not redesign dashboard,
     add medical interpretation, add unit conversion, or create a second compare
     engine.

## Source Of Truth

- Issue: #14 `Make blood-test detail the canonical follow-up place`
- Product sentence / PRD: `docs/codex-prd.md#1-product-sentence`
- Detail workflow: `docs/codex-prd.md#view-blood-test-detail`
- Guardrails: `docs/codex-prd.md#4-non-negotiable-guardrails`
- Domain rules: `app/Domain/AGENTS.md`
- Livewire rules: `app/Livewire/AGENTS.md`
- View rules: `resources/views/AGENTS.md`
- Test rules: `tests/AGENTS.md`
- UI/design reference: existing blood-test detail, dashboard overview, and
  consult/source-document section patterns; no new visual language in this slice.
- Known gaps: none. If a redesign beyond existing patterns is requested, create
  a separate `[NEEDS-DESIGN]` issue before implementation.

## Current State

- Branch: `codex/v2-clean-autoconfirm`
- Baseline commit: `e2e4c4d docs: strengthen agent workflow gates`
- Latest published commit before sharpening: `7ec3ab2 docs: add issue 14 slice
  tracker`
- Dirty files intentionally in scope:
  - this tracker until committed and pushed
- Dirty files intentionally out of scope:
  - `.omo/`
  - `bloed-overzicht.tsx`

## Acceptance Matrix

| Requirement | Positive proof | Negative proof | Test proof |
| --- | --- | --- | --- |
| Selected blood test owns the page | Older owned `/blood-tests/{id}` shows its own date/title/overview | Latest upload values do not appear unless they belong to that ID | Existing older-owned regression plus a new expanded detail test |
| Source documents | Document metadata/link appears for the selected blood test | Raw disk, storage path, and foreign document names do not appear | Feature/Livewire assertion for selected and foreign documents |
| Confirmed values | Confirmed rows appear in overview/confirmed sections | Draft rows do not feed downstream-looking overview/change sections | Detail test with one confirmed value and one draft value |
| Comparable changes | Comparable confirmed previous value shows descriptive delta/change | Unit mismatch, missing previous value, and drafts show not comparable or stay absent | Test reusing `BuildLongitudinalChanges` fixtures |
| Context notes | Notes linked to this blood test appear near the detail workspace | Notes from another user or another blood test do not appear | Context-note owner-scope/detail test |
| Management fallback | Intake progress, drafts, confirmed rows, edit/delete/manual fallback remain visible | Patient-friendly overview does not replace review/management controls | Existing review test expanded rather than replaced |
| Medical-copy boundary | Copy says source, confirmed value, context, change, not comparable | No diagnosis, advice, urgency, risk, score, supplement, or treatment copy | Blade text assertions plus browser scan |

## First Tests To Write

- `tests/Feature/Livewire/ExtractedDraftReviewTest.php`
  - `it shows source documents for the selected owned blood test without storage paths`
  - `it shows context notes for the selected blood test only`
  - `it shows confirmed only comparable changes for the selected blood test`
  - `it keeps the management layer available below the patient friendly overview`

Use synthetic values only. The draft fixture should use a recognizable value
such as draft TSH so the test can prove it stays out of downstream sections.

## Task Board

- [ ] Add failing feature/Livewire tests for source documents, context notes,
  and comparable changes on one owned blood-test detail page
  (ref: `docs/codex-prd.md#view-blood-test-detail`, #14).
- [ ] Use shared confirmed-only longitudinal changes for the target blood test
  versus previous owned tests without unit conversion
  (ref: `app/Domain/BloodTests/BuildLongitudinalChanges.php`, #15, #14).
- [ ] Render source-document links through owner-authorized download routes
  without exposing storage paths
  (ref: `docs/codex-prd.md#blood-test-document`, #14).
- [ ] Render context notes linked to the current blood test, keeping private
  free text out of query strings and logs
  (ref: `docs/codex-prd.md#context-note`, #14).
- [ ] Preserve the existing management layer below the overview: intake
  progress, confirmed rows, draft review, edit/delete, and manual fallback
  (ref: `docs/codex-prd.md#review-or-confirm-values`, #14).
- [ ] Browser-QA the detail route on synthetic QA data and prove drafts/foreign
  data do not leak into downstream sections
  (ref: `docs/templates/definition-of-done.md#browser-qa`, #14).

## Non-Goals For This Slice

- No dashboard redesign.
- No new consult-pack behavior.
- No runtime AI, Exa, Firecrawl, OCR, provider sync, or external processing.
- No medical explanation, health scoring, urgency ranking, supplement copy, or
  treatment language.
- No unit conversion beyond existing comparable same-unit behavior.
- No broad data-model rewrite unless a failing test proves the current shape
  cannot satisfy #14.

## Failure Modes To Guard

- Latest-upload bleed: detail page silently uses the newest blood test instead
  of the route blood test.
- Draft bleed: unconfirmed rows appear in overview, changes, consult-like
  sections, status, trends, or export-like surfaces.
- Ownership bleed: another user's blood test, document, context note, or result
  appears through ID tampering or joined queries.
- Storage leak: private disk paths, generated storage names, or raw source
  paths appear in HTML, URLs, export surfaces, logs, or browser-visible text.
- Advice creep: copy frames changes as medical meaning, action, risk, urgency,
  optimization, or diagnosis.
- Management loss: the friendly overview hides review, edit, delete, draft, or
  manual correction controls.

## Validation Evidence

Focused commands:

```bash
php artisan view:clear
php artisan test tests/Feature/Livewire/ExtractedDraftReviewTest.php
php artisan test tests/Feature/BloodTests/BuildLongitudinalChangesTest.php
```

Full validator:

```bash
sh scripts/validate.sh
```

Browser/manual QA:

- Matching surface: browser route for one blood-test detail page.
- Route or command: `/blood-tests/{id}` with synthetic QA seed data.
- Account/data scenario: `qa@example.com` / `password`, two synthetic blood
  tests, confirmed values, one draft row, context note, and source documents.
- Positive observations:
  - selected older blood test has its own overview;
  - source document metadata/link is visible;
  - confirmed values are visible;
  - comparable changes are visible only when same-unit and confirmed;
  - context note for this blood test is visible;
  - existing management layer remains below the overview.
- Negative observations:
  - latest upload values do not bleed into older detail;
  - draft TSH does not appear in downstream sections;
  - foreign user data does not appear;
  - storage paths do not appear;
  - diagnosis/advice/urgency/scoring copy does not appear.

## Privacy And Product Boundaries

- [ ] Confirmed-only downstream preserved.
- [ ] Owner scope enforced server-side.
- [ ] No private PDFs, biomarker values, notes, exports, account data, or source
  documents sent to external services.
- [ ] No diagnosis, treatment, advice, urgency, scoring, or recommendation copy.

## Resume Point

Read this tracker, `AGENTS.md`, `app/Livewire/BloodTests/AGENTS.md`,
`resources/views/livewire/blood-tests/AGENTS.md`, and issue #14. Then write the
failing Livewire/detail tests named above before changing `ReviewBloodTest`.

## Open Questions

- None.
