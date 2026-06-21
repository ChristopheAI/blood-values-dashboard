# Agent Efficiency Playbook

Date: 2026-06-21
Purpose: reduce ambiguity and repeated discovery for future Codex runs.

## Core Rule

Run one active slice at a time. A slice is a small buildable unit with its own
goal, issue, touched files, tests, browser route, and handoff. Do not mix parser
fixes, UI work, docs-only work, and consult/export changes unless they are one
inseparable behavior.

## Start Of Slice Checklist

1. Read `AGENTS.md`.
2. Read the nearest child `AGENTS.md` for every directory you will touch.
3. Read `docs/codex-prd.md`.
4. Read the GitHub issue for the slice.
5. Run `git status --short --branch`.
6. Identify unrelated dirty files and name them in the first status update.
7. Confirm the validation commands and browser QA route before coding.

## Issue Shape

Use `.github/ISSUE_TEMPLATE/developer-slice.yml` for product/build work. A good
issue contains:

- one product goal;
- explicit out-of-scope items;
- likely files/areas;
- confirmed-only and owner-scope checks;
- acceptance criteria;
- exact test commands;
- browser-QA routes;
- known dirty files or do-not-touch files.

Intent Layer work is repo infrastructure. Keep it in local `AGENTS.md` files
unless the user explicitly asks for a GitHub tracking issue.

## Current-State Handoff

Use `docs/templates/slice-handoff-template.md` after every substantial slice.
The handoff should record:

- branch;
- baseline commit;
- files changed;
- files intentionally not touched;
- focused test results;
- full validation result;
- browser QA result;
- known risks;
- next recommended issue.

## QA Seed Scenario

Use the synthetic QA scenario when browser or consult/export work needs a stable
dataset:

```bash
php artisan app:seed-blood-test-demo
```

Login:

```text
qa@example.com
password
```

The scenario is synthetic and idempotent. It creates two blood tests, source
document records/files, confirmed values, one draft review row, a pinned
biomarker, context note, and reminder. See `docs/testing/qa-seed-scenario.md`.

## Definition Of Done

Use `docs/templates/definition-of-done.md` and make the slice-specific version
concrete before implementation. For code changes, "done" normally means:

- focused tests pass;
- `sh scripts/validate.sh` passes;
- browser QA has inspected the real route when UI/workflow changed;
- private data did not leave the local project;
- drafts remain out of downstream surfaces;
- owner scope is enforced server-side;
- only relevant files are staged.

## Worktree Hygiene

- Never use `git add .`.
- Keep unrelated untracked files unstaged.
- If a dirty file is unrelated, name it and ignore it.
- If a dirty file affects the current slice, inspect it and work with it.
- Prefer committing completed infrastructure slices before starting product
  slices, so later Codex runs do not have to reason through overlapping work.

## Stop Conditions

Stop and ask one precise question only when:

- the next step would destroy or rewrite user work;
- a private-data boundary is unclear;
- a missing secret or account is required;
- the requested scope conflicts with the PRD or ADRs.

Otherwise, keep moving with the smallest correct slice.
