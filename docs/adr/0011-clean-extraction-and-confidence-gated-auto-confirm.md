# ADR-0011: Clean-By-Default Extraction And Confidence-Gated Auto-Confirm

## Status

Proposed

The auto-confirm policy relaxes the confirmed-only trust boundary set in ADR-0005
and ADR-0009, so it is recorded explicitly. Stays Proposed until the name cleaning
and the confidence threshold are validated on synthetic fixtures and live-verified.

## Context

Tabular extraction (ADR-0010) works but produces best-effort drafts that the user
must review and confirm one by one — friction the product owner explicitly does not
want. Two separate things cause that friction: imperfect parsing (e.g. prose merged
into a biomarker name), and the per-value confirm step itself. The owner wants clean
extraction by default, and no manual confirmation for values the system is confident
about.

## Decision

Reduce extraction friction to near-zero while keeping a safety net:

1. Clean-by-default extraction.
   - Anchor the extracted name on the user's biomarker catalog: if a catalog
     biomarker name is a prefix of (or confidently matches) the extracted text, use
     the catalog's canonical name and set `biomarker_id`. Deterministic, local, no AI.
   - Per-format tuning of row/column geometry against merged prose (see the finetune
     loop), so a known layout extracts clean.
2. Confidence-gated auto-confirm.
   - Extracted rows at or above a conservative confidence threshold are auto-confirmed
     (`confirmed_at` set) on upload — they become tracked data with zero user action.
   - Rows below the threshold (uncertain parse, unmatched name, missing unit/range)
     stay as drafts for an optional glance.
   - Every auto-confirmed value stays fully editable and deletable, keeps its link to
     the source PDF, and is labelled as auto-filled from the PDF.
3. Finetune loop. Each real lab format that extracts imperfectly is reproduced as a
   sanitized synthetic fixture (no real PDF/values) and the parser is tuned until that
   format is clean, locked by a test. For the small set of labs the user actually
   uses, this converges to effectively perfect.

This relaxes ADR-0009's "structured values become usable only after explicit user
review": high-confidence extracted values may now be auto-confirmed. Draft-then-confirm
remains for everything below the threshold; the source PDF and full editability remain
the backstop.

## Stop Conditions

- Do not auto-confirm anything below the tuned confidence threshold.
- Do not auto-create catalog entries from extracted names; catalog anchoring only
  matches existing entries.
- No OCR, AI/LLM, external service, network call, or new package.
- Determinism and synthetic-only testing; never commit a real PDF or log values.
- If a clean, confident result cannot be produced deterministically for a layout, the
  row stays a draft — never a wrong auto-confirm.

## Evidence

- Source: product owner decision (sanitized session, 2026-06-19)
  - Claim type: fact
  - Summary: The owner wants zero confirmation friction for confidently-extracted
    values; cleaning up messy drafts is not the user's job.

- Source: live verification (blood test 5, sanitized)
  - Claim type: fact
  - Summary: Bounded names and low-confidence flags work; the residual friction is the
    few prose-polluted / low-confidence drafts.

- Source: `docs/adr/0009-...` and `docs/adr/0010-...`
  - Claim type: fact
  - Summary: Extraction is local best-effort behind a confirm gate; this ADR
    deliberately relaxes the gate for high-confidence values only.

## Considered Options

- Keep manual confirm for every extracted value (rejected: the friction the owner
  refused).
- Auto-confirm everything regardless of confidence (rejected as default: a wrong
  machine read would silently enter tracked data and the doctor export).
- Clean-by-default plus confidence-gated auto-confirm (chosen).
- OCR/AI to "perfect" extraction (rejected: breaks the ADR boundary; own risks/review).

## Decision Drivers

- Zero friction for the normal case is an explicit product goal.
- A wrong auto-confirmed lab value would silently enter trends and the doctor export,
  so auto-confirm must be confidence-gated and reversible.
- Catalog anchoring and per-format tuning are local, deterministic, and testable.
- For a personal app with a few labs, finetuning converges to clean quickly.

## Consequences

- `biomarker_results` may be created with `confirmed_at` set at upload for
  high-confidence rows.
- A named confidence threshold, the catalog anchor, and per-format tuning are added to
  the extraction path; all unit-tested on synthetic fixtures.
- The review screen shows auto-confirmed values (labelled, editable) plus any remaining
  low-confidence drafts.
- Owner-scoping, no-overwrite-of-existing-confirmed, `PrivacyBoundaryTest`, and the
  no-OCR/AI boundary all stay.
- "Perfect for every PDF" is explicitly not promised; the threshold plus the draft
  fallback handle the unseen and the uncertain.

## Confidence

Medium. The cleaning and the threshold need synthetic-fixture validation and live
verification before this is Accepted.

## Follow-Up Questions

- What confidence threshold balances "auto-confirm clean values" against "never
  auto-confirm a wrong one"?
- Should auto-confirmed values be visually distinct from user-confirmed ones (an
  "auto" tag)?
- How is catalog anchoring scored (exact prefix vs fuzzy) to avoid matching the wrong
  biomarker?
