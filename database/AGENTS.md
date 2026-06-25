# Database Layer

Owns migrations, factories, seeders, and local schema shape. The schema carries
privacy and product contracts; it is not just persistence plumbing.

## Entry Points

- `migrations/2026_06_18_000001_*` through `2026_06_18_000010_*` - blood tests,
  documents, biomarkers, results, context, reminders, and extraction runs.
- `migrations/2026_06_20_000001_make_pinned_biomarker_biomarker_nullable.php` -
  nullable follow-up link used by privacy deletion behavior.
- `factories/*Factory.php` - synthetic owned records for tests.
- `seeders/DatabaseSeeder.php` - local development seed surface.

## Contracts & Invariants

- `user_id` is the owner boundary for blood tests, biomarkers, categories,
  context notes, reminders, and pinned biomarkers.
- `biomarker_results.confirmed_at` is the downstream trust gate.
- `entry_source = extracted` with `confirmed_at = null` represents review-only
  draft intake data.
- `blood_test_documents.storage_path` is a generated private storage reference;
  `original_filename` is sanitized metadata, not a trusted path.
- Result rows store the value/range/unit evidence used at confirmation time.
  Later catalog changes must not silently rewrite history.
- Nullable biomarker links exist for extracted drafts and privacy cleanup. Keep
  model/query code defensive around nulls.
- `source_snippet` is private extraction evidence. Use synthetic text in tests
  and never export/log it by default.

## Patterns

- Add migrations that preserve existing owner-scope and confirmed-only queries.
- Update factories when schema invariants change so tests keep building valid
  owner-aligned records.
- In tests, explicitly align `BloodTest`, `Biomarker`, and `User` when asserting
  owner-scoped behavior.
- Keep database changes paired with feature tests when they affect privacy,
  export, deletion, confirmation, or source documents.

## Anti-patterns

- Do not add external-provider IDs, AI output fields, OCR payloads, or raw PDF
  text without a spec, ADR, and privacy review.
- Do not make nullable privacy-cleanup links non-null to simplify a relation.
- Do not treat `database/database.sqlite` as a source artifact to edit by hand.
- Do not put real private biomarker values, PDF text, symptoms, or medication
  notes in factories or seeders.

## Related Context

- Root rules: `../AGENTS.md`
- Model rules: `../app/Models/AGENTS.md`
- Domain rules: `../app/Domain/AGENTS.md`
- Privacy domain: `../app/Domain/Privacy/AGENTS.md`
- Test rules: `../tests/AGENTS.md`
