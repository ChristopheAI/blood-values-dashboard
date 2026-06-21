# Blood-Test Review Views

Owns the Blade for the blood-test detail/review surface. It presents authorized
component data; it does not enforce ownership, confirmed-only filtering, or
domain meaning by itself.

## Entry Points

- `review-blood-test.blade.php` - patient-friendly overview include, intake
  progress, confirmed values table, source documents, review strip, manual form,
  and linked context notes.

## Contracts & Invariants

- The overview include must render the `bloodTestOverview` for this blood test,
  not a global latest-upload summary.
- Confirmed rows, draft rows, counts, and progress states may be derived from
  the loaded component data, but authorization and trust filtering belong in the
  component/domain layer.
- Source documents link only through named owner-authorized download/delete
  routes. Never show storage paths.
- `wire:click` IDs are display affordances only; the Livewire component must
  re-authorize them.
- Context note bodies are user-authored private text. Render them plainly, do
  not interpret them.
- Copy must stay operational and descriptive: source document, values, ranges,
  review state, previous value. No diagnosis, treatment, urgency, or advice.

## Patterns

- Keep the patient-friendly overview above the management/review layer.
- Preserve the review fallback below the overview when drafts, corrections, or
  manual entry still matter.
- Use `data-test` selectors for confirmed rows, draft rows, source documents,
  progress stages, and form controls that tests or browser QA rely on.
- Keep tables responsive or compact enough for mobile review.

## Anti-patterns

- Do not hide draft review friction to make the page look complete.
- Do not put biomarker values, notes, filenames, or consult text into GET URLs.
- Do not calculate status, trend comparability, owner scope, or confirmed-only
  eligibility as the only enforcement in Blade.
- Do not add frontend scripts or external widgets for private lab data.

## Related Context

- Parent view rules: `../../AGENTS.md`
- Livewire component: `../../../../app/Livewire/BloodTests/AGENTS.md`
- Domain rules: `../../../../app/Domain/AGENTS.md`
- Tests: `../../../../tests/Feature/Livewire/AGENTS.md`
