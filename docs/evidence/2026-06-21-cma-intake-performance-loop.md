# 2026-06-21 CMA Intake Performance Loop

Branch: `codex/v2-clean-autoconfirm`

Scope: no-behavior-change performance pass for the local CMA intake storage path.
No private PDF content or real biomarker values were used for this evidence.

## Baseline

- Python parser-lab: `python3 -m unittest discover -s tests/python -p 'test_*.py'`
  - Result: 4 tests passed
  - Wall time: 0.06s
- Tabular/CMA parser units: `php artisan test tests/Unit/TabularBiomarkerExtractionTest.php tests/Unit/CmaLayoutBiomarkerExtractionTest.php`
  - Result: 22 tests, 110 assertions passed
  - Wall time: 0.24s
- Assisted intake feature path: `php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php`
  - Initial post-catalog-cache result: 48 tests, 340 assertions passed
  - Final post-confirmed-cache result: 48 tests, 342 assertions passed
  - Final wall time: 0.87s
- Frontend build: `npm run build`
  - Result: Vite build passed
  - Wall time: 0.69s

## Hotspot

The pure parser tests and frontend build were already small. The actionable
hotspot was the extraction storage path: catalog matching repeatedly selected
the same owner biomarker catalog while storing one batch of extracted
candidates.

Characterization test:

```bash
php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php --filter='keeps catalog lookups bounded while storing a batch of trusted candidates'
```

Red result before the catalog-cache optimization:

- 1 test failed as intended.
- The synthetic 12-candidate batch performed 50 owner biomarker catalog selects.
- Failure: `Failed asserting that 50 is equal to 4 or is less than 4.`

Red result before the confirmed-value-cache optimization:

- 1 test failed as intended after adding the second bounded-query assertion.
- The synthetic 12-candidate batch performed 13 confirmed-value selects.
- Failure: `Failed asserting that 13 is equal to 1 or is less than 1.`

Green result after the optimizations:

- 1 test passed, 6 assertions.
- The same synthetic batch remained confirmed and the blood test status remained
  `confirmed`.
- The test now enforces bounded owner catalog lookups and bounded existing
  confirmed-value checks for this path. The confirmed-value bound allows the
  existing status recalculation query after storage.

## Change

`RunBloodTestExtraction` now:

- caches the owner biomarker catalog per extraction invocation;
- appends newly auto-imported biomarkers to that in-memory catalog;
- caches existing confirmed biomarker IDs per blood test;
- records newly auto-confirmed biomarker IDs in that in-memory confirmed cache;
- resets both caches at the start of every extraction invocation to preserve
  fresh per-run behavior.

## Verification

Full validation command:

```bash
sh scripts/validate.sh
```

Result:

- Parser-lab: 4 tests passed
- Frontend build: passed
- Pint: passed
- PHPStan: passed
- Initial full-run before the confirmed-value cache: Pest 225 tests, 3828
  assertions passed
- Final focused intake run after both optimizations: 48 tests, 342 assertions
  passed
- Final full-run after both optimizations: Pest 225 tests, 3830 assertions
  passed
- Dusk: 2 tests, 197 assertions passed
- Whitespace: passed

## Xhigh Review

LazyCodex xhigh code review result:

- Verdict: `UNCONDITIONAL APPROVAL`
- Code quality status: `CLEAR`
- Recommendation: `APPROVE`
- Blockers: none
- Reviewer conclusion: no further safe no-behavior-change optimization is
  recommended for this pass.

Reviewer-rerun evidence:

- Focused synthetic batch test: 1 test, 6 assertions passed
- Full assisted intake feature file: 48 tests, 342 assertions passed
- Pint on touched files: passed
- `git diff --check`: passed
- `sh scripts/validate.sh`: passed on rerun with parser-lab, build, Pint,
  PHPStan, Pest, Dusk, and whitespace all green
