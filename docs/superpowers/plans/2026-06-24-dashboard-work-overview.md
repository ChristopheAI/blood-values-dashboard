# Dashboard Work Overview Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework `/dashboard` from a latest-upload digest into a calm work overview that answers: what needs review, what was last confirmed, where is the blood-test timeline, and what is ready for consult preparation.

**Architecture:** Keep the existing Laravel/Livewire app boundaries. Add a small dashboard domain builder that derives owner-scoped, confirmed-only dashboard state; keep authorization and trust filtering out of Blade. Reuse the existing latest upload summary partial below the new work overview, but make it secondary.

**Tech Stack:** Laravel, Blade, Flux UI components, Pest feature tests, existing Dusk/browser QA through `scripts/validate.sh`.

---

## Scope

This is a dashboard clarity slice only.

In scope:

- `/dashboard` information hierarchy.
- Owner-scoped dashboard overview data.
- Confirmed-only latest values.
- Draft/review counts as review prompts, without exposing draft values downstream.
- Compact blood-test timeline.
- Consult preparation entry point.
- Dutch operational copy.

Out of scope:

- Parser changes.
- Runtime AI, OCR, external processing, provider sync, wearable sync.
- Medical advice, urgency, diagnosis, scores, supplement/training recommendations.
- Settings/reminders/context category cleanup from the separate clarity pass.

## File Structure

- Modify `app/Http/Controllers/DashboardController.php`
  - Inject a new dashboard overview builder.
  - Pass one structured `$dashboardOverview` payload to the view.

- Create `app/Domain/Dashboard/BuildDashboardOverview.php`
  - Build owner-scoped dashboard state.
  - Count review drafts by owned blood tests.
  - Count confirmed values through `confirmed_at`.
  - List recent blood tests with status, confirmed count, draft count, document count.
  - Select a neutral next-step message.

- Modify `resources/views/dashboard.blade.php`
  - Put `Volgende stap` first.
  - Put `Bloedtesten` timeline second.
  - Move latest confirmed values below the timeline.
  - Keep reminders, followed biomarkers, and quick actions lower.

- Create `resources/views/dashboard/_next-step.blade.php`
  - Render the top task block.

- Create `resources/views/dashboard/_blood-test-timeline.blade.php`
  - Render compact owned blood-test timeline.

- Modify `resources/views/dashboard/_blood-results-overview.blade.php`
  - Make it work as a secondary block under the dashboard timeline.
  - Keep confirmed-only wording.

- Modify `tests/Feature/DashboardTest.php`
  - Add tests for next-step priority.
  - Add timeline tests for owner scope, confirmed counts, draft counts, document counts.
  - Update assertions that assumed latest upload dominates the page.

- Optional if browser smoke assertions depend on dashboard order: modify `tests/Browser/PdfFirstIntakeSmokeTest.php`.

## Language Contract

- "Dashboard" means the authenticated start page.
- "Volgende stap" means a workflow prompt, not medical advice.
- "Aandacht" remains a range/status description only.
- "Review" means values awaiting owner confirmation.
- "Consultlijst" means print/export preparation for a doctor conversation, not advice.

## Task 1: Add Dashboard Overview Builder

**Files:**

- Create: `app/Domain/Dashboard/BuildDashboardOverview.php`
- Test: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Write failing tests for overview priorities**

Add tests to `tests/Feature/DashboardTest.php`:

```php
public function test_dashboard_prompts_review_when_owned_extracted_drafts_remain(): void
{
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create([
        'title' => 'June blood test',
        'test_date' => '2026-06-15',
        'status' => 'reviewing',
    ]);
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Ferritin']);

    BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
        'value' => 42,
        'unit' => 'ug/L',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-next-step"', false)
        ->assertSee('Waarden nakijken')
        ->assertSee('1 waarde wacht op review')
        ->assertSee('June blood test');
}

public function test_dashboard_prompts_upload_when_no_blood_tests_exist(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-next-step"', false)
        ->assertSee('Eerste lab-PDF toevoegen')
        ->assertSee('Sleep je lab-PDF hierheen');
}
```

- [ ] **Step 2: Run focused test and confirm failure**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php --filter='dashboard prompts'
```

Expected: fails because `dashboard-next-step`, `Waarden nakijken`, and `Eerste lab-PDF toevoegen` are not rendered yet.

- [ ] **Step 3: Implement `BuildDashboardOverview`**

Create `app/Domain/Dashboard/BuildDashboardOverview.php`:

```php
<?php

namespace App\Domain\Dashboard;

