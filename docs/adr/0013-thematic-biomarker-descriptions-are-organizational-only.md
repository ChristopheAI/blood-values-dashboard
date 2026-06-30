# ADR-0013: Thematic Biomarker Descriptions Are Organizational Only

## Status

Accepted

## Context

Users think in themes such as inflammation, thyroid, or lipids, but the app currently
shows confirmed biomarker values in flat attention, normal, and timeline sections.
Grouping by owner-scoped `BiomarkerCategory` can improve consult and dashboard
scanability without changing the confirmed-only trust boundary.

Optional marker descriptions can reduce confusion about lab naming, but this app must
not become an education product that interprets results, predicts risk, or encourages
extra testing.

## Decision

Add a thematic biomarker overview that:

1. Groups confirmed-only values by owner-scoped category for selected blood test scope.
2. Reuses existing longitudinal trend labels; no new comparison logic.
3. Attaches optional neutral descriptions from a local audited static reference map
   (`config/biomarker_reference_descriptions.php`), looked up by normalized marker name.
4. Omits descriptions when no audited match exists; never fabricates copy at runtime.
5. Keeps **Overig** for uncategorized confirmed markers instead of hiding them.
6. Stays additive to existing consult attention/normal/trend sections in v1 of this slice.
7. Excludes theme descriptions from CSV export in the first slice.

Allowed description tone:

- "Wordt in labrapporten vermeld als…"
- "Onderdeel van het totaal bloedbeeld / differentiatie…"
- "Meet een waarde die labs often groeperen onder…"

Forbidden description tone:

- voorspelt, risico, behandelen, advies, optimaliseren, extra testen, urgentie
- causal claims such as "bij ontsteking is dit altijd hoog"

Out of scope for this ADR:

- runtime AI/LLM descriptions;
- medical interpretation, risk prediction, optimal ranges, or advice;
- auto-categorization during CMA intake;
- full biomarker catalog CRUD UI.

## Evidence

- Source: `docs/superpowers/plans/2026-06-27-thematic-biomarker-overview.md`
  - Claim type: inference
  - Summary: Theme grouping improves navigation; static copy must stay organizational.
- Source: `docs/agent-learnings.md` (2026-06-24)
  - Claim type: inference
  - Summary: Result context must stay attached to unit, range, source, and trust state
    without becoming interpretation.

## Considered Options

- Option A: Theme grouping only, no descriptions.
- Option B: Theme grouping plus local static reference descriptions (chosen).
- Option C: Runtime AI-generated descriptions per marker.

## Decision Drivers

- Improve consult/dashboard scanability without expanding privacy boundary.
- Keep copy auditable and testable in config, not generated at runtime.
- Preserve confirmed-only and owner-scoped invariants.

## Consequences

- New domain builder `BuildThematicBiomarkerOverview` and reference lookup class.
- Consult pack and dashboard gain an additive **Per thema** section with
  `include_themes` flag.
- Category assignment remains seeder/manual in Phase A; most markers may appear under
  **Overig** until catalog UI or deterministic mapping arrives.
- Reference config requires forbidden-word regression tests.

## Confidence

High

## Follow-Up Questions

- When should deterministic name-to-category mapping during intake be proposed?
- Should CSV export gain a separate theme section in a later slice?
