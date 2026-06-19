# Codex Kickoff — V2 Slice: Layout-Aware Tabular Extraction Fallback

Date: 2026-06-19

Prepared from a sanitized Cowork review session. V1 and the first V2 extraction
slice are merged on `main`. Tabular lab reports can produce zero candidates because
ADR-0009's inline strategy only matches single-line entries. This slice adds the
positional fallback from ADR-0010. Sanitized brief: no real PDF content, lab
values, filenames, or personal data.

## Governance — step 0 result

ADR-0010 is **Accepted** after a local, sanitized feasibility check on 2026-06-19.

- `smalot/pdfparser` exposes `Page::getDataTm()`.
- The local check returned positioned fragments with x/y coordinates and generic
  column labels, without printing or committing PDF content, lab values, filenames,
  or personal data.
- Build may proceed only against synthetic fixtures and the guardrails below.

## Approach (from ADR-0010)

- Add a positional/columnar strategy behind the existing candidate interface; run it
  as a cascade after the inline strategy (inline first; if empty, positional; either
  may return none).
- Split a thin PDF-to-fragments adapter (the only code touching the parser) from the
  pure fragments-to-candidates logic (row/column geometry + cell parsing), so the
  logic is unit-testable without a PDF.
- Emit a candidate only when a header row is recognized and the row has at least a
  name and a numeric value.

## Conventions (reuse, do not reinvent)

- Extraction stays in the domain layer; output stays the existing candidate type;
  nothing downstream changes.
- Reuse owner-scoping, the confirmed-value skip, the `unique(blood_test_id,
  biomarker_id)` constraint, and the existing status calculator unchanged.

## Acceptance criteria

- A synthetic tabular fixture (columns: analysis, value, unit, reference; synthetic
  names and synthetic numbers) yields the expected candidates deterministically
  (was zero).
- Every candidate is a draft (`extracted`, `confirmed_at` null); blank or unparseable
  reference → `unknown` status via the existing calculator.
- No header recognized → zero candidates (never guess column meaning).
- Drafts stay out of dashboard, history, compare, consult, and export until confirmed.
- A confirmed value is never overwritten; an unmatched name keeps its raw extracted
  name with no biomarker link.
- No new package, no network, no OCR/AI; `PrivacyBoundaryTest` stays green.
- Confidence is set per strategy (inline high, positional medium, ambiguous low) and
  the low-confidence flag renders.
- Extraction failure leaves the blood test in review with manual entry intact.

## Test contract (write first)

- Unit, no PDF: row clustering by vertical position; header detection → column bands;
  cell-under-band reads; reference parsing for decimal comma, dash range, one-sided
  (`<` / `>`), and blank → null; non-numeric value row skipped; candidate cap.
- Feature: the synthetic tabular fixture → expected candidates, deterministic.
- Feature: a header-not-found fixture → zero candidates.
- Invariant (extended): tabular drafts never appear in any confirmed-only surface
  until confirmed.
- Regression: the existing inline fixture still extracts.
- Architecture: `PrivacyBoundaryTest` green — no external/AI/network in the path.
- Confidence tiers asserted per strategy.

## Stop conditions

Follow ADR-0010 "Stop Conditions". In short, stop before building if: positional
fragments are unusable; the only working path needs OCR, AI/LLM, an external service,
or a new network-calling package; usefulness needs relaxing confirmed-only or
auto-confirm/auto-map; deterministic testing would need a real PDF or runtime content
logging; detection cannot be deterministic; or scope creeps toward all layouts.

## Guardrails (non-negotiable)

- Local and in-process; no network; not OCR. No new dependency without a separate ADR
  and package review.
- Draft-then-confirm and confirmed-only downstream are hard. No medical copy.
- Synthetic fixtures only; never commit a real PDF; never log PDF content or values.

## Working agreement (from /implement)

- Full quality; tests-first; commit incrementally on `codex/v2-tabular-extraction`
  (step 0 + ADR finalize → adapter + pure logic + unit tests → cascade wiring →
  feature + invariant tests). No TODO stubs. If you must pause, write `progress.md`
  and commit. Only "done" when `sh scripts/validate.sh` is green. Do not merge —
  review first; track with a V2-milestone issue and close it from the merge commit.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/adr/0009-use-local-best-effort-pdf-extraction.md,
docs/adr/0010-use-layout-aware-positional-text-extraction.md, and
docs/codex-v2-tabular-extraction-kickoff.md. V1 and the first extraction slice are on
main. ADR-0010 is Accepted after a local sanitized feasibility check proved usable
positioned fragments. Do not repeat that check by logging PDF content or committing
real PDFs.

Build on codex/v2-tabular-extraction, tests-first, per the kickoff:
- a thin PDF-to-fragments adapter + pure fragments-to-candidates logic (row/column
  reconstruction, header anchor, cell parsing), unit-tested without a PDF;
- a positional strategy that runs after the inline one (cascade), emitting drafts
  only when a header is found and a row has a name + numeric value;
- a synthetic tabular fixture + deterministic test; header-not-found → zero;
- extend the invariant test so tabular drafts never reach status/history/compare/
  consult/export until confirmed; keep PrivacyBoundaryTest and the inline fixture green;
- confidence per strategy.

No OCR, no AI/LLM, no external processing, no new package. Confirmed-only and
draft-then-confirm stay hard. Stop and report when sh scripts/validate.sh is green.
Do not merge; I review first.
```
