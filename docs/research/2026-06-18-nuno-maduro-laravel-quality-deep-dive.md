# Research Run: Nuno Maduro Laravel Quality Deep Dive

Date: 2026-06-18

Question:

- How should Nuno Maduro's public Laravel/PHP quality philosophy influence the
  implementation bar for this private blood values dashboard?

Decision affected:

- The future Laravel validation ladder.
- Whether Pest, Pint, Larastan/PHPStan, architecture tests, browser tests, and
  dependency-security checks become part of the implementation control plane.

Scope:

- Public sources only.
- Exa discovery.
- Firecrawl extraction of public Pest docs, Laravel/PHP tooling pages, public
  Nuno profile surfaces, Laravel News, Over Engineered, Packagist security
  writing, and public X mirror content.

Out of scope:

- Private X content.
- Copying Nuno's strict starter kit wholesale.
- Adding implementation dependencies before the Laravel app phase is active.
- Medical interpretation or health advice.

## Sources

Primary tool and ecosystem sources:

- `https://github.com/nunomaduro`
- `https://pestphp.com/docs/arch-testing`
- `https://pestphp.com/docs/browser-testing`
- `https://pestphp.com/docs/type-coverage`
- `https://pestphp.com/docs/mutation-testing`
- `https://pestphp.com/docs/pest-v4-is-here-now-with-browser-testing`
- `https://github.com/laravel/pint`
- `https://github.com/larastan/larastan`

Supporting sources:

- `https://laravel-news.com/nuno-maduro-laravel-starter-kit`
- `https://overengineered.fm/episodes/actually-good-browser-testing-w-nuno-maduro`
- `https://blog.packagist.com/an-update-on-composer-packagist-supply-chain-security/`
- `https://vanlett.com/enunomaduro`

Source weighting:

- Highest: official docs and GitHub repositories.
- Medium: Laravel News and podcast pages.
- Lower: public X mirror summaries.

## Finding 1: Strictness Is A System, Not A Single Test Suite

Observed:

- Pest docs cover feature/unit tests, browser testing, architecture testing,
  type coverage, and mutation testing.
- Public Nuno-related surfaces repeatedly frame quality as a spectrum:
  formatting, linting, static analysis, automated refactoring, type coverage,
  unit tests, integration tests, browser tests, CI, and guardrails.

Decision impact:

- V1 should not be declared production-grade because it "runs".
- Validation should prove layers of correctness, especially because the app
  handles private health data.

Project translation:

```text
format -> static analysis -> domain tests -> feature tests
-> architecture tests -> browser tests -> dependency checks -> CI
```

## Finding 2: Pest Architecture Tests Are Directly Useful For Product Boundaries

Observed:

- Pest architecture testing lets a project define rules about dependencies,
  namespaces, strict types, forbidden calls, presets, and architectural shape.

Decision impact:

- Architecture tests should be used to protect this project's domain boundary,
  not only code style.

Candidate architecture tests for this app:

- domain logic does not live in Blade views;
- Livewire components orchestrate UI but do not own core status calculation;
- private health records are user-scoped;
- no public routes expose health data;
- external research tools are not imported into runtime health-data code;
- uploaded documents use private storage;
- V1 UI copy does not include diagnosis, treatment, supplement, or medical
  advice language;
- `dd`, `dump`, and debug calls are absent from app code.

## Finding 3: Browser Testing Fits The Real User Workflows

Observed:

- Pest v4 browser testing is Playwright-based and supports Laravel testing
  features such as factories, authentication assertions, events, database
  refreshes, multiple browsers, viewport/device checks, screenshots, and
  debugging.
- The Over Engineered episode positions Pest 4 browser testing as a stronger
  replacement for older, flaky Laravel browser-testing approaches.

Decision impact:

- Browser tests are especially valuable here because the product has sensitive,
  multi-step workflows.

Core browser flows to prove:

- login;
- create blood test from PDF upload;
- review extracted or drafted biomarker values;
- manually correct or add a value;
- confirm a blood test;
- view biomarker trend;
- compare two blood tests;
- create consult export;
- delete/export personal data.

Constraint:

- Pest browser testing should be adopted only after compatibility with the
  actual Laravel scaffold is verified. Until then, browser validation can be
  done through the available Browser/Chrome tooling and later formalized.

## Finding 4: Type Coverage And Mutation Testing Are Valuable But Should Be Staged

Observed:

- Pest type coverage can enforce missing type declarations with minimum
  thresholds.
