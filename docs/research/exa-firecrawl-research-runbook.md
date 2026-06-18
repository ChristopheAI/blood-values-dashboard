# Exa And Firecrawl Research Runbook

Date: 2026-06-18

Purpose:

- Make public research repeatable.
- Keep Exa and Firecrawl outside private health-data processing.
- Turn research into source-index entries, research docs, ADRs, specs, and
  validation checks.

## Boundary

Allowed:

- public websites;
- public documentation;
- public GitHub repositories;
- public practitioner discussions;
- competitor pages;
- privacy policies;
- product changelogs.

Forbidden in V1:

- lab-result PDFs;
- biomarker values;
- Apple Health exports;
- wearable exports;
- medication, symptom, training, food, sleep, or supplement notes;
- consult exports;
- account or identity data.

## Standard Workflow

```text
decision question
-> Exa discovery
-> Firecrawl extraction
-> notes with claim types
-> docs/evidence/source-index.md
-> research doc
-> ADR/spec update if a decision changes
-> sh scripts/validate.sh
-> commit
```

## Research Run Template

Use this structure for every run:

```text
Question:
Decision affected:
Scope:
Out of scope:
Exa queries:
Firecrawl sources:
Findings:
- Fact:
- Inference:
- Hypothesis:
- Unknown:
Decision impact:
Next action:
Validation:
```

## Tool Roles

Exa:

- find candidate sources;
- surface public repos and docs;
- identify practitioner discussions;
- broaden or challenge assumptions.

Firecrawl:

- scrape known URLs;
- crawl public docs when needed;
- extract structured claims;
- compare or monitor public source changes;
- produce clean source material for research docs.

## Handling Secrets

- Never commit API keys.
- Prefer runtime environment variables.
- Do not store raw Firecrawl dumps unless deliberately sanitized.
- Remove temporary scrape files after summarizing findings.

## Quality Gate

A research run is useful only when it changes or confirms one of:

- product boundary;
- V1/V2 scope;
- data model;
- privacy model;
- validation approach;
- user workflow;
- architecture decision;
- risk register.
