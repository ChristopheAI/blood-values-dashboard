# Compacte PDF-upload-dropzone Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Maak de bestaande `/blood-tests`-dropzone compacter en rustiger zonder uploadgedrag, privacygrenzen, trustlogica of voortgang te wijzigen.

**Architecture:** Dit is een presentatie-only wijziging in één Blade-partial. De bestaande Alpine-uploadstate, NDJSON-stream, controllerflow en downstreamcontracten blijven ongewijzigd; een gerichte Pest-presentatietest beschrijft de nieuwe compacte hiërarchie en bestaande browser-QA bewijst het echte uploadpad.

**Tech Stack:** Laravel 13, Blade, Alpine.js, Tailwind CSS v4, Flux UI, Pest, Codex in-app Browser.

---

## Bestandsstructuur

- `resources/views/blood-tests/_upload-dropzone.blade.php` — behoudt de bestaande upload- en voortgangsinteractie en rendert de compactere single-surface dropzone.
- `tests/Feature/BloodTests/BloodTestPdfUploadTest.php` — bewaakt de compacte hiërarchie, expliciete privacycopy en het bestaande uploadcontract.
- `docs/superpowers/specs/2026-07-21-compact-pdf-upload-dropzone-design.md` — reeds goedgekeurde ontwerpgrens; niet wijzigen tijdens implementatie tenzij live bewijs een echte tegenspraak aantoont.

Geen controller, route, model, migratie, JavaScriptmodule, packagebestand of andere view hoort bij deze slice.

### Task 0: Isoleer de implementatie

**Files:**
- Reference: `docs/superpowers/specs/2026-07-21-compact-pdf-upload-dropzone-design.md`

- [ ] **Step 1: Maak een aparte worktree vanaf het goedgekeurde plancommit**

Gebruik eerst `superpowers:using-git-worktrees`. Maak daarna de featureworktree:

```bash
git worktree add \
  -b codex/compact-pdf-upload-dropzone \
  /Users/christophe/.config/superpowers/worktrees/laravel-1st-project/compact-pdf-upload-dropzone \
  HEAD
```

Expected: een nieuwe worktree op branch `codex/compact-pdf-upload-dropzone`, zonder de vele niet-gerelateerde wijzigingen uit de huidige checkout.

- [ ] **Step 2: Installeer alleen ontbrekende dependencies**

Run vanuit de nieuwe worktree:

```bash
test -d vendor || composer install
test -d node_modules || npm install
```

Expected: bestaande dependencies worden hergebruikt wanneer aanwezig; anders eindigen beide installaties zonder fout.

- [ ] **Step 3: Controleer de geïsoleerde basis**

```bash
git status --short --branch
php artisan test tests/Feature/BloodTests/BloodTestPdfUploadTest.php --filter='renders an upload-first empty intake dropzone without metadata or account fields'
```

Expected: de worktree is schoon en de bestaande gerichte test is groen vóór de nieuwe rode test wordt geschreven.

### Task 1: Leg de compacte presentatie vast met een falende test

**Files:**
- Modify: `tests/Feature/BloodTests/BloodTestPdfUploadTest.php:55-86`
- Modify: `tests/Feature/BloodTests/BloodTestPdfUploadTest.php:116-134`

- [ ] **Step 1: Vervang de oude trust-notice-asserties door het nieuwe presentatiecontract**

Vervang in `renders an upload-first empty intake dropzone without metadata or account fields` de response- en HTML-asserties door:

```php
$response
    ->assertOk()
    ->assertSee('Sleep je lab-PDF hierheen')
    ->assertSee('of kies hieronder een bestand')
    ->assertSee('Alleen PDF')
    ->assertSee('Geen externe verwerking')
    ->assertSee('Eerst bevestigen')
    ->assertSee('data-test="upload-mark"', false)
    ->assertSee('data-test="upload-trust-signals"', false)
    ->assertSee('data-test="lab-pdf-dropzone"', false)
    ->assertSee('data-test="intake-progress"', false)
    ->assertDontSee('data-test="upload-trust-notice"', false)
    ->assertDontSee('data-test="upload-pdf-button"', false)
    ->assertDontSee('data-test="blood-test-date-input"', false)
    ->assertDontSee('data-test="blood-test-lab-input"', false)
    ->assertDontSee('data-test="blood-test-title-input"', false)
    ->assertDontSee('name="email"', false)
    ->assertDontSee('name="account"', false);

expect($html)
    ->toContain('data-density="compact"')
    ->toContain('data-test="intake-progress-stage-extract"')
    ->toContain(':data-state="progressStages.extract"')
    ->toContain('data-test="intake-progress-stage-values"')
    ->toContain(':data-state="progressStages.values"')
    ->toContain('data-test="intake-progress-stage-status"')
    ->toContain(':data-state="progressStages.status"')
    ->toContain('data-test="intake-progress-stage-trend"')
    ->toContain(':data-state="progressStages.trend"')
    ->not->toContain('min-h-[22rem]')
    ->not->toContain('data-state="pending"');
```

