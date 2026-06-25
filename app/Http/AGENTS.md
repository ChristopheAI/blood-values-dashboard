# HTTP Layer

Owns web controllers and authenticated route boundaries. Controllers validate
requests, authorize ownership, call domain/model code, and return views,
downloads, redirects, or exports. They do not own domain meaning or UI copy.

## Entry Points

- `routes/web.php` - app routes under `auth` and `verified` middleware.
- `Controllers/BloodTests/*` - upload, compare, delete, document download/delete.
- `Controllers/ConsultOverview/*` - consult view and CSV export.
- `Controllers/ContextNotes/*`, `Reminders/*`, `Biomarkers/*` - CRUD-style owner
  scoped actions.
- `Controllers/Privacy/*` - data export and delete-all health data boundaries.

## Contracts & Invariants

- Treat every route parameter and request field as untrusted.
- Route model binding is not authorization. Check the model belongs to the
  authenticated user or query through the authenticated user.
- Downloads must stream only private files through owner-authorized routes; never
  expose storage paths.
- Mutations use POST/PATCH/DELETE with CSRF. Sensitive health text must not ride
  in GET query strings.
- Controllers may select owned records and normalize filters, but confirmed-only
  data selection belongs in domain/model query rules.

## Patterns

- Keep controllers single-action and small; push reusable behavior to
  `app/Domain` or model scopes.
- For selected IDs, intersect with owned records server-side before passing data
  downstream.
- For exports, validate transport privacy as well as authorization.
- Return generic 403/404 behavior for foreign records; do not reveal filenames,
  labels, or existence.

## Anti-patterns

- Do not trust hidden form fields, Livewire state, selected IDs, or route IDs.
- Do not build CSV/export rows directly from request data.
- Do not add external calls for private PDFs, biomarker values, context notes, or
  consult material.
- Do not make GET routes carry consult questions, symptoms, medication notes, or
  other sensitive free text.

## Related Context

- Root rules: `../../AGENTS.md`
- Domain rules: `../Domain/AGENTS.md`
- Model rules: `../Models/AGENTS.md`
- View rules: `../../resources/views/AGENTS.md`
