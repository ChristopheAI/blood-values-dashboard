# Railway Deployment Runbook

Status: staging preparation only. This runbook does not approve real private
lab PDFs or health data on Railway.

## Boundary

Railway can host this Laravel app, but hosted deployment changes the privacy and
durability boundary. Use Railway first with synthetic QA data. Real lab PDFs,
biomarker values, context notes, consult exports, and account data stay out of
Railway until the production checklist is completed.

Required before real data:

- Railway Postgres backup and restore path verified.
- Private file storage location decided and backed up.
- Railway Volume mounted at `/app/storage/app/private` if `FILESYSTEM_DISK=local`
  remains the upload storage path.
- `APP_DEBUG=false`, `LOG_LEVEL=warning`, and no sensitive values in logs.
- `sh scripts/validate.sh` green before deploy.
- Browser QA with synthetic data after deploy.

## Files

- `railway/app.railway.json` - app service config: Railpack, Vite build,
  migration pre-deploy, `/up` healthcheck.
- `railway/init-app.sh` - pre-deploy migration script.
- `railway/worker.railway.json` and `railway/run-worker.sh` - optional queue
  worker service.
- `railway/cron.railway.json` and `railway/run-cron.sh` - optional Laravel
  scheduler service.
- `.env.railway.example` - Railway variable template with placeholders only.
- `docs/adr/0014-prepare-railway-staging-deployment.md` - deployment decision.

The config files are intentionally not named `railway.json` at the repo root.
Railway config-as-code applies per service, and worker/cron services must not
inherit the app service healthcheck by accident.

## App Service

GitHub visibility gate: Railway can deploy only committed code from GitHub.
Before creating or redeploying the Railway service, commit these Railway prep
files and push the selected branch to `origin`. A locally green worktree is not
enough for Railway if `railway/`, `.env.railway.example`, or this runbook are
still untracked or only local.

1. Create a Railway project from the GitHub repo.
2. Add a Postgres service.
3. Create the Laravel app service from the repo.
4. In the service settings, set the config-as-code path to:

```text
/railway/app.railway.json
```

5. Add variables from `.env.railway.example` and replace:
   - `APP_KEY`
   - `APP_URL` only if using a custom domain instead of Railway's generated
     public domain
   - `DB_URL` if the Postgres service is not named `Postgres`
6. Set the public domain only after variables are present.
7. Keep the Railway healthcheck path at `/up`.

Generate the app key locally:

```bash
php artisan key:generate --show
```

### HTTPS behind Railway's edge

Railway terminates TLS at its edge and forwards to the container over HTTP with
`X-Forwarded-*` headers. The app is configured to handle this:

- `bootstrap/app.php` trusts the forwarding proxy (`$middleware->trustProxies(at: '*')`),
  so `request()->secure()` reflects the real scheme.
- `AppServiceProvider::configureDefaults` calls `URL::forceScheme('https')` when
  `app()->isProduction()`, so assets, redirects, paginator links, and Livewire
  endpoints are generated as `https://`.

Without both, generated URLs come out as `http://` on an `https://` page and the
browser blocks the mixed-content CSS/JS — the app renders broken on first load.
After deploy, confirm the landing page loads styled and that page source shows
`https://` asset URLs.

## Private File Storage

The app stores uploaded blood-test PDFs through Laravel's `local` disk under
`storage/app/private`.

For staging without real data, disposable filesystem storage is acceptable.
For real lab PDFs, create a Railway Volume and mount it at:

```text
/app/storage/app/private
```

Do not mount the volume only at `/storage` or another relative-looking path; the
Laravel app writes under `/app/storage/app/private` in Railway's app container.

Volumes are mounted at runtime, not during pre-deploy. The pre-deploy script
must not depend on uploaded files or volume contents.

## Worker Service

Do not create this service until the app has queued jobs that must run
asynchronously.

If needed:

1. Create a separate Railway service from the same repo.
2. Set the config-as-code path to:

```text
/railway/worker.railway.json
```

3. Use the same required environment variables as the app service.
4. Do not assign a public domain.

## Scheduler Service

Do not create this service until `routes/console.php` has scheduled tasks.
The prepared script follows the Laravel deployment guide's always-on scheduler
loop, which runs `php artisan schedule:run` every minute.

If needed:

1. Create a separate Railway service from the same repo.
2. Set the config-as-code path to:

```text
/railway/cron.railway.json
```

3. Use the same required environment variables as the app service.
4. Do not assign a public domain.

If a future scheduled task can run on Railway's native cron model instead, use a
service that runs `php artisan schedule:run --verbose --no-interaction` once and
exits, then set a Railway `cronSchedule`. Railway native cron has a minimum
frequency of every 5 minutes and schedules run in UTC, so do not use it for
Laravel tasks that require every-minute scheduling.

## Verification

Before deploy:

```bash
sh scripts/validate.sh
git diff --check
```

After deploy:

```bash
curl -fsS https://REPLACE_WITH_RAILWAY_DOMAIN/up
```

Then run the synthetic QA flow in the hosted app:

1. Register or seed only synthetic data.
2. Upload a synthetic PDF.
3. Confirm the review page loads.
4. Confirm drafts stay out of dashboard, consult, export, and trends.
5. Confirm source document download uses the authorized route and does not show
   generated private storage paths.

## Rollback

- Use Railway deployment history to roll back the app service.
- Take a Postgres backup before migrations that are not trivially reversible.
- If a Railway Volume contains real documents, verify file presence after
  rollback; app rollback does not automatically roll back volume contents.

## Out Of Scope

- No runtime AI, OCR service, provider integration, wearable sync, or external
  processing.
- No real private lab PDFs until storage/backups/deletion/logging are reviewed.
- No root `railway.json` until the service layout is final.
