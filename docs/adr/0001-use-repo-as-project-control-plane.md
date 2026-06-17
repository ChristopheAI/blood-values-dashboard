# ADR-0001: Use The Repository As The Project Control Plane

## Status

Accepted

## Context

This project started from the rule: first project thinking, then building. The
workspace must survive context loss, agent handoffs, and future implementation
sessions without relying on chat memory.

The project also handles sensitive personal health data, so implementation
should not begin from an empty Laravel scaffold with decisions scattered across
conversation history.

## Decision

Use this repository as the durable project control plane.

Keep the product brief, V1 spec, validation protocol, review gate, handoff,
production checklist, ADRs, evidence index, and implementation plans in git
before Laravel scaffold work begins.

## Evidence

- Source: `https://github.com/ChristopheAI/Codex`
  - Claim type: fact
  - Summary: The starter-kit workflow starts from project brief, docs,
    validation, and handoff before implementation.

- Source: `https://github.com/ChristopheAI/ai-architect-program-research`
  - Claim type: fact
  - Summary: Durable research and decisions were separated into evidence,
    models, ADRs, and templates.

- Source: `docs/session-handoff.md`
  - Claim type: fact
  - Summary: Chat context is temporary; repository context is durable.

## Considered Options

- Keep decisions in chat.
- Keep a single long planning document.
- Scaffold Laravel first and document later.
- Use the repository as the control plane.

## Decision Drivers

- Sensitive personal data requires explicit privacy decisions.
- Future sessions need a stable source of truth.
- Validation should prove the current phase.
- Reviewers need files they can inspect.

## Consequences

- Planning docs are not optional decoration.
- Every meaningful project decision should update the repo.
- `scripts/validate.sh` must evolve with the project phase.
- Laravel scaffold work stays blocked until the review gate is handled or
  explicitly overridden.

## Confidence

High

## Follow-Up Questions

- Should GitHub branch protection be added before implementation begins?
- Should the pre-scaffold review result be stored as an ADR after the gate is
  completed?
