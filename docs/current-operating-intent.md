# Current Operating Intent

Date: 2026-06-21
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
  - #16 `Build consult pack as downstream print/export output` - next product
    reconciliation/hardening slice.
  - #17 `Prove full multi-blood-test follow-up flow in browser QA` - end-to-end
    proof gate after #14/#15/#16 are ready.
- Latest code checkpoint before this intent-layer hardening:
  `c6dff10 fix: order blood tests by collection date`.
- PR CI on 2026-06-21: two `validate` checks completed successfully.
- Last local full validation before this docs slice: `sh scripts/validate.sh`
  passed after the recency-ordering fix.
- Local app route used for browser QA: `http://127.0.0.1:8000`.
- Known unrelated untracked files: `.omo/`, `bloed-overzicht.tsx`.
- Do not merge this branch. The owner reviews code and live-verifies the flow.

## Completed Shape On This Branch

- V2 clean-by-default local extraction and confidence-gated auto-confirm are
  implemented without OCR, runtime AI, external processing, or new package
  scope.
- Upload-first intake lands on the blood-test detail/review route with progress,
  confirmed values, and a compact draft review strip.
- Patient-friendly "Je bloedresultaten" overview is reused on dashboard and
  individual blood-test detail pages.
- `/blood-tests/{id}` is the canonical workspace for one owned blood draw:
  source documents, confirmed values, comparable previous changes, context
  notes, drafts, and management fallback stay together.
- Detail views use the route blood test, not the newest upload.
- Dashboard and blood-test lists order by most recent `test_date`, with null
  dates last, instead of raw creation order.
- Consult Pack code already exists in `app/Domain/Consult`,
  `app/Http/Controllers/ConsultOverview`, `resources/views/consult-overview`,
  and `tests/Feature/ConsultOverview`.

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

Finish the intent-layer hardening first:

- keep this file current;
- keep `docs/architecture.md` aligned with branch reality;
- close or update stale slice trackers after the implementation lands;
- keep GitHub issues as product/build todos, not as replacements for local
  `AGENTS.md` intent layers.

After that, the next product work should be issue #16: Consult Pack
reconciliation and hardening, not a blind rebuild. Consult Pack already exists,
so start by comparing the PRD/issue acceptance criteria with current code and
tests.

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
- `.omo/` and `bloed-overzicht.tsx` stay untouched unless explicitly requested.
- Issue #17 is used as the full browser proof gate after the relevant product
  slices are ready.
