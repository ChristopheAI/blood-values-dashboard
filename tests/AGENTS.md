# Tests

Owns Pest feature/unit tests and Dusk smoke coverage for the app's privacy,
confirmed-only, intake, review, compare, consult/export, and UI invariants. Tests
are part of the product boundary, not disposable scaffolding.

## Entry Points

- `Feature/Architecture/*BoundaryTest.php` - package, privacy, and medical-copy
  boundaries.
- `Feature/Intake/AssistedPdfExtractionTest.php` and `Unit/*ExtractionTest.php`
  - deterministic PDF/CMA extraction regressions.
- `Feature/Livewire/AGENTS.md` - local contract for review/confirmation
  authorization, detail-overview, draft, and trend behavior tests.
- `Feature/DashboardTest.php`, `Feature/BloodTests/*`, `Feature/ConsultOverview/*`
  - downstream confirmed-only and owner-scope surfaces.
- `Browser/PdfFirstIntakeSmokeTest.php` - browser proof for the intake flow.

## Contracts & Invariants

- Never weaken or delete a failing test to get green output.
- Every downstream surface test must protect `confirmed_at` and owner scoping
  when the feature touches health data.
- Parser fixes start with sanitized synthetic fixtures, not real private PDFs.
- Browser/live QA is required for UI or intake changes; green feature tests alone
  are not enough.
- Tests must not commit or print real biomarker values, private PDFs, symptoms,
  medication notes, or consult exports.

## Patterns

- Add the smallest regression that fails for the real bug before changing parser
  or trust logic.
- Keep focused tests near the feature area, then run `sh scripts/validate.sh`
  before handoff when code changes.
- Include corrupted cross-owner records in privacy-sensitive feature tests when
  a relation can be forged or mislinked.
- Assert forbidden medical copy through architecture/boundary tests when adding
  new user-visible language.
- Use browser QA to observe real routes, counts, source-document visibility, and
  mobile/desktop layout when the workflow changes.

## Anti-patterns

- Do not replace browser QA with only Pest output for intake or UI work.
- Do not use real lab PDFs or real private biomarker data as fixtures.
- Do not test only the happy path when a surface includes selected IDs, exports,
  downloads, delete actions, or user-written free text.
- Do not let draft values into expected dashboard/history/compare/consult/export
  assertions.

## Related Context

- Root rules: `../AGENTS.md`
- Domain rules: `../app/Domain/AGENTS.md`
- Intake domain details: `../app/Domain/Intake/AGENTS.md`
- Consult domain details: `../app/Domain/Consult/AGENTS.md`
- HTTP rules: `../app/Http/AGENTS.md`
- Livewire rules: `../app/Livewire/AGENTS.md`
- Livewire test details: `Feature/Livewire/AGENTS.md`
- Model rules: `../app/Models/AGENTS.md`
- UI rules: `../resources/views/AGENTS.md`
- Validation protocol: `../docs/validation-protocol.md`
