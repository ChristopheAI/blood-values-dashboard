# ADR-0005: Use PDF-First Intake With Confirmed Values

## Status

Accepted

## Context

The primary real-world artifact is the lab-result PDF. The user does not first
have an abstract blood-test record; the user has a blood draw result document
that must be stored, found again, reviewed, and converted into structured
follow-up data.

ADR-0004 correctly protected against untrusted automation, but it made manual
entry sound like the product's first action. That no longer matches the desired
workflow. V1 should start from the uploaded document while still refusing to
treat unreviewed extraction as truth.

## Decision

Use PDF-first intake for V1:

- the first blood-test action is uploading the original lab-result PDF or
  supported document;
- the system creates a blood test in an `uploaded` or `reviewing` state;
- the original document is stored privately and remains separate from
  structured values;
- biomarker values become usable for status, history, comparison, and export
  only after user review, correction, or confirmation;
- manual entry remains the required fallback when a value cannot be extracted or
  trusted;
- unreviewed OCR, AI interpretation, and provider integrations remain out of
  the first slice.

Owner scoping, `unknown` status, no-medical-advice language, export/delete, and
private document handling remain V1 requirements.

## Evidence

- Source: user correction in project session
  - Claim type: fact
  - Summary: The intended workflow is login, upload the blood-test PDF, then let
    the system help process what comes after.

- Source: `docs/project-brief.md`
  - Claim type: fact
  - Summary: The product problem starts with scattered lab PDFs, portals,
    screenshots, notes, and difficulty retrieving original results.

- Source: `docs/v1-spec.md`
  - Claim type: fact
  - Summary: The updated V1 spec defines PDF upload, private document storage,
    review/confirmation, status, history, and comparison as the first useful
    loop.

- Source: `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
  - Claim type: fact
  - Summary: The earlier ADR already identified automation risk, owner scoping,
    and `unknown` status as important safety boundaries.

## Considered Options

- Manual-first entry without requiring the original PDF.
- PDF-first intake with human review and confirmation.
- Fully automatic OCR/PDF extraction first.
- Lab/provider integration first.
- AI interpretation first.

## Decision Drivers

- The original lab document is the real source users need to find again.
- PDF-first matches the user's mental workflow.
- Reviewed structured values are safer than unreviewed extraction.
- Private file storage and owner isolation must be proven before real personal
  lab documents are used.
- The app must remain a personal organization and follow-up system, not a
  diagnosis or advice engine.

## Consequences

- The first vertical slice now includes private document upload.
- The previous pre-scaffold review result for a manual-first slice must be
  refreshed before scaffold work continues.
- Validation must include private document access checks after Laravel is
  scaffolded.
- The UI should lead with uploading a blood-test document, then reviewing values.
- OCR or AI may be explored later only as an assistive draft layer that requires
  user confirmation.

## Confidence

High

## Follow-Up Questions

- Should the first PDF-first slice show the PDF inline or provide a private
  download/open action beside the review form?
- Which file types are accepted in V1: PDF only, or PDF plus images?
- Should assisted text extraction be evaluated after the manual review flow is
  proven?
