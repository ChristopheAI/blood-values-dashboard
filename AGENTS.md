# AGENTS.md

## Project

This repository is the planning and future implementation workspace for a
personal blood values dashboard built with Laravel.

The product is a private personal tracking system for blood tests, biomarkers,
lab-result PDFs, context notes, trends, documents, reminders, and consult
preparation.

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
- `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
- `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
- `docs/adr/0007-use-staged-laravel-quality-ladder.md`
- `docs/research/laravel-stack-decision.md`
- `docs/research/ai-architect-program-transfer.md`
- `docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md`
- `docs/superpowers/plans/2026-06-16-first-vertical-slice.md` (superseded)
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
- keep out-of-scope items out of V1, especially unreviewed OCR, AI
  interpretation, medical recommendations, wearable integrations, and provider
  connections.
- use Exa and Firecrawl only as public research tools unless a later ADR,
  privacy review, and explicit approval allow runtime use.
- when implementation resumes, follow the staged Laravel quality ladder in
  ADR-0007 instead of treating a locally running app as complete.

## Validation

Current validation command:

```bash
sh scripts/validate.sh
```

At this planning stage, the command validates project-control files and checks
that no Laravel app has been scaffolded yet. Before the first implementation
task, update it so V1 behavior is proven with Laravel/Pest/browser checks.

## Product Rules

- The original lab-result PDF is the V1 intake source.
- Structured biomarker values become usable dashboard data only after user
  review or confirmation.
- Manual entry and correction remain required fallbacks for values that cannot
  be extracted or trusted.
- Original lab documents and structured values must remain separate.
- Status can be `low`, `normal`, `high`, or `unknown`.
- Use `unknown` when ranges, units, or comparison rules are not trustworthy.
- Privacy, export, and deletion are V1 concerns, not later polish.
- Any language that sounds like diagnosis, treatment, or supplement advice is
  out of scope unless a later spec deliberately changes the product boundary.
- Exa and Firecrawl may support public research, competitor analysis, and
  documentation review, but must not process private lab PDFs, biomarker data,
  Apple Health exports, medication notes, symptoms, consult exports, or account
  data in V1.
- AI-assisted code is not trusted until validation proves behavior, boundaries,
  and privacy expectations through the staged Laravel quality ladder.

## Truth-First Working Rules

- Treat user assumptions, diagnoses, and proposed fixes as unverified until
  checked against the project files, code, documentation, or constraints.
- If a plan is wrong or too broad, say so directly and propose the smaller
  correct next step.
- Prefer minimal, verifiable changes over broad rewrites.
- Do not claim completion without fresh validation evidence.
