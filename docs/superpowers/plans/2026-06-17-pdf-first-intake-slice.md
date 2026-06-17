# PDF-First Intake Slice Plan

Date: 2026-06-17

Status: planning baseline after PDF-first scope correction.

Do not execute this plan until the pre-scaffold review is refreshed for the
PDF-first scope.

## Goal

Prove the smallest useful loop:

```text
login -> upload lab PDF -> private blood test document -> review/confirm values
-> status -> biomarker history -> compare two tests
```

## Scope

In this slice:

- Laravel Livewire starter kit;
- authenticated owner only;
- PDF/document upload as the first blood-test action;
- private document storage;
- blood test status: `uploaded`, `reviewing`, `confirmed`;
- small biomarker catalog;
- review/confirmation form for biomarker values;
- status calculation;
- biomarker history;
- compare two blood tests;
- feature tests for owner isolation and document access;
- `sh scripts/validate.sh` upgraded to Laravel-phase checks.

Out of this slice:

- automatic OCR;
- AI interpretation;
- provider integrations;
- consult export;
- reminders;
- context notes;
- public sharing;
- medical advice.

## Tasks

1. Refresh pre-scaffold review.
   - Verify ADR-0005, updated V1 spec, and this plan.
   - Record whether the PDF-first slice is `GO`, `GO WITH CHANGES`, or `NO-GO`.

2. Scaffold Laravel only after the review gate.
   - Use the Livewire starter kit unless the refreshed review changes ADR-0003.
   - Preserve all project-control docs.

3. Replace planning validation.
   - `sh scripts/validate.sh` should run Laravel tests, frontend build, and
     whitespace checks.

4. Implement authenticated owner baseline.
   - Health records belong to one user.
   - Tests prove User A cannot access User B's blood tests or documents.

5. Implement PDF-first blood-test intake.
   - Upload document privately.
   - Create blood test with status `uploaded`.
   - Confirm date/lab/title and move to `reviewing`.

6. Implement review/confirmation of values.
   - Show the document access near the result-entry form.
   - Let the user create/select biomarker, value, unit, range.
   - Save confirmed values.

7. Implement status logic.
   - Use existing `low`, `normal`, `high`, `unknown` rules.
   - Keep logic in testable domain code.

8. Implement history and compare.
   - Biomarker history uses confirmed values only.
   - Compare handles missing values and unit mismatches honestly.

9. Run manual QA with a real-looking lab PDF.
   - Upload two documents.
   - Confirm shared biomarkers.
   - View history.
   - Compare tests.
   - Confirm no medical advice appears.

## Validation

The slice is not complete until `sh scripts/validate.sh` proves:

- auth-protected access;
- owner isolation;
- private document access;
- blood test upload/review flow;
- biomarker result confirmation;
- status edge cases;
- biomarker history ordering;
- compare-two-tests behavior;
- frontend build.
