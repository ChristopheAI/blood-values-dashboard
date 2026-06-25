#!/usr/bin/env python3
from __future__ import annotations

import datetime as dt
import os
import zipfile
from dataclasses import dataclass
from pathlib import Path
from xml.sax.saxutils import escape


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "docs" / "verification" / "behavior-matrix.xlsx"


HEADERS = [
    "Area",
    "Feature",
    "User story",
    "Expected behavior from code",
    "Route / surface",
    "Source files",
    "Existing tests",
    "Verification method",
    "Status",
    "Actual result",
    "Defects",
    "Defect type",
    "Fix reference",
    "Notes / open questions",
]


@dataclass(frozen=True)
class Row:
    area: str
    feature: str
    story: str
    expected: str
    surface: str
    sources: str
    tests: str
    method: str
    status: str = "Unverified"
    actual: str = "Not yet verified in this matrix."
    defects: str = "-"
    defect_type: str = "-"
    fix: str = "-"
    notes: str = "-"

    def values(self) -> list[str]:
        return [
            self.area,
            self.feature,
            self.story,
            self.expected,
            self.surface,
            self.sources,
            self.tests,
            self.method,
            self.status,
            self.actual,
            self.defects,
            self.defect_type,
            self.fix,
            self.notes,
        ]


ROWS = [
    Row(
        "Auth/account",
        "Registration",
        "As a new owner I want to register so private blood-data routes can be tied to my account.",
        "Fortify registration routes render and create an authenticated user; private app routes live behind auth and, for V2 app surfaces, verified middleware.",
        "GET /register; POST /register; auth middleware group",
        "routes/web.php:27; routes/settings.php:7; resources/views/pages/auth/register.blade.php",
        "tests/Feature/Auth/RegistrationTest.php",
        "Pest auth feature tests plus private-route smoke.",
    ),
    Row(
        "Auth/account",
        "Login and logout",
        "As a returning owner I want to log in and log out so my health data is not public.",
        "Fortify login routes authenticate the user; logout action invalidates the session and regenerates CSRF token.",
        "GET /login; POST /login; POST /logout",
        "resources/views/pages/auth/login.blade.php; app/Livewire/Actions/Logout.php:15",
        "tests/Feature/Auth/AuthenticationTest.php",
        "Pest auth feature tests.",
    ),
    Row(
        "Auth/account",
        "Email verification gate",
        "As an owner I want private V2 pages gated behind verified access so sensitive data is not exposed to unverified accounts.",
        "Dashboard, blood tests, documents, consult, biomarkers, context notes, reminders, and data settings are inside auth plus verified route groups.",
        "Route group middleware auth, verified",
        "routes/web.php:27; routes/settings.php:13",
        "tests/Feature/Auth/EmailVerificationTest.php",
        "Pest route/auth tests and route-list inspection.",
    ),
    Row(
        "Auth/account",
        "Password confirmation for sensitive settings",
        "As an owner I want sensitive settings protected by password confirmation before data export or deletion.",
        "settings/data, settings/security, data export, and delete-all routes use password.confirm middleware in addition to auth and verified.",
        "GET /settings/data; POST /settings/data/export; DELETE /settings/data; GET /settings/security",
        "routes/settings.php:16; routes/settings.php:22; routes/settings.php:28; routes/settings.php:34",
        "tests/Feature/Auth/PasswordConfirmationTest.php; tests/Feature/Privacy/DataExportAndDeletionTest.php",
        "Pest feature tests plus route middleware inspection.",
    ),
    Row(
        "Auth/account",
        "Security settings",
        "As an owner I want to manage password, two-factor access, and passkeys for account protection.",
        "Security Livewire page renders password update, 2FA, recovery-code, and passkey controls using Fortify and Passkeys routes.",
        "GET /settings/security; user/two-factor-*; user/passkeys*",
        "routes/settings.php:16; resources/views/pages/settings/security.blade.php",
        "tests/Feature/Settings/SecurityTest.php; tests/Feature/Auth/TwoFactorChallengeTest.php",
        "Pest settings/security tests.",
    ),
    Row(
        "Auth/account",
        "Profile and account deletion",
        "As an owner I want to update or delete my account without weakening private health-data boundaries.",
        "Profile settings update owner profile fields; delete-user modal requires password through Livewire before account removal.",
        "GET /settings/profile; Livewire delete-user modal",
        "routes/settings.php:10; resources/views/pages/settings/profile.blade.php; resources/views/pages/settings/delete-user-modal.blade.php",
        "tests/Feature/Settings/ProfileUpdateTest.php",
        "Pest settings feature tests.",
    ),
    Row(
        "Dashboard",
        "Empty upload-first dashboard",
        "As an owner without confirmed values I want the dashboard to invite PDF upload instead of showing unrelated health widgets.",
        "Dashboard controller passes latest summary; view shows upload dropzone and hides personal overview when no confirmed owned values exist.",
        "GET /dashboard",
        "app/Http/Controllers/DashboardController.php:12; resources/views/dashboard.blade.php; resources/views/blood-tests/_upload-dropzone.blade.php",
        "tests/Feature/DashboardTest.php:test_empty_dashboard_is_upload_first_without_metadata_or_account_fields",
        "Pest feature test and Dusk intake smoke.",
    ),
    Row(
        "Dashboard",
        "Confirmed latest upload summary",
        "As an owner I want my latest confirmed blood-test values summarized without drafts or foreign rows.",
        "BuildLatestUploadSummary selects the latest owned blood test with confirmed owned biomarker results and emits attention, normal, review, range, and label rows.",
        "GET /dashboard; dashboard blood-results overview",
        "app/Domain/Dashboard/BuildLatestUploadSummary.php:39; resources/views/dashboard/_blood-results-overview.blade.php",
        "tests/Feature/DashboardTest.php:test_dashboard_latest_upload_digest_uses_confirmed_owned_results_only",
        "Pest dashboard feature tests.",
    ),
    Row(
        "Dashboard",
        "Dashboard support panels",
        "As an owner I want recent tests, pinned biomarkers, next reminder, and quick actions near the dashboard summary.",
        "Dashboard view renders recent blood tests, pinned biomarkers, next open reminder, and links to upload/context/reminders/consult when data exists.",
        "GET /dashboard",
        "resources/views/dashboard.blade.php:48",
        "tests/Feature/DashboardTest.php; tests/Feature/Reminders/ReminderTest.php; tests/Feature/Biomarkers/PinnedBiomarkerTest.php",
        "Pest dashboard/reminder/pin tests.",
    ),
    Row(
        "PDF-first intake",
        "PDF upload validation and private storage",
        "As an owner I want to upload a lab PDF that is stored privately and connected to a new blood test.",
        "StoreBloodTestController requires a PDF up to 12000 KB, stores a UUID-named file on local disk under a user-scoped folder, sanitizes original filename metadata, creates BloodTest and BloodTestDocument, then redirects to detail unless streaming.",
        "GET /blood-tests; POST /blood-tests",
        "app/Http/Controllers/BloodTests/StoreBloodTestController.php:20; resources/views/blood-tests/_upload-dropzone.blade.php",
        "tests/Feature/BloodTests/BloodTestPdfUploadTest.php; tests/Feature/Intake/AssistedPdfExtractionTest.php",
        "Pest upload tests plus Dusk upload smoke.",
    ),
    Row(
        "PDF-first intake",
        "Streaming intake progress",
        "As an owner I want upload progress to show extract, values, status, and trend stages while the app processes the PDF.",
        "If X-Intake-Stream is 1 and NDJSON is accepted, controller streams stage/state events and final redirect; dropzone JS falls back to normal submit if streaming fails.",
        "POST /blood-tests with X-Intake-Stream",
        "app/Http/Controllers/BloodTests/StoreBloodTestController.php:74; resources/views/blood-tests/_upload-dropzone.blade.php:59",
        "tests/Feature/BloodTests/BloodTestPdfUploadTest.php; tests/Browser/PdfFirstIntakeSmokeTest.php",
        "Pest progress assertions and Dusk browser smoke.",
    ),
    Row(
        "Extraction",
        "Local parser boundary",
        "As an owner I want extraction to stay local without runtime AI, OCR, network, or external health-data processing.",
        "Extraction services use local PDF parsing and text/layout heuristics; architecture tests forbid runtime research AI/OCR/outbound network clients in intake.",
        "RunBloodTestExtraction; ExtractBiomarkerDrafts",
        "app/Domain/Intake/ExtractBiomarkerDrafts.php; app/Domain/Intake/RunBloodTestExtraction.php",
        "tests/Feature/Architecture/PrivacyBoundaryTest.php; tests/Feature/Architecture/PackageBoundaryTest.php",
        "Architecture tests and package boundary tests.",
    ),
    Row(
        "Extraction",
        "Inline and tabular candidate extraction",
        "As an owner I want text-layer PDFs to produce candidate biomarker rows when the layout is parseable.",
        "ExtractBiomarkerDrafts first attempts inline text patterns, then positioned tabular extraction and CMA layout fallback for supported layouts.",
        "Extraction domain",
        "app/Domain/Intake/ExtractBiomarkerDrafts.php:20; app/Domain/Intake/ExtractTabularBiomarkerCandidates.php:19",
        "tests/Feature/Intake/AssistedPdfExtractionTest.php; tests/Unit/TabularBiomarkerExtractionTest.php",
        "Unit parser tests and feature upload tests with synthetic fixtures.",
    ),
    Row(
        "Extraction",
        "CMA fixed-layout extraction",
        "As an owner with a trusted CMA-style PDF I want fixed-column rows extracted deterministically.",
        "ExtractCmaLayoutBiomarkerCandidates recognizes CMA column headers and parses name, value, unit, and reference columns with fixed positions.",
        "CMA layout parser",
        "app/Domain/Intake/ExtractCmaLayoutBiomarkerCandidates.php:14",
        "tests/Unit/CmaLayoutBiomarkerExtractionTest.php; tests/python/test_cma_layout_lab.py",
        "Unit parser tests and Python parser-lab tests.",
    ),
    Row(
        "Extraction",
        "Confidence-gated auto-confirm",
        "As an owner I want clean trusted extraction rows to become active only when deterministic trust gates pass.",
        "RunBloodTestExtraction stores candidates; rows auto-confirm only when matched/imported biomarker, confidence, unit, value, and reference checks pass. Auto-confirmed rows get confirmed_at and status; others remain drafts.",
        "RunBloodTestExtraction storeDrafts",
        "app/Domain/Intake/RunBloodTestExtraction.php:111",
        "tests/Feature/Intake/AssistedPdfExtractionTest.php:auto-confirms high confidence catalog matched candidates and leaves lower confidence rows as drafts",
        "Pest intake feature tests.",
    ),
    Row(
        "Extraction",
        "Duplicate and ambiguous extraction handling",
        "As an owner I want ambiguous duplicate extracted rows to require review instead of silent auto-confirm.",
        "Trusted CMA duplicate names are disambiguated by unit only when each duplicate has a distinct non-empty unit; duplicate matched catalog IDs and ambiguous same-unit prefix siblings stay in review.",
        "RunBloodTestExtraction duplicate handling",
        "app/Domain/Intake/RunBloodTestExtraction.php:113; app/Domain/Intake/RunBloodTestExtraction.php:216",
        "tests/Feature/Intake/AssistedPdfExtractionTest.php:keeps duplicate trusted CMA layout names in review; auto-imports trusted CMA duplicate names when units disambiguate them",
        "Pest intake feature tests.",
    ),
    Row(
        "Extraction",
        "Extraction failure and rollback",
        "As an owner I want extraction failures not to leave partial parser output as trusted data.",
        "RunBloodTestExtraction marks failed runs, stores candidate_count 0 on failure, recalculates blood-test status, and wraps draft persistence in a transaction.",
        "RunBloodTestExtraction",
        "app/Domain/Intake/RunBloodTestExtraction.php:55; app/Domain/Intake/RunBloodTestExtraction.php:64",
        "tests/Feature/Intake/AssistedPdfExtractionTest.php:records failed extraction runs without storing parser output; rolls back candidate rows when extraction persistence fails mid-run",
        "Pest intake feature tests.",
    ),
    Row(
        "Review/detail",
        "Extracted draft review confirmation",
        "As an owner I want to review extracted drafts and confirm only the values I trust.",
        "ReviewBloodTest loads owned drafts, lets the owner use a draft, validates/normalizes form fields, resolves or creates an owned biomarker, sets confirmed_at, recalculates blood-test status, and resets the form.",
        "GET /blood-tests/{bloodTest}; Livewire confirmResult/useDraft",
        "app/Livewire/BloodTests/ReviewBloodTest.php:46; app/Livewire/BloodTests/ReviewBloodTest.php:161",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:shows extracted drafts and lets the owner confirm a draft through the review form",
        "Livewire/Pest feature tests.",
    ),
    Row(
        "Review/detail",
        "Manual entry fallback",
        "As an owner I want manual entry when extraction found no trustworthy values or failed.",
        "Review component toggles manual entry, validates name/value/unit/reference/note, creates or reuses owned biomarker, and stores confirmed reviewed result.",
        "Blood-test detail Livewire form",
        "app/Livewire/BloodTests/ReviewBloodTest.php:198; app/Livewire/BloodTests/ReviewBloodTest.php:406",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:frames the review form as manual entry when extraction found no drafts; shows a manual-entry fallback when extraction failed",
        "Livewire/Pest feature tests.",
    ),
    Row(
        "Review/detail",
        "Edit and delete confirmed values",
        "As an owner I want to correct or delete a confirmed value while preserving ownership and recalculating status.",
        "ReviewBloodTest loads only owned confirmed results, pre-fills edit form, updates payload while keeping entry-source trace, deletes confirmed rows, and recalculates blood-test status.",
        "Blood-test detail Livewire edit/delete",
        "app/Livewire/BloodTests/ReviewBloodTest.php:127; app/Livewire/BloodTests/ReviewBloodTest.php:147",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:shows auto-confirmed extracted values as auto-filled and lets the owner edit or delete them",
        "Livewire/Pest feature tests.",
    ),
    Row(
        "Review/detail",
        "Input normalization and duplicate prevention",
        "As an owner I want pasted PDF whitespace, decimal commas, and duplicate biomarker names handled predictably.",
        "ReviewBloodTest normalizes unicode whitespace, unit punctuation, decimal commas, and rejects duplicate same-blood-test biomarkers or ambiguous normalized catalog names.",
        "Blood-test detail Livewire form",
        "app/Livewire/BloodTests/ReviewBloodTest.php:349; app/Livewire/BloodTests/ReviewBloodTest.php:448",
        "tests/Feature/Livewire/BloodTestReviewAuthorizationTest.php; tests/Feature/Livewire/ExtractedDraftReviewTest.php",
        "Livewire/Pest validation and duplicate tests.",
    ),
    Row(
        "Review/detail",
        "Owner-scoped review actions",
        "As an owner I want tampered Livewire properties or action parameters blocked from touching another account's data.",
        "ReviewBloodTest aborts unless blood test, draft, confirmed result, and biomarker relations are owned and valid for the current user; corrupted cross-owner biomarker relations abort.",
        "Blood-test detail Livewire actions",
        "app/Livewire/BloodTests/ReviewBloodTest.php:306; app/Livewire/BloodTests/ReviewBloodTest.php:315; app/Livewire/BloodTests/ReviewBloodTest.php:330",
        "tests/Feature/Livewire/BloodTestReviewAuthorizationTest.php",
        "Livewire/Pest authorization tests.",
    ),
    Row(
        "Blood-test detail",
        "Canonical per-afname detail page",
        "As an owner I want each blood draw detail page to show its own overview rather than the newest upload.",
        "GET /blood-tests/{bloodTest} mounts ReviewBloodTest for an owned blood test, loads documents/context/results/extraction runs, and passes BuildLatestUploadSummary::forBloodTest for that route blood test.",
        "GET /blood-tests/{bloodTest}",
        "app/Livewire/BloodTests/ReviewBloodTest.php:39; app/Livewire/BloodTests/ReviewBloodTest.php:225",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:renders the patient friendly overview for an older owned blood test",
        "Livewire/Pest feature tests and Dusk smoke.",
    ),
    Row(
        "Blood-test detail",
        "Detail source documents",
        "As an owner I want source documents visible on detail without storage paths leaking.",
        "Review view lists owned source documents and uses authorized download routes; storage_path is not rendered.",
        "Blood-test detail; GET /blood-test-documents/{id}/download",
        "resources/views/livewire/blood-tests/review-blood-test.blade.php; app/Http/Controllers/BloodTests/DownloadBloodTestDocumentController.php:14",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:shows source documents for the selected owned blood test without storage paths; tests/Feature/BloodTests/BloodTestDocumentDownloadTest.php",
        "Livewire/Pest and document download feature tests.",
    ),
    Row(
        "Blood-test detail",
        "Detail context notes",
        "As an owner I want context notes near the selected blood test without leaking notes from other tests or owners.",
        "Review component loads context notes for the route blood test; context-note controllers enforce owner scope for attachments and mutations.",
        "GET /blood-tests/{bloodTest}; /context-notes",
        "app/Livewire/BloodTests/ReviewBloodTest.php:227; app/Http/Controllers/ContextNotes/StoreContextNoteController.php",
        "tests/Feature/Livewire/ExtractedDraftReviewTest.php:shows context notes for the selected blood test only; tests/Feature/ContextNotes/ContextNoteTest.php",
        "Pest feature tests.",
    ),
    Row(
        "Longitudinal changes",
        "Shared longitudinal builder",
        "As an owner I want changes over time calculated once so dashboard, detail, compare, and consult agree.",
        "BuildLongitudinalChanges filters owned blood tests, uses confirmedForUser rows, groups by biomarker, returns same-unit numeric deltas, directions, and explicit non-comparable reasons for missing values, unit mismatch, missing unit, or non-numeric data.",
        "Domain service",
        "app/Domain/BloodTests/BuildLongitudinalChanges.php:16; app/Domain/BloodTests/BuildLongitudinalChanges.php:44",
        "tests/Feature/BloodTests/BuildLongitudinalChangesTest.php",
        "Pest domain feature tests.",
    ),
    Row(
        "Longitudinal changes",
        "Blood-test comparison page",
        "As an owner I want to compare two owned blood tests using confirmed values only.",
        "CompareBloodTestsController validates first/second IDs, loads tests, aborts if either is foreign, and renders rows from CompareBloodTests, which delegates to BuildLongitudinalChanges::between.",
        "GET /blood-tests/compare?first=&second=",
        "app/Http/Controllers/BloodTests/CompareBloodTestsController.php:14; app/Domain/BloodTests/CompareBloodTests.php:23",
        "tests/Feature/BloodTests/CompareBloodTestsTest.php",
        "Pest feature tests.",
    ),
    Row(
        "Longitudinal changes",
        "Dashboard trend labels",
        "As an owner I want dashboard trend labels to use the same confirmed-only longitudinal rules as other surfaces.",
        "BuildLatestUploadSummary receives BuildLongitudinalChanges, maps changes to current result IDs, and derives first/not-comparable/unchanged/changed labels from LongitudinalChange.",
        "GET /dashboard",
        "app/Domain/Dashboard/BuildLatestUploadSummary.php:15; app/Domain/Dashboard/BuildLatestUploadSummary.php:84",
        "tests/Feature/DashboardTest.php:test_upload_summary_uses_shared_longitudinal_changes_builder_for_trends",
        "Pest dashboard regression test.",
    ),
    Row(
        "Biomarkers",
        "Biomarker history",
        "As an owner I want a biomarker page to show confirmed owned history in test-date order.",
        "ShowBiomarkerController renders owned biomarker history from confirmed values only and pin state; foreign biomarkers are blocked by owner scope.",
        "GET /biomarkers/{biomarker}",
        "app/Http/Controllers/Biomarkers/ShowBiomarkerController.php; resources/views/biomarkers/show.blade.php",
        "tests/Feature/Biomarkers/BiomarkerHistoryTest.php",
        "Pest biomarker feature tests.",
    ),
    Row(
        "Biomarkers",
        "Pinned biomarkers",
        "As an owner I want to pin and unpin owned biomarkers for dashboard and consult preparation.",
        "Pin/unpin controllers require owned biomarker route model context; pinned biomarkers use owner-owned scopes in dashboard/export/consult builders.",
        "POST /biomarkers/{biomarker}/pin; DELETE /biomarkers/{biomarker}/pin",
        "app/Http/Controllers/Biomarkers/PinBiomarkerController.php; app/Models/PinnedBiomarker.php; app/Domain/Consult/BuildConsultOverview.php:112",
        "tests/Feature/Biomarkers/PinnedBiomarkerTest.php; tests/Feature/ConsultOverview/ConsultOverviewTest.php",
        "Pest feature tests.",
    ),
    Row(
        "Consult pack",
        "Consult selection and filters",
        "As an owner I want to select owned blood tests and include sections for pins, attention, normal values, trends, context, and source documents.",
        "ShowConsultOverviewController validates dates, selected IDs, include flags, and POST-only questions; authorizeSelectedBloodTests aborts unless every selected ID is owned.",
        "GET/POST /consult-overview",
        "app/Http/Controllers/ConsultOverview/ShowConsultOverviewController.php:15; resources/views/consult-overview/index.blade.php",
        "tests/Feature/ConsultOverview/ConsultOverviewTest.php; tests/Feature/ConsultOverview/ConsultOverviewEmptySelectionTest.php",
        "Pest consult feature tests.",
    ),
    Row(
        "Consult pack",
        "Consult domain output",
        "As an owner I want consult output to contain confirmed owner data in predictable sections.",
        "BuildConsultOverview returns selected owned blood tests, pinned biomarkers, confirmed attention results, confirmed normal results, trend results/changes, context notes, source documents, and questions according to explicit include flags.",
        "Consult domain builder",
        "app/Domain/Consult/BuildConsultOverview.php:44",
        "tests/Feature/ConsultOverview/ConsultOverviewTest.php; tests/Feature/ConsultOverview/ConsultPackTest.php",
        "Pest consult feature tests.",
    ),
    Row(
        "Consult pack",
        "Attention before normal",
        "As an owner preparing a doctor conversation I want attention values shown before normal values.",
        "Consult pack view renders consult-attention-values before selected tests, pinned, normal values, trends, source documents, context, and questions; tests assert ordering before normal and trend sections.",
        "consult-overview._pack",
        "resources/views/consult-overview/_pack.blade.php:49",
        "tests/Feature/ConsultOverview/ConsultPackTest.php:builds a print ready consult pack from selected owned confirmed values and source documents",
        "Pest view/content order test.",
    ),
    Row(
        "Consult pack",
        "Consult trends from shared domain logic",
        "As an owner I want consult trend changes to be descriptive and consistent with the shared longitudinal logic.",
        "BuildConsultOverview injects BuildLongitudinalChanges, calls across selected tests, filters comparable changes, and maps result, previousResult, and changeLabel.",
        "Consult trend changes",
        "app/Domain/Consult/BuildConsultOverview.php:153",
        "tests/Feature/ConsultOverview/ConsultPackTest.php; tests/Feature/BloodTests/BuildLongitudinalChangesTest.php",
        "Pest consult and domain tests.",
    ),
    Row(
        "Consult pack",
        "Questions privacy",
        "As an owner I want private consult questions shown in the view but not leaked into GET URLs or CSV export forms.",
        "ShowConsultOverviewController only accepts questions on POST; CSV filters force questions to null; _pack export form omits questions hidden inputs.",
        "POST /consult-overview; POST /consult-overview.csv",
        "app/Http/Controllers/ConsultOverview/ShowConsultOverviewController.php:47; app/Http/Controllers/ConsultOverview/ExportConsultOverviewCsvController.php:149; resources/views/consult-overview/_pack.blade.php",
        "tests/Feature/ConsultOverview/ConsultOverviewPrivacyTest.php",
        "Pest privacy feature tests.",
    ),
    Row(
        "Consult pack",
        "Consult source document links",
        "As an owner I want source documents available from the consult pack without storage path leakage.",
        "BuildConsultOverview sourceDocuments returns documents only through owned blood tests; _pack renders original filename with authorized document download route.",
        "consult source document section",
        "app/Domain/Consult/BuildConsultOverview.php:170; resources/views/consult-overview/_pack.blade.php:175",
        "tests/Feature/ConsultOverview/ConsultPackTest.php; tests/Feature/BloodTests/BloodTestDocumentDownloadTest.php",
        "Pest consult and document authorization tests.",
    ),
    Row(
        "Consult pack",
        "CSV export",
        "As an owner I want a structured CSV consult export that is safe to open in spreadsheets.",
        "ExportConsultOverviewCsvController authorizes selected blood tests, rebuilds overview, outputs section/date/biomarker/value/unit/status/note rows, omits questions, and prefixes formula-like cells with a single quote.",
        "POST /consult-overview.csv",
        "app/Http/Controllers/ConsultOverview/ExportConsultOverviewCsvController.php:15; app/Http/Controllers/ConsultOverview/ExportConsultOverviewCsvController.php:128",
        "tests/Feature/ConsultOverview/ConsultOverviewTest.php:exports the consult overview structured rows as csv; escapes spreadsheet formulas in consult csv export cells; tests/Feature/ConsultOverview/ConsultPackTest.php",
        "Pest CSV feature tests.",
    ),
    Row(
        "Source documents",
        "Owner-authorized download",
        "As an owner I want to download my original source PDF, and no other user's document.",
        "DownloadBloodTestDocumentController aborts unless the document's blood test belongs to Auth::id, checks storage existence, and streams the file with original filename and mime type.",
        "GET /blood-test-documents/{bloodTestDocument}/download",
        "app/Http/Controllers/BloodTests/DownloadBloodTestDocumentController.php:14",
        "tests/Feature/BloodTests/BloodTestDocumentDownloadTest.php",
        "Pest document download tests.",
    ),
    Row(
        "Source documents",
        "Document deletion",
        "As an owner I want to delete an owned source document and its private stored file.",
        "DestroyBloodTestDocumentController owner-authorizes the document, deletes storage file and model record, and keeps downstream values separate from source documents.",
        "DELETE /blood-test-documents/{bloodTestDocument}",
        "app/Http/Controllers/BloodTests/DestroyBloodTestDocumentController.php",
        "tests/Feature/BloodTests/BloodTestDocumentDeletionTest.php",
        "Pest document deletion tests.",
    ),
    Row(
        "Blood tests",
        "Blood-test deletion",
        "As an owner I want to delete an owned blood test without deleting another owner's data.",
        "DestroyBloodTestController owner-authorizes the blood test before deletion and redirects back to the blood-test index.",
        "DELETE /blood-tests/{bloodTest}",
        "app/Http/Controllers/BloodTests/DestroyBloodTestController.php",
        "tests/Feature/BloodTests/BloodTestStatusTest.php; tests/Feature/BloodTests/BloodTestDocumentDeletionTest.php",
        "Pest blood-test deletion and source-document deletion feature tests.",
    ),
    Row(
        "Privacy",
        "Data export",
        "As an owner I want a JSON export of my personal tracking data.",
        "DownloadDataExportController streams BuildDataExport payload as blood-values-data-export-YYYY-MM-DD.json; BuildDataExport returns owned blood tests, categories, biomarkers, confirmed biomarker results, document metadata, extraction runs, pins, context notes, and reminders.",
        "POST /settings/data/export",
        "app/Http/Controllers/Privacy/DownloadDataExportController.php:13; app/Domain/Privacy/BuildDataExport.php:33",
        "tests/Feature/Privacy/DataExportAndDeletionTest.php; tests/Feature/Intake/AssistedPdfExtractionTest.php:keeps extracted drafts out of confirmed-only workflows and export until confirmed",
        "Pest privacy/export tests.",
    ),
    Row(
        "Privacy",
        "Delete all health data",
        "As an owner I want to remove all health tracking records and stored PDFs for my account.",
        "DestroyAllHealthDataController requires DELETE ALL confirmation; DeleteAllHealthData deletes owned context notes, reminders, pins, blood tests, biomarkers, categories, stored documents, and nulls cross-owner corrupted references.",
        "DELETE /settings/data",
        "app/Http/Controllers/Privacy/DestroyAllHealthDataController.php; app/Domain/Privacy/DeleteAllHealthData.php:20",
        "tests/Feature/Privacy/DataExportAndDeletionTest.php; tests/Feature/Intake/AssistedPdfExtractionTest.php:delete all removes extracted drafts and extraction runs",
        "Pest privacy tests and Dusk smoke export/delete sequence.",
    ),
    Row(
        "Context notes",
        "Context note CRUD",
        "As an owner I want to add, edit, and delete descriptive context notes linked to owned blood tests.",
        "Context note controllers validate date/category/body/blood_test_id, resolve only owned blood-test IDs, owner-authorize mutations, and render notes with their linked owned blood test.",
        "GET/POST/PATCH/DELETE /context-notes",
        "app/Http/Controllers/ContextNotes/StoreContextNoteController.php; app/Http/Controllers/ContextNotes/UpdateContextNoteController.php; resources/views/context-notes/index.blade.php",
        "tests/Feature/ContextNotes/ContextNoteTest.php",
        "Pest context-note feature tests.",
    ),
    Row(
        "Reminders",
        "Reminder CRUD",
        "As an owner I want reminders for follow-up tasks without leaking another user's reminders.",
        "Reminder controllers render open/completed reminders, validate due date/title/note/completed state, owner-authorize edits/deletes, and dashboard shows next open reminder.",
        "GET/POST/PATCH/DELETE /reminders",
        "app/Http/Controllers/Reminders/IndexRemindersController.php; resources/views/reminders/index.blade.php",
        "tests/Feature/Reminders/ReminderTest.php; tests/Feature/DashboardTest.php",
        "Pest reminder feature tests.",
    ),
    Row(
        "Cross-cutting",
        "Confirmed-only downstream",
        "As an owner I want drafts excluded from dashboard, status, history, compare, consult, export, and trends until explicitly confirmed.",
        "Downstream builders/controllers use confirmedForUser or confirmed_at filters; review keeps extracted drafts unconfirmed until owner action or deterministic auto-confirm gate.",
        "Dashboard/detail/compare/consult/export/history",
        "app/Models/BiomarkerResult.php; app/Domain/Dashboard/BuildLatestUploadSummary.php; app/Domain/BloodTests/BuildLongitudinalChanges.php; app/Domain/Consult/BuildConsultOverview.php; app/Domain/Privacy/BuildDataExport.php",
        "tests/Feature/Intake/AssistedPdfExtractionTest.php:keeps extracted drafts out of confirmed-only workflows and export until confirmed; tests/Feature/BloodTests/BuildLongitudinalChangesTest.php; tests/Feature/ConsultOverview/ConsultPackTest.php",
        "Focused Pest tests across downstream surfaces.",
    ),
    Row(
        "Cross-cutting",
        "Owner scoping",
        "As an owner I want every private route and downstream builder to reject or ignore foreign IDs and corrupted relations.",
        "Routes are authenticated; controllers and builders apply user_id checks; Livewire actions re-authorize server-side; corrupted cross-owner biomarker/pin/context links are rejected or omitted.",
        "All scoped private surfaces",
        "routes/web.php:27; routes/settings.php:13; app/Livewire/BloodTests/ReviewBloodTest.php; app/Domain/Consult/BuildConsultOverview.php",
        "tests/Feature/Livewire/BloodTestReviewAuthorizationTest.php; tests/Feature/ConsultOverview/ConsultOverviewTest.php; tests/Feature/ContextNotes/ContextNoteTest.php; tests/Feature/BloodTests/CompareBloodTestsTest.php",
        "Pest authorization and corrupted-relation tests.",
    ),
    Row(
        "Cross-cutting",
        "Medical-copy boundary",
        "As an owner I want the app to describe personal tracking data without diagnosis, treatment, supplement advice, triage, or health scores.",
        "View copy and test guardrails avoid forbidden medical/advice terms; consult pack includes personal-tracking/not-medical-advice framing.",
        "All user-visible V2 surfaces",
        "resources/views; tests/Feature/Architecture/MedicalCopyBoundaryTest.php",
        "tests/Feature/Architecture/MedicalCopyBoundaryTest.php; tests/Browser/PdfFirstIntakeSmokeTest.php",
        "Architecture copy scan and Dusk forbidden-copy checks.",
    ),
    Row(
        "QA support",
        "Synthetic QA seed scenario",
        "As a reviewer I want repeatable synthetic data for browser QA without using personal lab content.",
        "BloodValuesQaScenarioSeeder creates an idempotent synthetic scenario for a user and uses safe fixture-like data.",
        "php artisan db:seed or QA seed command path",
        "database/seeders/BloodValuesQaScenarioSeeder.php",
        "tests/Feature/QaSeedScenarioTest.php",
        "Pest seed scenario test.",
        notes="Support row, not end-user feature; kept because loop verification depends on safe synthetic data.",
    ),
]


