# Pre-Scaffold Review Request

Date: 2026-06-16

## Decision Needed

Give a go/no-go on whether this project is ready to scaffold the Laravel
Livewire starter kit and start the first implementation slice.

This is a review of the planning baseline, not an app-code review.

## Reviewer Profiles

Primary reviewer:

- senior Laravel application architect;
- has shipped Laravel apps with authentication, authorization, policies,
  file storage, tests, and production validation;
- understands Livewire tradeoffs and can push back on overusing components.

Secondary reviewer:

- application security/privacy engineer;
- comfortable reviewing sensitive personal data flows;
- can assess user isolation, private files, export/delete, logging, and
  deployment risks;
- ideally familiar with GDPR/health-data constraints.

One person is acceptable only if they cover both profiles credibly.

## Repository State

Branch:

- `main`

Commits to review:

- `80a417b docs: add project planning baseline`
- `2086814 docs: record planning baseline checkpoint`

Current validation command:

```bash
sh scripts/validate.sh
```

Expected current result:

```text
Planning validation passed.
```

Important current fact:

- no Laravel scaffold exists yet;
- this is intentional;
- `scripts/validate.sh` currently proves planning integrity and absence of app
  scaffold.

## Read In This Order

1. `README.md`
2. `AGENTS.md`
3. `docs/session-handoff.md`
4. `docs/project-brief.md`
5. `docs/v1-spec.md`
6. `docs/research/laravel-stack-decision.md`
7. `docs/superpowers/plans/2026-06-16-first-vertical-slice.md`
8. `docs/validation-protocol.md`
9. `docs/ops/production-checklist.md`
10. `scripts/validate.sh`

## Product Boundary To Verify

The app is a private personal blood values dashboard.

It should help the user:

- enter blood tests;
- manually enter biomarker values;
- calculate simple status labels from entered ranges;
- view history and compare two tests;
- preserve context;
- prepare consult notes;
- export/delete data.

It must not:

- diagnose;
- give medical advice;
- recommend supplements, diet, training, or treatment;
- run AI/OCR/provider integrations in the first slice;
- expose health data outside the authenticated owner.

## Architecture Questions

Please answer:

1. Is the brief/spec/plan sequence strong enough to scaffold Laravel now?
2. Is Livewire starter kit a reasonable V1 default for this product shape?
3. Are domain rules sufficiently separated from Livewire components in the plan?
4. Is the first slice small enough?
5. Is the first slice too small to prove meaningful product value?
6. Does the plan protect against building a generic admin panel instead of a
   personal dashboard?
7. Should Filament be introduced now, later, or never for V1?
8. Is SQLite acceptable for the first local slice?
9. Does the task plan create too much code before proving status logic?
10. Does the plan contain any irreversible architectural choice too early?

## Security And Privacy Questions

Please answer:

1. Are auth and user isolation treated as first-class requirements?
2. Does the plan require tests proving User A cannot access User B's records?
3. Are private lab documents handled cautiously enough for a later V1 slice?
4. Are export and delete requirements visible early enough?
5. Is any sensitive data likely to leak through logs, public storage, analytics,
   error reports, or generated exports?
6. Is the medical boundary clear enough in copy, data model, and workflows?
7. Should the first slice block document upload until user isolation is proven?
8. What security check must be added before real personal data is entered?

## Go / No-Go Output Required

Use this format:

```text
Decision: GO / GO WITH CHANGES / NO-GO

Blocking issues:
- ...

Important issues:
- ...

Minor issues:
- ...

Required changes before scaffold:
- ...

Required changes after scaffold but before first personal data:
- ...

Reviewer confidence:
- High / Medium / Low
```

## Non-Negotiable Review Bar

Do not approve because the docs look polished.

Approve only if:

- the next implementation slice is small and testable;
- domain logic is not trapped inside UI components;
- user isolation is testable;
- health-data privacy risks are named before files/uploads/exports;
- `scripts/validate.sh` has a clear transition from planning checks to Laravel
  implementation checks;
- the project can be rolled back safely after each task.

