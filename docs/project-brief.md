# Project Brief: Persoonlijk Bloedwaarden-Dashboard

## One-Line Purpose

Een persoonlijk Laravel-dashboard om bloedtesten, biomarkers, contextnotities, trends, documenten en consultvoorbereiding gestructureerd bij te houden zonder diagnosemachine of medisch advies te worden.

## Problem

Bloedresultaten zitten vaak verspreid over labo-PDF's, losse portalen, screenshots, notities en herinneringen in het hoofd. Daardoor is het moeilijk om snel te zien:

- welke biomarkers wanneer gemeten zijn;
- welke waarden laag, normaal, hoog of onbekend waren;
- wat doorheen de tijd verandert;
- welke context rond slaap, voeding, training, supplementen, medicatie of klachten relevant was;
- welke vragen bij een volgend artsenconsult gesteld moeten worden;
- waar het originele document of resultaat terug te vinden is.

Het probleem is niet dat de gebruiker een medische diagnose nodig heeft. Het probleem is dat persoonlijke gezondheidsdata zonder eigen structuur snel onvindbaar, onvergelijkbaar en moeilijk bespreekbaar wordt.

## User

V1 is voor persoonlijk gebruik door een individuele gebruiker die zijn eigen bloedwaarden beter wil ordenen en opvolgen.

Primaire gebruiker:

- wil bloedtesten historisch kunnen terugvinden;
- voert biomarkers manueel en zorgvuldig in;
- wil trends en verschillen tussen testmomenten zien;
- wil context bewaren rond levensstijl, klachten, supplementen of medicatie;
- wil een overzicht kunnen meenemen naar een arts;
- wil controle houden over gevoelige data.

Niet de primaire gebruiker in V1:

- artsen of medische praktijken;
- coaches met meerdere klanten;
- families met meerdere profielen;
- publieke of commerciële health-SaaS gebruikers.

## Why Laravel

Laravel is hier logisch omdat dit geen gewone website is. De discovery-notities tonen dat Laravel vooral sterk wordt wanneer data, regels, bestanden, opvolging, communicatie en administratieve workflows samenkomen.

Dit project heeft precies die platformkenmerken:

- gestructureerde data: bloedtesten, biomarkers, meetwaarden, referentieranges en contextnotities;
- regels: statusberekening per waarde, eenheden, ranges, datums en vergelijkingen;
- login en privacy: gevoelige data hoort achter authenticatie;
- documenten: labo-PDF's of andere bewijsstukken moeten aan bloedtesten gekoppeld kunnen worden;
- opvolging: trends, pinned biomarkers, reminders en consultvoorbereiding;
- exports: gebruiker moet data kunnen meenemen of verwijderen;
- adminachtige motor: biomarker-catalogus, datakwaliteit, statussen en overzichten zijn belangrijker dan een flashy frontend.

Laravel is minder logisch als dit alleen een informatieve health-website zou zijn. Maar als persoonlijk dataplatform met private records, uploads, workflows, exports en later mogelijk OCR of automatisering is Laravel een goede keuze.

## Outcome

V1 werkt wanneer de gebruiker een echte bloedtest kan registreren, de belangrijkste biomarkers manueel kan invoeren, statussen en trends kan bekijken, twee testmomenten kan vergelijken, context kan bewaren en een bruikbaar consultoverzicht kan exporteren.

De gebruiker moet na V1 kunnen zeggen:

- "Ik weet welke waarden ik heb ingevoerd."
- "Ik zie wat veranderd is sinds een vorige test."
- "Ik kan belangrijke biomarkers pinnen."
- "Ik vind het originele document en mijn contextnotities terug."
- "Ik kan een overzicht maken voor mijn arts."
- "Ik kan mijn data exporteren of verwijderen."

## Scope

In scope voor V1:

- login-afgeschermde persoonlijke omgeving;
- bloedtest toevoegen met datum, labo en optioneel document;
- biomarkers manueel invoeren;
- biomarker-catalogus met naam, categorie, eenheid en referentierange;
- status per waarde: laag, normaal, hoog of onbekend;
- trendgrafiek per biomarker;
- twee bloedtesten vergelijken;
- belangrijke biomarkers pinnen;
- contextnotities toevoegen rond slaap, voeding, training, supplementen, medicatie en klachten;
- consultoverzicht of export maken;
- reminder voor volgende bloedtest;
- data exporteren en verwijderen;
- duidelijke producttekst dat dit geen diagnosemachine en geen medisch advies is.

Out of scope voor V1:

