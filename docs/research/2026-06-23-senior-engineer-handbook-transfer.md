# Senior Engineer Handbook Transfer

Date: 2026-06-23

## Source

- Repository: `https://github.com/jordan-cutler/path-to-senior-engineer-handbook`
- Owner: `jordan-cutler`
- Default branch inspected: `main`
- Commit inspected: `3a9ad5bf31a8b34c00b341ba2186e6b3fc3da023`
- License: MIT

## What It Is

This repository is a curated resource catalog for reaching senior engineer:
newsletters, books, courses, papers, YouTube channels, podcasts, communities,
LinkedIn profiles, platforms, and tools.

It is not a framework, codebase, methodology, or implementation template.

Useful categories for this project:

- communication, writing, and relationships;
- system design;
- reliability engineering;
- leadership and influence;
- product and UX judgement;
- deliberate learning resources.

## What Not To Copy

Do not copy the catalog into this repository. A long resource list would not
make the Laravel project safer, clearer, or easier to resume.

Do not let generic career-growth material widen the product scope. This remains
a private blood-values app with local PDF-first intake, confirmed-only
downstream behavior, owner scoping, and no medical advice.

Do not treat a paid course, newsletter, or public engineering personality as a
source of product truth.

## Transfer

The useful transfer is an operating filter for non-trivial slices:

```text
communicate the outcome -> map the system -> name the tradeoff ->
prove the behavior -> leave the next engineer unblocked
```

Applied to this Laravel project:

- Communication: every slice should explain the outcome, tradeoff, and proof in
  plain language.
- System design: before coding, name the route, owner boundary, model/domain
  boundary, downstream surfaces, and failure state.
- Reliability: prove the happy path and the negative path; green tests are not
  enough when browser/export/upload behavior is the real surface.
- Product judgement: prefer the smallest wedge that makes the app more true;
  reject generic dashboard polish.
- Writing: handoff should include branch state, changed files, commands, browser
  proof, skipped checks, and residual risk.
- Leadership: make scope and privacy tradeoffs explicit instead of hiding them
  inside implementation choices.

## Adopted Practice

Use the Senior Engineer Filter in `docs/agent-efficiency-playbook.md` before
starting or handing off any slice that changes behavior, architecture, privacy,
exports, intake, or user-visible workflow.

The filter is advisory. It does not replace the Slice Contract, ADRs, tests,
validation, or browser/manual QA.
