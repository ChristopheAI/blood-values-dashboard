# Laravel Stack Decision Research: Blood Values Dashboard V1

Date: 2026-06-16

## 1. Decision

Use the official Laravel Livewire starter kit as the likely V1 application
foundation.

Do not start V1 with Inertia/React/Vue. Do not start V1 as a Filament-only app.

Recommended stack direction for the first implementation spec:

- Laravel application with the Livewire starter kit.
- Blade/Livewire for authenticated personal workflows.
- Keep domain logic in Laravel services/actions/models, not inside Livewire
  views.
- Defer Filament until there is a clear admin/catalog-management need that
  would be faster as a panel.
- Defer Inertia until the app demonstrably needs rich client-side state,
  stronger TypeScript boundaries, or a React/Vue ecosystem.

This is a planning decision, not an implementation step.

## 2. Intents

Decision being made:

- Which Laravel frontend/application stack should V1 use for a personal blood
  values dashboard?

Decision owner:

- Christophe, building a first serious Laravel project with Codex support.

Constraints:

- V1 must stay small, reviewable, and privacy-conscious.
- V1 is a personal health-data organizer, not a medical advice product.
- The core work is data entry, status calculation, trends, comparisons, context
  notes, export/delete, and consult preparation.
- The project should teach useful Laravel platform thinking, not become a JS
  framework exercise.
- No Laravel code should be scaffolded until the V1 spec and task plan exist.

Decision goal:

- Minimize cost per useful task for V1 while preserving a path to a cleaner,
  more custom app later.

## 3. Evaluation Axes

- Cost per useful task: how quickly the stack helps build validated V1 behavior.
- Reliability: maturity, maintenance, upgrade path, and failure surface.
- Privacy/security fit: ability to keep sensitive workflows server-centered and
  testable.
- UI fit: forms, tables, charts, compare views, consult export, and reminders.
- Debuggability: how easy it is to understand failures.
- Lock-in and rollback: how painful it is to change direction later.
- Learning value: whether the stack teaches Laravel platform fundamentals.

## 4. Queries Used

Representative queries:

- `Laravel official starter kits Livewire React Vue 2026`
- `Laravel Livewire Inertia performance benchmark comparison`
- `Livewire vs Inertia performance benchmark Laravel`
- `Filament performance Laravel admin panel Livewire`
- `site:github.com/livewire/livewire issues performance Livewire morph request Laravel`
- `site:github.com/filamentphp/filament discussions Livewire performance Laravel admin panel`
- `site:github.com/inertiajs/inertia-laravel issues Laravel React Vue forms validation`
- `site:news.ycombinator.com Laravel Livewire Inertia`
- `site:news.ycombinator.com Filament PHP Laravel admin panel`
- `site:x.com Povilas Korop Livewire Inertia Laravel`

## 5. Findings

### A-grade: Official And Operational Facts

- Laravel's current official starter kits include React, Svelte, Vue, and
  Livewire. The React/Svelte/Vue kits use Inertia; the Livewire kit is described
  as a fit for teams that primarily use Blade and want a simpler alternative to
  JavaScript-driven SPA frameworks.
  Source: https://laravel.com/docs/13.x/starter-kits

- Inertia has an official Laravel adapter and Laravel starter kits are described
  by Inertia docs as the fastest way to start Laravel/Inertia projects.
  Source: https://inertiajs.com/docs/v3/installation/server-side-setup

- Livewire is an actively maintained Laravel full-stack UI framework. Packagist
  shows Livewire 4.3.1, Laravel 10-13 support, high installs, and active
  browser/unit testing guidance.
  Source: https://packagist.org/packages/livewire/livewire
  Source: https://github.com/livewire/livewire/releases

- Filament is a Laravel UI framework for apps and admin panels, built with
  Livewire. Current docs emphasize resources, forms, tables, actions, widgets,
  notifications, deployment, and upgrading.
  Source: https://filamentphp.com/docs

- Filament has strong adoption and active releases. Packagist shows
  `filament/filament` as a full-stack component collection, with v5.6.7 current
  on 2026-06-08.
  Source: https://packagist.org/packages/filament/filament

- Inertia's Laravel adapter is mature and actively maintained; Packagist shows
  `inertiajs/inertia-laravel` v3.1.0, Laravel 11-13 support, and low open issue
  count at the time checked.
  Source: https://packagist.org/packages/inertiajs/inertia-laravel

Decision utility:

- All three serious options are viable. The choice is not "which one is good?",
  but "which one reduces V1 risk for this specific app?"

### B-grade: Expert / Practitioner Synthesis

