# Competitor Analysis: Personal Blood Values Dashboard

Date: 2026-06-18

Research method:

- Firecrawl scrape of official product pages, privacy pages, and relevant
  open-source repositories.
- Firecrawl search for practitioner signals from Reddit and Hacker News.
- Evidence is treated as product-direction input, not as medical authority.

Decision intent:

- Decide what the Laravel blood values dashboard should copy, reject, or defer
  after looking at adjacent products.
- Protect the product boundary: personal ordering and follow-up, not diagnosis
  or medical advice.
- Keep V1 local/private unless a later ADR deliberately changes that.

## Evaluation Axes

- PDF/document intake.
- Structured biomarker extraction and review.
- Manual correction.
- Trends and comparison.
- Context around the blood test.
- Consult preparation.
- Export and deletion.
- Privacy model.
- Apple Health or wearable integration.
- Risk of drifting into diagnosis, treatment, supplement advice, or health
  optimization claims.

## Competitor Clusters

### 1. Lab Trackers And Blood Test Wallets

Examples:

- Hemeify: `https://hemeify.com/`
- TrackMyLabs: `https://trackmylabs.app/`
- BloodId: `https://bloodid.app/`
- Biotracker: `https://www.biotracker.me/`

Observed pattern:

- Upload PDF, photo, or report from many labs.
- Extract biomarkers into a unified record.
- Show trend charts.
- Provide ranges, status, and plain-language explanations.
- Offer export or sharing for doctor conversations.

Useful for this project:

- PDF-first intake is a validated pattern.
- Biomarker trend views are expected.
- Manual review/correction matters because lab documents vary.
- Export for consult preparation is not polish; it is a core workflow.

Risks to avoid:

- Plain-English explanations can easily become medical interpretation.
- "What needs attention" language can imply clinical judgement.
- "Optimal range" language can create a hidden diagnosis/optimization product.

### 2. Broad Health Timeline Products

Examples:

- Guava Health: `https://guavahealth.com/`
- MedicalHistory.app: `https://medicalhistory.app/`

Observed pattern:

- Store lab results, documents, symptoms, medications, doctor notes, and health
  events in one timeline.
- Help users prepare for visits and keep scattered medical information together.
- Connect or import data from multiple sources.

Useful for this project:

- A consult-prep view is strategically stronger than a generic dashboard.
- Context notes around a blood test are valuable when revisiting results later.
- Document archive plus structured values is a stronger model than values alone.

Risks to avoid:

- Becoming a broad medical-record vault too early.
- Adding roles, care-team sharing, chronic-condition workflows, or family
  management before the personal blood-test workflow is excellent.

### 3. Optimization And Action-Plan Platforms

Examples:

- InsideTracker: `https://info.insidetracker.com/blood-results-upload`
- Seren: `https://myseren.app/`

Observed pattern:

- Upload blood results.
- Combine blood data with profile/context and sometimes wearable data.
- Provide personalized analysis, optimal zones, and action plans.
- Market toward improvement, performance, and lifestyle optimization.

Useful for this project:

- Biomarker categories help users orient themselves.
- Context profile and lifestyle data can make later review more meaningful.
- Wearable data has value as context around test dates.

Risks to avoid:

- Action plans cross the V1 product boundary.
- Nutrition, supplement, fitness, or treatment recommendations are out of scope.
- "Improve your health" framing turns the product into a medical/optimization
  system rather than a private personal record and follow-up system.

### 4. Open-Source And Local-First Health Tools

Examples:

- getbased: `https://getbased.health/`
- OpenBio: `https://openbio.health/`
- OpenMed: `https://github.com/ianrowan/OpenMed`
- Open Wearables: `https://github.com/the-momentum/open-wearables`
- Vitals: `https://github.com/jordangarrison/vitals`

Observed pattern:

- Privacy, ownership, local processing, and open-source transparency are major
  differentiators.
- Apple Health export/import is a practical first bridge for wearable data.
- Self-hosted or local-first systems appeal to users who distrust opaque health
  platforms.

Useful for this project:

- Local/private V1 is a real product position, not only a development shortcut.
- Apple Health should start as import/export context, not live sync.
- Wearable infrastructure is a separate subsystem and should not be mixed into
  biomarker results.

Risks to avoid:

- Open-source tools often become too broad or too technical.
- AI assistants and cross-source interpretation can blur the no-advice boundary.
- Wearable integrations can consume architecture before the blood-test workflow
  is stable.

### 5. Niche Context Trackers

Example:

- Phaze Lab Tracker: `https://phaze.fit/lab-tracker`

Observed pattern:

- Lab tracking is positioned around a specific personal context, such as GLP-1
  use, weight, meals, or metabolic monitoring.
- The product keeps labs near lifestyle context.

Useful for this project:

- Context notes are not optional decoration. They answer: what was happening
  around this blood test?

Risks to avoid:

- A niche-specific tracker can imply treatment monitoring or protocol advice.
- V1 should remain general personal follow-up, not a disease, drug, or protocol
  tracker.

## Practitioner Signals

Firecrawl search returned relevant practitioner discussions from Reddit and
Hacker News.

Observed needs:

- Users ask for a simple way to input or upload blood test results and plot
  historic values.
- Users dislike scattered PDF lab reports across portals, email, and folders.
- Users want trends without maintaining spreadsheets manually.
- Users compare tools partly on privacy, export, and data ownership.
- Users are interested in Apple Health and wearable data, but the ecosystem is
  fragmented.
- Some users are skeptical of expensive optimization platforms when the core
  value is tracking and remembering results.

Decision utility:

- This supports the V1 focus on PDF intake, confirmation, trends, compare,
  context, consult overview, export, and delete.
