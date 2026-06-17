# Pre-Scaffold Review Result

Date: 2026-06-17

Reviewer stance:

- Internal planning review using the requested reviewer profiles:
  - senior Laravel application architect;
  - application security/privacy engineer;
  - AI Architect-style product-system check.

This is not an external human review and not a medical review.

## Decision

Decision: GO WITH CHANGES

Meaning:

- The PDF-first scope is coherent enough to scaffold Laravel.
- Scaffold work may start only after the required pre-scaffold changes below
  are complete and validation is green.
- The first implementation must follow
  `docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md`, not the
  superseded manual-first plan.
- OCR, AI interpretation, provider integrations, consult export, reminders,
  context notes, public sharing, and medical advice must stay out of the first
  slice.

## Blocking Issues

None after the review packet, handoff, and validation checks are updated for
PDF-first intake.

## Important Issues

- PDF upload enters the first slice, so private file storage is no longer a
  later concern. It must be implemented with owner isolation from the first
  file feature.
- The first slice is still broader than the previous manual-first slice. Keep
  it to upload, review/confirm values, status, history, and compare.
- Do not add automatic OCR in this slice. Any extraction can only appear later
  as a draft layer that requires user confirmation.
- Health data ownership and private document access must be proven before any
  real personal PDF is uploaded.
- The superseded
  `docs/superpowers/plans/2026-06-16-first-vertical-slice.md` must remain
  historical only.

## Minor Issues

- The implementation plan should decide whether V1 accepts PDF only or PDF plus
  images before file validation is coded.
- The first UI can use a private download/open action beside the review form;
  inline PDF preview is useful but should not block the slice.

## Required Changes Before Scaffold

- Ensure the review request points to the PDF-first plan.
- Ensure `scripts/validate.sh` checks ADR-0005 and this refreshed review result.
- Confirm `sh scripts/validate.sh` passes after the review update.

## Required Changes After Scaffold But Before First Personal Data

- Replace planning validation with Laravel-phase validation.
- Add status-calculation tests before building broad UI.
- Add owner-isolation tests proving User A cannot access User B's blood tests,
  documents, biomarkers, results, context notes, or exports.
- Add private document storage/access tests before real personal PDFs are used.
- Store uploaded documents outside public web paths.
- Avoid logging filenames, biomarker values, ranges, or document contents in
  application logs.
- Run manual QA through the real first workflow: upload two PDFs, confirm shared
  biomarkers, view history, compare tests, and confirm no medical advice
  appears.

## Scorecard

### Product And Scope

| Check | Score | Notes |
| --- | ---: | --- |
| Product is clearly a personal tracking system, not medical advice. | 3 | Repeated across brief, spec, ADR-0005, copy rules, and validation. |
| V1 scope is narrow enough to build. | 2 | Full V1 remains broad, but the active slice is limited to PDF intake, review, status, history, and compare. |
| Out-of-scope boundaries are explicit. | 3 | Unreviewed OCR, AI, integrations, recommendations, sharing, and medical advice are excluded. |
| First slice proves real product value. | 3 | PDF upload plus confirmed values proves the actual user workflow better than manual-first entry. |
| First slice avoids unreviewed OCR, AI, integrations, and medical recommendations. | 3 | Explicit in ADR-0005, V1 spec, and slice plan. |
| Product-system check keeps the first slice focused. | 3 | It names PDF upload without review/private storage as a break point. |

Average: 2.8

### Laravel Architecture

| Check | Score | Notes |
| --- | ---: | --- |
| Livewire starter kit is justified for V1. | 3 | Still fits a private, authenticated, workflow-heavy app. |
| Domain logic is outside Livewire components. | 3 | Status, comparison, privacy checks, and export rules are assigned to testable domain/application code. |
| Status calculation is testable as plain domain logic. | 3 | V1 spec defines deterministic edge cases. |
| Data model is coherent for blood tests, documents, biomarkers, and results. | 2 | Conceptual model is coherent; exact migrations still need implementation proof. |
| Compare-two-tests workflow has clear rules. | 3 | Same-unit delta, missing values, and unit mismatch rules are named. |
| Filament is not introduced prematurely. | 3 | Deferred unless catalog management proves to be the bottleneck. |
| ADRs capture the stack and architecture decisions. | 3 | ADR-0001 through ADR-0005 cover control plane, ADRs, Livewire, owner/privacy, and PDF-first intake. |

Average: 2.9

### Security And Privacy

| Check | Score | Notes |
| --- | ---: | --- |
| All health data is owner-scoped. | 3 | ADR-0004 and V1 spec make this non-negotiable. |
| User isolation tests are required. | 3 | Required in spec, plan, and this review result. |
| Private document storage is specified for PDF-first upload work. | 3 | Private storage and document access tests are required before real PDFs. |
| Export/delete requirements are visible. | 2 | V1 scope includes them; first slice excludes export implementation. |
| Medical boundary is reflected in copy rules. | 3 | Copy rules forbid diagnosis, advice, risk prediction, and health scoring. |
| Production/privacy checklist exists before deployment work. | 3 | Present under `docs/ops/`, with document review/confirmation added. |
| ADRs capture owner-scoped private health data before scaffold. | 3 | ADR-0004 plus ADR-0005 cover owner scoping and PDF-first document handling. |

Average: 2.9

### Workflow And Validation

| Check | Score | Notes |
| --- | ---: | --- |
| Planning baseline is committed before scaffold. | 2 | Baseline exists; this PDF-first review update still needs commit/push if remote continuity matters. |
| Handoff points to the actual checkpoint. | 2 | Handoff names PDF-first correction; update again after this review if committing. |
| Validation protocol explains marker resolution. | 3 | Present. |
| `scripts/validate.sh` proves current phase. | 3 | Planning validation passes and blocks accidental scaffold files. |
| Plan updates validation immediately after scaffold. | 3 | The PDF-first plan requires Laravel tests, frontend build, and document access tests. |
| CI will run validation after implementation begins. | 2 | CI file exists; Laravel-phase CI must be updated after scaffold. |
| Evidence index and ADRs are part of validation. | 3 | Included in `scripts/validate.sh`. |

Average: 2.6

## Reviewer Confidence

Medium-high.

The project is ready to scaffold after this review update is validated. The
main risk is not Laravel or Livewire; it is treating PDF upload as "automatic
truth." The build should keep the document as source, require user-confirmed
structured values, and prove owner isolation before real personal lab PDFs are
used.
