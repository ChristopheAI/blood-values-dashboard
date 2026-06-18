# Blood Values Workflow Value Evidence

Date: 2026-06-18

## Decision Intent

Answer the practical project question:

- which workflow costs money, time, people, or chaos;
- what proves value for this project;
- whether the first implementation slice should stay PDF-first and
  review-confirmed, or broaden into AI interpretation, OCR automation,
  wearables, provider integrations, or medical advice.

This run uses public evidence only. No private lab PDFs, biomarker values,
Apple Health exports, symptoms, medication notes, account data, or personal
health data were processed.

Tooling note:

- Exa was used for search and page fetches.
- Firecrawl was requested as an allowed public research tool, but no callable
  Firecrawl tool was available in this Codex session. It was not used or
  implied.

## Search Lenses

Evidence was gathered through four lenses:

- real user complaints and workarounds;
- real competitor positioning and pricing;
- real market activity around blood testing and longitudinal health data;
- open-source or local-first activity that shows what builders try when they
  care about control and privacy.

Representative Exa queries:

- `people complain lab results are scattered across portals PDFs screenshots hard to track trends compare blood test results Reddit forum`
- `blood test lab results tracking app competitors upload PDF trends export doctor pricing Healthmatters Hemeify Guava InsideTracker TrackMyLabs Soka Biotracker market`
- `funding market activity personal health records lab results tracking app Guava Health InsideTracker Function Health blood test tracking funding users launch acquisition`
- `real users ask how to organize track blood test results over time spreadsheet reddit quantified self labs PDF portals units privacy manual entry`

## Evidence Grade

### A-grade: Real User Pain

Users repeatedly describe the same workflow failure: bloodwork exists, but it is
not reliably usable over time.

