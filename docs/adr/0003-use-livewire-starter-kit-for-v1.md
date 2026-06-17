# ADR-0003: Use Laravel Livewire Starter Kit For V1

## Status

Accepted as current V1 direction

## Context

The project needs a private authenticated Laravel application for manual blood
test entry, biomarker results, status labels, trend/history views, comparison,
context notes, and later export/delete behavior.

The first version should optimize for clear server-side workflows, boring
Laravel defaults, and testable domain logic rather than frontend novelty.

## Decision

Use the official Laravel Livewire starter kit as the expected V1 foundation
when implementation begins.

Keep domain logic outside Livewire components. Livewire may orchestrate the UI,
but status calculation, comparison rules, owner scoping, export rules, and
privacy-sensitive behavior belong in testable Laravel application/domain code.

## Evidence

- Source: `docs/research/laravel-stack-decision.md`
  - Claim type: inference
  - Summary: Livewire is the likely V1 default for a private, authenticated,
    workflow-heavy, moderately interactive Laravel app.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: The V1 spec records Livewire starter kit as default assumption and
    explicitly keeps domain logic outside Livewire components.

## Considered Options

- Laravel Livewire starter kit.
- Laravel Breeze / Blade-only.
- Inertia with React or Vue.
- Filament-first admin panel.
- Non-Laravel stack.

## Decision Drivers

- V1 is workflow-heavy and personal, not a public marketing surface.
- The data model and privacy rules matter more than rich client-side state.
- Livewire keeps the first implementation close to Laravel conventions.
- Filament could pull the product toward a generic admin panel too early.
- Inertia/React adds frontend surface area before V1 proves the core workflow.

## Consequences

- Implementation should start from Laravel Livewire starter kit unless the
  review gate changes this decision.
- Validation must include Laravel tests after scaffold.
- Livewire components must not become the only place where medical-boundary,
  privacy, or status semantics live.

## Confidence

Medium-high

## Follow-Up Questions

- Does the pre-scaffold reviewer confirm Livewire is appropriate for V1?
- At what point, if any, does Filament become useful for catalog management?
