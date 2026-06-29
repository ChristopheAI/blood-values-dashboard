# Thematic Biomarker Overview Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show confirmed biomarker values grouped by human theme (e.g. Ontstekingen), with optional neutral descriptions and trend vs previous test — organizational only, no medical advice.

**Architecture:** Add a shared domain builder that groups `BiomarkerResult::confirmedForUser` rows by owner-scoped `BiomarkerCategory`, reuses `BuildLongitudinalChanges` for trend labels, and pulls optional marker copy from a local audited reference map (not runtime AI). First surface: consult pack; second: dashboard latest digest. Categories without confirmed values in scope are omitted.

**Tech Stack:** Laravel domain builders, Blade partials, Pest unit/feature tests, existing `BuildLongitudinalChanges` / `Format` helpers.

---

## 1. Problem statement

Today the app shows biomarkers **flat** (attention / normal / timeline tables). Users think in **themes** (“ontsteking”, “schildklier”, “lipiden”) but must map lab names themselves.

Vitasure’s product-page pattern (theme → markers → one-line description) is **navigation/education**, not diagnosis. We adopt the **structure**, not their claims (“voorspelt risico…”) .

### What exists

| Asset | Status |
| --- | --- |
| `BiomarkerCategory` model (owner-scoped, `name` only) | ✅ DB |
| `Biomarker.biomarker_category_id` | ✅ nullable FK |
| `BuildLongitudinalChanges` + trend labels in dashboard summary | ✅ |
| `confirmedForUser` trust gate | ✅ |
| Category assignment UI | ❌ missing |
| Marker/theme descriptions | ❌ missing |
| Theme-grouped views | ❌ missing |

### What we build

```text
Thema (Ontstekingen)
  → bevestigde markers in scope (selected blood test(s))
  → optionele neutrale beschrijving (local reference)
  → trend t.o.v. vorige test (bestaande longitudinal builder)
```

---

## 2. Scope boundaries

### In scope (this plan)

- Domain builder: `BuildThematicBiomarkerOverview`
- Local reference file for **optional** neutral marker descriptions (NL)
- Consult pack section **Per thema** (+ include flag)
- Dashboard latest-upload digest subsection grouped by theme
- Unit + feature tests (confirmed-only, owner scope, uncategorized bucket)
- Spec touch-up + short ADR for reference-copy boundary
- Default category **seeder helper** for owner (v1-spec category names)

### Out of scope

- Runtime AI/LLM descriptions
- Medical interpretation, risk prediction, “optimal ranges”, advice
- Vitasure-style “if you worry about X, we test Y” marketing copy in app
- Full biomarker catalog CRUD UI (follow-up slice)
- Auto-category during CMA intake (follow-up; manual/seeder first)
- Wearables, test ordering, external APIs
- Replacing attention/normal consult sections (additive, not swap)

---

## 3. Architecture

### 3.1 Data flow

```text
User + scope (BloodTest | list<bloodTestId>)
  → BuildThematicBiomarkerOverview
      → confirmed results query (confirmedForUser + blood_test filter)
      → eager load biomarker.category
      → BuildLongitudinalChanges for scope blood tests
      → group by category (null → "Overig")
      → attach description from BiomarkerReferenceDescriptions (optional)
      → sort categories by name, markers by name within category
  → Blade partial renders theme blocks
```

### 3.2 New files

| File | Responsibility |
| --- | --- |
| `app/Domain/Biomarkers/BuildThematicBiomarkerOverview.php` | Core grouping + row DTOs |
| `app/Domain/Biomarkers/BiomarkerReferenceDescriptions.php` | Read-only lookup: normalized name → neutral NL description |
| `config/biomarker_reference_descriptions.php` | Audited static copy (starts small: inflammation cluster) |
| `resources/views/shared/_thematic-biomarker-overview.blade.php` | Reusable theme UI |
| `database/seeders/DefaultBiomarkerCategoriesSeeder.php` | Idempotent owner categories per v1-spec list |
| `docs/adr/0013-thematic-biomarker-descriptions-are-organizational-only.md` | Copy boundary ADR |

### 3.2 Modified files

