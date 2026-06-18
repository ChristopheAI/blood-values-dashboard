# Session Handoff

Use this file to make the project resumable from another Codex thread.

Chat context is temporary. Repository context is durable.

## Current Repository Purpose

This repository is the start of a personal Laravel project: a private blood
values dashboard for organizing blood tests, biomarkers, context notes,
documents, trends, comparisons, reminders, and consult preparation.

The product boundary is explicit:

- build a personal ordering and follow-up system;
- do not build a diagnosis machine;
- do not provide medical advice.

## Current Operating Model

```text
brief -> evidence -> ADR -> spec -> task plan -> baseline commit -> build -> verify -> review -> handoff
```

The project is following the `ChristopheAI/Codex` starter-kit workflow:

- start with a filled project brief;
- adapt `AGENTS.md`;
- add a validation command;
- commit planning artifacts before the first implementation task;
- make V1 complete only when `sh scripts/validate.sh` proves the relevant
  behavior.

## Latest Durable Checkpoint

Update this section after each meaningful session.

- Branch: `main`
- Remote:
  - `origin` -> `https://github.com/ChristopheAI/blood-values-dashboard.git`
- Commit state:
  - Planning baseline, pre-scaffold review gate, AI Architect decision layer,
    future AI-agent boundary, Freek/Spatie engineering profile, and engineering
    source radar should be committed and pushed.
- Latest meaningful local checkpoint:
  - Current HEAD after this session should include:
    `docs: add engineering source radar`
- Files created so far:
  - `README.md`
  - `docs/project-brief.md`
  - `docs/v1-spec.md`
  - `docs/product-system-check.md`
  - `docs/evidence/source-index.md`
  - `docs/templates/adr-template.md`
  - `docs/adr/0001-use-repo-as-project-control-plane.md`
  - `docs/adr/0002-use-adrs-for-architecture-decisions.md`
  - `docs/adr/0003-use-livewire-starter-kit-for-v1.md`
  - `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
  - `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
  - `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
  - `docs/adr/0007-use-staged-laravel-quality-ladder.md`
  - `docs/adr/0008-future-ai-agents-must-be-proposal-only.md`
  - `docs/research/laravel-stack-decision.md`
  - `docs/research/ai-architect-program-transfer.md`
  - `docs/research/competitor-analysis.md`
  - `docs/research/exa-firecrawl-research-runbook.md`
  - `docs/research/2026-06-18-apple-health-context-import.md`
  - `docs/research/2026-06-18-andrew-codesmith-public-thinking-profile.md`
  - `docs/research/2026-06-18-nuno-maduro-public-engineering-profile.md`
  - `docs/research/2026-06-18-nuno-maduro-laravel-quality-deep-dive.md`
  - `docs/research/2026-06-18-relaticle-laravel-ai-agent-patterns.md`
  - `docs/research/2026-06-18-freek-spatie-laravel-engineering-profile.md`
  - `docs/research/2026-06-18-engineering-source-radar.md`
  - `docs/testing/pdf-first-intake-test-conversion.md`
  - `docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md`
  - `docs/superpowers/plans/2026-06-16-first-vertical-slice.md` (superseded)
  - `AGENTS.md`
  - `docs/session-handoff.md`
  - `docs/validation-protocol.md`
  - `docs/ops/production-checklist.md`
  - `docs/reviews/pre-scaffold-review-request.md`
  - `docs/reviews/pre-scaffold-review-scorecard.md`
  - `docs/reviews/pre-scaffold-review-result.md`
  - `scripts/validate.sh`
  - `.github/workflows/ci.yml`
- What was validated:
  - Project brief exists and defines the personal blood values dashboard.
  - Control-plane files exist.
  - Planning-stage validation command exists.
  - No Laravel app should be scaffolded in this phase.
  - `sh scripts/validate.sh` passed after the control-plane files were added.
  - `sh scripts/validate.sh` passed again after `docs/v1-spec.md` and the
    stricter spec checks were added.
  - Decision research recommends the Laravel Livewire starter kit as the likely
    V1 foundation, with Filament and Inertia deferred until concrete need.
  - V1 spec defines data model, workflows, status calculation, privacy boundary,
    first vertical slice, and validation plan.
  - First vertical-slice implementation plan exists and covers planning
    baseline commit, Livewire starter scaffold, Laravel validation, status
    domain logic, data model, blood test creation, result entry, biomarker
    history, and compare two tests.
  - `sh scripts/validate.sh` passed after the first-slice plan was added.
  - Control-plane audit found missing root orientation and validation/privacy
    docs; `README.md`, `docs/validation-protocol.md`, and
    `docs/ops/production-checklist.md` were added.
  - First-slice plan was corrected so baseline commit and scaffold copy preserve
    the full control-plane, including `README.md`, validation protocol, and
    production/privacy checklist.
  - `sh scripts/validate.sh` passed after the control-plane coherence repair.
  - Pre-scaffold reviewer packet and scorecard were added so a senior Laravel
    architect and application security/privacy reviewer can give a go/no-go
    before implementation.
  - `sh scripts/validate.sh` passed before committing the pre-scaffold review
    gate.
  - Private GitHub repository was created:
    `https://github.com/ChristopheAI/blood-values-dashboard`.
  - `main` was pushed to `origin/main`.
  - GitHub issue `#1` was created for the pre-scaffold review gate:
    `https://github.com/ChristopheAI/blood-values-dashboard/issues/1`.
  - `ChristopheAI/ai-architect-program-research` was reviewed as a reusable
    research operating-system pattern.
  - Evidence/source discipline, ADR templates, four ADRs, and a product-system
    check were added to strengthen the planning baseline.
  - `scripts/validate.sh` now checks that the evidence and ADR layer exists.
  - Product direction was corrected from manual-first to PDF-first intake with
    review/confirmation.
  - ADR-0005 supersedes the manual-first intake decision while preserving
    owner scoping, `unknown` status, and no-medical-advice boundaries.
  - The old manual-first pre-scaffold review was reset and replaced with a
    refreshed PDF-first `GO WITH CHANGES` result.
  - Firecrawl competitor research was added across lab trackers, health
    timeline products, optimization platforms, open-source/local-first tools,
    and practitioner search surfaces.
  - The competitor analysis reinforces the V1 boundary: PDF-first intake,
    reviewed/confirmed values, trends, comparison, context, consult export,
    privacy/export/delete, no AI advice, no optimal-range claims, and no
    wearable sync in V1.
  - `sh scripts/validate.sh` passed after adding the Firecrawl competitor
    analysis and source-index entry.
  - ADR-0006 was added to define Exa as the public source-discovery tool and
    Firecrawl as the public extraction/monitoring tool, while forbidding both
    from processing private health data in V1.
  - The first Exa plus Firecrawl research run was started for Apple Health
    context import. It found public evidence for large Apple Health exports,
    streaming XML parsing, local database imports, deduplication, and treating
    wearable data as V2 context rather than V1 biomarker data.
  - Exa and Firecrawl were used to build a public-source Andrew Codesmith
    thinking profile. The useful transfer is pragmatic AI-assisted app
    building, learning loops, and product-building energy; he is not treated as
    authority for medical-data privacy architecture.
  - Public-source research on Nuno Maduro was added as a Laravel/PHP quality
    profile. The useful transfer is Pest-style behavior tests, Pint formatting,
    Larastan/PHPStan static analysis, architecture tests, browser checks,
    dependency review, and CI guardrails once implementation starts.
  - A deeper Nuno Maduro research run was added and promoted into ADR-0007: use
    a staged Laravel quality ladder. The app should progress from scaffold
    integrity to behavior tests, Pint, Larastan/PHPStan, architecture tests,
    browser workflow proof, dependency/security checks, CI, and later
    type-coverage/mutation testing for critical rules.
  - The Relaticle Laravel AI-agent case study was reviewed. It does not change
    V1, but it produced ADR-0008: future AI agents must be proposal-only,
    owner-scoped, idempotent, auditable, human-approved, and unable to directly
    mutate or interpret private health records.
  - Freek.dev and Spatie public engineering material were reviewed. The useful
    transfer is package discipline, living project guidelines, AI-as-helper not
    reviewer, architecture testing later, and no Spatie package by reputation
    alone. Packages touching private data require explicit review.
  - Additional engineering-source research was run with Exa and Firecrawl across
    Laravel, Livewire, Pest, security, file-upload, and architecture sources.
    This produced a source radar for implementation guardrails: private lab-PDF
    storage, generated filenames, owner-authorized downloads, Livewire
    public-property/action-parameter distrust, cross-user denial tests, and
    package review before sensitive dependencies.
  - The engineering source radar was converted into an explicit first-slice test
    contract in `docs/testing/pdf-first-intake-test-conversion.md`. It names the
    future Pest/Livewire test files, test cases, reasons, and expected proof for
    PDF upload, private storage, owner authorization, Livewire tamper denial,
    deletion access blocking, package review, and no runtime Exa/Firecrawl/AI
    processing of lab PDFs.
