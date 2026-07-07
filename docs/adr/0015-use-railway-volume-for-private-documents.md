# ADR-0015: Use A Railway Volume For Private Document Storage

## Status

Accepted (2026-07-07, ratification owner-delegated to the working session)

The decision's own preconditions stay hard gates: EU region, scheduled volume
backups, and one documented restore drill before any real document upload.

## Context

ADR-0014 prepared Railway as a staging target and left one follow-up question
open: when real lab PDFs are uploaded to Railway, should the durable storage
target be a Railway Volume or S3-compatible object storage?

The app stores uploaded blood-test PDFs through Laravel's `local` disk under
`storage/app/private`. The production checklist blocks real health data on
Railway until storage, backups, deletion, and logging are verified. The repo's
package rule requires an explicit review or ADR before any Composer package
that touches private health data is installed.

The workload is personal-scale: one owner, tens of lab PDFs per year, no
multi-service file access, no CDN or public delivery need.

## Decision

Use a Railway Volume, mounted at `/app/storage/app/private`, as the durable
storage target for private documents when Railway hosting becomes real.

Conditions that are part of this decision, not optional extras:

- the Railway project runs in an EU region before any real document is
  uploaded;
- scheduled volume backups are configured and a restore drill has been
  performed and documented once, before the first real upload;
- the volume is sized so that manual backups stay possible (Railway limits a
  manual backup to 50% of the volume's total size — keep used space under
  half);
- deletion flows are re-verified against the volume (deleting a document must
  remove the file from the volume, not only the database row).

Amendment (2026-07-07, pre-mortem research): Railway volume backups live WITH
the volume and die with it — the official caveats state "wiping a volume
deletes all backups" and "backups can only be restored into the same project +
environment", with at most 3 months of retention. Railway's own May 2026
incident (Google Cloud suspended Railway's production account without prior
notice; ~8 hours platform-wide outage, persistent disks temporarily
inaccessible) shows platform/account-level loss is a real scenario. Volume
backups therefore only cover data mistakes, not platform loss. Additional
hard precondition: an **off-platform backup** — a periodic export of the
Postgres database and the private files to a location the owner controls
outside Railway — configured and drill-tested before the first real document
upload.

Revisit triggers that reopen the S3 question: a second service needs direct
file access, storage outgrows what a single volume handles comfortably, a
backup/retention requirement exceeds Railway's backup features, or Railway's
EU-region or backup guarantees change.

## Evidence

- Source: `https://docs.railway.com/volumes` and
  `https://docs.railway.com/volumes/backups`
  - Claim type: fact
  - Summary: Services with volumes support manual and automated (scheduled)
    backups with restore; manual backups are limited to 50% of the volume's
    total size.

- Source: `https://docs.railway.com/volumes/backups` (Caveats)
  - Claim type: fact
  - Summary: "Wiping a volume deletes all backups"; "backups can only be
    restored into the same project + environment"; scheduled retention is at
    most 3 months (monthly schedule). Volume backups are snapshots coupled to
    the volume, not an independent backup tier.

- Source: `https://blog.railway.com/p/incident-report-may-19-2026-gcp-account-outage`
  - Claim type: fact
  - Summary: Google Cloud suspended Railway's production account without
    prior notice on 2026-05-19, causing a ~8-hour platform-wide outage with
    persistent disks temporarily inaccessible — platform/account-level loss
    is a demonstrated failure mode, which coupled backups do not survive.

- Source: `https://docs.railway.com/integrations/api/manage-volumes`
  - Claim type: fact
  - Summary: Volume backups are manageable via the public API — list, create,
    restore, lock against expiration, delete, and backup schedules — so the
    restore drill can be scripted and repeated.

- Source: `docs/ops/railway-deployment.md`
  - Claim type: fact
  - Summary: The runbook already requires the volume mount path
    `/app/storage/app/private` and warns that app rollbacks do not roll back
    volume contents.

- Source: `AGENTS.md` package rule and ADR-0014 boundary
  - Claim type: fact
  - Summary: S3 would require `league/flysystem-aws-s3-v3` (or equivalent), a
    new package touching private health data, which the repo gates behind a
    package review; a volume needs no new package because the `local` disk
    keeps working unchanged.

## Considered Options

- Railway Volume mounted at the existing private storage path.
- S3-compatible object storage (AWS S3, Cloudflare R2, Backblaze B2) via a
  Flysystem adapter.
- Keep the project local-only and defer the question.

## Decision Drivers

- Minimize the number of parties holding private health data: a volume keeps
  documents within the one provider that already runs the app and database;
  S3 adds a second data processor to review, contract, and monitor.
- No new Composer package: the `local` disk works unchanged on a volume, so
  the package-review gate is never triggered; S3 requires a new adapter
  package that touches every private document.
- Railway's volume backups (manual + scheduled + API-driven restore) cover
  the personal-scale durability need that used to be S3's main argument.
- Reversibility is asymmetric: the storage path sits behind Laravel's
  filesystem abstraction, so migrating a volume's tens of files to S3 later
  is an afternoon (`Storage` disk swap plus one copy run with e.g. rclone);
  going to S3 now buys durability headroom this workload does not need at
  the price of permanent extra surface.
- S3's genuine advantages — multi-service access, lifecycle policies,
  versioning, unbounded scale — solve problems this single-owner app does
  not have.

## Consequences

- The Railway deployment runbook gains a pre-real-data step: configure
  scheduled volume backups and perform one documented restore drill.
- Volume contents do not roll back with app deployments; document deletion
  and restore behavior must be tested against the volume, not assumed.
- If a revisit trigger fires, the migration path is: add the reviewed S3
  adapter package, configure a second disk, copy files, flip the disk config,
  and retire the volume — no schema or domain changes.

## Confidence

Medium-high: the facts about Railway volume backups come from current
official docs, and the reversibility argument keeps the cost of being wrong
low. The restore drill is the deliberate checkpoint that converts this from
paper decision to verified one.

## Follow-Up Questions

- Which EU region does the Railway project pin, and does the Postgres service
  live in the same region as the volume?
- Should the restore drill become a recurring (e.g. quarterly) runbook step
  rather than a one-time gate?
