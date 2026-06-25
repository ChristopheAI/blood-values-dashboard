# Post-PDF Workflow Reverse Engineering

Date: 2026-06-25
Status: research synthesis, no implementation approval
Scope: public research only; no private PDFs, biomarker values, screenshots, notes, or source snippets were sent to Exa, Firecrawl, or external processors.

## Decision

Choose the safest next post-PDF workflow direction for the private Laravel blood-values app after deterministic PDF intake and review.

Recommendation: continue with the existing Laravel direction and make the next slice a confirmed-only consult/export hardening slice, backed by visible source lineage and blood-test detail context. Do not build a new extraction, provider-sync, wearable, runtime AI, or FHIR-first core layer.

In one sentence:

```text
PDF in -> review remainder -> confirmed blood-test detail -> source-backed changes/context -> consult/export.
```

## Intents

- Help the user understand received blood results without having to mentally reconstruct PDF rows.
- Preserve `confirmed_at` as the trust gate for every downstream surface.
- Keep original source documents separate from structured values.
- Make uncertainty visible without turning the app into medical advice.
- Learn from public software patterns without expanding this app's boundary.

## Context Used

- `AGENTS.md`: confirmed-only, owner scope, no external private processing, no medical advice.
- `README.md`: V2 clean-by-default CMA intake and confirmed-only downstream behavior.
- `docs/current-operating-intent.md`: current active next move is issue #16 Consult Pack reconciliation/hardening.
- `docs/codex-prd.md`: product sentence and confirmed-only timeline/detail/compare/consult/export model.
- `docs/project-brief.md`: original V1 outcomes and out-of-scope boundaries.
- `docs/agent-learnings.md`: public research only; public comparables are pattern sources, not scope permission.

## Queries Used

- `official patient portal lab results design patient understanding reference range trends doctor questions study FHIR LOINC UCUM personal health record`
- `open source personal health record lab results PDF review confirmed values provenance audit FHIR GitHub local first`
- `practitioner discussion personal health record app lab results PDF FHIR local first audit trail user review Reddit Hacker News blog`
- `AI OCR lab report extraction human review confidence audit trail FHIR pipeline architecture risks healthcare documents`

## Evidence Map

### Official / Research Sources

Grade A:

