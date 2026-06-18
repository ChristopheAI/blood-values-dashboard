# Research Run: Freek / Spatie Laravel Engineering Profile

Date: 2026-06-18

Question:

- What should this private blood values dashboard borrow from Freek Van der
  Herten and Spatie's public Laravel engineering material?

Decision affected:

- Laravel package adoption.
- AI-assisted coding rules.
- V1 validation and maintenance bar.
- Whether Spatie packages should be installed because they are popular.

Scope:

- Public Freek.dev pages.
- Public Spatie blog, guidelines, package list, and package documentation.
- Exa discovery and browser verification.

Out of scope:

- Medical interpretation.
- Personal lab PDF processing.
- Runtime Firecrawl, Exa, or AI use inside the Laravel app.
- Installing Laravel or any Composer package.

## Research Tool Use

Exa was used for public source discovery and content fetching.

Browser verification was used for current Freek.dev, Spatie, package, and
guideline pages.

Firecrawl was not executed in this run because no local Firecrawl MCP or
environment-variable configuration was available. The project should not paste
API keys into shell commands, project files, docs, or temporary committed
artifacts. This preserves ADR-0006's boundary: Firecrawl is allowed for public
research, but not at the cost of careless secret handling.

## Queries Used

- `Freek Van der Herten Spatie Laravel package development testing maintainability package training Pest GitHub Actions documentation`
- `site:freek.dev Laravel Spatie package testing Freek Van der Herten package design`
- `site:freek.dev "You can't out-prompt an attacker" Laravel AI authenticated user server-side human irreversible`

## Sources

- `https://freek.dev/`
- `https://freek.dev/1671-how-we-created-200-packages`
- `https://freek.dev/2980-introducing-spatie-guidelines-for-laravel-boost`
- `https://freek.dev/2710-architecture-testing-in-laravel-with-pest`
- `https://freek.dev/2843-running-php-tests-in-parallel-on-github-actions`
- `https://freek.dev/topics/ai`
- `https://laravelpackage.training/`
- `https://spatie.be/guidelines`
- `https://spatie.be/open-source/packages`
- `https://spatie.be/blog/the-robots-are-replacing-the-packages`
- `https://spatie.be/blog/reading-code-with-ai-not-generating-it`
- `https://spatie.be/blog/locally-great-globally-drifting`
- `https://spatie.be/blog/spatie-guidelines-as-ai-skills`
- `https://spatie.be/docs/laravel-medialibrary/v11/introduction`
- `https://spatie.be/docs/laravel-backup/v9/introduction`
- `https://spatie.be/docs/laravel-health/v1/introduction`
- `https://spatie.be/docs/laravel-permission/v6/introduction`
- `https://spatie.be/docs/laravel-activitylog/v4/introduction`

## Decision

Use Freek and Spatie as a Laravel engineering reference, not as a package
shopping list.

For this project:

```text
No Composer package is accepted by reputation alone.
Every package that touches auth, files, exports, jobs, health data, or logs
needs a fit, ownership, privacy, maintenance, and validation review first.
```

Spatie's public material strengthens our existing quality direction:

- write down project rules before AI or humans generate code;
- use AI as a reader, counter, candidate generator, and implementation helper;
- keep human review as the final authority;
- test behavior and architecture, not just whether the app boots;
- install packages when they reduce ownership of a hard, external, or
  opinionated problem;
- avoid packages when native Laravel plus a small local implementation keeps
  the private health-data surface simpler.

## Findings

### Fact: Freek/Spatie's Strength Is Maintainable Laravel Craft

Freek.dev presents Freek as a Laravel developer at Spatie and Oh Dear who
maintains hundreds of open-source packages. Spatie's public package page lists
hundreds of Laravel/PHP packages and large download counts.

Decision utility: A

Impact:

- This is a strong Laravel engineering source.
- It is not a medical, privacy-law, or blood-biomarker authority.
- Use it to sharpen implementation discipline, not product claims.

### Fact: Spatie Packages Are Built From Repeated Real Project Needs

Freek's package history explains that many packages started inside client
projects and were extracted when useful beyond one app.

Decision utility: A

Impact:

- Do not package or abstract early.
- Build V1 around the actual workflow first: upload lab PDF, review values,
  follow trends, compare tests, export/delete data.
- Extract or adopt only when the problem repeats or becomes clearly hard.

### Fact: Good Packages Carry Tests, Documentation, And Maintenance Cost

