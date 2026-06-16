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

### Continuation Check

Use when resuming from another thread.

Read:

- `README.md`;
- `AGENTS.md`;
- `docs/session-handoff.md`;
- `docs/project-brief.md`;
- `docs/v1-spec.md`;
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