use App\Models\BloodTest;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildDashboardOverview
{
    /**
     * @return array{
     *     nextStep: array{kind: string, title: string, body: string, href: string, action: string},
     *     bloodTests: Collection<int, array{id: int, title: string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>,
     *     reviewDraftCount: int,
     *     confirmedValueCount: int,
     *     bloodTestCount: int
     * }
     */
    public function __invoke(User $user): array
    {
        $bloodTests = BloodTest::query()
            ->where('user_id', $user->id)
            ->withCount([
                'documents',
                'results as confirmed_results_count' => fn ($query) => $query
                    ->whereNotNull('confirmed_at')
                    ->whereHas('biomarker', fn ($query) => $query->where('user_id', $user->id)),
                'results as draft_results_count' => fn ($query) => $query
                    ->whereNull('confirmed_at')
                    ->where('entry_source', 'extracted')
                    ->where(function ($query) use ($user): void {
                        $query
                            ->whereNull('biomarker_id')
                            ->orWhereHas('biomarker', fn ($query) => $query->where('user_id', $user->id));
                    }),
            ])
            ->recentFirst()
            ->limit(5)
            ->get();

        $timeline = $bloodTests->map(fn (BloodTest $bloodTest): array => [
            'id' => $bloodTest->id,
            'title' => $bloodTest->title ?: 'Bloedtest zonder titel',
            'href' => route('blood-tests.show', $bloodTest),
            'date' => $bloodTest->test_date?->translatedFormat('j F Y') ?? 'Geen datum',
            'status' => $bloodTest->status,
            'confirmedCount' => (int) $bloodTest->confirmed_results_count,
            'draftCount' => (int) $bloodTest->draft_results_count,
            'documentCount' => (int) $bloodTest->documents_count,
        ]);

        $reviewDraftCount = $timeline->sum('draftCount');
        $confirmedValueCount = $timeline->sum('confirmedCount');

        return [
            'nextStep' => $this->nextStep($timeline, $reviewDraftCount, $confirmedValueCount),
            'bloodTests' => $timeline,
            'reviewDraftCount' => $reviewDraftCount,
            'confirmedValueCount' => $confirmedValueCount,
            'bloodTestCount' => $timeline->count(),
        ];
    }

    /**
     * @param  Collection<int, array{id: int, title: string, href: string, date: string, status: string, confirmedCount: int, draftCount: int, documentCount: int}>  $bloodTests
     * @return array{kind: string, title: string, body: string, href: string, action: string}
     */
    private function nextStep(Collection $bloodTests, int $reviewDraftCount, int $confirmedValueCount): array
    {
        if ($bloodTests->isEmpty()) {
            return [
                'kind' => 'upload',
                'title' => 'Eerste lab-PDF toevoegen',
                'body' => 'Start met je originele PDF. Waarden komen pas in overzichten na bevestiging.',
                'href' => route('blood-tests.index'),
                'action' => 'Lab-PDF uploaden',
            ];
        }

        if ($reviewDraftCount > 0) {
            $firstReviewBloodTest = $bloodTests->first(fn (array $bloodTest): bool => $bloodTest['draftCount'] > 0);

            return [
                'kind' => 'review',
                'title' => 'Waarden nakijken',
                'body' => $reviewDraftCount === 1
                    ? '1 waarde wacht op review voordat ze in overzichten komt.'
                    : $reviewDraftCount.' waarden wachten op review voordat ze in overzichten komen.',
                'href' => $firstReviewBloodTest['href'],
                'action' => 'Review openen',
            ];
        }

        if ($confirmedValueCount > 0) {
            return [
                'kind' => 'consult',
                'title' => 'Consultlijst voorbereiden',
                'body' => 'Gebruik alleen bevestigde waarden, bronbestanden en context voor een compact overzicht.',
                'href' => route('consult-overview.index'),
                'action' => 'Consultlijst openen',
            ];
        }

        return [
            'kind' => 'add-values',
            'title' => 'Waarden toevoegen',
            'body' => 'Er zijn bloedtesten, maar nog geen bevestigde waarden voor dashboard, trends of consult.',
            'href' => $bloodTests->first()['href'],
            'action' => 'Bloedtest openen',
        ];
    }
}
```

- [ ] **Step 4: Run focused test**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php --filter='dashboard prompts'
```

Expected: still fails until the controller/view render the payload.

## Task 2: Wire Dashboard Controller

**Files:**

- Modify: `app/Http/Controllers/DashboardController.php`
- Test: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Inject the new builder**

Change the controller signature and view payload:

```php
use App\Domain\Dashboard\BuildDashboardOverview;
use App\Domain\Dashboard\BuildLatestUploadSummary;
```

