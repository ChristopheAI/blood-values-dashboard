# ADR-0013: Confirmed-Only Blood Results Overview Surface

## Status

Accepted (2026-07-07)

Validation evidence at acceptance: `sh scripts/validate.sh` green on
2026-07-06/07 (Vite build, Pint, PHPStan 0 errors, 321 Pest tests incl. the
confirmed-only, tenant-isolation, and medical-copy boundary suites, 3 Dusk
smoke tests); a multi-agent code review of the working tree (8 finder angles,
findings fixed or refuted — see `docs/session-handoff.md`); and browser QA on
the seeded scenario (counts reconcile, drafts invisible, reading model renders,
no overflow — recorded in the task plan). Review and ratification were
owner-delegated to the working session on 2026-07-07.

## Context

`DESIGN.md` section 5 already specifies a "Blood Results Overview" component
family: one summary panel, one attention group, and one compact normal-values
panel, with confirmed `low`/`high` values allowed to use an amber featured card
and confirmed `normal`/`unknown` values rendered as compact rows.

Today that visual language exists only as a latest-upload digest on the
dashboard (`BuildLatestUploadSummary` rendered through
`resources/views/dashboard/_blood-results-overview.blade.php`). There is no
surface that shows all of the user's confirmed biomarkers grouped by status.

The owner requested this slice on 2026-07-02 as the next step of the intended
"thematic biomarker overview" direction. That roadmap phrasing is owner intent
from the request itself; it is not yet recorded in a repo roadmap document or
GitHub issue, which is part of why this ADR exists.

The confirmed-only trust boundary (ADR-0005, ADR-0009, ADR-0011) and the
non-medical-advice boundary (`AGENTS.md`, README Product Boundary) already
govern every downstream surface. A new overview surface must sit inside both
boundaries, not renegotiate them.

## Decision

Add a read-only, confirmed-only Blood Results Overview surface that shows the
user's confirmed biomarker values grouped by status:

- one summary panel with counts per status;
- one attention group for confirmed `low`/`high` values, using the restrained
  amber featured treatment from `DESIGN.md`;
- confirmed `normal` and `unknown` values as compact rows, not cards, with one
  calm green good-area for the normal group.

Data flows through a new owner-scoped domain builder
(`app/Domain/Dashboard/BuildBloodResultsOverview`) that maps
`BiomarkerResult::confirmedForUser($user->id)` to view rows. Status comes from
the `BiomarkerStatus` enum (`Low`, `High`, `Normal`, `Unknown`); the persisted
`status` column is the primary source, with `DetermineBiomarkerStatus` as the
deterministic derivation when a persisted status is absent or untrusted.

The surface shows name, value, unit, reference range, and status in words. It
does not interpret, explain meaning, or advise.

## Amendment (2026-07-06)

Reading-model research (NL/BE lab-report conventions, NVKC/Thuisarts patient
education, adversarial review against `MedicalCopyBoundaryTest`) showed the
"does not interpret" line was being read too broadly: the amber attention card
alarmed without the one piece of context every authoritative NL source pairs
with an out-of-range result. Two additions are ruled IN scope because they
state facts, not an interpretation of the user's value:

1. A generic education line, once per out-of-range section, explaining what a
   reference range *is* (lab-specific; healthy people also fall outside it),
   without any population statistic that a user-entered range may not support.
2. A factual, dated own-history line on attention cards (previous value with
   its date and delta), valence-free, guarded by `BuildLongitudinalChanges`
   comparability rules. Chart-style trend lines stay out of scope.

The distinction that holds the boundary: the surface may state what a
reference range is and what the user's own earlier numbers were, but still
never says what *this* value means for *this* person. "No individual-value
interpretation" replaces the broader "does not explain meaning".

## Stop Conditions

- No draft, unconfirmed, or extracted-but-unreviewed value may appear;
  `confirmed_at` through `confirmedForUser` remains the only entry path.
