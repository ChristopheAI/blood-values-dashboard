# First Vertical Slice Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the smallest useful Laravel/Livewire slice where an authenticated user can create two blood tests, enter biomarker results, see status calculation, view biomarker history, and compare two tests.

**Architecture:** Use the official Laravel Livewire starter kit for auth and app shell, but keep health-domain rules in plain Laravel classes and models. Livewire components orchestrate form workflows; status calculation, comparison rules, and user scoping live in testable domain/application code.

**Tech Stack:** Laravel Livewire starter kit, Blade/Livewire, SQLite for local first slice, Pest/PHPUnit via the starter kit, Vite/Tailwind from the starter kit.

---

## Pre-Execution Rule

Do not execute this plan until the current planning baseline has been reviewed.

Before Task 1, the repository should contain a planning baseline and
pre-scaffold review gate with:

- `laravel-platform-discovery.md`
- `README.md`
- `AGENTS.md`
- `docs/project-brief.md`
- `docs/v1-spec.md`
- `docs/product-system-check.md`
- `docs/evidence/source-index.md`
- `docs/adr/0001-use-repo-as-project-control-plane.md`
- `docs/adr/0002-use-adrs-for-architecture-decisions.md`
- `docs/adr/0003-use-livewire-starter-kit-for-v1.md`
- `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
- `docs/research/laravel-stack-decision.md`
- `docs/research/ai-architect-program-transfer.md`
- `docs/session-handoff.md`
- `docs/validation-protocol.md`
- `docs/ops/production-checklist.md`
- `docs/reviews/pre-scaffold-review-request.md`
- `docs/reviews/pre-scaffold-review-scorecard.md`
- `docs/reviews/pre-scaffold-review-result.md`
- `docs/superpowers/plans/2026-06-16-first-vertical-slice.md`
- `scripts/validate.sh`
- `.github/workflows/ci.yml`

The pre-scaffold review gate must produce a go/no-go decision before Task 1
scaffolds Laravel. Current decision: `GO WITH CHANGES`, recorded in
`docs/reviews/pre-scaffold-review-result.md`.

## File Structure Map

Expected files after implementation:

- Create Laravel starter files via `laravel/livewire-starter-kit`.
- Preserve `README.md`, `AGENTS.md`, `docs/`, `scripts/validate.sh`, and the
  project CI workflow when copying scaffold files.
- Modify `scripts/validate.sh`: switch from planning-stage validation to Laravel validation.
- Modify `.github/workflows/ci.yml`: install PHP/Node dependencies and run `sh scripts/validate.sh`.
- Create `app/Enums/BiomarkerStatus.php`: canonical status values.
- Create `app/Domain/Biomarkers/DetermineBiomarkerStatus.php`: status calculation.
- Create `app/Domain/BloodTests/CompareBloodTests.php`: two-test comparison rows.
- Create `app/Models/BloodTest.php`: user-owned blood test.
- Create `app/Models/BiomarkerCategory.php`: user-owned biomarker grouping.
- Create `app/Models/Biomarker.php`: user-owned biomarker catalog item.
- Create `app/Models/BiomarkerResult.php`: measured biomarker value.
- Create database migrations for the above models.
- Create factories for test data.
- Create Livewire pages/components for blood tests, biomarker entry, biomarker history, and comparison.
- Create feature/unit tests proving auth, ownership, status logic, result entry, history, and comparison.

## Task 0: Pre-Scaffold Gate Checkpoint

**Files:**
- Verify: `README.md`
- Verify: `AGENTS.md`
- Verify: `docs/project-brief.md`
- Verify: `docs/v1-spec.md`
- Verify: `docs/product-system-check.md`
- Verify: `docs/evidence/source-index.md`
- Verify: `docs/adr/0001-use-repo-as-project-control-plane.md`
- Verify: `docs/adr/0002-use-adrs-for-architecture-decisions.md`
- Verify: `docs/adr/0003-use-livewire-starter-kit-for-v1.md`
- Verify: `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
- Verify: `docs/research/laravel-stack-decision.md`
- Verify: `docs/research/ai-architect-program-transfer.md`
- Verify: `docs/validation-protocol.md`
- Verify: `docs/ops/production-checklist.md`
- Verify: `docs/reviews/pre-scaffold-review-request.md`
- Verify: `docs/reviews/pre-scaffold-review-scorecard.md`
- Verify: `docs/reviews/pre-scaffold-review-result.md`
- Verify: `docs/superpowers/plans/2026-06-16-first-vertical-slice.md`
- Verify: `scripts/validate.sh`

