# Blood Results Overview Spec

Date: 2026-07-02

Governed by ADR-0013 (Proposed). This spec stays inside the V1/V2 product
boundary: personal tracking and consult preparation, no diagnosis, no medical
advice.

## 1. Purpose

Give the user one confirmed-only overview of their confirmed biomarker values,
grouped by status, so they can see at a glance what is inside range, what is
outside the entered reference range, and what could not be classified.

The surface organizes; it does not interpret.

## 2. Boundary

In scope:

- all confirmed biomarker values of the authenticated owner;
- grouping and counts per status (`low`, `high`, `normal`, `unknown`);
- name, value, unit, reference range, and status in words per row;
- the visual language from `DESIGN.md` section 5, "Blood Results Overview".

Out of scope (non-goals):

- interpretation, advice, or explanation of what a value means;
- urgency, risk, scoring, or extra-testing encouragement;
- draft or unconfirmed values in any form, including counts;
- write actions (confirm, edit, delete happen on their existing surfaces);
- trend lines or comparisons (existing surfaces already cover those);
- new packages, AI, OCR, or external processing.

## 3. Data Source

- New builder: `app/Domain/Dashboard/BuildBloodResultsOverview`.
- Entry path: `BiomarkerResult::confirmedForUser($user->id)` — the existing
  scope that enforces `confirmed_at` plus owner scope on both the blood test
  and the biomarker. No other query path is allowed.
- The builder maps results to plain view rows (name, value, unit, reference
  minimum/maximum, reference unit, status) and groups them as:
  - `summary`: counts per status;
  - `attention`: confirmed `low` and `high` rows;
  - `normal`: confirmed `normal` rows;
  - `unknown`: confirmed `unknown` rows.
- Livewire orchestrates only; grouping and status logic live in the builder.

## 4. Status Rules

- Status values come from `App\Enums\BiomarkerStatus`: `Low`, `High`, `Normal`,
  `Unknown`.
- The persisted `status` column on `biomarker_results` is the primary source.
- When a persisted status is absent or not trustworthy, derive it with
  `App\Domain\Biomarkers\DetermineBiomarkerStatus` using the row's stored
  value, unit, and reference fields — never with catalog defaults, so later
  catalog edits do not rewrite history.
- When derivation inputs are missing or mismatched, the row is `unknown`; it is
  shown in the unknown group, never guessed into another group.

## 5. Answered Code Questions

Questions this spec was required to resolve by reading the code:

- **Value column on `biomarker_results`:** the column is `value`,
  `decimal(12, 4)` (migration `2026_06_18_000005_create_biomarker_results_table`).
  Eloquent returns it as a string (`@property string $value` on the model), so
  the builder must cast to float before status derivation and keep the original
  string for display.
- **Is status persisted or computed?** Persisted: `biomarker_results.status` is
  a string column with default `'unknown'`, set at confirm/auto-confirm time
  and recalculated only when the user edits value/range/unit (v1-spec section
  5). `DetermineBiomarkerStatus` remains available as the deterministic
  derivation fallback described in section 4.
- **Biomarker label attribute:** `Biomarker::name` (required). `short_name` is
  optional and nullable; there is no dedicated label accessor. The overview
  uses `name` as the row label.

## 6. UI

Per `DESIGN.md` section 5 ("Blood Results Overview"), section 2 (color), and
section 7 (depth):

- one summary panel with per-status counts;
- one amber-tinted featured attention card for confirmed `low`/`high` values
  with a single getallenlijn and a one-sentence non-medical takeaway;
- confirmed `normal` and `unknown` values as compact rows, not cards, with
  tabular values and a mini reference line when available;
- exactly one calm green good-area for the normal group; no extra color
  categories or bright blocks;
- borders-first surfaces (`border-neutral-200` / `dark:border-neutral-700`),
  no heavy shadows;
- motion: none;
- dark mode variants for every surface and status treatment;
- status must be visible in words, never only as color.

Rendering:

- class-based Livewire component under `app/Livewire/BloodTests/`;
- Blade view under `resources/views/livewire/blood-tests/`.

## 7. Copy Rules

- Status labels in words: `laag`, `hoog`, `normaal`, `onbekend`.
- Frame status as "status op basis van ingevoerde referentierange".
- Allowed framing: "persoonlijk overzicht", "opvolging", "bespreek met je
  arts".
- Forbidden: "diagnose", "behandeling", "advies", "aanbevolen supplement",
  "gezondheidsscore", "optimaal voor jou", "risico", "medisch oordeel",
  urgency language, and any explanation of what a marker means medically.
- Rows show only: name, value, unit, reference range, status.

## 8. Validation

Required before the slice is complete:

- unit tests for the builder: grouping, counts, string-to-float casting,
  status fallback to `DetermineBiomarkerStatus`, and `unknown` on missing or
  mismatched inputs;
- feature tests proving confirmed-only behavior: drafts (extracted,
  `confirmed_at` null) never appear, in values or in counts;
- feature tests proving tenant isolation: user A never sees user B's rows;
- the architecture boundary tests stay green: `MedicalCopyBoundaryTest`,
  `PrivacyBoundaryTest`, `PackageBoundaryTest`;
- `sh scripts/validate.sh` passes;
- browser QA on the seeded QA scenario before handoff.

## 9. Open Questions

### Resolved (build, 2026-07-02)

- **Route:** `GET /blood-results`, named `blood-results.overview`, inside the
  authenticated route group next to the blood-tests routes.
- **Naming:** the component is `App\Livewire\BloodTests\ConfirmedBiomarkerOverview`
  with view `resources/views/livewire/blood-tests/confirmed-biomarker-overview.blade.php`,
  a deliberate choice to avoid collision with the existing dashboard digest
  partial `resources/views/dashboard/_blood-results-overview.blade.php`. The
  domain builder keeps the spec name: `App\Domain\Dashboard\BuildBloodResultsOverview`.
- **Ordering within groups:** most recently confirmed first
  (`latest('confirmed_at')` in the builder); grouping by status happens on the
  already-ordered collection.
- **Column names (corrected against the migration):** the reference columns are
  `reference_min` and `reference_max` (not `reference_minimum`/`reference_maximum`)
  and the unit column is `unit` (not `value_unit`). The status enum lives at
  `App\Enums\BiomarkerStatus`; the model does not cast `status` to the enum, so
  the builder maps it with `BiomarkerStatus::tryFrom`.

### Open

- Navigation entry point (sidebar and/or dashboard link) — deferred; the route
  exists but is not yet linked from navigation.
