# V1 Spec: Persoonlijk Bloedwaarden-Dashboard

Date: 2026-06-16

## 1. Purpose

V1 builds a private Laravel application for manually tracking personal blood
tests and biomarker values.

The system helps the user:

- understand what was measured;
- see what changed over time;
- preserve personal context;
- prepare questions and summaries for a doctor;
- find original documents and structured values again;
- keep control over sensitive data.

The system must not diagnose, treat, prescribe, recommend supplements, or imply
medical authority.

## 2. Stack Decision

Default V1 stack assumption:

- Laravel application.
- Official Laravel Livewire starter kit.
- Blade/Livewire for authenticated personal workflows.
- Pest or PHPUnit for behavior tests once implementation starts.
- No Filament in the first slice unless a later task proves catalog management
  is the bottleneck.
- No Inertia/React/Vue in V1 unless a later spec revision proves Livewire cannot
  support the needed interaction.

Source:

- `docs/research/laravel-stack-decision.md`

Architecture rule:

- Livewire components may orchestrate UI workflows.
- Domain logic must live outside Livewire views/components in testable Laravel
  domain code.

## 3. V1 Boundary

In scope:

- login-protected personal dashboard;
- create, edit, view, and delete blood tests;
- optional document attachment per blood test;
- create and manage a small biomarker catalog;
- manually enter biomarker results;
- store units and reference ranges;
- compute `low`, `normal`, `high`, or `unknown` status;
- view a biomarker trend over time;
- compare two blood tests;
- pin important biomarkers;
- add context notes for sleep, food, training, supplements, medication, and
  symptoms/complaints;
- create a consult overview/export;
- create a reminder for next blood test;
- export and delete personal data.

Out of scope:

- diagnosis, treatment, or medical advice;
- AI interpretation;
- OCR or automatic PDF extraction;
- lab/provider/Apple Health integrations;
- supplement, diet, or training recommendations;
- secure share links;
- multi-profile/family/coach workflows;
- wearable data;
- billing/subscriptions;
- large medical reference database;
- automatic unit conversion;
- personal optimal ranges or health scoring.

## 4. Users And Permissions

V1 has one primary authenticated user type:

- personal user;
- owns all blood tests, documents, biomarker results, context notes, pins,
  reminders, and exports;
- can only see and change their own records.

Implementation implication:

- every user-owned record must be scoped by `user_id` or a direct parent record
  owned by the user;
- tests must prove one user cannot access another user's records once auth exists;
- no public pages should expose health data.

No roles in V1:

- no doctor role;
- no admin role;
- no coach role;
- no shared access role.

## 5. Conceptual Data Model

This is a conceptual model for the V1 spec. Exact migration names and Laravel
models belong in the implementation task plan.

### User

Purpose:

- authenticated owner of all personal data.

Important fields:

- name;
- email;
- password/auth fields from starter kit.

Rules:

- all personal health data is private to the owning user.

### Blood Test

Purpose:

- one lab test event on a specific date.

Important fields:

- `user_id`;
- test date;
- lab/source name;
- optional title;
- optional general notes.

Rules:

- test date is required;
- lab/source name is required or must default to `unknown`;
- deleting a blood test deletes or detaches its biomarker results, documents,
  and test-specific context according to the privacy deletion rule.

### Blood Test Document

Purpose:

- original supporting file such as a PDF, image, or screenshot.

Important fields:

- `blood_test_id`;
- original filename;
- stored private path;
- mime type;
- file size.

Rules:

- file is stored privately, not under a public web path;
- V1 does not parse the file;
- the document remains separate from structured biomarker values.

### Biomarker Category

Purpose:

- groups biomarkers into practical categories.

Examples:

- blood count;
- inflammation;
- liver;
- kidney;
- lipids;
- glucose/metabolism;
- vitamins/minerals;
- thyroid;
- hormones;
- other.

Rules:

- category is organizational only;
- category must not imply diagnosis.

### Biomarker

Purpose:

- catalog item for a measurable marker.

Important fields:

