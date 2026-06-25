# Views Layer

Owns Blade and Livewire-facing presentation for the Laravel blood-values app.
Views may render safe data and guide workflows, but they do not own domain
truth, authorization, confirmed-only filtering, or medical interpretation.

## Entry Points

- `dashboard.blade.php` and `dashboard/_blood-results-overview.blade.php` -
  upload-first dashboard and patient-friendly confirmed results.
- `livewire/blood-tests/AGENTS.md` - local contract for the review/detail
  surface, confirmed table, draft strip, source documents, and manual fallback.
- `blood-tests/*.blade.php` - blood-test list, compare, and upload dropzone.
- `consult-overview/AGENTS.md` - local contract for consult/print/export
  presentation.
- `partials/`, `layouts/`, and `components/` - shared structure and navigation.

## Contracts & Invariants

- Render only data already authorized and filtered by controllers/domain
  builders.
- Confirmed-only surfaces must not silently add draft values in the view.
- Source documents are linked only through named owner-authorized routes; never
  expose storage paths.
- Forms that carry sensitive health text or consult questions must use POST
  bodies with CSRF, not GET query strings.
- Copy must remain descriptive: personal tracking, values, ranges, changes,
  source documents, and consult preparation. No diagnosis, treatment,
  supplement advice, health scoring, or extra-testing encouragement.
- `unknown` is an honest state, not a problem to hide with reassuring copy.

## Patterns

- Prefer compact, scan-friendly operational layouts over marketing-style cards.
- Reuse partials for repeated value rows and result summaries.
- Keep management/review fallback visible below patient-friendly summaries when
  drafts or corrections still matter.
- Use `data-test` selectors for important UI states that feature/browser tests
  assert.
- Keep mobile layouts free of horizontal overflow; tables need responsive
  treatment or compact alternatives.

## Anti-patterns

- Do not implement owner checks, confirmed-only filtering, status calculation,
  or comparison math in Blade as the only enforcement point.
- Do not include sensitive values, notes, questions, file names, or biomarker
  data in generated GET URLs.
- Do not make attention sections sound urgent, diagnostic, or prescriptive.
- Do not hide draft/review friction if the domain state still requires review.

## Related Context

- Root rules: `../../AGENTS.md`
- Domain rules: `../../app/Domain/AGENTS.md`
- HTTP/form routing rules: `../../app/Http/AGENTS.md`
- Livewire component rules: `../../app/Livewire/AGENTS.md`
- Blood-test review views: `livewire/blood-tests/AGENTS.md`
- Consult view details: `consult-overview/AGENTS.md`
- Test rules: `../../tests/AGENTS.md`
