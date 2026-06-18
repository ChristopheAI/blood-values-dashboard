# Codex Kickoff — V2 Slice 1: Assisted PDF Extraction (draft → confirm)

Date: 2026-06-18

Prepared in a Cowork session and approved via a visual plan. V1 is complete on
`main`: PDF-first intake, consult preparation, export + delete-all, and reminders —
all tested and reviewed in real code. This is the first V2 slice. It crosses a line
V1 deliberately fenced off (assisted extraction of lab PDFs), so it is
governance-first. Chat is temporary; this is not.

## What this slice does

Insert ONE new step into the proven intake flow: after a lab PDF is uploaded,
auto-extract candidate biomarker values as DRAFTS, then let the existing
review-and-confirm gate promote them. Nothing downstream changes — status, history,
compare, consult, and export keep consuming confirmed values only.

Design philosophy: extraction is best-effort and never trusted. Correctness comes
from the user confirming each value, exactly as today. A draft is just a pre-filled
guess that saves typing.

In scope: local text-layer extraction of digital lab PDFs into draft
`biomarker_results`; drafts shown in the existing review screen, flagged and
unconfirmed; user confirms/corrects/deletes each draft via the existing confirm path.

Out of scope (later slices / separate review): OCR for scanned/image PDFs (needs a
local OCR engine — separate slice); any LLM/AI extraction or external extraction
service; auto-confirming anything; any change to the confirmed-only downstream.

## Governance first (the repo's own gate)

V1 explicitly deferred OCR/AI extraction and requires a spec + ADR + privacy review
before this kind of feature (AGENTS.md; v1-spec §13 open question). Before code:

1. Write ADR-0009 "use local, best-effort PDF extraction with draft-then-confirm":
   records local parsing only, no external/AI processing of lab PDFs, extracted
   values are drafts until confirmed, and that a stronger OCR/AI engine would need
   its own later ADR + privacy review. Resolves the §13 open question.
2. Add a short V2 section to `docs/v1-spec.md` (or a new `docs/v2-spec.md`)
   describing the draft lifecycle and the data shape below.
3. Record the package review for the PDF text library you choose (see Data model)
   inside the ADR — fit, privacy, maintenance, and that it is local with no network.

## Decisions (resolving the visual-plan open questions)

- Engine: local text-layer extraction only this slice (digital PDFs). OCR for scans
  is a later slice.
- Catalog mapping: match an extracted name to the user's existing biomarker catalog;
  if there is no confident match, keep the raw extracted name on the draft and let
  the user pick or create on confirm. Never silently create catalog entries.
- Low-confidence drafts: show them all, flag low confidence — never hide. Surface
  uncertainty, do not bury it.
- Schema: reuse `biomarker_results` with `entry_source = 'extracted'` and
  `confirmed_at = null`; add a lightweight `extraction_runs` row for auditability.

## Conventions (from the merged code — do not reinvent)

- Owner-scoped by `user_id`; authorize server-side; never trust Livewire input.
- Domain logic in `app/Domain/...`; single-action controllers; enums in `app/Enums`;
  a factory per model; `data-test` selectors. Mirror the existing intake/review
  code: `StoreBloodTestController`, `app/Livewire/BloodTests/ReviewBloodTest`,
  `app/Domain/Biomarkers/DetermineBiomarkerStatus`.
- The confirmed-only invariant lives in `BloodTest::confirmedResults()`
  (`whereNotNull('confirmed_at')`) — drafts (`confirmed_at` null) must stay out of it.

## Data model

- `biomarker_results`: allow `entry_source = 'extracted'` (draft). value/unit/range
  may be null on a draft when extraction was unsure; `confirmed_at` stays null until
  the user confirms. Respect the existing `unique(blood_test_id, biomarker_id)` —
  one draft per biomarker per test; if extraction yields a name already present,
  update the draft, do not duplicate.
- new table `extraction_runs`: `id`, `blood_test_id` (cascade), `engine` (string),
  `status` (pending/done/failed), `candidate_count`, timestamps — owner via the
  blood test.
- optional nullable on the draft row: `extraction_confidence`, `source_snippet` for
  the flag and provenance. Keep it PII-light; do not log snippets.

## Build order (tests-first)

1. ADR + spec + package review (governance above).
2. `app/Domain/Intake/ExtractBiomarkerDrafts`: takes a stored private PDF, runs the
   local parser, returns candidate rows. Pure and testable against a fixture PDF. No
   network, no external API.
