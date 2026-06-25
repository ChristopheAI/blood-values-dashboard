# PDF-First Intake Test Conversion

Date: 2026-06-18

Purpose:

- Convert the engineering source radar into concrete first-slice test gates.
- Trace the original pre-scaffold test contract into the current Laravel/Pest
  implementation tests.
- Explain exactly which rules were converted and why.

Implementation status:

- This file began as a pre-scaffold conversion artifact. The Laravel app now
  exists, and the V1/V2 intake contract has been converted into real Pest,
  Livewire, architecture, and browser-smoke tests.
- Keep this document as the source trace for why those tests exist, but do not
  treat its pre-scaffold wording as the current repo state.
- ADR-0011 extends the original V1/V2 rule: clean, unambiguous,
  high-confidence extracted rows may be auto-confirmed; below-threshold rows
  remain drafts and stay out of downstream confirmed-only workflows.

Planning boundary:

- This document itself does not create Laravel code.
- The executable tests now live in `tests/Feature`, `tests/Feature/Architecture`,
  and `tests/Browser`.
- The repository now validates the implementation stage through
  `sh scripts/validate.sh`, including frontend build, Pint, PHPStan, Pest,
  browser smoke, and whitespace checks.
- The named tests below remain the historical minimum contract for the original
  PDF-first intake slice.

Primary source:

- `docs/research/2026-06-18-engineering-source-radar.md`

Related sources:

- `docs/v1-spec.md`
- `docs/validation-protocol.md`
- `docs/adr/0004-manual-entry-and-owner-scoped-health-data.md`
- `docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md`
- `docs/adr/0007-use-staged-laravel-quality-ladder.md`

## Conversion Summary

Converted now:

1. private lab-PDF storage;
2. generated storage names;
3. sanitized original display filename;
4. owner-authorized document downloads;
5. unauthenticated denial;
6. cross-user denial;
7. upload validation;
8. Livewire action-parameter distrust;
9. Livewire public-property distrust;
10. delete blocks document access;
11. package review before sensitive dependencies;
12. no public/external processing path for lab PDFs.

Originally deferred:

1. architecture tests for module boundaries;
2. browser workflow proof;
3. dependency/security automation;
4. backup/activity-log package behavior.

Current implementation note:

- Architecture and browser checks now exist for the implemented slice, including
  privacy/medical-copy boundaries and a PDF-first Dusk smoke flow. Broader
  dependency/security automation and backup/activity-log behavior remain
  deferred unless a later ADR expands scope.

Why some items were deferred originally:

- They require a Laravel scaffold, concrete namespaces, CI setup, package
  decisions, or deployment context. The correct move now is to define the test
  contract, not invent code around missing app files.

## Future Test Files

These are target files for the Laravel implementation phase.

- `tests/Feature/BloodTests/BloodTestPdfUploadTest.php`
- `tests/Feature/BloodTests/BloodTestDocumentDownloadTest.php`
- `tests/Feature/BloodTests/BloodTestDocumentDeletionTest.php`
- `tests/Feature/Livewire/BloodTestReviewAuthorizationTest.php`
- `tests/Feature/Architecture/PrivacyBoundaryTest.php`
- `tests/Feature/Architecture/PackageBoundaryTest.php`

Exact class, model, route, and component names may be adjusted to the scaffold's
real naming, but the behaviors below must stay intact.

## Test Conversion Matrix

### 1. Lab PDFs Are Private Source Documents

Converted to:

- `BloodTestPdfUploadTest::owner_can_upload_a_lab_pdf_to_private_storage`

Why:

- The original lab PDF is the V1 source artifact. If it is public, the product
  fails its privacy premise before biomarker logic matters.

Proof expected:

- authenticated owner uploads a valid PDF;
- a blood-test document record is created;
- file is stored on the private disk;
- no public disk file is created;
- blood test starts as `uploaded` or the implemented equivalent.

Source basis:

- Laravel File Storage;
- Laravel HTTP Tests;
- Livewire File Uploads;
- Engineering Source Radar rules 1 and 2.

### 2. Storage Names Are Generated, Not User Supplied

Converted to:

- `BloodTestPdfUploadTest::lab_pdf_storage_path_does_not_use_original_filename`

Why:

- User-controlled filenames are unsafe as storage paths and may leak private
  details from lab documents.

Proof expected:

- upload file named `synthetic-private-lab-2026-05-19.pdf`;
- stored path does not contain the original filename;
- stored path contains a generated identifier or server-chosen path segment;
- original filename is stored only as sanitized display metadata.

Source basis:

- Nazar Boyko file-upload pipeline;
- Stephen Rees-Carter file-upload guidance;
- Engineering Source Radar rules 2 and 3.

