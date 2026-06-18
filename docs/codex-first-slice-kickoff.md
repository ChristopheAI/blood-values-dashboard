# Codex Kickoff — First Vertical Slice (PDF-First Intake)

Date: 2026-06-18

Prepared in a Cowork review session. That session's sandbox could not host the
build (no PHP/Composer, Packagist + Anaconda blocked, mount cannot delete/commit),
so execution is handed to Codex, which runs on the Mac with the full toolchain.
This file is the durable build order. Chat is temporary; this is not.

## Decision

Planning is complete. The next step is to BUILD the first slice — not to write
more planning docs.

Evidence the gate is green:

- `docs/reviews/pre-scaffold-review-result.md` = `GO WITH CHANGES`, **Blocking
  Issues: None**.
- All three "Required Changes Before Scaffold" are satisfied; `sh scripts/validate.sh`
  passes.
- The only open workflow debt is the uncommitted baseline (handled in step 0).

## Step 0 — Make the baseline honest (before scaffold)

1. If git reports a stale lock, remove it on the Mac:
   `rm -f ".git/index.lock"` (left by a sandbox session; safe to delete).
2. Commit the pending planning changes so git matches the documented checkpoint:
   - modified: `AGENTS.md`, `README.md`, `docs/evidence/source-index.md`,
     `docs/session-handoff.md`, `scripts/validate.sh`
   - new: `docs/research/2026-06-18-blood-values-workflow-value-evidence.md`,
     `docs/codex-first-slice-kickoff.md` (this file)
   - `git add -A && git commit -m "docs: commit pending planning baseline"`

## Build order

Follow `docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md`. Tests-first
throughout: convert `docs/testing/pdf-first-intake-test-conversion.md` into real
Pest tests BEFORE writing the matching behavior.

1. **Scaffold** the Laravel Livewire starter kit (ADR-0003). Use **SQLite** for
   this slice (this answers the v1-spec §13 open question). Preserve every doc.
2. **Flip validation to Laravel phase.** Replace the planning-grep in
   `scripts/validate.sh` with `composer test`, `npm run build`, and whitespace
   checks; remove the "no Laravel scaffold" guard. Update
   `.github/workflows/ci.yml` to match.
3. **Status domain logic** (v1-spec §6) as pure, testable code, OUT of Livewire
   components. Write the 8 edge-case tests first (matrix below), then green.
4. **Owner-scoped data model:** `blood_tests`, `blood_test_documents`,
   `biomarker_categories`, `biomarkers`, `biomarker_results`. Every record scoped
   by `user_id` (or owner via parent). Feature tests prove User A cannot read,
   download, or compare User B's records.
5. **Private PDF intake:** store the upload on a private disk under a generated
   storage name; keep the original filename only as sanitized metadata; serve via
   an owner-authorized download route; never expose a public storage URL. Cover
   the test contract, incl. Livewire action-parameter and public-property tamper
   denial, and deletion blocking document access.
6. **Review/confirm → status → history → compare.** Only confirmed values feed
   status, history, and compare. Compare handles missing values and unit
   mismatches honestly (`not measured` / `not comparable`).
7. **Prove green:** `php artisan test` passes and `npm run build` succeeds.

## Status test matrix (write first — v1-spec §6)

- value below range -> `low`
- value inside range -> `normal`
- value above range -> `high`
- missing range -> `unknown`
- mismatched unit -> `unknown`
- reversed min > max -> `unknown`
- one-sided minimum, value below -> `low`
- one-sided maximum, value above -> `high`

## Fold in two review gaps WHILE building (not as new docs)

An external review (ChatGPT) surfaced two genuinely additive items the planning
set does not yet cover. Bake them into the slice instead of documenting them:

- **Measurable targets, asserted in tests/QA:** (a) 100% of status/history/compare
  uses only confirmed values; (b) 0 unauthorized cross-user access in security
  tests; (c) a real lab PDF can be uploaded and its key biomarkers confirmed in
  one focused flow.
- **UX states designed up front** in the Livewire views: empty account; PDF
  uploaded / not yet reviewed; review in progress (partial); fully confirmed;
  `unknown`-status (missing/unreliable unit or range); comparison with
  insufficient data; upload-validation / authorization / document-unavailable
  errors.

## Guardrails (non-negotiable — AGENTS.md + ADRs)

- No diagnosis / treatment / supplement-advice copy (v1-spec §10 forbidden words).
- No automatic OCR or AI in this slice; extracted values are never trusted until
  the user confirms them.
- No runtime Exa/Firecrawl/AI processing of lab PDFs.
- Domain logic (status, comparison, privacy, export) lives in testable Laravel
  code, not in Livewire views.
- No Composer package touching auth, files, exports, jobs, logs, or health data
  added by reputation alone — review fit/privacy/maintenance first.

## Paste-prompt for a new Codex thread

```text
Read README.md, AGENTS.md, docs/session-handoff.md, docs/v1-spec.md,
docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md,
docs/testing/pdf-first-intake-test-conversion.md,
docs/reviews/pre-scaffold-review-result.md, and docs/codex-first-slice-kickoff.md.

The pre-scaffold gate is GO WITH CHANGES with no blocking issues. Execute the
first vertical slice now, tests-first.

0. Clear any stale .git/index.lock, then commit the pending planning baseline.
1. Scaffold the Laravel Livewire starter kit (ADR-0003), SQLite for this slice,
   preserving all docs.
2. Replace scripts/validate.sh planning checks with composer test + npm run build
   + whitespace; update .github/workflows/ci.yml.
3. Implement v1-spec §6 status logic as pure domain code; write the 8 edge-case
   Pest tests first, then make them green.
4. Build the owner-scoped data model and prove User A cannot access User B's
   records.
5. Implement private PDF intake (generated storage name, sanitized display
   filename, owner-authorized download, no public URL) with the tamper-denial and
   deletion tests from the test contract.
6. Implement review/confirm -> status -> biomarker history -> compare two tests,
   using only confirmed values.
7. Design the UX/empty/error states named in docs/codex-first-slice-kickoff.md and
   assert the three measurable targets.

Do not add OCR, AI interpretation, medical-advice copy, reminders, context notes,
or consult export in this slice. Stop and report when php artisan test is green
and npm run build succeeds.
```
