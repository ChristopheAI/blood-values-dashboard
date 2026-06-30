# ADR-0011: Clean-By-Default Extraction And Confidence-Gated Auto-Confirm

## Status

Accepted (amended 2026-06-25)

### Amendment 2026-06-25 — safe below-detection auto-confirm

Trusted CMA tabular/layout rows whose value is a detection limit (`<10`, `<1,1`) may
auto-confirm when the parsed bound is compatible with a one-sided reference:

- below-detection (`<X`) with only a maximum reference: auto-confirm as `normal`
  when `X <= referenceMaximum`;
- above-detection (`>X`) with only a minimum reference: auto-confirm as `normal`
  when `X >= referenceMinimum`;
- when the bound crosses the incompatible side of the threshold (`<14` with max
  `13`), confidence stays below the auto-confirm threshold and the row remains a
  draft with `unknown` status until review.

This keeps ADR-0011 conservative while removing review friction for deterministic
negative inflammation markers such as RA and CCP on native CMA PDFs.

### Amendment 2026-06-25 — no auto-confirm of `unknown`-status values

The original policy auto-confirmed trusted CMA rows that lacked parseable reference
bounds, recording them with `unknown` status. This amendment narrows that: a value the
system cannot classify (no usable reference range, so status resolves to `unknown`) is no
longer auto-confirmed. It is still auto-imported — the owner-scoped biomarker is created
and the value is stored — but as an unconfirmed draft routed to review, so the owner
eyeballs an unclassifiable value before it becomes tracked data. Rows with a one-sided or
full reference range are unaffected and still auto-confirm. This tightens, never relaxes,
the trust boundary, so it stays within the accepted policy. Enforced by
`RunBloodTestExtraction::autoConfirmYieldsConclusiveStatus`.

### Amendment 2026-06-30 — parser-lab is a heuristic, not a spec twin

`tools/parser_lab/cma_layout_lab.py` (sanitized, dev-only CMA structure check, run via
`scripts/validate.sh`) uses its own header-matching rule — a word-boundary regex — while
the production parser, `ExtractCmaLayoutBiomarkerCandidates::header()`, matches headers
as a case-insensitive substring (`stripos`). The two rules can disagree on whether a given
layout's header is detectable, so the lab is documented as a heuristic plausibility check,
not a guarantee of what the real parser will do. No code change: the lab never writes to
the database, so a disagreement costs a wasted manual check, not a wrong stored value. If
the two are ever brought into lockstep, this amendment should be superseded rather than
silently dropped.



The auto-confirm policy relaxes the confirmed-only trust boundary set in ADR-0005
and ADR-0009, so it is recorded explicitly. The implementation has been validated
on synthetic fixtures, automated browser/feature checks, and a fresh owner-led
local upload on `codex/v2-clean-autoconfirm`.

The 2026-06-24 live gate accepted the policy as "passed with review remainder":
high-confidence rows entered downstream through `confirmed_at`, one uncertain
extracted row stayed as a draft, and the blood test remained in review until the
owner handles that draft. This accepts the trust policy; it does not approve
lowering thresholds, auto-confirming all rows, merging the branch, or expanding
into OCR, AI, external processing, or medical advice.

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
   - Trusted CMA layout/tabular rows may create the missing catalog biomarker during intake
     when the catalog is empty or incomplete, but only after the same deterministic
     value/unit gates pass and no duplicate name or catalog conflict exists. Missing
     reference bounds are allowed only for trusted CMA values and produce `unknown`
     status, never a guessed normal/high/low status.
   - Trusted CMA duplicate names may be auto-imported only when their units uniquely
     disambiguate the duplicate rows. In that case the local biomarker name receives a
     unit suffix, e.g. `Marker (%)`, before matching/import. Same-unit duplicates still
     stay in review.
   - Per-format tuning of row/column geometry against merged prose (see the finetune
     loop), so a known layout extracts clean.
2. Confidence-gated auto-confirm.
   - Extracted rows at or above a conservative confidence threshold are auto-confirmed
     (`confirmed_at` set) on upload — they become tracked data with zero user action.
   - Rows below the threshold, non-CMA unmatched names, duplicate CMA names, catalog
     conflicts, uncertain parses, or incomplete-but-actionable rows stay as drafts for
     an optional glance. Trusted CMA fragments with no unit and no reference are
     discarded as non-actionable instead of shown as review friction.
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
- Do not auto-create catalog entries from generic extracted names. The only exception
  is trusted CMA auto-import after deterministic source, duplicate, catalog conflict,
  value, and unit checks pass. If reference bounds are missing, status must remain
  `unknown`, and the value is routed to review rather than auto-confirmed (2026-06-25
  amendment).
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

