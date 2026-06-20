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

- Branch: `codex/v2-clean-autoconfirm`
- Worktree:
  `/Users/christophe/Projects/Laravel 1st project`
- Remote:
  - `origin` -> `https://github.com/ChristopheAI/blood-values-dashboard.git`
- Commit state:
  - V2 clean-by-default extraction and confidence-gated auto-confirm are
    implemented on this branch and awaiting owner review/live verification.
  - Do not merge yet. The user reviews the code and live-verifies a fresh upload
    first.
- Latest meaningful local checkpoint:
  - Page-aware tabular row clustering prevents cross-page name pollution while
    preserving real page-continuation rows.
  - Catalog anchoring is deterministic and conservative: ambiguous exact aliases
    and equal-strength prefixes stay as drafts.
  - `AUTO_CONFIRM_CONFIDENCE_THRESHOLD = 0.85`; clean, unambiguous,
    catalog-matched rows may be auto-confirmed, while prefix-only, ambiguous,
    missing-unit, missing-range, unmatched, or noisy rows remain drafts.
  - Extracted biomarker names are whitespace-normalized before catalog matching,
    including non-breaking PDF spacing, so clean rows do not become unmatched
    drafts because of layout artifacts.
  - Catalog anchoring accepts punctuation/whitespace boundaries after a
    canonical name, but still keeps those prefix-only rows as drafts.
  - Extracted decimal-comma values and numeric PDF whitespace are normalized
    before confidence gating, status calculation, and storage, matching manual
    review input behavior.
  - Extracted units/reference units are whitespace-normalized before confidence
    gating, status calculation, and storage, including non-breaking PDF spacing,
    so padding cannot create an `unknown` auto-confirmed value or false unit
    mismatch.
  - Wrapper/trailing punctuation around extracted value/reference units is
    normalized centrally before auto-confirm, so candidate sources behave
    consistently.
  - Duplicate extracted candidates that map to the same catalog biomarker in one
    run stay as separate drafts, preventing silent overwrite/auto-confirm of an
    ambiguous repeated row.
  - Present but unparseable reference bounds keep extracted rows below the
    auto-confirm gate; parser output must be numerically parseable, not just
    non-empty.
  - Candidates with unparseable values are counted in extraction telemetry but
    are not stored as biomarker results.
  - Reference-unit mismatches keep extracted rows below the auto-confirm gate
    because comparison rules are not trustworthy.
  - Tabular reference cells with an explicit unit preserve that `reference_unit`
    instead of falling back to the value unit, including compact forms like
    `10-20g/L` and `<8g/L`, so mismatches are not masked.
  - Wrapper/trailing punctuation around tabular reference units is stripped, so
    common formatting like `10-20 (mg/L)` does not force a clean row to draft.
  - Reversed two-sided reference ranges keep extracted rows below the
    auto-confirm gate; one-sided parseable ranges remain allowed.
  - Empty intake is upload-first with a PDF dropzone and file-selection
    auto-submit. No metadata form, email, account field, OCR, AI, external
    service, or new package was introduced.
  - The result screen lands on confirmed values, status/trend affordances, and a
    compact review strip for below-threshold rows.
  - Confirmed-only downstream behavior remains covered for dashboard, history,
    compare, consult overview, and data export; auto-confirmed extracted rows
    count as confirmed, drafts do not.
  - Blood-test status recalculation is owner-scoped: corrupt cross-owner
    biomarker drafts cannot make, feed, or block confirmed owner status.
  - Extracted source snippets are stored as compact single-line trace metadata,
    preserving short provenance without raw line-break/control whitespace.
  - Failed and empty extraction states are inspectable and fall back to manual
    entry.
  - Latest full local validation passed on this branch with `sh
    scripts/validate.sh`.
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
  - `docs/research/2026-06-18-blood-values-workflow-value-evidence.md`
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
  - Exa public-source research was added to answer which workflow costs time,
    money, people, or chaos. The evidence points to retrieval, normalization,
    confirmation, longitudinal comparison, and consult preparation across
    scattered lab documents and portals. Firecrawl was requested but not
    available as a callable tool in that session, so it was not used.
  - The workflow-value evidence reinforces the existing PDF-first,
    review-confirmed V1 slice and does not justify AI interpretation,
    unreviewed OCR, wearable sync, provider integrations, or medical advice in
    V1.
  - The planning baseline was committed before implementation.
  - Laravel was scaffolded from the Livewire starter kit on branch
    `codex/pdf-first-intake-slice`.
  - The PDF-first intake test contract was converted into real Pest/Livewire
    coverage before implementing the behavior.
  - `scripts/validate.sh` now validates the implementation phase: scaffold
    files, `composer test`, `npm run build`, and `git diff --check`.
  - `sh scripts/validate.sh` passed with 54 tests, 565 assertions, Pint,
    PHPStan, frontend build, and whitespace checks.