```php
public function __invoke(
    BuildDashboardOverview $buildDashboardOverview,
    BuildLatestUploadSummary $buildLatestUploadSummary,
): View {
    $user = Auth::user();

    return view('dashboard', [
        'dashboardOverview' => $buildDashboardOverview($user),
        'latestUploadSummary' => $buildLatestUploadSummary($user),
        'recentBloodTests' => $user->bloodTests()
            ->recentFirst()
            ->limit(5)
            ->get(),
        'pinnedBiomarkers' => PinnedBiomarker::query()
            ->forUserWithOwnedBiomarker($user->id)
            ->with('biomarker')
            ->latest()
            ->get(),
        'nextReminder' => $user->reminders()
            ->open()
            ->orderBy('due_date')
            ->orderBy('id')
            ->first(),
    ]);
}
```

- [ ] **Step 2: Run focused dashboard tests**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Expected: existing tests should still pass or fail only because the view has not rendered the new sections yet.

## Task 3: Render Next Step And Timeline Above Latest Values

**Files:**

- Create: `resources/views/dashboard/_next-step.blade.php`
- Create: `resources/views/dashboard/_blood-test-timeline.blade.php`
- Modify: `resources/views/dashboard.blade.php`
- Test: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add `_next-step` partial**

Create `resources/views/dashboard/_next-step.blade.php`:

```blade
<section class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-neutral-900" data-test="dashboard-next-step">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Volgende stap') }}</flux:heading>
            <div class="text-xl font-semibold text-neutral-900 dark:text-white">{{ __($nextStep['title']) }}</div>
            <flux:text>{{ __($nextStep['body']) }}</flux:text>
        </div>

        <flux:button :href="$nextStep['href']" variant="primary" class="shrink-0">
            {{ __($nextStep['action']) }}
        </flux:button>
    </div>
</section>
```

- [ ] **Step 2: Add `_blood-test-timeline` partial**

Create `resources/views/dashboard/_blood-test-timeline.blade.php`:

```blade
<section class="space-y-4" data-test="dashboard-blood-test-timeline">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Bloedtesten') }}</flux:heading>
        <flux:button :href="route('blood-tests.index')" variant="outline" size="sm">{{ __('Alle bloedtesten') }}</flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
        @forelse ($bloodTests as $bloodTest)
            <a href="{{ $bloodTest['href'] }}" class="block border-b border-neutral-100 p-4 last:border-b-0 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800/50" data-test="dashboard-blood-test-row">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="font-medium text-neutral-900 dark:text-white">{{ $bloodTest['title'] }}</div>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">{{ $bloodTest['date'] }}</div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                        <span class="rounded-full bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $bloodTest['confirmedCount'] }} {{ __('bevestigd') }}</span>
                        @if ($bloodTest['draftCount'] > 0)
                            <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">{{ $bloodTest['draftCount'] }} {{ __('review') }}</span>
                        @endif
                        @if ($bloodTest['documentCount'] > 0)
                            <span class="rounded-full bg-neutral-100 px-2 py-1 dark:bg-neutral-800">{{ $bloodTest['documentCount'] }} {{ __('bronbestand') }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="p-5 text-sm text-neutral-600 dark:text-neutral-400">
                {{ __('Nog geen bloedtesten. Upload je eerste lab-PDF om te starten.') }}
            </div>
        @endforelse
    </div>
</section>
```

- [ ] **Step 3: Reorder `dashboard.blade.php`**

Use this structure inside the main section:

```blade
@include('dashboard._next-step', ['nextStep' => $dashboardOverview['nextStep']])

@if ($recentBloodTests->isEmpty())
    @include('blood-tests._upload-dropzone')
@endif

@include('dashboard._blood-test-timeline', ['bloodTests' => $dashboardOverview['bloodTests']])

@if ($latestUploadSummary)
    <section class="space-y-4" data-test="dashboard-latest-confirmed-values">
        <flux:heading size="lg">{{ __('Laatste bevestigde waarden') }}</flux:heading>
        @include('dashboard._blood-results-overview', ['summary' => $latestUploadSummary])
    </section>
@endif
```

Keep the existing lower grid for followed biomarkers, next reminder, and quick actions after this block.

- [ ] **Step 4: Run focused tests**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Expected: new next-step tests pass. Existing tests may need assertion updates for changed section order or headings.

## Task 4: Add Timeline Privacy And Confirmed-Only Tests

**Files:**

- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add owner-scope timeline test**

Add:

```php
public function test_dashboard_timeline_lists_only_owned_blood_tests(): void
{
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    BloodTest::factory()->for($user)->create(['title' => 'Owned timeline test']);
    BloodTest::factory()->for($otherUser)->create(['title' => 'Foreign timeline test']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-blood-test-timeline"', false)
        ->assertSee('Owned timeline test')
        ->assertDontSee('Foreign timeline test');
}
```

- [ ] **Step 2: Add draft-value non-leak test**

Add:

```php
public function test_dashboard_timeline_counts_drafts_without_showing_draft_values(): void
{
    $user = User::factory()->create();
    $bloodTest = BloodTest::factory()->for($user)->create(['title' => 'Review test']);
    $marker = Biomarker::factory()->for($user)->create(['name' => 'Draft marker']);

    BiomarkerResult::factory()->for($bloodTest)->for($marker)->create([
        'value' => 999,
        'unit' => 'mg/L',
        'entry_source' => 'extracted',
        'confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1 review')
        ->assertDontSee('Draft marker')
        ->assertDontSee('999');
}
```

- [ ] **Step 3: Run focused tests**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php --filter='dashboard timeline'
```

Expected: pass after builder and view wiring.

## Task 5: Tighten Dashboard Copy And Lower Latest Values

**Files:**

- Modify: `resources/views/dashboard/_blood-results-overview.blade.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Keep latest values descriptive and secondary**

In `_blood-results-overview.blade.php`, keep the existing data-test selectors but change the top heading only when rendered under the new dashboard wrapper. Do not add medical urgency language. Acceptable copy:

```blade
<flux:heading size="xl">{{ __('Je bloedresultaten') }}</flux:heading>
```

The dashboard wrapper already provides:

```blade
<flux:heading size="lg">{{ __('Laatste bevestigde waarden') }}</flux:heading>
```

Do not change status copy to "urgent", "risk", "bad", "danger", "diagnosis", or "advies".

- [ ] **Step 2: Update old dominance assertions**

Where tests assert the latest upload digest as the main page, keep confirmed-only assertions but also assert the new dashboard framing:

```php
->assertSee('Volgende stap')
->assertSee('Bloedtesten')
->assertSee('Laatste bevestigde waarden')
->assertSee('data-test="dashboard-latest-confirmed-values"', false)
```

- [ ] **Step 3: Run all dashboard tests**

Run:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Expected: pass.

## Task 6: Browser QA And Full Validation

**Files:**

- Modify only if needed: `tests/Browser/PdfFirstIntakeSmokeTest.php`
- No code changes unless browser QA reveals a mismatch.

- [ ] **Step 1: Build assets**

Run:

```bash
npm run build
```

Expected: exits 0.

- [ ] **Step 2: Run full validator**

Run:

```bash
sh scripts/validate.sh
```

Expected: exits 0.

- [ ] **Step 3: Drive dashboard in browser**

Open:

```text
http://127.0.0.1:8000/dashboard
```

Verify visible behavior:

- `Volgende stap` appears first.
- If drafts exist, the next step is review, not consult.
- If no blood tests exist, upload is first.
- `Bloedtesten` timeline appears before latest values.
- Draft counts can appear, but draft biomarker names/values do not appear as dashboard values.
- `Laatste bevestigde waarden` uses confirmed rows only.
- No medical advice, urgency, diagnosis, scoring, supplement/training recommendation.
- No storage paths or private source snippets.
- No horizontal overflow on desktop or mobile.

- [ ] **Step 4: Commit**

Stage only touched files:

```bash
git add app/Domain/Dashboard/BuildDashboardOverview.php app/Http/Controllers/DashboardController.php resources/views/dashboard.blade.php resources/views/dashboard/_next-step.blade.php resources/views/dashboard/_blood-test-timeline.blade.php resources/views/dashboard/_blood-results-overview.blade.php tests/Feature/DashboardTest.php
git commit -m "fix: make dashboard a work overview"
```

If browser test files changed, stage them explicitly too.

## Self-Review Checklist

- Confirmed-only: latest value section still uses `BuildLatestUploadSummary` and `confirmed_at`.
- Owner scope: overview builder queries only `where('user_id', $user->id)` and owned biomarker relations.
- Drafts: dashboard may show draft counts and review action, but not draft values as downstream data.
- Privacy: no sensitive free text in GET URLs; no source paths; no external calls.
- Medical boundary: no diagnosis, urgency, treatment, supplement/training advice, health score, or extra testing encouragement.
- UX: dashboard answers "what now?" before showing detailed values.
- Validation: focused dashboard tests, `npm run build`, `sh scripts/validate.sh`, and browser QA all pass before handoff.
