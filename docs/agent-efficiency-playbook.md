# Agent Efficiency Playbook

Date: 2026-06-21
Purpose: reduce ambiguity and repeated discovery for future Codex runs.

## Core Rule

Run one active slice at a time. A slice is a small buildable unit with its own
goal, issue, touched files, tests, browser route, and handoff. Do not mix parser
fixes, UI work, docs-only work, and consult/export changes unless they are one
inseparable behavior.

## Skool-Derived Operating Loop

Use this loop for every non-trivial slice:

```text
product sentence -> PRD/spec or ADR if needed -> GitHub issue -> source-linked
tasks -> explore -> plan -> code -> test -> browser QA -> commit -> handoff
```

Do not let implementation start from a vague screen idea. The source of truth
must be a product outcome, PRD/spec, ADR, issue, or explicit design gap. If a
task needs a source but none exists, mark the gap instead of inventing the
missing design or requirement.

## Official Codex Use-Case Mapping

Source: `https://developers.openai.com/codex/use-cases`

Map official Codex use cases to this repo in the following way:

- Understand large codebases: before a non-trivial edit, trace the request flow,
  owner boundaries, validation, domain layer, and next files to read. This maps
  to `AGENTS.md`, child `AGENTS.md`, `docs/architecture.md`, and codegraph or
  targeted source reads.
- Keep documentation up-to-date: when code, issues, PR state, or branch reality
  changes, update only the docs that must change and include verification
  evidence. This maps to `docs/current-operating-intent.md`, `docs/architecture.md`,
  slice trackers, and handoff notes.
- QA your app: for UI/workflow changes, drive the real route with synthetic data
  and report expected result, actual result, severity, and repro steps for any
  bug. This maps to issue #17 and the browser QA gate.
- Follow a goal: use a durable goal only for long-running work with a clear
  success condition and validation loop. Do not use it to blur unrelated slices
  together.
- Review pull requests or local diffs: before handoff, inspect the diff for
  regressions, missing tests, privacy leaks, confirmed-only leaks, and stale
  docs.

The repo-specific privacy rule overrides generic Codex use cases: do not use
AI tools, Exa, Firecrawl, OCR services, screenshots, logs, or external services
on private PDFs, biomarker values, notes, consult exports, account data, or
source documents.

## Reliable Codex Build Loop

Source: `/Users/christophe/Projects/Codextheverythingapp/codex-playlist-notes.md`

The Codex guide is useful for this Laravel project as a reliability pattern, not
as product scope. The product remains the private blood-values app. The guide's
usable signal is: Codex becomes reliable when work lives in local source files,
permissions are explicit, repeated work becomes SOPs/skills, and every change
leaves proof.

Use this loop for every build slice:

```text
source of truth -> bounded task -> permission boundary -> first failing proof ->
smallest code change -> review own diff -> focused tests -> full validator when
code changed -> browser route/export proof -> issue/PR/handoff evidence
```

Concrete actions for this repo:

| Action | Why it matters here | Failure prevented |
| --- | --- | --- |
| Start from `AGENTS.md`, nearest child `AGENTS.md`, PRD, ADR, and issue. | Codex should come to the repo context instead of inventing from chat. | Generic health-dashboard drift and missed privacy rules. |
| Name the exact owned route, selected IDs, or data boundary before coding. | Blood data is owner-scoped and route-specific. | Foreign-data leaks, newest-upload mistakes, and widened queries. |
| Write the first failing test before behavior changes. | Confirmed-only and privacy rules need executable proof. | Green code that silently includes drafts or wrong tests. |
| Keep permissions explicit. | Private PDFs, biomarker values, notes, and exports are sensitive. | AI/OCR/Exa/Firecrawl/log/screenshot leakage. |
| Review the diff before handoff. | AI code can pass tests while adding scope or stale docs. | Hidden broad rewrites, weak copy, and unrelated churn. |
| Drive the matching surface. | The user experiences routes, exports, downloads, and browser flows, not just tests. | Claiming completion from green tests while the app is wrong. |
| Promote recurring checks to SOPs, templates, or child `AGENTS.md`. | Repeated friction should become local context. | Rediscovering the same rule every run. |