- No medical interpretation of an individual value, urgency, diagnosis,
  advice, scoring, or extra-testing encouragement in copy or structure.
- No OCR, AI/LLM, external service, network call, or new package.
- The architecture boundary tests (`MedicalCopyBoundaryTest`,
  `PrivacyBoundaryTest`, `PackageBoundaryTest`) must stay green; the surface
  adapts to them, never the other way around.

## Evidence

- Source: `DESIGN.md` section 5, "Blood Results Overview"
  - Claim type: fact
  - Summary: The design system already specifies this surface: summary panel,
    amber featured attention card with a single getallenlijn, compact
    normal/unknown rows, one green good-area, borders-first, no motion, status
    visible in words.

- Source: owner request (this session, 2026-07-02)
  - Claim type: fact
  - Summary: The owner asked for a confirmed-only overview of confirmed
    biomarkers grouped by status as the next slice, governance docs first.

- Source: `app/Models/BiomarkerResult.php`
  - Claim type: fact
  - Summary: `BiomarkerResult::confirmedForUser()` already enforces
    `confirmed_at` plus owner scope on both the blood test and the biomarker,
    and is the established entry path for every downstream surface (export,
    consult, trends, dashboard).

- Source: `AGENTS.md` and README Product Boundary
  - Claim type: fact
  - Summary: Confirmed-only downstream and the non-medical-advice boundary are
    global invariants; new surfaces inherit them.

## Considered Options

- Extend `BuildLatestUploadSummary` (rejected: it is a latest-upload digest;
  forcing an all-confirmed view into it would overload one builder with two
  shapes).
- Plain controller + Blade view like the dashboard (workable, but rejected for
  this slice: the blood-tests area already has a class-based Livewire pattern
  and the owner directed a Livewire component here).
- Class-based Livewire component with the domain logic in a dedicated builder
  (chosen: matches `app/Livewire/BloodTests/ReviewBloodTest` and keeps domain
  logic out of the component, per the V1 architecture rule).
- Skipping governance and building directly (rejected: `AGENTS.md` requires
  spec/ADR/task plan before a new surface).

## Decision Drivers

- The design system already defines the surface; the missing piece is a
  governed data path, not new visual invention.
- `confirmedForUser` gives a single, tested trust gate to reuse.
- A read-only surface with no write actions keeps the risk profile small.
- Status grouping is organizational presentation of already-computed statuses,
  not interpretation.

## Consequences

- A new domain builder `BuildBloodResultsOverview` is added under
  `app/Domain/Dashboard` with unit coverage; Livewire stays orchestration-only.
- The surface lives inside the confirmed-only boundary; drafts stay invisible
  and feature tests must prove they do not leak.
- Copy uses status words (`laag`, `hoog`, `normaal`, `onbekend` in UI copy) and
  the "status op basis van ingevoerde referentierange" framing; no new product
  language categories are introduced.
- The existing dashboard digest remains unchanged; this ADR adds a surface, it
  does not move or replace the dashboard blocks.
- The "thematic biomarker overview" roadmap intent gains its first recorded
  governance trail (this ADR, the spec, and the task plan).

## Confidence

High for the governance decision: the surface is already specified visually,
the trust gate exists in code, and the slice adds no new data paths or write
actions. Implementation confidence is deferred to the task plan and its tests.

## Follow-Up Questions

- Should the overview later group by biomarker category (thematic grouping) in
  addition to status, and does that need a spec amendment?
- Where does the surface get its navigation entry (sidebar, dashboard link, or
  both) without demoting the dashboard next-step flow?
  *Answered during build (2026-07-06, commit d29892b):* both — the sidebar and
  mobile header ("Mijn bloedwaarden") plus the dashboard 'Bevestigd' tile,
  which links to the page whose summary line reconciles with its count.
  Recorded in the spec §9 and covered by the reachability feature test.
