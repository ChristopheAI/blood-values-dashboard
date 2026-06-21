# Slice Tracker: Issue 14 Canonical Blood-Test Detail

Use this tracker to finish the blood-test detail page as the canonical workspace
for one blood draw. Keep it free of private health data.

## Current Objective

Finish GitHub issue #14: make an owned blood-test detail page show the original
source documents, confirmed values, comparable changes, context, drafts, and
management fallback for that exact blood test.

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
- Latest local commit: `e2e4c4d docs: strengthen agent workflow gates`
- Dirty files intentionally in scope:
  - this tracker until committed
- Dirty files intentionally out of scope:
  - `.omo/`
  - `bloed-overzicht.tsx`

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
- Observed proof: source documents, confirmed values, comparable changes,
  context notes, and existing management layer appear for the selected blood
  test.
- Must not appear/leak: draft TSH in downstream sections, foreign user data,
  storage paths, diagnosis/advice/urgency/scoring copy.

## Privacy And Product Boundaries

- [ ] Confirmed-only downstream preserved.
- [ ] Owner scope enforced server-side.
- [ ] No private PDFs, biomarker values, notes, exports, account data, or source
  documents sent to external services.
- [ ] No diagnosis, treatment, advice, urgency, scoring, or recommendation copy.

## Resume Point

Read this tracker, `AGENTS.md`, `app/Livewire/BloodTests/AGENTS.md`,
`resources/views/livewire/blood-tests/AGENTS.md`, and issue #14. Then write the
failing Livewire/detail test before changing `ReviewBloodTest`.

## Open Questions

- None.
