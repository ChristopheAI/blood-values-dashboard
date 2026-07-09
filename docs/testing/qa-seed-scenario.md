# QA Seed Scenario

Purpose: provide a stable synthetic dataset for browser QA, consult/export
checks, and future Codex runs. This avoids repeated setup and keeps private lab
PDFs or real biomarker data out of the repo.

## Command

```bash
php artisan app:seed-blood-test-demo
```

The command is idempotent. Running it again updates the same synthetic user and
records instead of creating duplicates.

In `APP_ENV=production`, the command refuses to create the fixed QA login unless
you pass `--force` for a deliberate, temporary QA session.

## Login

```text
Email: qa@example.com
Password: password
```

## Scenario

The seeder creates:

- one verified user;
- two blood tests, one older and one current;
- one synthetic source PDF file per blood test in private local storage;
- confirmed values across both tests;
- one current high value for attention/consult QA;
- one unconfirmed extracted draft row for review-strip QA;
- one pinned biomarker;
- one context note linked to the current blood test;
- one open reminder.

## Expected Browser QA Surfaces

- `/dashboard`
  - recent blood-test data visible;
  - confirmed values visible;
  - review/draft state remains separate.
- `/blood-tests`
  - two synthetic blood tests visible for `qa@example.com`.
- `/blood-tests/{older-id}`
  - older detail page shows the older overview, not the current upload.
- `/blood-tests/{current-id}`
  - current detail page shows confirmed values, one draft row, source document,
    context note, and manual fallback.
- `/consult-overview`
  - selected tests should produce confirmed-only consult output.

## Expected Data Shape

- User: `qa@example.com`.
- Blood tests:
  - `QA Blood Test - Older`, date `2026-04-15`.
  - `QA Blood Test - Current`, date `2026-06-15`.
- Confirmed values:
  - Ferritin in both tests.
  - CRP in both tests, with the current result above its synthetic reference
    range.
  - Vitamin D in the current test.
- Draft value:
  - TSH draft in the current test, `entry_source = extracted`,
    `confirmed_at = null`, and low confidence.

## Privacy Notes

- All values are synthetic.
- Source PDFs contain placeholder bytes only.
- This scenario must not be used as medical evidence, health guidance, or real
  biomarker interpretation.
