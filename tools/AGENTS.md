# Tools

Owns dev-only, sanitized diagnostic scripts that support working on the app.
Not part of the runtime application and not invoked from `app/`. It does not
own production extraction logic, which lives in `app/Domain/Intake`.

## Entry Points

- `parser_lab/cma_layout_lab.py` - standalone, zero-dependency CLI that reports
  sanitized CMA layout structure (column positions, row/reference counts,
  confidence hints) from local `pdftotext -layout` output. Never prints or
  stores extracted names, values, units, or reference text.

## Contracts & Invariants

- No real biomarker layout text, extracted values, or PDFs are ever committed
  here. Inputs are produced and consumed outside the repo.
- Scripts here are diagnostic only. They must not be imported by, or change
  the behavior of, the Laravel application.
- `parser_lab/cma_layout_lab.py` is a heuristic plausibility check, not a spec
  twin of the production parser: its header-matching rule (word-boundary
  regex) differs from `ExtractCmaLayoutBiomarkerCandidates::header()`
  (case-insensitive substring) and the two can disagree. See ADR-0011,
  amendment 2026-06-30.
- Covered by `tests/python/` and run as a fixed step in `scripts/validate.sh`
  (`python3 -m unittest discover -s tests/python`), so it is held to the same
  "must pass before merge" bar as the rest of the validator, even though it
  ships no production behavior.

## Patterns

- Keep new dev-only scripts dependency-free where practical (see the PEP 723
  inline `requires-python`/`dependencies` header in `cma_layout_lab.py`) so
  they run without a project-wide Python environment.
- If a script's output could ever resemble real extracted patient data, default
  to sanitized/structural output (booleans, counts, lengths) over raw text.
