# Blood Values Dashboard Design System

## 1. Atmosphere & Identity

A quiet review workspace for private health records. The interface should feel restrained, factual, and low-pressure: users are handling sensitive lab data, so the product earns trust through clear states, recoverable actions, and visible boundaries rather than decorative polish. The signature is confirmation-first clarity: uploaded documents, extracted drafts, confirmed values, and downstream trends are always visually distinct.

## 2. Color

### Palette

| Role | Token | Light | Dark | Usage |
|------|-------|-------|------|-------|
| Surface/primary | Tailwind `white` | `#ffffff` | `neutral-900` | Page and main content surfaces |
| Surface/secondary | `neutral-50` | `#fafafa` | `neutral-900` | Upload background, subtle grouped areas |
| Surface/elevated | `white` | `#ffffff` | `neutral-800` | Dropzone card and raised panels |
| Text/primary | `neutral-900` | `#171717` | `white` | Headings and primary labels |
| Text/secondary | `neutral-600` | `#525252` | `neutral-400` | Help text, filenames, metadata |
| Border/default | `neutral-200` | `#e5e5e5` | `neutral-700` | Cards, sections, form panels |
| Border/subtle | `neutral-300` | `#d4d4d4` | `neutral-700` | Dashed empty/upload states |
| Action/primary | `neutral-900` | `#171717` | `white` | Primary upload and confirmation buttons |
| Action/link | `blue-700` | `#1d4ed8` | `blue-300` | Document links and progress active states |
| Status/progress | `blue-50` / `blue-800` | active state | `blue-950` / `blue-200` | Current extraction stage |
| Status/success | `green-50` / `green-800` | done state | `green-950` / `green-200` | Completed extraction stage |
| Status/review | `amber-50` / `amber-200` | draft review rows | `amber-950/40` / `amber-900` | Below-threshold extracted drafts |
| Status/error | `red-50` / `red-800` | errors/destructive | `red-950` / `red-200` | Failed extraction and destructive actions |

### Rules

- Use neutral surfaces as the default; status colors are reserved for real state.
- Amber means “needs review”, not warning or diagnosis.
- Blue means active process or navigational link.
- Green means process completed, not medical normality unless the row explicitly says status.
- Do not introduce decorative gradients or non-semantic accent colors.

## 3. Typography

### Scale

| Level | Size | Weight | Line Height | Tracking | Usage |
|-------|------|--------|-------------|----------|-------|
| H1/Page | Flux heading defaults | 600 | framework default | 0 | Page titles |
| H2/Section | Flux `size="lg"` | 600 | framework default | 0 | Section headings |
| Body | `text-base` | 400 | framework default | 0 | Standard content |
| Body/sm | `text-sm` | 400-500 | framework default | 0 | Form help, metadata, compact rows |
| Caption | `text-xs` | 500-600 | framework default | uppercase only for state labels | State labels and compact metadata |

### Font Stack

- Primary: Instrument Sans via `--font-sans`, then system sans.
- Mono: not part of the current UI system.

### Rules

- Keep compact dashboard surfaces at `text-sm` unless a Flux component defines the size.
- Do not use hero-scale type inside review, upload, or form panels.
- Labels must be explicit when adjacent numbers could be confused.

## 4. Spacing & Layout

### Base Unit

All spacing follows Tailwind’s 4px-based scale.

| Token | Tailwind | Value | Usage |
|-------|----------|-------|-------|
| Tight | `gap-2`, `p-2` | 8px | Inline controls |
| Compact | `gap-3`, `p-3` | 12px | Draft rows and compact metadata |
| Standard | `gap-4`, `p-4` | 16px | Cards and lists |
| Panel | `gap-5`, `p-5` | 20px | Form/review/source panels |
| Comfortable | `gap-8`, `p-8` | 32px | Upload hero-dropzone |

### Grid

- Main content max width: `max-w-5xl`.
- Breakpoints follow Tailwind defaults.
- Dense review data should use responsive grids: one column on mobile, explicit columns from `sm` upward.

### Rules

- Keep page sections un-nested and scan-friendly.
- Avoid putting cards inside larger decorative cards unless the inner element is an actionable repeated item.
- Numeric lab data needs visible labels or table-like alignment.

## 5. Components

### Upload Dropzone

- **Structure**: one dashed outer drop target, one elevated inner panel, hidden file input, real button trigger, selected filename, progress block.
- **States**: idle, drag-over, file selected, uploading, progress stages, validation error.
- **Accessibility**: file input remains present; trigger is a button; status copy stays near the upload control.
- **Motion**: only color/opacity transitions.

### Review Strip

- **Structure**: bordered section with extracted draft rows. Draft rows use amber review styling and explicit value/reference/status metadata.
- **States**: extraction failed, no drafts found, draft low-confidence, draft standard-confidence.
- **Accessibility**: controls are real buttons; labels distinguish measured value from reference range.
- **Motion**: none.

### Blood Results Overview

- **Structure**: one summary panel, one attention group, one compact normal-values panel. Normal values render as rows, not full cards.
- **Featured attention**: confirmed `low`/`high` values may use an amber-tinted featured card with a single getallenlijn and a one-sentence takeaway.
- **Compact rows**: confirmed `normal` and `unknown` rows use tabular values, a short takeaway, and a mini reference line when available.
- **Color**: keep one calm green good-area and one restrained amber attention treatment; avoid extra categories or bright blocks.
- **Accessibility**: status text must be visible in words, not only color; range lines are context, not the only status signal.
- **Motion**: none.

### Form Panel

- **Structure**: Flux heading/text, select, inputs, textarea, primary action.
- **States**: add manual value, review extracted draft, source document missing.
- **Accessibility**: labels are generated by Flux components and remain visible.

## 6. Motion & Interaction

| Type | Duration | Easing | Usage |
|------|----------|--------|-------|
| Micro | Tailwind `transition` default | framework default | Drag-over and hover feedback |

### Rules

- Animate only color, opacity, and transform if needed.
- Upload progress must change state text/classes immediately; do not rely on a single spinner.
- Destructive actions stay explicit and visually separated.

## 7. Depth & Surface

### Strategy

Borders-first with restrained surface fills.

| Type | Value | Usage |
|------|-------|-------|
| Default border | `border border-neutral-200 dark:border-neutral-700` | Page sections, forms, lists |
| Dashed border | `border border-dashed border-neutral-300 dark:border-neutral-700` | Upload and empty states |
| Subtle shadow | `shadow-xs` | Upload inner panel only |

### Rules

- Do not add heavy shadows or decorative depth.
- Use filled status surfaces only when a row or stage has a meaningful state.
- The review surface must prioritize scan accuracy over visual decoration.
