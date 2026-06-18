# Validation Protocol

Use this when validating, resuming, or continuing the project from a marker,
checkpoint, task plan, or previous Codex thread.

## Principle

Validation must prove the intended thing, not just run whatever checks are
available.

For this project, generic success is not enough. The check must prove the
current phase:

- planning phase: control-plane files exist and no Laravel scaffold exists;
- implementation phase: Laravel tests/build/smoke checks prove the active slice;
- closeout phase: V1 scope is satisfied and residual gaps are named.

Planning validation also checks that evidence, ADRs, and the product-system
check exist. This prevents architecture decisions from living only in chat.

## Marker Resolution

When the user says "validate from `<marker>`", classify the marker before
running checks.

Possible marker types:

- git commit or ref;
- GitHub branch, pull request, issue, or workflow run;
- Codex thread, session, task, or run ID;
- documented marker in `docs/session-handoff.md`;
- plan task in `docs/superpowers/plans/`;
- plain text note with no machine-readable meaning.

Resolution steps:

1. Check `docs/session-handoff.md`.
2. Check `docs/superpowers/plans/`.
3. Check whether the marker is a local git commit or ref.
4. Check whether a GitHub remote exists when remote context is relevant.
5. If the marker still cannot be resolved, say that clearly before running
   generic checks.

Do not silently treat an unresolved UUID or chat phrase as a git commit.

## Validation Levels

### Planning Baseline

Use before the first implementation commit.

```bash
sh scripts/validate.sh
git status --short --branch
```

This should prove:

- required planning docs exist;
- no Laravel app scaffold exists yet;
- whitespace check passes;
- known next action is documented in `docs/session-handoff.md`.

### Implementation Slice

Use after Laravel is scaffolded and `scripts/validate.sh` has been upgraded.

Expected command:

```bash
sh scripts/validate.sh
```

At minimum, implementation validation should run:

- PHP tests;
- frontend build;
- whitespace checks;
- any browser or smoke check required by the active slice.

Implementation validation should follow the staged Laravel quality ladder in
`docs/adr/0007-use-staged-laravel-quality-ladder.md`:

1. scaffold integrity;
2. domain and feature behavior tests;
3. formatting with Pint or equivalent;
4. static analysis with Larastan/PHPStan;
5. architecture tests for privacy and product boundaries;
6. browser workflow proof;
7. dependency/security checks and CI;
8. type coverage and mutation testing for critical domain rules when stable.

The ladder is staged. Do not block the first usable vertical slice by requiring
every strict tool immediately, but do not call V1 complete until validation
proves the sensitive workflows and boundaries.

Critical implementation checks should eventually cover:

- owner-scoped health records;
- private document storage;
- PDF-first intake and review/confirmation;
- status calculation;
- trend and compare views;
- export/delete behavior;
- no diagnosis, treatment, supplement, or medical-advice language;
- no runtime Exa or Firecrawl processing of private health data;
- no runtime AI agent mutating, confirming, exporting, or interpreting private
  health records without proposal-only human approval;
- dependency/security review for packages touching files, auth, exports,
  background jobs, external APIs, or health data.
- Livewire public properties and action parameters treated as untrusted browser
  input, with server-side validation and authorization before mutations,
  downloads, exports, deletion, or confirmation;
- lab PDFs stored on private disks with generated storage names, sanitized
  display filenames, owner-authorized download routes, and upload/download tests
  using fake files/disks.

The first PDF-intake implementation must use
`docs/testing/pdf-first-intake-test-conversion.md` as the test contract. That
document maps the engineering source radar to concrete future Pest/Livewire
tests and explains why each test exists.

### Continuation Check

Use when resuming from another thread.

Read:

- `README.md`;
- `AGENTS.md`;
- `docs/session-handoff.md`;
- `docs/project-brief.md`;
- `docs/v1-spec.md`;
- `docs/product-system-check.md`;
- ADRs under `docs/adr/`;
- active plan under `docs/superpowers/plans/`;
- latest git status/log.

Then report:

```text
Marker:
Resolved as:
Scope validated:
Commands/checks:
Result:
Gaps:
Next action:
```

### V1 Closeout

Use when deciding whether V1 is complete.

Check:

- `docs/project-brief.md`;
- `docs/v1-spec.md`;
- active implementation plan;
- `sh scripts/validate.sh` output;
- known gaps in `docs/session-handoff.md`;
- whether `docs/reflection.md` should be created from the reflection template.

## What Not To Do

- Do not claim a marker was validated if the marker was never resolved.
- Do not substitute generic checks for continuation validation without saying so.
- Do not rely on chat-only context when repository handoff should carry context.
- Do not treat planning validation as implementation validation.
- Do not scaffold Laravel just to make a validation script pass.
