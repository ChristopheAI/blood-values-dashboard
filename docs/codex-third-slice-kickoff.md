# Codex Kickoff — Third Slice (Data Export + Delete-All)

Date: 2026-06-18

Prepared in a Cowork review session (read-only sandbox; Codex executes on the Mac).
Two slices are merged to `main` (`c84977c`): PDF-first intake → review/confirm →
status → history → compare, and consult preparation (pinned biomarkers, context
notes, consult overview/export). Both are unit + feature + Dusk-tested,
owner-scoped, medical-boundary guarded, and privacy-hardened. This is the durable
build order for the next slice. Chat is temporary; this is not.

## Why this slice

The product promise is "keep control over sensitive data." The v1-spec treats
export and delete as a **V1 baseline, not later polish** (§7.8, §9). Right now a
user can delete a single blood test or context note, but cannot export their full
record or wipe everything. This slice closes that non-negotiable: a portable full
export, and an irreversible delete-all that also removes the private PDFs from
disk — both behind password confirmation.

Out of this slice (keep it tight): reminders (separate small slice; not built yet,
so excluded from export), OCR/AI, provider integrations, multi-user/sharing.

## Conventions to follow (from the merged code — do not reinvent)

- The account area lives in `routes/settings.php` as Livewire pages
  (`Route::livewire('settings/security', 'pages::settings.security')`) behind
  `auth` and, for sensitive actions, `password.confirm`. The new Data/Privacy page
  belongs here, password-confirmed.
- Everything is owner-scoped by `user_id`. `User` already exposes `bloodTests()`,
  `biomarkers()`, `pinnedBiomarkers()`, `contextNotes()`. Authorize server-side;
  distrust Livewire public properties and action parameters.
- Private file cleanup already exists: `DestroyBloodTestController` removes the
  stored PDF and the existing deletion test asserts `Storage::assertMissing` +
  a 404 on download. Mirror that pattern for delete-all.
- Domain logic in `app/Domain/...`; single-action invokable controllers for side
  effects; enums in `app/Enums`; a factory per model; stable `data-test` selectors.
- Sensitive free-text and health data never go in a URL/query string (see the
  consult hardening). Destructive actions are POST/DELETE with CSRF.

## Build order (tests-first)

Write the failing test first, then the behavior.

1. **Full data export.** A domain builder (`app/Domain/Privacy/BuildDataExport`)
   returns the complete owned dataset: blood tests (date, lab, title, notes,
   status), biomarker results (value, unit, reference min/max/unit, status,
   entry_source, confirmed_at, note), the biomarker catalog + categories, pinned
   biomarkers, context notes, and **document metadata** (original filename, mime,
   size, created_at — not the binary in this step). A single-action controller
   streams it as a downloadable JSON file. Structure it so reminders slot in later.
   Tests: export includes every owner entity type; excludes any other user's rows;
   correct `content-type` and download filename; only confirmed/user data, no
   derived medical conclusions.
2. **Delete-all health data.** A domain action deletes all of the user's blood
   tests (cascading results + documents), pinned biomarkers, context notes, and
   biomarker catalog, **and removes every one of the user's private PDFs from
   storage** — without deleting the auth account. Behind `password.confirm`.
   Tests: after delete-all, 0 owner health rows remain AND 0 owner private files
   remain on disk; the account still authenticates; another user's data and files
   are untouched; the action requires password confirmation (unconfirmed → blocked).
3. **Standalone document delete** (§7.8). Owner-only delete of a single
   `BloodTestDocument`: removes the file and blocks the download afterward. Mirror
   the existing blood-test deletion test.
4. **Settings → Data page.** A Livewire page in the settings area: a Download-my-
   data action, and a Delete-all action gated by password confirmation **and** an
   explicit typed/checked confirmation, with plain copy that it is irreversible.
   No medical-conclusion language.
5. **Extend the Dusk smoke** (or add `DataExportDeletionSmokeTest.php`): download
   the export, then run delete-all through the confirmation flow and assert the
   dashboard/blood-tests are empty afterward. Keep `MedicalCopyBoundaryTest` green.
6. **Prove green:** `sh scripts/validate.sh` (Pint, PHPStan, Pest, Vite, Dusk).

## Fold in while building (not as new docs)

- **Measurable targets, asserted in tests/QA:** (a) export contains 100% of the
  owner's records across all entity types and 0 rows from any other user; (b)
  after delete-all, 0 owner health rows and 0 owner private files remain, while
  the account still logs in; (c) every destructive action requires password
  confirmation.
- **UX states up front:** empty (nothing to export/delete); export ready/
  download; delete-all confirmation (password + explicit confirm + irreversible
  warning); post-delete empty dashboard; authorization/error states.

## Guardrails (non-negotiable)

- Delete-all is irreversible — require `password.confirm` plus an explicit
  in-form confirmation, and say so plainly. No silent or one-click wipe.
- Delete-all must leave no orphaned private PDFs on disk; prove file removal, not
  just row deletion.
- Export and delete operate only on `Auth::user()`'s data; prove cross-user
  isolation for both.
- The export is structured personal tracking data — no diagnosis/advice/score
  fields; honor v1-spec §10 in any rendered copy.
- No new Composer package for this slice. If you later bundle the original PDFs
  into a ZIP export, use PHP's built-in `ZipArchive` (no new dependency) and add
  a package review only if you reach for anything else.
- If a durable decision emerges (export format, keep-account vs delete-account,
  zip bundling), record a short ADR and update `docs/v1-spec.md` +
  `docs/session-handoff.md`.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/v1-spec.md (esp. §5 data model, §7.8 Export And Delete Data,
§9 Privacy And Security, §10), docs/session-handoff.md, and
docs/codex-third-slice-kickoff.md. Two slices are merged on main (c84977c).

Build the data-export + delete-all privacy slice now, tests-first, following the
conventions in the merged code (owner-scoping, settings-area Livewire pages behind
password.confirm, single-action controllers, domain logic outside Livewire,
private-file cleanup like DestroyBloodTestController, data-test selectors, factories):

1. Full data export: a domain builder returning all owned data (blood tests,
   biomarker results, catalog + categories, pinned biomarkers, context notes,
   document metadata), streamed as a downloadable JSON file. Test it includes every
   owner entity type and zero other-user rows.
2. Delete-all health data: delete all owned blood tests (cascade results +
   documents), pins, context notes, and catalog, and remove all of the user's
   private PDFs from storage, WITHOUT deleting the auth account. Gate behind
   password.confirm. Test: zero owner rows and zero owner files remain, account
   still authenticates, other users untouched, confirmation required.
3. Standalone document delete (owner-only; removes file + blocks download).
4. A Settings -> Data Livewire page: download-my-data + delete-all with password
   confirmation, explicit confirmation, and irreversible-warning copy.
5. Extend the Dusk smoke: download export, then delete-all through the confirmation
   flow, asserting an empty dashboard afterward. Keep MedicalCopyBoundaryTest green.

Do not add reminders, OCR, AI, provider integrations, or sharing in this slice. Use
no new Composer package (use ZipArchive if you bundle PDFs later). Keep destructive
actions POST/DELETE with CSRF and no health data in URLs. Stop and report when
sh scripts/validate.sh is green.
```
