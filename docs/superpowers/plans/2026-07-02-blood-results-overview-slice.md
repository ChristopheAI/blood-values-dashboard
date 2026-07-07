# Blood Results Overview Slice Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the confirmed-only Blood Results Overview surface from ADR-0013 and `docs/blood-results-overview-spec.md`: all confirmed biomarker values of the owner, grouped as summary + attention (`low`/`high`) + compact `normal`/`unknown` rows.

**Architecture:** Keep the existing Laravel/Livewire boundaries. A new domain builder derives owner-scoped, confirmed-only overview state from `BiomarkerResult::confirmedForUser`; a class-based Livewire component orchestrates only. No trust or grouping logic in Blade.

**Tech Stack:** Laravel, class-based Livewire, Blade, Flux UI components, Pest unit + feature tests, full QA through `scripts/validate.sh`.

---

## Status (2026-07-07)

Tasks 1–4 are substantively complete on `feat/blood-results-overview`, with
recorded deviations from the letter of this plan (see the dated amendments in
`docs/blood-results-overview-spec.md` and the deviations log in
`docs/session-handoff.md`): the component/view/test names use
`ConfirmedBiomarkerOverview`, builder assertions live in
`tests/Feature/BloodTests/ConfirmedBiomarkerOverviewTest.php` instead of a
separate unit-test file, and the reading model evolved to one dated row per
biomarker. `sh scripts/validate.sh` passed in full on 2026-07-06 (Vite build,
Pint, PHPStan, 319 Pest tests, 3 Dusk smoke tests).

Browser QA on the seeded scenario passed on 2026-07-07 (Task 4 Step 3):
summary counts reconcile with the seeded confirmed values (4 biomarkers from
6 measurements, 0/1/2/1 per status), the attention card renders the full
reading model (value+reference anchor, beyond sentence, range bar, dated
history), the detection-limit row explains itself, the seeded TSH draft and
storage paths are nowhere in the page, and there is no horizontal overflow.
Dark-scheme QA is not applicable: commit bc70d59 deliberately removed dark
mode app-wide pending the UX-audit #54 checkpoint, so the view's dark:
variants are currently unreachable.

Tasks 5–6 closed on 2026-07-07: review and ratification were owner-delegated
to the working session (multi-agent code review with all findings fixed or
refuted, plus the browser QA above), ADR-0013 moved to Accepted with the
validation evidence, and the slice outcome is recorded in
`docs/session-handoff.md`. The checkboxes below are left as the execution
record they are.

---

## Scope

This is a read-only overview slice.

In scope:

- `app/Domain/Dashboard/BuildBloodResultsOverview` with unit tests.
- Class-based Livewire component + Blade view under
  `resources/views/livewire/blood-tests/`.
- Feature tests for confirmed-only behavior, tenant isolation, and absence of
  medical copy.
- Dutch operational copy per the spec's copy rules.

Out of scope:

- Parser or intake changes.
- Write actions on the overview.
- Trends, comparison, or thematic/category grouping.
- Dashboard restructuring; the existing dashboard digest stays as is.
- Runtime AI, OCR, external processing, new packages.

## File Structure

- Create `app/Domain/Dashboard/BuildBloodResultsOverview.php`
  - Query through `BiomarkerResult::confirmedForUser($user->id)` only.
  - Map to view rows: name, value (display string), unit, reference min/max,
    reference unit, status.
  - Group into `summary` (counts per status), `attention` (`low`/`high`),
    `normal`, `unknown`.
  - Cast the decimal-as-string `value` to float for status derivation via
    `DetermineBiomarkerStatus` when the persisted status is absent or
    untrusted; keep the original string for display.

- Create `tests/Unit/BuildBloodResultsOverviewTest.php`
  - Grouping, counts, casting, status fallback, `unknown` on missing or
    mismatched inputs.

- Create `app/Livewire/BloodTests/BloodResultsOverview.php`
  - Inject/resolve the builder; expose one structured payload; no domain logic.

- Create `resources/views/livewire/blood-tests/blood-results-overview.blade.php`
  - Render per `DESIGN.md` section 5: summary panel, amber featured attention
    card with one getallenlijn, compact `normal`/`unknown` rows, one green
    good-area, borders-first, motion none, dark mode, status labels in words.

- Modify `routes/web.php` (authenticated group)
  - Register the overview route in the blood-tests area.

- Create `tests/Feature/BloodTests/BloodResultsOverviewTest.php`
  - Confirmed-only, tenant isolation, and copy boundary tests.

- Read before editing: `app/Livewire/AGENTS.md`, `app/Domain/AGENTS.md`,
  `resources/views/AGENTS.md`, `tests/AGENTS.md` (intent layer rule).

## Language Contract

- "Overzicht" means organizational presentation of confirmed values, not
  assessment.
- "Aandacht" remains a range/status description only, never urgency.
- Status words are `laag`, `hoog`, `normaal`, `onbekend`, framed as "status op
  basis van ingevoerde referentierange".
- No "diagnose", "behandeling", "advies", "risico", "gezondheidsscore", or
  meaning-of-marker explanations.

## Task 1: Builder And Unit Tests

**Files:**

