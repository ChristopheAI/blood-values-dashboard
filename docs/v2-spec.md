# V2 Spec

Date: 2026-06-18

V2 starts after V1 is complete on `main`. V1 delivered PDF-first intake,
confirmed biomarker values, history, compare, consult preparation, data export,
delete-all, and in-app reminders.

The V2 boundary stays the same product boundary: this is a personal tracking
and consult-preparation system, not a diagnosis machine and not a medical
advice system.

ADR-0011 adds a narrower V2 trust rule on top of the original assisted-extraction
slice: clean, unambiguous, high-confidence extracted rows may be auto-confirmed
at upload time; everything uncertain remains a draft.

## 1. Assisted PDF Extraction

Purpose:

- reduce manual typing after a lab PDF is uploaded;
- keep the original PDF as the source document;
- keep user confirmation as the trust boundary.

Rules:

- extraction runs locally against the privately stored PDF;
- this slice reads only digital PDF text layers;
- OCR for scanned PDFs is out of scope;
- AI, LLMs, external extraction services, Exa, Firecrawl, OpenAI, Anthropic, and
  other third-party processing of private lab PDFs are out of scope;
- clean, unambiguous rows at or above the configured confidence threshold may be
  auto-confirmed at upload time;
- below-threshold, unmatched, ambiguous, missing-unit, missing-range, or noisy
  rows remain drafts until the user confirms them;
- drafts must not feed status, history, compare, consult overview, data export,
  or dashboard attention lists;
- auto-confirmed rows count as confirmed data for downstream workflows, remain
  editable/deletable, and must stay traceable as extracted from the PDF.

## 2. Draft Biomarker Result

Drafts and auto-confirmed extracted values reuse `biomarker_results`.

Important fields:

- `blood_test_id`;
- nullable `biomarker_id`;
- `extracted_name`;
- value;
- unit;
- nullable reference range fields;
- `status = unknown`;
- `entry_source = extracted`;
- `confirmed_at = null` for drafts, set for auto-confirmed extracted values;
- nullable `extraction_confidence`;
- nullable short `source_snippet`.

Rules:

- a draft may be tied to an existing biomarker when name matching is confident;
- unknown names must remain as extracted names and never silently create catalog
  entries;
- an extracted row must not overwrite an existing confirmed result;
- if a draft for the same blood test and biomarker already exists, extraction may
  update that draft;
- confidence-gated auto-confirm may set `confirmed_at` for clean rows;
- manual confirmation uses the existing review flow and sets `confirmed_at`;
- auto-confirm requires an existing, unambiguous catalog biomarker match, a
  parseable numeric value, a non-empty unit, at least one reference bound, and
  confidence at or above the configured threshold.

## 3. Extraction Run

Purpose:

- record that extraction was attempted;
- make successful, empty, and failed extraction states inspectable;
- avoid hiding parser uncertainty.

Important fields:

- `blood_test_id`;
- `engine`;
- `status`: `pending`, `done`, or `failed`;
- `candidate_count` counts extracted parser candidates before storage skips an
  already-confirmed biomarker value;
- timestamps.

Rules:

- ownership is inherited through the blood test;
- no full PDF text is stored in extraction run rows;
- source snippets on drafts must stay short and PII-light;
- delete-all removes extraction runs through blood-test deletion.

## 4. Review UX

The existing review screen remains the promotion gate.

States:

- PDF uploaded and extraction attempted;
- auto-confirmed values ready for status and trends;
- drafts ready;
- no drafts found, manual entry still available;
- extraction failed, manual entry still available;
- value confirmed.

Required copy:

- "Extracted - please confirm";
- "Low confidence" where relevant;
- neutral fallback language such as "No extracted drafts found."

Forbidden copy:

- diagnosis, treatment, advice, recommended supplement, health score, optimal
  for you, predicted risk, or medical judgment language from V1 section 10.

## 5. Validation

Tests must prove:

- a fixture PDF extracts deterministic candidate values;
- upload creates draft biomarker results with `entry_source = extracted` and
  `confirmed_at = null` for below-threshold rows;
- clean, unambiguous catalog-matched rows at the threshold are auto-confirmed
  with `confirmed_at` set;
- prefix-only, ambiguous, missing-unit, and missing-range rows remain drafts;
- drafts do not appear in status, history, compare, consult overview, data
  export, or dashboard attention lists until confirmed;
- auto-confirmed rows do appear in those downstream confirmed-only workflows;
- confirming a draft promotes it like a manually entered PDF-reviewed value;
- extraction stays owner-scoped;
- no extracted draft overwrites a previously confirmed value;
- no runtime external service or AI processor is introduced;
- delete-all removes drafts and extraction runs.
