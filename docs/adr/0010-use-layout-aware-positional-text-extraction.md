# ADR-0010: Use Layout-Aware Positional Text Extraction As A Local Fallback

## Status

Accepted

Step 0 was completed locally on 2026-06-19. The existing parser's positional API
returned usable positioned text fragments from a digital text-layer tabular PDF.
No PDF content, lab values, filenames, or personal data were logged or committed.

## Context

ADR-0009 added local best-effort extraction that turns a lab PDF's text layer into
draft biomarker values behind the existing confirm gate. Its first strategy matches
simple inline lines: one marker, its value, unit, and an inline reference range on a
single line.

Some real lab reports use a tabular layout instead, with separate columns for the
analysis name, value, unit, and reference. These reports still carry a digital text
layer, but the values are positioned spatially across columns rather than written as
inline lines. On such a report the inline strategy matches nothing, so extraction
completes with zero candidates and the user falls back to fully manual entry.

We want a layout-aware fallback that recovers candidates from tabular text layers
while staying strictly inside the ADR-0009 boundary: local only, no OCR, no AI/LLM,
no external processing of private PDFs, and no new dependency.

## Decision

Add a second, local, layout-aware extraction strategy that reuses the already
approved PDF parser's positional API (text fragments with x/y coordinates from the
existing dependency — no new package). It reconstructs the table from fragment
positions:

- group fragments into rows by their vertical position;
- detect a header row by matching known column labels, and derive the horizontal
  band of each column (analysis, value, unit, reference);
- for each data row, read the cell under each band and parse the value, unit, and
  reference range;
- emit a candidate only when a header is recognized and the row has at least a name
  and a numeric value.

Run the strategies as a cascade: the existing inline strategy first; if it yields
nothing, the positional strategy. Either may return zero candidates.

This changes only how draft candidates are produced. Every candidate remains a draft
(`entry_source = 'extracted'`, `confirmed_at = null`); draft-then-confirm and
confirmed-only downstream are untouched. Positional text-layer reconstruction reads
the PDF's own text coordinates and is not OCR (no rasterization, no image
processing), so it stays within ADR-0009 and needs no new package and no OCR/AI path.

## Stop Conditions

Codex must stop and report before building if any of these hold:

- the positional API returns no usable coordinates (empty matrices or a single
  undifferentiated text blob) for a digital text-layer PDF — positional
  reconstruction is then infeasible and the PDF may be image-only, which is OCR
  territory and out of scope;
- making extraction work would require OCR, image rasterization, an external service
  or API, an AI/LLM, or any new package that performs network calls;
- usefulness would require relaxing the confirmed-only invariant, auto-confirming, or
  silently creating or auto-mapping catalog entries;
- deterministic testing would require committing a real lab PDF as a fixture, or
  logging PDF text or values at runtime;
- header or column detection cannot be made deterministic and testable;
- scope expands toward handling every possible lab layout in one slice.

## Evidence

- Source: `docs/adr/0009-use-local-best-effort-pdf-extraction.md`
  - Claim type: fact
  - Summary: Local best-effort, draft-then-confirm extraction is accepted; the first
    strategy matches only simple inline lines.

- Source: project session observation (sanitized)
  - Claim type: fact
  - Summary: A tabular lab report with a digital text layer produced an extraction
    run that completed with zero candidates under the inline strategy.

- Source: `AGENTS.md`, `docs/adr/0008-future-ai-agents-must-be-proposal-only.md`
  - Claim type: fact
  - Summary: No OCR, AI/LLM, or external processing of private lab PDFs; a dependency
    touching health data needs a separate review.

- Source: step 0 local feasibility check (sanitized, 2026-06-19)
  - Claim type: fact
  - Summary: `Page::getDataTm()` exists in `smalot/pdfparser` and returned 293
    positioned fragments across three pages, with x/y coordinate ranges, numeric
    fragments, and generic column labels for analysis, unit, and reference
    detectable without printing PDF content or values. This proves the positional
    fallback is feasible enough to build against synthetic fixtures.

## Considered Options

- Keep the inline-only strategy (rejected: returns nothing on tabular layouts).
- Broaden the inline regex over flattened text (insufficient when the flattened text
  is column-major rather than row-major).
- Positional/columnar reconstruction from the existing parser's coordinates (chosen).
- OCR, an external extraction service, or an AI/LLM (rejected: breaks the ADR-0009
  boundary; each would need its own ADR and review).

## Decision Drivers

- Tabular lab reports are common; the fallback must generalize beyond one inline
  format.
- Positional reconstruction reuses the existing local dependency — no new package, no
  network, no OCR.
- The draft-then-confirm gate makes an imperfect parse safe: every candidate is a
  correctable draft.
- The change is contained to the extraction domain layer; downstream stays
  confirmed-only.
- Determinism and synthetic-only testing are required for trust.

## Consequences

- A second extraction strategy is added behind the existing candidate interface; the
  PDF-to-fragments adapter is separated from the pure fragments-to-candidates logic
  so the logic is unit-testable without a PDF.
- Confidence becomes meaningful per strategy (inline high, positional medium,
  ambiguous cell low), enabling the low-confidence flag in the review screen
  (ADR-0009 follow-up).
- A synthetic tabular fixture and its deterministic extraction test are added; no
  real lab PDF is ever committed.
- `PrivacyBoundaryTest` continues to guard against external/AI processing; the
  positional path adds no network and no image processing.
- The full acceptance criteria, test contract, and build order live in the matching
  kickoff, `docs/codex-v2-tabular-extraction-kickoff.md`.
- If future implementation evidence contradicts the step 0 result, the slice must
  stop before shipping positional code.

## Confidence

Medium-high. Step 0 proved usable positioned fragments, but row and column
reconstruction still need tests before implementation can be trusted.

## Follow-Up Questions

- Which column labels and reference formats actually occur, and are they covered by
  the synthetic fixture?
- What vertical and horizontal tolerances reliably cluster rows and columns without
  merging them?
- How are repeated per-page headers and non-data section rows handled?
- Should the extraction run record which strategy produced it, for audit and
  debugging?
