# AGENTS.md

## Project

Personal Laravel/Livewire workspace for private blood-test follow-up: lab PDFs,
biomarkers, context notes, trends, documents, reminders, consult/export, privacy,
and deletion. It is not a diagnosis machine and must not provide medical advice.

## Current Phase

V2 clean-by-default CMA intake with confidence-gated auto-confirm. The Laravel
Livewire scaffold exists; current work is local deterministic PDF-first intake,
compact review for uncertain rows, and confirmed-only downstream behavior.

Keep new work inside the reviewed PDF-first/V2 boundary unless a spec, ADR, and
task plan explicitly expand it. Do not add runtime AI interpretation, unreviewed
OCR, provider integrations, wearable sync, external processing, or
medical-advice features to this slice.

## Current Active Rules

- Local only: no OCR, AI/LLM, external service, network processing, or new package
  for private lab intake without a new ADR/privacy review.
- Private PDFs, biomarkers, symptoms, medication notes, consult exports, account
  data, source snippets, screenshots, and logs must never go to Exa, Firecrawl,
  AI tools, OCR services, or external processors.
- Trusted CMA layout/tabular extraction may auto-confirm deterministic
  high-confidence rows according to ADR-0011.
- Below-threshold rows, catalog conflicts, ambiguous matches, same-unit
  duplicates, missing-unit rows, and uncertain parses stay as drafts.
- Trusted CMA duplicate names may be disambiguated by unit only when every
  duplicate row has a distinct non-empty unit; otherwise keep them in review.
- Confirmed-only downstream remains: status, history, compare, consult, export,
  and trends may use values only after explicit confirmation or ADR-0011
  deterministic auto-confirm.
- Repo-intelligence tooling follows ADR-0012: use only the local/index-only
  advisory profile, and do not enable LLM docs, hooks, telemetry, hosted
  services, AGENTS rewrites, or private-data processing without a new ADR.
- Do not merge this branch. ADR-0011 live upload verification passed on
  2026-06-24 with review remainder, but owner code review still remains before
  merge.

## Intent Layer

Before modifying files in a subdirectory, read the nearest child `AGENTS.md`.
There must be only one root context file: this `AGENTS.md`; do not add a root
`CLAUDE.md`.

Child contexts: `app/Domain`, `app/Http`, `app/Livewire`, `app/Models`,
`resources/views`, `tests`, `docs`, and `database`.

Global invariants stay here and apply everywhere:

- `confirmed_at` is the downstream trust gate.
- Owner scoping must be enforced server-side, not only in UI.
- Private health data never leaves the local/private boundary.
- Source documents and structured values remain separate.
- Use `unknown` when ranges, units, or comparisons are not trustworthy.
- The app describes personal tracking data; it does not diagnose, advise,
  prescribe, score health, or encourage extra testing.

## Skill Routing

Use skills only when they sharpen this repo's workflow:

- `architect` before new slices, ADR/spec changes, workflow changes, or
  privacy-impacting decisions.
- Programming/debugging/TDD skills for code changes, regressions, parser fixes,
  failing tests, and confirmed-only behavior changes.
- Frontend/browser-QA skills for Livewire/Blade UI, intake, dashboard, detail,
  consult, print, or export surfaces.
- GitHub skills only for issue, PR, CI, or review-comment work.
- Prefer verifier/reviewer skills over generic PRD/helper skills when the risk is
  false completion, privacy drift, owner-scope leaks, or confirmed-only regression.
- Do not use broad research, copywriting, automation, or runtime-AI skills to
  expand V1 scope without a new ADR/privacy review.

## Commands

Use exact commands. Prefer focused checks while developing, then the full
validator before handoff.

When dependencies are missing, run `composer install` and `npm install` first.

```bash
npm run build
php artisan test
sh scripts/validate.sh
sh scripts/repowise-local-check.sh
```

Focused examples:

```bash
php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php
php artisan test tests/Unit/TabularBiomarkerExtractionTest.php
php artisan test --filter='auto-imports trusted CMA duplicate names when units disambiguate them'
```

For UI/intake changes, also use the browser: open `/blood-tests`, upload a fresh
PDF, inspect confirmed/draft counts, progress stages, source document visibility,
and absence of unrelated manual-entry friction.

## Source Of Truth