Freek's package writing emphasizes clear API, documentation, tests, and ongoing
maintenance. Laravel Package Training also frames package quality around
testing, GitHub Actions, changelogs, version support, and maintainability.

Decision utility: A

Impact:

- A dependency is not free. It imports someone else's architecture and update
  lifecycle.
- For private health data, dependency review is part of privacy engineering.
- V1 should prefer Laravel-native behavior unless a package clearly reduces
  risk.

### Fact: The Package Question Has Changed

Spatie's "robots are replacing the packages" article gives a useful rule:
before installing a package, ask whether we want to own the problem.

Decision utility: A

Impact:

- Shared/simple problem: consider owning it locally.
- Hard problem: prefer a strong package if we do not want the edge cases.
- External problem: prefer a maintained package because APIs/protocols drift.
- Tasteful architecture problem: choose an author/library whose direction we
  trust.

For this project:

- storing one private lab PDF per blood test can start with Laravel private
  storage;
- complex media collections, conversions, downloads, or file metadata may later
  justify `spatie/laravel-medialibrary`;
- role/permission management is not a V1 need for a personal single-owner app;
- backups, health checks, and activity logs are candidates only after a review
  of privacy, deployment, retention, and redaction.

### Fact: Spatie Treats Guidelines As Living Project Infrastructure

Spatie's guidelines page says their team writes down agreed coding decisions
and treats guidelines as living documents. Their AI-guidelines posts turn those
rules into reusable AI context.

Decision utility: A

Impact:

- `AGENTS.md`, `docs/validation-protocol.md`, ADRs, and session handoff files
  are not ceremony; they are the project's operating memory.
- When the Laravel app exists, its conventions should be written into the repo
  before broad AI-assisted implementation.
- AI output should be checked against committed rules, not vibe.

### Fact: AI Is Useful, But Not The Reviewer

Spatie's AI review writing separates AI as a mechanical helper from human
review as the real judgment layer. Their codebase-reading post also says AI
claims should be treated as hypotheses and checked against code.

Decision utility: A

Impact:

- Keep the current truth-first rule.
- Use AI to map routes, models, migrations, ownership checks, test gaps, and
  drift.
- Do not let AI approve architecture, privacy, or medical-language boundaries.
- This reinforces ADR-0008: AI may help propose; it does not own truth.

### Fact: Architecture Tests And Parallel CI Are Later Scaling Tools

Freek.dev points to architecture testing with Pest and explains parallel test
sharding on GitHub Actions for larger suites.

Decision utility: B

Impact:

- Architecture tests belong in the staged Laravel quality ladder once code
  exists.
- Parallel CI is not a V1-first requirement. It becomes useful when test volume
  grows.
- Start with correct tests; optimize runtime later.

### Fact: Freek.dev Surfaces Current Laravel Security And Maintenance Signals

The Freek.dev homepage and AI topic pages currently surface items about
Livewire security updates, prompt-injection guardrails, AI evals, Packagist
supply-chain security, logging, and monitoring.

Decision utility: B

Impact:

- Keep dependencies current once implementation begins.
- Any Livewire starter-kit app must have a security update habit.
- AI safety is tool-boundary and test-boundary work, not prompt faith.

## Spatie Package Fit Notes

These are not install decisions. They are future review notes.

### `spatie/laravel-medialibrary`

Potential fit:

- multiple documents per blood test;
- document metadata;
- controlled downloads;
- future previews or derived PDF images;
- file lifecycle events.

V1 default:

- Start with Laravel private storage unless the first implementation slice shows
  real complexity.

Review required before adoption:

- private disk behavior;
- no public URLs for lab documents;
- owner-scoped download authorization;
- deletion cascade;
- export behavior;
- metadata leakage;
- test coverage for direct URL access.

### `spatie/laravel-backup`

Potential fit:

- deployed production backups;
- scheduled encrypted backups;
- monitoring backup health.

V1 default:

- Not needed for local planning or first private prototype.

Review required before adoption:

- encryption;
- local vs cloud destination;
- retention;
- restore proof;
- whether backups duplicate sensitive health data outside the intended storage
  boundary.

### `spatie/laravel-health`

Potential fit:

- deployed app monitoring;
- disk, DB, queue, schedule, backup, and security advisory checks.

V1 default:

- Not needed before deployment.

Review required before adoption:

- whether health endpoints expose sensitive operational details;
- authentication/secret-token setup;
- notification destination privacy.

