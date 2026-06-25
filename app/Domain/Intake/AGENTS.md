# Intake Domain

Owns deterministic local PDF/text extraction, candidate normalization, trust
gates, draft storage, and ADR-0011 confidence-gated auto-confirm. It does not
own user-facing copy, upload routing, or downstream interpretation.

## Entry Points

- `RunBloodTestExtraction.php` - orchestrates extraction, storage,
  auto-confirm, status recalculation, and progress stages.
- `ExtractBiomarkerDrafts.php` - combines local PDF text/layout extraction into
  draft candidates.
- `ExtractCmaLayoutBiomarkerCandidates.php` and
  `ExtractTabularBiomarkerCandidates.php` - CMA/layout-specific candidate logic.
- `ExtractedBiomarkerCandidate.php` - candidate DTO and source labels.
- `PositionedTextFragment.php` - page/x/y text fragments for layout parsing.

## Contracts & Invariants

- No image-text extraction, automated interpretation, external service, network
  call, or new intake package for private PDFs.
- Real PDFs and real biomarker values do not become fixtures, logs, screenshots,
  or research input. Use sanitized synthetic fixtures only.
- Auto-confirm is only for deterministic trusted CMA rows that pass source,
  confidence, value, unit, reference, catalog, and duplicate gates.
- Missing-unit, ambiguous, duplicate same-unit, conflicting, below-threshold, or
  noisy rows stay drafts or are discarded only when non-actionable by ADR-0011.
- Trusted CMA duplicate names may be disambiguated by unit only when every
  duplicate has a distinct non-empty unit.
- Page-aware row clustering must prevent prose from other pages merging into
  biomarker names.

## Patterns

- Start parser changes with a failing synthetic regression.
- Normalize numbers and wrapper/trailing unit punctuation consistently across
  auto-confirm and manual review paths.
- Keep extraction telemetry honest: `candidate_count` records parser output,
  even when storage later skips rows.
- Preserve source labels so CMA-only trust does not leak to generic extraction.

## Anti-patterns

- Do not broaden confidence thresholds or source trust to reduce review rows.
- Do not make a wrong value look clean by hiding it from review.
- Do not use downstream dashboard/consult needs as a reason to relax intake
  safety.
- Do not add medical meaning in this layer; it extracts and gates data only.

## Related Context

- Parent domain rules: `../AGENTS.md`
- Models: `../../Models/AGENTS.md`
- Tests: `../../../tests/AGENTS.md`
- ADR: `../../../docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`
