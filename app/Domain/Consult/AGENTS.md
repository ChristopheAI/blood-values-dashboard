# Consult Domain

Owns the data contract for consult/export outputs. It turns selected owned
blood tests into confirmed-only collections for attention, normal values,
changes, context, source documents, and user questions. It does not own UI
layout, CSV streaming, route authorization, or medical interpretation.

## Entry Points

- `BuildConsultOverview.php` - builds consult overview data from a `User` plus
  explicit filters.

## Contracts & Invariants

- Every biomarker value emitted here must pass `BiomarkerResult::confirmedForUser`.
- Selected `blood_test_ids` must resolve to owned blood tests only; foreign IDs
  must not leak existence or data.
- Pinned biomarkers use owner-owned biomarker scopes.
- Context notes are user-authored observations; never convert them into advice.
- Source documents are metadata for owner-authorized links, not raw storage
  paths.
- Trend/change rows are descriptive and only comparable when values are numeric
  and units match. No automatic unit conversion here.
- `questions` may be shown in the consult view but must not be propagated into
  CSV export forms, GET URLs, logs, or browser history.

## Patterns

- Keep include flags explicit: attention, normal, trends, context, pinned, source
  documents.
- Put cross-surface change/trend rules in shared domain logic when they outgrow
  consult-only behavior.
- Return empty collections for empty selections instead of widening queries.
- Prefer boring labels such as changed, higher/lower, normal, unknown; avoid
  clinical phrasing.

## Anti-patterns

- Do not read draft or unconfirmed rows for consult/export output.
- Do not rank attention values by urgency or imply triage.
- Do not include private source snippets or file storage paths in export data.
- Do not let consult output become a recommendation engine.

## Related Context

- Parent domain rules: `../AGENTS.md`
- HTTP/export boundary: `../../Http/AGENTS.md`
- Models: `../../Models/AGENTS.md`
- Consult views: `../../../resources/views/consult-overview/AGENTS.md`
- Tests: `../../../tests/AGENTS.md`
