# Current Operating Intent

Date: 2026-06-24
Audience: Codex agents entering `codex/v2-clean-autoconfirm`.

This is the first "where are we now?" card. It does not replace `AGENTS.md`,
the PRD, specs, ADRs, tests, or live browser proof. Update it after any
meaningful slice so the next agent starts from the real project state instead
of old conversation memory.

## Read Order

1. `AGENTS.md`
2. This file.
3. `docs/codex-prd.md`.
4. The nearest child `AGENTS.md` for every directory you will touch.
5. The active GitHub issue or slice tracker.
6. The focused tests and browser route named by that issue/tracker.

## Codex Work Mode

The official Codex use-case page maps this project to four active modes:

- understand the bounded codebase area before editing;
- keep docs synchronized with code, PR, and issue reality;
- drive real browser QA on synthetic data before claiming workflow completion;
- use durable goals only when the task has one clear long-running success
  condition.

For this private health-data app, generic Codex workflows are always narrowed by
the local privacy and confirmed-only rules in `AGENTS.md`.

## Product Sentence

The user has multiple blood draws and wants one private place where each blood
test keeps the original PDF, confirmed values, units/ranges, context, and
differences from previous tests, so the user can understand what changed and
prepare compactly for a doctor conversation without the app making medical
claims or encouraging extra testing.

Short pipeline:

```text
PDF in -> blood test -> review -> confirmed values -> timeline/detail ->
comparison -> context -> consult/export.
```

## Current Branch And PR

- Branch: `codex/v2-clean-autoconfirm`.
- Pull request: #11, `[codex] V2 clean auto-confirm and follow-up overview`.
- PR state on 2026-06-21: open draft.
- Active product issues:
  - #14 `Make blood-test detail the canonical follow-up place` - implemented on
    this branch, still open until review/merge.
  - #15 `Add confirmed-only longitudinal changes and trends` - implemented and
    dashboard-hardened on this branch, still open until review/merge.
  - #16 `Build consult pack as downstream print/export output` - next product
    reconciliation/hardening slice.
  - #17 `Prove full multi-blood-test follow-up flow in browser QA` - end-to-end
    proof gate after #14/#15/#16 are ready.
- Latest code checkpoint before this intent-layer hardening:
  `c6dff10 fix: order blood tests by collection date`.
- PR CI on 2026-06-21: two `validate` checks completed successfully.
- Last local full validation on 2026-06-24: `sh scripts/validate.sh` passed
  after adding the synthetic multi-blood-test and owner-isolation Dusk gate for
  #17.
- Latest owner-led live upload gate on 2026-06-24: passed with review remainder.
  A fresh real local PDF upload produced a completed extraction run with 18
  candidates, 16 deterministic auto-confirmed extracted values, 1 extracted draft
  left for review, and 1 source document. The blood test correctly remained in
  `reviewing` state. Browser/database/export checks confirmed the draft stayed out
  of downstream/export counts and generated private storage names were not visible.
  Private biomarker names, values, source snippets, and PDF contents were not
  recorded in repo docs.
- Latest local UI/product hardening slice on 2026-06-24: commit `afcfb31`
  `fix: harden consult and navigation copy`
  removed starter-kit/product noise from navigation and the public welcome page,
  aligned visible copy to Dutch across dashboard/intake/detail/context/consult
  surfaces, and made the consult page output-first: attention values, compact
  normal values, trends/timeline, and source documents render above the lower
  selection/configuration form. Hard boundaries stayed intact: confirmed-only
  and owner scope remain in domain/controller code, sensitive consult text stays
  POST/CSRF-only, consult questions are excluded from CSV hidden inputs and GET
  URLs, CSV formula escaping remains covered, and no medical advice, diagnosis,
  urgency, runtime AI/OCR, or external processing was added.
- Latest validation for that slice on 2026-06-24: `sh scripts/validate.sh`
  passed locally, including frontend build, Pint, PHPStan, 248 Pest tests, 3 Dusk
  browser smoke tests, and whitespace checks. In-app browser QA covered `/`,
  `/dashboard`, `/blood-tests/3`, consult opened from detail, and mobile consult
  at 390x844 with no starter-kit noise, forbidden medical copy, storage-path
  leak, or horizontal overflow.