- Spatie uses both Livewire and Inertia. Their rule of thumb: Inertia wins when
  end-to-end type safety and the React ecosystem matter; Livewire wins when the
  project should rely more on backend technologies or needs incremental
  adaptation.
  Source: https://spatie.be/blog/livewire-and-inertia-how-we-love-and-use-both

- Laravel News frames Livewire as the backend/Laravel comfort-zone option and
  Inertia as the Vue/React option for teams that want a JS frontend without a
  separate API.
  Source: https://laravel-news.com/livewire-inertia

- ScalablePath stresses that Livewire and Inertia are different approaches, not
  interchangeable tools. Both simplify SPA-like Laravel development, but they
  optimize for different teams and mental models.
  Source: https://www.scalablepath.com/php/livewire-vs-inertia

- A real-development comparison found Livewire faster for DataTable-style
  implementation speed, while Inertia/Vue was stronger for complex/nested
  client state and "real reactivity."
  Source: https://www.desarrollolibre.net/blog/laravel/laravel-livewire-vs-laravel-inertia-with-vue-a-comparison-of-real-developments

- A Filament field report says Filament is excellent for MVPs, internal tools,
  backoffice UIs, and admin dashboards, but can become frustrating past the
  happy path when heavy interactivity, pixel-perfect UI, or custom JS logic are
  required.
  Source: https://dev.to/tonegabes/how-filament-saved-or-complicated-my-admin-panel-an-honest-review-156b

Decision utility:

- For this V1, Livewire has the best cost/useful-task ratio because most value
  is server-side workflow plus moderate interactivity, not a rich JS app.
- Inertia becomes more attractive if trend charts, overlays, and compare screens
  become client-heavy.
- Filament becomes more attractive if the first slice is mostly data management
  rather than a user-facing personal dashboard.

### B/C-grade: Operational Friction Signals

- Livewire friction appears around morphing, component boundaries, redirects,
  large components, and deployment-specific behavior.
  Sources:
  - https://github.com/livewire/livewire/discussions/9085
  - https://github.com/livewire/livewire/discussions/7526
  - https://github.com/livewire/livewire/discussions/8270
  - https://github.com/livewire/livewire/discussions/7903

- Filament friction appears around table/admin performance, asset/deployment
  setup, plugin/version compatibility, and the fact that Filament is layered on
  Laravel + Livewire + its own abstractions.
  Sources:
  - https://github.com/filamentphp/filament/discussions/5654
  - https://github.com/filamentphp/filament/discussions/5098
  - https://github.com/filamentphp/filament/discussions/13727
  - https://github.com/filamentphp/filament/discussions/9816

- Inertia friction appears around validation/errors/redirect behavior and
  frontend/backend coordination. The issue count is not high, but the model
  requires comfort with a JS frontend stack.
  Sources:
  - https://github.com/inertiajs/inertia-laravel/issues/356
  - https://github.com/inertiajs/inertia-laravel/issues/520
  - https://github.com/inertiajs/inertia-laravel/issues/170

Decision utility:

- None of these are blockers.
- They shape mitigations: keep Livewire components small, do not hide domain
  logic in components, avoid Filament as the entire app until custom UX needs
  are known, and avoid Inertia unless client-side complexity pays for itself.

### B/C-grade: Practitioner Channels

- Reddit: practitioners commonly describe Livewire as fast for solo/smaller
  projects and Inertia as stronger when the user needs richer client-side state.
  The same thread contains warnings that large Livewire forms/components can
  become heavy.
  Source: https://www.reddit.com/r/laravel/comments/s9za3a/what_are_your_honest_thoughts_about_livewire_vs/

- Reddit: Filament users report fast CRUD/internal-tool delivery, but also note
  performance issues, learning curve, and tension when an app already uses
  Inertia/Vue/React.
  Source: https://www.reddit.com/r/laravel/comments/185rwo1/how_many_of_you_are_using_filament/

- Reddit: Livewire 4 reactions are mixed. Some like the new structure and
  backwards compatibility path; others dislike single-file/component structure
  or report past upgrade pain.
  Sources:
  - https://www.reddit.com/r/laravel/comments/1qcqac4/everything_new_in_livewire_4/
  - https://www.reddit.com/r/laravel/comments/1qf075r/livewire_4_deep_dive_components_performance_new/

- Hacker News: Filament has strong positive practitioner comments for admin
  panels, including comparisons against Nova, but there is also caution around
  relying on Livewire stability during major upgrades.
  Source: https://news.ycombinator.com/item?id=36964072

- Hacker News: Livewire has both fans and critics. Critics point to magic,
  rehydration, server round-trips, and performance for complex UI. Fans point to
  productivity for simple and moderate app workflows.
  Sources:
  - https://news.ycombinator.com/item?id=28849802
  - https://news.ycombinator.com/item?id=37481930
  - https://news.ycombinator.com/item?id=29892140

