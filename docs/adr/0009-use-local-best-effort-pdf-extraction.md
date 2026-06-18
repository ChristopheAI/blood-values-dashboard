# ADR-0009: Use Local Best-Effort PDF Extraction With Draft-Then-Confirm

## Status

Accepted

## Context

V1 proved manual PDF-first intake (slice 1, merged): the user uploads a private
lab-result PDF and then confirms biomarker values before they become usable data.
`docs/v1-spec.md` §13 asked whether assisted text extraction should follow once the
manual review flow is proven. It is now proven on `main`.

AGENTS.md and ADR-0008 deliberately fence off unreviewed OCR and AI: lab PDFs are
private source documents, and any feature touching them, or any package handling
health data, needs an explicit decision and review first. This ADR records that
decision for the first V2 slice so implementation can proceed inside the existing
boundaries.

## Decision

Add assisted extraction as a best-effort, local, draft-then-confirm step:

- after a lab PDF is uploaded, a local text-layer parser produces candidate
  biomarker values;
- candidates are stored as drafts on `biomarker_results` with
  `entry_source = 'extracted'` and `confirmed_at = null`;
- drafts never feed status, history, comparison, consult, or export — only
  confirmed values do, via `BloodTest::confirmedResults()`;
- the existing review screen pre-fills the drafts; the user confirms, corrects, or
  deletes each one, exactly as manual entry does today;
- extraction runs locally and server-side on the private PDF — no external service,
  no AI/LLM, and no third-party processing of lab PDFs;
- OCR for scanned/image PDFs and any AI/LLM extraction engine are out of scope here
  and would each need their own later ADR plus privacy review (ADR-0008 applies);
- any PDF text-extraction library must be local, make no network calls, and pass
  the AGENTS.md package review (fit, privacy, maintenance), recorded before it is
  added.

This resolves the §13 open question: yes to assisted extraction, but only as a
local best-effort draft layer behind the existing confirm gate.

## Package Review

Chosen package: `smalot/pdfparser`.

- Fit: pure PHP PDF text parser that extracts text from digital PDF text layers.
  This matches the slice scope: best-effort local text extraction, not OCR and not
  interpretation.
- Privacy: parsing runs inside the Laravel process against the privately stored
  PDF on the local disk. The package does not add a network client, queue worker,
  external API, cloud processor, or AI/LLM path for lab PDFs.
- Maintenance: Composer metadata lists current stable releases through `v2.12.5`,
  public source and issues on GitHub, and runtime requirements compatible with the
  app platform (`php >=7.1`, `ext-zlib`, `ext-iconv`,
  `symfony/polyfill-mbstring`).
- License: LGPL-3.0-only. Acceptable for this private application use while the
  package is consumed as an unmodified Composer dependency.
- Validation: tests must prove fixture extraction is deterministic, drafts stay
  behind `confirmed_at`, and `PrivacyBoundaryTest` keeps runtime external/AI
  processors out of the app.
- Limitation: scanned or image-only PDFs may produce no candidates. Manual entry
  stays available, and OCR remains a later ADR.

Package metadata source: `composer show smalot/pdfparser --all` on 2026-06-18.

## Evidence

- Source: `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
  - Claim type: fact
  - Summary: PDF-first intake with user-confirmed values is the accepted direction;
    manual review is proven and merged.

- Source: `docs/v1-spec.md` §13
  - Claim type: fact
  - Summary: The spec defers assisted text extraction and asks for it only after
    the manual review flow is proven.

- Source: `AGENTS.md`
  - Claim type: fact
  - Summary: Unreviewed OCR/AI is out of V1; packages touching health data need an
    explicit fit, privacy, and maintenance review; lab PDFs are private documents.

- Source: `docs/adr/0008-future-ai-agents-must-be-proposal-only.md`
  - Claim type: fact
  - Summary: Future AI must be proposal-only and may not directly mutate or
    interpret private health records.

- Source: approved visual plan (Cowork session, 2026-06-18)
  - Claim type: fact
  - Summary: The local, draft-then-confirm approach was reviewed and approved
    before this ADR was written.

## Considered Options

- Keep manual entry only, with no extraction.
- Local best-effort text-layer extraction with draft-then-confirm.
- Local OCR for scanned PDFs included in the first V2 slice.
- An external extraction API or an AI/LLM extraction engine.

## Decision Drivers

- Manual review is proven, so extraction can reduce typing without being treated as
  truth.
- The draft-then-confirm gate keeps the confirmed-only invariant intact.
- Private health data must stay local — no third-party or AI processing of lab PDFs.
- Scope must stay tight; OCR and AI are large, separate risks.
- A new dependency touching private files must be reviewed before adoption.

## Consequences

- `biomarker_results` gains an `'extracted'` entry source; an `extraction_runs`
  table is added for auditability.
- The upload flow gains a local extraction step that moves the blood test to
  `reviewing` with drafts attached.
- `scripts/validate.sh` should add a check that this ADR exists, as was done for
  ADR-0005 through ADR-0008, and the test suite must prove drafts never pass the
  confirm gate.
- `PrivacyBoundaryTest` continues to guard against any external or AI processing in
  the extraction path.
- A later ADR is required before adding OCR or any AI/LLM extraction engine.

## Confidence

High

## Follow-Up Questions

- Which local PDF text library is chosen, and what did its package review conclude?
- Text-layer only this slice, or is a local OCR fallback worth a follow-up slice?
- Should extracted names auto-match the user's catalog, or always require the user
  to pick or create on confirm?
- How are low-confidence drafts visually flagged in the review screen?