| File | Change |
| --- | --- |
| `app/Domain/Consult/BuildConsultOverview.php` | Add `thematicOverview` when `include_themes` |
| `app/Http/Controllers/ConsultOverview/ShowConsultOverviewController.php` | Validate `include_themes` |
| `resources/views/consult-overview/_pack.blade.php` | New section |
| `resources/views/consult-overview/index.blade.php` | Checkbox |
| `app/Domain/Dashboard/BuildLatestUploadSummary.php` | Expose `thematicGroups` via builder injection OR call thematic builder |
| `resources/views/dashboard/_latest-blood-test.blade.php` | Optional theme grouping in digest |
| `tests/Unit/Biomarkers/BuildThematicBiomarkerOverviewTest.php` | New |
| `tests/Feature/ConsultOverview/ConsultPackTest.php` | Theme section |
| `tests/Feature/DashboardTest.php` | Theme grouping when categories set |
| `docs/v1-spec.md` | Thematic overview acceptance |

### 3.3 Output contract (domain)

```php
/**
 * @return array{
 *   categories: list<array{
 *     key: string,
 *     name: string,
 *     themeDescription: string|null,
 *     markers: list<array{
 *       biomarkerId: int,
 *       name: string,
 *       description: string|null,
 *       valueLabel: string,
 *       unit: string|null,
 *       status: string,
 *       statusLabel: string,
 *       trendLabel: string|null,
 *       testDate: string|null,
 *       bloodTestTitle: string|null,
 *     }>
 *   }>,
 *   uncategorizedCount: int,
 * }
 */
```

Rules:

- Only categories with ≥1 confirmed marker in scope appear.
- `key`: slug of category name or `uncategorized`.
- `themeDescription`: optional, from `BiomarkerCategory` later; null in v1 unless we add column — **start null**, theme name is enough.
- `description`: from reference map only; null if unknown (no fabrication).
- `trendLabel`: reuse `LongitudinalChange.changeLabel` or `—` when not comparable.

### 3.4 Description copy rules (ADR-0013)

Allowed:

- “Wordt in labrapporten vermeld als…”
- “Onderdeel van het totaal bloedbeeld / differentiatie…”
- “Meet een waarde die labs often groeperen onder…”

Forbidden:

- voorspelt, risico, behandelen, advies, optimaliseren, extra testen, urgentie
- causal claims (“bij ontsteking is dit altijd hoog”)

Reference file is **public generic education**, not personalized interpretation.

### 3.5 Category assignment strategy

**Phase A (this plan):**

- `DefaultBiomarkerCategoriesSeeder` creates v1-spec categories for a user if missing.
- QA seeder already assigns categories; extend with inflammation markers + descriptions in reference map.
- Uncategorized markers still render under **Overig**.

**Phase B (later slice, not this plan):**

- Catalog UI to assign category per biomarker
- Optional deterministic name→category map on auto-import (ADR required)

---

## 4. UI surfaces

### 4.1 Consult pack (primary)

New section after attention/normal or before trends:

- Heading: **Per thema**
- Subcopy: “Alleen bevestigde waarden, gegroepeerd voor overzicht. Geen medische interpretatie.”
- Per theme: heading + marker rows (name, value, status, trend, optional description in muted text)
- Checkbox: `include_themes` (default **on** when POST from dashboard handoff — update `consultHandoffQuery`)

Print-friendly (supports PR C): section uses simple headings, descriptions hidden on print if too long (optional `print:hidden` on description only).

### 4.2 Dashboard latest digest (secondary)

Inside `_latest-blood-test` when summary exists:

- Collapsible or secondary block **Per thema** below flat preview (keep flat rows for regression until browser QA proves theme view sufficient)
- OR replace flat list with theme groups when any categorized markers exist (product choice: **additive first**)

---

## 5. Testing strategy

### Unit (`BuildThematicBiomarkerOverviewTest`)

- Groups two markers under same category
- Uncategorized → Overig
- Excludes drafts and foreign biomarker links
- Trend label present when comparable prior exists
- Empty scope → empty categories
- Cross-owner blood test in collection → 403 at controller; builder returns empty if blood test not owned

### Feature