### 3. Original Filename Is Sanitized Metadata

Converted to:

- `BloodTestPdfUploadTest::original_filename_is_sanitized_before_display_storage`

Why:

- The user should recognize the document later, but the app must not trust a
  browser-supplied filename as a path, header, or raw UI string.

Proof expected:

- malicious-looking filename with slashes/control characters is uploaded;
- display filename is normalized or rejected according to implementation rules;
- stored path remains generated;
- response/page does not render unsafe raw filename.

Source basis:

- Laravel Validation;
- Nazar Boyko file-upload pipeline;
- private document requirements in V1 spec.

### 4. PDF Upload Validates Type And Size

Converted to:

- `BloodTestPdfUploadTest::pdf_upload_rejects_non_pdf_files`
- `BloodTestPdfUploadTest::pdf_upload_rejects_files_above_configured_size`

Why:

- A lab-PDF intake path is an upload surface. It must accept the intended source
  document and reject obvious wrong or excessive files.

Proof expected:

- fake PDF with `application/pdf` is accepted;
- fake text/image/PHP-like file is rejected;
- oversize file is rejected;
- validation error is visible without storing a permanent document.

Source basis:

- Laravel Validation;
- Laravel HTTP Tests;
- Livewire File Uploads;
- Engineering Source Radar rule 8.

### 5. Guests Cannot Upload Or Download Lab Documents

Converted to:

- `BloodTestPdfUploadTest::guest_cannot_access_pdf_intake`
- `BloodTestDocumentDownloadTest::guest_cannot_download_lab_pdf`

Why:

- Login is the first privacy boundary. A private health-data app cannot expose
  upload or download routes to unauthenticated users.

Proof expected:

- guest request to PDF intake redirects to login or returns unauthorized;
- guest request to document download redirects to login or returns unauthorized;
- no file bytes are returned.

Source basis:

- Laravel Authorization;
- Laravel HTTP Tests;
- V1 privacy baseline.

### 6. Owners Can Download Their Own Lab PDF

Converted to:

- `BloodTestDocumentDownloadTest::owner_can_download_their_own_lab_pdf`

Why:

- The app must help the user retrieve the source document while keeping access
  controlled.

Proof expected:

- owner uploads/stores a lab PDF;
- owner hits the download/open route;
- response is a download or controlled stream;
- response uses safe headers and does not expose a raw public URL.

Source basis:

- Laravel File Storage;
- Laravel HTTP Tests;
- V1 "documents and values terugvinden" goal.

### 7. Cross-User Document Access Is Forbidden

Converted to:

- `BloodTestDocumentDownloadTest::user_cannot_download_another_users_lab_pdf`
- `BloodTestDocumentDownloadTest::user_cannot_view_another_users_blood_test_document_record`

Why:

- Owner isolation is the central privacy invariant. It must be proven at the
  document layer, not only at the blood-test page layer.

Proof expected:

- user A has a document;
- user B is authenticated;
- user B gets forbidden/not found when requesting user A's document;
- no file bytes, metadata, or signed route target leaks.

Source basis:

- Laravel Authorization;
- Livewire Security;
- ADR-0004 owner-scoped health data;
- Engineering Source Radar rule 7.

### 8. Cross-User Blood-Test Actions Are Forbidden

Converted to:

- `BloodTestReviewAuthorizationTest::user_cannot_open_review_for_another_users_blood_test`
- `BloodTestReviewAuthorizationTest::user_cannot_confirm_values_for_another_users_blood_test`

Why:

- Review/confirmation turns source documents into structured health data.
  Ownership must be rechecked before confirmation, not only before display.

Proof expected:

- user B cannot open user A's review screen/component;
- user B cannot submit confirmed values for user A's blood test;
- database remains unchanged.

Source basis:

- Laravel Authorization;
- Livewire Security;
- ADR-0005 PDF-first intake with confirmed values.

### 9. Livewire Action Parameters Are Untrusted

Converted to:

- `BloodTestReviewAuthorizationTest::tampered_livewire_action_parameter_cannot_confirm_another_users_blood_test`
- `BloodTestReviewAuthorizationTest::tampered_livewire_action_parameter_cannot_download_another_users_document`

Why:

- Livewire action parameters are browser-controlled. If a blood-test ID or
  document ID can be changed client-side, the server must still reject the
  action.

Proof expected:

- component renders for user B with user B data;
- test calls the Livewire action using user A's ID;
- action returns forbidden/unauthorized or validation failure;
- user A records and files remain untouched.

Source basis:

- Livewire Security;
- Stephen Rees-Carter public-property/action-parameter warning;
- Engineering Source Radar rule 5.

