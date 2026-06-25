# Slice Contract Template

Use this before implementation starts when a slice changes product behavior,
workflow, UI, exports, downloads, intake, privacy-sensitive data, or
confirmed-only downstream behavior.

The contract is the small proof card for the slice. It joins the product
outcome to the exact surface where a future agent, reviewer, or owner can see
whether the work is true.

Keep it free of private health data.

## Outcome

One sentence describing what must become true for the owner.

## Source Reference

- Issue:
- PRD/spec/ADR:
- Design/data-model reference:
- Known gap:

Use repo-relative paths and issue numbers. If a needed source is missing, mark
it `[NEEDS-DESIGN]` or `[NEEDS-DECISION]` instead of inventing the missing
requirement during implementation.

## Route Or Surface

- Matching surface: browser / route / export / download / console command /
  GitHub issue form / other
- Route or command:
- Account or synthetic data scenario:

## Required State

Name the state that must exist and be checked.

Examples:

- owned selected blood tests;
- confirmed values only;
- draft review rows present but excluded downstream;
- empty state;
- permission denial;
- failed parse;
- source document visible through owner-authorized access.

## Positive Proof

What must be visible, returned, exported, stored, or tested when the slice works?

- Automated proof:
- Browser/manual proof:
- Expected counts or output:

## Repo Intelligence Check

Use when the slice touches shared code, privacy-sensitive flows, downstream
confirmed-only behavior, exports, downloads, routes, or broad refactors.

- Repowise/codegraph check run or explicitly skipped:
- Central/risky files:
- Hidden coupling or co-change notes:
- Governing ADR/spec found:
- Tests or browser checks added because of this signal:

## Negative Proof

What must not appear, leak, change, or be broadened?

- No drafts in downstream surfaces:
- No foreign user data:
- No private data in URL, logs, screenshots, AI tools, Exa, Firecrawl, OCR, or
  external services:
- No diagnosis, advice, urgency, scoring, supplement, diet, training, or extra
  testing recommendation copy:
- No unrelated route, schema, package, or UI behavior change:

## Drift Gates

- [ ] Every changed behavior maps back to this contract or a named source.
- [ ] Extra behavior found during implementation is moved to a follow-up source
  or explicitly approved before being built.
- [ ] If live browser behavior contradicts tests, live behavior becomes the next
  bug report and a synthetic regression is written before parser/product code
  changes.
- [ ] The handoff records proof for each positive and negative check.
