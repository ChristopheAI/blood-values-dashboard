# Consult Overview Views

Owns the printable/export-adjacent consult UI. This surface helps the user take
organized confirmed data to a doctor; it must not become medical advice or a
private-data transport leak.

## Entry Points

- `index.blade.php` - consult selection form and overview shell.
- `_pack.blade.php` - print-ready consult pack sections.

## Contracts & Invariants

- Attention values render before normal values.
- Normal values stay compact; attention and unknown values stay descriptive, not
  urgent or diagnostic.
- Source documents link only through owner-authorized download routes.
- Forms that preserve filters use POST with CSRF when sensitive data may be
  involved.
- User questions may render on the page after POST, but must not be copied into
  CSV export hidden inputs or GET links.
- Print/export controls are commands, not recommendations.

## Patterns

- Use `data-test` selectors on consult pack sections and export/print controls.
- Keep selected blood tests, attention, normal, changes, context, and source
  documents as visibly distinct sections.
- Favor short patient-friendly Dutch/English labels that mirror stored facts.
- Keep mobile layouts scroll-safe and printable layout uncluttered.

## Anti-patterns

- Do not add copy like urgent, concerning, diagnosis, treatment, supplement,
  optimize, or recommended testing.
- Do not expose storage paths, raw source snippets, or foreign filenames.
- Do not hide empty/unknown states by inventing reassurance.
- Do not make CSV export depend on visible page text instead of the domain data.

## Related Context

- View rules: `../AGENTS.md`
- Consult domain: `../../../app/Domain/Consult/AGENTS.md`
- HTTP/export boundary: `../../../app/Http/AGENTS.md`
- Test rules: `../../../tests/AGENTS.md`
