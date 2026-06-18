# Research Run: Andrew Codesmith Public Thinking Profile

Date: 2026-06-18

Question:

- What public thinking patterns from Andrew Codesmith can usefully strengthen
  this Laravel blood values dashboard project?

Decision affected:

- How to use creator/engineer thinking as inspiration without treating it as
  authority or copying blindly.

Scope:

- Public web sources only.
- Exa source discovery.
- Firecrawl extraction of public Substack and profile pages.

Out of scope:

- Private information.
- Non-public LinkedIn content.
- Claiming access to Andrew's private knowledge, intent, or "brain".
- Treating his advice as a substitute for project evidence, privacy review, or
  validation.

## Identity Resolution

Exa returned multiple people named Andrew or Andrew Smith. This run focuses on
Andrew Codesmith / Andrew Tattersall because the user named "Andrew Codesmith"
and Exa surfaced public sources using that handle.

Excluded from this run:

- unrelated Andrew Smith profiles;
- unrelated GitHub profiles using similar names;
- inaccessible LinkedIn content.

## Sources

Exa discovery surfaced:

- `https://andrewcodesmith.substack.com/`
- `https://andrewcodesmith.substack.com/p/anti-vibe-coders-guide-to-building`
- `https://andrewcodesmith.substack.com/p/what-im-learningcoding-and-life`
- `https://vigyata.ai/@andrewcodesmith`
- `https://www.linkedin.com/in/andrewtattersalltech`

Firecrawl extraction results:

- Substack home: accessible.
- "Anti vibe coder's guide to building with AI": accessible.
- "What I'm learning/coding & life": accessible.
- Vigyata profile: accessible.
- LinkedIn profile: blocked with HTTP 403, not used as extracted evidence.

## Findings

### Fact: He publicly writes about AI tooling for software work

Public sources reference Claude Code, Cursor, AI tools, and AI-assisted app
building.

Decision impact:

- This supports using AI tools in the project workflow, but only inside the
  existing control plane: docs, ADRs, validation, browser checks, and commits.

### Fact: He frames himself around app building and tech content

Public sources describe tech, coding, AI tools, solo app building, and building
products.

Decision impact:

- Useful lens: build real usable workflows, not only notes or abstract research.

### Fact: He discusses learning and career development

Public sources emphasize ongoing learning, tech career growth, and developer
skill development.

Decision impact:

- Useful lens: keep a learning loop in the project through research runs,
  source-index entries, and retrospectives.

### Fact: He mentions SQL/Postgres learning in public writing

The public Substack result references learning Postgres and SQL.

Decision impact:

- Useful reminder: for a health-data app, durable data modeling and queryable
  records matter more than shiny UI first.

### Inference: His useful project stance is pragmatic AI-assisted building

Across the public profile surfaces, the transferable pattern is not a specific
Laravel architecture. It is a practical workflow:

```text
learn fast -> use AI tools -> build concrete apps -> keep improving skill and
product judgment
```

Decision impact:

- This aligns with the project's current direction: Exa and Firecrawl for
  public research, Laravel for the private product, validation as the finish
  line.

### Inference: He is not the right authority for medical/privacy decisions

The public sources found are about tech, AI tooling, app building, learning, and
career. They are not sufficient evidence for medical-data privacy architecture.

Decision impact:

- Do not use Andrew Codesmith as authority for lab data privacy, Apple Health
  handling, or medical product boundaries.
- Use him as a workflow/product-building inspiration only.

## Transfer To This Project

Adopt:

- use AI tools deliberately, but inside a validation loop;
- build usable vertical slices rather than endless abstract planning;
- keep learning visible in research docs;
- treat SQL/data modeling as a core skill for the product;
- connect product building with user outcome and communication.

Reject:

- copying content-creator recommendations without project evidence;
- using AI tooling as a substitute for architecture;
- treating public creator content as security or medical authority;
- expanding scope because a tool makes it feel easy.

## Practical Project Heuristics

For each next feature:

1. Define the user outcome.
2. Use Exa to find public evidence and examples.
3. Use Firecrawl to extract the strongest public sources.
4. Decide whether the evidence affects V1, V2, or out-of-scope.
5. Update docs/ADR/spec before implementation.
6. Build the smallest real workflow.
7. Validate with automated checks and browser use.
8. Commit the result.

## Known Unknowns

- LinkedIn extraction was blocked, so LinkedIn claims are not used as direct
  Firecrawl evidence.
- Social-video content was not transcribed or analyzed in this run.
- Public creator profiles may be marketing surfaces and should be treated as
  lower-weight evidence than code, docs, architecture notes, or verified
  project behavior.

## Recommendation

Use Andrew Codesmith as an inspiration source for AI-assisted product-building
discipline, not as an architecture authority.

For this Laravel health project, the better synthesis is:

```text
Andrew-style practical building energy
+ Robin/Evidence-style decision discipline
+ Laravel control-plane validation
+ strict privacy boundary
= useful, safe progress
```
