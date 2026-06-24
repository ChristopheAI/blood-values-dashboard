# ADR-0012: Use Local Repo Intelligence For Agent Workflows

## Status

Accepted

## Context

AI-assisted work on this app repeatedly depends on knowing more than "what file
contains what". A useful agent needs to know route-to-handler flow, central
files, co-change risk, stale decisions, hidden coupling, test gaps, and whether
a change touches confirmed-only or owner-scoped behavior.

The Repowise repository is a useful reference for this workflow. It indexes a
codebase into graph, git, documentation, decision, and code-health layers, and
it exposes those layers through CLI and MCP tools. Its PHP/Laravel support
includes Composer PSR-4 resolution, Laravel routes, service providers, and
Eloquent relationship edges.

This project handles private health data. Repository-intelligence tooling can be
useful only when it stays in the development control plane and does not become a
runtime processor, external data processor, medical feature, or source of truth
for private records.

## Decision

Adopt Repowise-style local repo intelligence as an advisory development workflow
for risky slices.

Default posture:

- Local-only and index-only.
- No LLM/generated wiki pages by default.
- No hosted endpoint, PR bot, telemetry, Codex hook, AGENTS rewrite, or managed
  editor-file generation by default.
- No private lab PDFs, biomarker values, context notes, consult exports, account
  data, source documents, screenshots, logs, or storage files may be processed
  by repo-intelligence tooling.
- `.repowise/` is a local cache and must stay out of git.

When Repowise is installed, use the project wrapper:

```bash
sh scripts/repowise-local-check.sh
```

The wrapper forces the safe profile:

```text
telemetry disabled -> index-only init/update -> health -> risk
```

Use the output to ask better questions before touching risky code:

- Which files are central or unhealthy?
- Which files co-change with the target?
- Which route, controller, Livewire component, domain service, model, or view is
  in the blast radius?
- Which ADR/spec governs the change?
- Which tests or browser/manual checks must prove the slice?

Repo-intelligence output is never enough to claim completion. The normal ladder
still applies: read the relevant `AGENTS.md`, inspect code, write focused tests,
run validation, and drive the matching browser/export/console surface.

## Stop Conditions

- Do not run full Repowise documentation generation, LLM providers, hosted MCP,
  telemetry, hooks, AGENTS management, or PR bot integration without a new ADR
  and privacy review.
- Do not send private PDFs, biomarker data, notes, exports, account data, source
  documents, screenshots, logs, or storage files to any external service.
- Do not treat a risk score, health score, generated explanation, graph edge, or
  dead-code report as authoritative without checking code and tests.
- Do not use AGPL-licensed Repowise source code as vendored app code in this
  repository without an explicit license review.

## Evidence

- Source: `https://github.com/repowise-dev/repowise`
  - Claim type: fact
  - Summary: Repowise is a public Python/MCP codebase-intelligence tool with
    graph, git, docs, decisions, code health, risk, distill, and MCP surfaces.

- Source: Repowise docs inspected on 2026-06-23
  - Claim type: fact
  - Summary: Repowise supports index-only init/update, `--no-codex`,
    `--no-agents`, telemetry opt-out, code health, change risk, MCP tools, and
    PHP/Laravel framework edges.

- Source: `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
  - Claim type: fact
  - Summary: External tools may support public research but must not process
    private health data.

- Source: `docs/adr/0007-use-staged-laravel-quality-ladder.md`
  - Claim type: fact
  - Summary: Running locally or passing one tool is not enough; the project uses
    staged validation, focused tests, and browser proof.

- Source: `docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md`
  - Claim type: fact
  - Summary: PDF intake and confirmed-only downstream behavior require local,
    deterministic proof and fresh owner verification before merge.

## Considered Options

- Ignore repo-intelligence tooling.
- Install and run Repowise with default interactive behavior.
- Use hosted/LLM-backed Repowise docs for this repository.
- Adopt only the local, index-only, advisory parts.

## Decision Drivers

- The project benefits from route-aware and history-aware codebase context.
- Private health data cannot be exposed to external processors.
- Tooling must reduce agent mistakes without replacing source review.
- The active quality ladder already requires tests, browser proof, privacy
  checks, and handoff evidence.
- AGPL source cannot be copied into the app casually.

## Consequences

- Risky slices should include a repo-intelligence note in the Slice Contract or
  Definition of Done when the tool is available.
- `.repowise/` remains local and ignored.
- `scripts/repowise-local-check.sh` is the only approved project wrapper for
  Repowise until a later ADR expands the boundary.
- Repowise findings can create follow-up issues or test ideas, but they do not
  modify product scope by themselves.
- If the wrapper is unavailable because Repowise is not installed, the slice can
  proceed with codegraph, `rg`, tests, and browser QA; the missing tool is noted
  as skipped, not treated as a blocker.

## Confidence

Medium

The local/index-only pattern is useful and low risk. The exact value of Repowise
on this Laravel codebase still needs a first local run and comparison against
our existing codegraph, tests, and browser QA evidence.
