# Research Run: Apple Health Context Import

Date: 2026-06-18

Question:

- If Apple Watch or Apple Health support is added later, what is the safest
  architecture direction for this Laravel blood values dashboard?

Decision affected:

- Whether wearable support should be a V1 feature, a V2 context import, or a
  runtime provider integration.

Scope:

- Public Apple Health export/import tools and open-source architectures.
- Local processing patterns.
- Data-volume, privacy, and parsing implications.

Out of scope:

- Processing Christophe's real Apple Health export.
- Processing lab-result PDFs.
- Building wearable integration.
- Creating medical or lifestyle recommendations.

## Exa Discovery

Query used:

```text
Apple Health export XML personal health data import open source dashboard
architecture privacy parser GitHub
```

Useful sources discovered:

- `https://github.com/jordangarrison/vitals`
- `https://github.com/BRO3886/healthsync`
- `https://github.com/leecdiang/Apple-Health-Pro`
- `https://github.com/cvyl/apple-health-parser`
- `https://www.themomentum.ai/open-source/mcp-apple-health-server`
- `https://github.com/the-momentum/open-wearables`

## Firecrawl Extraction

Firecrawl scraped the public sources above and returned public README/docs
content only. No private health data was sent.

## Findings

### Fact: Apple Health exports can be large

Open-source tools repeatedly mention large `export.xml` or `export.zip` files,
including multi-year exports with millions of records.

Decision impact:

- Do not design this as a normal synchronous upload-and-parse request.
- Any later import needs background processing, progress state, and failure
  recovery.

### Fact: Streaming parsers are common

Relevant projects use or recommend streaming/token-based parsing patterns for
Apple Health XML.

Decision impact:

- A later Laravel implementation should use a streaming XML parser or an
  external local import worker.
- Avoid loading the full XML into memory.

### Fact: Local SQLite-style processing is common

Several tools parse exports into local databases for query and visualization.

Decision impact:

- This supports a local/private V2 context-import model.
- It does not support sending the export to Firecrawl or another web scraping
  API.

### Fact: Deduplication and batch inserts matter

`healthsync` highlights idempotent imports and batch inserts.

Decision impact:

- A later data model needs stable import keys or source fingerprints.
- Re-importing an Apple Health export should not duplicate context samples.

### Inference: Wearable data should be context, not biomarker data

Apple Health data is high-volume time-series context. Blood biomarkers are
low-frequency lab measurements with lab-specific units and ranges.

Decision impact:

- Keep Apple Health records out of `biomarker_results`.
- Model them as context summaries or selected samples around blood-test dates.

### Inference: V2 should start with export import, not live sync

Live provider integrations add mobile, permissions, OAuth/device sync, and
ongoing background-sync complexity.

Decision impact:

- First wearable milestone should be user-controlled Apple Health export import.
- Live Apple Watch sync should remain a later separate architecture decision.

### Hypothesis: The best user value is date-window summaries

For a blood values dashboard, the useful Apple Health question is likely:

- What did sleep, resting heart rate, HRV, steps, workouts, or weight look like
  around the blood test date?

Decision impact:

- V2 should not expose every raw Apple Health metric first.
- Start with user-selected metrics and date windows around blood tests.

### Unknown: Which metrics Christophe actually wants

Possible metrics:

- sleep duration;
- resting heart rate;
- HRV;
- steps;
- workouts/training load;
- weight;
- blood pressure;
- oxygen saturation.

Decision impact:

- Do not design a broad Apple Health dashboard before selecting the first 3-5
  context metrics.

## Recommendation

Do not add Apple Watch or Apple Health support to V1.

For V2, prefer:

```text
Apple Health export.zip
-> local/private import
-> streaming parser/background job
-> deduplicated context samples
-> selected date-window summaries around blood tests
-> shown as context, not advice
```

Reject for now:

- live sync;
- provider OAuth;
- sending Apple Health export to external APIs;
- AI coaching on wearable data;
- mixing wearable time-series data into biomarker results.

## Decision Impact

This run confirms ADR-0006:

- Exa and Firecrawl are useful for public research.
- They must not process private Apple Health exports.

It also supports a future ADR:

- `Use Apple Health export import as V2 context data`

## Next Action

Create a V2 ADR only when the project is ready to expand beyond the V1
PDF-first blood-test workflow.