- medische diagnose of behandeladvies;
- AI-interpretatie van resultaten;
- supplement-, dieet- of trainingsaanbevelingen;
- automatische OCR/PDF-extractie;
- automatische koppelingen met labo's, artsenportalen of Apple Health;
- secure share links voor externe toegang;
- multi-user, familieprofielen of coach/client-flows;
- wearable-integraties;
- commerciële subscription-, billing- of marketplace-functionaliteit;
- complexe gender-, hormoon- of persoonlijke optimale range-logica;
- volledige catalogus van honderden of duizenden biomarkers.

## Key Data

Belangrijke data-entiteiten voor V1:

- gebruiker;
- bloedtest;
- labo of bronorganisatie;
- optioneel document bij een bloedtest;
- biomarker;
- biomarker-categorie;
- meetwaarde;
- eenheid;
- referentierange;
- berekende of manueel bevestigde status;
- pinned biomarker;
- contextnotitie;
- reminder;
- export of consultoverzicht.

Belangrijke datakwaliteitsregels:

- elke meetwaarde hoort bij exact een bloedtest en een biomarker;
- status mag "onbekend" zijn wanneer range, eenheid of waarde onvoldoende vergelijkbaar is;
- originele documenten en gestructureerde waarden blijven gescheiden;
- handmatige invoer is leidend in V1;
- onzekerheid wordt zichtbaar gemaakt, niet verstopt.

## Workflows

### Bloedtest Registreren

De gebruiker maakt een bloedtest aan met datum, labo/bron en optioneel document. Daarna kan hij biomarkers toevoegen of later aanvullen.

### Biomarkers Invoeren

De gebruiker kiest of maakt een biomarker, vult waarde, eenheid en referentierange in, en krijgt een status: laag, normaal, hoog of onbekend.

### Trends Bekijken

De gebruiker opent een biomarker en ziet de waarden doorheen de tijd, inclusief rangecontext en eventuele pinned status.

### Twee Testen Vergelijken

De gebruiker selecteert twee bloedtesten en ziet per gedeelde biomarker wat gestegen, gedaald, gelijk gebleven of onbekend is.

### Context Bewaren

De gebruiker voegt notities toe rond slaap, voeding, training, supplementen, medicatie of klachten zodat trends later niet los van de werkelijkheid bekeken worden.

### Consult Voorbereiden

De gebruiker maakt een overzicht met recente testen, afwijkende of pinned waarden, belangrijke trends en eigen vragen/notities voor een arts.

### Data Controleren

De gebruiker kan data exporteren en verwijderen. Privacy is geen latere polish maar een basisvoorwaarde.

## Success Criteria

V1 is geslaagd wanneer:

- een gebruiker minstens twee bloedtesten kan registreren met overlappende biomarkers;
- statusberekening correct laag, normaal, hoog of onbekend toont op basis van waarde, eenheid en range;
- een trendgrafiek per biomarker zichtbaar is;
- twee bloedtesten vergelijkbaar zijn in een overzicht;
- pinned biomarkers in een apart overzicht of consultcontext terugkomen;
- contextnotities gekoppeld zijn aan een datum of bloedtest;
- export of consultoverzicht bruikbaar is zonder medische claims te maken;
- data exporteerbaar en verwijderbaar is;
- `sh scripts/validate.sh` uiteindelijk de relevante V1-checks bewijst.

## Proposed Stack

- App/runtime: Laravel, later te bepalen na spec.
- Database/storage: relationele database plus private bestandsopslag, later te bepalen.
- AI/model layer: geen in V1.
- Deployment: nog niet beslist; V1 mag eerst lokaal of private self-hosted ontwikkeld worden.

Deze stack is bewust nog niet ingevuld als installatiebeslissing. Eerst brief, daarna spec, taken, validation script en pas dan implementatie.

## Key Decisions

- Decision: V1 begint met manuele invoer.
  Reason: OCR en labformat-extractie zijn foutgevoelig. Voor een eerste persoonlijk systeem is betrouwbare structuur belangrijker dan automatisering.

- Decision: Het product geeft geen medisch advies.
  Reason: De waarde zit in ordenen, opvolgen en consultvoorbereiding. Diagnose en behandeladvies horen bij een arts.

- Decision: Documenten en gestructureerde waarden blijven apart.
  Reason: Het originele laboresultaat blijft de bron, terwijl ingevoerde biomarkers een gestructureerde persoonlijke laag vormen.

- Decision: Status mag onbekend zijn.
  Reason: Een waarde zonder betrouwbare range, eenheid of vergelijkingsbasis mag niet kunstmatig als normaal of afwijkend worden voorgesteld.

