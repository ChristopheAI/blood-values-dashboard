#!/bin/sh
set -eu

echo "== Planning files =="

required_files="
README.md
AGENTS.md
docs/project-brief.md
docs/v1-spec.md
docs/product-system-check.md
docs/evidence/source-index.md
docs/templates/adr-template.md
docs/adr/0001-use-repo-as-project-control-plane.md
docs/adr/0002-use-adrs-for-architecture-decisions.md
docs/adr/0003-use-livewire-starter-kit-for-v1.md
docs/adr/0004-manual-entry-and-owner-scoped-health-data.md
docs/research/laravel-stack-decision.md
docs/research/ai-architect-program-transfer.md
docs/superpowers/plans/2026-06-16-first-vertical-slice.md
docs/session-handoff.md
docs/validation-protocol.md
docs/ops/production-checklist.md
docs/reviews/pre-scaffold-review-request.md
docs/reviews/pre-scaffold-review-scorecard.md
laravel-platform-discovery.md
"

for file in $required_files; do
  if [ ! -f "$file" ]; then
    echo "Missing required file: $file" >&2
    exit 1
  fi
  echo "ok: $file"
done

echo
echo "== Project brief scope checks =="

grep -qi "geen diagnosemachine" docs/project-brief.md
echo "ok: project brief excludes diagnosis-machine positioning"

grep -qi "geen medisch advies" docs/project-brief.md
echo "ok: project brief excludes medical advice"

grep -qi "manual\\|manueel" docs/project-brief.md
echo "ok: project brief keeps manual entry in V1"

grep -qi "export" docs/project-brief.md
echo "ok: project brief includes export/data control"

echo
echo "== V1 spec checks =="

grep -qi "Livewire starter kit" docs/v1-spec.md
echo "ok: V1 spec records Livewire starter kit direction"

grep -qi "Status Calculation" docs/v1-spec.md
echo "ok: V1 spec defines status calculation"

grep -qi "unknown" docs/v1-spec.md
echo "ok: V1 spec preserves unknown status"

grep -qi "Out of scope" docs/v1-spec.md
echo "ok: V1 spec defines out-of-scope boundaries"

echo
echo "== Evidence and ADR checks =="

grep -qi "fact" docs/evidence/source-index.md
grep -qi "inference" docs/evidence/source-index.md
grep -qi "hypothesis" docs/evidence/source-index.md
grep -qi "unknown" docs/evidence/source-index.md
echo "ok: source index separates claim types"

grep -qi "ai-architect-program-research" docs/research/ai-architect-program-transfer.md
echo "ok: AI Architect transfer source is documented"

grep -qi "Focus And UX Simplicity" docs/research/ai-architect-program-transfer.md
echo "ok: AI Architect transfer preserves focus/UX gate"

grep -qi "10-gate model" docs/product-system-check.md
echo "ok: product-system check uses 10-gate lens"

grep -qi "Verdict: bouwen" docs/product-system-check.md
echo "ok: product-system check records build verdict"

for adr in docs/adr/*.md; do
  grep -qi "^## Status" "$adr"
  grep -qi "^## Decision" "$adr"
  grep -qi "^## Consequences" "$adr"
done
echo "ok: ADRs include status, decision, and consequences"

grep -qi "Livewire starter kit" docs/adr/0003-use-livewire-starter-kit-for-v1.md
echo "ok: ADR records Livewire starter kit direction"

grep -qi "owner-scoped" docs/adr/0004-manual-entry-and-owner-scoped-health-data.md
echo "ok: ADR records owner-scoped health data"

echo
echo "== First-slice plan checks =="

grep -qi "First Vertical Slice Implementation Plan" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan exists"

grep -qi "Do not execute this plan" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan preserves pre-execution gate"

grep -qi "Biomarker Status Domain Logic" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan covers status logic"

grep -qi "Compare Two Tests" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan covers comparison"

grep -qi "README.md" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan preserves README"

grep -qi "docs/validation-protocol.md" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan includes validation protocol"

grep -qi "docs/ops/production-checklist.md" docs/superpowers/plans/2026-06-16-first-vertical-slice.md
echo "ok: first-slice plan includes production/privacy checklist"

echo
echo "== Pre-scaffold review checks =="

grep -qi "Decision: GO / GO WITH CHANGES / NO-GO" docs/reviews/pre-scaffold-review-request.md
echo "ok: review request requires go/no-go decision"

grep -qi "senior Laravel" docs/reviews/pre-scaffold-review-request.md
echo "ok: review request names Laravel reviewer profile"

grep -qi "application security/privacy" docs/reviews/pre-scaffold-review-request.md
echo "ok: review request names security/privacy reviewer profile"

grep -qi "owner-scoped" docs/reviews/pre-scaffold-review-scorecard.md
echo "ok: scorecard checks owner scoping"

echo
echo "== Control-plane coherence checks =="

grep -qi "Planning baseline" README.md
echo "ok: README states current phase"

grep -qi "Marker Resolution" docs/validation-protocol.md
echo "ok: validation protocol explains marker resolution"

grep -qi "Privacy Baseline" docs/ops/production-checklist.md
echo "ok: production checklist includes privacy baseline"

grep -qi "README.md" AGENTS.md
echo "ok: AGENTS points to README"

grep -qi "docs/validation-protocol.md" docs/session-handoff.md
echo "ok: handoff points to validation protocol"

echo
echo "== Planning-stage boundary checks =="

if [ -f artisan ] || [ -f composer.json ] || [ -d app ] || [ -d routes ] || [ -d database ]; then
  echo "Laravel app files detected." >&2
  echo "This validation script is still for the planning stage." >&2
  echo "Update scripts/validate.sh before implementation validation." >&2
  exit 1
fi

echo "ok: no Laravel app scaffold detected yet"

echo
echo "== Whitespace checks =="
git diff --check
echo "ok: git diff whitespace check passed"

echo
echo "Planning validation passed."
