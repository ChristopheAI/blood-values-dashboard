# Intake Evidence Extraction — Parking Plan

> **Status:** Parked. Execute only when the trigger fires (see §6).
> **Risk:** Highest in the app — a wrong auto-confirm lands an unreviewed value
> on someone's health timeline.
> **Verification requirement:** `composer test` and `sh scripts/validate.sh`
> must pass locally after EACH step. Do not batch.

## 1. Why this exists

`app/Domain/Intake/RunBloodTestExtraction.php` (~980 lines) contains two trust
gates that look independent but share evidence predicates:

- `canAutoConfirm` (ADR-0011) — may a candidate be written as a confirmed value
  without human review?
- `canCreateTrustedBiomarker` — may a candidate create a new catalog biomarker?

Both gate on the same five predicates plus the status resolver, but the
predicates are private methods on the orchestrator with no unit-test surface of
their own. A previous extraction attempt broke `canCreateTrustedBiomarker` by
assuming the predicates were confirm-gate-only. They are not.

This plan captures the verified overlap and the correct extraction order so the
next attempt does not repeat the mistake.

## 2. Verified evidence overlap (read from code on 2026-06-30)

### Shared predicates — BOTH gates call these

| Predicate | `canAutoConfirm` | `canCreateTrustedBiomarker` |
| --- | --- | --- |
| `isTrustedCmaSource` | yes (both paths) | yes (both paths) |
| `hasQualitativeReferenceEvidence` | yes (qual path) | yes (qual path) |
| `hasParseableReferenceEvidence` | yes (numeric path) | yes (numeric path) |
| `hasCompatibleReferenceUnit` | yes (numeric path) | yes (numeric path) |
| `autoConfirmYieldsConclusiveStatus` | yes (both paths) | yes (both paths) |

### Status resolver — needed by `autoConfirmYieldsConclusiveStatus`

- `statusForCandidate(ExtractedBiomarkerCandidate $candidate): BiomarkerStatus`
- `status(string $unit, ?string $referenceUnit, string $value, ?string $referenceMin, ?string $referenceMax): BiomarkerStatus`
  - delegates to `DetermineBiomarkerStatus` + `DetectionLimitValue` + `QualitativeLabValue`

### Unique to `canAutoConfirm` — must NOT move to `CandidateEvidence`

- `$biomarker instanceof Biomarker`
- `hasUnambiguousLiteralCatalogMatch($document, $candidate, $biomarker)`
- `hasUnambiguousNumericFormat($candidate)` (numeric path only — the `1.234`
  European-thousands ambiguity guard)
- `is_numeric($this->normalizedNumber($candidate->value))` (numeric path)
- `$this->normalizedUnit($candidate->unit) !== ''` (numeric path)

### Unique to `canCreateTrustedBiomarker` — must NOT move to `CandidateEvidence`

- `$candidate->confidence >= AUTO_CONFIRM_CONFIDENCE_THRESHOLD`
- `$name !== ''`
- `containsLetter($name)`
- `! hasPotentialCatalogMatch($document, $candidate)`

### Where the confidence threshold actually lives

`canAutoConfirm` does NOT check confidence directly. The chain is:

```
effectiveConfidence($document, $candidate, $biomarker)
  → if !canAutoConfirm(...): return min($candidate->confidence, DRAFT_CONFIDENCE_CAP)
  → else: return $candidate->confidence

shouldAutoConfirm($document, $candidate, $biomarker, $confidence)
  → return $confidence >= AUTO_CONFIRM_CONFIDENCE_THRESHOLD && canAutoConfirm(...)
```

`canCreateTrustedBiomarker` checks `$candidate->confidence >= AUTO_CONFIRM_CONFIDENCE_THRESHOLD`
directly. Keep this asymmetry intact during extraction.

## 3. The landmine

If `canAutoConfirm` is extracted into a standalone `AutoConfirmPolicy` and the
five shared predicates move with it, `canCreateTrustedBiomarker` loses access to
`isTrustedCmaSource`, `hasQualitativeReferenceEvidence`,
`hasParseableReferenceEvidence`, `hasCompatibleReferenceUnit`, and
`autoConfirmYieldsConclusiveStatus`. It silently stops working.

**Correct order:** extract the shared evidence FIRST, then the policy wrappers
as thin clients over it.

## 4. Normalization helper complication

The shared predicates depend on normalization helpers that are also used
elsewhere in `RunBloodTestExtraction` (storing values, name normalization):

- `normalizedNumber(string $value): string`
- `normalizedUnit(?string $unit): string`
- `normalizedNullableNumber(?string $value): ?string`
- `normalizedNullableUnit(?string $unit): ?string`

Recommended approach: extract a small `CandidateNormalization` helper alongside
`CandidateEvidence`. Both `CandidateEvidence` and `RunBloodTestExtraction`
depend on it. This avoids duplication and keeps step 1 a pure refactor.

If a single-class step 1 is preferred, `CandidateEvidence` may receive the
orchestrator's normalized values via constructor, but this changes predicate
signatures and is less clean.

## 5. Extraction order

### Step 1 — `CandidateEvidence` (+ `CandidateNormalization`)

**New files:**

- `app/Domain/Intake/CandidateEvidence.php`
- `app/Domain/Intake/CandidateNormalization.php`
- `tests/Unit/Intake/CandidateEvidenceTest.php`
- `tests/Unit/Intake/CandidateNormalizationTest.php`

**`CandidateEvidence` holds:**

- `isTrustedCmaSource(ExtractedBiomarkerCandidate $candidate): bool`
- `hasQualitativeReferenceEvidence(ExtractedBiomarkerCandidate $candidate): bool`
  (depends on `qualitativeReferenceHint`)
