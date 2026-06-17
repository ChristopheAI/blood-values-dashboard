# AI Architect Program Transfer

Date: 2026-06-17

Source:

- `https://github.com/ChristopheAI/ai-architect-program-research`

## Purpose

Use the AI Architect research repo to strengthen this Laravel planning
workspace without copying its domain or turning this project into a generic AI
builder exercise.

The useful transfer is process discipline:

```text
raw evidence
-> synthesized model
-> ADR
-> review question or issue
-> validation update
```

## What Transfers

### 1. Evidence Discipline

The AI Architect repo keeps `fact`, `inference`, `hypothesis`, and `unknown`
separate. This project should do the same whenever product, architecture,
privacy, or medical-boundary decisions are made.

Application here:

- use `docs/evidence/source-index.md` as the source ledger;
- keep medical/product claims traceable to a source or mark them as inference;
- avoid treating planned features as proven product value.

### 2. ADRs As Thinking Artifacts

The AI Architect repo uses ADRs for decisions that change the model. This
project should use ADRs for decisions that affect:

- privacy and owner scoping;
- manual entry versus automation;
- Livewire versus other Laravel stacks;
- status calculation semantics;
- document storage and export/delete behavior;
- the point at which implementation may begin.

Application here:

- keep short ADRs under `docs/adr/`;
- use `docs/templates/adr-template.md`;
- update or supersede decisions through new ADRs, not silent rewrites.

### 3. Small Scoped System

The transferable model is:

```text
domain knowledge
+ repeated painful workflow
+ existing manual process
+ small first system
+ real validation
+ review/testing discipline
= useful software
```

Application here:

- the first build slice remains auth, two blood tests, small biomarker catalog,
  manual results, status calculation, history, and compare;
- OCR, AI interpretation, document parsing, integrations, and recommendations
  remain out of the first slice;
- V1 should prove personal organization and consult preparation, not a broad
  health platform.

### 4. System Surface Awareness

The research repo emphasizes that a builder should understand the app surface
without reading every line of code.

Application here:

- `README.md`, `AGENTS.md`, `docs/session-handoff.md`, and ADRs must explain
  the system enough for a new agent or human reviewer to continue;
- every future implementation slice should be explainable through inputs,
  outputs, data stores, user ownership, failure modes, and validation.

### 5. Review And QA As A Gate

The research repo treats review, tests, and manual QA as part of the build loop,
not as polish.

Application here:

- keep the pre-scaffold review gate active;
- upgrade `scripts/validate.sh` immediately after scaffold;
- require owner-isolation tests before real personal data is entered;
- do not accept "AI said it works" as proof.

### 6. Focus And UX Simplicity

The AI Architect model names overbuilt, generic, cognitively heavy AI-built
apps as a failure mode.

Application here:

- dashboard V1 must have one clear job: organize and compare personal blood
  values for follow-up and consult preparation;
- avoid a generic health cockpit;
- avoid adding many panels before the core entry, status, history, and compare
  workflows are usable;
- keep medical language restrained and non-advisory.

## What Does Not Transfer

- The AI Architect product domain does not define this product's medical or
  privacy boundary.
- Distribution and sales gates are not primary for this personal V1.
- The research repo's model is not medical authority.
- The research repo should not override the Laravel discovery notes, V1 spec,
  OWASP security guidance, or pre-scaffold review gate.

## Resulting Changes

Adopt in this repo:

- `docs/evidence/source-index.md`;
- `docs/templates/adr-template.md`;
- `docs/adr/` decision records;
- `docs/product-system-check.md` as a compact build-readiness check;
- validation checks proving these files exist and stay referenced.

Do not start Laravel scaffold because of this transfer. The next system step is
still the pre-scaffold review gate.