- Pest mutation testing checks whether tests actually catch intentional code
  changes, which is stronger than coverage alone.

Decision impact:

- Both are useful, but not all strict gates should be mandatory on day one.

Project translation:

- Use type coverage first on app/domain code after the first real vertical
  slice exists.
- Use mutation testing later on critical domain rules:
  - status calculation;
  - owner scoping;
  - deletion/export behavior;
  - confirmation state transitions;
  - parser/draft extraction rules.

Do not:

- block early implementation by requiring 100% mutation coverage before the
  workflow exists.

## Finding 5: Pint And Larastan Are Baseline Laravel Quality Tools

Observed:

- Pint keeps Laravel/PHP code style clean and consistent.
- Larastan extends PHPStan for Laravel and catches classes of bugs before
  runtime tests.

Decision impact:

- These should become part of `scripts/validate.sh` once the Laravel app exists.

Staging recommendation:

- Pint: add early, because it is low-friction.
- Larastan/PHPStan: add after the scaffold and initial domain model exist.
- Start static analysis at a realistic level; increase strictness as the code
  stabilizes.
- Avoid broad ignore rules without explanation.

## Finding 6: Supply-Chain Security Matters For Laravel Health Data

Observed:

- Public PHP ecosystem security writing highlights Composer/Packagist malware
  detection, dependency policy, transparency logs, immutable versions, MFA, and
  package supply-chain attacks.

Decision impact:

- This app should be conservative with Composer packages, especially anything
  touching PDFs, files, auth, exports, background jobs, external APIs, or health
  data.

Project rules:

- Prefer official Laravel ecosystem packages where possible.
- Run `composer audit` in validation once Composer exists.
- Review abandoned packages.
- Require an ADR or documented review for packages that process private data.
- Keep raw research tools such as Exa and Firecrawl out of runtime app code.

## Quality Ladder For This Project

### Phase 0: Planning Baseline

Current state.

Required:

- project brief;
- V1 spec;
- ADRs;
- source index;
- validation protocol;
- `sh scripts/validate.sh`;
- no Laravel scaffold in the planning repo.

### Phase 1: Scaffold Integrity

When Laravel app code exists.

Required:

- `composer validate`;
- dependency install/build checks;
- starter-kit tests pass;
- app serves locally;
- login route verified in browser;
- `scripts/validate.sh` updated from planning mode.

### Phase 2: First Domain Slice

Required:

- Pest tests for status calculation;
- owner-scoped access tests;
- private document storage tests;
- blood-test creation and confirmation tests;
- no diagnosis/advice language in the core V1 UI.

### Phase 3: Style And Static Analysis

Required:

- Pint check;
- Larastan/PHPStan at a calibrated level;
- no unexplained baselines or blanket ignores;
- static-analysis failures treated as implementation issues, not noise.

### Phase 4: Architecture Tests

Required:

- app/domain boundary tests;
- no public health-data routes;
- private storage boundary;
- no runtime Exa/Firecrawl processing;
- no debug calls;
- no accidental medical-advice language.

### Phase 5: Browser Workflow Proof

Required:

- login;
- PDF-first intake;
- review/confirm;
- trend;
- compare;
- consult/export;
- delete.

Use Pest browser testing if compatible; otherwise use Browser/Chrome validation
until the formal test stack is stable.

### Phase 6: Dependency And CI Guardrails

Required:

- `composer audit`;
- conservative package review;
- GitHub Actions or equivalent running the validation command;
- no package touching private health data without review;
- lockfile changes reviewed deliberately.

## What Not To Copy From Nuno Blindly

- Do not import a strict starter kit wholesale into this project without
  checking compatibility with the existing scaffold and product needs.
- Do not set maximum strictness before the first vertical slice is working.
- Do not add Rector, type coverage, mutation testing, and browser testing all
  at once if it slows down the first safe user workflow.
- Do not treat social posts as stronger evidence than docs, code, and local
  validation.

## Recommendation

Use Nuno's public expertise as a quality compass:

```text
AI can accelerate implementation.
Validation decides whether the implementation is trusted.
```

For this project, the correct transfer is a staged quality ladder:

1. Start with real V1 workflows.
2. Add Pest behavior tests.
3. Add Pint.
4. Add Larastan/PHPStan.
5. Add architecture tests for privacy/product boundaries.
6. Add browser workflow checks.
7. Add dependency/security checks and CI.
8. Add type coverage and mutation testing to critical domain code later.

This gives the project Nuno-level seriousness without turning the first
Laravel implementation slice into tool theatre.