Read narrowly by task. Default order:

1. `README.md`, `docs/project-brief.md`, `docs/current-operating-intent.md`,
   `docs/codex-prd.md`, `docs/v1-spec.md`.
2. Relevant ADRs in `docs/adr/`, especially 0005, 0007, 0009, 0010, 0011, 0012.
3. Relevant child `AGENTS.md`, focused tests, active issue/slice tracker, and
   `docs/validation-protocol.md`.

Use `docs/evidence/source-index.md` for facts vs hypotheses. Use kickoff,
handoff, testing, research, and review docs only when the task touches that area.

## Workflow

Sequence: brief -> evidence -> ADR/spec -> task plan -> build -> verify ->
review -> handoff.

Use GitHub issues as product/build slice trackers, not as replacements for
`AGENTS.md`, ADRs, specs, tests, or live browser proof.

Before implementation:

- Update specs/ADRs when data model, privacy, validation, architecture, stack,
  automation, or workflow gates change.
- Keep out-of-scope items out: unreviewed OCR, AI interpretation, medical
  recommendations, wearable/provider integrations, and runtime external processing.
- Use Exa/Firecrawl only for public research unless a later ADR/privacy review
  and explicit approval allow runtime use.
- Follow ADR-0007's staged Laravel quality ladder; a locally running app is not
  complete.
- PDF extraction changes follow: sanitized synthetic fixture -> failing test ->
  smallest parser/trust fix -> focused tests -> full validator -> fresh upload.
- Runtime AI agents remain out of V1. Any later agent must be proposal-only,
  human-approved, owner-scoped, idempotent, auditable, and covered by spec/privacy
  review.
- Do not install Composer packages by reputation alone; packages touching auth,
  files, exports, jobs, logs, external APIs, or health data need explicit review.

## Validation

Current validation command:

```bash
sh scripts/validate.sh
```

`sh scripts/validate.sh` runs the Laravel test/quality suite, frontend build, and
whitespace checks. UI/intake changes also need browser QA with a fresh upload:
verify the result page, confirmed/draft counts, source document visibility, and
that drafts stay out of downstream surfaces. Green tests alone do not make a PR
ready.

## Git And PR Rules

- Stage only the files you changed; do not use `git add .`.
- Keep parser fixes, UI fixes, and docs-only changes atomic unless inseparable.
- Use terse Conventional Commit style already present on this branch, e.g.
  `fix: ...` or `test: ...`.
- Push to the feature branch when asked, but do not merge.
- Keep the PR draft until code review passes. Owner live verification for
  ADR-0011 passed on 2026-06-24 with review remainder.
- If real-upload behavior contradicts tests, treat the live behavior as the
  next bug report and write a synthetic regression before changing parser code.

## Product Rules

- Original lab PDFs are the intake source and remain separate from structured data.
- Structured values become dashboard data only after explicit confirmation or
  ADR-0011 confidence-gated deterministic auto-confirm.
- Manual entry/correction remains the fallback for untrusted extraction.
- Status is `low`, `normal`, `high`, or `unknown`; use `unknown` when ranges,
  units, values, or comparison rules are not trustworthy.
- Privacy, export, and deletion are V1 concerns, not polish.
- No diagnosis, treatment, supplement advice, health scoring, or extra-testing
  encouragement unless a later spec changes the boundary.
- AI-assisted code is untrusted until validation proves behavior, boundaries, and
  privacy expectations. AI output is not trusted health data.
- Spatie/Freek material is an engineering reference, not package approval.
- Treat Livewire public properties and action parameters as untrusted browser input;
  validate and authorize server-side before ownership, export, download, deletion,
  or confirmation decisions.
- Lab PDFs use private generated storage names and owner-authorized routes;
  original filenames are sanitized metadata, not trusted paths.
- Keep testing/kickoff docs aligned when PDF intake, CMA extraction, auto-confirm,
  or intake UX behavior changes.

## Truth-First Working Rules

- Treat user assumptions, diagnoses, and proposed fixes as unverified until
  checked against the project files, code, documentation, or constraints.
- If a plan is wrong or too broad, say so directly and propose the smaller
  correct next step.
- Prefer minimal, verifiable changes over broad rewrites.
- Do not claim completion without fresh validation evidence.
