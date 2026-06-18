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
docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md
docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md
docs/research/laravel-stack-decision.md
docs/research/ai-architect-program-transfer.md
docs/research/competitor-analysis.md
docs/research/exa-firecrawl-research-runbook.md
docs/research/2026-06-18-apple-health-context-import.md
docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
docs/superpowers/plans/2026-06-16-first-vertical-slice.md
docs/session-handoff.md
docs/validation-protocol.md
docs/ops/production-checklist.md
docs/reviews/pre-scaffold-review-request.md
docs/reviews/pre-scaffold-review-scorecard.md
docs/reviews/pre-scaffold-review-result.md
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

grep -qi "PDF-first\\|labo-PDF" docs/project-brief.md
echo "ok: project brief records PDF-first intake"

grep -qi "review\\|bevestig" docs/project-brief.md
echo "ok: project brief requires review/confirmation"

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

grep -qi "PDF-first intake" docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md
echo "ok: ADR records PDF-first intake decision"

grep -qi "public research tools" docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md
grep -qi "Do not use Exa or Firecrawl for private health data in V1" docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md
echo "ok: ADR records Exa and Firecrawl public-research boundary"

grep -qi "Exa And Firecrawl Research Boundary" docs/evidence/source-index.md
echo "ok: source index records Exa and Firecrawl boundary"

grep -qi "Standard Workflow" docs/research/exa-firecrawl-research-runbook.md
grep -qi "Forbidden in V1" docs/research/exa-firecrawl-research-runbook.md
echo "ok: Exa and Firecrawl research runbook records workflow and boundary"

grep -qi "Apple Health Context Import" docs/research/2026-06-18-apple-health-context-import.md
grep -qi "streaming parser" docs/research/2026-06-18-apple-health-context-import.md
grep -qi "not as V1 live sync" docs/evidence/source-index.md
echo "ok: Apple Health research run records V2 context-import direction"

grep -qi "Decision: GO WITH CHANGES" docs/reviews/pre-scaffold-review-result.md
echo "ok: pre-scaffold review result records PDF-first go-with-changes"

echo
echo "== First-slice plan checks =="

grep -qi "PDF-First Intake Slice Plan" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: PDF-first slice plan exists"

grep -qi "Do not execute this plan" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: first-slice plan preserves pre-execution gate"

grep -qi "private document storage" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: PDF-first plan covers private document storage"

grep -qi "review/confirmation" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: PDF-first plan covers value review/confirmation"

grep -qi "status logic" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: first-slice plan covers status logic"

grep -qi "compare two blood tests" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: first-slice plan covers comparison"

grep -qi "sh scripts/validate.sh" docs/superpowers/plans/2026-06-17-pdf-first-intake-slice.md
echo "ok: first-slice plan includes validation protocol"

echo
echo "== Pre-scaffold review checks =="

grep -qi "Decision: GO / GO WITH CHANGES / NO-GO" docs/reviews/pre-scaffold-review-request.md
echo "ok: review request requires go/no-go decision"

grep -qi "2026-06-17-pdf-first-intake-slice" docs/reviews/pre-scaffold-review-request.md
echo "ok: review request points to PDF-first slice plan"

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

grep -qi "0005-use-pdf-first" AGENTS.md
echo "ok: AGENTS points to PDF-first ADR"

grep -qi "0006-use-exa-and-firecrawl" AGENTS.md
grep -qi "must not process private lab PDFs" AGENTS.md
echo "ok: AGENTS points to Exa and Firecrawl research boundary"

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
