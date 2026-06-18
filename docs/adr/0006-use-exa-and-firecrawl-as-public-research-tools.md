# ADR-0006: Use Exa And Firecrawl As Public Research Tools

## Status

Accepted

## Context

The project benefits from evidence-backed research. The user wants Exa and
Firecrawl used deliberately, and the product involves sensitive personal health
data.

This creates an important boundary:

- Exa and Firecrawl can improve public research, competitor analysis,
  documentation review, privacy-policy comparison, and market monitoring.
- Exa and Firecrawl must not become implicit processors of the user's personal
  lab PDFs, Apple Health exports, biomarker values, medication notes, symptoms,
  or other private health data.

The project already uses a source index, ADRs, and research documents as a
control plane. Research tools should feed that control plane, not bypass it.

## Decision

Use Exa and Firecrawl as project research tools for public or non-sensitive
sources only.

Default roles:

- Exa is the discovery tool: find relevant public sources, competitors,
  documentation, repositories, practitioner discussions, and market language.
- Firecrawl is the extraction and monitoring tool: scrape, crawl, map, extract,
  compare, and monitor public sources once they are identified.

Do not use Exa or Firecrawl for private health data in V1.

Private data includes:

- uploaded lab-result PDFs;
- extracted or manually entered biomarker values;
- Apple Health exports;
- wearable data;
- medication, supplement, symptom, complaint, training, sleep, or food notes;
- consult exports;
- account data or user identity data.

Any future use of Exa, Firecrawl, or another external API inside the application
runtime requires a separate ADR, privacy review, threat model update, and
explicit user approval.

## Evidence

- Source: `docs/research/competitor-analysis.md`
  - Claim type: inference
  - Summary: Firecrawl research is useful for comparing public competitor
    claims and practitioner surfaces, but the product boundary remains
    PDF-first, user-confirmed, privacy-first, and no medical advice.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: V1 excludes AI interpretation, provider integrations, Apple Health
    integrations, wearable data, and unreviewed OCR.

- Source: `docs/evidence/source-index.md`
  - Claim type: fact
  - Summary: Project evidence must separate facts, inferences, hypotheses, and
    unknowns.

- Source: `https://docs.firecrawl.dev/api-reference/v2-introduction`
  - Claim type: fact
  - Summary: Firecrawl provides API access to web data through search, scrape,
    crawl, map, and extraction-style workflows.

- Source: `https://docs.exa.ai/`
  - Claim type: fact
  - Summary: Exa is designed for web search and retrieval workflows.

## Considered Options

- No external research tools.
- Use Exa and Firecrawl only manually during planning.
- Use Exa and Firecrawl as public research tools feeding project docs.
- Integrate Exa and Firecrawl into the Laravel application runtime.
- Use Exa or Firecrawl for private lab document processing.

## Decision Drivers

- Protect sensitive health data.
- Keep the V1 app local/private by default.
- Avoid hidden external processors in the personal health data path.
- Improve research quality without expanding product risk.
- Keep decisions durable in git through source-index entries, ADRs, specs, and
  research documents.
- Preserve the product boundary: ordering and follow-up, not diagnosis or
  advice.

## Consequences

- Exa and Firecrawl can be used to create and refresh research documents.
- Competitor monitoring can be automated later as a project-development tool.
- Research outputs must cite public sources and separate claim types.
- Firecrawl API keys must not be committed, logged into project files, or
  stored in repo configuration.
- Any raw scrape dumps should stay temporary unless explicitly sanitized and
  intentionally committed.
- The Laravel app must not depend on Exa or Firecrawl for V1 user workflows.
- If a future runtime integration is proposed, it must be reviewed as a new
  external data processor.

## Practical Workflow

Use this sequence for future research:

```text
question -> Exa source discovery -> Firecrawl source extraction -> source index
-> research doc -> ADR/spec update if a decision changes -> validation
```

Research examples:

- competitor feature and positioning analysis;
- privacy-policy comparison;
- Apple Health or wearable architecture research;
- Laravel package documentation review;
- open-source repository discovery;
- practitioner pain-point research from public forums;
- change monitoring for competitor claims.

Forbidden V1 examples:

- sending a lab PDF to Firecrawl;
- using Exa or Firecrawl to parse personal biomarker data;
- sending Apple Health export files to an external API;
- using scraped public medical pages to generate health advice;
- using competitor "optimal range" language as medical truth.

## Confidence

High

## Follow-Up Questions

- Should the project add a reusable research runbook for Exa plus Firecrawl?
- Should competitor monitoring become a scheduled local script later?
- Which public sources should be watched after V1 is stable?