- [ ] **Step 1: Run planning validation**

Run:

```bash
sh scripts/validate.sh
```

Expected:

```text
Planning validation passed.
```

- [ ] **Step 2: Review current git state**

Run:

```bash
git status --short --branch
```

Expected:

```text
## main...origin/main
```

- [ ] **Step 3: Confirm latest checkpoint**

Run:

```bash
git log --oneline --decorate -3
```

Expected:

```text
<hash> (HEAD -> main, origin/main) docs: record pre-scaffold review decision
<hash> docs: add AI Architect decision layer
<hash> docs: record GitHub repository setup
```

- [ ] **Step 4: Confirm the review gate allows scaffold**

Run:

```bash
grep -q "Decision: GO WITH CHANGES" docs/reviews/pre-scaffold-review-result.md
```

Expected: exit code `0`.

- [ ] **Step 5: Confirm no Laravel scaffold exists yet**

Run:

```bash
test ! -f artisan
test ! -f composer.json
test ! -d app
test ! -d routes
test ! -d database
```

Expected: exit code `0`.

- [ ] **Step 6: Proceed to scaffold only the first slice**

Constraint:

```text
Do not add document upload, OCR, AI interpretation, reminders, exports,
pinned biomarkers, or consult-PDF work in Task 1.
```

Expected implementation scope:

```text
Laravel Livewire starter kit + immediate validation transition only.
```

## Task 1: Scaffold Laravel Livewire Starter Kit

**Files:**
- Create: Laravel starter app files in repository root
- Preserve: `README.md`
- Preserve: `AGENTS.md`
- Preserve: `docs/`
- Preserve: `scripts/validate.sh`
- Preserve: `.github/workflows/ci.yml`

- [ ] **Step 1: Create a temporary Livewire starter app**

Run:

```bash
tmpdir="$(mktemp -d)"
composer create-project laravel/livewire-starter-kit "$tmpdir/app"
```

Expected:

```text
Application key set successfully.
```

- [ ] **Step 2: Copy scaffold into current repository**

Run:

```bash
rsync -a \
  --exclude='.git' \
  --exclude='README.md' \
  --exclude='docs' \
  --exclude='AGENTS.md' \
  --exclude='scripts/validate.sh' \
  --exclude='.github/workflows/ci.yml' \
  "$tmpdir/app/" ./
rm -rf "$tmpdir"
```

Expected:

```text
```

No output is acceptable.

- [ ] **Step 3: Confirm scaffold exists**

Run:

```bash
test -f artisan
test -f composer.json
test -d app
test -d routes
test -d database
```

Expected: exit code `0`.

- [ ] **Step 4: Set local environment to SQLite**

Run:

```bash
cp .env.example .env
php artisan key:generate
perl -0pi -e 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
perl -0pi -e 's/DB_HOST=.*\n//; s/DB_PORT=.*\n//; s/DB_DATABASE=.*\n//; s/DB_USERNAME=.*\n//; s/DB_PASSWORD=.*\n//' .env
touch database/database.sqlite
```

Expected:

```text
INFO  Application key set successfully.
```

- [ ] **Step 5: Install frontend dependencies and build**

Run:

```bash
npm install
npm run build
```

Expected:

```text
built in
```

- [ ] **Step 6: Run starter tests**

Run:

```bash
composer test
```

Expected: all starter tests pass.

- [ ] **Step 7: Commit scaffold**

Run:

```bash
git add .
git commit -m "chore: scaffold Laravel Livewire starter"
```

Expected:

```text
[main <hash>] chore: scaffold Laravel Livewire starter
```

## Task 2: Switch Validation To Laravel Phase

**Files:**
- Modify: `scripts/validate.sh`
- Modify: `.github/workflows/ci.yml`
- Test: `sh scripts/validate.sh`

- [ ] **Step 1: Replace `scripts/validate.sh`**

Replace `scripts/validate.sh` with:

```bash
#!/bin/sh
set -eu

echo "== Required project files =="

required_files="
README.md
AGENTS.md
docs/project-brief.md
docs/v1-spec.md
docs/research/laravel-stack-decision.md
docs/superpowers/plans/2026-06-16-first-vertical-slice.md
docs/session-handoff.md
docs/validation-protocol.md
docs/ops/production-checklist.md
docs/reviews/pre-scaffold-review-request.md
docs/reviews/pre-scaffold-review-scorecard.md
composer.json
artisan
"

for file in $required_files; do
  if [ ! -f "$file" ]; then
    echo "Missing required file: $file" >&2
    exit 1
  fi
  echo "ok: $file"
done

echo
echo "== PHP tests =="
composer test

echo
echo "== Frontend build =="
npm run build

echo
echo "== Whitespace checks =="
git diff --check

echo
echo "Validation passed."
```

- [ ] **Step 2: Ensure script is executable**

Run:

```bash
chmod +x scripts/validate.sh
```

Expected: exit code `0`.

- [ ] **Step 3: Replace CI workflow**

Replace `.github/workflows/ci.yml` with:

```yaml
name: CI

on:
  push:
  pull_request:

jobs:
  validate:
    runs-on: ubuntu-latest

    steps:
      - name: Check out repository
        uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: sqlite, pdo_sqlite
          coverage: none

      - name: Set up Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: npm

      - name: Install PHP dependencies
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Install frontend dependencies
        run: npm ci

      - name: Prepare application
        run: |
          cp .env.example .env
          php artisan key:generate
          touch database/database.sqlite

      - name: Validate project
        run: sh scripts/validate.sh
```

- [ ] **Step 4: Run validation**

Run:

```bash
sh scripts/validate.sh
```

Expected:

```text
Validation passed.
```

- [ ] **Step 5: Commit validation switch**

Run:

```bash
git add scripts/validate.sh .github/workflows/ci.yml
git commit -m "chore: switch validation to Laravel checks"
```

Expected:

```text
[main <hash>] chore: switch validation to Laravel checks
```

## Task 3: Biomarker Status Domain Logic

**Files:**
- Create: `app/Enums/BiomarkerStatus.php`
- Create: `app/Domain/Biomarkers/DetermineBiomarkerStatus.php`
- Create: `tests/Unit/Domain/Biomarkers/DetermineBiomarkerStatusTest.php`

- [ ] **Step 1: Write the failing status tests**

Create `tests/Unit/Domain/Biomarkers/DetermineBiomarkerStatusTest.php`:

```php
<?php

use App\Domain\Biomarkers\DetermineBiomarkerStatus;
use App\Enums\BiomarkerStatus;

it('classifies a value below a complete range as low', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 3.9,
        unit: 'mmol/L',
        referenceMin: 4.0,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Low);
});

it('classifies a value inside a complete range as normal', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 5.2,
        unit: 'mmol/L',
        referenceMin: 4.0,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Normal);
});

it('classifies a value above a complete range as high', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 6.2,
        unit: 'mmol/L',
        referenceMin: 4.0,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::High);
});

it('returns unknown when both range boundaries are missing', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 5.2,
        unit: 'mmol/L',
        referenceMin: null,
        referenceMax: null,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Unknown);
});

it('returns unknown when units do not match', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 5.2,
        unit: 'mg/dL',
        referenceMin: 4.0,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Unknown);
});

it('returns unknown when minimum is greater than maximum', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 5.2,
        unit: 'mmol/L',
        referenceMin: 6.0,
        referenceMax: 4.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Unknown);
});

it('classifies below a one-sided minimum as low', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 3.8,
        unit: 'mmol/L',
        referenceMin: 4.0,
        referenceMax: null,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::Low);
});

it('classifies above a one-sided maximum as high', function () {
    $status = app(DetermineBiomarkerStatus::class)(
        value: 6.2,
        unit: 'mmol/L',
        referenceMin: null,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($status)->toBe(BiomarkerStatus::High);
});

it('returns unknown for values inside one-sided boundaries', function () {
    $minimumOnly = app(DetermineBiomarkerStatus::class)(
        value: 4.2,
        unit: 'mmol/L',
        referenceMin: 4.0,
        referenceMax: null,
        referenceUnit: 'mmol/L',
    );

    $maximumOnly = app(DetermineBiomarkerStatus::class)(
        value: 5.8,
        unit: 'mmol/L',
        referenceMin: null,
        referenceMax: 6.0,
        referenceUnit: 'mmol/L',
    );

    expect($minimumOnly)->toBe(BiomarkerStatus::Unknown)
        ->and($maximumOnly)->toBe(BiomarkerStatus::Unknown);
});
```

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Unit/Domain/Biomarkers/DetermineBiomarkerStatusTest.php
```

Expected: FAIL because `DetermineBiomarkerStatus` and `BiomarkerStatus` do not exist.

- [ ] **Step 3: Create status enum**

Create `app/Enums/BiomarkerStatus.php`:

```php
<?php