- Consult pack renders Ontstekingen block when seeded
- Dashboard shows theme when categories assigned
- `include_themes=0` hides section
- CSV export: **exclude** theme descriptions initially (or include as extra section in follow-up — default **out** for slice 1)

---

## 6. Validation

```bash
php artisan test tests/Unit/Biomarkers/BuildThematicBiomarkerOverviewTest.php
php artisan test tests/Feature/ConsultOverview/ConsultPackTest.php
php artisan test tests/Feature/DashboardTest.php
sh scripts/validate.sh
```

Browser QA:

- Upload/confirm blood test with categorized markers
- Consult pack → **Per thema** visible with trends
- Print preview (when PR C lands) shows theme headers

---

## 7. Implementation tasks

### Task 1: ADR + spec

**Files:**

- Create: `docs/adr/0013-thematic-biomarker-descriptions-are-organizational-only.md`
- Modify: `docs/v1-spec.md` (Consult Overview + dashboard digest bullets)
- Modify: `docs/evidence/source-index.md`

- [ ] **Step 1:** Write ADR — organizational-only theme grouping; static reference descriptions; no AI; no diagnosis language
- [ ] **Step 2:** Add v1-spec acceptance: theme view shows confirmed values by category with trend; descriptions optional
- [ ] **Step 3:** Commit `docs: ADR-0013 thematic biomarker overview boundary`

---

### Task 2: Reference descriptions (inflammation starter set)

**Files:**

- Create: `config/biomarker_reference_descriptions.php`
- Create: `app/Domain/Biomarkers/BiomarkerReferenceDescriptions.php`

- [ ] **Step 1:** Write failing unit test for lookup by normalized name (case/ accent insensitive)

```php
public function test_reference_description_lookup_is_case_insensitive(): void
{
    $lookup = new BiomarkerReferenceDescriptions;

    $this->assertSame(
        'Marker die in labrapporten vaak wordt gebruikt bij ontstekingsonderzoek.',
        $lookup->forName('hsCRP'),
    );
    $this->assertNull($lookup->forName('Unknown marker XYZ'));
}
```

- [ ] **Step 2:** Run test — expect FAIL
- [ ] **Step 3:** Implement config + lookup class with normalized key (`Str::lower` + trim)
- [ ] **Step 4:** Seed config with neutral NL copy for: hsCRP, WBC, neutrofielen, eosinofielen, basofielen, lymfocyten, monocyten (Vitasure-inspired **structure**, rewritten copy without risk claims)
- [ ] **Step 5:** Run test — PASS
- [ ] **Step 6:** Commit `feat: local biomarker reference descriptions lookup`

---

### Task 3: Default categories seeder

**Files:**

- Create: `database/seeders/DefaultBiomarkerCategoriesSeeder.php`
- Modify: `database/seeders/BloodValuesQaScenarioSeeder.php` (use shared category names)

- [ ] **Step 1:** Seeder creates owner categories: Bloedbeeld, Ontstekingen, Lever, Nieren, Lipiden, Glucose/stofwisseling, Vitaminen/mineralen, Schildklier, Hormonen, Overig (if not exists)
- [ ] **Step 2:** Feature test: running seeder twice is idempotent
- [ ] **Step 3:** Commit `feat: default biomarker categories seeder`

---

### Task 4: BuildThematicBiomarkerOverview (core)

**Files:**

- Create: `app/Domain/Biomarkers/BuildThematicBiomarkerOverview.php`
- Create: `tests/Unit/Biomarkers/BuildThematicBiomarkerOverviewTest.php`
- Modify: `app/Domain/Biomarkers/` — add `AGENTS.md` section if file exists, else note in root Domain AGENTS

- [ ] **Step 1:** Write failing tests:
  - groups by category
  - uncategorized bucket
  - confirmed-only (draft excluded)
  - trend label attached
  - foreign biomarker excluded via confirmedForUser

- [ ] **Step 2:** Implement builder:

```php
final class BuildThematicBiomarkerOverview
{
    public function __construct(
        private readonly BuildLongitudinalChanges $buildLongitudinalChanges,
        private readonly BiomarkerReferenceDescriptions $descriptions,
    ) {}

    /** @param list<int> $bloodTestIds */
    public function forBloodTests(User $user, array $bloodTestIds): array { /* ... */ }

    public function forBloodTest(User $user, BloodTest $bloodTest): array
    {
        return $this->forBloodTests($user, [$bloodTest->id]);
    }
}
```