- Source: synthetic reviewed-draft unit normalization regression (2026-06-20)
  - Claim type: fact
  - Summary: When a below-threshold draft is manually confirmed, wrapper/trailing
    punctuation around value and reference units is stripped before storage and status
    calculation, matching the auto-confirm path for common PDF formatting.

- Source: synthetic CMA duplicate-unit regression (2026-06-21)
  - Claim type: fact
  - Summary: Trusted CMA duplicate names with distinct units are deterministically
    disambiguated by appending the unit before auto-import, while same-unit duplicates
    remain review drafts.

- Source: fresh owner-led local upload gate (sanitized, 2026-06-24)
  - Claim type: fact
  - Summary: A real local PDF upload landed on the result/review route with the
    extraction run done, 18 candidates counted, 16 extracted rows confirmed by
    the deterministic trust gate, 1 extracted row left as draft review remainder,
    and 1 source document attached. Browser and database checks confirmed the
    draft stayed out of export/downstream counts, the source document used an
    owner-authorized download route, and generated private storage names were not
    visible in the page. Private biomarker names, values, source snippets, and PDF
    contents were not recorded in this ADR.

- Source: focused and full validation after the live gate (2026-06-24)
  - Claim type: fact
  - Summary: Focused intake/review/upload/privacy feature tests passed
    (107 tests, 776 assertions), followed by the full validator
    `sh scripts/validate.sh` passing parser-lab checks, Vite build, Pint,
    PHPStan/Larastan, Pest, Dusk, and whitespace checks.

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
- `ExtractedBiomarkerCandidate` carries a local source label so CMA-only trust policy
  does not leak to generic inline or tabular extraction.
- Trusted CMA rows may auto-create owner-scoped biomarkers with default unit and any
  available reference metadata, then auto-confirm the result in the same local
  transaction. Rows without parseable reference bounds resolve to `unknown` status and are
  routed to review (auto-imported as an unconfirmed draft), never auto-confirmed — see the
  2026-06-25 amendment.
- Catalog anchoring must be unambiguous. Ambiguous exact aliases or equal-length prefix
  matches stay as drafts instead of becoming auto-confirmed values.
- Duplicate CMA names in the same extraction stay as drafts instead of being collapsed
  into one created biomarker.
- Duplicate trusted CMA names with distinct units are renamed with a unit suffix before
  matching/import, allowing unit-distinct pairs to become separate confirmed biomarkers.
  Same-unit duplicates still stay as drafts.
- Missing-unit rows cannot pass the auto-confirm gate. Trusted CMA fragments that also
  lack reference bounds are discarded as non-actionable; other incomplete rows may
  remain low-confidence drafts when they still carry useful review evidence.
- The review screen shows auto-confirmed values (labelled, editable) plus any remaining
  low-confidence drafts.
- Reviewed draft confirmation normalizes wrapper/trailing punctuation around units
  before status calculation and storage, so the manual trust gate does not reintroduce
  formatting artifacts already handled by auto-confirm.
- Owner-scoping, no-overwrite-of-existing-confirmed, `PrivacyBoundaryTest`, and the
  no-OCR/AI boundary all stay.
- "Perfect for every PDF" is explicitly not promised; the threshold plus the draft
  fallback handle the unseen and the uncertain.

## Confidence

High for the first supported local CMA intake policy: synthetic unit/feature/browser
checks cover page-aware clustering, continuation rules, catalog anchoring, ambiguity,
missing units, candidate accounting, confirmed-only downstream behavior, and the
upload-first intake result flow, and the 2026-06-24 owner-led local upload verified
the same policy outside synthetic fixtures. Confidence remains bounded to local
deterministic PDF-first extraction for the reviewed layout family. New lab formats
or unacceptable draft remainder still require the sanitized fixture finetune loop.

## Follow-Up Questions

- Does the visible "auto-filled from PDF" treatment give enough distinction from
  manually confirmed values during real review?
- Which next real lab format needs a sanitized synthetic fixture in the finetune loop?
