# Research Run: Nuno Maduro Public Engineering Profile

Date: 2026-06-18

Question:

- Which public engineering patterns from Nuno Maduro are useful for this
  Laravel blood values dashboard?

Decision affected:

- How strict the Laravel implementation validation bar should be once the
  project moves from planning into code.

Scope:

- Public sources only.
- Exa discovery and public web extraction.
- Nuno's Laravel/PHP/open-source work, public profile, and public X mirror
  signals.

Out of scope:

- Private messages or private X content.
- Treating social posts as final architecture authority.
- Using Nuno's work to justify expanding the product into AI advice or medical
  interpretation.

## Identity Resolution

This run focuses on Nuno Maduro, public handle `@enunomaduro` on X and
`@nunomaduro` on GitHub.

Public sources identify him as:

- staff software engineer at Laravel;
- Laravel core team member;
- open-source contributor;
- creator or co-creator of Pest, Pint, Larastan, Laravel Zero, OpenAI for PHP,
  Collision, PHP Insights, and related PHP/Laravel tools.

## Sources

Primary sources:

- `https://github.com/nunomaduro`
- `https://pestphp.com/`
- `https://github.com/pestphp/pest`
- `https://github.com/laravel/pint`
- `https://github.com/larastan/larastan`

Supporting public sources:

- `https://eventy.io/speakers/nunomaduro`
- `https://laravel-news.com/pest2-laracon-2023`
- `https://vanlett.com/enunomaduro`
- `https://x.com/enunomaduro`

X note:

- The direct X page did not expose useful browser-readable text in this run.
- A public mirror surfaced recent public posts. Treat those as lower-weight
  evidence than GitHub, project docs, or Laravel ecosystem sources.

## Findings

### Fact: Nuno is deeply tied to Laravel/PHP quality tooling

His public GitHub/profile surfaces connect him to Pest, Pint, Larastan,
Laravel Zero, OpenAI for PHP, Collision, and other tools.

Decision impact:

- This is directly relevant to our Laravel project.
- Unlike generic creator inspiration, this profile can inform the Laravel
  implementation validation stack.

### Fact: Pest emphasizes readable, expressive tests

Pest describes itself as an elegant PHP testing framework focused on simplicity,
with browser testing, readable failures, architecture testing, parallel testing,
coverage, watch mode, and other quality features.

Decision impact:

- V1 implementation should prefer Pest for feature/domain/browser tests unless
  a later Laravel scaffold makes PHPUnit clearly better.
- Test names should read like product behavior, not framework ceremony.

### Fact: Pint is opinionated code style for Laravel/PHP

Laravel Pint exists to keep code style clean and consistent with minimal setup.

Decision impact:

- Once implementation starts, `scripts/validate.sh` should run Pint or a
  project-equivalent formatting check.

### Fact: Larastan/PHPStan catches bugs before tests

Larastan focuses on finding code errors and improving Laravel code quality,
including bugs that can be detected before runtime tests.

Decision impact:

- Static analysis should be added once the Laravel codebase exists.
- Start at a realistic level and increase strictness over time instead of
  blocking early product flow with a maximal config.

### Fact: Public X mirror emphasizes stricter AI-era guardrails

The public X mirror surfaced posts arguing that AI-written code needs more than
tests: formatting, linting, static analysis, automated refactoring, type
coverage, unit tests, integration tests, CI, and guardrails.

Decision impact:

- This matches the project's existing direction: AI can help build, but the
  system must prove correctness.
- For private health data, guardrails matter more than speed.

### Fact: Public X mirror emphasizes supply-chain security concerns

The mirror surfaced public comments about Composer malware filtering and
registry/release security such as 2FA.

Decision impact:

- Keep dependencies minimal.
- Prefer official Laravel ecosystem packages.
- Review any package that touches PDF parsing, file storage, health data,
  authentication, exports, or background jobs.

### Inference: Nuno's useful transfer is "quality automation as product safety"

The transferable pattern is not only "write tests". It is a stack of automated
guardrails that make AI-assisted Laravel development safer:

```text
formatting -> linting/static analysis -> domain tests -> feature tests
-> architecture tests -> browser tests -> CI validation
```

Decision impact:

- The Laravel implementation should not be considered production-grade simply
  because it runs locally.
- V1 should become complete only when validation proves the sensitive workflows.

## Transfer To This Project

Adopt:

- Pest as the default Laravel test style, if compatible with the chosen starter
  kit.
- Pint or equivalent formatting in validation.
- Larastan/PHPStan once app code exists.
- Architecture tests for product boundaries:
  - private health data must be owner-scoped;
  - no public health-data routes;
  - uploaded documents stored privately;
  - no diagnosis/advice language in V1 UI copy;
  - external research tools must not enter the runtime health-data path.
- Browser tests for core flows:
  - login;
  - PDF-first blood-test creation;
  - review/confirm biomarker values;
  - trend view;
  - compare two tests;
  - export/delete.
- A dependency review habit before adding packages.

Reject:

- Tool worship.
- Adding every strict tool before the first vertical slice can move.
- Treating social posts as final architecture evidence.
- Using AI velocity as a reason to weaken privacy or validation.

## Practical Validation Implication

When the Laravel scaffold exists, evolve `scripts/validate.sh` from planning
checks to implementation checks:

```text
composer validate
composer audit
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm/build checks if frontend assets exist
browser/end-to-end check for critical workflows
```

Exact commands should match the scaffold and installed packages.

## Known Unknowns

- Direct X page content was not browser-readable in this run.
- Public mirror content may be incomplete or stale.
- Pest v4 browser testing and exact Laravel starter-kit compatibility must be
  confirmed inside the actual app before becoming mandatory.
- Larastan strictness level should be calibrated after the real domain model
  exists.

## Recommendation

Use Nuno Maduro as a strong Laravel/PHP quality reference.

For this project, his public engineering profile strengthens this rule:

```text
AI may help write code, but validation owns trust.
```

The most useful next transfer is:

- keep V1 small;
- implement real workflows;
- prove them with Pest, Pint, static analysis, architecture tests, browser
  checks, and CI;
- keep package dependencies conservative because this app handles private
  health data.