- Decision: Privacy en export/delete zijn V1-scope.
  Reason: Gevoelige gezondheidsdata vereist vanaf het begin controle door de gebruiker.

## Risks

- Risk: Referentieranges verschillen per labo, geslacht, leeftijd, methode of context.
  Mitigation: V1 gebruikt expliciet ingevoerde ranges per biomarkerwaarde of catalogusitem en toont "onbekend" wanneer vergelijking niet verantwoord is.

- Risk: De app lijkt per ongeluk op een diagnose- of adviesmachine.
  Mitigation: UI-copy, export en workflows spreken over ordenen, opvolgen en vragen voorbereiden, niet over diagnose of behandeling.

- Risk: Manuele invoer kan fouten bevatten.
  Mitigation: Waarden blijven bewerkbaar, bron-documenten blijven gekoppeld, en V1 moet invoer reviewbaar maken.

- Risk: Scope kruipt richting AI, OCR, wearable-data of supplementadvies.
  Mitigation: Die functies blijven expliciet buiten V1 en vereisen later een aparte spec.

- Risk: Privacy wordt te laat aangepakt.
  Mitigation: Login, export en verwijderbaarheid staan in V1. Bestanden worden als gevoelige private documenten behandeld.

- Risk: Biomarker-catalogus wordt te groot voor een eerste project.
  Mitigation: Start met een kleine, handmatig beheerde catalogus van relevante biomarkers en breid alleen uit wanneer echte invoer daarom vraagt.

## Product-To-System Check

### 1. Wat probeer ik te bouwen?

Een persoonlijk opvolgsysteem voor bloedwaarden: geen medische interpretatiemachine, maar een private omgeving waarin bloedtesten, biomarkers, documenten, context, trends en consultvragen samenkomen.

### 2. Hoe moet dit systeem werken?

De gebruiker voert bloedtesten en biomarkerwaarden in, het systeem bewaart die gestructureerd, berekent eenvoudige statussen waar dat verantwoord is, toont trends en vergelijkingen, en maakt een consultgericht overzicht of export.

### 3. Welke componenten heb ik nodig?

Minimaal nodig: authenticatie, bloedtestbeheer, biomarker-catalogus, meetwaarden, statuslogica, trendweergave, compare-flow, pinned biomarkers, contextnotities, documentopslag, export/delete en reminder.

### 4. Waar moet deze logica leven?

De domeinregels rond statussen, ranges, vergelijkingen en export horen in de applicatielaag en niet verspreid in views. De UI mag tonen en invoer begeleiden, maar mag niet de enige plek zijn waar betekenis of datakwaliteit wordt bepaald.

### 5. Waarom breekt dit ding?

Het project breekt als het medisch advies probeert te geven, als ranges te simplistisch worden voorgesteld, als OCR/AI te vroeg wordt toegevoegd, als privacy als polish wordt behandeld, of als de eerste versie een breed health-platform wordt in plaats van een strak bloedwaarden-opvolgsysteem.

### 6. Verdict: bouwen

Bouwen is logisch, maar niet meteen. De volgende stap is een V1-spec en planningbaseline, niet Laravel-code.

## First Build Slice

De eerste implementatieslice moet klein maar echt zijn:

- gebruiker kan inloggen;
- gebruiker kan twee bloedtesten aanmaken;
- gebruiker kan een beperkte set biomarkers manueel invoeren;
- gebruiker ziet status per waarde;
- gebruiker kan een eenvoudige trend per biomarker bekijken;
- gebruiker kan twee bloedtesten vergelijken.

Nog niet in de eerste slice:

- OCR;
- AI-uitleg;
- consult-PDF;
- reminders;
- uitgebreide biomarker-catalogus;
- documentupload als dat de slice te groot maakt.

## Verification

V1 is pas klaar wanneer een projectlokaal validatiecommando de relevante checks bewijst:

```bash
sh scripts/validate.sh
```

In de latere planning moet dat script minstens meegroeien naar:

- codekwaliteit en tests;
- domeintests voor statusberekening;
- test of smoke check voor bloedtest aanmaken;
- test of smoke check voor biomarkerwaarde invoeren;
- test voor vergelijken van twee bloedtesten;
- test voor export/delete wanneer die in scope zit;
- eventueel browser-smoke voor de belangrijkste V1-flow.

Voor nu bewijst deze brief alleen de projectrichting. Hij is geen toestemming om al Laravel-code te genereren.