- Latest user-journey hardening on 2026-07-10: account deletion invokes the
  health-data/PDF cleanup boundary and purges database-backed sessions; exports
  exclude source snippets; deletion and comparison flows have server-side
  confirmation/ownership checks; and Dutch interface/validation/auth copy is
  the default. A dashboard with both confirmed and draft rows keeps its
  confirmed-only consult path available while explicitly warning that drafts
  remain outside downstream use.
- Local app route used for browser QA: `http://127.0.0.1:8000`.
- Known unrelated local artifacts: ignored `.codex/` and `.omo/`; scratch
  `bloed-overzicht.tsx` should stay outside this repo.
- Do not merge this branch. ADR-0011 live verification passed on 2026-06-24 with
  review remainder, but owner code review still remains before merge.

## Completed Shape On This Branch

- V2 clean-by-default local extraction and confidence-gated auto-confirm are
  implemented without OCR, runtime AI, external processing, or new package
  scope.
- ADR-0011 is accepted for the reviewed local CMA trust policy after synthetic
  tests, automated browser/feature checks, full validation, and the 2026-06-24
  owner-led live upload gate. Acceptance means "passed with review remainder",
  not "auto-confirm every row" and not approval to lower thresholds.
- Upload-first intake lands on the blood-test detail/review route with progress,
  confirmed values, and a compact draft review strip.
- Patient-friendly "Je bloedresultaten" overview is reused on dashboard and
  individual blood-test detail pages.
- `/blood-tests/{id}` is the canonical workspace for one owned blood draw:
  source documents, confirmed values, comparable previous changes, context
  notes, drafts, and management fallback stay together.
- Dashboard upload summaries use the shared confirmed-only
  `BuildLongitudinalChanges` domain builder instead of separate trend queries.
- Detail views use the route blood test, not the newest upload.
- Dashboard and blood-test lists order by most recent `test_date`, with null
  dates last, instead of raw creation order.
- Consult Pack code already exists in `app/Domain/Consult`,
  `app/Http/Controllers/ConsultOverview`, `resources/views/consult-overview`,
  and `tests/Feature/ConsultOverview`.
- Issue #17 now has an automated synthetic Dusk gate covering dashboard,
  blood-test list, older/current detail pages, compare, consult CSV export,
  draft exclusion, source-document path hiding, owner-isolation route/download/
  export checks, and mobile/desktop consult form smoke.

## Hard Invariants

- `confirmed_at` is the downstream trust gate.
- Dashboard, status, history, compare, consult, export, and trends use confirmed
  values only.
- Owner scoping is enforced server-side for routes, Livewire actions,
  downloads, exports, deletion, selected IDs, and query builders.
- Private lab PDFs, biomarker values, context notes, consult exports, account
  data, symptoms, medication notes, source snippets, and private source
  documents never go to AI tools, Exa, Firecrawl, OCR services, logs,
  screenshots, or external processors.
- Source documents and structured biomarker values stay separate.
- Use `unknown` when range, unit, value, or comparison basis is not trustworthy.
- The app describes personal tracking data. It does not diagnose, advise,
  prescribe, score health, imply urgency, recommend supplements, or encourage
  extra testing.

## Active Next Move

Intent-layer hardening now records the live gate. The next product work should be
issue #16: Consult Pack
reconciliation and hardening, not a blind rebuild. Issue #15 is implemented on
this branch, and Consult Pack already exists, so start by comparing the
PRD/issue acceptance criteria with current code and tests.

Before changing consult code, read:

- `docs/codex-prd.md#phase-4-consult-pack`;
- GitHub issue #16;
- `app/Domain/Consult/AGENTS.md`;
- `app/Http/AGENTS.md`;
- `resources/views/consult-overview/AGENTS.md`;
- `tests/AGENTS.md`;
- `tests/Feature/ConsultOverview/ConsultPackTest.php`;
- `tests/Feature/ConsultOverview/ConsultOverviewPrivacyTest.php`.

Tests-first wedge for the next consult slice:

```text
One or more selected owned blood tests produce a print/export-friendly consult
view with attention values first, compact normal values, comparable changes,
source documents, selected context, and no medical claims or private-data leaks.
```

## Done Means

- The matching source of truth is updated before code.
- Focused tests prove the behavior or boundary.
- `sh scripts/validate.sh` passes for code changes.
- Browser QA drives the matching route when UI/workflow changes.
- Only relevant files are staged.
- Ignored local agent artifacts stay out of Git, and scratch health UI files stay
  outside this repo unless explicitly requested.
- Issue #17 is used as the full browser proof gate after the relevant product
  slices are ready.
