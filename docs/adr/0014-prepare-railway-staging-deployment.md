# ADR-0014: Prepare Railway As A Staging Deployment Target

## Status

Accepted

## Context

The project is a private Laravel/Livewire blood-values app with uploaded lab
PDFs, confirmed biomarker values, context notes, consult exports, and deletion
flows. The current production checklist still says the project is not
production-ready, and real health-data use outside a local private environment
remains blocked until storage, backups, deletion, logging, and privacy gates are
explicitly verified.

The user wants the project prepared for Railway based on the official Laravel
deployment guide. That preparation should make a first staging deployment
repeatable without silently approving hosted production use for private lab
data.

## Decision

Prepare Railway as a staging/deploy target, not as a production approval.

Use Railway Railpack for the Laravel app service, Railway Postgres for hosted
relational data, `/up` as the app healthcheck, and database-backed
session/cache/queue settings. Keep the pre-deploy script migrations-only and
set `RAILPACK_SKIP_MIGRATIONS=true` so Railpack does not also run migrations
at container startup.

Deliberately do not follow the Laravel-guide pre-deploy script that also runs
`optimize:clear` plus config/event/route/view caching: Railway's own pre-deploy
documentation states the step runs in a separate container whose filesystem
changes are not persisted, so those cache files never reach the runtime
containers — while `optimize:clear` includes `cache:clear`, which with
`CACHE_STORE=database` durably flushes the shared Postgres cache table on
every deploy. Railpack already builds the artisan caches into the image and
re-runs `optimize:clear` + `optimize` at each container start, so pre-deploy
caching is redundant even where it would persist.

Keep private PDF uploads on Laravel's `local` disk for now, but require a
Railway Volume mounted at `/app/storage/app/private` before any real lab PDF or
private health document is uploaded to Railway. Without that volume, Railway
filesystem storage is staging-only and disposable.

Do not create worker or Laravel scheduler services until the app actually needs
queued jobs or scheduled tasks. Provide scripts and config files for those
services so they can be enabled deliberately later. If a future scheduled task
can tolerate Railway's native cron cadence, prefer a native cron service that
runs once and exits instead of an always-on Laravel scheduler loop.

## Evidence

- Source: `docs/ops/production-checklist.md`
  - Claim type: fact
  - Summary: The project is not production-ready and requires a storage,
    backup, privacy, logging, HTTPS, and validation review before real health
    data leaves the local private environment.

- Source: `config/filesystems.php`
  - Claim type: fact
  - Summary: The default local disk writes private files under
    `storage/app/private`.

- Source: `bootstrap/app.php`
  - Claim type: fact
  - Summary: Laravel already exposes the app health route at `/up`.

- Source: `https://docs.railway.com/guides/laravel`
  - Claim type: fact
  - Summary: Railway supports Laravel via GitHub or CLI deploys, Railpack
    detection, Postgres, pre-deploy commands, workers, cron services, and
    `stderr` logging.

- Source: `https://docs.railway.com/volumes`
  - Claim type: fact
  - Summary: Railway volumes provide persistent service storage and are mounted
    at runtime, not during build or pre-deploy.

- Source: `https://docs.railway.com/deployments/pre-deploy-command`
  - Claim type: fact
  - Summary: "Pre-deploy commands execute in a separate container from your
    application" and "changes to the filesystem are not persisted and volumes
    are not mounted" — artisan cache files written in pre-deploy never reach
    the runtime containers. This contradicts the caching block in Railway's
    own Laravel guide, which this ADR therefore does not follow.

- Source: `https://railpack.com/languages/php` and
  `https://github.com/railwayapp/railpack` (`core/providers/php/php.go`,
  `core/providers/php/start-container.sh`)
  - Claim type: fact
  - Summary: Railpack's Laravel provider runs config/event/route/view caching
    at build time (baked into the deploy image) and its container entrypoint
    runs `optimize:clear` + `optimize` on every Laravel container start.
    `RAILPACK_SKIP_MIGRATIONS` only controls the `migrate --force` at startup.

- Source: `https://laravel.com/docs/12.x/deployment#optimization`
  - Claim type: fact
  - Summary: `optimize:clear` removes the optimization caches "as well as all
    keys in the default cache driver" — with `CACHE_STORE=database` it flushes
    the shared cache table in Postgres.

## Considered Options

- Deploy from Railway's generic Laravel template.
- Deploy this existing GitHub repo as a Railway app service with Postgres.
- Keep the project local-only and do no Railway preparation.
- Refactor private PDF storage to S3-compatible object storage before Railway.

## Decision Drivers

- The existing repo already contains the real product, tests, ADRs, and privacy
  boundaries; starting from a template would lose that control plane.
- Hosted deployment changes the privacy and durability boundary.
- Railway app filesystem is not durable enough for private lab PDFs unless a
  volume is mounted.
- Postgres fits Railway's documented Laravel path better than SQLite for hosted
  deployment.
- Worker and scheduler services should be explicit, not accidentally enabled.

## Consequences

- Railway deploy settings live in `railway/*.railway.json` and must be selected
  per Railway service instead of relying on one root config file.
- `.env.railway.example` is safe to copy into Railway's variables editor only
  after replacing secrets and domain values.
- Staging deployments may use synthetic QA data without a volume, but real
  private health documents require a volume and backup/restore verification.
- The app service can be deployed without adding new Composer packages or
  external health-data processors.

## Confidence

Medium

## Follow-Up Questions

- Should Railway become the real private production host after a separate
  storage, backup, restore, deletion, and logging review?
- If real lab PDFs are uploaded to Railway, should the durable storage target be
  a Railway Volume or S3-compatible object storage?
