# NL Market Research: Positioning Bloedwaarden-Dashboard

Date: 2026-06-27  
Audience: product/build decisions for this Laravel repo  
Method: public web research only (official sites, App Store listings, help docs).  
No private PDFs, biomarker values, or user data were used.

Claim types follow `docs/evidence/source-index.md`: fact / inference / hypothesis / unknown.

## Decision Intent

Validate whether this project occupies a **distinct lane**:

> Private follow-up for **existing** lab PDFs (huisarts/CMA) → owner-confirmed values →
> trends/compare → **consult export**, without selling tests or giving medical advice.

And derive **concrete product actions** (copy, PR C, what to reject).

---

## 1. Positioning (recommended)

**One sentence (NL):**

> Privé lab-dossier van je eigen PDF's: bevestigde waarden, wijzigingen en bronnen —
> klaar om compact met je arts te bespreken.

**English product sentence (unchanged from PRD):**

> Turn scattered lab PDFs into a private, owner-scoped, confirmed-only timeline for
> doctor conversation — not diagnosis.

**Do not position as:**

- gezondheidscheck / bloedtest bestellen (Levenswijs, Vitasure, Coolioo)
- AI wellness coach / biohacking optimizer (FitReelix, InsideTracker pattern)
- vervanging van MedGemak / huisartsportaal
- AI die uitslagen “uitlegt” of advies geeft (SnapLabs, FitReelix pattern)

---

## 2. Market lanes (NL)

| Lane | Who | Revenue model | Overlap with us |
| --- | --- | --- | --- |
| **A. Test verkopen + dashboard** | Levenswijs, Vitasure, Coolioo | Panel + abo/consult upsell | Laag — zij zijn lab-first, wij PDF-first |
| **B. Officieel zorgportaal** | MijnGezondheid.net / MedGemak | Zorgaanbieder | Laag — geen eigen PDF-archief/export |
| **C. PDF/AI tracker apps** | SnapLabs, FitReelix, Hemeify (intl) | Abo / scans | Medium — zelfde intake, andere trust/privacy |
| **D. Handmatige tracker** | Bloedonderzoek Records | App aankoop | Medium — geen PDF, wel trends |
| **E. Private dossier + confirm gate** | **This project** | Personal tool (V1) | **Target lane** |

**Inference:** Lane E is crowded internationally but **under-served in NL Dutch copy** with
**local/private + confirmed-only + consult pack** and **without** runtime cloud AI on PDFs.

---

## 3. Competitor matrix (NL + adjacent, 2026-06-27)

| Product | PDF in | Extraction | User confirm | Trends | Consult/print | Advies/score | Privacy signal |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **MedGemak / MGn** | Portal view (fact) | N/A | Provider | Limited in app (fact) | Unknown export | No app advice (fact) | DigiD, zorg (fact) |
| **Levenswijs** | Own flow only (fact) | Lab pipeline (fact) | Arts rapport (fact) | Dashboard vergelijken (fact) | Dashboard (fact) | **Arts + advies FAQ** (fact) | AVG EU (fact) |
| **Vitasure VitaTrack** | Own kits (fact) | Dashboard (fact) | Tips in UI (fact) | VitaTrack (fact) | Dashboard (fact) | **Tips voeding/leefstijl** (fact) | NEN7510 claim (fact) |
| **Coolioo** | Thuis kit (fact) | Dashboard uitleg (fact) | — | Dashboard (fact) | Dashboard link (fact) | “Geen diagnose, wel inzicht” (fact) | ISO lab (fact) |
| **FitReelix** | **AI PDF scan** (fact) | Cloud AI (fact) | Unclear | Trends (fact) | — | **Status schatting, supplementen** (fact) | Privacy claim (fact) |
| **SnapLabs** | **AI PDF/photo** (fact) | Cloud AI (fact) | Auto | Graphs (fact) | Subscription (fact) | **AI explains results** (fact) | Encrypted (fact) |
| **Bloedonderzoek Records** | No (fact) | Manual (fact) | User entry (fact) | Charts (fact) | — | Disclaimer only (fact) | App privacy policy |
| **This repo (V2)** | **Local PDF** (fact) | Deterministic CMA (fact) | **`confirmed_at` gate** (fact) | Longitudinal (fact) | **Consult pack POST** (fact) | **Explicitly out of scope** (fact) | Local/private ADR (fact) |

