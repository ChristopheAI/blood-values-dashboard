# ADR-0009: Use Local Assisted PDF Extraction With Drafts

## Status

Accepted

## Context

V1 proved the private PDF-first intake loop: upload a lab PDF, keep the source
document private, and let the user confirm structured biomarker values before
they feed status, history, compare, consult overview, or export workflows.

The V1 spec deliberately deferred automatic OCR or assisted extraction. V2 can
now reduce typing, but the privacy and trust boundary must stay intact: a
machine-read value is not trusted health data until the user confirms it.

## Decision

Add assisted extraction as a local, best-effort draft step after PDF upload.

- Extract only from digital PDF text layers in this slice.
- Do not use OCR, external APIs, AI, LLMs, Exa, Firecrawl, OpenAI, Anthropic, or
  other third-party processing for private lab PDFs.
- Store extracted biomarker rows as drafts with `entry_source = extracted` and
  `confirmed_at = null`.
- Keep all downstream workflows on the existing confirmed-only invariant.
- Show drafts in the existing review screen so the user can confirm, correct, or
  ignore them.
- Record an `extraction_runs` row for auditability and failure visibility.
- Any OCR, AI, external extraction, or auto-confirm behavior needs a later ADR,
  spec, package/privacy review, and tests before implementation.

This resolves the V1 open question: assisted PDF text extraction may be added
after PDF-first manual review is proven, but only as local draft extraction with
user confirmation.

## Package Review

Chosen package: `smalot/pdfparser`.

- Fit: pure PHP PDF text parser for reading text from digital PDFs. This matches
  the slice goal: local text-layer extraction only, not OCR.
- Privacy: parsing runs inside the Laravel process against the privately stored
  file. No runtime network call, external processor, cloud service, or AI model
  is introduced.
- Maintenance: Composer metadata lists current stable releases through
  `v2.12.5`, source and issue tracker on GitHub, and runtime requirements
  compatible with this app's PHP platform (`php >=7.1`, `ext-zlib`,
  `ext-iconv`, and `symfony/polyfill-mbstring`).
- License: LGPL-3.0-only. Acceptable for this private application use while the
  library is consumed as a dependency and not modified.
- Validation: tests must prove a fixture PDF extracts deterministic candidates,
  privacy architecture tests still reject external/AI processing references,
  and the extraction path keeps drafts out of confirmed-only workflows.
- Limitation: scanned PDFs and difficult layout PDFs may produce no candidates
  or low-confidence candidates. The manual review path remains available.

Package metadata source: `composer show smalot/pdfparser --all` on 2026-06-18.

## Evidence

- Source: `docs/codex-v2-assisted-extraction-kickoff.md`
  - Claim type: fact
  - Summary: The approved V2 kickoff limits this slice to local text-layer PDF
    extraction into drafts, with user confirmation before downstream use.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: No extracted value is trusted for trends, status, compare, or
    export until user review or confirmation.

- Source: `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
  - Claim type: fact
  - Summary: PDF intake is source-first and structured values become usable only
    after review or confirmation.

- Source: `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
  - Claim type: fact
  - Summary: External research tools must not process private health data.

- Source: `docs/adr/0008-future-ai-agents-must-be-proposal-only.md`
  - Claim type: fact
  - Summary: AI output is never trusted health data until reviewed and confirmed
    by the user.

## Considered Options

- Keep V2 manual-only and defer extraction again.
- Use a local text-layer parser and store drafts for confirmation.
- Use local OCR for scanned PDFs in the same slice.
- Use an LLM or external extraction service.
- Auto-confirm high-confidence extraction results.

## Decision Drivers

- Private lab PDFs must stay local.
- Draft values must not become trusted health data automatically.
- V2 should improve the proven intake flow without changing downstream
  confirmed-only behavior.
- The first V2 slice should stay small enough to prove with deterministic tests.
- Scanned-PDF OCR and AI extraction have larger privacy, dependency, and
  correctness surfaces.

## Consequences

- Digital PDFs with text layers can pre-fill review drafts.
- Scanned PDFs may still require manual entry until a later OCR slice.
- The schema gains extraction metadata and nullable draft fields.
- The upload path runs synchronous extraction for now.
- Browser and feature tests must prove drafts are visible for review but absent
  from status, history, compare, consult overview, and export until confirmed.
- Delete-all must remove draft rows and extraction run audit rows through the
  owner-scoped blood-test deletion path.

## Confidence

High

## Follow-Up Questions

- Which local OCR engine, if any, is acceptable for a later scanned-PDF slice?
- Should low-confidence candidate thresholds become user-configurable after
  real lab PDFs are tested?
- Should extraction runs expose a fuller user-visible failure reason later, or
  stay minimal to avoid storing sensitive snippets?