def col_name(index: int) -> str:
    name = ""
    while index:
        index, rem = divmod(index - 1, 26)
        name = chr(65 + rem) + name
    return name


def xml_text(value: str) -> str:
    return escape(value, {"'": "&apos;", '"': "&quot;"})


def shared_strings(strings: list[str]) -> tuple[list[str], dict[str, int]]:
    unique: list[str] = []
    index: dict[str, int] = {}
    for string in strings:
        if string not in index:
            index[string] = len(unique)
            unique.append(string)
    return unique, index


def sheet_xml(rows: list[list[str]], string_index: dict[str, int]) -> str:
    widths = [18, 28, 42, 72, 34, 60, 54, 34, 16, 32, 28, 18, 24, 42]
    cols = "".join(
        f'<col min="{idx}" max="{idx}" width="{width}" customWidth="1"/>'
        for idx, width in enumerate(widths, start=1)
    )
    row_xml = []
    for row_num, row in enumerate(rows, start=1):
        height = "28" if row_num == 1 else "96"
        cells = []
        for col_num, value in enumerate(row, start=1):
            ref = f"{col_name(col_num)}{row_num}"
            style = "1" if row_num == 1 else "2"
            idx = string_index[value]
            cells.append(f'<c r="{ref}" t="s" s="{style}"><v>{idx}</v></c>')
        row_xml.append(f'<row r="{row_num}" ht="{height}" customHeight="1">{"".join(cells)}</row>')

    max_row = len(rows)
    max_col = col_name(len(HEADERS))
    validations = f"""
    <dataValidations count="2">
      <dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="I2:I{max_row}">
        <formula1>"Spec'd,Tested-Pass,Tested-Fail,Fixed,Verified"</formula1>
      </dataValidation>
      <dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="L2:L{max_row}">
        <formula1>"-,Functional,Logistical,UX,Privacy,Security,Test gap,Open question"</formula1>
      </dataValidation>
    </dataValidations>
    """
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
  <cols>{cols}</cols>
  <sheetData>{"".join(row_xml)}</sheetData>
  <autoFilter ref="A1:{max_col}{max_row}"/>
  {validations}