namespace App\Enums;

enum BiomarkerStatus: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Unknown = 'unknown';
}
```

- [ ] **Step 4: Create status calculator**

Create `app/Domain/Biomarkers/DetermineBiomarkerStatus.php`:

```php
<?php

namespace App\Domain\Biomarkers;

use App\Enums\BiomarkerStatus;

class DetermineBiomarkerStatus
{
    public function __invoke(
        int|float|string|null $value,
        ?string $unit,
        int|float|string|null $referenceMin,
        int|float|string|null $referenceMax,
        ?string $referenceUnit,
    ): BiomarkerStatus {
        $numericValue = $this->numeric($value);
        $min = $this->numeric($referenceMin);
        $max = $this->numeric($referenceMax);

        if ($numericValue === null || $this->blank($unit)) {
            return BiomarkerStatus::Unknown;
        }

        if ($min === null && $max === null) {
            return BiomarkerStatus::Unknown;
        }

        if (! $this->blank($referenceUnit) && $this->normalizeUnit($unit) !== $this->normalizeUnit($referenceUnit)) {
            return BiomarkerStatus::Unknown;
        }

        if ($min !== null && $max !== null && $min > $max) {
            return BiomarkerStatus::Unknown;
        }

        if ($min !== null && $max !== null) {
            return match (true) {
                $numericValue < $min => BiomarkerStatus::Low,
                $numericValue > $max => BiomarkerStatus::High,
                default => BiomarkerStatus::Normal,
            };
        }

        if ($min !== null && $numericValue < $min) {
            return BiomarkerStatus::Low;
        }

        if ($max !== null && $numericValue > $max) {
            return BiomarkerStatus::High;
        }

        return BiomarkerStatus::Unknown;
    }

    private function numeric(int|float|string|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function blank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }

    private function normalizeUnit(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
```

- [ ] **Step 5: Run status tests**

Run:

```bash
php artisan test tests/Unit/Domain/Biomarkers/DetermineBiomarkerStatusTest.php
```

Expected: PASS.

- [ ] **Step 6: Run full validation**

Run:

```bash
sh scripts/validate.sh
```

Expected:

```text
Validation passed.
```

- [ ] **Step 7: Commit status logic**

Run:

```bash
git add app/Enums/BiomarkerStatus.php app/Domain/Biomarkers/DetermineBiomarkerStatus.php tests/Unit/Domain/Biomarkers/DetermineBiomarkerStatusTest.php
git commit -m "feat: add biomarker status calculation"
```

Expected:

```text
[main <hash>] feat: add biomarker status calculation
```

## Task 4: Data Model For First Slice

**Files:**
- Create: `app/Models/BloodTest.php`
- Create: `app/Models/BiomarkerCategory.php`
- Create: `app/Models/Biomarker.php`
- Create: `app/Models/BiomarkerResult.php`
- Create: migrations for `blood_tests`, `biomarker_categories`, `biomarkers`, `biomarker_results`
- Create: factories for the four models
- Test: `tests/Feature/BloodValues/DataModelTest.php`

- [ ] **Step 1: Generate models, migrations, and factories**

Run:

```bash
php artisan make:model BloodTest -mf
php artisan make:model BiomarkerCategory -mf
php artisan make:model Biomarker -mf
php artisan make:model BiomarkerResult -mf
```

Expected:

```text
INFO  Model [app/Models/BloodTest.php] created successfully.
```

- [ ] **Step 2: Write relationship and ownership tests**

Create `tests/Feature/BloodValues/DataModelTest.php`:

```php
<?php

use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('stores a user-owned blood test with biomarker results', function () {
    $user = User::factory()->create();
    $category = BiomarkerCategory::factory()->for($user)->create(['name' => 'Metabolism']);
    $biomarker = Biomarker::factory()->for($user)->for($category)->create(['name' => 'Glucose']);
    $bloodTest = BloodTest::factory()->for($user)->create(['lab_name' => 'Labo CMA']);

    $result = BiomarkerResult::factory()
        ->for($bloodTest)
        ->for($biomarker)
        ->create([
            'value' => 5.2,
            'unit' => 'mmol/L',
            'reference_min' => 4.0,
            'reference_max' => 6.0,
            'reference_unit' => 'mmol/L',
            'status' => BiomarkerStatus::Normal,
        ]);

    expect($bloodTest->user->is($user))->toBeTrue()
        ->and($bloodTest->results)->toHaveCount(1)
        ->and($result->biomarker->is($biomarker))->toBeTrue()
        ->and($result->status)->toBe(BiomarkerStatus::Normal);
});
```

- [ ] **Step 3: Run test and verify failure**

Run:

```bash
php artisan test tests/Feature/BloodValues/DataModelTest.php
```

Expected: FAIL because relationships, casts, and schema are incomplete.

- [ ] **Step 4: Implement migrations**

Use these columns in the generated migrations:

```php
Schema::create('blood_tests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('tested_at');
    $table->string('lab_name')->default('unknown');
    $table->string('title')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

Schema::create('biomarker_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->timestamps();
    $table->unique(['user_id', 'name']);
});

Schema::create('biomarkers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('biomarker_category_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('short_name')->nullable();
    $table->string('default_unit')->nullable();
    $table->decimal('reference_min', 12, 4)->nullable();
    $table->decimal('reference_max', 12, 4)->nullable();
    $table->string('reference_unit')->nullable();
    $table->text('range_note')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
    $table->unique(['user_id', 'name']);
});

Schema::create('biomarker_results', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blood_test_id')->constrained()->cascadeOnDelete();
    $table->foreignId('biomarker_id')->constrained()->cascadeOnDelete();
    $table->decimal('value', 12, 4);
    $table->string('unit');
    $table->decimal('reference_min', 12, 4)->nullable();
    $table->decimal('reference_max', 12, 4)->nullable();
    $table->string('reference_unit')->nullable();
    $table->string('status')->default('unknown');
    $table->text('note')->nullable();
    $table->timestamps();
    $table->unique(['blood_test_id', 'biomarker_id']);
});
```

- [ ] **Step 5: Implement model relationships and casts**

Minimum relationships:

```php
// BloodTest
public function user(): BelongsTo;
public function results(): HasMany;

// BiomarkerCategory
public function user(): BelongsTo;
public function biomarkers(): HasMany;

// Biomarker
public function user(): BelongsTo;
public function category(): BelongsTo;
public function results(): HasMany;

// BiomarkerResult
public function bloodTest(): BelongsTo;
public function biomarker(): BelongsTo;
```

Required casts:

```php
// BloodTest
'tested_at' => 'date',

// BiomarkerResult
'status' => BiomarkerStatus::class,
```

- [ ] **Step 6: Run data model test**

Run:

```bash
php artisan test tests/Feature/BloodValues/DataModelTest.php
```

Expected: PASS.

- [ ] **Step 7: Run full validation and commit**

Run:

```bash
sh scripts/validate.sh
git add app database tests
git commit -m "feat: add blood values data model"
```

Expected:

```text
Validation passed.
[main <hash>] feat: add blood values data model
```

## Task 5: Blood Test Creation Flow

**Files:**
- Create/modify Livewire page for listing blood tests
- Create/modify Livewire page for creating blood tests
- Modify routes under authenticated middleware
- Test: `tests/Feature/BloodValues/BloodTestFlowTest.php`

- [ ] **Step 1: Write feature tests**

Create `tests/Feature/BloodValues/BloodTestFlowTest.php`:

```php
<?php

use App\Models\BloodTest;
use App\Models\User;

it('requires authentication to view blood tests', function () {
    $this->get('/blood-tests')->assertRedirect('/login');
});

it('allows an authenticated user to create a blood test', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/blood-tests', [
            'tested_at' => '2026-06-01',
            'lab_name' => 'Labo CMA',
            'title' => 'June baseline',
            'notes' => 'Fasted morning test.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('blood_tests', [
        'user_id' => $user->id,
        'tested_at' => '2026-06-01',
        'lab_name' => 'Labo CMA',
    ]);
});