- It does not justify diagnosis, supplement recommendations, or full wearable
  sync in V1.

## Feature Matrix

| Capability | Market signal | V1 decision |
| --- | --- | --- |
| Lab PDF upload | Common and expected | In scope |
| Automatic extraction | Common, but trust-sensitive | Draft only, user confirms |
| Manual entry/correction | Necessary fallback | In scope |
| Biomarker trends | Core value | In scope |
| Compare two tests | Strong personal utility | In scope |
| Biomarker categories | Common orientation aid | In scope, non-diagnostic |
| Plain-English explanations | Common but risky | Avoid medical interpretation |
| Optimal ranges | Common in optimization tools | Out of scope |
| Action plan | InsideTracker/Seren pattern | Out of scope |
| Supplement/diet/training advice | Biohacking pattern | Out of scope |
| Context notes | Strong adjacent pattern | In scope |
| Consult preparation | Strong health timeline pattern | In scope |
| Export/delete | Privacy expectation | In scope |
| Apple Health/wearables | Valuable but bigger subsystem | V2 candidate |
| Provider/lab connection | Useful but complex | Out of scope for V1 |
| Local/private operation | Differentiator | Keep as V1 default |

## Recommended Product Position

The strongest position is:

> A private blood values archive that turns lab PDFs into user-confirmed
> follow-up data for trends, context, comparison, consult preparation, export,
> and deletion.

The product should not position itself as:

- an AI doctor;
- a supplement coach;
- a lifestyle optimizer;
- a full medical record system;
- a wearable analytics platform.

## What To Copy

- PDF-first intake.
- Review and confirm before values become trusted tracking data.
- Biomarker trend views.
- Test-to-test comparison.
- Context notes near the blood test date.
- Pinned important biomarkers.
- Consult overview/export.
- Data export and deletion.
- Privacy-first language backed by architecture.

## What To Reject

- Diagnosis or treatment suggestions.
- AI interpretation as a source of truth.
- "Optimal range" as universal truth.
- Health scores.
- Supplement, diet, or training recommendations.
- Public share links in V1.
- Live wearable sync before import/export is understood.

## V2 Candidates

### Apple Health Context Import

Use Apple Health export ZIP/XML as the first wearable bridge.

Why:

- It respects local/private operation.
- It avoids OAuth/provider complexity.
- It lets the user select context metrics around blood-test dates.
- It keeps wearable data separate from biomarker results.

Required before implementation:

- ADR for wearable context import.
- Threat model for Health export files.
- Data model separating health context samples from biomarker results.
- Clear UI language that correlation is not causation.

### Optional AI Extraction

If added later, AI extraction must produce drafts only.

Rules:

- Never treat extracted values as confirmed.
- Preserve original document.
- Show extraction uncertainty.
- Allow correction.
- Keep medical interpretation out of scope.

## Failure Modes

- The app becomes a dashboard full of numbers but does not answer "what changed?"
- The app stores PDF documents but fails to make values findable.
- The app extracts values without a review layer.
- The app copies optimization-platform language and accidentally gives advice.
- Wearable integration arrives before the blood-test core is stable.
- Privacy is treated as copywriting instead of architecture.

## Product Implications For Laravel

Laravel remains logical because the valuable system is workflow-heavy:

- authenticated ownership;
- private document storage;
- PDF intake;
- draft extraction/review state;
- confirmed biomarker results;
- catalog/range rules;
- trends and compare;
- context notes;
- reminders;
- export/delete;
- later background jobs and imports.

This is more than a static website. It is a private administrative product
motor around sensitive personal data.

## Source Appendix

Official/product sources scraped with Firecrawl:

- `https://hemeify.com/`
- `https://hemeify.com/privacy`
- `https://trackmylabs.app/`
- `https://bloodid.app/`
- `https://guavahealth.com/`
- `https://guavahealth.com/privacy`
- `https://getbased.health/`
- `https://openbio.health/`
- `https://phaze.fit/lab-tracker`
- `https://medicalhistory.app/`
- `https://myseren.app/`
- `https://www.biotracker.me/`
- `https://info.insidetracker.com/blood-results-upload`
- `https://github.com/ianrowan/OpenMed`
- `https://github.com/the-momentum/open-wearables`
- `https://github.com/jordangarrison/vitals`

Practitioner search surfaces returned by Firecrawl:

- `https://www.reddit.com/r/QuantifiedSelf/comments/13oaz96/question_is_there_an_website_or_app_that_lets_you/`
- `https://www.reddit.com/r/QuantifiedSelf/comments/1iivrmn/i_built_an_app_that_turns_messy_lab_reports_into/`
- `https://www.reddit.com/r/QuantifiedSelf/comments/1laoj7i/new_mac_app_for_tracking_blood_test_results_over/`
- `https://news.ycombinator.com/item?id=36025977`
- `https://news.ycombinator.com/item?id=36255910`
- `https://www.reddit.com/r/AdvancedRunning/comments/155qqzw/experience_with_insidetracker/`
- `https://www.reddit.com/r/PeterAttia/comments/1br6o8d/insidetracker_vs_marek_health/`
- `https://www.reddit.com/r/QuantifiedSelf/comments/1i7uu6c/i_open_sourced_my_project_to_analyze_your_years/`
- `https://www.reddit.com/r/QuantifiedSelf/comments/1ic1lrl/how_do_you_keep_track_of_all_your_health_data/`

## Known Unknowns

- Firecrawl captured public pages, not paid product flows.
- Product marketing may overstate extraction quality.
- Reddit and Hacker News discussions are biased toward technical users.
- App Store reviews, pricing history, and real retention data were not reviewed.
- Privacy policies describe intent and process, but do not prove security
  implementation quality.
