# Bugbot review rules — blood-values-dashboard

Personal Laravel/Livewire app for private blood-test follow-up. Not a diagnosis
machine. Review against these invariants before suggesting scope expansion.

## Privacy and boundaries (blocking)

- Flag any change that sends private health data (PDFs, biomarkers, symptoms,
  medication notes, consult exports, source snippets, logs) to external services,
  OCR, LLM runtime, or network processors.
- Flag new Composer/npm packages touching auth, files, exports, jobs, logs,
  external APIs, or health data without an ADR/privacy note in the PR.
- Do not suggest runtime AI interpretation, unreviewed OCR, provider integrations,
  wearable sync, or medical-advice features inside the V2 PDF-first slice.

## Confirmed-only trust gate (blocking)

- `confirmed_at` gates dashboard, history, compare, consult, export, and trends.
- Draft/unconfirmed extracted rows must never appear in downstream builders,
  exports, or consult output.
- Auto-confirm is allowed only for deterministic trusted CMA rows that pass
  ADR-0011 gates (confidence, unit, reference, catalog match, duplicate checks).
- Values with `unknown` status, ambiguous numeric formats, missing units, catalog
  conflicts, or same-unit duplicates must stay as review drafts — never
  auto-confirmed to reduce friction.

## Owner scoping (blocking)

- Every query, export, download, deletion, and confirmation path must enforce
  owner scope server-side — not only in Blade/Livewire UI.
- Flag Livewire public properties or action parameters used for ownership,
  export, download, or deletion without server-side authorization.
- Flag `$result->biomarker->name` or similar dereferences on queries that could
  include null-biomarker or cross-owner rows.

## Intake and extraction

- Parser fixes need a failing synthetic regression test before the fix.
- Never use real lab PDFs or real biomarker values as fixtures.
- Flag broadened confidence thresholds or relaxed trust gates to hide review rows.
- Same-run auto-import must not let a later candidate fold into or overwrite an
  earlier biomarker created in the same extraction run.
- Ambiguous dotted-thousands values (e.g. `1.234`) must route to review, not
  auto-confirm.

## Status and medical copy

- Status is `low`, `normal`, `high`, or `unknown` — use `unknown` when ranges,
  units, or comparisons are not trustworthy.
- Flag diagnosis, treatment, supplement advice, health scoring, optimal ranges,
  or extra-testing encouragement in user-visible copy.

## Deletion and data integrity

- Privacy deletion must not orphan health records without tests proving rollback
  behavior on multi-document partial failures.
- Migration `down()` methods that delete null-biomarker rows are intentional for
  privacy-deletion residue — flag only if they delete more than documented.

## Tests expected for risky changes

- Intake/parser changes: `tests/Feature/Intake/AssistedPdfExtractionTest.php`
- Confirmed-only regressions: dashboard, consult, export, compare tests
- Livewire auth: `tests/Feature/Livewire/*Authorization*`
- UI/intake changes also need browser QA — green unit tests alone are not enough

## Non-blocking follow-ups

- Mobile-responsive review tables
- Duplicated formatters between Livewire and `App\Support\Format`
- Performance: loading full dossier when scoped recompute is possible
