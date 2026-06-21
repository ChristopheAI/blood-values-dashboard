# Definition Of Done Template

Use this before implementation starts. Delete any line that truly does not apply
and add slice-specific checks where needed.

## Product Boundary

- [ ] The slice supports the PRD product sentence.
- [ ] The slice is tied to a GitHub issue or explicit source-of-truth gap.
- [ ] Every user-facing/domain task cites a resolvable PRD/spec/ADR/design,
  data-model, or issue reference, unless it is pure infrastructure.
- [ ] Any missing design or product decision is tagged `[NEEDS-DESIGN]` or
  `[NEEDS-DECISION]` instead of being invented during implementation.
- [ ] The slice does not add medical diagnosis, treatment advice, health
  scoring, urgency ranking, supplement/diet/training recommendations, or extra
  testing encouragement.
- [ ] Runtime AI, OCR, Exa, Firecrawl, provider sync, wearable import, and
  external processing remain out of scope unless a new ADR explicitly allows
  them.

## Data Boundary

- [ ] Confirmed-only downstream is preserved.
- [ ] Drafts stay out of dashboard, status, history, compare, consult, export,
  and trends.
- [ ] Owner scoping is enforced server-side.
- [ ] Sensitive free text does not travel through GET query strings.
- [ ] Source documents are accessed only through owner-authorized routes.

## Tests

- [ ] A failing regression or feature test was written first when behavior
  changed.
- [ ] Focused feature/unit tests pass.
- [ ] Privacy or corrupted-link fixtures are included when selected IDs,
  exports, downloads, delete actions, or relations are touched.
- [ ] Medical-copy boundary tests are updated when user-visible copy changes.

## Browser QA

- [ ] The matching surface was driven in this turn: browser route, export,
  console command, issue form, or other user-visible/developer-visible surface.
- [ ] Real route inspected when the slice changes UI or workflow behavior.
- [ ] Correct account/user used.
- [ ] Synthetic QA data or a privacy-safe fixture was used unless the owner
  explicitly requested a real private-data check.
- [ ] Expected confirmed count or confirmed-only output observed.
- [ ] Expected draft/review exclusion observed where downstream data is touched.
- [ ] Source document visibility or owner-authorized access checked where
  documents are touched.
- [ ] Mobile or responsive surface checked when layout changed.

## Validation

- [ ] `php artisan view:clear` run when Blade/Livewire views changed.
- [ ] Focused tests run and passed.
- [ ] `sh scripts/validate.sh` run and passed for code changes.
- [ ] Any failure is documented with exact command and reason.

## Git Hygiene

- [ ] `git status --short --branch` checked.
- [ ] Only files in this slice are staged.
- [ ] Unrelated `.omo/`, scratch files, generated files, or other slices remain
  unstaged.
- [ ] Commit message uses terse Conventional Commit style.
- [ ] Handoff names remaining dirty files and whether they are intentionally
  unrelated.