Vervang in `renders the dropzone choose control as a button and keeps the input pdf only` alleen de oude trust-selector en voeg de compacte contracten toe:

```php
expect($html)
    ->toContain('data-test="lab-pdf-dropzone"')
    ->toContain('data-density="compact"')
    ->toContain('data-test="upload-trust-signals"')
    ->toContain('fetch(form.action')
    ->toContain("'Accept': 'application/x-ndjson'")
    ->toContain("'X-Intake-Stream': '1'")
    ->toContain('progressStages[payload.stage] = payload.state')
    ->toContain('window.location.href = payload.redirect')
    ->toContain('@drop.prevent="dragging = false; setFiles($event.dataTransfer.files); if (fileName) $nextTick(() => $el.closest(\'form\').requestSubmit())"')
    ->toContain('@change="fileName = $event.target.files[0]?.name ?? \'\'; if (fileName) $nextTick(() => $el.form.requestSubmit())"')
    ->toContain('data-test="selected-file-name"')
    ->not->toContain('data-test="upload-trust-notice"')
    ->not->toContain('min-h-[22rem]')
    ->toMatch('/<button\s+[^>]*type="button"[^>]*data-test="choose-pdf-button"/s')
    ->toMatch('/<input\s+[^>]*name="document"[^>]*accept="application\/pdf"[^>]*data-test="lab-pdf-input"/s');
```

- [ ] **Step 2: Run de gerichte test en bewijs de rode fase**

```bash
php artisan test tests/Feature/BloodTests/BloodTestPdfUploadTest.php --filter='renders an upload-first empty intake dropzone without metadata or account fields'
```

Expected: FAIL omdat `of kies hieronder een bestand`, `data-test="upload-mark"`, `data-test="upload-trust-signals"` en `data-density="compact"` nog niet bestaan.

- [ ] **Step 3: Run ook de knopcontracttest en bewijs dat dezelfde ontbrekende presentatie faalt**

```bash
php artisan test tests/Feature/BloodTests/BloodTestPdfUploadTest.php --filter='renders the dropzone choose control as a button and keeps the input pdf only'
```

Expected: FAIL op het ontbrekende compacte trust-signaalcontract, niet op route-, database- of bootfouten.

### Task 2: Bouw de minimale single-surface dropzone

**Files:**
- Modify: `resources/views/blood-tests/_upload-dropzone.blade.php:12-57`
- Test: `tests/Feature/BloodTests/BloodTestPdfUploadTest.php`

- [ ] **Step 1: Vervang uitsluitend het zichtbare dropzone-section**

Vervang regels 12-57 door onderstaande Blade. Laat het progress-section en het volledige `labPdfIntake()`-script ongewijzigd.

```blade
    <section
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="dragging = false; setFiles($event.dataTransfer.files); if (fileName) $nextTick(() => $el.closest('form').requestSubmit())"
        :class="dragging
            ? 'border-blue-400 bg-blue-50 dark:border-blue-500 dark:bg-neutral-800/80'
            : 'border-neutral-300 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900'"
        class="flex min-h-64 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-6 text-center transition sm:p-8"
        data-density="compact"
        data-test="lab-pdf-dropzone"
    >
        <div class="flex w-full max-w-xl flex-col items-center gap-4">
            <span
                aria-hidden="true"
                class="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-900"
                data-test="upload-mark"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4.25A1.75 1.75 0 0 0 6.75 20h10.5A1.75 1.75 0 0 0 19 18.25V14" />
                </svg>
            </span>

            <div class="space-y-1">
                <p class="text-xl font-semibold text-neutral-900 dark:text-white sm:text-2xl">{{ __('Sleep je lab-PDF hierheen') }}</p>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ __('of kies hieronder een bestand') }}</p>
            </div>

            <input
                x-ref="input"
                id="document"
                name="document"
                type="file"
                accept="application/pdf"
                required
                class="sr-only"
                data-test="lab-pdf-input"
                @change="fileName = $event.target.files[0]?.name ?? ''; if (fileName) $nextTick(() => $el.form.requestSubmit())"
            />

            <button
                type="button"
                x-ref="chooseButton"
                :disabled="isUploading"
                @click="$refs.input.click()"
                class="inline-flex h-10 cursor-pointer items-center rounded-lg bg-neutral-900 px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-900"
                data-test="choose-pdf-button"
            >{{ __('PDF kiezen') }}</button>

            <span x-show="fileName" x-text="fileName" class="text-sm text-neutral-600 dark:text-neutral-400" data-test="selected-file-name"></span>

            <ul class="flex flex-wrap justify-center gap-2 text-xs text-neutral-700 dark:text-neutral-300" data-test="upload-trust-signals">
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Alleen PDF') }}</li>
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Geen externe verwerking') }}</li>
                <li class="rounded-full border border-neutral-200 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800">{{ __('Eerst bevestigen') }}</li>
            </ul>
        </div>

        @error('document')
            <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror
    </section>
```

- [ ] **Step 2: Run beide rode tests en bewijs de groene fase**