3. Hook into upload: after `StoreBloodTestController` stores the PDF, run extraction
   (synchronous for now), create draft `biomarker_results` (`entry_source`
   'extracted', `confirmed_at` null) plus an `extraction_runs` row, and move the
   blood test to `reviewing`.
4. Review screen: `ReviewBloodTest` shows the drafts pre-filled, each flagged
   "extracted — please confirm" with a low-confidence marker where relevant;
   confirm/correct/delete uses the existing confirm path (sets `confirmed_at`).
5. Prove the invariants by test (below); keep the existing 81 tests green.
6. Extend the Dusk smoke: upload a fixture PDF, see drafts, confirm one, see it in
   history. Keep `MedicalCopyBoundaryTest` and `PrivacyBoundaryTest` green.
7. Green: `sh scripts/validate.sh`.

## Test contract (write first)

- a fixture lab PDF extracts the expected candidate values, deterministically;
- extracted rows are created as drafts: `entry_source` 'extracted', `confirmed_at`
  null;
- a draft does NOT appear in status, biomarker history, compare, the consult
  overview, or the data export until confirmed (confirmed-only invariant holds);
- confirming a draft sets `confirmed_at` and promotes it exactly like manual entry;
- extraction is owner-scoped: a user only ever extracts into their own blood test;
- no extracted value overwrites a previously confirmed value;
- `PrivacyBoundaryTest` still passes — no Exa/Firecrawl/OpenAI/Anthropic/LLM or
  external call introduced; extraction is local;
- delete-all removes drafts; the data export keeps drafts out (consistent with
  confirmed-only) — assert both.

## Guardrails (non-negotiable)

- Local extraction only. No external service, no AI/LLM, no third-party processing
  of lab PDFs in this slice. `PrivacyBoundaryTest` is the guard.
- Extracted values are never trusted: drafts until the user confirms. Confirmed-only
  continues to feed everything downstream.
- The chosen PDF library must be local and reviewed (AGENTS.md package rule) and
  recorded in the ADR. No new dependency by reputation alone.
- No medical copy (v1-spec §10). "Extracted — please confirm" is fine; no
  interpretation, no diagnosis.
- Owner-scope everything; authorize server-side.

## Fold in while building

- Measurable targets: (a) 0 drafts ever appear in status/history/compare/consult/
  export before confirmation; (b) a fixture PDF produces the expected drafts
  deterministically; (c) 0 external network calls in the extraction path.
- UX states: PDF uploaded and extracting; drafts ready (flagged, low-confidence
  marked); no drafts found (fall back to manual, as today); extraction failed
  (manual entry still works); all confirmed.

## Working agreement (from the /implement command)

- Full quality; no cut corners. Tests-first; commit incrementally on
  `codex/v2-assisted-extraction` (ADR+spec → service+tests → upload hook → review UI
  → integration tests). No TODO stubs. If you must pause, write `progress.md`
  (done / next / blockers), commit it, and report it as partial. Only "done" when
  `sh scripts/validate.sh` is green. Do not merge — review first.
- Track it: create a "V2" milestone and an issue for this slice; close it from the
  merge commit.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/v1-spec.md (§5, §6, §10, §13), docs/session-handoff.md,
docs/testing/pdf-first-intake-test-conversion.md, and
docs/codex-v2-assisted-extraction-kickoff.md. V1 is complete on main. This is V2
slice 1; the visual plan is approved.

Governance first: write ADR-0009 (local best-effort PDF extraction, draft -> confirm,
no external/AI processing of lab PDFs), add a short V2 spec section, and record the
package review for the local PDF text library you choose.

Then build, tests-first, on codex/v2-assisted-extraction, following the merged
conventions:
1. app/Domain/Intake/ExtractBiomarkerDrafts: local text-layer parse of the stored
   private PDF -> candidate rows; deterministic against a fixture PDF; no network.
2. Hook after StoreBloodTestController: create draft biomarker_results
   (entry_source 'extracted', confirmed_at null) + an extraction_runs row; move the
   blood test to 'reviewing'.
3. Show drafts in ReviewBloodTest, flagged "extracted — please confirm"; confirm via
   the existing path (sets confirmed_at).
4. Prove: drafts never appear in status/history/compare/consult/export until
   confirmed; confirming promotes like manual; owner-scoped; PrivacyBoundaryTest and
   MedicalCopyBoundaryTest stay green; extend the Dusk smoke.

Local extraction only — no external service, no AI/LLM, no new dependency without the
package review. No medical copy. Work at full quality, commit incrementally, no TODO
stubs, progress.md if you pause. Stop and report when sh scripts/validate.sh is green.
Do not merge; I review first.
```
