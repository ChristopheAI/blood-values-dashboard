# ADR-0002: Use ADRs For Architecture Decisions

## Status

Accepted

## Context

The project already has several decisions that affect the future system:

- PDF-first intake with confirmed structured values before unreviewed OCR or
  provider integrations;
- Laravel Livewire starter kit as likely V1 foundation;
- strict medical-advice boundary;
- owner-scoped private health data;
- status `unknown` when comparison is not trustworthy;
- pre-scaffold review before implementation.

If these decisions remain only in prose documents, they can drift or be
overwritten silently during implementation.

## Decision

Use ADRs under `docs/adr/` for architecturally significant decisions.

Each ADR should capture status, context, decision, evidence, considered options,
decision drivers, consequences, confidence, and follow-up questions.

## Evidence

- Source: `https://docs.cloud.google.com/architecture/architecture-decision-records`
  - Claim type: fact
  - Summary: ADRs capture options, requirements, decisions, and the reasons
    behind them close to the codebase.

- Source: `https://docs.aws.amazon.com/prescriptive-guidance/latest/architectural-decision-records/adr-process.html`
  - Claim type: fact
  - Summary: ADRs describe significant architecture choices, their context, and
    consequences.

- Source: `https://github.com/ChristopheAI/ai-architect-program-research`
  - Claim type: fact
  - Summary: ADRs were used as core thinking artifacts to separate evidence
    from interpretation.

## Considered Options

- Keep decisions inside `docs/project-brief.md`.
- Keep decisions inside `docs/v1-spec.md`.
- Keep decisions inside chat summaries.
- Add ADRs as a decision log.

## Decision Drivers

- Major decisions need reviewable rationale.
- Future implementation should not rediscover the same trade-offs.
- Sensitive data decisions need traceability.
- Short files are easier to keep current than one giant planning document.

## Consequences

- Not every note becomes an ADR.
- Accepted ADRs should be superseded by new ADRs rather than silently rewritten
  when a major decision changes.
- Reviewers should consult ADRs during the pre-scaffold gate and later code
  review.

## Confidence

High

## Follow-Up Questions

- Which decisions after scaffold should become ADRs rather than normal docs?
