# Current Operating Intent

Date: 2026-06-30
Audience: Codex agents entering `main` after thematic biomarker overview slice.

This is the first "where are we now?" card. It does not replace `AGENTS.md`,
the PRD, specs, ADRs, tests, or live browser proof. Update it after any
meaningful slice so the next agent starts from the real project state instead
of old conversation memory.

## Read Order

1. `AGENTS.md`
2. This file.
3. `docs/codex-prd.md`.
4. The nearest child `AGENTS.md` for every directory you will touch.
5. The active GitHub issue or slice tracker.
6. The focused tests and browser route named by that issue/tracker.

## Product Sentence

The user has multiple blood draws and wants one private place where each blood
test keeps the original PDF, confirmed values, units/ranges, context, and
differences from previous tests, so the user can understand what changed and
prepare compactly for a doctor conversation without the app making medical
claims or encouraging extra testing.

Short pipeline:

```text
PDF in -> blood test -> review -> confirmed values -> timeline/detail ->
comparison -> context -> consult/export.
```

## Current Branch And PR

- Branch: `cursor/thematic-biomarker-overview-5034` (in progress from `main`).
- Base: `main` with merged V2 intake, consult pack, dashboard polish, and
  qualitative/detection-limit parser fixes.
- Active slice: thematic biomarker overview (**Per thema**) per
  `docs/superpowers/plans/2026-06-27-thematic-biomarker-overview.md` and
  ADR-0013.
- Previous intent card referenced `codex/v2-clean-autoconfirm` / PR #11; that
  work is merged on `main`.

## Completed Shape On Main (before this slice)

- V2 clean-by-default local extraction and confidence-gated auto-confirm.
- Upload-first intake, canonical blood-test detail workspace, longitudinal changes.
- Consult pack with print/CSV, privacy tests, dashboard consult handoff.
- Dashboard KPI strip, Dutch dates, visual polish.

## Active Slice — Thematic Biomarker Overview

Build:

- `BuildThematicBiomarkerOverview` domain builder (confirmed-only, owner-scoped).
- Local reference descriptions in `config/biomarker_reference_descriptions.php`.
- Consult pack section **Per thema** with `include_themes`.
- Additive dashboard theme block on latest blood test digest.
- ADR-0013 copy boundary; default category seeder helper.

Done when:

- Unit tests cover grouping, Overig, drafts, trends, owner scope.
- Consult feature tests cover theme section on/off.
- Dashboard shows theme when categories assigned.
- `sh scripts/validate.sh` passes.
- Browser QA on consult pack with categorized markers (no private PDF content in docs).

## Hard Invariants

- `confirmed_at` is the downstream trust gate.
- Owner scoping is enforced server-side.
- Private health data stays local; no runtime AI descriptions.
- Theme copy is organizational only (ADR-0013); CSV export excludes descriptions in v1.

## Active Next Move After This Slice

- Category catalog UI and optional deterministic intake auto-mapping (new ADR).
- Refresh browser proof gate for full multi-blood-test follow-up flow.
- Keep consult/export lineage tests aligned when adding new export sections.
