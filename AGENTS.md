# AGENTS.md

## Project

This repository is the planning and implementation workspace for a personal
blood values dashboard built with Laravel.

The product is a private personal tracking system for blood tests, biomarkers,
lab-result PDFs, context notes, trends, documents, reminders, and consult
preparation.

It is not a diagnosis machine and must not provide medical advice.

## Current Phase

V2 clean-by-default CMA intake with confidence-gated auto-confirm.

The Laravel Livewire starter scaffold exists. The current implementation work is
focused on local, deterministic PDF-first intake: clean CMA extraction,
confidence-gated auto-confirm, compact review for the uncertain few, and
confirmed-only downstream behavior.

Keep new work inside the reviewed PDF-first/V2 boundary unless a spec, ADR, and
task plan explicitly expand it. Do not add runtime AI interpretation, unreviewed
OCR, provider integrations, wearable sync, external processing, or
medical-advice features to this slice.

## Current Active Rules

- Local only: no OCR, AI/LLM, external service, network processing, or new
  package for private lab intake without a new ADR/privacy review.
- Private lab PDFs, biomarker values, symptoms, medication notes, consult
  exports, and account data must never be sent to Exa, Firecrawl, AI tools, or
  any external service.
- Trusted CMA layout/tabular extraction may auto-confirm deterministic
  high-confidence rows according to ADR-0011.
- Below-threshold rows, catalog conflicts, ambiguous matches, same-unit
  duplicates, missing-unit rows, and uncertain parses stay as drafts.
- Trusted CMA duplicate names may be disambiguated by unit only when every
  duplicate row has a distinct non-empty unit; otherwise keep them in review.
- Confirmed-only downstream remains: status, history, compare, consult, export,
  and trends may use confirmed values only.
- Do not merge this branch. The owner reviews the code and live-verifies a fresh
  upload first.

## Intent Layer

Before modifying files in a subdirectory, read the nearest child `AGENTS.md`
first. There must be only one root context file: this `AGENTS.md`. Do not add a
root `CLAUDE.md`.

- `app/Domain/AGENTS.md` - domain/application rules for extraction, status,
  compare, dashboard, consult, export, and privacy actions.
- `app/Http/AGENTS.md` - controller, route, download, export, and mutation
  boundaries.
- `app/Livewire/AGENTS.md` - Livewire component rules for untrusted public
  state and review interactions.
- `app/Models/AGENTS.md` - Eloquent relationship, scope, and ownership
  contracts.
- `resources/views/AGENTS.md` - Blade/Livewire presentation rules, medical-copy
  boundary, and privacy-safe UI behavior.
- `tests/AGENTS.md` - Pest/Dusk test conventions and confirmed-only/privacy
  regression expectations.
- `docs/AGENTS.md` - project control-plane rules for briefs, specs, ADRs,
  research, evidence, and handoff docs.
- `database/AGENTS.md` - schema, migration, factory, and privacy-sensitive data
  shape contracts.

Global invariants stay here and apply everywhere:

- `confirmed_at` is the downstream trust gate.
- Owner scoping must be enforced server-side, not only in UI.
- Private PDFs, biomarker values, notes, exports, and account data never go to
  AI tools, Exa, Firecrawl, OCR services, logs, screenshots, or external
  processing.
- Source documents and structured values remain separate.
- Use `unknown` when ranges, units, or comparisons are not trustworthy.
- The app describes personal tracking data; it does not diagnose, advise,
  prescribe, score health, or encourage extra testing.

## Commands

Use exact commands. Prefer focused checks while developing, then the full
validator before handoff.

```bash
composer install
npm install
npm run build
php artisan test
sh scripts/validate.sh
```

Focused examples:

```bash
php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php
php artisan test tests/Unit/TabularBiomarkerExtractionTest.php
php artisan test --filter='auto-imports trusted CMA duplicate names when units disambiguate them'
```

For UI/intake changes, validation is not enough. Also use the browser with the
local app:

```text
open /blood-tests -> upload a fresh PDF -> inspect the result page
```