- [HL7 PHR Manage Test Results](https://build.fhir.org/ig/HL7/phrsfm-ig/en/Requirements-PHRSFMR2-PH.2.5.3.html): PHRs should capture/display diagnostic test results, preserve source-reported ranges, support filtering, graphing, grouping, and annotations.
- [FHIR Observation](https://build.fhir.org/observation.html): Observation carries atomic values and metadata; DiagnosticReport gives workflow/report context; Provenance can record source detail.
- [US Core DiagnosticReport Lab](https://build.fhir.org/ig/HL7/US-Core/StructureDefinition-us-core-diagnosticreport-lab.html): lab reports group Observation resources and include status, category, code, patient, effective time, issued time, performer/results interpreter, and result references.
- [JMIR Human Factors lab-result UX study](https://humanfactors.jmir.org/2021/4/e26017): patient-facing lab-result displays benefit from graphical representations, clear takeaways, annotations, jargon support, trends, and question support for patient-provider interactions.
- [Patient-centered test-result interface design](https://pmc.ncbi.nlm.nih.gov/articles/PMC6078112/): patients need visual ranges, nontechnical descriptions, and tools that help them derive meaning from results.

Grade B:

- [BMC lab-results interface design](https://link.springer.com/article/10.1186/s12911-018-0589-7): contextualized displays can reduce perceived urgency of borderline values and help attention/filtering.
- [Communicating laboratory results to patients and families](https://www.degruyterbrill.com/document/doi/10.1515/cclm-2018-0634/html?lang=en): systems should signal whether differences are meaningful, support conversion/tools, and make data usable outside the portal.

### Open-Source Comparables

Grade B:

- [Mediqux](https://github.com/DMJoh/Mediqux): local-first medical records with lab PDF upload, local processing, review/verify before saving, manual entry, and no external APIs.
- [Fasten OnPrem](https://github.com/fastenhealth/fasten-onprem): self-hosted PHR/FHIR viewer, but open-source edition now avoids direct EHR integrations and relies on manual entry or FHIR bundle upload.
- [OpenVitals](https://github.com/zmeyer44/OpenVitals): useful pattern around provenance, confidence, source tracing, and audit; out-of-scope patterns include AI extraction/chat, wearables, and hosted/cloud paths.
- [Strand](https://github.com/potalora/strand): local-first FHIR timeline, source-backed records, no diagnosis/advice, local terminology matching, uncoded rather than guessed; out-of-scope patterns include OCR/cloud/hybrid extraction paths.
- [OwnChart](https://github.com/nickpdawson/OwnChart): strong evidence-vault model with raw sources immutable, facts/corrections layered on top, human review for candidates, and source-backed/user-canonical/unknown states; out-of-scope patterns include AI research agent and broad health cockpit.

### Practitioner Signals

Grade B/C:

- [HN: webapp to input/store blood test results](https://news.ycombinator.com/item?id=36025977): users report low-tech PDF storage, manual entry, need to graph results, and the importance of initial units/reference ranges.
- [Connected own health records](https://evestel.substack.com/p/i-connected-my-own-health-records): structured FHIR may be incomplete while PDFs carry more complete lab/procedure information; do not treat imported codes as ground truth without metadata/review.
- [Fasten Health review](https://unsubbed.co/tools/fasten-health/): useful caution that early public descriptions and current OSS capability can diverge; manual/FHIR-file workflows may be more realistic than seamless provider integration.

### Risk / Deferred Patterns

Grade B:

- [MedExtract lab-report pipeline](https://medextract.ai/en/blog/lab-report-pipeline-python): common AI/OCR pipeline shape is ingestion -> extraction -> validation -> FHIR transform -> delivery; low-confidence results are flagged for human review.
- [Mistral healthcare OCR cookbook](https://docs.mistral.ai/resources/cookbooks/mistral-ocr-hcls-ocr_hcls): production document-AI patterns include classification, confidence thresholds, human review, audit logging, FHIR validation.
- [AWS medical-record digitization](https://aws.amazon.com/blogs/architecture/automate-medical-record-digitization-with-amazon-bedrock-data-automation-and-aws-healthlake/): event-driven PDF -> extracted fields -> FHIR store pipelines depend on cloud services, IAM, KMS, audit trails, and low-confidence routing.
- [Healthcare OCR compliance pipeline](https://trueocr.com/designing-an-ocr-pipeline-for-compliance-heavy-healthcare-re): confidence should drive review, and every state transition should be auditable.
- [FhirBridgeAI](https://github.com/wocha/FhirBridgeAI): strong engineering pattern around ADRs, outbox, audit ledger, fail-closed design, and honest limitation tracking; too heavy for this app's current slice.

## What Other Engineers Would Likely Do Differently

1. FHIR-first engineers would model results as Observation/DiagnosticReport early.
   Safe transfer: shape exports and future mapping around Observation/DiagnosticReport concepts.
   Reject now: rewriting the local Laravel core into a FHIR server.

2. AI/OCR-first engineers would send PDFs through an extraction pipeline with confidence, review queues, and FHIR output.
   Safe transfer: confidence thresholds, review remainder, lineage, audit events.
   Reject now: external OCR/AI processing, automated clinical interpretation, cloud document pipelines.

3. Local-first engineers would keep everything private, store original documents, layer corrections over immutable sources, and avoid guessing codes.
   Safe transfer: source document + structured value separation, corrections/review, "unknown" and "uncoded rather than guessed".
   Reject now: broad personal health record expansion, multi-person/family scope, device sync.

4. Patient-portal UX engineers would optimize for comprehension: visual range context, changes over time, annotations, and question prep.
   Safe transfer: detail workspace and consult/export should answer "what changed?", "what is still unclear?", and "what do I ask my doctor?"
   Reject now: personalized medical interpretation, treatment suggestions, risk/urgency claims.

5. Audit/provenance engineers would make source lineage and state transitions first-class before more automation.
   Safe transfer: show source document, confirmed/draft state, extraction run, confirmation time, and comparable basis.
   Reject now: heavy compliance platform or tamper-evident ledger unless future production/privacy scope demands it.

## What Transfers Safely

- Keep `confirmed_at` as the downstream trust gate.
- Keep drafts visible only in review/remainder zones.
- Make blood-test detail the canonical workspace for one draw.
- Show source-backed lineage in consult/export: blood test, source document, value status, unit/range, comparison basis.
- Use "unknown" when unit/range/comparison basis is not trustworthy.
- Support context notes and doctor-question prep without answering the medical question.
- Prepare future FHIR export by aligning concepts, not by changing the core runtime.

## Defer Or Reject

- Runtime AI interpretation: reject for this boundary.
- Unreviewed OCR: reject.
- External PDF processing: reject.
- Provider/EHR integrations and SMART on FHIR: defer to a new ADR/privacy review.
- Wearable sync and correlations: defer.
- Health scoring, optimal ranges, supplement advice, treatment advice, diagnosis, urgency: reject.
- FHIR-server rewrite: reject for now.
- Broad PHR/family/multi-user expansion: defer.

## Best Post-PDF Workflow Direction

The best direction is not "better extraction" and not "FHIR-first rewrite".

The safest next direction is:

```text
confirmed-only blood-test detail -> consult/export pack -> lineage/audit clarity
```

That means the app should help the user see, for each selected blood test:

- confirmed values first;
- attention/unknown values separated from compact normal values;
- comparable changes versus previous owned tests;
- remaining drafts/review remainder kept visibly out of downstream counts;
- source documents visible as provenance, not raw storage paths;
- selected context notes;
- user-authored doctor questions;
- no advice, diagnosis, risk score, urgency, or extra-test recommendation.

## Recommended Next Slice

Proceed with issue #16 Consult Pack reconciliation/hardening.

Slice objective:

```text
One or more selected owned blood tests produce a print/export-friendly consult view with attention values first, compact normal values, comparable changes, source documents, selected context, and no medical claims or private-data leaks.
```

Implementation notes for the future build:

- Start by comparing current consult code/tests against `docs/codex-prd.md#phase-4-consult-pack` and issue #16.
- Keep query builders confirmed-only and owner-scoped.
- Add no external APIs.
- Treat FHIR/LOINC/UCUM as optional future export metadata, not required for this slice.
- If adding lineage display, prefer existing source-document and extraction-run metadata before adding schema.
- Browser QA must cover desktop/mobile consult, selected blood tests, draft exclusion, source-document path hiding, and no forbidden medical language.

## Known Unknowns

- Whether issue #16 already covers every consult/export acceptance criterion.
- Whether the current UI already exposes enough provenance without schema changes.
- Whether a future FHIR export should be CSV/print-first, FHIR Bundle export, or both.
- Whether user-authored consult questions are best stored durably or only submitted per export.

## Signal Distortions

- GitHub READMEs often describe aspirational scope; treat them as architecture signals, not product proof.
- AI/OCR vendor posts are useful for pipeline shape but biased toward external processing.
- Patient-portal studies often assume provider context and clinician messaging that this personal app does not have.
- US FHIR/provider access patterns do not directly transfer to a private Belgian/Flemish personal app.

## No-Build Recommendation

Do not implement from this report until the user explicitly asks to build. The current output is a research-backed architecture recommendation only.
