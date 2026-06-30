# Architecture map - Persoonlijk Bloedwaarden-Dashboard

A map of how the app is built. It is a map, not a spec: the source of truth
stays `docs/codex-prd.md`, the specs, ADRs, tests, and per-slice kickoff docs.
Update this when a slice changes the shape.

Status: V2 is implemented on `main` — PDF-first intake, confidence-gated
auto-confirm (ADR-0011), canonical blood-test detail, longitudinal changes,
consult pack, privacy export/delete, reminders, dashboard polish, and thematic
biomarker overview (ADR-0013). Active follow-up slices: category assignment,
consult CSV domain extraction, domain routing cleanup.

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
  │                      BuildLongitudinalChanges · BiomarkerPresentation ·
  │                      BuildThematicBiomarkerOverview · BuildLatestUploadSummary ·
  │                      BuildDashboardOverview · BuildConsultOverview ·
  │                      BuildDataExport · DeleteAllHealthData ·
  │                      ExtractBiomarkerDrafts · RunBloodTestExtraction
  │
  ▼  Models (Eloquent) . User · BloodTest · BiomarkerResult · Biomarker ·
  │                      BloodTestDocument · PinnedBiomarker · ContextNote ·
  │                      Reminder · BiomarkerCategory
  │
  ▼  Persistence ....... SQLite · private disk (lab PDFs, generated names)

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
gate.

## 3. Feature map — the files behind each slice

### 1 · PDF-first intake — merged
- UI: `StoreBloodTestController`, `ReviewBloodTest` (Livewire), compare, documents
- Domain: `DetermineBiomarkerStatus`, `CompareBloodTests`, intake extractors
- Tests: intake, privacy, medical-copy boundaries

### 2 · Consult preparation — merged
- UI: `ConsultOverview` Show + CSV, context notes, pinned biomarkers
- Domain: `BuildConsultOverview`, `BuildLongitudinalChanges`
- Tests: `ConsultPackTest`, `ConsultOverviewPrivacyTest`

### 3 · Privacy export + delete — merged
- Domain: `BuildDataExport`, `DeleteAllHealthData`
- Tests: `DataExportAndDeletionTest`

### 4 · Reminders — merged
- UI: reminders CRUD, dashboard next reminder

### 5 · V2 clean extraction + detail workspace — merged
- Domain: `RunBloodTestExtraction`, `BuildLatestUploadSummary`, `BuildDashboardOverview`
- UI: upload-first dashboard, canonical blood-test detail

### 6 · Thematic biomarker overview — merged/in PR
- Domain: `BuildThematicBiomarkerOverview`, `BiomarkerReferenceDescriptions`,
  `BiomarkerPresentation` (shared labels)
- UI: consult **Per thema**, dashboard theme block
- ADR: 0013

## 4. Invariants — the non-negotiables

- **Owner-scoping.** Every health record belongs to a `user_id`; foreign access → 403.
- **Confirmed-only.** Only `confirmed_at` values feed downstream surfaces.
- **Review drafts.** `BiomarkerResult::reviewDraftsForUser()` centralizes extracted
  unconfirmed rows pending owner review.
- **Medical boundary.** No diagnosis / treatment / advice copy.
- **Private + local.** Lab PDFs on private disk; no external processing.
- **Presentation vs rules.** Status/trend *labels* live in `BiomarkerPresentation`;
  status *calculation* stays in `DetermineBiomarkerStatus`.
- **Tests own trust.** Pest + Dusk + PHPStan + Pint + `scripts/validate.sh` + CI.

## 5. Known architecture follow-ups

| Follow-up | Why |
| --- | --- |
| Move consult CSV row assembly to domain | Export logic still in HTTP controller |
| Shared consult filter FormRequest | Duplicated validation in Show + CSV controllers |
| Remove `route()` from `BuildDashboardOverview` | Domain should not depend on route names |
| Category assignment UI | Makes thematic overview useful (see plan 2026-06-30) |
