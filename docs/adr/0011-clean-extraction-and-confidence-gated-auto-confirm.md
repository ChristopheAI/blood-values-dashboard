# ADR-0011: Clean-By-Default Extraction And Confidence-Gated Auto-Confirm

## Status

Proposed

The auto-confirm policy relaxes the confirmed-only trust boundary set in ADR-0005
and ADR-0009, so it is recorded explicitly. The implementation has been validated
on synthetic fixtures and automated browser/feature checks on
`codex/v2-clean-autoconfirm`, but the ADR stays Proposed until owner review and a
fresh live upload verify the flow with a real local PDF.

## Context

Tabular extraction (ADR-0010) works but produces best-effort drafts that the user
must review and confirm one by one — friction the product owner explicitly does not
want. Two separate things cause that friction: imperfect parsing (e.g. prose merged
into a biomarker name), and the per-value confirm step itself. The owner wants clean
extraction by default, and no manual confirmation for values the system is confident
about.

The "prose merged into a biomarker name" failure has a concrete, identified root
cause. Positioned fragments did not record their page, and rows were clustered by
vertical position across the whole document. So a biomarker name on the results page
and an unrelated line on a later non-table page — a billing/marketing page bundled
into the same PDF — that happened to share a vertical coordinate collapsed into one
row, and the name cell absorbed the foreign text. The earlier name-bounding fix only
capped the symptom; clustering per page removes the cause.

## Decision

Reduce extraction friction to near-zero while keeping a safety net:

1. Clean-by-default extraction.
   - Page-aware row clustering: positioned fragments carry their page number, and rows
     are reconstructed within a single page, never across pages. This removes the
     cross-page collision that was the main source of merged-in prose, and means a
     non-table page (no recognized header row) contributes no candidates.
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
4. Upload-first, instant-result intake (UX expression of the zero-friction goal).
   - The empty intake/dashboard state is the upload itself — a single "drop your lab
     PDF" zone (PDF only; no OCR/image path, per ADR-0009), not a form. No account or
     email step is introduced, and nothing leaves the device.
   - While the local parse runs, a deterministic progress affordance shows the stages
     (extract → values → status → trend) so the work is visible.
   - On completion the user lands on the result — auto-confirmed values, status, and
     trend — with any below-threshold rows in a small review strip. The first thing
     shown is the user's own data, not a form.
   - This is the local, no-funnel counterpart to commercial upload-first demos: the
     same immediacy, without lead capture, off-device upload, or AI.

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
  - Summary: Bounded names and low-confidence flags work, but only cap the symptom; the
    residual friction is the few prose-polluted / low-confidence drafts.

- Source: real tabular layout reviewed (sanitized, 2026-06-19)
  - Claim type: fact
  - Summary: The merged-in prose originates on a separate non-table page bundled into
    the same PDF, not interleaved in the results table. The parser merged it because
    rows were clustered by vertical position across pages. Page-aware clustering
    removes the cause; no real content or values were logged or committed.

- Source: synthetic continuation regression (2026-06-19)
  - Claim type: fact
  - Summary: Headerless later pages only inherit the active table layout when the
    previous page ended with a table row near the bottom; standalone later-page prose
    that merely aligns with the learned columns is ignored.

- Source: synthetic ambiguity regressions (2026-06-19)
  - Claim type: fact
  - Summary: Ambiguous exact aliases and equally-strong catalog prefixes stay
    unanchored drafts; they are never auto-confirmed by picking an arbitrary match.

- Source: synthetic missing-unit regression (2026-06-19)
  - Claim type: fact
  - Summary: A numeric tabular row without a unit is preserved as a low-confidence
    draft instead of being dropped or auto-confirmed.

- Source: automated intake UX checks (2026-06-19)
  - Claim type: fact
  - Summary: The upload-first screen exposes the PDF dropzone/input selectors,
    auto-submits file selection into the result flow, removes the separate upload
    submit button, and keeps the file input constrained to PDFs. Native OS picker
    opening and drag/drop acceptance remain manual live-review checks.

- Source: extraction-run accounting regression (2026-06-19)
  - Claim type: fact
  - Summary: `candidate_count` records extracted candidates even when storage skips a
    row because a confirmed value already exists, so extraction telemetry does not
    undercount parser output.

- Source: competitive UX review (sanitized, 2026-06-19)
  - Claim type: fact
  - Summary: Commercial upload-first demo flows lead with the PDF upload as the first
    action and land on an immediate result, which reads as strong; they pay for it with
    email/lead capture and off-device processing. The local app reproduces the
    immediacy with neither cost.

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

- `PositionedTextFragment` carries a page number, and tabular rows are clustered per
  page, so text from a different page never merges into a cell.
- A later page without its own table header may continue the active layout only after
  the previous page ended with a table row near the bottom; otherwise it is treated as
  non-table context.
- The intake/empty state becomes upload-first (a dropzone, not a form), and the
  post-upload screen lands on auto-confirmed results rather than an empty review form.
  No email or account is introduced; nothing leaves the device.
- `biomarker_results` may be created with `confirmed_at` set at upload for
  high-confidence rows.
- A named confidence threshold, the catalog anchor, and per-format tuning are added to
  the extraction path; all unit-tested on synthetic fixtures.
- Catalog anchoring must be unambiguous. Ambiguous exact aliases or equal-length prefix
  matches stay as drafts instead of becoming auto-confirmed values.
- Missing-unit rows are kept as low-confidence drafts. They are useful review evidence,
  but they cannot pass the auto-confirm gate.
- The review screen shows auto-confirmed values (labelled, editable) plus any remaining
  low-confidence drafts.
- Owner-scoping, no-overwrite-of-existing-confirmed, `PrivacyBoundaryTest`, and the
  no-OCR/AI boundary all stay.
- "Perfect for every PDF" is explicitly not promised; the threshold plus the draft
  fallback handle the unseen and the uncertain.

## Confidence

Medium-high for the deterministic implementation path: synthetic unit/feature/browser
checks cover page-aware clustering, continuation rules, catalog anchoring, ambiguity,
missing units, candidate accounting, confirmed-only downstream behavior, and the
upload-first intake result flow. Still Proposed until owner review and a fresh live
upload verify the same flow outside synthetic fixtures.

## Follow-Up Questions

- Does owner live review accept `AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85` for the
  first supported lab format?
- Does the visible "auto-filled from PDF" treatment give enough distinction from
  manually confirmed values during real review?
- Which next real lab format needs a sanitized synthetic fixture in the finetune loop?
