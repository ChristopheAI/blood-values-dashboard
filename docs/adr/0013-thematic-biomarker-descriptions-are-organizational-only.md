# ADR-0013: Thematic Biomarker Descriptions Are Organizational Only

## Status

Accepted

## Context

Users think in human themes (e.g. ontsteking, slaap, hartgezondheid) while the app
stores flat confirmed biomarker rows. Competitor product pages group markers under
themes with short educational copy. We want the **navigation structure** (theme →
marker → optional one-line description → trend) without diagnosis, risk language,
or treatment advice.

Private health data must stay local. Theme descriptions must not come from runtime
AI, OCR, or external services.

## Decision

Add a thematic biomarker overview that:

- groups **confirmed-only** values by owner-scoped `BiomarkerCategory`;
- attaches optional neutral Dutch descriptions from a local audited config map;
- reuses `BuildLongitudinalChanges` for trend labels;
- renders first on the consult pack, second on the dashboard latest digest;
- omits categories with no confirmed markers in scope;
- places uncategorized markers under **Overig**.

Default category names follow Vitasure-inspired lifestyle themes (Hartgezondheid,
Ontstekingen, Cognitie, Uithoudingsvermogen, Slaap, Hormoonbalans, Metabolisme,
Herstel, Fitness) plus **Overig**. Category assignment remains manual/seeder-first;
auto-category during intake is out of scope until a later ADR.

### Copy boundary

Allowed reference descriptions:

- what the marker is called in lab reports;
- which broader panel it belongs to;
- neutral organizational context.

Forbidden in reference copy and UI:

- voorspelt, risico, behandelen, advies, optimaliseren, extra testen, urgentie;
- causal or diagnostic claims;
- personalized interpretation.

Descriptions are **public generic education**, not personalized results.

## Consequences

- Consult handoff enables `include_themes=1` by default.
- CSV export excludes theme descriptions in the first slice.
- Catalog UI for category assignment remains a follow-up slice.
- New markers without reference entries show value/trend without description.

## Related

- ADR-0005 confirmed-only downstream
- ADR-0011 auto-confirm boundary
- `docs/superpowers/plans/2026-06-27-thematic-biomarker-overview.md`
