# Codex Kickoff — V2: Clean-By-Default Extraction + Confidence-Gated Auto-Confirm

Date: 2026-06-19

Implements ADR-0011 (Proposed). V1, tabular extraction, and name-bounding were merged
on `main` before this branch. The V2 implementation work on
`codex/v2-clean-autoconfirm` is now committed and locally validated through the
page-aware clustering, catalog anchor, confidence threshold, auto-confirm,
upload-first intake, and hardening follow-ups. Goal: zero user friction for
confidently-extracted values, with a safety net for the uncertain few. Sanitized: no
real PDF content, names, or values.

## Current Branch State

As of the local validation pass on 2026-06-19, the branch contains:

- page-aware row clustering and same-layout page continuation rules;
- `AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85`;
- unambiguous catalog anchoring only; ambiguous aliases/prefixes remain drafts;
- missing-unit rows kept as low-confidence drafts;
- no overwrite of existing confirmed values;
- upload-first intake with file-selection auto-submit and result landing;
- confirmed-only downstream invariants for status/history/compare/consult/export.

ADR-0011 remains Proposed until owner review and a fresh live upload verify the flow.

## Goal

- Good extraction → clean values, auto-confirmed, zero clicks.
- Imperfect extraction → finetune the parser per format until clean (synthetic
  fixtures), and meanwhile keep uncertain rows as drafts — never a wrong auto-confirm.

## Step 0 — page-aware clustering fix (completed)

The root cause of name over-capture is known and fixed on this branch — no dump
needed. The merged-in prose lived on a separate non-table page bundled into the same
PDF; positioned fragments did not record their page and rows were clustered by vertical
position across the whole document, so a name on the results page and a line on the
later page that shared a y-coordinate collapsed into one row.

The committed fix:

- `PositionedTextFragment` gains a `page` field (defaults to 1).
- `ExtractBiomarkerDrafts::positionedFragments()` records a 1-based page number per
  fragment.
- `ExtractTabularBiomarkerCandidates::rows()` sorts page-first and only clusters
  fragments within the same page.
- Two unit tests added: cross-page text at the same y does not merge into the name; a
  continuation row on a later page (no repeated header) still extracts.

This was validated and committed on `codex/v2-clean-autoconfirm`. Do not re-dump real
PDFs or log values.

## Build (tests-first, on `codex/v2-clean-autoconfirm`)

1. Clean names (page-aware clustering from step 0 already removes the cross-page prose;
   this layer handles the rest).
   - Catalog anchor: if a catalog biomarker name is a case-insensitive prefix of the
     extracted name, use the catalog canonical name and set `biomarker_id`. Never
     auto-create catalog entries.
   - Per-format row/column tuning for any residual same-page noise (e.g. cut the name
     cell at a large x-gap; drop prose fragments beyond the name cluster). Add a
     synthetic fixture per real format; tune until the name is clean.
2. Confidence model: a clean, catalog-matched row with a parseable value/unit/range is
   high; truncated, unmatched, or missing-unit is low. Make the threshold one named
   constant.
3. Confidence-gated auto-confirm: in the existing `storeDrafts` path, set `confirmed_at`
   on rows at/above the threshold; leave below-threshold rows as drafts. Never overwrite
   an existing confirmed value. The blood test becomes `confirmed` only when no drafts
   remain, else `reviewing`.
4. UI: auto-confirmed values appear under "Confirmed values", tagged "auto-filled from
   PDF", editable and deletable; only below-threshold rows show as "Extracted drafts".
5. Intake UX (upload-first, instant result).
   - The empty dashboard/intake state is a hero dropzone ("drop your lab PDF", PDF only
     — no OCR/image path per ADR-0009) as the primary action — not a form. Introduce no
     email/account field.
   - During the local parse, show a deterministic progress affordance with real stages
     (extract → values → status → trend). No fake timers — drive it off actual steps.
   - On completion, land on the result: auto-confirmed values + status + trend, with
     below-threshold rows in a compact review strip. The first screen is the user's own
     data, not an empty form. Use `data-test` selectors for the dropzone and result.