Sources: product pages fetched 2026-06-27 — Levenswijs, Vitasure, FitReelix, MedGemak help;
App Store SnapLabs/Bloedonderzoek Records; repo `docs/codex-prd.md`, `AGENTS.md`.

---

## 4. Messaging gap (Ads + landing copy)

**What NL test-sellers emphasize (fact, from sites):**

- “zonder verwijzing”, “binnen 48 uur”, “arts beoordeelt”
- “begrijpelijke taal”, “tips”, “vitaler leven”
- wearables koppelen (Vitasure)

**What they do not emphasize:**

- Upload **bestaande huisarts-PDF**
- **Jij** bevestigt waarden vóór trends/consult
- **Printbare consultlijst** met bron-PDF metadata
- **Local-only** / geen cloud-AI op labdata

**Inference:** External messaging for this app should lead with **dossier + consult**,
not “gezondheidscheck” or “AI analyse”.

**Suggested DO / DON'T copy (NL):**

| DO | DON'T |
| --- | --- |
| lab-dossier, uitslagen bijhouden | gezondheidscheck, preventief testen |
| bevestigde waarden | AI interpretatie, optimal range |
| wijzigingen t.o.v. vorige test | score, risico, urgent |
| bron-PDF bewaard | extra testen aanraden |
| bespreken met arts | behandeling, supplement advies |

---

## 5. Search & trends research (owner-run)

Cloud agent cannot execute signed-in Google Trends AI or Keyword Planner. Use these
**ready-made steps** locally (15–30 min).

### 5.1 Google Trends Explore (NL)

Open (compare terms, 12 months or 5 years):

