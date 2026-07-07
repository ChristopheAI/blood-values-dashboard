# Pre-Mortem Inputs For The Railway Real-Data Step

Date: 2026-07-07. Method: Firecrawl search (discovery) + Exa/Firecrawl fetch
(verification) per ADR-0006. This note feeds the pre-mortem and SPOF gates in
`docs/ops/railway-deployment.md`; decisions stay with the production slice.

## 1. Off-platform backup: the mechanics exist, pull beats push

- Railway publishes an official template ("Deploy Postgres S3 Backup",
  `https://railway.com/deploy/postgres-s3-backup`) that runs one backup and
  exits, paired with Railway Cron; a maintained community service
  (`https://github.com/imedwei/railway-postgres-backup`) supports S3 and GCS.
- Both are push-based and add a second cloud provider holding health data.
  For a one-operator personal app, a **pull-based backup keeps the party
  count at one**: a cron on the owner's own machine running `pg_dump` against
  Railway's TCP proxy plus a copy of the private files, stored locally
  (Time-Machine-covered) or on owner-controlled storage.
- The app's existing privacy data-export flow is a candidate vehicle for the
  file half of the backup — it already assembles the owner's documents.
- Open decision for the production slice: pull-based local cron (preferred on
  current evidence) vs. the Railway S3-backup template (fallback if the
  operator machine proves unreliable as an anchor).

## 2. Encryption at rest for private files: available, but it sharpens a SPOF

- Application-level encryption of file contents is standard Laravel practice
  (`Crypt`, AES-256-CBC), and `swisnl/laravel-encrypted-data` provides a
  transparent `encrypted` filesystem driver that wraps another disk.
- Adopting it would mitigate the leak half of the pre-mortem (volume
  contents unreadable without `APP_KEY`), but it is a new Composer package
  touching private health data → the package-review gate applies, and it
  makes `APP_KEY` loss equal permanent data loss — the human-SPOF map must
  then treat the key with backup-grade care (second copy in the password
  manager, tested).
- Not decided here; candidate mitigation for the production slice.

## 3. GDPR article 9: the household exemption is the current shield

- Health data is special-category data (art. 9); transfers to a US provider
  sit in Schrems II / SCC territory, and commentary flags a CLOUD-Act
  tension that contracts alone cannot resolve. Railway offers a self-service
  DPA (`https://railway.com/legal/dpa`) and a published subprocessor list.
- The load-bearing nuance for THIS project: a private individual processing
  exclusively their own data for personal use falls under the GDPR household
  exemption (art. 2(2)(c)) — the app is not a controller offering a service.
  The DPA + EU region remain worth having, but the compliance burden is not
  the blocker it would be for a product.
- **Revisit trigger: any second user** (even family). One extra account
  converts this into controller processing of special-category data, and the
  transfer/art. 9 analysis must be done for real before that happens.

## Sources

- `https://docs.railway.com/volumes/backups` — backup caveats (verified; see
  ADR-0015 amendment).
- `https://blog.railway.com/p/incident-report-may-19-2026-gcp-account-outage`
  — account-level platform loss (verified; see ADR-0015 amendment).
- `https://railway.com/deploy/postgres-s3-backup`,
  `https://github.com/imedwei/railway-postgres-backup` — backup mechanics.
- `https://geisi.dev/blog/how-improve-privacy-with-encrypt-files-with-laravel/`,
  `https://packagist.org/packages/swisnl/laravel-encrypted-data` — file
  encryption options.
- `https://pmc.ncbi.nlm.nih.gov/articles/PMC8216070/`,
  `https://www.kiteworks.com/gdpr-compliance/us-companies-eu-data-sovereignty-compliance/`
  — art. 9 / SCC / CLOUD-Act framing (inference-grade, not legal advice).