it('does not show another users blood tests', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    BloodTest::factory()->for($otherUser)->create(['lab_name' => 'Hidden Lab']);

    $this->actingAs($user)
        ->get('/blood-tests')
        ->assertOk()
        ->assertDontSee('Hidden Lab');
});
```

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/BloodValues/BloodTestFlowTest.php
```

Expected: FAIL because `/blood-tests` routes do not exist.

- [ ] **Step 3: Implement authenticated routes**

Add routes that support:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/blood-tests', ...)->name('blood-tests.index');
    Route::post('/blood-tests', ...)->name('blood-tests.store');
});
```

Store validation:

```php
'tested_at' => ['required', 'date'],
'lab_name' => ['nullable', 'string', 'max:255'],
'title' => ['nullable', 'string', 'max:255'],
'notes' => ['nullable', 'string'],
```

Store behavior:

```php
$request->user()->bloodTests()->create([
    'tested_at' => $validated['tested_at'],
    'lab_name' => $validated['lab_name'] ?: 'unknown',
    'title' => $validated['title'] ?? null,
    'notes' => $validated['notes'] ?? null,
]);
```

- [ ] **Step 4: Add relationship to User**

In `app/Models/User.php`:

```php
public function bloodTests(): HasMany
{
    return $this->hasMany(BloodTest::class);
}
```

- [ ] **Step 5: Run flow tests**

Run:

```bash
php artisan test tests/Feature/BloodValues/BloodTestFlowTest.php
```

Expected: PASS.

- [ ] **Step 6: Run full validation and commit**

Run:

```bash
sh scripts/validate.sh
git add app routes resources tests
git commit -m "feat: add blood test creation flow"
```

Expected:

```text
Validation passed.
[main <hash>] feat: add blood test creation flow
```

## Task 6: Biomarker Catalog And Result Entry

**Files:**
- Create routes/components for biomarker catalog creation
- Create routes/components for adding results to a blood test
- Test: `tests/Feature/BloodValues/BiomarkerResultEntryTest.php`

- [ ] **Step 1: Write result-entry tests**

Create `tests/Feature/BloodValues/BiomarkerResultEntryTest.php`:

```php
<?php

use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerCategory;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('creates a biomarker result and stores calculated status', function () {
    $user = User::factory()->create();
    $category = BiomarkerCategory::factory()->for($user)->create();
    $biomarker = Biomarker::factory()->for($user)->for($category)->create(['name' => 'Glucose']);
    $bloodTest = BloodTest::factory()->for($user)->create();

    $this->actingAs($user)
        ->post("/blood-tests/{$bloodTest->id}/results", [
            'biomarker_id' => $biomarker->id,
            'value' => '6.2',
            'unit' => 'mmol/L',
            'reference_min' => '4.0',
            'reference_max' => '6.0',
            'reference_unit' => 'mmol/L',
            'note' => 'Morning fasting result.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('biomarker_results', [
        'blood_test_id' => $bloodTest->id,
        'biomarker_id' => $biomarker->id,
        'status' => BiomarkerStatus::High->value,
    ]);
});

it('prevents adding results to another users blood test', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBloodTest = BloodTest::factory()->for($otherUser)->create();
    $biomarker = Biomarker::factory()->for($user)->create();

    $this->actingAs($user)
        ->post("/blood-tests/{$otherBloodTest->id}/results", [
            'biomarker_id' => $biomarker->id,
            'value' => '5.2',
            'unit' => 'mmol/L',
            'reference_min' => '4.0',
            'reference_max' => '6.0',
            'reference_unit' => 'mmol/L',
        ])
        ->assertForbidden();

    expect(BiomarkerResult::count())->toBe(0);
});
```

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/BloodValues/BiomarkerResultEntryTest.php
```

Expected: FAIL because result routes do not exist.

- [ ] **Step 3: Implement result route and ownership check**

Route behavior:

```php
Route::post('/blood-tests/{bloodTest}/results', ...)->name('blood-tests.results.store');
```

Ownership guard:

```php
abort_unless($bloodTest->user_id === $request->user()->id, 403);
```

Biomarker guard:

```php
abort_unless(
    Biomarker::whereKey($validated['biomarker_id'])
        ->where('user_id', $request->user()->id)
        ->exists(),
    403
);
```

