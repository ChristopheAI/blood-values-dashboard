# Slice Tracker Template

Use this when a slice may span a long session, multiple context windows, or
parallel review. Keep it free of private health data.

## Current Objective

One sentence describing the exact slice being built.

## Source Of Truth

- Issue:
- Product sentence / PRD:
- Spec / ADR:
- Design or data-model reference:
- Known gaps:

## Slice Contract

Use `docs/templates/slice-contract-template.md` when this slice changes product
behavior, workflow, UI, exports, downloads, intake, privacy-sensitive data, or
confirmed-only downstream behavior.

- Outcome:
- Source reference:
- Route or surface:
- Required state:
- Positive proof:
- Negative proof:
- Drift gates:

## Current State

- Branch:
- Baseline commit:
- Latest local commit:
- Dirty files intentionally in scope:
- Dirty files intentionally out of scope:

## Task Board

- [ ] Task 1 (ref: path-or-issue)
- [ ] Task 2 (ref: path-or-issue)
- [ ] Task 3 (ref: path-or-issue)

## Validation Evidence

Focused commands:

```bash

```

Repo intelligence:

```bash
sh scripts/repowise-local-check.sh
```

- Central/risky files:
- Hidden coupling or co-change notes:
- Governing ADR/spec:
- Skipped reason, if unavailable:

Full validator:

```bash
sh scripts/validate.sh
```

Browser/manual QA:

- Matching surface:
- Route or command:
- Account/data scenario:
- Observed proof:
- Must not appear/leak:

## Privacy And Product Boundaries

- [ ] Confirmed-only downstream preserved.
- [ ] Owner scope enforced server-side.
- [ ] No private PDFs, biomarker values, notes, exports, account data, or source
  documents sent to external services.
- [ ] No diagnosis, treatment, advice, urgency, scoring, or recommendation copy.

## Resume Point

Write the next safest action for a fresh Codex context.

## Open Questions

- None, or list the precise blocker.
