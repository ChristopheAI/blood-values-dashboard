# Models Layer

Owns Eloquent records, relationships, casts, fillable fields, and reusable query
scopes. Models can encode ownership and confirmed-only relationship contracts,
but broader workflows belong in `app/Domain`.

## Entry Points

- `BloodTest.php` - owner root for documents, results, extraction runs, context
  notes, and confirmed result relation.
- `BiomarkerResult.php` - measured value, unit/range snapshot, status,
  `confirmed_at`, extraction metadata, and `confirmedForUser` /
  `reviewDraftsForUser` scopes.
- `Biomarker.php`, `BiomarkerCategory.php`, `PinnedBiomarker.php` - owner-scoped
  catalog and follow-up markers.
- `BloodTestDocument.php` - private source document metadata, not the PDF path
  authorization layer.
- `ContextNote.php`, `Reminder.php` - user-owned context and planning records.

## Contracts & Invariants

- `BloodTest::confirmedResults()` and `BiomarkerResult::confirmedForUser()` are
  trust-boundary helpers; keep both `confirmed_at` and owner-owned biomarker
  checks intact.
- `BiomarkerResult` stores the range/unit evidence used at entry time. Do not
  assume later catalog edits should rewrite historical results.
- Nullable cross-links may exist after privacy deletion or corrupted fixtures;
  queries must handle nulls without leaking foreign data.
- `BloodTestDocument` stores generated storage location plus sanitized original
  filename metadata; authorization is enforced by controller/domain access
  paths.

## Patterns

- Add small reusable scopes for owner/confirmed filters when multiple domain
  builders need the same boundary.
- Keep relationship methods narrow and explicit; avoid broad eager-loading
  helpers that mix owners.
- Update factories/tests when a model-level invariant changes.

## Anti-patterns

- Do not remove owner checks from confirmed scopes to make joins easier.
- Do not put parser, export, consult, or UI formatting workflows in models.
- Do not make original filenames trusted paths.
- Do not add automatic unit conversion or optimal-range logic at model level.

## Related Context

- Root rules: `../../AGENTS.md`
- Domain rules: `../Domain/AGENTS.md`
- HTTP rules: `../Http/AGENTS.md`
- Test rules: `../../tests/AGENTS.md`
