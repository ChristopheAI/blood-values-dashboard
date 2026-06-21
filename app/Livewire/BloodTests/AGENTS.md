# Blood-Test Livewire

Owns the authenticated `/blood-tests/{id}` detail/review component. This is the
interactive bridge between uploaded PDFs, extracted drafts, confirmed values,
source documents, context notes, and patient-friendly overview data.

## Entry Points

- `ReviewBloodTest.php` - detail page, review workflow, manual fallback,
  confirmed edit/delete, draft delete, status recalculation, and trend labels.

## Contracts & Invariants

- `mount()` must reject blood tests not owned by the authenticated user.
- Every action must re-query the owned blood test/result. Public properties such
  as `bloodTestId`, `draftResultId`, `editingResultId`, and form IDs are browser
  input.
- Draft actions are valid only for `entry_source = extracted` and
  `confirmed_at = null`.
- Confirmed edit/delete actions are valid only for rows with non-null
  `confirmed_at`.
- A result linked to a foreign biomarker is forbidden. A null biomarker is only
  acceptable for unconfirmed extracted drafts.
- Confirming or deleting a row must recalculate the parent blood-test status.
- Detail-page overview must be built for the current owned blood test, not the
  latest upload.
- Trend labels are descriptive only, confirmed-only, owner-scoped, and
  comparable only when units match.

## Patterns

- Normalize name, unit, note, and numeric inputs before validation or status
  calculation.
- Use `DetermineBiomarkerStatus` for status, and domain builders for overview
  data.
- Keep manual fallback available when extraction fails, finds no rows, or the
  user needs a correction path.
- Reset draft/edit/form state after successful mutation.

## Anti-patterns

- Do not trust Livewire public state for authorization or ownership.
- Do not show dashboard/history/compare/consult/export-ready data from drafts.
- Do not auto-create or match ambiguous biomarkers without an owner-scoped,
  deterministic check.
- Do not add medical advice, urgency, diagnosis, or extra-testing prompts here.

## Related Context

- Parent Livewire rules: `../AGENTS.md`
- Domain rules: `../../Domain/AGENTS.md`
- Model rules: `../../Models/AGENTS.md`
- Review Blade: `../../../resources/views/livewire/blood-tests/AGENTS.md`
- Livewire tests: `../../../tests/Feature/Livewire/AGENTS.md`