- [lab uitslag vs bloedwaarden vs bloedtest](https://trends.google.com/trends/explore?geo=NL&q=lab%20uitslag,bloedwaarden,bloedtest&date=today%2012-m)
- [bloedwaarden huisarts vs bloedtest zonder verwijzing](https://trends.google.com/trends/explore?geo=NL&q=bloedwaarden%20huisarts,bloedtest%20zonder%20verwijzing&date=today%205-y)
- [lab uitslag pdf / bloeduitslag bewaren](https://trends.google.com/trends/explore?geo=NL&q=lab%20uitslag%20pdf,bloeduitslag%20bewaren&date=today%205-y)

**Gemini side panel prompts (generic — no personal health data):**

1. *Vergelijk zoektermen voor mensen die bloeduitslagen van de huisarts willen
   bijhouden versus mensen die zelf een bloedtest willen bestellen in Nederland.*
2. *Welke gerelateerde queries gaan over trends, vergelijken, excel of app?*
3. *Welke termen zijn seizoensgebonden vs stabiel?*

**Record in checklist:** `docs/research/2026-06-27-market-research-owner-checklist.md`

### 5.2 Keyword Planner seeds

Google Ads → Expert Mode → Keyword Planner → Discover new keywords:

```
lab uitslag pdf
bloedwaarden bijhouden
bloedonderzoek uitslag
bloedwaarden app
lab resultaten dashboard
bloedtest vergelijken
consult huisarts bloedwaarden
bloeduitslag excel
CMA lab uitslag
MedGemak uitslag export
```

**Classify each result:**

- **Organizational** → our lane
- **Transactional test** → Levenswijs lane (ignore for product)
- **Informational biomarker** → out of scope (advice trap)

**Hypothesis:** Organizational cluster has **lower volume** but **higher intent fit**
for this repo. SEO is not the primary GTM for V1 personal use.

### 5.3 Meta Ads Library (NL)

URL: [facebook.com/ads/library](https://www.facebook.com/ads/library) — country **Netherlands**, ad type **All ads**.

Search terms to run:

| Search | Purpose |
| --- | --- |
| `Levenswijs` | Preventief test + arts positioning |
| `Vitasure` | VitaTrack dashboard + tips |
| `Coolioo` | Thuis check copy |
| `gezondheidscheck` | Category hooks |
| `bloedtest` | Broad competitive tone |

**Capture:** screenshot or note primary headline, CTA, longevity (weeks+ = working creative).

**Inference:** Expect heavy **test-order + reassurance + arts** angles; unlikely to see
“upload your existing PDF” — confirms whitespace for lane E.

---

## 6. Practitioner pain (supports product, from prior evidence)

From `docs/research/2026-06-18-blood-values-workflow-value-evidence.md` (still valid):

- PDFs scattered across mail, portals, folders
- Spreadsheets break on units, ranges, lab formats
- Users want **history + compare + doctor visit prep**

**Inference:** Killer workflow is **consult preparation + longitudinal compare**, not
another results viewer.

---

## 7. Product hypotheses

| ID | Hypothesis | Test | Status |
| --- | --- | --- | --- |
| H1 | **Consult export/print** reduces prep time vs PDF map + Excel | 3 interviews + own dogfood | hypothesis |
| H2 | **Review/confirm gate** is acceptable if auto-confirm handles trusted CMA rows | ADR-0011 live gate + draft remainder | partial fact (owner live 2026-06-24) |
| H3 | Dashboard **“Klaar voor consult?”** increases consult pack usage vs buried CTA | Before/after own usage; analytics N/A V1 | hypothesis |
| H4 | NL users won't search for our niche; discovery = word-of-mouth / personal need | Trends/Planner owner-run | unknown |
| H5 | **Local/private** is differentiator vs SnapLabs/FitReelix cloud AI | Competitor matrix | inference |

---

## 8. Product actions (prioritized)

### P0 — Align product with lane E

| # | Action | Rationale |
| --- | --- | --- |
| 1 | **PR C: consult document mode** — print header, `@media print`, nav hidden | Market gap vs test-sellers; matches consult job |
| 2 | **Dashboard IA: demote duplicate latest panel** or merge with table | Reduce noise; mock alignment |
| 3 | **Handoff parity:** “Selectie aanpassen” pre-fills same defaults as POST | Fixes broken consult funnel |

### P1 — Messaging (when public-facing copy changes)

| # | Action |
| --- | --- |
| 4 | Welcome/dashboard subcopy: dossier + consult, not gezondheidscheck |
| 5 | Explicit “geen medisch advies / geen test bestellen” in footer or about |
| 6 | NL date formatting — **done** on main (`d5ce0b9`) |

### P2 — Privacy trust (supports positioning)

| # | Action |
| --- | --- |
| 7 | Account delete must call `DeleteAllHealthData` + storage sweep |
| 8 | Export/consult copy: data stays local (accurate to deployment) |

### P3 — Research follow-up (owner)

| # | Action |
| --- | --- |
| 9 | Complete owner checklist (Trends AI + Planner + Ads Library) |
| 10 | 3 interviews — protocol: `docs/research/2026-06-27-interview-protocol-consult-workflow.md` |
| 11 | Aggregate CMA vs non-CMA upload ratio (counts only, no PDF content) |

### Explicitly defer

- Test ordering, lab partnerships, ZorgDomein
- Runtime cloud AI PDF interpretation (ADR violation)
- Wearable sync marketing (out of V2 scope)
- SEO/content on individual biomarkers (advice trap)

---

## 9. Relation to existing docs

- Extends `docs/research/competitor-analysis.md` with **NL 2026** players and lane map.
- Confirms `docs/research/2026-06-18-blood-values-workflow-value-evidence.md` workflow pain.
- Does **not** change ADR/spec boundaries; narrows **go-to-market language** and **PR C priority**.

---

## 10. Known unknowns

- Exact NL search volumes (requires owner Keyword Planner run).
- SnapLabs/FitReelix NL user counts and retention.
- Whether MedGemak export satisfies consult prep for target users.
- Paid ad longevity for NL brands (requires Ads Library manual capture).

---

## Source appendix (this run)

- https://levenswijs.health/
- https://www.vitasure.nl/producten/gezondheidscheck-compleet/
- https://fitreelix.com/nl/bloedwaarden-analyseren-app/
- https://home.mijngezondheid.net/nl
- https://apps.apple.com/nl/app/snaplabs-lab-results-tracker/id6756438055
- https://apps.apple.com/nl/app/bloedonderzoek-records/id6450719426
- https://www.facebook.com/ads/library (methodology)
- Repo: `docs/codex-prd.md`, `AGENTS.md`, dashboard PR A/B on `main`