### `spatie/laravel-activitylog`

Potential fit:

- audit trail for uploads, confirmed values, exports, and deletion events.

V1 default:

- Consider a small local audit trail first, because generic model-change logs
  can accidentally duplicate sensitive biomarker data.

Review required before adoption:

- redaction;
- retention;
- export/delete interaction;
- whether old/new values may contain health data;
- owner scoping for log reads.

### `spatie/laravel-permission`

Potential fit:

- later multi-user, caregiver, practitioner, or family-sharing model.

V1 default:

- Do not use for a single-owner personal app.
- Use owner-scoped policies and Laravel authorization.

Review required before adoption:

- real role model;
- invitation/sharing workflow;
- revocation;
- audit and export/delete implications.

### `spatie/guidelines-skills` Or `spatie/boost-spatie-guidelines`

Potential fit:

- dev-only coding guidance once Laravel Boost or a compatible AI workflow is
  deliberately added.

V1 default:

- Do not add until the Laravel scaffold exists and the project has chosen its
  coding conventions.

Review required before adoption:

- dev-only dependency scope;
- compatibility with this repo's truth-first rules;
- no override of privacy/product boundaries.

## Transfer To This Project

Adopt now:

- Treat this repo's docs as living project guidelines.
- Keep `scripts/validate.sh` as the gate, not a decorative script.
- Continue using ADRs when package, privacy, AI, or data-flow decisions change.
- Do not install Spatie packages just because Spatie is trusted.
- Ask "do we want to own this problem?" before each dependency.
- Keep private health-data flows small, explicit, owner-scoped, and tested.

Adopt during implementation:

- Add behavior tests before broad UI growth.
- Add architecture tests once module boundaries exist.
- Add dependency/security checks before external packages touch files, exports,
  jobs, auth, or health data.
- Write project-specific conventions into the repo before using AI to generate
  large amounts of code.

Reject for V1:

- Spatie package bundle-by-default.
- `spatie/laravel-permission` for a single-owner app.
- `spatie/laravel-medialibrary` before native private storage proves too thin.
- generic activity logging that stores old/new biomarker values without
  redaction rules.
- production monitoring packages before there is a deployment target.
- AI coding guidelines that override local privacy, no-advice, and confirmation
  rules.

## Failure Modes In Practice

- Package sprawl: trusted vendor becomes an excuse to install instead of
  designing the smallest private-data path.
- Hidden data duplication: media, backup, or activity-log packages copy private
  files/values into places the app does not expose clearly.
- AI global drift: generated files look fine locally but diverge from project
  conventions over time.
- False security: "popular package" is treated as enough review.
- Over-tooling: V1 gets delayed by ops packages that solve deployment problems
  before deployment exists.

## Proven Workarounds

- Keep a package review note or ADR before sensitive dependencies.
- Start with native Laravel for first-slice file storage and owner policies.
- Add packages only when they reduce ownership of hard, external, or
  opinionated problems.
- Turn recurring conventions into committed docs and validation checks.
- Use AI to count, list, map, and propose; use human review and tests to decide.

## Recommendation

Freek/Spatie should influence how we build, not what we blindly install.

The strongest project rule from this run:

```text
Spatie is a quality signal, not an approval stamp.
Private health-data dependencies require explicit review.
```

This reinforces ADR-0007 and ADR-0008 rather than replacing them.

## Top 3 Next Actions

1. Owner: Codex. Effort: small. Risk: low. Add this research run to the source
   index, validation script, and handoff.
2. Owner: Codex during first Laravel implementation. Effort: medium. Risk:
   medium. Add a package-review checklist before installing any Composer
   package touching private files, auth, exports, jobs, logs, or health data.
3. Owner: Christophe plus Codex before V1 deployment. Effort: medium. Risk:
   medium. Decide whether backups, activity logs, and health checks are native
   V1 features or reviewed Spatie package integrations.

## Signal Distortions

- Freek.dev is a curated blog/newsletter surface, not an exhaustive engineering
  doctrine.
- Spatie has a commercial and reputational stake in packages, courses, and
  tools.
- Public package popularity does not prove fit for a private health-data app.
- Current AI posts are fast-moving and may become stale.
- This run did not execute Firecrawl extraction because a safe local
  Firecrawl credential path was not present.

## Confidence

High for the package-discipline and validation transfer.

Medium for individual package recommendations until the Laravel scaffold and
first implementation slice exist.