- Known gaps:
  - `scripts/validate.sh` is still planning-stage only and must be upgraded
    before implementation.
  - No Laravel scaffold yet.
  - The PDF-first pre-scaffold result is an internal planning review, not an
    external human review.
- Next recommended action:
  - Keep Apple Health or wearable import as a separate V2 ADR/spec before any
    implementation.
  - Before installing any Composer package that touches auth, files, exports,
    jobs, logs, or health data, create a package review note or ADR.
  - When Laravel implementation resumes, create the future Pest/Livewire tests
    named in `docs/testing/pdf-first-intake-test-conversion.md` before building
    the matching behavior.

## Active Context Markers

| Marker | Type | Meaning | How To Resume |
| --- | --- | --- | --- |
| planning-baseline | project state | The project is still before Laravel implementation. | Read `README.md`, `AGENTS.md`, `docs/session-handoff.md`, `docs/v1-spec.md`, the active plan, review docs, and run `sh scripts/validate.sh`. |

## Handoff Prompt For A New Codex Thread

```text
Read README.md, AGENTS.md, docs/session-handoff.md, docs/validation-protocol.md,
docs/project-brief.md, docs/v1-spec.md, docs/product-system-check.md,
docs/evidence/source-index.md, docs/adr/,
docs/research/laravel-stack-decision.md,
docs/research/ai-architect-program-transfer.md,
docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md,
docs/superpowers/plans/2026-06-16-first-vertical-slice.md as historical context,
docs/reviews/pre-scaffold-review-request.md,
docs/reviews/pre-scaffold-review-scorecard.md,
docs/reviews/pre-scaffold-review-result.md, and the latest git status/log.

Summarize:
- the product purpose;
- the current planning checkpoint;
- what has been validated;
- what the next smallest action is.

Do not implement Laravel until Christophe explicitly chooses an execution path
for the first-slice plan.
```

## End-Of-Session Update Checklist

- [ ] Record latest branch and commit state.
- [ ] Record what was validated and how.
- [ ] Record known gaps.
- [ ] Record the next smallest action.
- [ ] Keep this file short enough that a new thread can read it quickly.
