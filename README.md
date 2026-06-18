# Persoonlijk Bloedwaarden-Dashboard

Planning workspace for a first Laravel project: a private personal dashboard for
uploading lab-result PDFs, turning reviewed biomarker values into structured
data, tracking context notes, trends, comparisons, reminders, documents, and
consult preparation.

This is not a diagnosis machine and must not provide medical advice.

## Current Phase

Planning baseline.

No Laravel app has been scaffolded yet. Do not install Laravel, create routes,
write migrations, or generate app code until the planning baseline is committed
and the pre-scaffold review gate has been handled, and the first-slice
execution path is explicitly chosen.

## Read First

- `AGENTS.md`
- `docs/project-brief.md`
- `docs/v1-spec.md`
- `docs/product-system-check.md`
- `docs/evidence/source-index.md`
- `docs/adr/`
- `docs/research/laravel-stack-decision.md`
- `docs/research/ai-architect-program-transfer.md`
- `docs/research/2026-06-18-blood-values-workflow-value-evidence.md`
- `docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md`
- `docs/superpowers/plans/2026-06-16-first-vertical-slice.md` (superseded)
- `docs/session-handoff.md`
- `docs/validation-protocol.md`
- `docs/ops/production-checklist.md`
- `docs/reviews/pre-scaffold-review-request.md`
- `docs/reviews/pre-scaffold-review-scorecard.md`

## Workflow

```text
brief -> evidence -> ADR -> spec -> task plan -> baseline commit -> build -> verify -> review -> handoff
```

The first implementation target is the first vertical slice:

- auth;
- PDF-first blood test intake;
- private lab-document storage;
- review/confirmation of biomarker values from the uploaded document;
- small biomarker catalog;
- status calculation;
- biomarker history;
- compare two blood tests.

## Validation

Current planning-stage validation:

```bash
sh scripts/validate.sh
```

This currently proves that the planning/control-plane files exist and that no
Laravel scaffold has been created yet. After Laravel is scaffolded, the script
must be updated to run Laravel tests and frontend build checks.

## Product Boundary

In scope:

- personal organization and follow-up of blood values;
- plain status labels based on entered reference ranges;
- context preservation;
- doctor-consult preparation;
- privacy, export, and deletion controls.

Out of scope:

- medical diagnosis;
- treatment advice;
- supplement/diet/training recommendations;
- AI interpretation;
- unreviewed OCR/lab-provider integrations in the first slice.
