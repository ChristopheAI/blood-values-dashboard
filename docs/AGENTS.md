# Docs Control Plane

Owns the repository control plane: briefs, specs, ADRs, evidence, research,
validation protocol, reviews, kickoff notes, and handoffs. Docs should compress
decisions and evidence so implementation work does not drift.

## Entry Points

- `current-operating-intent.md` - first "where are we now?" card for a new
  Codex run on the active branch.
- `project-brief.md`, `codex-prd.md`, `v1-spec.md`, `v2-spec.md` - product and
  implementation scope.
- `product-system-check.md` - product-to-system sanity check.
- `adr/*.md` - durable architecture, privacy, and implementation decisions.
- `evidence/source-index.md` and `evidence/*.md` - facts, claims, and validation
  evidence.
- `testing/*.md` and `validation-protocol.md` - test and QA expectations.
- `agent-efficiency-playbook.md` and `templates/*.md` - Codex workflow,
  handoff, and Definition of Done helpers.
- `session-handoff.md` and `codex-*-kickoff.md` - branch/session state.

## Contracts & Invariants

- Use docs to decide scope before code when privacy, data model, workflow, or
  architecture boundaries change.
- ADRs record durable decisions; specs describe required behavior; evidence docs
  separate facts, inferences, hypotheses, and unknowns.
- Public research may use Exa/Firecrawl, but private PDFs, biomarker values,
  notes, exports, account data, and source documents must never leave the local
  project.
- Keep docs aligned with actual code, branch state, tests, and browser proof.
- Do not use competitor or AI-product language to smuggle in diagnosis,
  optimization, optimal ranges, or health coaching.

## Patterns

- For a new slice, move in this order: brief/evidence -> ADR/spec if needed ->
  task plan or GitHub issue -> baseline commit -> build -> verify -> review ->
  handoff.
- Requirements should be outcomes and business rules, not a list of screens.
- When a branch changes CMA extraction, auto-confirm, or intake UX, update
  `codex-v2-clean-autoconfirm-kickoff.md` and related testing docs if behavior
  changed.
- Keep handoff notes concrete: branch, commit, files, commands, browser QA, and
  known risks.
- Update `current-operating-intent.md` after meaningful branch state changes so
  a new agent does not start from stale conversation context.
- Treat Intent Layer work as repo infrastructure. Implement it in local
  `AGENTS.md` files; do not create GitHub product todos unless tracking work is
  explicitly requested.

## Anti-patterns

- Do not let docs become a second source of truth that contradicts tests or live
  browser behavior.
- Do not add broad future scope without an ADR/spec/privacy review.
- Do not describe runtime AI, OCR, provider sync, wearable import, or medical
  advice as active scope unless a later approved spec changes the boundary.
- Do not store private health details in docs, screenshots, logs, or research
  notes.

## Related Context

- Root rules: `../AGENTS.md`
- Domain rules: `../app/Domain/AGENTS.md`
- HTTP rules: `../app/Http/AGENTS.md`
- Model rules: `../app/Models/AGENTS.md`
- Test rules: `../tests/AGENTS.md`