## Test contract (write first)

- Page-aware (already in the suite, keep green): cross-page text at the same y does not
  merge into the name; a continuation row on a later page still extracts.
- Catalog anchor: extracted "Marker <prose>" + catalog "Marker" → name "Marker",
  `biomarker_id` set.
- Prose-noise fixture: the name extracts clean (no sentence tail).
- High-confidence row → auto-confirmed (`confirmed_at` set) on upload.
- Low-confidence / unmatched / missing-unit row → stays a draft (`confirmed_at` null).
- Auto-confirm never overwrites an existing confirmed value; owner-scoped.
- Invariant: only confirmed values feed status/history/compare/consult/export (now
  including auto-confirmed); below-threshold drafts stay out until confirmed.
- Intake UX (Dusk/feature): from the empty state, uploading a PDF lands on the results
  view with auto-confirmed values visible (not an empty review form); the empty state
  renders the dropzone as the primary action (`data-test`); no email/account field is
  introduced.
- Dropzone definition of done: automated tests assert that the choose control is a
  `<button type="button">`, the file input remains `name="document"` and
  `accept="application/pdf"`, the selected-file-name surface exists, and file selection
  auto-submits into the result flow. Native OS file picker opening and OS drag/drop
  acceptance remain manual UI checks during live review.
- `PrivacyBoundaryTest` and `MedicalCopyBoundaryTest` stay green; no new
  package/network/OCR/AI.

## Guardrails (non-negotiable)

- Local only; no OCR, AI/LLM, external service, network, or new package.
- Synthetic fixtures only; never log or commit real PDF content or values.
- Auto-confirm is confidence-gated and reversible; below threshold stays a draft. No
  catalog auto-create.

## Finetune loop (ongoing)

Each new real lab format that extracts imperfectly → reproduce as a sanitized synthetic
fixture → tune → lock with a test. Converges to clean for the labs actually in use.

## Working agreement (/implement)

Tests-first; commit incrementally on `codex/v2-clean-autoconfirm`; no TODO stubs;
`progress.md` if you pause; only "done" when `sh scripts/validate.sh` is green. Do not
merge — review first (I review the real code and live-verify a fresh upload). Track with
a V2 issue; close it from the merge commit.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/adr/0009/0010/0011, and docs/codex-v2-clean-autoconfirm-kickoff.md.
Branch is codex/v2-clean-autoconfirm. ADR-0011 is Proposed pending owner live review.

Already committed and validated on this branch: page-aware row clustering, continuation
rules, catalog anchor, AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85, confidence-gated
auto-confirm, upload-first intake, ambiguity hardening, missing-unit drafts, and
confirmed-only downstream invariants. Do not rebuild those slices unless a fresh failing
test proves a regression. Do not log or commit real PDF content/values.

Continue on codex/v2-clean-autoconfirm, tests-first:
1. Clean names: catalog-prefix anchor -> canonical name + biomarker_id (never auto-create
   catalog); per-format tuning to drop prose from the name cell, with a synthetic
   prose-noise fixture.
2. A named confidence threshold; high = clean+catalog-matched+parseable, low = otherwise.
3. Confidence-gated auto-confirm in storeDrafts: confirmed_at set at/above threshold,
   draft below; never overwrite existing confirmed; blood test -> confirmed only if no
   drafts remain.
4. UI: auto-confirmed values under Confirmed values, tagged auto-filled, editable; only
   below-threshold rows as drafts.
5. Intake UX: empty state = upload-first dropzone (PDF only, no OCR), no email/account field;
   the local parse shows a real-stage progress affordance (extract -> values -> status ->
   trend); land on auto-confirmed results + trend, below-threshold rows in a compact
   review strip. data-test selectors; nothing leaves the device.

No OCR/AI/external/new package. Synthetic fixtures only; no real content/values logged.
Confirmed-only downstream stays (now includes auto-confirmed). Stop and report when
sh scripts/validate.sh is green. Do not merge; I review + live-verify first.
```