- Reuse row formatting patterns from `BuildLatestUploadSummary` (extract shared private trait or duplicate minimally — prefer **small shared helper** `SummarizeConfirmedResultRow` only if duplication >30 lines)

- [ ] **Step 3:** Run unit tests — PASS
- [ ] **Step 4:** Commit `feat: thematic biomarker overview domain builder`

---

### Task 5: Consult integration

**Files:**

- Modify: `app/Domain/Consult/BuildConsultOverview.php`
- Modify: `app/Http/Controllers/ConsultOverview/ShowConsultOverviewController.php`
- Modify: `app/Domain/Dashboard/BuildDashboardReadiness.php` (`consultHandoffQuery` add `include_themes` => 1)
- Modify: `resources/views/consult-overview/index.blade.php`
- Modify: `resources/views/consult-overview/_pack.blade.php`
- Modify: `resources/views/dashboard/_next-step.blade.php` (hidden input if needed)
- Create: `resources/views/shared/_thematic-biomarker-overview.blade.php`
- Modify: `tests/Feature/ConsultOverview/ConsultPackTest.php`

- [ ] **Step 1:** Add `include_themes` filter (default false for backward compat in GET; true in dashboard handoff)
- [ ] **Step 2:** Failing feature test: consult shows Ontstekingen with hsCRP + trend
- [ ] **Step 3:** Wire builder + partial
- [ ] **Step 4:** Update dashboard POST handoff hidden field `include_themes=1`
- [ ] **Step 5:** Run tests — PASS
- [ ] **Step 6:** Commit `feat: consult pack thematic biomarker section`

---

### Task 6: Dashboard integration

**Files:**

- Modify: `app/Domain/Dashboard/BuildLatestUploadSummary.php` OR `DashboardController` inject thematic builder
- Modify: `resources/views/dashboard/_latest-blood-test.blade.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1:** Pass `thematicOverview` to latest blood test partial when summary exists
- [ ] **Step 2:** Failing test: categorized marker appears under theme heading on dashboard
- [ ] **Step 3:** Render shared partial below existing flat preview (additive)
- [ ] **Step 4:** Run tests — PASS
- [ ] **Step 5:** Commit `feat: dashboard thematic biomarker grouping`

---

### Task 7: Full validation + handoff

- [ ] **Step 1:** `sh scripts/validate.sh`
- [ ] **Step 2:** Update `docs/current-operating-intent.md` active slice
- [ ] **Step 3:** Browser QA notes in PR (no private PDF content in docs)
- [ ] **Step 4:** Draft PR

---

## 8. Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Copy drifts into medical advice | ADR-0013 + forbidden-word test on reference config |
| Most markers uncategorized → empty UX | Show **Overig**; seeder + later catalog UI |
| Duplicated row formatting | Extract minimal shared summarizer if >30 lines duplicated |
| Consult pack too long | Theme section collapsible; print CSS in PR C |
| Description mismatch wrong marker | Lookup by owner biomarker **name** only; null if no match |

---

## 9. Self-review (spec coverage)

| Requirement | Task |
| --- | --- |
| Theme grouping | Task 4, 5, 6 |
| Confirmed values only | Task 4 tests |
| Trend vs previous | Task 4 (longitudinal reuse) |
| Optional description | Task 2 |
| No medical advice | Task 1 ADR + copy rules |
| Owner scope | Task 4 tests |
| Consult surface | Task 5 |
| Dashboard surface | Task 6 |

---

## 10. Execution handoff

**Plan saved to:** `docs/superpowers/plans/2026-06-27-thematic-biomarker-overview.md`

**Recommended execution order:** Task 1 → 2 → 3 → 4 → 5 → 6 → 7

**Estimated slices:** 2 PRs acceptable — PR A: Tasks 1–4 (domain + reference); PR B: Tasks 5–7 (UI + validation)

**Two execution options:**

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks
2. **Inline Execution** — implement sequentially in one session with checkpoints after Task 4 and Task 6

Which approach should we use to start implementation?