- A Quantified Self user could not find a satisfying blood-data system and went
  back to spreadsheets; replies describe failures caused by different labs,
  inconsistent standards, changing interpretations, and EMR presentation quirks
  ([Quantified Self Forum](https://forum.quantifiedself.com/t/spreadsheet-lovers-i-need-your-help-to-quantify-my-blood/9418)).
- A Mayo Clinic Connect user with CKD asked for tracker/software help; a reply
  describes pulling labs from a cancer center, dialysis center, and Quest into
  Excel while avoiding reference ranges because they vary by source
  ([Mayo Clinic Connect](https://connect.mayoclinic.org/discussion/does-anyone-use-a-blood-test-tracker-for-excel-or-google-sheets/)).
- An Ask MetaFilter user had eight years of repeated blood tests in Excel and
  wanted cleaner side-by-side comparison by date for healthcare professionals
  ([Ask MetaFilter](https://ask.metafilter.com/261401/Logging-tracking-blood-test-results-just-beyond-Excel)).
- A Hacker News thread asks for a web app beyond Excel/Sheets for manual blood
  test entry, historic graphs, reference comparison, and unit conversion
  ([Hacker News](https://news.ycombinator.com/item?id=36025977)).
- A New Zapiens community post describes cross-country and multi-lab tracking
  where OCR/import tools fail on reading, mapping, units, terminology, and
  formats; the reply mentions human QA as still relevant
  ([New Zapiens](https://newzapiens.com/community/general/best-tools-for-consolidating-bloodwork-across-labs-countries-formats-with-ocr-human-qa)).

Inference:

The costly workflow is not merely "view blood values." It is retrieval,
normalization, confirmation, longitudinal comparison, and appointment
preparation across scattered sources.

### A-grade: Competitor Wedge

Direct competitors converge on the same promise: collect lab documents from
any source, turn them into structured biomarker history, show trends, and make
the data exportable or shareable.

- LabsVault positions the problem as patient portals, abandoned spreadsheets,
  paid testing dashboards, unit differences, and lost PDFs; it sells PDF/photo
  import, unit conversion, tracking, export, and delete control
  ([LabsVault](https://labsvault.com/)).
- Healthmatters sells centralized upload from PDFs, scans, and images,
  timelines, graphs, tables, comparison, export, sharing, manual entry, and a
  paid data-entry service
  ([Healthmatters](https://healthmatters.io/)).
- TrackMyLabs promises upload/connect labs, automatic marker extraction, trend
  charts, reference ranges, doctor sharing, export/delete options, and
  privacy/security controls
  ([TrackMyLabs](https://trackmylabs.app/)).
- Hemeify sells PDF/JPG/PNG upload, extraction, low/normal/high/optimal
  status, trends, PDF/CSV export, doctor sharing, and supplement insights for
  a low monthly subscription
  ([Hemeify](https://hemeify.com/)).
- Biotracker focuses on PDF/screenshot import, automatic extraction, local
  storage of extracted medical data, charts, trends, and generated insights
  ([Biotracker](https://www.biotracker.me/)).
- Soka's 2026 comparison treats PDF/photo/manual import, biomarker coverage,
  trend charts, unit conversion, privacy, and price as the buying axes; it
  explicitly calls Apple Health provider-dependent and weak for manual/PDF
  tracking
  ([Soka app comparison](https://soka.health/blog/best-app-to-track-blood-test-results)).

Inference:

The market validates PDF/document intake, biomarker history, trends, unit/range
handling, export, privacy, and consult sharing as expected capabilities. It
does not prove that V1 should copy AI explanations, supplement insights,
optimal ranges, secure share links, or broad health coaching.

### B-grade: Market Activity

Broader health-data platforms show real money and activity around longitudinal
lab data, but they are adjacent to this project rather than direct V1 models.

- Function Health raised a $298M Series B at a reported $2.5B valuation and
  frames the opportunity as consolidating health data, lab tests, notes, scans,
  and AI-powered guidance
  ([TechCrunch](https://techcrunch.com/2025/11/19/function-health-closes-298m-series-b-at-a-2-5b-valuation-launches-medical-intelligence/)).
- Fitt Insider reported Function's 2024 Series A, 50K paying customers, 200K
  waitlist, and 100-test diagnostics panel
  ([Fitt Insider, 2024](https://insider.fitt.co/function-adds-53m-for-preventative-lab-tests/)).
- Fitt Insider later reported Function's Getlabs acquisition and described
  bloodwork as becoming preventative health infrastructure
  ([Fitt Insider, 2026](https://insider.fitt.co/function-acquires-getlabs-brings-bloodwork-home/)).
- Guava argues that records are scattered across health systems, apps, paper,
  portals, and PDFs, and that visit-prep summaries can reduce appointment
  friction
  ([Guava](https://guavahealth.com/article/why-track-your-health)).

Inference:

There is real commercial energy around turning health records and lab data into
longitudinal systems. The direct lesson for this repo is not "build an AI
health platform." The lesson is that structured, reusable health data has
market pull when it reduces fragmentation and appointment friction.

### B-grade: Builder Activity

Open-source and local-first projects show that builders with the same itch
often create pipelines around PDFs, local storage, time-series views, and agent
readability.

- `al-matty/soma` is a local-first pipeline for lab PDFs into DuckDB/dbt and
  markdown summaries; it explicitly discusses privacy risks and opt-in cloud
  model usage
  ([soma](https://github.com/al-matty/soma)).
- `zlnsk/lab` combines PDF upload, biomarker trends, wearables, AI insights,
  Supabase RLS, and recommendations; it is useful as a scope-warning because it
  quickly expands into wearables, AI interpretation, optimal ranges, and
  supplement/lifestyle advice
  ([zlnsk/lab](https://github.com/zlnsk/lab)).

Inference:

Local/private architecture is a meaningful position for sensitive lab data.
The common builder failure mode is combining too many subsystems before the
core lab-document-to-confirmed-values workflow is trustworthy.

## What Costs Money, Time, People, Or Chaos?

The expensive workflow in this project is:

```text
find prior lab documents
-> enter or extract values
-> normalize units and names
-> preserve source documents
-> confirm what is trustworthy
-> compare repeated markers over time
-> prepare a doctor-facing summary
```

Specific cost surfaces:

- Time: hunting portals, inboxes, files, and old PDFs; re-entering data into
  spreadsheets; finding previous values before a consult.
- Money: paying for bundled testing platforms when the user already has lab
  results; retesting or wasting appointment time because prior results are not
  organized.
- People: doctors, practitioners, or the user manually reconstructing history;
  some competitors even sell human data-entry services.
- Chaos: inconsistent lab names, units, ranges, countries, report formats, and
  partial portal histories.

## What Proves Value For This Project?

The value proof for this Laravel project is narrow and testable:

- two source lab documents can be uploaded and privately stored;
- each document creates a blood test record without trusting the original
  filename as a storage path;
- the user can review, correct, or manually add overlapping biomarker values;
- only confirmed values appear in trends and comparison;
- status can be `low`, `normal`, `high`, or `unknown`;
- `unknown` is used when unit, range, or comparison basis is not trustworthy;
- a user can find the original PDF again from the blood test;
- the user can compare two test dates on shared biomarkers;
- the user can export or prepare a consult summary without medical claims;
- one user cannot access another user's documents or health data.

That proof is stronger than a broad dashboard demo because it removes the real
workflow friction the sources expose.

## Decision

Keep the first implementation slice exactly where the current project has it:

```text
auth
-> private PDF-first intake
-> review/confirmation of structured values
-> small biomarker catalog
-> status calculation with unknown fallback
-> biomarker history
-> compare two blood tests
-> later consult/export proof
```

Do not broaden V1 into:

- AI health interpretation;
- automatic OCR without review;
- supplement, diet, training, or treatment advice;
- optimal-range scoring;
- provider/lab connections;
- Apple Health or wearable sync;
- broad medical-record vault features;
- secure share links for outside users.

## Consequences For Planning

- `docs/testing/pdf-first-intake-test-conversion.md` is the right next
  implementation contract.
- The first test dataset should contain two lab documents with overlapping
  biomarkers, at least one unit/range mismatch, and one marker that must become
  `unknown`.
- Any OCR or AI extraction later must produce draft values only, never trusted
  health data.
- A competitor saying "AI extraction" or "plain-English explanation" is not a
  sufficient reason to copy it; those features increase privacy and medical
  boundary risk.
- Privacy, export, delete, generated storage names, and owner authorization are
  not polish. They are part of the market promise and the safety boundary.

## Known Unknowns

- The public sources prove the general workflow pain, not Christophe's exact
  private lab-data workflow.
- Belgium/Flanders lab portals, formats, units, and language differences still
  need focused research if localization becomes a product requirement.
- Willingness to pay for a private single-user self-hosted version is not proven
  by competitor pricing alone.
- OCR accuracy across Christophe's actual lab PDFs is unknown and should not be
  assumed before private, explicit, local-only testing.

