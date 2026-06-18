# Package Review: Laravel Dusk

Date: 2026-06-18

## Packages

- Composer dev dependency: `laravel/dusk`

## Fit

The first PDF-first intake slice needs a browser smoke test that exercises the
real authenticated flow: register/login, PDF upload, manual biomarker
confirmation, history, and comparison.

Pest Browser was considered first because the project already uses Pest 4. The
current Pest Browser Laravel HTTP server driver does not provide request file
handling in the code path inspected for multipart uploads. Dusk is a better fit
for this specific smoke test because it drives the app through a real local HTTP
server and supports file attachment for browser tests.

## Privacy Boundary

This package is a development and CI test dependency only. It must not be used
by runtime code, controllers, Livewire components, jobs, exports, or document
processing.

The browser smoke uses generated fixture PDFs under `tests/Fixtures` and does
not upload private personal lab documents.

## Maintenance

Laravel Dusk is first-party Laravel browser testing tooling and is maintained
as part of the Laravel ecosystem.

## Validation

The package is accepted only if:

- the browser smoke test runs locally;
- `sh scripts/validate.sh` includes the browser smoke;
- CI installs the matching ChromeDriver before validation;
- runtime architecture tests continue to prove no AI/research tooling is wired
  into app routes, views, or application code.