</worksheet>
"""


def styles_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color theme="1"/><name val="Aptos"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border><left style="thin"><color rgb="FFD9E2F3"/></left><right style="thin"><color rgb="FFD9E2F3"/></right><top style="thin"><color rgb="FFD9E2F3"/></top><bottom style="thin"><color rgb="FFD9E2F3"/></bottom><diagonal/></border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>
  </cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
  <dxfs count="0"/>
  <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
"""


def workbook_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Behavior Matrix" sheetId="1" r:id="rId1"/></sheets>
</workbook>
"""


def workbook_rels_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
"""


def root_rels_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"""


def content_types_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"""


def shared_strings_xml(strings: list[str], total_count: int) -> str:
    items = "".join(f"<si><t>{xml_text(value)}</t></si>" for value in strings)
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="{total_count}" uniqueCount="{len(strings)}">{items}</sst>
"""


def core_xml() -> str:
    now = dt.datetime.now(dt.timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:dcmitype="http://purl.org/dc/dcmitype/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>V2 Behavior Verification Matrix</dc:title>
  <dc:creator>Codex</dc:creator>
  <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{now}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{now}</dcterms:modified>
</cp:coreProperties>
"""


def app_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
  xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Codex</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>
  <TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>Behavior Matrix</vt:lpstr></vt:vector></TitlesOfParts>
  <Company></Company>
  <LinksUpToDate>false</LinksUpToDate>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>16.0000</AppVersion>
</Properties>
"""


def build() -> None:
    rows = [HEADERS] + [row.values() for row in ROWS]
    flat_strings = [value for row in rows for value in row]
    strings, string_index = shared_strings(flat_strings)
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    if OUTPUT.exists():
        OUTPUT.unlink()

    with zipfile.ZipFile(OUTPUT, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        archive.writestr("[Content_Types].xml", content_types_xml())
        archive.writestr("_rels/.rels", root_rels_xml())
        archive.writestr("docProps/core.xml", core_xml())
        archive.writestr("docProps/app.xml", app_xml())
        archive.writestr("xl/workbook.xml", workbook_xml())
        archive.writestr("xl/_rels/workbook.xml.rels", workbook_rels_xml())
        archive.writestr("xl/styles.xml", styles_xml())
        archive.writestr("xl/sharedStrings.xml", shared_strings_xml(strings, len(flat_strings)))
        archive.writestr("xl/worksheets/sheet1.xml", sheet_xml(rows, string_index))

    print(f"Wrote {OUTPUT.relative_to(ROOT)} with {len(ROWS)} feature rows")


if __name__ == "__main__":
    os.chdir(ROOT)
    build()
