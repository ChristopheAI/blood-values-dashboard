# Codex Kickoff — V2: Clean-By-Default Extraction + Confidence-Gated Auto-Confirm

Date: 2026-06-19

Implements ADR-0011 (Proposed). V1, tabular extraction, and name-bounding are merged
on `main` (`689672b`). Goal: zero user friction for confidently-extracted values, with
a safety net for the uncertain few. Sanitized: no real PDF content, names, or values.

## Goal

- Good extraction → clean values, auto-confirmed, zero clicks.
- Imperfect extraction → finetune the parser per format until clean (synthetic
  fixtures), and meanwhile keep uncertain rows as drafts — never a wrong auto-confirm.

## Step 0 — sanitized geometry dump (local, gated)

Before tuning, dump locally (sanitized) the positioned fragments for the real tabular
layout that over-captured names, to see WHY prose merges into the name cell (row
clustering vs an unbounded name cell). Log nothing; commit no real PDF/values. Report
the generic shape only: the x-gap structure between the name and the prose, and whether
the prose sits on a separate y-band.

## Build (tests-first, on `codex/v2-clean-autoconfirm`)

1. Clean names.
   - Catalog anchor: if a catalog biomarker name is a case-insensitive prefix of the
     extracted name, use the catalog canonical name and set `biomarker_id`. Never
     auto-create catalog entries.
   - Per-format row/column tuning from step 0 (e.g. cut the name cell at a large
     x-gap; drop prose fragments beyond the name cluster). Add a synthetic
     "prose-noise" fixture reproducing the real shape; tune until the name is clean.
2. Confidence model: a clean, catalog-matched row with a parseable value/unit/range is
   high; truncated, unmatched, or missing-unit is low. Make the threshold one named
   constant.
3. Confidence-gated auto-confirm: in the existing `storeDrafts` path, set `confirmed_at`
   on rows at/above the threshold; leave below-threshold rows as drafts. Never overwrite
   an existing confirmed value. The blood test becomes `confirmed` only when no drafts
   remain, else `reviewing`.
4. UI: auto-confirmed values appear under "Confirmed values", tagged "auto-filled from
   PDF", editable and deletable; only below-threshold rows show as "Extracted drafts".

## Test contract (write first)

- Catalog anchor: extracted "Marker <prose>" + catalog "Marker" → name "Marker",
  `biomarker_id` set.
- Prose-noise fixture: the name extracts clean (no sentence tail).
- High-confidence row → auto-confirmed (`confirmed_at` set) on upload.
- Low-confidence / unmatched / missing-unit row → stays a draft (`confirmed_at` null).
- Auto-confirm never overwrites an existing confirmed value; owner-scoped.
- Invariant: only confirmed values feed status/history/compare/consult/export (now
  including auto-confirmed); below-threshold drafts stay out until confirmed.
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
main is at 689672b. ADR-0011 is Proposed.

Step 0 (local, sanitized): dump positioned fragments for the tabular layout that
over-captured names; report only the generic gap/band shape; log nothing, commit no real
PDF/values.

Then build on codex/v2-clean-autoconfirm, tests-first:
1. Clean names: catalog-prefix anchor -> canonical name + biomarker_id (never auto-create
   catalog); per-format tuning to drop prose from the name cell, with a synthetic
   prose-noise fixture.
2. A named confidence threshold; high = clean+catalog-matched+parseable, low = otherwise.
3. Confidence-gated auto-confirm in storeDrafts: confirmed_at set at/above threshold,
   draft below; never overwrite existing confirmed; blood test -> confirmed only if no
   drafts remain.
4. UI: auto-confirmed values under Confirmed values, tagged auto-filled, editable; only
   below-threshold rows as drafts.

No OCR/AI/external/new package. Synthetic fixtures only; no real content/values logged.
Confirmed-only downstream stays (now includes auto-confirmed). Stop and report when
sh scripts/validate.sh is green. Do not merge; I review + live-verify first.
```
