# Research Run: Engineering Source Radar

Date: 2026-06-18

Question:

- Which additional engineering sources should influence this private Laravel
  blood values dashboard?

Decision affected:

- First implementation guardrails.
- Livewire security rules.
- Lab-PDF upload and private file-storage rules.
- Testing and architecture validation.
- Package and AI-assisted engineering discipline.

Scope:

- Public Laravel, Livewire, Pest, security, and practitioner sources.
- Exa search and fetch.
- Firecrawl scrape of selected public URLs.

Out of scope:

- Personal lab PDFs.
- Biomarker values.
- Medical advice, diagnosis, or interpretation.
- Runtime Exa, Firecrawl, or AI use inside the Laravel app.
- Installing packages or scaffolding Laravel code.

## Decision Intent

We are not trying to collect famous names.

We are choosing which public engineering sources deserve influence over a
private health-data Laravel app where the most important V1 risks are:

- owner boundary leaks;
- unsafe Livewire public properties or action parameters;
- unsafe lab-PDF uploads;
- public or poorly authorized document downloads;
- untested status/comparison rules;
- package sprawl;
- AI-generated global drift.

## Evaluation Axes

- Relevance to this app's actual risk surface.
- Source authority and proximity to Laravel/Livewire/PHP practice.
- Whether the source changes a concrete V1 rule.
- Whether the advice can be validated with tests.
- Whether following the source reduces privacy, security, or maintenance risk.

## Tool Use

Exa was used for discovery and content fetching across Laravel/Livewire
security, official docs, file storage, testing, and domain-architecture sources.

Firecrawl was executed against these public URLs:

- `https://livewire.laravel.com/docs/4.x/security`
- `https://livewire.laravel.com/docs/4.x/uploads`
- `https://securinglaravel.com/in-depth-dont-trust-public-livewire-properties/`
- `https://laravel.com/docs/13.x/filesystem`
- `https://laravel-beyond-crud.com/`

Firecrawl returned successful markdown extraction for all five public pages.
Only summarized findings are recorded here; raw scrape dumps were not committed.

## Queries Used

- `senior Laravel engineers writing about testing architecture security policies file uploads private data Livewire Pest Laravel app engineering practices`
- `Laravel security engineer Stephen Rees-Carter Livewire public properties file uploads private storage authorization policies health data app`
- `Laravel engineering sources official docs authorization filesystem validation file uploads policies testing Livewire security medical private data application`
- `Laravel senior engineers domain driven design modular Laravel actions data transfer objects validation service layer personal project architecture Brent Roose Martin Joo Wendell Adriel`
- `Laravel private document storage signed URLs file downloads authorization testing Storage fake official docs senior engineer source`

## A Sources: Directly Actionable For V1

### Laravel Authorization

- Source: `https://laravel.com/docs/13.x/authorization`
- Claim type: fact
- Decision utility: A
- Useful because: the product is owner-scoped. Every blood test, lab document,
  biomarker value, context note, export, and deletion action needs server-side
  authorization.

Transfer:

- Use Laravel policies for model/resource authorization.
- Do not equate "logged in" with "allowed".
- Tests must prove user A cannot view, change, export, or delete user B's data.

### Laravel Validation

- Source: `https://laravel.com/docs/13.x/validation`
- Claim type: fact
- Decision utility: A
- Useful because: lab PDF upload, biomarker values, dates, units, ranges, notes,
  and comparison inputs all need server-side validation.

Transfer:

- Use form requests or explicit validation boundaries where appropriate.
- Validation and authorization should live close to intake actions.
- Invalid/uncertain biomarker data should become `unknown`, not guessed.

### Laravel File Storage

- Source: `https://laravel.com/docs/13.x/filesystem`
- Claim type: fact
- Decision utility: A
- Useful because: the original lab-result PDF is a V1 source artifact and must
  not become publicly reachable by accident.

Transfer:

- Store lab PDFs on a private/local disk by default.
- Serve downloads through authenticated, owner-authorized routes.
- Use generated storage paths/names instead of user-supplied filenames.
- Test with `Storage::fake()`.

### Laravel HTTP Tests

- Source: `https://laravel.com/docs/13.x/http-tests`
- Claim type: fact
- Decision utility: A
- Useful because: file upload and download behavior can be proven without real
  storage by using Laravel's fake upload and fake disk helpers.

Transfer:

- Use `UploadedFile::fake()->create(..., 'application/pdf')` for PDF intake
  tests.
- Use `Storage::fake()` to prove private storage behavior.
- Use response assertions for downloads and forbidden/unauthorized paths.

### Livewire Security

- Source: `https://livewire.laravel.com/docs/4.x/security`
- Claim type: fact
- Decision utility: A
- Useful because: the likely V1 UI uses Livewire, and Livewire action
  parameters/public properties travel through the browser.

Transfer:

- Treat Livewire action parameters as untrusted input.
- Treat Livewire public properties as untrusted input.
- Use policies/`$this->authorize()` before persisting changes.
- Use model properties or `#[Locked]` for server-owned identifiers.
- Custom middleware that matters to authorization must persist across Livewire
  requests.

### Livewire File Uploads

- Source: `https://livewire.laravel.com/docs/4.x/uploads`
- Claim type: fact
- Decision utility: A
- Useful because: V1 intake starts with uploading a lab-result PDF.

Transfer:

- Configure upload validation for PDFs deliberately.
- Understand temporary upload behavior before permanent storage.
- Avoid public temporary or permanent lab-document URLs.
- Test Livewire upload components with fake files and fake disks.

### Pest Architecture Testing

- Source: `https://pestphp.com/docs/arch-testing`
- Claim type: fact
- Decision utility: A
- Useful because: this repo already accepted a staged Laravel quality ladder.

