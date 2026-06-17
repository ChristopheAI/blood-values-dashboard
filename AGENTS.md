# AGENTS.md

## Project

This repository is the planning and future implementation workspace for a
personal blood values dashboard built with Laravel.

The product is a private personal tracking system for blood tests, biomarkers,
context notes, trends, documents, reminders, and consult preparation.

It is not a diagnosis machine and must not provide medical advice.

## Current Phase

Planning baseline.

Do not install Laravel, generate routes, write migrations, scaffold UI, or add
application code until the planning baseline is complete and the next task
explicitly asks for implementation.

## Source Of Truth

Read these first:

- `README.md`
- `docs/project-brief.md`
- `docs/v1-spec.md`
- `docs/product-system-check.md`
- `docs/evidence/source-index.md`
- `docs/adr/0001-use-repo-as-project-control-plane.md`
- `docs/adr/0002-use-adrs-for-architecture-decisions.md`
- `docs/adr/0003-use-livewire-starter-kit-for-v1.md`
- `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
- `docs/research/laravel-stack-decision.md`
- `docs/research/ai-architect-program-transfer.md`
- `docs/superpowers/plans/2026-06-16-first-vertical-slice.md`
- `laravel-platform-discovery.md`
- `docs/session-handoff.md`
- `docs/validation-protocol.md`
- `docs/ops/production-checklist.md`
- `docs/reviews/pre-scaffold-review-request.md`
- `docs/reviews/pre-scaffold-review-scorecard.md`

The project brief defines V1 scope. The source index separates facts,
inferences, hypotheses, and unknowns. ADRs define durable project decisions.
The discovery notes explain why Laravel is logical only when data, rules,
follow-up, documents, communication, and automation come together behind an
administrative product motor.

## Workflow

Use this sequence:

```text
brief -> evidence -> ADR -> spec -> task plan -> baseline commit -> build -> verify -> review -> handoff
```

Before implementation:

- create or update the V1 spec when data model, privacy, validation, or workflow
  decisions need review;
- create or update an ADR when an architecture, privacy, stack, automation, or
  implementation-gate decision changes;
- create a small task plan for the first vertical slice;
- handle the pre-scaffold review gate in `docs/reviews/`;
- keep out-of-scope items out of V1, especially OCR, AI interpretation,
  medical recommendations, wearable integrations, and provider connections.

## Validation

Current validation command:

```bash
sh scripts/validate.sh
```

At this planning stage, the command validates project-control files and checks
that no Laravel app has been scaffolded yet. Before the first implementation
task, update it so V1 behavior is proven with Laravel/Pest/browser checks.

## Product Rules

- Manual biomarker entry is the V1 source of truth.
- Original lab documents and structured values must remain separate.
- Status can be `low`, `normal`, `high`, or `unknown`.
- Use `unknown` when ranges, units, or comparison rules are not trustworthy.
- Privacy, export, and deletion are V1 concerns, not later polish.
- Any language that sounds like diagnosis, treatment, or supplement advice is
  out of scope unless a later spec deliberately changes the product boundary.

## Truth-First Working Rules

- Treat user assumptions, diagnoses, and proposed fixes as unverified until
  checked against the project files, code, documentation, or constraints.
- If a plan is wrong or too broad, say so directly and propose the smaller
  correct next step.
- Prefer minimal, verifiable changes over broad rewrites.
- Do not claim completion without fresh validation evidence.