Status calculation:

```php
$status = app(DetermineBiomarkerStatus::class)(
    value: $validated['value'],
    unit: $validated['unit'],
    referenceMin: $validated['reference_min'] ?? null,
    referenceMax: $validated['reference_max'] ?? null,
    referenceUnit: $validated['reference_unit'] ?? null,
);
```

- [ ] **Step 4: Run result-entry tests**

Run:

```bash
php artisan test tests/Feature/BloodValues/BiomarkerResultEntryTest.php
```

Expected: PASS.

- [ ] **Step 5: Run full validation and commit**

Run:

```bash
sh scripts/validate.sh
git add app routes resources tests
git commit -m "feat: add biomarker result entry"
```

Expected:

```text
Validation passed.
[main <hash>] feat: add biomarker result entry
```

## Task 7: Biomarker History And Compare Two Tests

**Files:**
- Create: `app/Domain/BloodTests/CompareBloodTests.php`
- Create routes/components for biomarker history and test comparison
- Test: `tests/Feature/BloodValues/BiomarkerHistoryAndCompareTest.php`

- [ ] **Step 1: Write history and comparison tests**

Create `tests/Feature/BloodValues/BiomarkerHistoryAndCompareTest.php`:

```php
<?php

use App\Enums\BiomarkerStatus;
use App\Models\Biomarker;
use App\Models\BiomarkerResult;
use App\Models\BloodTest;
use App\Models\User;

it('shows biomarker history in date order for the authenticated user', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Glucose']);
    $oldTest = BloodTest::factory()->for($user)->create(['tested_at' => '2026-05-01']);
    $newTest = BloodTest::factory()->for($user)->create(['tested_at' => '2026-06-01']);

    BiomarkerResult::factory()->for($oldTest)->for($biomarker)->create(['value' => 5.1, 'unit' => 'mmol/L']);
    BiomarkerResult::factory()->for($newTest)->for($biomarker)->create(['value' => 5.7, 'unit' => 'mmol/L']);

    $this->actingAs($user)
        ->get("/biomarkers/{$biomarker->id}")
        ->assertOk()
        ->assertSeeInOrder(['2026-05-01', '5.1000', '2026-06-01', '5.7000']);
});

it('compares two blood tests for overlapping biomarkers', function () {
    $user = User::factory()->create();
    $biomarker = Biomarker::factory()->for($user)->create(['name' => 'Glucose']);
    $oldTest = BloodTest::factory()->for($user)->create(['tested_at' => '2026-05-01']);
    $newTest = BloodTest::factory()->for($user)->create(['tested_at' => '2026-06-01']);

    BiomarkerResult::factory()->for($oldTest)->for($biomarker)->create([
        'value' => 5.1,
        'unit' => 'mmol/L',
        'status' => BiomarkerStatus::Normal,
    ]);

    BiomarkerResult::factory()->for($newTest)->for($biomarker)->create([
        'value' => 5.7,
        'unit' => 'mmol/L',
        'status' => BiomarkerStatus::Normal,
    ]);

    $this->actingAs($user)
        ->get("/blood-tests/compare?from={$oldTest->id}&to={$newTest->id}")
        ->assertOk()
        ->assertSee('Glucose')
        ->assertSee('5.1000')
        ->assertSee('5.7000')
        ->assertSee('+0.6000');
});
```

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/BloodValues/BiomarkerHistoryAndCompareTest.php
```

Expected: FAIL because history and compare routes do not exist.

- [ ] **Step 3: Implement compare domain class**

Create `app/Domain/BloodTests/CompareBloodTests.php`:

```php
<?php

namespace App\Domain\BloodTests;

use App\Models\BloodTest;
use Illuminate\Support\Collection;