- Known gaps:
  - ADR-0011 is still Proposed until owner review and a fresh local upload
    verify the real UX outside synthetic fixtures.
  - Native OS picker opening and OS drag/drop acceptance are manual live-review
    checks; automated Dusk coverage uses `attach()`.
  - Each imperfect real lab format still needs a sanitized synthetic fixture
    before parser tuning. Do not commit or log real PDF content or values.
  - OCR, AI/LLM, provider integrations, wearable sync, and medical advice remain
    out of scope unless a later spec/ADR deliberately expands the boundary.
- Next recommended action:
  - Let the owner review this branch and live-verify a fresh local upload:
    Choose PDF opens the native picker; drag/drop highlights and accepts a PDF;
    upload lands on auto-filled results plus review strip.
  - If live review finds another imperfect row, reproduce it as a sanitized
    synthetic fixture first, then tune the parser against that test.
  - Before installing any Composer package that touches auth, files, exports,
    jobs, logs, or health data, create a package review note or ADR.
  - Keep new implementation inside the PDF-first V1 boundary unless a spec, ADR,
    and task plan deliberately expand it.

## Active Context Markers

| Marker | Type | Meaning | How To Resume |
| --- | --- | --- | --- |
| v2-clean-autoconfirm | project state | V2 clean extraction, confidence-gated auto-confirm, upload-first intake, and hardening follow-ups are implemented on `codex/v2-clean-autoconfirm`; pending owner review/live upload before merge. | Read `README.md`, `AGENTS.md`, `docs/session-handoff.md`, `docs/v2-spec.md`, `docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`, `docs/codex-v2-clean-autoconfirm-kickoff.md`, latest git log/status, then run `sh scripts/validate.sh`. |

## Handoff Prompt For A New Codex Thread

```text
Read README.md, AGENTS.md, docs/session-handoff.md, docs/validation-protocol.md,
docs/project-brief.md, docs/v1-spec.md, docs/product-system-check.md,
docs/evidence/source-index.md, docs/adr/,
docs/v2-spec.md,
docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md,
docs/research/laravel-stack-decision.md,
docs/research/ai-architect-program-transfer.md,
docs/research/2026-06-18-blood-values-workflow-value-evidence.md,
docs/research/2026-06-18-engineering-source-radar.md,
docs/testing/pdf-first-intake-test-conversion.md,
docs/codex-v2-clean-autoconfirm-kickoff.md,
docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md,
docs/superpowers/plans/2026-06-16-first-vertical-slice.md as historical context,
docs/reviews/pre-scaffold-review-request.md,
docs/reviews/pre-scaffold-review-scorecard.md,
docs/reviews/pre-scaffold-review-result.md, and the latest git status/log.

Summarize:
- the product purpose;
- the current implementation checkpoint;
- what has been validated;
- what the next smallest action is.

Continue on `codex/v2-clean-autoconfirm`. Do not merge. ADR-0011 remains
Proposed until owner review and fresh local upload verification. Do not expand
beyond the PDF-first boundary without a spec, ADR, and task plan. Runtime AI
interpretation, unreviewed OCR, provider integrations, wearable sync, and
medical advice remain out of scope.
```

## End-Of-Session Update Checklist

- [ ] Record latest branch and commit state.
- [ ] Record what was validated and how.
- [ ] Record known gaps.
- [ ] Record the next smallest action.
- [ ] Keep this file short enough that a new thread can read it quickly.
