# Source Index

This file tracks source surfaces used to shape the project. Keep direct links,
local paths, and claim types where possible.

Claim types:

- `fact`: directly observed in a source.
- `inference`: reasoned from one or more facts.
- `hypothesis`: plausible but not yet proven.
- `unknown`: important, but evidence is missing.

Do not collapse these categories.

## Local Project Sources

### Laravel Platform Discovery

- Source: `laravel-platform-discovery.md`
- Claim type: fact
- Summary: Laravel is most useful here when data, roles, rules, follow-up,
  communication, documents, and automation combine into an administrative
  platform motor rather than a normal website.

### Project Brief

- Source: `docs/project-brief.md`
- Claim type: fact
- Summary: The product boundary is a personal blood values ordering and
  follow-up system, not diagnosis or medical advice.

### V1 Spec

- Source: `docs/v1-spec.md`
- Claim type: fact
- Summary: V1 starts from login-protected lab-PDF intake, private document
  storage, reviewed or corrected biomarker values, status calculation, trends,
  comparison, context notes, consult/export, and privacy controls, while
  excluding diagnosis, AI interpretation, unreviewed OCR, integrations, and
  recommendations.

### Stack Decision

- Source: `docs/research/laravel-stack-decision.md`
- Claim type: inference
- Summary: Laravel Livewire starter kit is the likely V1 default because the
  product is private, authenticated, workflow-heavy, and only moderately
  interactive.

### Competitor Analysis

- Source: `docs/research/competitor-analysis.md`
- Claim type: inference
- Summary: Firecrawl research across lab trackers, health timeline products,
  optimization platforms, open-source/local-first tools, and practitioner
  discussions supports a PDF-first, user-confirmed, privacy-first V1 while
  keeping AI advice, optimal ranges, action plans, provider connections, and
  wearable sync out of scope until later ADRs.

## External Workflow Sources

### Codex Starter Kit

- Source: `https://github.com/ChristopheAI/Codex`
- Claim type: fact
- Summary: The workflow model starts from project brief and control-plane docs,
  then moves through spec, tasks, validation, implementation, review, and
  handoff.

### AI Architect Program Research

- Source: `https://github.com/ChristopheAI/ai-architect-program-research`
- Claim type: fact
- Summary: The repository separates raw evidence, synthesized models, ADRs, and
  templates so research becomes a durable operating system instead of loose
  notes.

Relevant transfer:

- claim discipline: fact, inference, hypothesis, unknown;
- evidence -> model -> ADR -> open question loop;
- 10-gate idea validation model;
- ADRs as core thinking artifacts.

## Security And Architecture Sources

### OWASP Threat Modeling

- Source: `https://cheatsheetseries.owasp.org/cheatsheets/Threat_Modeling_Cheat_Sheet.html`
- Claim type: fact
- Summary: Threat modeling should happen early in the SDLC and answer what is
  being built, what can go wrong, what will be done about it, and whether the
  result is good enough.

### OWASP Secure By Design

- Source: `https://owasp.org/www-project-secure-by-design-framework/`
- Claim type: fact
- Summary: Security should be embedded during architecture and system design
  before development begins.

### Architecture Decision Records

- Source: `https://docs.cloud.google.com/architecture/architecture-decision-records`
- Claim type: fact
- Summary: ADRs capture design choices, requirements, options, reasons, and
  history close to the application code.

- Source: `https://docs.aws.amazon.com/prescriptive-guidance/latest/architectural-decision-records/adr-process.html`
- Claim type: fact
- Summary: ADRs describe significant architecture choices, context, and
  consequences, and should be reviewed before acceptance.

- Source: `https://martinfowler.com/bliki/ArchitectureDecisionRecord.html`
- Claim type: fact
- Summary: ADRs should be short, stored near the code, and retained or
  superseded rather than silently rewritten.