```bash
php artisan test tests/Feature/BloodTests/BloodTestPdfUploadTest.php --filter='renders an upload-first empty intake dropzone without metadata or account fields|renders the dropzone choose control as a button and keeps the input pdf only'
```

Expected: beide tests PASS; de output bevat geen warnings of errors.

- [ ] **Step 3: Run het volledige upload-featurebestand**

```bash
php artisan test tests/Feature/BloodTests/BloodTestPdfUploadTest.php
```

Expected: alle tests in het bestand PASS, inclusief private opslag, streamingprogress, foutpad, bestandsnaamsanitisatie en cleanup.

- [ ] **Step 4: Inspecteer de diff op scope en whitespace**

```bash
git diff --check
git diff -- resources/views/blood-tests/_upload-dropzone.blade.php tests/Feature/BloodTests/BloodTestPdfUploadTest.php
git status --short
```

Expected: alleen de dropzone-partial en de upload-featuretest zijn gewijzigd; geen progresslogica, route, controller of dependencybestand is geraakt.

- [ ] **Step 5: Commit de groene slice**

```bash
git add -- resources/views/blood-tests/_upload-dropzone.blade.php tests/Feature/BloodTests/BloodTestPdfUploadTest.php
git commit -m "fix: compact pdf upload dropzone"
```

Expected: één atomische commit met exact twee bestanden.

### Task 3: Volledige validatie en echte browser-QA

**Files:**
- Verify: `resources/views/blood-tests/_upload-dropzone.blade.php`
- Verify: `tests/Feature/BloodTests/BloodTestPdfUploadTest.php`
- Reference: `tests/Fixtures/assisted-extraction-lab.pdf`

- [ ] **Step 1: Run de volledige validator**

```bash
sh scripts/validate.sh
```

Expected: Laravel-tests, kwaliteitschecks, frontendbuild en whitespacechecks zijn allemaal groen. Claim geen gereedheid wanneer één fase faalt.

- [ ] **Step 2: Start de lokale QA-runtime in de geïsoleerde worktree**

In terminal 1:

```bash
php artisan migrate --force
php artisan app:seed-blood-test-demo
php artisan serve --host=127.0.0.1 --port=8000
```

In terminal 2:

```bash
npm run dev -- --host 127.0.0.1
```

Expected: Laravel draait op `http://127.0.0.1:8000`, Vite op `http://127.0.0.1:5173`, en alleen de synthetische QA-seed wordt gebruikt.

- [ ] **Step 3: Controleer de desktopweergave via de in-app Browser**

1. Log in met `qa@example.com` / `password`.
2. Open `http://127.0.0.1:8000/blood-tests`.
3. Controleer dat de dropzone één gestippeld oppervlak heeft, zonder geneste kaart.
4. Controleer dat de uploadmarkering, `PDF kiezen` en alle drie trust-signalen direct zichtbaar zijn.
5. Controleer dat de vergelijkingskaart hoger in dezelfde viewport verschijnt dan in de vastgelegde beginsituatie.
6. Controleer dat focus op `PDF kiezen` zichtbaar is en donkere modus geen contrastverlies veroorzaakt.

Expected: compacte hiërarchie zonder horizontale overflow, dubbele rand of afgesneden actie.

- [ ] **Step 4: Controleer de mobiele weergave op 390x844**

Gebruik de Browser-viewportcapability voor `390x844`, herlaad `/blood-tests` en controleer:

- trust-signalen wrappen binnen de dropzone;
- `PDF kiezen` blijft volledig zichtbaar en bedienbaar;
- titel en ondersteunende tekst overlappen niet;
- er is geen horizontale overflow.

Reset de viewportoverride na deze controle.

Expected: dezelfde inhoudshiërarchie blijft bruikbaar op mobiel.

- [ ] **Step 5: Bewijs het echte synthetische uploadpad**

Upload via de zichtbare filekeuze `tests/Fixtures/assisted-extraction-lab.pdf` en controleer:

- de geselecteerde bestandsnaam verschijnt;
- `extract`, `waarden`, `status` en `trend` bereiken `done`;
- de browser navigeert naar de nieuwe bloedtestdetailpagina;
- bevestigde waarden en de reviewstrip verschijnen volgens de bestaande fixture;
- onzekere rijen blijven draft;
- het brondocument is zichtbaar via de owner-authorized route;
- geen opslagpad of private echte data verschijnt.

Expected: alleen de presentatie is veranderd; het volledige bestaande intakecontract blijft werken.

- [ ] **Step 6: Leg het eindbewijs vast**

```bash
git status --short --branch
git log -2 --oneline
```

Noteer in de handoff:

- rode testcommando's en verwachte failures;
- gefocuste groene testoutput;
- volledige validatoruitkomst;
- desktop- en mobiele browserwaarnemingen;
- synthetische uploaduitkomst;
- commit-SHA van `fix: compact pdf upload dropzone`;
- expliciet dat niet is gemerged.

Expected: de featurebranch bevat het ontwerpcommit, het plancommit en één atomische implementatiecommit; merge blijft buiten scope.