- `hasParseableReferenceEvidence(ExtractedBiomarkerCandidate $candidate): bool`
  (depends on `isTrustedCmaSource` + `hasParseableReferenceBounds`)
- `hasCompatibleReferenceUnit(ExtractedBiomarkerCandidate $candidate): bool`
- `autoConfirmYieldsConclusiveStatus(ExtractedBiomarkerCandidate $candidate): bool`
- `statusForCandidate(ExtractedBiomarkerCandidate $candidate): BiomarkerStatus`
- `status(...): BiomarkerStatus` (delegates to `DetermineBiomarkerStatus` +
  `DetectionLimitValue` + `QualitativeLabValue`)

**`RunBloodTestExtraction` change:** both `canAutoConfirm` and
`canCreateTrustedBiomarker` delegate their shared predicate calls to
`$this->candidateEvidence`. No behavior change. No gate-logic change.

**Unit tests for `CandidateEvidence`:**

1. trusted-source detection (CMA layout, CMA tabular, inline, unknown)
2. confidence threshold is NOT in `CandidateEvidence` (it stays in the gates)
3. qualitative reference evidence (explicit `referenceQualitative`, PCR-negative
   expectation via source-snippet hint)
4. parseable numeric reference bounds (null min/max on trusted CMA, valid range,
   reversed range, unparseable bounds)
5. compatible reference unit (null reference unit, matching, mismatched)
6. ambiguous three-digit decimal format guard (`1.234` → not unambiguous) —
   note: `hasUnambiguousNumericFormat` is unique to `canAutoConfirm` and stays
   there; do NOT add it to `CandidateEvidence`
7. conclusive vs unknown status (qualitative, numeric, detection-limit)

**Safety net:** the existing `tests/Feature/Intake/AssistedPdfExtractionTest.php`
(~70 end-to-end cases) is the primary regression catcher. If step 1 breaks
behavior, that test goes red.

**Verify before step 2:**

```bash
php artisan test tests/Unit/Intake/CandidateEvidenceTest.php
php artisan test tests/Unit/Intake/CandidateNormalizationTest.php
php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php
sh scripts/validate.sh
```

### Step 2 — `AutoConfirmPolicy` + `TrustedImportPolicy`

**Only after step 1 is green and merged.**

**New files:**

- `app/Domain/Intake/AutoConfirmPolicy.php`
- `app/Domain/Intake/TrustedImportPolicy.php`
- `tests/Unit/Intake/AutoConfirmPolicyTest.php`
- `tests/Unit/Intake/TrustedImportPolicyTest.php`

**`AutoConfirmPolicy` holds (thin wrapper over `CandidateEvidence`):**

- `$biomarker instanceof Biomarker`
- `hasUnambiguousLiteralCatalogMatch` (needs `$document` + catalog lookup)
- `hasUnambiguousNumericFormat` (numeric path only)
- numeric/qualitative value shape checks
- delegates shared evidence to `CandidateEvidence`

**`TrustedImportPolicy` holds (thin wrapper over `CandidateEvidence`):**

- `$candidate->confidence >= AUTO_CONFIRM_CONFIDENCE_THRESHOLD`
- `$name !== ''`
- `containsLetter($name)`
- `! hasPotentialCatalogMatch` (needs `$document` + catalog lookup)
- delegates shared evidence to `CandidateEvidence`

**`RunBloodTestExtraction` change:** `canAutoConfirm` and
`canCreateTrustedBiomarker` become one-line delegations to the policy classes.

**Verify:**

```bash
php artisan test tests/Unit/Intake/
php artisan test tests/Feature/Intake/AssistedPdfExtractionTest.php
sh scripts/validate.sh
```

## 6. Trigger — when to execute

Do this work when ANY of:

- a change to `canAutoConfirm`, `canCreateTrustedBiomarker`, or a shared
  evidence predicate lands in a PR;
- a new trusted-source label is added (expanding `isTrustedCmaSource`);
- the ADR-0011 confidence threshold or `DRAFT_CONFIDENCE_CAP` is revisited;
- the Cloud Agent VM has PHP/composer/sqlite installed (via env-setup agent),
  making local verification cheap.

Until then, the code works and `AssistedPdfExtractionTest` guards it.

## 7. Branch and PR

- Branch: `cursor/intake-evidence-extraction-5034` off `main`.
- PR: draft, titled `refactor: extract CandidateEvidence from RunBloodTestExtraction`.
- Do NOT combine with the thematic biomarker overview (#42) or architecture
  hardening (#43) PRs — unrelated, lower priority, and V2 merge is already done.
- Do NOT push to `main` without local `sh scripts/validate.sh` green.

## 8. Context used

- `app/Domain/Intake/RunBloodTestExtraction.php` lines 505–531, 670–690, 692–895
- `app/Domain/Intake/AGENTS.md`
- `docs/adr/0011-clean-extraction-and-confidence-gated-auto-confirm.md` (incl.
  2026-06-25 amendments)
- `tests/Feature/Intake/AssistedPdfExtractionTest.php` (safety net)

## 9. Anti-patterns to avoid

- Do NOT extract `canAutoConfirm` alone and move the shared predicates with it.
- Do NOT add `hasUnambiguousNumericFormat` to `CandidateEvidence` — it is
  confirm-gate-only.
- Do NOT add the confidence threshold check to `CandidateEvidence` — it lives in
  `shouldAutoConfirm` / `canCreateTrustedBiomarker` separately.
- Do NOT batch step 1 and step 2 in one PR without verification between.
- Do NOT claim done without local `sh scripts/validate.sh` green.
