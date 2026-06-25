# Livewire Feature Tests

Owns feature tests for the blood-test review/detail Livewire component. These
tests protect privacy, confirmed-only behavior, draft review, old-upload detail
pages, and interactive mutation paths.

## Entry Points

- `ExtractedDraftReviewTest.php` - draft display/confirm/delete, manual
  fallback, status recalculation, overview reuse, trends, and copy regressions.
- `BloodTestReviewAuthorizationTest.php` - owner-scope and forbidden cross-owner
  review actions.

## Contracts & Invariants

- Use `Livewire::actingAs($user)` and construct owned records explicitly.
- Every mutation test must prove the row belongs to the selected owned blood
  test and owner-owned biomarker path.
- Draft setup uses `entry_source = extracted` and `confirmed_at = null`.
- Confirmed setup uses non-null `confirmed_at`; downstream expectations must not
  count drafts.
- Include foreign-owner or corrupted-link fixtures when authorization or
  relation boundaries are the point of the test.
- Detail overview regressions must assert the specific blood test under review,
  not only that some overview is visible.

## Patterns

- Assert both Livewire output and persisted database state after confirm, edit,
  delete, or status recalculation.
- Use synthetic biomarker names/values only. No real private PDF data or real
  personal biomarker values in fixtures.
- Keep copy assertions focused on product boundaries: review state, source
  document presence, and no misleading "nothing counts" wording.
- Add a focused test here before changing `ReviewBloodTest.php` behavior.

## Anti-patterns

- Do not weaken authorization assertions to satisfy a UI refactor.
- Do not assert only happy-path owner behavior when selected IDs or public
  component state are involved.
- Do not make drafts appear in expected dashboard/history/compare/consult/export
  data.
- Do not use tests as a place to encode medical interpretation.

## Related Context

- Parent test rules: `../../AGENTS.md`
- Livewire component: `../../../app/Livewire/BloodTests/AGENTS.md`
- Review Blade: `../../../resources/views/livewire/blood-tests/AGENTS.md`
- Domain rules: `../../../app/Domain/AGENTS.md`