- name;
- optional short name;
- category;
- default unit;
- optional reference range minimum;
- optional reference range maximum;
- optional range note;
- active/inactive flag.

Rules:

- catalog starts small and manually maintained;
- biomarker names should be plain and searchable;
- range fields are defaults, not universal medical truth;
- unknown or lab-specific ranges should be allowed.

### Biomarker Result

Purpose:

- one measured value for one biomarker in one blood test.

Important fields:

- `blood_test_id`;
- `biomarker_id`;
- numeric value;
- unit;
- reference range minimum;
- reference range maximum;
- reference range unit;
- status;
- optional note.

Rules:

- one blood test should not contain duplicate results for the same biomarker
  unless a later spec introduces repeat measurements;
- the result stores the range used at the time of entry so later catalog edits
  do not silently rewrite history;
- status can be recalculated only when the user edits value/range/unit;
- status must be `unknown` when comparison is not trustworthy.

### Pinned Biomarker

Purpose:

- marks biomarkers the user wants to follow closely.

Important fields:

- `user_id`;
- `biomarker_id`;
- optional note;
- pinned timestamp.

Rules:

- pinning does not imply medical importance;
- pinned biomarkers should appear in dashboard and consult overview.

### Context Note

Purpose:

- records context around a date or blood test.

Important fields:

- `user_id`;
- optional `blood_test_id`;
- note date;
- category;
- body text.

Allowed categories:

- sleep;
- food;
- training;
- supplement;
- medication;
- complaint;
- stress;
- other.

Rules:

- context notes are user-written observations;
- notes must not be converted into medical advice;
- medication/supplement notes are descriptive, not prescriptive.

### Reminder

Purpose:

- remembers a planned follow-up blood test.

Important fields:

- `user_id`;
- due date;
- title;
- optional note;
- completed timestamp.

Rules:

- reminders are personal planning aids;
- no external email/SMS notification is required in the first slice unless later
  explicitly planned.

### Export / Consult Overview

Purpose:

- produces a user-readable overview for personal archive or doctor visit.

Important content:

- selected date range;
- selected blood tests;
- pinned biomarkers;
- abnormal or unknown values;
- trends for selected biomarkers;
- context notes;
- user questions.

Rules:

- export copy must say it is user-entered personal tracking data;
- export must not contain medical conclusions;
- first implementation may be printable HTML or CSV before PDF.

## 6. Status Calculation

Allowed statuses:

- `low`;
- `normal`;
- `high`;
- `unknown`.

Inputs:

- numeric value;
- value unit;
- reference range minimum;
- reference range maximum;
- reference range unit.

Rules:

- if value is missing or non-numeric: `unknown`;
- if unit is missing: `unknown`;
- if both reference minimum and maximum are missing: `unknown`;
- if reference range unit is present and does not match value unit: `unknown`;
- if only minimum exists:
  - value below minimum: `low`;
  - value equal or above minimum: `unknown`;
- if only maximum exists:
  - value above maximum: `high`;
  - value equal or below maximum: `unknown`;
- if both minimum and maximum exist:
  - value below minimum: `low`;
  - value above maximum: `high`;
  - value inside inclusive range: `normal`;
- if minimum is greater than maximum: `unknown`.

Why one-sided ranges remain partly unknown:

- Some lab values use threshold logic where "below max" may be acceptable, but
  the meaning depends on the biomarker and lab. V1 should avoid pretending a
  one-sided threshold is a universal normal range.

Test cases required later:

- value below range returns `low`;
- value inside range returns `normal`;
- value above range returns `high`;
- missing range returns `unknown`;
- mismatched unit returns `unknown`;
- reversed min/max returns `unknown`;
- one-sided minimum below threshold returns `low`;
- one-sided maximum above threshold returns `high`.

## 7. Core Workflows

### 7.1 Register Blood Test

User goal:

- record that a blood test happened.

Steps:

1. User opens blood tests.
2. User creates a new blood test.
3. User enters test date and lab/source.
4. User optionally adds a general note.
5. User optionally attaches the original document.
6. System stores the blood test under the authenticated user.

