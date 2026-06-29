# CMA biomarker → Vitasure theme mapping (slice 4)

Date: 2026-06-29  
Status: organizational reference — not medical grouping  
Implementation: `config/biomarker_theme_mapping.php` + `AssignDefaultBiomarkerThemes`

## Purpose

Map common CMA / lab marker names to the nine Vitasure-inspired lifestyle themes so
**Per thema** views are useful after PDF intake. This is navigation only; it does not
change status, ranges, or medical meaning.

Unmatched markers stay uncategorized and appear under **Overig** in the thematic overview.

## How to apply

```bash
php artisan biomarkers:assign-themes --email=you@example.com
php artisan app:seed-blood-test-demo   # QA seeder also assigns themes
```

Rules:

- Only biomarkers **without** a category are updated (unless `--overwrite`).
- Categories are created idempotently via `DefaultBiomarkerCategoriesSeeder`.
- Intake auto-assign remains out of scope until a separate ADR (ADR-0013).

## Theme reference (Vitasure Premium)

| Thema | Typical markers (CMA / NL lab names) |
| --- | --- |
| **Hartgezondheid** | ApoB, LDL, HDL, totaal cholesterol, triglyceriden, Lp(a) |
| **Ontstekingen** | CRP / CRP hooggevoelig / hsCRP, WBC / leukocyten, neutrofielen, basofielen, eosinofielen, lymfocyten, monocyten |
| **Cognitie** | Vitamine B12, foliumzuur / folaat |
| **Uithoudingsvermogen** | Ferritine, hemoglobine, HCT, MCV/MCH/MCHC/RDW, RBC, ijzer, TIBC, transferrine, bloedplaatjes / MPV |
| **Slaap** | Magnesium, rode bloedcel magnesium, vitamine D |
| **Hormoonbalans** | TSH, estradiol, progesteron, cortisol, DHEAS, calcium, PTH |
| **Metabolisme** | Glucose (nuchter), insuline, HbA1c / hemoglobine A1c (IFCC/NGSP), ALT |
| **Herstel** | Albumine, AST, GGT, CK, kalium, natrium |
| **Fitness** | Testosteron, vrij testosteron, SHBG |
| **Overig** | Everything else (e.g. creatinine, eGFR, urinezuur until explicitly mapped) |

## CMA-specific aliases in config

These names appear in tests or typical CMA PDFs:

| Extracted / catalog name | Theme |
| --- | --- |
| `CRP hooggevoelig*` | Ontstekingen |
| `Hemoglobine A1c (IFCC)*` | Metabolisme |
| `Hemoglobine A1c (NGSP)*` | Metabolisme |
| `Ferritin` / `Ferritine` | Uithoudingsvermogen |
| `Vitamin D` / `Vitamine D` | Slaap |
| `Hemoglobine` | Uithoudingsvermogen |

## Explicitly unmapped (→ Overig until added)

Kidney-focused markers are common on CMA panels but not Vitasure themes:

- Creatinine, eGFR / MDRD, cystatine C, urinezuur

Add these only after product decision (extend mapping or keep Overig).

## Verification

1. `php artisan test tests/Unit/Biomarkers/ResolveBiomarkerThemeCategoryTest.php`
2. `php artisan test tests/Feature/Biomarkers/AssignDefaultBiomarkerThemesTest.php`
3. Assign themes → reload dashboard → **Per thema** shows Ontstekingen / Uithoudingsvermogen blocks
4. Consult handoff → **Per thema** with same markers

## Related

- ADR-0013 thematic descriptions boundary
- PR #39 thematic biomarker overview
- `config/biomarker_reference_descriptions.php` (neutral copy, inflammation starter set)
