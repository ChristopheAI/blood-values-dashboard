# Architecture map — Persoonlijk Bloedwaarden-Dashboard

A map of how the app is built, grounded in the real code on `main`. It is a map,
not a spec: the source of truth stays `docs/v1-spec.md`, the ADRs, and the
per-slice kickoff docs. Update this when a slice changes the shape.

Status: V1 is complete on `main` — four merged slices (PDF-first intake, consult
preparation, privacy export + delete-all, reminders). V2 assisted PDF extraction
is planned (see `docs/codex-v2-assisted-extraction-kickoff.md`), shown below in
brackets.

## 1. The stack, by layer

The one structural rule: business rules live in the Domain layer, never in
Livewire or controllers. Controllers and Livewire only orchestrate.

```
Request
  │
  ▼  Routes ............ web.php · settings.php · auth+verified · password.confirm
  │
  ▼  Controllers + LW .. StoreBloodTest · ReviewBloodTest (Livewire) ·
  │                      ConsultOverview · DownloadDataExport · +more
  │
  ▼  Domain  (rules) ... DetermineBiomarkerStatus · CompareBloodTests ·
  │                      BuildConsultOverview · BuildDataExport · DeleteAllHealthData
  │                      [ExtractBiomarkerDrafts — V2]
  │
  ▼  Models (Eloquent) . User · BloodTest · BiomarkerResult · Biomarker ·
  │                      BloodTestDocument · PinnedBiomarker · ContextNote ·
  │                      Reminder · BiomarkerCategory
  │
  ▼  Persistence ....... SQLite (9 tables) · private disk (lab PDFs, generated names)

Cross-cutting (through every layer):
  auth (Fortify · passkeys · 2FA) · owner-scoping (user_id) ·
  confirmed-only (confirmed_at) · medical boundary · tests / CI
```

## 2. One request traced — from "upload a PDF" to a tracked value

```
1  POST /blood-tests            [Routes]        auth · CSRF
2  StoreBloodTestController      [Controller]    validate: PDF only, <=12 MB
3  Storage::local {uuid}.pdf     [Persistence]   private disk · generated name
4  BloodTest(status: uploaded)   [Models]        + BloodTestDocument
5  ReviewBloodTest (Livewire)    [UI]            owner check, else 403
6  DetermineBiomarkerStatus()    [Domain]   <==  THE GATE -> low / normal / high / unknown
7  BiomarkerResult.confirmed_at  [Models]        now real data
8  confirmedResults()            [Domain]   -->  status · history · compare · consult · export
```

Before step 6 it is just a guess next to your PDF. After it, the value is
tracked, status-labeled, and yours — and only confirmed values travel past the
gate. Everything before the gate is about getting the PDF in safely (private
storage, owner checks, validation); everything after is the confirmed-only
pipeline.

## 3. Feature map — the files behind each slice

Every slice has the same shape: a thin UI layer, one domain service for the
rules, a model plus migration, and its own tests. The exceptions are telling.

### 1 · PDF-first intake — merged
- UI: `StoreBloodTestController`, `ReviewBloodTest` (Livewire),
  `CompareBloodTestsController`, `DownloadBloodTestDocumentController`,
  `DestroyBloodTestController`, `ShowBiomarkerController`
- Domain: `DetermineBiomarkerStatus`, `CompareBloodTests`
- Data: `BloodTest`, `BloodTestDocument`, `Biomarker`, `BiomarkerCategory`,
  `BiomarkerResult` · 5 migrations
- Tests: `BiomarkerStatusTest`, `BloodTestReviewAuthorizationTest`,
  `BloodTestPdfUploadTest`, `BloodTestDocumentDownloadTest`,
  `BloodTestDocumentDeletionTest`, `BiomarkerHistoryTest`, `CompareBloodTestsTest`,
  `PrivacyBoundaryTest`

### 2 · Consult preparation — merged
- UI: `ContextNotes` Index/Store/Update/Destroy, `ConsultOverview` Show + Csv,
  `PinBiomarkerController`, `UnpinBiomarkerController`, `DashboardController`
- Domain: `BuildConsultOverview`
- Data: `PinnedBiomarker`, `ContextNote`, `ContextNoteCategory` (enum) · 2 migrations
- Tests: `ConsultOverviewTest`, `ContextNoteTest`, `PinnedBiomarkerTest`,
  `MedicalCopyBoundaryTest`

### 3 · Privacy: export + delete-all — merged
- UI: `DownloadDataExportController`, `DestroyAllHealthDataController`,
  `DestroyBloodTestDocumentController`, settings/data page (password-confirmed)
- Domain: `BuildDataExport`, `DeleteAllHealthData`
- Data: reuses existing models · no new migration
- Tests: `DataExportAndDeletionTest`

### 4 · Reminders — merged
- UI: `Reminders` Index/Store/Update/Destroy, dashboard "next reminder"
- Domain: none — plain CRUD, no real rules
- Data: `Reminder` · `create_reminders` migration
- Tests: `ReminderTest` (+ updates to the export and dashboard tests)

### 5 · Assisted extraction — V2, planned
- UI: `ReviewBloodTest` (reused), an upload hook
- Domain: `ExtractBiomarkerDrafts`
- Data: `extraction_runs` migration · `biomarker_results` gains
  `entry_source = 'extracted'` for drafts
- Tests: the "draft never leaks" contract

## 4. Invariants — the non-negotiables that cut across every layer

- **Owner-scoping.** Every health record belongs to a `user_id`; a different user
  is denied (403), proven by tamper/isolation tests in every slice.
- **Confirmed-only.** Only values with `confirmed_at` set feed status, history,
  compare, consult, and export — via `BloodTest::confirmedResults()`. Drafts and
  unconfirmed entries never count.
- **Medical boundary.** No diagnosis / treatment / advice copy (v1-spec §10);
  `MedicalCopyBoundaryTest` scans the views to keep it true.
- **Private + local.** Lab PDFs live on a private disk under generated names with
  owner-authorized download; no external or AI processing of lab PDFs —
  `PrivacyBoundaryTest` guards it.
- **Tests own trust.** Pest + Dusk + PHPStan + Pint + `scripts/validate.sh` + CI.
  Nothing is "done" until that is green; AI-written code is not trusted until it is.
```