- Create: `app/Domain/Dashboard/BuildBloodResultsOverview.php`
- Create: `tests/Unit/BuildBloodResultsOverviewTest.php`

- [ ] **Step 1: Write failing unit tests**

Cover at minimum:

- confirmed `low`/`high` rows land in `attention`; `normal` and `unknown` land
  in their own groups; summary counts match;
- a result with `confirmed_at === null` never appears in any group or count;
- a persisted status is used as-is; an empty/invalid persisted status falls
  back to `DetermineBiomarkerStatus` with the row's stored value/unit/range
  fields (not catalog defaults);
- the decimal-as-string `value` is cast to float for derivation and preserved
  as string for display;
- missing or mismatched derivation inputs produce `unknown`.

- [ ] **Step 2: Run focused tests and confirm failure**

```bash
php artisan test tests/Unit/BuildBloodResultsOverviewTest.php
```

- [ ] **Step 3: Implement the builder**

Query only through `BiomarkerResult::confirmedForUser($user->id)`, eager-load
`biomarker` for the `name` label, map, group, and return one array payload.

- [ ] **Step 4: Run focused tests until green**

```bash
php artisan test tests/Unit/BuildBloodResultsOverviewTest.php
```

## Task 2: Livewire Component And Blade View

**Files:**

- Create: `app/Livewire/BloodTests/BloodResultsOverview.php`
- Create: `resources/views/livewire/blood-tests/blood-results-overview.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the class-based component**

Follow the `app/Livewire/BloodTests/ReviewBloodTest.php` pattern: resolve the
builder in `render()`/mount, pass one payload to the view, no queries or
grouping in the component beyond calling the builder.

- [ ] **Step 2: Register the authenticated route**

Place it with the existing blood-tests routes behind `auth`. Record the chosen
path and route name in `docs/blood-results-overview-spec.md` section 9.

- [ ] **Step 3: Build the view per DESIGN.md section 5**

- Summary panel with per-status counts.
- One amber featured attention card (`amber-50`/`amber-950/40` treatment) with
  a single getallenlijn and a one-sentence non-medical takeaway.
- `normal` and `unknown` as compact rows with tabular values and a mini
  reference line when available; one green good-area for the normal group.
- Borders-first, `shadow-xs` at most, motion none, dark mode variants,
  status words visible next to any color treatment.
- Add stable `data-test` selectors for the summary, attention, normal, and
  unknown regions.

- [ ] **Step 4: Verify in the browser**

```bash
php artisan app:seed-blood-test-demo
```

Open the new route as `qa@example.com`, check both color schemes, and confirm
no draft values, no storage paths, and no medical copy are visible.

## Task 3: Feature Tests

**Files:**

- Create: `tests/Feature/BloodTests/BloodResultsOverviewTest.php`

- [ ] **Step 1: Confirmed-only tests**

An extracted draft (`entry_source` extracted, `confirmed_at` null) with a
distinctive name and value must not appear in the page body, and group counts
must not include it.

- [ ] **Step 2: Tenant isolation tests**

User A's page never contains user B's biomarker names or values; the route
requires authentication.

- [ ] **Step 3: Copy boundary tests**

Assert status words render (`laag`, `hoog`, `normaal`, `onbekend`) and that
forbidden terms from the spec's copy rules do not; keep assertions aligned
with `MedicalCopyBoundaryTest` so both stay green together.

- [ ] **Step 4: Run focused tests**

```bash
php artisan test tests/Feature/BloodTests/BloodResultsOverviewTest.php
php artisan test tests/Feature/Architecture
```

## Task 4: Full Validation

- [ ] **Step 1: Build assets**

```bash
npm run build
```

- [ ] **Step 2: Run the full validator**

```bash
sh scripts/validate.sh
```

Expected: exits 0, including `MedicalCopyBoundaryTest`, `PrivacyBoundaryTest`,
and `PackageBoundaryTest`.

- [ ] **Step 3: Browser QA pass**

Re-check the seeded scenario: summary counts match seeded confirmed values,
drafts stay invisible, dark mode holds, no horizontal overflow.

## Task 5: Review

- [ ] **Step 1: Self-review against the checklist below**
- [ ] **Step 2: Owner review of the surface and copy before any merge**

Commit style: terse Conventional Commits; stage only touched files, never
`git add .`.

## Task 6: Handoff

- [ ] **Step 1: Update `docs/blood-results-overview-spec.md` section 9** with
  the decided route and ordering.
- [ ] **Step 2: Record the slice outcome** in the session handoff doc and, if
  accepted, move ADR-0013 from Proposed to Accepted with validation evidence.

## Self-Review Checklist

- Confirmed-only: every row comes through `confirmedForUser`; drafts leak
  nowhere, including counts.
- Owner scope: enforced server-side by the scope, proven by feature tests.
- Medical boundary: status words only, reference-range framing, no
  interpretation, urgency, advice, or scoring.
- Architecture: grouping/status logic in the builder, Livewire orchestration
  only, nothing trust-related in Blade.
- Design: matches DESIGN.md section 5, dark mode included, motion none.
- Validation: focused tests, architecture tests, `npm run build`,
  `sh scripts/validate.sh`, and browser QA all pass before handoff.