Check the observable result: confirmed row count, draft/review row count,
progress stages, source document visibility, and absence of unrelated manual
entry friction.

## Source Of Truth

Read these first:

- `README.md`
- `docs/project-brief.md`
- `docs/codex-prd.md`
- `docs/agent-efficiency-playbook.md`
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
- `docs/adr/0008-future-ai-agents-must-be-proposal-only.md`
- `docs/adr/0009-use-local-best-effort-pdf-extraction.md`
- `docs/adr/0010-use-layout-aware-positional-text-extraction.md`
- `docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`
- `docs/codex-v2-clean-autoconfirm-kickoff.md`
- `docs/research/laravel-stack-decision.md`
- `docs/research/ai-architect-program-transfer.md`
- `docs/research/2026-06-18-blood-values-workflow-value-evidence.md`
- `docs/research/2026-06-18-engineering-source-radar.md`
- `docs/testing/pdf-first-intake-test-conversion.md`
- `docs/current-operating-intent.md`
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
- when PDF extraction changes, follow the CMA finetune loop:
  sanitized synthetic fixture -> failing test -> smallest parser/trust fix ->
  focused tests -> full validator -> fresh live upload.
- keep runtime AI agents out of V1; any later AI agent must be proposal-only,
  human-approved, owner-scoped, idempotent, auditable, and covered by a new
  spec/privacy review.
- do not install Composer packages by reputation alone. Any package touching
  auth, private files, exports, jobs, logs, external APIs, or health data needs
  an explicit fit, privacy, maintenance, and validation review first.

## Validation

Current validation command:

```bash
sh scripts/validate.sh
```

At this implementation stage, the command validates scaffold integrity, runs the
Laravel test/quality suite, builds frontend assets, and checks whitespace. Add
browser checks when a task changes the user-facing workflow.

Browser/live QA gate for intake changes:

- use a fresh upload, not an already-processed blood test record;
- verify the result page, not only the database;
- compare confirmed and draft counts against the expected parser behavior;
- confirm that drafts remain out of downstream status/history/compare/consult
  surfaces until explicitly confirmed;
- do not claim PR readiness from green tests alone.

## Git And PR Rules

- Stage only the files you changed; do not use `git add .`.
- Keep parser fixes, UI fixes, and docs-only changes atomic unless they are one
  inseparable behavior.
- Use terse Conventional Commit style already present on this branch, e.g.
  `fix: ...` or `test: ...`.
- Push to the feature branch when asked, but do not merge.
- Keep the PR draft until code review and owner live verification have both
  passed.
- If real-upload behavior contradicts tests, treat the live behavior as the
  next bug report and write a synthetic regression before changing parser code.

## Product Rules

- The original lab-result PDF is the V1 intake source.
- Structured biomarker values become usable dashboard data only after explicit
  confirmation or ADR-0011 confidence-gated deterministic auto-confirm.
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
- AI output is not trusted health data. It may only become structured health
  data after explicit user review or confirmation.
- Spatie/Freek material is a Laravel engineering quality reference, not a
  blanket approval to add Spatie packages to V1.
- Treat Livewire public properties and action parameters as untrusted browser
  input. Any value that determines ownership, authorization, export, download,
  deletion, or confirmation must be validated and authorized server-side.
- Lab PDFs must be stored as private source documents with generated storage
  names and owner-authorized download routes. Original filenames may be kept as
  sanitized metadata, not trusted paths.
- Keep `docs/testing/pdf-first-intake-test-conversion.md` aligned with the real
  Pest/Livewire tests that prove the PDF-intake behavior.
- Keep `docs/codex-v2-clean-autoconfirm-kickoff.md` aligned with the actual V2
  branch state when CMA extraction, auto-confirm, or intake UX behavior changes.

## Truth-First Working Rules

- Treat user assumptions, diagnoses, and proposed fixes as unverified until
  checked against the project files, code, documentation, or constraints.
- If a plan is wrong or too broad, say so directly and propose the smaller
  correct next step.
- Prefer minimal, verifiable changes over broad rewrites.
- Do not claim completion without fresh validation evidence.