### 10. Livewire Public Properties Are Untrusted

Converted to:

- `BloodTestReviewAuthorizationTest::tampered_livewire_public_property_cannot_switch_owner_context`

Why:

- Public properties look like PHP properties but travel through the browser.
  Any property that controls ownership or selected blood test must be locked,
  model-bound, or reauthorized.

Proof expected:

- test mutates a public `bloodTestId`, `documentId`, or implemented equivalent;
- component cannot use that tampered value to read or mutate another user's
  data;
- test proves server-side authorization, not only UI hiding.

Source basis:

- Livewire Security;
- Securing Laravel "Don't Trust Public Livewire Properties";
- Engineering Source Radar rule 5.

### 11. Deleting A Blood Test Blocks Document Access

Converted to:

- `BloodTestDocumentDeletionTest::deleting_blood_test_removes_or_blocks_its_lab_pdf`

Why:

- Privacy/delete is V1 behavior. A deleted blood test must not leave an
  accessible private document behind.

Proof expected:

- owner uploads a PDF;
- owner deletes the blood test or attached document according to implementation
  rules;
- subsequent download request fails;
- file is deleted or access is blocked and documented.

Source basis:

- V1 export/delete requirements;
- Laravel File Storage;
- ADR-0004 privacy/data-control boundary.

### 12. No Public URL Is Exposed For Lab PDFs

Converted to:

- `BloodTestDocumentDownloadTest::lab_pdf_page_does_not_expose_public_storage_url`

Why:

- A direct `/storage/...` URL or public disk URL turns a private source document
  into shareable media.

Proof expected:

- blood-test detail/review page includes a download/open action;
- rendered HTML does not contain a public storage URL for the PDF;
- action route is authenticated and owner-authorized.

Source basis:

- Laravel File Storage;
- Livewire File Uploads;
- Engineering Source Radar rules 1, 2, and 4.

### 13. Package Review Before Sensitive Dependencies

Converted to:

- `PackageBoundaryTest::sensitive_dependency_requires_documented_review`

Why:

- Packages that touch files, auth, exports, jobs, logs, or health data can
  duplicate or expose private data. This is a control-plane test/architecture
  gate, not a product UI test.

Proof expected:

- once Composer exists, architecture/check script inspects sensitive packages or
  a maintained allowlist;
- any package in sensitive categories has a review note or ADR;
- build fails when a sensitive package is added without review.

Source basis:

- Freek/Spatie engineering profile;
- Spatie package decision model;
- ADR-0007 staged Laravel quality ladder.

### 14. No Runtime Exa, Firecrawl, Or AI Processing Of Lab PDFs

Converted to:

- `PrivacyBoundaryTest::lab_pdf_intake_has_no_runtime_exa_firecrawl_or_ai_processor`

Why:

- Public research tools are allowed for planning. They are not allowed in the
  runtime path for private lab PDFs in V1.

Proof expected:

- no production PDF intake service depends on Exa, Firecrawl, or AI clients;
- any future parser is local or separately approved by ADR/privacy review;
- architecture test or validation check fails if runtime references appear.

Source basis:

- ADR-0006 Exa/Firecrawl public-research boundary;
- ADR-0008 future AI proposal-only boundary;
- V1 out-of-scope rules.

## Deferred Conversion

### Architecture Tests For Module Boundaries

Deferred target:

- `PrivacyBoundaryTest::domain_code_does_not_depend_on_http_livewire_state`
- `PrivacyBoundaryTest::medical_advice_copy_is_not_present_in_user_facing_views`

Why deferred:

- Namespaces and views do not exist before scaffold.
- These should be added once the first domain/action/view structure exists.

### Browser Workflow Proof

Deferred target:

- browser test for `login -> upload PDF -> review -> confirm -> history -> compare`.

Why deferred:

- Requires a running Laravel app and UI routes.

### Backup, Activity Log, Health Check Package Behavior

Deferred target:

- package-specific tests or ADRs if those packages are adopted.

Why deferred:

- Those packages are not V1-first dependencies.
- Installing them now would broaden scope instead of proving PDF intake.

## Why These Tests First

These tests protect the non-negotiable product promises:

- "Waar staat mijn bloeduitslag van toen?" requires private document retrieval.
- "Wat is er veranderd tegenover vorige keer?" requires confirmed structured
  data, not arbitrary uploads.
- "Kan ik mijn data exporteren of verwijderen?" requires owner-scoped data
  control.
- "Alles achter login" requires authentication and authorization checks.
- "Geen diagnosemachine" requires keeping AI/medical interpretation out of the
  intake path.

If these tests fail, the Laravel app may run, but the product is not safe enough
to use with real lab data.
