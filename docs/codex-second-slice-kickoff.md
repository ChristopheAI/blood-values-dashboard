# Codex Kickoff — Second Slice (Consult Preparation)

Date: 2026-06-18

Prepared in a Cowork review session (read-only sandbox; Codex executes on the Mac).
The first vertical slice is merged to `main` (`f692a02`): PDF-first intake,
review/confirm, status, history, compare — unit + feature + Dusk-tested and
security-proven. This is the durable build order for the next slice. Chat is
temporary; this is not.

## Why this slice

The first slice proved the data core. This slice delivers the product's headline
value: walking into a doctor's appointment with a usable personal overview. It
adds the three features deferred from slice one that together make the dashboard
worth opening — pinned biomarkers, context notes, and a consult overview/export —
on top of the now-proven, owner-scoped, confirmed-values core.

Out of this slice (keep it tight): full data export/delete-all (separate privacy
slice), reminders (separate small slice), OCR/AI, provider integrations.

## Conventions to follow (from the merged slice — do not reinvent)

- Routes live in the `['auth','verified']` group in `routes/web.php`; full-page
  Livewire components are routed directly (e.g.
  `Route::get('blood-tests/{bloodTest}', ReviewBloodTest::class)`); side effects
  use single-action invokable controllers.
- Everything is owner-scoped by `user_id`; the catalog is per-user
  (`User::biomarkers()`). Authorize server-side in every controller and Livewire
  action; never trust a Livewire public property or action parameter for ownership.
- "Only confirmed data" is expressed via `BloodTest::confirmedResults()`
  (`whereNotNull('confirmed_at')`). The consult overview and dashboard must use
  confirmed results only.
- Domain logic lives in `app/Domain/...` (see `DetermineBiomarkerStatus`,
  `CompareBloodTests`), not in Livewire or views. Enums in `app/Enums`. A factory
  for every model. Stable `data-test="..."` selectors on interactive elements.

## Build order (tests-first)

Write the failing test first, then the behavior. Mirror the existing layout under
`tests/Feature/...` and `tests/Unit/...`.

1. **Pinned biomarkers.** Table `pinned_biomarkers` (`user_id`, `biomarker_id`,
   `note` nullable, timestamps; `unique(user_id, biomarker_id)`). Owner-scoped
   pin/unpin toggle. Tests: pin/unpin works; User A cannot pin/unpin against User
   B's biomarker; a tampered id is rejected; pinned markers surface on the
   dashboard.
2. **Context notes.** Table `context_notes` (`user_id`, `blood_test_id` nullable,
   `note_date`, `category`, `body`). Category is a `ContextNoteCategory` enum:
   sleep, food, training, supplement, medication, complaint, stress, other.
   Owner-scoped CRUD; attaches to a date or a blood test. Tests: create/edit/
   delete; owner isolation; notes appear near their blood test and in the consult
   overview; medication/supplement notes are stored as descriptive text (no
   prescriptive transformation).
3. **Consult overview / export.** Render on demand (no new table required this
   slice). The user selects a date range or specific blood tests and chooses what
   to include: pinned biomarkers, abnormal + `unknown` confirmed values, trends,
   context notes, and free-text questions for the doctor. Output printable HTML
   first, plus a CSV of the structured rows. The header must state, in plain
   language, that this is self-entered personal tracking data and not medical
   advice, and invite the user to discuss it with their doctor. Tests: overview
   contains only confirmed/user-entered data; the abnormal+unknown selection is
   correct; pinned + context appear; the disclaimer renders; no forbidden §10 term
   appears (the existing `MedicalCopyBoundaryTest` auto-covers new views — keep it
   green).
4. **Dashboard wiring.** The dashboard is currently a static
   `Route::view('dashboard', 'dashboard')`. Back it with the owner's data (a
   full-page Livewire component or a controller-backed view) to surface pinned
   biomarkers and confirmed values needing attention (`low`/`high`/`unknown`).
5. **Extend the Dusk smoke** (extend `tests/Browser/PdfFirstIntakeSmokeTest.php`
   or add `ConsultPreparationSmokeTest.php`): pin a biomarker, add a context note,
   build a consult overview, and assert no forbidden §10 copy renders on any of
   those pages.
6. **Prove green:** `sh scripts/validate.sh` (Pint, PHPStan, Pest, Vite, Dusk).

## Fold in while building (not as new docs)

- **Measurable targets, asserted in tests/QA:** (a) the consult overview shows
  only confirmed/user-entered data and always renders the "not medical advice"
  disclaimer; (b) 0 unauthorized cross-user access to pins, context notes, or
  consult overviews; (c) a pinned biomarker and a context note both appear in the
  consult overview for the same user.
- **UX states up front:** empty (no pins / no notes / nothing selected); pinned
  vs unpinned toggle; context note attached to a date vs a blood test; consult
  overview with insufficient selection; authorization / empty / error states.

## Guardrails (non-negotiable)

- The consult overview is the highest-risk surface for the medical boundary:
  describe, organize, and prompt questions — never conclude, diagnose, advise, or
  rank health. Honor v1-spec §10; keep `MedicalCopyBoundaryTest` green over the
  new views.
- Context notes are descriptive user observations, never converted into advice.
- Owner-scope everything; authorize server-side; distrust Livewire public
  properties and action parameters.
- No new Composer package touching auth, files, exports, jobs, logs, or health
  data by reputation alone — review first. HTML/CSV output this slice avoids a new
  PDF dependency; a PDF library, if wanted later, needs that review.
- If a durable decision emerges (export format, persisting saved consults), record
  a short ADR and update `docs/v1-spec.md` + `docs/session-handoff.md`.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/v1-spec.md (esp. §5 Pinned Biomarker / Context Note /
Export-Consult Overview, §7.5-7.8, §8, §10), docs/session-handoff.md, and
docs/codex-second-slice-kickoff.md. The first slice is merged on main (f692a02).

Build the consult-preparation slice now, tests-first, following the conventions in
the merged code (owner-scoping, confirmedResults, single-action controllers,
domain logic outside Livewire, data-test selectors, factories):

1. Pinned biomarkers (pinned_biomarkers table, owner-scoped pin/unpin, dashboard
   surfacing) with owner-isolation and tamper-denial tests.
2. Context notes (context_notes table + ContextNoteCategory enum, owner-scoped
   CRUD, attach to date or blood test) with owner-isolation tests.
3. Consult overview/export rendered on demand: select date range/tests, include
   pinned + abnormal/unknown confirmed values + trends + context + free-text
   questions; output printable HTML + CSV; always render a "self-entered personal
   tracking data, not medical advice, discuss with your doctor" disclaimer.
4. Extend the dashboard (currently a static view) to show pinned biomarkers and
   low/high/unknown confirmed values needing attention.
5. Extend the Dusk smoke to pin -> add context note -> build consult overview, and
   keep MedicalCopyBoundaryTest green over the new views.

Do not add full data export/delete-all, reminders, OCR, AI, or provider
integrations in this slice. Use HTML/CSV (no new PDF dependency). Stop and report
when sh scripts/validate.sh is green.
```
