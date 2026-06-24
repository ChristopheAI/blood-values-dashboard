# Persoonlijk Bloedwaarden-Dashboard

Laravel workspace for a private personal dashboard for uploading lab-result
PDFs, turning reviewed biomarker values into structured data, tracking context
notes, trends, comparisons, reminders, documents, and consult preparation.

This is not a diagnosis machine and must not provide medical advice.

## Current Phase

V2 clean-by-default CMA intake with confidence-gated auto-confirm.

The Laravel Livewire scaffold exists. Current work is local deterministic
PDF-first intake, compact review for uncertain rows, and confirmed-only
downstream behavior. ADR-0011 is accepted for the reviewed local CMA trust policy
after synthetic tests, automated checks, full validation, and a 2026-06-24
owner-led local upload that passed with review remainder.

Keep new work inside the reviewed PDF-first/V2 boundary unless a spec, ADR, and
task plan explicitly expand it. Do not add runtime AI interpretation,
unreviewed OCR, provider integrations, wearable sync, external processing, or
medical-advice features to this slice.

## Read First

- `AGENTS.md`
- `docs/project-brief.md`
- `docs/codex-prd.md`
- `docs/agent-efficiency-playbook.md`
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

The current implementation target is the V2 follow-up flow:

- auth;
- PDF-first blood test intake;
- private lab-document storage;
- deterministic confidence-gated auto-confirm for trusted local CMA extraction;
- review/confirmation of uncertain biomarker values from the uploaded document;
- small biomarker catalog;
- status calculation;
- biomarker history;
- compare two blood tests;
- confirmed-only dashboard/detail/consult/export surfaces.

## Validation

Current implementation-stage validation:

```bash
sh scripts/validate.sh
```

This currently proves scaffold integrity, runs the Laravel test/quality suite,
builds frontend assets, and checks whitespace.

## QA Scenario

Seed a stable synthetic browser-QA dataset:

```bash
php artisan app:seed-blood-test-demo
```

Login with `qa@example.com` / `password`. See
`docs/testing/qa-seed-scenario.md`.

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