Do not convert Codex-guide ideas into app features unless a product issue or ADR
explicitly says so. "Codex as everything app" belongs to the agent/workflow
layer. This Laravel app's feature layer stays blood-test understanding:
original PDF, confirmed values, context, comparisons, and consult preparation.

## Discovery And Wedge Gate

Before a slice becomes implementation work, name the smallest wedge that proves
the product idea for the user. The wedge is not the smallest UI and not the
fastest technical shortcut. It is the smallest observable outcome that makes
the product more true.

For this app, the durable wedge is:

```text
one owned blood test can be understood from its original source document,
confirmed values, context, and honest changes versus previous owned tests
without diagnosis or advice.
```

Reject work that does not strengthen this wedge unless it is explicit
infrastructure, privacy hardening, or validation support. This prevents the app
from drifting into a generic health dashboard, a pretty browse surface, or an AI
interpretation product.

## Requirements Are Outcome Contracts

Treat requirements as build-agnostic outcomes and constraints, not as screens.
A requirement should say what must become true for the user no matter how the UI
is designed.

Good requirement shape:

- user-owned data and route;
- observable outcome;
- hard constraints;
- measurable acceptance;
- negative proof for leaks, scope creep, and unsafe copy.

Weak requirement shape:

- "add a card";
- "make a dashboard";
- "show insights";
- "improve UX";
- "users should love it".

When a requested screen does not map to an outcome, stop and reduce it to the
outcome before coding. For #14, the outcome is not "add more blocks to the
detail page"; it is "one blood draw can be understood in one place."

## Build-Ready Spec Bundle

Before orchestration or implementation, the slice needs a compact build-ready
bundle:

- product outcome;
- constraints and non-goals;
- source references;
- domain terms;
- likely files;
- first failing tests;
- acceptance matrix with positive and negative proof;
- matching browser route or command;
- privacy and medical-copy boundaries.

If the bundle is missing design detail for a user-facing flow, tag the work as
`[NEEDS-DESIGN]`. If it is missing a product or architecture decision, tag it as
`[NEEDS-DECISION]`. Do not let an agent fill those gaps from taste.

## Slice Contract

Use `docs/templates/slice-contract-template.md` as the proof card for any slice
that changes product behavior, workflow, UI, exports, downloads, intake,
privacy-sensitive data, or confirmed-only downstream behavior.

The contract joins the outcome to the observable surface before coding:

```text
outcome -> source reference -> route/surface -> required state ->
positive proof -> negative proof -> drift gates
```

This is not a second PRD. Keep it small. Its job is to stop agents from
building from a vague idea or claiming "done" from green tests alone. If a
changed behavior cannot map back to the contract or a named source, treat it as
scope drift and move it to a follow-up source instead of merging it silently.

## Local Repo Intelligence

Source: `docs/adr/0012-use-local-repo-intelligence-for-agent-workflows.md`

Adopt the useful Repowise pattern as a local advisory layer: graph context, git
history, code health, change risk, decision history, and route-aware blast
radius before risky changes.

Approved wrapper:

```bash
sh scripts/repowise-local-check.sh
```

This wrapper is the safe profile:

```text
telemetry disabled -> index-only init/update -> health -> risk
```

Use it before touching shared, privacy-sensitive, or downstream behavior when
Repowise is available. Bring the results back into the slice contract as a short
note: central files, risky files, suspected hidden coupling, governing ADRs, and
tests or browser checks that must prove the work.

Hard limits:

- Repo intelligence is development tooling, not product scope.
- It must not process private PDFs, biomarker values, notes, exports, account
  data, source documents, screenshots, logs, or storage files.
- It must not generate LLM docs, install hooks, rewrite `AGENTS.md`, enable
  telemetry, or use hosted services without a new ADR and privacy review.
- Its output is advisory only; code inspection, tests, validation, and browser
  proof remain the trust gates.

## Senior Engineer Filter

Source: `docs/research/2026-06-23-senior-engineer-handbook-transfer.md`

The useful signal from the senior-engineer handbook is not the catalog of links.
It is the pattern behind the catalog: senior work combines communication, system
design, reliability, product judgement, writing, and ownership of tradeoffs.

