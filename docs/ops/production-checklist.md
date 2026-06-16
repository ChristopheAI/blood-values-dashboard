# Production And Privacy Checklist

This project is not production-ready yet.

Use this checklist before any deployment, external sharing, cloud processing,
or real health-data use outside a local private development environment.

## Current Status

- Phase: planning baseline.
- Production deployment: not started.
- External processing: not allowed in V1 without a separate decision.
- Health data boundary: personal organization only, no medical advice.

## Privacy Baseline

- [ ] All health routes require authentication.
- [ ] Every health record is scoped to the owning user.
- [ ] User A cannot view, edit, compare, export, or delete User B's records.
- [ ] Uploaded lab documents are stored privately.
- [ ] Direct public file URLs cannot expose lab documents.
- [ ] Export is available to the owning user.
- [ ] Deletion behavior is explicit and tested.
- [ ] No AI/OCR/provider integration processes documents by default.
- [ ] No analytics or logging captures sensitive biomarker values or documents.

## Medical Boundary

- [ ] UI copy says this is personal tracking/consult preparation.
- [ ] UI copy does not claim diagnosis, treatment, or medical advice.
- [ ] Status labels explain they are based on entered reference ranges.
- [ ] Unknown status remains available when comparison is not trustworthy.
- [ ] Consult export contains no medical conclusions.

## Deployment Baseline

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` is unique and secret.
- [ ] Database backups are configured.
- [ ] Private file storage is backed up or explicitly disposable.
- [ ] HTTPS is enforced.
- [ ] Session/cookie settings are reviewed.
- [ ] Error reporting avoids leaking sensitive values.
- [ ] `sh scripts/validate.sh` passes in CI.

## Before Real Personal Data

- [ ] Decide local-only, private server, or hosted deployment.
- [ ] Confirm backup and restore process.
- [ ] Confirm deletion/export process.
- [ ] Confirm where documents are stored.
- [ ] Confirm no third-party processing happens without explicit approval.

## Out Of Scope For First Slice

- OCR extraction.
- AI interpretation.
- Provider integrations.
- Secure share links.
- Multi-user roles.
- Doctor access.