Transfer:

- Add architecture tests after the first module boundaries exist.
- Use security and Laravel presets where useful.
- Enforce no debug functions, no unsafe functions, and agreed boundaries in CI.

## B Sources: Strong Practitioner Evidence

### Stephen Rees-Carter / Securing Laravel

- Sources:
  - `https://securinglaravel.com/in-depth-dont-trust-public-livewire-properties/`
  - `https://securinglaravel.com/security-tip-pests-security-preset-strict-equality/`
- Claim type: inference from security-practitioner writing
- Decision utility: B/A depending on topic
- Useful because: Stephen Rees-Carter specializes in Laravel security audits and
  writes from a security-failure mindset.

Transfer:

- Public Livewire properties are functionally browser-controlled state.
- Add tests that tamper with IDs or authorization-bearing state.
- Pest security presets and strict equality are good candidates once code
  exists.

### Jonathan Bird Livewire Security Guide

- Source: `https://jonathanbird.com.au/blog/livewire-security-a-practical-guide-for-livewire-4-2026-guide`
- Claim type: practitioner synthesis
- Decision utility: B
- Useful because: it translates official Livewire security concerns into a
  practical checklist.

Transfer:

- Validate public properties.
- Lock server-only properties.
- Authorize every server action.
- Keep secrets and sensitive derived data out of public component state.
- Let storage generate names for uploads.

### Nazar Boyko File Upload Pipeline

- Source: `https://www.nazarboyko.com/articles/laravel-file-uploads-done-safely`
- Claim type: practitioner synthesis
- Decision utility: B
- Useful because: it frames uploads as a small deliberate pipeline rather than a
  one-line storage call.

Transfer:

- Validate before reading/storing.
- Prefer `mimetypes:` for security-sensitive upload acceptance.
- Sniff MIME as a backstop.
- Use generated names.
- Sensitive files belong on private disks and signed/authorized routes.
- For PDFs, store raw bytes but keep the storage and download path strict.

### Brent Roose / Laravel Beyond CRUD

- Source: `https://laravel-beyond-crud.com/`
- Claim type: practitioner architecture guidance
- Decision utility: B
- Useful because: this app has real domain concepts, but V1 is not yet a large
  enterprise application.

Transfer:

- Cherry-pick actions, small domain rules, data objects, and tests where they
  reduce complexity.
- Keep Laravel defaults where they remain clear.
- Do not add a DDD generator or complex module framework in V1.

### Spatie Package Decision Model

- Source: `https://spatie.be/blog/the-robots-are-replacing-the-packages`
- Claim type: practitioner decision model
- Decision utility: B
- Useful because: it gives a sharper question before `composer require`.

Transfer:

- Ask whether the project should own the problem.
- Native Laravel is likely enough for first private PDF storage.
- Packages become more attractive for hard, external, or opinionated problems.

## C Sources: Useful Signals, Not Decision Authorities

These may inspire later checks, but should not drive V1 decisions alone:

- generic DEV.to upload/security articles;
- random personal health Laravel repos;
- DDD scaffolding packages;
- broad enterprise Laravel marketing pages;
- example apps without clear privacy and test evidence.

Why:

- They may be correct in parts, but source authority, maintenance history, and
  fit to private health data are weaker.

## Immediate V1 Engineering Rules

These rules should influence the first implementation slice:

1. Lab PDFs are private source documents, not public media.
2. Store lab PDFs on a private disk with generated names.
3. Preserve original display filename only as sanitized metadata, never as the
   storage path.
4. Serve documents only through authenticated and owner-authorized routes.
5. Treat Livewire public properties and action parameters as untrusted input.
6. Use policies or explicit authorization before every mutation, export, or
   download.
7. Tests must cover cross-user denial for view, edit, download, export, and
   delete.
8. Tests must prove upload validation, private storage, and download behavior
   with fake files/disks.
9. Architecture tests come after boundaries exist; do not front-load ceremony.
10. Use packages only after fit/privacy/maintenance validation.

## Failure Modes This Radar Prevents

- "It works locally" but user B can download user A's lab PDF.
- A Livewire public property carries an ID that can be changed in the browser.
- Uploaded PDFs are stored under original names or public paths.
- Activity logs/backups duplicate health data without retention/redaction
  rules.
- A package is installed because a respected engineer uses it, not because it
  fits this app.
- AI-generated code follows local syntax but violates global privacy rules.

## Recommendation

The next implementation plan should add these gates before or during the first
PDF-intake slice:

```text
authorization tests
-> upload validation tests
-> private storage tests
-> download denial tests
-> Livewire tamper tests
-> architecture/security tests when boundaries exist
```

This source radar does not expand V1 scope. It tightens how V1 must be proven.

## Top 3 Next Actions

1. Owner: Codex. Effort: small. Risk: low. Add this source radar to the source
   index, AGENTS, validation script, and handoff.
2. Owner: Codex during implementation. Effort: medium. Risk: medium. Convert
   the immediate rules into tests for the PDF-first intake slice.
3. Owner: Christophe plus Codex before adding dependencies. Effort: small. Risk:
   low. Use the package-review discipline before installing storage, logging,
   backup, health, or permission packages.

## Known Unknowns

- The exact Laravel/Livewire versions used in the scaffold may change details.
- The upload implementation path may be Livewire, controller-based, or hybrid.
- Deployment target is not yet chosen, so backup/monitoring choices remain
  premature.
- No external human security review has been performed.

## Confidence

High for Livewire authorization/public-property and private-storage guardrails.

Medium for architecture-structure transfer until implementation reveals real
complexity.
