# Current Operating Intent

Date: 2026-06-29
Audience: Codex agents and Cursor cloud agents entering this repo.

This is the "where are we now?" card. It does not replace `AGENTS.md`, the PRD,
specs, ADRs, tests, or live browser proof. Update it after any meaningful slice.

## Read Order

1. `AGENTS.md`
2. This file.
3. `docs/codex-prd.md`
4. The nearest child `AGENTS.md` for every directory you will touch.
5. The active GitHub issue or slice tracker.
6. The focused tests and browser route named by that issue/tracker.

## Product Sentence

Private blood-test dossier: PDF in → review → confirmed values → dashboard/trends
→ consult/export. No diagnosis, no medical advice, no runtime AI on private data.

```text
PDF in -> blood test -> review -> confirmed values -> timeline/detail ->
comparison -> context -> consult/export.
```

## Current Branch And PR Landscape (2026-06-29)

**Base:** `main` at dashboard polish + Dutch dates (`d5ce0b9`).

| PR | Branch | Status | Content |
| --- | --- | --- | --- |
| #39 | `cursor/thematic-biomarker-overview-5f64` | Draft | Thematic biomarker overview + consult handoff alignment |
| #40 | `cursor/architect-skill-5f64` | Draft | `.cursor/skills/architect/SKILL.md` |
| #37 | `cursor/consult-handoff-prefill-5f64` | Draft | Superseded by cherry-pick into #39 |
| #36 | `cursor/nl-market-research-5f64` | Draft | Market research docs only |
| #38 | `cursor/thematic-biomarker-plan-5f64` | Draft | Plan doc only (implementation in #39) |

**Active engineering slice:** finish and validate PR #39 (thematic overview).

Do not merge without owner code review per `AGENTS.md`.

## Completed On Main (recent)

- Dashboard KPI strip, readiness banner, consult POST handoff (#34)
- Dashboard visual polish: KPI icons, selection pills, latest tests table (#35)
- Dutch dates via `Format::dutchDate` app-wide

## In Progress On PR #39

- `BuildThematicBiomarkerOverview` — confirmed-only grouping by `BiomarkerCategory`
- Vitasure-inspired default categories seeder (9 themes + Overig)
- Neutral reference descriptions (`config/biomarker_reference_descriptions.php`)
- Consult pack **Per thema** section (`include_themes`)
- Dashboard latest digest **Per thema** block
- ADR-0013 organizational-only copy boundary
- Dashboard consult handoff + **Selectie aanpassen** GET prefill includes `include_themes`

## Hard Invariants

- `confirmed_at` is the downstream trust gate.
- Owner scope enforced server-side everywhere.
- Private health data never leaves local/private boundary.
- No runtime AI/OCR/external processing on private intake without new ADR.
- App describes tracking data; it does not diagnose, advise, or score health.

## Active Next Move

1. **Validate PR #39 locally:** `php artisan test tests/Unit/Biomarkers/`, consult/dashboard feature tests, `sh scripts/validate.sh`.
2. **Browser QA:** upload/confirm → dashboard **Per thema** → consult pack **Per thema** with trends.
3. **Owner review** of PR #39 and #40; close superseded #37 after #39 merges.
4. **Later slices (not now):** category assignment UI, auto-category on import (ADR), account-delete storage sweep, consult print mode (PR C).

## Done Means

- Focused tests prove behavior and boundaries.
- `sh scripts/validate.sh` passes for code changes.
- Browser QA on matching routes when UI/workflow changes.
- Only relevant files staged; no merge without owner review.