- Hacker News: Inertia also has split reactions. Some praise it as a bridge
  between Laravel/Rails/Django and frontend frameworks without a JSON API; at
  least one practitioner reported regretting Inertia and preferring a more
  explicit API + React Router style.
  Sources:
  - https://news.ycombinator.com/item?id=41465900
  - https://news.ycombinator.com/item?id=42720071
  - https://news.ycombinator.com/item?id=29934191

- X/Twitter: a Laravel education/practitioner signal from Povilas Korop shows
  real-world jobs asking for all three paths: Inertia with Vue/React, Laravel
  APIs, and Livewire. This supports learning one pragmatic stack first, not
  pretending there is one universal winner.
  Source: https://x.com/PovilasKorop/status/1957775323229016103

Decision utility:

- Practitioner evidence supports a pragmatic choice: Livewire for the V1
  Laravel-first personal dashboard, with clear escape hatches.

## 6. Option Scores

Scores are relative for this project, not universal.

| Option | Cost/useful task | V1 fit | Custom UX | Debuggability | Rollback | Verdict |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Blade/controllers only | 3 | 3 | 3 | 5 | 5 | Good fallback, too bare for reactive V1 |
| Livewire starter kit | 5 | 5 | 4 | 3 | 4 | Best V1 default |
| Filament-only app | 5 for CRUD, 3 for product UX | 3 | 2 | 3 | 3 | Great admin accelerator, not main V1 UI |
| Inertia + Vue/React | 3 | 3 | 5 | 4 | 3 | Better later if client state dominates |
| Inertia app + Filament admin | 2 | 2 | 5 | 2 | 2 | Too much for V1 |

## 7. Failure Modes In Practice

### Livewire

- Components grow too large and become hard to reason about.
- Round-trips feel bad for highly interactive chart or compare interactions.
- DOM morphing / lifecycle behavior creates surprising UI bugs.
- Developers hide domain rules inside component methods.

Mitigations:

- Keep Livewire components as thin workflow/UI components.
- Put status calculation, comparison, export, and privacy rules in domain code.
- Use ordinary Blade/controller pages when reactivity is unnecessary.
- Use small islands/components for specific interactions instead of one giant
  dashboard component.
- Add browser smoke tests for the core flows once implementation starts.

### Filament

- App starts to feel like an admin panel rather than a personal health tool.
- Custom product UX fights Filament conventions.
- Performance issues appear in complex tables/resources.
- Plugin/version compatibility can delay upgrades.

Mitigations:

- Do not make Filament the default app shell for V1.
- Consider Filament later for biomarker catalog management or private admin
  screens only.
- Keep domain model independent from Filament resources.

### Inertia

- V1 becomes a React/Vue learning project instead of a Laravel platform project.
- More frontend files, build tooling, state, forms, and component decisions.
- Easier to overbuild chart interactions before the data model is proven.

Mitigations:

- Defer until there is a concrete client-state pain.
- If adopted later, use it for specific app surfaces rather than rewriting the
  whole product prematurely.

## 8. Proven Workarounds

- Start with Livewire for auth-protected CRUD/workflow screens, but keep the
  domain model independent.
- Use small, replaceable chart components. The charting choice should be made in
  the V1 spec or first task plan, not in the stack decision.
- Use Filament only when a resource-management surface is clearly admin-like.
- Keep export/delete/status calculation covered by tests before adding UI polish.
- Avoid premature OCR, AI interpretation, provider integrations, and wearable
  imports.

## 9. Recommendation And Why Alternatives Lose

Recommendation:

- Choose the Laravel Livewire starter kit for V1.

Why it wins:

- It matches the project shape: personal, authenticated, data/workflow-heavy,
  Laravel-first, moderate interactivity.
- It has official Laravel starter-kit support.
- It keeps the first project focused on Laravel domain modeling instead of a
  separate frontend framework.
- It has enough interactivity for forms, pinned biomarkers, compare screens, and
  dashboard widgets.
- It can coexist with plain Blade and later Filament if needed.

Why Blade-only loses:

- Too little built-in reactive structure for a dashboard with compare, pins,
  inline validation, and trend exploration.

Why Filament-only loses:

- Fastest for admin CRUD, but the product is a personal health dashboard and
  consult-prep tool. It needs tailored user workflows, not only resource pages.

Why Inertia loses for V1:

- It is stronger for rich client-side state and React/Vue/TypeScript work, but
  that is not the first risk. The first risk is getting the domain data,
  privacy boundaries, status logic, and workflows correct.

Why hybrid Inertia + Filament loses:

- It adds two UI stacks before V1 has proven one core workflow.

