# Privacy Domain

Owns owner-scoped data export and delete-all health-data behavior. This layer
is a privacy boundary, not a reporting or recovery convenience layer.

## Entry Points

- `BuildDataExport.php` - builds the authenticated user's downloadable JSON
  payload.
- `DeleteAllHealthData.php` - removes the user's health data and stored lab PDF
  files.

## Contracts & Invariants

- Exported biomarker values must come through `BiomarkerResult::confirmedForUser`.
- Export is owner-scoped for blood tests, biomarkers, categories, notes,
  reminders, extraction runs, pinned biomarkers, and document metadata.
- Document export may include metadata such as original filename, MIME type, and
  size; it must not expose storage paths or raw PDF contents.
- Delete-all must remove owner records without deleting or leaking foreign
  records, even when corrupted cross-owner links exist.
- Nullable cross-links used for privacy cleanup are intentional. Do not make
  them non-null again without a replacement deletion strategy and tests.
- Stored lab PDFs are private files. A failed file delete is a real failure, not
  a warning to swallow.

## Patterns

- Add export fields explicitly and review whether they are sensitive, derived,
  confirmed-only, metadata-only, or excluded.
- Keep export shape boring and auditable: arrays of records, stable keys, no
  interpretation layer.
- When deletion behavior changes, test both database rows and storage effects.
- Use password-confirmed routes/controllers for transport; keep field-level
  privacy decisions here.

## Anti-patterns

- Do not export drafts, source snippets, storage paths, private PDF text, or
  medical interpretations.
- Do not add restore, sync, or external backup behavior without a spec, ADR, and
  privacy review.
- Do not broaden delete queries by joining from request-supplied IDs.
- Do not log export payloads or deletion details containing private health data.

## Related Context

- Parent domain rules: `../AGENTS.md`
- HTTP privacy routes: `../../Http/AGENTS.md`
- Model scopes/relations: `../../Models/AGENTS.md`
- Database shape: `../../../database/AGENTS.md`
- Tests: `../../../tests/AGENTS.md`
