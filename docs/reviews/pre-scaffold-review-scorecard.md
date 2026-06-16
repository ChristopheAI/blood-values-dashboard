# Pre-Scaffold Review Scorecard

Use this scorecard while reviewing
`docs/reviews/pre-scaffold-review-request.md`.

Scores:

- `0`: missing or wrong;
- `1`: present but weak;
- `2`: acceptable for scaffold;
- `3`: strong.

## Product And Scope

| Check | Score | Notes |
| --- | ---: | --- |
| Product is clearly a personal tracking system, not medical advice. |  |  |
| V1 scope is narrow enough to build. |  |  |
| Out-of-scope boundaries are explicit. |  |  |
| First slice proves real product value. |  |  |
| First slice avoids OCR, AI, integrations, and medical recommendations. |  |  |

Minimum to scaffold:

- no `0`;
- average at least `2`.

## Laravel Architecture

| Check | Score | Notes |
| --- | ---: | --- |
| Livewire starter kit is justified for V1. |  |  |
| Domain logic is outside Livewire components. |  |  |
| Status calculation is testable as plain domain logic. |  |  |
| Data model is coherent for blood tests, biomarkers, and results. |  |  |
| Compare-two-tests workflow has clear rules. |  |  |
| Filament is not introduced prematurely. |  |  |

Minimum to scaffold:

- no `0`;
- status calculation and user-owned data model score at least `3`.

## Security And Privacy

| Check | Score | Notes |
| --- | ---: | --- |
| All health data is owner-scoped. |  |  |
| User isolation tests are required. |  |  |
| Private document storage is specified before upload work. |  |  |
| Export/delete requirements are visible. |  |  |
| Medical boundary is reflected in copy rules. |  |  |
| Production/privacy checklist exists before deployment work. |  |  |

Minimum to scaffold:

- no `0`;
- owner scoping and user isolation tests score `3`.

## Workflow And Validation

| Check | Score | Notes |
| --- | ---: | --- |
| Planning baseline is committed before scaffold. |  |  |
| Handoff points to the actual checkpoint. |  |  |
| Validation protocol explains marker resolution. |  |  |
| `scripts/validate.sh` proves current phase. |  |  |
| Plan updates validation immediately after scaffold. |  |  |
| CI will run validation after implementation begins. |  |  |

Minimum to scaffold:

- no `0`;
- validation transition scores at least `2`.

## Reviewer Decision

Decision:

- [ ] GO
- [ ] GO WITH CHANGES
- [ ] NO-GO

Blocking issues:

-

Required changes before scaffold:

-

Required changes after scaffold:

-

Confidence:

- [ ] High
- [ ] Medium
- [ ] Low

