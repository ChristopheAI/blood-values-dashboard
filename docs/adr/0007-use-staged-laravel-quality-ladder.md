# ADR-0007: Use A Staged Laravel Quality Ladder

## Status

Accepted

## Context

The project will move from planning into Laravel implementation. It handles
private health data, lab PDFs, biomarker values, context notes, exports, and
deletion. Running locally is not enough proof that the product is safe or
correct.

Public-source research on Nuno Maduro's Laravel/PHP work reinforces that modern
AI-assisted Laravel development needs layered guardrails: formatting, static
analysis, behavior tests, architecture tests, browser tests, CI, and dependency
security checks.

The project should adopt this philosophy without copying a strict starter kit
blindly or blocking the first vertical slice with every possible quality tool at
once.

## Decision

Use a staged Laravel quality ladder once implementation resumes.

Default ladder:

1. Scaffold integrity.
2. Domain and feature behavior tests.
3. Formatting with Pint or equivalent.
4. Static analysis with Larastan/PHPStan.
5. Architecture tests for privacy and product boundaries.
6. Browser workflow proof.
7. Dependency/security checks and CI.
8. Type coverage and mutation testing for critical domain rules when the code
   is stable enough.

`scripts/validate.sh` must evolve with the phase of the project. A V1 is not
complete until the validation command proves the relevant behavior for the
current phase.

## Evidence

- Source: `docs/research/2026-06-18-nuno-maduro-laravel-quality-deep-dive.md`
  - Claim type: inference
  - Summary: Nuno Maduro's public Laravel/PHP quality work supports staged
    adoption of Pest, Pint, Larastan/PHPStan, architecture tests, browser tests,
    dependency checks, and CI.

- Source: `docs/research/2026-06-18-nuno-maduro-public-engineering-profile.md`
  - Claim type: inference
  - Summary: Nuno's public profile is directly relevant to the Laravel
    implementation validation stack.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: V1 includes private PDF-first intake, reviewed biomarker values,
    owner scoping, export/delete, and no diagnosis or medical advice.

- Source: `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
  - Claim type: fact
  - Summary: Exa and Firecrawl remain public research tools and must not become
    private health-data processors.

## Considered Options

- Keep only basic Laravel tests.
- Copy Nuno's strict starter kit wholesale.
- Add every strict tool immediately.
- Use a staged quality ladder tied to project phase.

## Decision Drivers

- Private health data requires stronger proof than a normal hobby app.
- The first vertical slice still needs to move.
- Tooling should enforce product boundaries, not become ceremony.
- AI-assisted code needs stricter validation, not more trust.
- Dependency risk matters when handling files and health data.

## Consequences

- The validation command must be upgraded when Laravel implementation starts.
- Pest becomes the preferred test style if compatible with the chosen starter
  kit.
- Pint should be added early once PHP code exists.
- Larastan/PHPStan should be added after the initial app/domain structure
  exists.
- Architecture tests should protect privacy and product boundaries.
- Browser tests should prove the real user workflows.
- Dependency review and `composer audit` become implementation concerns.
- Type coverage and mutation testing are later quality gates for critical
  domain rules, not day-one blockers.

## Confidence

High

## Follow-Up Questions

- Which Laravel scaffold branch is the active implementation branch for
  applying the ladder?
- Should Pest browser testing be adopted directly, or should Browser/Chrome
  validation remain the first E2E layer until compatibility is proven?
- What Larastan/PHPStan level is realistic after the first domain slice exists?
