# Biomarker Category Assignment Implementation Plan

> **For agentic workers:** Use TDD strictly: one failing test → minimal code → green → next test.
> Do not add intake auto-categorization in this slice.

**Goal:** Let the owner assign each owned biomarker to an owner-scoped category so **Per thema** (PR #42) is useful instead of dumping everything into **Overig**.

**Architecture:** Smallest catalog maintenance slice on existing models (`Biomarker`, `BiomarkerCategory`). Add owner-authorized PATCH on biomarker detail, lazy default-category bootstrap, and optional uncategorized nudge on dashboard. No new packages, no intake parser changes, no runtime AI.

**Depends on:** PR #42 thematic overview merged (or same branch rebased on it).

**Tech stack:** Single-action Laravel controller, Form Request or inline validation, Blade on `biomarkers/show`, Pest feature tests, reuse `DefaultBiomarkerCategoriesSeeder`.

---

## 1. Problem statement

PR #42 groups confirmed values by `biomarker.biomarker_category_id`. Today that FK is almost always `null` because:

| Asset | Status |
| --- | --- |
| `BiomarkerCategory` model + owner scope | ✅ |
| `Biomarker.biomarker_category_id` | ✅ nullable |
| `DefaultBiomarkerCategoriesSeeder` | ✅ exists, not auto-run for normal users |
| UI to assign category to biomarker | ❌ missing |
| Intake auto-map | ❌ out of scope (needs new ADR) |

Without assignment UI, **Per thema** degrades to one big **Overig** block — technically correct, product-empty.

---

## 2. Scope boundaries

### In scope

- PATCH owned biomarker → set/clear `biomarker_category_id`
- Category dropdown on `/biomarkers/{biomarker}` (primary surface)
- Lazy bootstrap: ensure default categories exist when owner opens assignment UI
- Show current category on biomarker detail (or explicit “Geen thema / Overig”)
- Feature tests: owner auth, foreign category rejected, assignment reflected in thematic builder
- v1-spec touch-up for catalog maintenance acceptance
- Dutch copy on touched biomarker surfaces (page still mixed EN/NL today)

### Out of scope

- Full biomarker catalog CRUD (create/edit name, ranges, deactivate)
- Bulk assign all markers at once
- Auto-categorization during CMA intake
- Category rename/delete UI (categories are stable v1 list; delete risks FK — defer)
- New reference descriptions
- Filament/admin panel

---

## 3. Architecture

### 3.1 Data flow

```text
Owner opens GET /biomarkers/{biomarker}
  → authorize biomarker.user_id
  → ensureDefaultCategories(user)  [lazy seeder if count = 0]
  → load owner categories ordered by name
  → render select + current value

Owner submits PATCH /biomarkers/{biomarker}
  → authorize biomarker.user_id
  → validate biomarker_category_id: nullable, exists in owner categories
  → update biomarker.biomarker_category_id only
  → redirect back with flash

Downstream (unchanged builders):
  BuildThematicBiomarkerOverview reads biomarker.category
  → grouped under theme name instead of Overig
```

### 3.2 New files

| File | Responsibility |
| --- | --- |
| `app/Http/Controllers/Biomarkers/UpdateBiomarkerCategoryController.php` | PATCH handler |
| `app/Domain/Biomarkers/EnsureDefaultBiomarkerCategories.php` | Idempotent lazy bootstrap wrapper around seeder |
| `tests/Feature/Biomarkers/UpdateBiomarkerCategoryTest.php` | Auth + validation + thematic side effect |

### 3.3 Modified files

| File | Change |
| --- | --- |
| `app/Http/Controllers/Biomarkers/ShowBiomarkerController.php` | Pass categories + ensure bootstrap |
| `resources/views/biomarkers/show.blade.php` | Category form, Dutch labels, `data-test` hooks |
| `routes/web.php` | `PATCH biomarkers/{biomarker}` |
| `tests/Feature/Biomarkers/*` or new file | Feature coverage |
| `tests/Unit/Biomarkers/BuildThematicBiomarkerOverviewTest.php` | Optional integration via feature test instead |
| `docs/v1-spec.md` | Catalog maintenance acceptance bullet |
| `docs/current-operating-intent.md` | Active slice after start |

### 3.4 Validation rules

```php
'biomarker_category_id' => [
    'nullable',
    'integer',
    Rule::exists('biomarker_categories', 'id')->where('user_id', $user->id),
],
```

- `null` → uncategorized → **Overig** in thematic views.
- Foreign category ID → 422 validation error (not 403 on exists leak — use scoped exists rule).
- Do not allow assigning category from URL tampering on foreign biomarker (403 on biomarker itself).

### 3.5 UX (minimal)

On biomarker detail header:

- Label: **Thema**
- Select: owner categories (from seeder list) + option **Geen thema**
- Helper: “Alleen voor overzicht. Geen medische interpretatie.” (ADR-0013 aligned)
- Submit: **Thema opslaan**
- Show saved category name when set

Optional v1.1 in same slice if cheap:

- Dashboard pill: “X biomarkers zonder thema” linking to first uncategorized biomarker — **defer unless trivial**

---

## 4. Testing strategy (TDD order)

### Feature (`UpdateBiomarkerCategoryTest`)

1. **RED:** owner can assign owned category → `biomarker_category_id` updated, redirect, flash
2. **RED:** foreign biomarker → 403
3. **RED:** foreign category id → 422
4. **RED:** null clears category
5. **RED:** after assign, `BuildThematicBiomarkerOverview` / consult POST shows theme block (integration)

### Unit (`EnsureDefaultBiomarkerCategoriesTest`) — optional

- First call creates categories; second call idempotent

### Regression

- `BuildThematicBiomarkerOverviewTest` unchanged
- `DataExportAndDeletionTest` — export still nulls corrupt cross-owner category links

---

## 5. Validation commands

```bash
php artisan test tests/Feature/Biomarkers/UpdateBiomarkerCategoryTest.php
php artisan test tests/Unit/Biomarkers/
php artisan test tests/Feature/ConsultOverview/ConsultPackTest.php
php artisan test tests/Feature/DashboardTest.php
sh scripts/validate.sh
```

Browser QA:

1. Open biomarker detail for CRP (no category)
2. Assign **Ontstekingen** → save
3. Dashboard + consult **Per thema** show CRP under Ontstekingen (not Overig)

---

## 6. Implementation tasks

### Task 1: Spec + slice contract

- [ ] Add v1-spec bullet: owner can assign biomarker to organizational category on detail page
- [ ] Optional: fill `docs/templates/slice-contract-template.md` copy under `docs/superpowers/plans/` handoff section
- [ ] Commit: `docs: biomarker category assignment acceptance`

### Task 2: EnsureDefaultBiomarkerCategories

- [ ] RED: unit/feature test — user with 0 categories gets 10 after invoke
- [ ] GREEN: `EnsureDefaultBiomarkerCategories` calling `DefaultBiomarkerCategoriesSeeder::seedForUser`
- [ ] Commit: `feat: lazy default biomarker categories bootstrap`

### Task 3: UpdateBiomarkerCategoryController

- [ ] RED: feature test assign category
- [ ] GREEN: controller + route PATCH
- [ ] RED: foreign biomarker / foreign category tests
- [ ] GREEN: validation + 403
- [ ] Commit: `feat: assign biomarker category on detail page`

### Task 4: Biomarker show UI

- [ ] RED: feature test sees form with categories on GET show
- [ ] GREEN: ShowBiomarkerController passes categories; blade form + Dutch copy
- [ ] Commit: `feat: biomarker detail category assignment form`

### Task 5: Integration proof

- [ ] RED: assign category → consult thematic section names theme
- [ ] GREEN: wire already done; test passes
- [ ] Update `docs/current-operating-intent.md`
- [ ] `sh scripts/validate.sh`
- [ ] Browser QA notes in PR (no private data)
- [ ] Draft PR off `main` (after #42 merged)

---

## 7. Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| User never discovers assignment UI | Later: nudge on dashboard; for now link from **Overig** copy in thematic partial |
| Category list differs from lab mental model | v1 fixed seeder list; rename UI deferred |
| English biomarker show page | Dutch on touched strings only in this slice |
| Accidental intake auto-map scope creep | Explicit out of scope; no ADR-0014 unless auto-map proposed |

---

## 8. Follow-up (not this slice)

- Bulk “assign uncategorized” workflow
- Category rename/delete with FK safety
- Deterministic name→category on trusted CMA import (new ADR)
- Dashboard uncategorized count banner

---

## 9. Execution handoff

**Plan saved to:** `docs/superpowers/plans/2026-06-30-biomarker-category-assignment.md`

**Branch naming:** `cursor/biomarker-category-assignment-5034`

**PR split:** single PR acceptable (4 tasks, one vertical slice)

**Recommended order:** Task 1 → 2 → 3 → 4 → 5 with strict TDD per task
