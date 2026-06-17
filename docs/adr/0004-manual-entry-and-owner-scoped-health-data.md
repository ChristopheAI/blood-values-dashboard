# ADR-0004: Use Manual Entry And Owner-Scoped Health Data In V1

## Status

Accepted

## Context

The application handles sensitive personal health-related data. V1 must help a
single authenticated user organize blood tests and biomarkers without implying
diagnosis or medical advice.

Automation features such as OCR, AI interpretation, provider connections, Apple
Health imports, or supplement recommendations would increase risk before the
core workflow is proven.

## Decision

Use manual biomarker entry as the V1 source of truth.

Scope all health data to the authenticated owner. Every user-owned record must
belong to the user directly or through an owned parent record. Tests must prove
that User A cannot access User B's records once implementation begins.

Use `unknown` status when value, unit, range, or comparison rules are not
trustworthy.

## Evidence

- Source: `docs/project-brief.md`
  - Claim type: fact
  - Summary: V1 is a personal ordering and follow-up system, not a diagnosis
    machine or medical advice product.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: Manual entry, owner scoping, status `unknown`, and privacy/export
    controls are explicit V1 requirements.

- Source: `https://owasp.org/www-project-secure-by-design-framework/`
  - Claim type: fact
  - Summary: Security should be embedded during architecture and system design
    before code is written.

- Source: `https://cheatsheetseries.owasp.org/cheatsheets/Threat_Modeling_Cheat_Sheet.html`
  - Claim type: fact
  - Summary: Threat modeling should happen early and include what is being
    built, what can go wrong, mitigations, and review.

## Considered Options

- Manual entry first.
- OCR/PDF extraction first.
- Lab/provider integration first.
- AI interpretation first.
- Shared doctor or coach access in V1.

## Decision Drivers

- Manual entry is auditable and easier to validate.
- Health-data privacy is a V1 concern, not later polish.
- Automation can produce wrong values or misleading conclusions.
- Owner scoping is the foundation for every later feature.
- Unknown status is safer than false normal/high/low labels.

## Consequences

- First implementation must prioritize auth, ownership, and status logic.
- Document upload should not precede owner-isolation confidence.
- AI/OCR/provider integrations require future ADRs and threat modeling.
- Export/delete workflows must be treated as privacy controls, not convenience
  features.

## Confidence

High

## Follow-Up Questions

- Should document upload be delayed until after owner isolation tests pass?
- What exact export/delete proof should block real personal data entry?
