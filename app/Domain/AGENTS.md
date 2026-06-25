# Domain Layer

Owns the testable application/domain rules for blood-test intake, biomarker
status, comparison, dashboard summaries, consult/export builders, and privacy
actions. Does not own HTTP authorization, Blade layout, migrations, or visual
copy beyond data labels returned to views.

## Entry Points

- `Biomarkers/DetermineBiomarkerStatus.php` - calculates `low`, `normal`,
  `high`, or `unknown`.
- `BloodTests/CompareBloodTests.php` - compares confirmed values between two
  blood tests.
- `Dashboard/BuildLatestUploadSummary.php` - builds patient-friendly confirmed
  result summaries for dashboard and blood-test detail surfaces.
- `Intake/AGENTS.md` - local contract for parser/trust/auto-confirm logic.
- `Consult/AGENTS.md` - local contract for consult/export data builders.
- `Privacy/AGENTS.md` - local contract for data export and delete-all privacy
  boundaries.

## Contracts & Invariants

- `confirmed_at` is the trust gate for dashboard, history, compare, consult,
  export, and trends.
- Owner scope belongs in queries/builders. Do not rely on controllers or views
  alone to hide foreign data.
- Use `BloodTest::confirmedResults()` or equivalent owner-scoped confirmed
  query patterns for downstream values.
- Source PDFs stay as `BloodTestDocument` records and private storage objects;
  structured biomarker values stay in `BiomarkerResult`.
- Status is `unknown` when value, unit, range, reference unit, or comparison
  basis is not trustworthy.
- Auto-confirm is allowed only inside the ADR-0011 deterministic trusted-CMA
  gates. Ambiguous, duplicate, missing-unit, below-threshold, or conflicting
  rows stay drafts.
- Never add image-text extraction, automated interpretation, external
  processing, provider sync, or network calls for private health data in this
  layer.

## Patterns

- Put reusable status, comparison, summary, export, and privacy rules here
  before exposing them through controllers, Livewire, or Blade.
- Keep builders invokable or narrowly scoped service classes that accept an
  authenticated `User` plus explicit owned model/filter inputs.
- Store range/unit evidence with each result so later catalog edits do not
  rewrite history.
- When a new downstream surface is added, first prove drafts and foreign records
  cannot enter the builder output.

## Anti-patterns

- Do not compute domain meaning only in Blade, Livewire public properties, or
  request parameters.
- Do not broaden auto-confirm trust to make a UI look cleaner.
- Do not turn attention, pinned, abnormal, or unknown values into medical
  importance, diagnosis, advice, or triage.
- Do not leak storage paths, original private content, or sensitive free text in
  export rows unless the export contract explicitly allows it.

## Related Context

- Root rules: `../../AGENTS.md`
- HTTP boundary: `../Http/AGENTS.md`
- Livewire boundary: `../Livewire/AGENTS.md`
- Model scopes/relations: `../Models/AGENTS.md`
- Intake details: `Intake/AGENTS.md`
- Consult details: `Consult/AGENTS.md`
- Privacy details: `Privacy/AGENTS.md`
- UI rules: `../../resources/views/AGENTS.md`
- Test rules: `../../tests/AGENTS.md`
- ADR: `../../docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`