class CompareBloodTests
{
    public function __invoke(BloodTest $from, BloodTest $to): Collection
    {
        $fromResults = $from->results()->with('biomarker')->get()->keyBy('biomarker_id');
        $toResults = $to->results()->with('biomarker')->get()->keyBy('biomarker_id');

        return $fromResults->keys()
            ->merge($toResults->keys())
            ->unique()
            ->map(function (int $biomarkerId) use ($fromResults, $toResults) {
                $old = $fromResults->get($biomarkerId);
                $new = $toResults->get($biomarkerId);
                $biomarker = $new?->biomarker ?? $old?->biomarker;

                $delta = null;

                if ($old && $new && $old->unit === $new->unit) {
                    $delta = (float) $new->value - (float) $old->value;
                }

                return [
                    'biomarker' => $biomarker,
                    'old' => $old,
                    'new' => $new,
                    'delta' => $delta,
                    'comparable' => $old && $new && $old->unit === $new->unit,
                ];
            })
            ->sortBy(fn (array $row) => $row['biomarker']->name)
            ->values();
    }
}
```

- [ ] **Step 4: Implement authenticated history and compare routes**

History route:

```php
Route::get('/biomarkers/{biomarker}', ...)->name('biomarkers.show');
```

Guard:

```php
abort_unless($biomarker->user_id === request()->user()->id, 403);
```

Compare route:

```php
Route::get('/blood-tests/compare', ...)->name('blood-tests.compare');
```

Compare guards:

```php
$from = BloodTest::whereKey(request('from'))->where('user_id', request()->user()->id)->firstOrFail();
$to = BloodTest::whereKey(request('to'))->where('user_id', request()->user()->id)->firstOrFail();
```

- [ ] **Step 5: Run history and comparison tests**

Run:

```bash
php artisan test tests/Feature/BloodValues/BiomarkerHistoryAndCompareTest.php
```

Expected: PASS.

- [ ] **Step 6: Run full validation and commit**

Run:

```bash
sh scripts/validate.sh
git add app routes resources tests
git commit -m "feat: add biomarker history and test comparison"
```

Expected:

```text
Validation passed.
[main <hash>] feat: add biomarker history and test comparison
```

## Task 8: First Slice Closeout

**Files:**
- Modify: `docs/session-handoff.md`
- Verify: `scripts/validate.sh`

- [ ] **Step 1: Run full validation**

Run:

```bash
sh scripts/validate.sh
```

Expected:

```text
Validation passed.
```

- [ ] **Step 2: Review git log**

Run:

```bash
git log --oneline -8
```

Expected: recent commits include scaffold, validation switch, status calculation, data model, blood test flow, result entry, and comparison.

- [ ] **Step 3: Update `docs/session-handoff.md`**

Record:

```markdown
- Latest meaningful local checkpoint:
  - `<hash> feat: add biomarker history and test comparison`
- What was validated:
  - `sh scripts/validate.sh` passed after first vertical slice.
- Known gaps:
  - Document upload, context notes, pinned biomarkers, consult export, reminders, and data export/delete remain outside the first slice.
- Next recommended action:
  - Choose the next V1 slice: pinned biomarkers and dashboard, or document upload/privacy download.
```

- [ ] **Step 4: Commit handoff**

Run:

```bash
git add docs/session-handoff.md
git commit -m "docs: record first slice checkpoint"
```

Expected:

```text
[main <hash>] docs: record first slice checkpoint
```

## Self-Review

Spec coverage:

- Auth and private ownership: Tasks 1, 4, 5, 6, 7.
- Blood tests: Tasks 4 and 5.
- Biomarker catalog: Tasks 4 and 6.
- Manual result entry: Task 6.
- Status calculation: Task 3 and Task 6.
- Biomarker history/trend foundation: Task 7.
- Compare two tests: Task 7.
- Planning validation and later Laravel validation: Tasks 0 and 2.

Known V1 gaps intentionally excluded from the first slice:

- document upload;
- pinned biomarkers;
- context notes;
- consult overview/export;
- reminders;
- full data export/delete;
- polished charts.

These are V1 features, but not first-slice features. They should each get a
separate task plan after the core data loop is proven.

Placeholder scan:

- This plan avoids unresolved placeholder steps and hidden deferred-work steps.

Type consistency:

- Status enum values are `low`, `normal`, `high`, and `unknown`.
- Model names used consistently: `BloodTest`, `BiomarkerCategory`, `Biomarker`,
  `BiomarkerResult`.
- Domain class names used consistently: `DetermineBiomarkerStatus`,
  `CompareBloodTests`.

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-06-16-first-vertical-slice.md`.

Two execution options:

1. Subagent-Driven (recommended): dispatch a fresh subagent per task, review
   between tasks, fast iteration.
2. Inline Execution: execute tasks in this session using executing-plans, batch
   execution with checkpoints.

Do not start either execution path until Christophe explicitly chooses it.
