# ADR-0004: Use Manual Entry And Owner-Scoped Health Data In V1

## Status

Superseded by ADR-0005 for the V1 intake/source-of-truth decision.

Owner scoping, status `unknown`, and privacy rules remain accepted and are
carried forward.

## Context

The application handles sensitive personal health-related data. V1 must help a
single authenticated user organize blood tests and biomarkers without implying
diagnosis or medical advice.

Automation features such as OCR, AI interpretation, provider connections, Apple
Health imports, or supplement recommendations would increase risk before the
core workflow is proven.

## Decision

ADR-0005 replaces the manual-first source-of-truth decision with PDF-first
intake plus reviewed or confirmed structured values.

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
  - Summary: PDF-first intake, reviewed values, owner scoping, status
    `unknown`, and privacy/export controls are explicit V1 requirements.

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
- PDF-first intake with human review.
- Lab/provider integration first.
- AI interpretation first.
- Shared doctor or coach access in V1.

## Decision Drivers

- Manual entry is auditable and remains a required fallback.
- PDF-first intake better matches the real user workflow: the lab result exists
  first as a document.
- Health-data privacy is a V1 concern, not later polish.
- Automation can produce wrong values or misleading conclusions.
- Owner scoping is the foundation for every later feature.
- Unknown status is safer than false normal/high/low labels.

## Consequences

- First implementation must prioritize auth, ownership, private document
  storage, review, and status logic.
- AI/OCR/provider integrations require future ADRs and threat modeling.
- Export/delete workflows must be treated as privacy controls, not convenience
  features.

## Confidence

High

## Follow-Up Questions

- What exact export/delete proof should block real personal data entry?