Acceptance criteria:

- blood test appears in the user's list;
- another user cannot access it;
- document, if present, is private and linked to the test.

### 7.2 Enter Biomarker Results

User goal:

- convert important lab values into structured personal data.

Steps:

1. User opens a blood test.
2. User chooses an existing biomarker or creates a new catalog item.
3. User enters value, unit, and reference range.
4. System calculates status.
5. User saves the result.

Acceptance criteria:

- result appears under the blood test;
- status is visible;
- value/range/unit can be edited;
- original document remains separate from the result.

### 7.3 View Biomarker Trend

User goal:

- see how one biomarker changes over multiple tests.

Steps:

1. User opens a biomarker detail page.
2. System lists historical results for that biomarker.
3. System shows a simple trend visualization or table-first chart fallback.
4. User can see date, value, unit, status, and source test.

Acceptance criteria:

- results are ordered by date;
- unavailable/mismatched units are not silently merged into a misleading line;
- pinned state is visible.

### 7.4 Compare Two Blood Tests

User goal:

- understand what changed between two test dates.

Steps:

1. User selects two blood tests.
2. System finds biomarkers present in either test.
3. System shows old value, new value, unit, status, and direction where
   comparable.

Comparison rules:

- same biomarker and same unit can show numeric delta;
- missing previous or current value shows `not measured`;
- different units show `not comparable`;
- status change should be visible even when numeric delta is not comparable.

Acceptance criteria:

- overlapping biomarkers show change;
- non-overlapping biomarkers are not hidden;
- no medical conclusion is generated.

### 7.5 Pin Biomarkers

User goal:

- keep important markers easy to revisit.

Steps:

1. User pins a biomarker from list, detail, or result view.
2. System shows pinned markers on dashboard and consult overview.
3. User can unpin later.

Acceptance criteria:

- pin is user-specific;
- pinning does not change status or medical meaning.

### 7.6 Add Context Notes

User goal:

- preserve personal context around measurements.

Steps:

1. User opens notes or a blood test.
2. User selects date/category.
3. User writes a note.
4. System links it to user and optionally to blood test.

Acceptance criteria:

- notes are visible near relevant blood tests and consult overview;
- notes can be edited/deleted;
- note category does not generate advice.

### 7.7 Prepare Consult Overview

User goal:

- create a compact view to discuss with a doctor.

Steps:

1. User selects date range or recent tests.
2. User chooses whether to include pinned markers, abnormal/unknown values,
   context notes, and personal questions.
3. System renders a printable/exportable overview.

Acceptance criteria:

- overview clearly says it is personal tracking data;
- overview contains no diagnosis or treatment copy;
- user can export or print the result.

### 7.8 Export And Delete Data

User goal:

- control sensitive personal data.

Export acceptance criteria:

- user can export structured data in a portable format;
- export includes blood tests, biomarker results, context notes, pins, and
  reminders;
- documents may be listed first, with file export planned separately if needed.

Delete acceptance criteria:

- user can delete individual blood tests;
- user can delete context notes;
- user can delete documents;
- user can delete all personal health data without deleting the auth account if
  implementation supports that distinction;
- delete behavior is explicit and tested.

## 8. Dashboard Shape

The first dashboard should prioritize action and review over decoration.

Primary dashboard blocks:

- recent blood tests;
- pinned biomarkers;
- values needing attention because status is `low`, `high`, or `unknown`;
- next reminder;
- quick actions: add blood test, add context note, compare tests.

Avoid in V1:

- health score;
- big motivational hero;
- medical insight cards;
- supplement recommendations;
- wearable-style wellness dashboard language.

## 9. Privacy And Security Requirements

V1 privacy baseline:

- every health record belongs to a user;
- all health routes require authentication;
- file uploads use private storage;
- direct file URLs must not expose documents;
- export/delete must be available to the owning user;
- no third-party processing of health documents;
- no AI, OCR, analytics, or external sharing by default.

