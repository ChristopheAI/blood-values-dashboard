# ADR-0008: Future AI Agents Must Be Proposal-Only

## Status

Accepted

## Context

The project is a private blood values dashboard. It stores sensitive health
data, lab PDFs, biomarker values, context notes, exports, and deletion controls.
V1 explicitly excludes AI interpretation and medical advice.

A public Laravel case study from Relaticle shows a production-style AI agent in
an open-source Laravel CRM. The strongest transferable lesson is not "add AI
now"; it is that safe AI writes require a durable proposal and approval
subsystem.

## Decision

Do not add a runtime AI agent to V1.

If AI is proposed later, it must be proposal-only:

- AI may produce drafts or proposals.
- AI may not directly create, update, delete, confirm, export, or interpret
  private health records.
- Every mutation must go through explicit human approval.
- Approved proposals must execute through allowlisted domain actions.
- Ownership must be re-checked at approval time from persisted proposal data.
- Approvals must be idempotent.
- Stale proposals must be superseded when context changes.
- Failures, retries, superseded proposals, approvals, and rejections must be
  visible to the user.

AI output is never trusted health data until reviewed and confirmed by the user.

## Evidence

- Source: `docs/research/2026-06-18-relaticle-laravel-ai-agent-patterns.md`
  - Claim type: inference
  - Summary: Relaticle's public Laravel AI-agent implementation uses
    proposal-only writes, human approval, idempotent pending actions, tenant
    scoping, stale proposal superseding, resumable streaming, and explicit
    failure states.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: V1 excludes diagnosis, treatment, AI interpretation, unreviewed
    OCR, integrations, recommendations, and wearable data.

- Source: `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
  - Claim type: fact
  - Summary: Structured biomarker values become trusted only after user review
    or confirmation.

- Source: `docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md`
  - Claim type: fact
  - Summary: External research tools may not process private health data in V1.

## Considered Options

- Add a runtime AI agent in V1.
- Use AI only for direct extraction and auto-confirmation.
- Allow AI writes with simple confirmation buttons.
- Keep AI out of V1 and require proposal-only architecture for any later AI.

## Decision Drivers

- Health data is sensitive.
- The product is not a diagnosis machine.
- Confirmed values must stay user-reviewed.
- AI-generated values, summaries, and actions can be wrong.
- A safe approval system requires persistence, idempotency, ownership checks,
  expiry, auditability, and visible failure states.

## Consequences

- V1 remains PDF-first and human-confirmed.
- Any later AI feature needs a separate spec, privacy review, and validation
  plan.
- AI proposal tables/models would be separate from confirmed biomarker results.
- Tests must prove no proposal can be applied by the wrong user or after it is
  expired, superseded, rejected, or already approved.
- Provider selection must be constrained by safety capabilities, not only model
  quality or cost.

## Confidence

High

## Follow-Up Questions

- Which future AI use case, if any, is worth exploring first: draft extraction,
  consult summary, search assistant, or context-note tagging?
- Should proposal-only AI be modeled as its own V2 epic after V1 is stable?
- What data, if any, may leave the local machine for provider inference?
