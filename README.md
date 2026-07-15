# Blood values dashboard

**Personal Laravel app** I built to turn **lab-result PDFs** into structured biomarker history: upload → review uncertain values → confirmed-only dashboard, trends, compare, consult prep, export.

Not a diagnosis product. No medical advice.

**Builder:** [ChristopheAI](https://github.com/ChristopheAI) · Portfolio: [vastpakt.be](https://vastpakt.be)

## Why it exists

Lab PDFs are hard to track over time. This app keeps **my** results private, structured, and comparable — with an explicit review step so bad extractions do not silently become “truth”.

## What it does (current focus)

- Auth + private document storage  
- **PDF-first intake** of blood test reports  
- Deterministic, **confidence-gated** extraction (local CMA layout path)  
- Review UI for uncertain rows; **confirmed-only** downstream  
- Biomarker catalog + status  
- History, compare two tests, consult-oriented overview  
- Privacy actions (export / delete health data)  

Explicitly **out of scope** for this slice: runtime AI medical interpretation, unreviewed OCR as source of truth, wearables, third-party clinical integrations.

## Stack

- **Laravel** + Livewire (PHP)  
- Domain modules under `app/Domain/` (Intake, Biomarkers, BloodTests, Dashboard, Consult, Privacy)  
- Tests + `sh scripts/validate.sh`  
- CI workflow in `.github/workflows/`  
- Specs / ADRs under `docs/`  

Built with the same **agentic coding** workflow I use elsewhere: agents implement; I set rules (AGENTS.md, ADRs) and review until it is safe to run on real uploads.

## Validation

```bash
sh scripts/validate.sh
```

## Docs map

| Start here | Purpose |
|---|---|
| `AGENTS.md` | Hard rules for agents working in this repo |
| `docs/project-brief.md` | Product intent |
| `docs/adr/` | Architecture decisions |
| `docs/ops/production-checklist.md` | Ops checklist |
| `docs/session-handoff.md` | Current handoff |

## Privacy

Personal health data must not land in public issues, screenshots, or sample fixtures beyond synthetic/test PDFs already in `tests/Fixtures/`.

## License / use

Personal project. Not offered as a medical device or clinical tool.