Security tests required later:

- unauthenticated user cannot access blood test pages;
- user A cannot access user B's blood test;
- user A cannot download user B's document;
- user A cannot compare tests owned by user B;
- deleting a blood test removes or blocks access to attached private files.

## 10. Copy And Medical Boundary

Required product language:

- "persoonlijk overzicht";
- "opvolging";
- "context";
- "voorbereiding voor consult";
- "bespreek met je arts";
- "status op basis van ingevoerde referentierange".

Forbidden product language:

- "diagnose";
- "behandeling";
- "advies";
- "aanbevolen supplement";
- "gezondheidsscore";
- "optimaal voor jou";
- "risico voorspeld";
- "medisch oordeel".

Any later AI or interpretation feature requires a separate spec.

## 11. First Vertical Slice

The first implementation slice should prove the core loop:

1. authenticated user exists;
2. user creates two blood tests;
3. user creates a small biomarker catalog entry;
4. user enters biomarker results for both tests;
5. system calculates status;
6. user views biomarker history;
7. user compares two tests.

Explicitly not in first slice:

- document upload;
- consult export;
- reminders;
- context notes;
- full dashboard polish;
- Filament;
- OCR/AI.

Why:

- This slice proves the hardest product core: structured test data,
  biomarker identity, status logic, history, and comparison.

## 12. Validation Plan

Before implementation starts, `scripts/validate.sh` should still prove planning
integrity and absence of accidental Laravel scaffold.

Once Laravel is scaffolded, `scripts/validate.sh` must be replaced or expanded
to run the strongest practical checks, likely:

```bash
composer test
npm run build
```

Later V1 validation must prove:

- auth-protected access;
- blood test create/edit/delete;
- biomarker catalog create/edit;
- result entry;
- status calculation edge cases;
- trend/history ordering;
- two-test comparison;
- user data isolation;
- export/delete behavior when implemented.

## 13. Open Questions

These do not block the first task plan, but should be decided before each
related feature is implemented:

- Should the first implementation use SQLite for local development and later
  move to MySQL/Postgres, or choose the production database immediately?
- Should document upload be in V1 proper or V1.1 after the core data loop?
- Should consult overview start as printable HTML, CSV, or both?
- Should reminders be in-app only for V1 or include email later?
- Should biomarker ranges be stored globally on the catalog, per result, or both
  as proposed here?
- Which charting approach is smallest and easiest to replace?

## 14. Product-To-System Check

### 1. Wat probeer ik te bouwen?

Een private Laravel/Livewire-app waarmee een gebruiker eigen bloedtesten en
biomarkerwaarden structureert, opvolgt, vergelijkt en meeneembaar maakt voor
consultvoorbereiding.

### 2. Hoe moet dit systeem werken?

De gebruiker registreert bloedtesten, voert biomarkers manueel in, bewaart de
gebruikte range bij elke waarde, krijgt eenvoudige statuslabels, ziet trends en
vergelijkingen, en houdt context/export/delete onder eigen controle.

### 3. Welke componenten heb ik nodig?

Voor de eerste slice: auth, bloedtesten, biomarker-catalogus, biomarkerresultaten,
statuscalculator, biomarkerhistoriek en testvergelijking. Daarna pas documenten,
contextnotities, exports, reminders en dashboardverfijning.

### 4. Waar moet deze logica leven?

Statusberekening, vergelijkingsregels, privacychecks en exportregels horen in
testbare Laravel-domain code. Livewire beheert de UI-flow maar mag niet de
enige plek zijn waar medische grensregels of datakwaliteit bestaan.

### 5. Waarom breekt dit ding?

Het breekt als we te snel OCR/AI/advies toevoegen, als Livewire-componenten de
domainlaag worden, als referentieranges als universele waarheid worden
behandeld, of als de app meer health-platform dan bloedwaarden-opvolger wordt.

### 6. Verdict: bouwen

Bouwen is logisch na een task plan en planningbaseline commit. Nog niet
scaffolden voordat die twee bestaan.

