# Codex Kickoff — Fourth Slice (Reminders) — closes V1

Date: 2026-06-18

Prepared in a Cowork review session (read-only sandbox; Codex executes on the Mac).
Three slices are merged to `main` (`ab6e007`): PDF-first intake, consult
preparation, and data export + delete-all. This is the **last V1 slice**, tracked
as issue #5 on the V1 milestone. Chat is temporary; this is not.

## Why this slice

The "remind me about the next blood test" outcome and the dashboard's "next
reminder" block (v1-spec §8) are the only V1 pieces left. It is small and
self-contained, and it closes two loose ends already sitting in the code: the
dashboard reminder slot, and the data export's empty `reminders: []` placeholder.

Out of scope: email/SMS/push notifications (v1-spec §5 — in-app only for V1),
recurring reminders, OCR/AI, anything medical.

## Conventions to follow (from the merged code — do not reinvent)

- Owner-scoped by `user_id`; authorize server-side; distrust Livewire public
  properties and action parameters. Mirror the **ContextNotes** feature shape:
  Index/Store/Update/Destroy single-action controllers under the
  `['auth','verified']` group in `routes/web.php`, with a factory and feature tests.
- Domain logic in `app/Domain/...` only if a non-trivial rule appears; enums in
  `app/Enums`; stable `data-test` selectors; Flux/Livewire views consistent with
  the existing pages.

## Data model

Migration `create_reminders_table`:

- `id`
- `user_id` — `foreignId()->constrained()->cascadeOnDelete()`
- `due_date` — `date`
- `title` — `string`
- `note` — `text` nullable
- `completed_at` — `timestamp` nullable (null = open)
- `timestamps`
- `index(['user_id', 'due_date'])`

Model `Reminder`: `belongsTo(User)`; cast `due_date` → date and `completed_at` →
datetime; a `scopeOpen` (`whereNull('completed_at')`). Add `User::reminders()`
(`hasMany`).

## Build order (tests-first)

Write the failing test first, then the behavior.

1. Migration + model + factory + `User::reminders()`. Test the `open` scope.
2. CRUD: `StoreReminderController`, `UpdateReminderController` (also marks complete
   by setting `completed_at`), `DestroyReminderController`, and an index page
   (Livewire or controller-backed view) listing open and completed reminders.
   Owner-scoped. Tests: create / edit / mark-complete / delete; User A cannot
   read, update, complete, or delete User B's reminder (403); tampered id rejected.
3. Dashboard: surface the next open reminder (soonest `due_date`, `scopeOpen`) in
   the existing `DashboardController` + view. Test it shows the soonest open
   reminder and not completed ones.
4. **Integration — wire reminders into the two existing privacy features (required):**
   - `app/Domain/Privacy/BuildDataExport.php`: replace `'reminders' => []` with the
     user's reminders (id, due_date, title, note, completed_at, timestamps),
     owner-scoped. Update the export test to assert reminders are included and
     another user's reminders are excluded.
   - `app/Domain/Privacy/DeleteAllHealthData.php`: also delete the user's reminders
     inside the transaction. Update the delete-all test to assert 0 owner reminders
     remain and another user's reminder is untouched.
5. Extend the Dusk smoke (or a small `ReminderSmokeTest`): create a reminder and
   see it on the dashboard. Keep `MedicalCopyBoundaryTest` green over the new views.
6. Prove green: `sh scripts/validate.sh` (Pint, PHPStan, Pest, Dusk, Vite).

## Fold in while building (not as new docs)

- Measurable targets, asserted in tests/QA: (a) the dashboard shows the single
  soonest open reminder; (b) 0 unauthorized cross-user access to reminders; (c)
  reminders appear in the data export and are removed by delete-all.
- UX states up front: empty (no reminders); open vs completed; overdue (`due_date`
  in the past, still open); authorization/error states. No medical phrasing —
  reminders are scheduling only.

## Guardrails (non-negotiable)

- In-app only — no email/SMS/push in V1 (v1-spec §5 Reminder rules).
- Reminders are personal planning aids with no medical meaning; honor v1-spec §10.
- Owner-scope everything; authorize server-side.
- No new Composer package.
- Do not skip the two integration updates (export + delete-all). A reminder that
  exports but never deletes — or deletes but never exports — is a bug.

## Working agreement (from the /implement command)

- Work at full quality; do not cut corners or simplify to finish faster.
- Tests-first; commit incrementally on `codex/reminders-slice`
  (model+migration+tests → CRUD → dashboard → the export/delete-all integration).
- No TODO stubs — implement everything in scope.
- If you must stop before green, write `progress.md` (done / next / blockers),
  commit it, and report honestly that it is partial — do not claim completion.
- Only report done when `sh scripts/validate.sh` is green. Then stop for review
  before merge; the merge closes #5.

## Paste-prompt for a new Codex thread

```text
Read AGENTS.md, docs/v1-spec.md (§5 Reminder, §7.8, §8, §10), docs/session-handoff.md,
and docs/codex-fourth-slice-kickoff.md. Three slices are merged on main (ab6e007);
this is the last V1 slice, tracked as issue #5.

Build the reminders slice now, tests-first, on codex/reminders-slice, following the
merged conventions (owner-scoping, ContextNotes-style controllers, factories,
data-test selectors):
1. reminders table (user_id, due_date, title, note, completed_at) + Reminder model +
   factory + User::reminders(); owner-scoped CRUD incl. mark-complete, with
   owner-isolation and tamper-denial tests.
2. Show the next open reminder on the dashboard.
3. Integration: include reminders in app/Domain/Privacy/BuildDataExport.php (replace
   reminders: []) and delete them in app/Domain/Privacy/DeleteAllHealthData.php;
   update both privacy tests accordingly.
4. Extend the Dusk smoke to create a reminder and see it on the dashboard; keep
   MedicalCopyBoundaryTest green.

In-app only (no email/SMS), no new Composer package, no medical copy. Work at full
quality, commit incrementally, no TODO stubs; if you must pause, write progress.md and
commit. Stop and report when sh scripts/validate.sh is green. Do not merge; I review
first, then we merge and close #5.
```