## 10. Top 3 Next Actions

1. Owner: Codex
   Effort: Medium
   Risk: Low
   Rollback: Delete/rewrite one doc
   Action: Write `docs/v1-spec.md` using Livewire starter kit as the default
   assumption, while keeping domain logic independent of Livewire.

2. Owner: Christophe + Codex
   Effort: Low
   Risk: Medium
   Rollback: Edit spec before scaffolding
   Action: Decide whether Filament is needed in V1 for biomarker catalog
   management or whether normal app screens are enough.

3. Owner: Codex
   Effort: Medium
   Risk: Low
   Rollback: Replace task plan before implementation
   Action: Create a first vertical-slice task plan: auth, blood test creation,
   manual biomarker values, status calculation, simple trend, compare two
   tests.

## 11. Signal Distortions

- Official docs naturally emphasize the happy path.
- Reddit/HN comments overrepresent developers with strong positive or negative
  experiences.
- Older Livewire criticism may refer to v2/v3 behavior and may not map cleanly
  to Livewire 4.
- Filament performance complaints often depend on table size, queries, debug
  tooling, deployment, and plugin state.
- Inertia criticism often depends on whether the team wanted a JS app in the
  first place.
- There are few rigorous apples-to-apples benchmarks for this exact choice; most
  "benchmarks" are practitioner comparisons, package adoption, or case-level
  experience.

## 12. Source Appendix By Platform

Official:

- Laravel starter kits: https://laravel.com/docs/13.x/starter-kits
- Inertia Laravel setup: https://inertiajs.com/docs/v3/installation/server-side-setup
- Livewire releases: https://github.com/livewire/livewire/releases
- Livewire Packagist: https://packagist.org/packages/livewire/livewire
- Filament docs: https://filamentphp.com/docs
- Filament Packagist: https://packagist.org/packages/filament/filament
- Inertia Laravel Packagist: https://packagist.org/packages/inertiajs/inertia-laravel
- Filament v5 / Livewire v4 note: https://filamentphp.com/content/danharrin-filament-v5-blueprint

Operational:

- Livewire redirect/update discussion: https://github.com/livewire/livewire/discussions/9085
- Livewire morph markers discussion: https://github.com/livewire/livewire/discussions/7526
- Livewire performance/loading discussion: https://github.com/livewire/livewire/discussions/8270
- Livewire partial renders proposal: https://github.com/livewire/livewire/discussions/7903
- Filament slow admin discussion: https://github.com/filamentphp/filament/discussions/5654
- Filament table performance discussion: https://github.com/filamentphp/filament/discussions/5098
- Filament panel performance discussion: https://github.com/filamentphp/filament/discussions/13727
- Filament Livewire not found discussion: https://github.com/filamentphp/filament/discussions/9816
- Inertia validation issue: https://github.com/inertiajs/inertia-laravel/issues/356
- Inertia validation redirect issue: https://github.com/inertiajs/inertia-laravel/issues/520
- Inertia updated data issue: https://github.com/inertiajs/inertia-laravel/issues/170

Benchmarks / independent evals / practitioner articles:

- Spatie on Livewire and Inertia: https://spatie.be/blog/livewire-and-inertia-how-we-love-and-use-both
- Laravel News on Livewire vs Inertia: https://laravel-news.com/livewire-inertia
- ScalablePath comparison: https://www.scalablepath.com/php/livewire-vs-inertia
- Real development comparison: https://www.desarrollolibre.net/blog/laravel/laravel-livewire-vs-laravel-inertia-with-vue-a-comparison-of-real-developments
- Filament field report: https://dev.to/tonegabes/how-filament-saved-or-complicated-my-admin-panel-an-honest-review-156b

Practitioner channels:

- Reddit Livewire vs Inertia: https://www.reddit.com/r/laravel/comments/s9za3a/what_are_your_honest_thoughts_about_livewire_vs/
- Reddit Filament usage: https://www.reddit.com/r/laravel/comments/185rwo1/how_many_of_you_are_using_filament/
- Reddit Livewire 4: https://www.reddit.com/r/laravel/comments/1qcqac4/everything_new_in_livewire_4/
- Reddit Livewire 4 deep dive: https://www.reddit.com/r/laravel/comments/1qf075r/livewire_4_deep_dive_components_performance_new/
- HN Filament v3: https://news.ycombinator.com/item?id=36964072
- HN Livewire criticism: https://news.ycombinator.com/item?id=28849802
- HN Inertia regret / counterpoint: https://news.ycombinator.com/item?id=42720071
- HN Inertia overview discussion: https://news.ycombinator.com/item?id=41465900
- X/Povilas Korop job-stack signal: https://x.com/PovilasKorop/status/1957775323229016103