Use this filter before starting or handing off a non-trivial slice:

```text
communicate the outcome -> map the system -> name the tradeoff ->
prove the behavior -> leave the next engineer unblocked
```

Checklist:

- Outcome: can the slice be explained in one plain-language sentence?
- System: are the route, owner boundary, domain/model boundary, downstream
  surfaces, and failure state named?
- Tradeoff: is the chosen scope smaller and safer than the tempting broad
  version?
- Proof: do tests and browser/manual checks cover both expected behavior and
  negative privacy/confirmed-only behavior?
- Handoff: can a fresh engineer resume from the docs, issue, diff, commands,
  and known risks without reading the whole conversation?

If the answer is weak, tighten the Slice Contract or source document before
coding.

## Top Engineer Habits Filter

Source: `docs/research/2026-06-23-engineers-codex-top-engineer-habits.md`

Use this filter during implementation and review:

```text
human-readable -> local-standard -> simple -> predictable -> reviewed ->
exception documented
```

Checklist:

- Human-readable: route, model, domain service, and test intent are clear
  without reading the conversation.
- Local-standard: existing Laravel, Livewire, Pest, Blade, domain-service, and
  docs patterns were followed before adding a new abstraction.
- Simple: the change is the smallest correct one that preserves privacy,
  confirmed-only behavior, owner scope, and source-document separation.
- Predictable: positive and negative paths are covered by tests and the matching
  browser/manual surface where needed.
- Reviewed: the diff was inspected for scope creep, private-data leaks, stale
  docs, weak tests, and unrelated churn.
- Exception documented: if a project rule was bent deliberately, the reason is
  recorded in an ADR, spec, slice tracker, or handoff.

## Context Budget

Treat context pressure as a quality risk. Keep long work anchored in durable
repo artifacts instead of conversation memory:

- GitHub issues hold the next buildable todos.
- `AGENTS.md` files hold intent-layer rules for what lives where.
- PRDs/specs/ADRs hold durable product and architecture decisions.
- `docs/templates/slice-tracker-template.md` holds resumable progress for long
  slices.
- Handoff docs hold the exact branch state, commands, browser routes, and risks.

When context gets large, update the tracker or issue with current state before
continuing. Do not rush implementation to finish before the context window runs
out.

## Agent Strengthening Loop

Source: `docs/research/2026-06-21-x-agent-workflow-signals.md`

After substantial slices, review whether agent friction came from a missing
repo rule, stale docs, unclear issue acceptance, noisy output, or weak browser
QA instructions. Only patch durable artifacts when the lesson is likely to
recur.

Use this improvement loop:

```text
friction observed -> source artifact identified -> smallest rule/doc patch ->
next slice uses the rule -> keep or remove based on evidence
```

For this repo, the agent's context layer is local and explicit: `AGENTS.md`,
child `AGENTS.md`, PRD, ADRs, architecture notes, GitHub issues, slice trackers,
and handoffs. Do not replace this with external tools for private health data.

Use bounded subagents for noisy, non-private work only: code mapping, synthetic
browser-QA checklists, test-output triage, issue/PRD reconciliation, and
post-implementation review. The main agent keeps the product decision and
privacy boundary.

Treat every slice like a small distributed workflow: permissions, traceability,
tests, browser route, commit, push, and issue/PR evidence must line up before
handoff.

## Skill Vs Agent Rule

Use a reusable skill when the workflow is short, repeatable, deterministic, and
benefits from recent context. Use a separate agent/subagent when the work needs
a clean slate, can run in parallel, is long-running, or would bloat the main
context.

For this app, subagents may inspect code, docs, tests, and synthetic fixtures.
They must not process private lab PDFs, biomarker data, context notes, consult
exports, account data, or source documents.

Good subagent targets:

- focused security/authorization review;
- test coverage review for a completed slice;
- UI/browser QA checklist execution on synthetic data;
- issue/PRD/docs reconciliation;
- codebase mapping for a bounded module.

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
- source references to PRD/spec/ADR/design/data-model where applicable;
- explicit `[NEEDS-DESIGN]` or `[NEEDS-DECISION]` gaps instead of fabricated
  requirements;
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
- source-of-truth references and unresolved gaps;
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
