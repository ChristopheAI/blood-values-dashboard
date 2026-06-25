# Livewire Layer

Owns interactive server-rendered components and Livewire actions. Livewire can
coordinate review workflows, but public properties and action parameters are
browser input and must be treated as untrusted.

## Entry Points

- `BloodTests/AGENTS.md` - local contract for the blood-test detail/review
  component and its confirmed/draft mutation paths.
- `Actions/Logout.php` - starter-kit logout action.

## Contracts & Invariants

- Mounting a model is not enough; ensure the authenticated user owns the blood
  test or related record before reading/mutating it.
- Public properties can be tampered with. Any ID that affects ownership,
  confirmation, deletion, export, or download must be reloaded and authorized
  server-side.
- Draft confirmation must recalculate status using domain rules and normalized
  units/ranges.
- Confirmed overview data must come from domain builders, not duplicated
  Livewire-only queries.

## Patterns

- Keep component state narrow and reset form/draft state after successful
  mutation.
- Use domain services for status, summary, extraction, and comparison behavior.
- After confirming, editing, or deleting results, refresh relations/summary data
  from the database.
- Pair Livewire behavior changes with feature tests under `tests/Feature/Livewire`.

## Anti-patterns

- Do not trust a public `bloodTestId`, `resultId`, selected ID list, or file path.
- Do not compute medical meaning or downstream confirmed-only filtering only in
  the component.
- Do not let draft rows enter dashboard/history/compare/consult/export through
  component convenience queries.
- Do not send private PDF or biomarker data to browser-side scripts or external
  services.

## Related Context

- Root rules: `../../AGENTS.md`
- Domain rules: `../Domain/AGENTS.md`
- Model rules: `../Models/AGENTS.md`
- Blood-test review details: `BloodTests/AGENTS.md`
- View rules: `../../resources/views/AGENTS.md`
- Test rules: `../../tests/AGENTS.md`
