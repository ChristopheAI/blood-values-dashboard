# Compacte PDF-upload-dropzone

Status: goedgekeurd ontwerp
Datum: 2026-07-21
Scope: presentatie van de bestaande `/blood-tests`-upload

## Doel

Maak de bestaande PDF-upload visueel compacter en rustiger, zonder de lokale
privacygrens, het bevestigingsmodel, de automatische upload of de zichtbare
voortgang te veranderen.

## Aanleiding en bewijs

Een live vergelijking met de Kokonut UI File Upload-demo en de lokale,
synthetisch gevulde `/blood-tests`-pagina liet zien dat de lokale intake
inhoudelijk sterker is, maar onnodig veel verticale ruimte gebruikt. De
bestaande dropzone bevat een grote buitencontainer met daarbinnen een tweede
omlijnde kaart. Daardoor krijgt lege ruimte meer visueel gewicht dan de
uploadactie.

De geselecteerde richting is variant A uit de visuele vergelijking: **compact,
één oppervlak**.

## Ontwerpbeslissing

De dropzone wordt één samenhangend oppervlak met deze hiërarchie:

1. Een kleine, decoratieve uploadmarkering.
2. De primaire instructie `Sleep je lab-PDF hierheen`.
3. Een korte secundaire regel die ook bestandsselectie benoemt.
4. De bestaande primaire actie `PDF kiezen`.
5. Drie compacte vertrouwenssignalen:
   - `Alleen PDF`;
   - `Geen externe verwerking`;
   - `Eerst bevestigen`.

De huidige binnenkaart en zijn extra rand verdwijnen. De dropzone blijft zelf
duidelijk herkenbaar door de gestippelde rand en de bestaande drag-over-state.
De minimumhoogte wordt verlaagd, zodat de vergelijkings- en lijstinhoud eerder
in beeld komt.

## Gedrag en dataflow

Het gedrag blijft ongewijzigd:

- klikken op `PDF kiezen` opent de bestaande verborgen PDF-input;
- een geselecteerd of gedropt bestand start de upload automatisch;
- alleen `application/pdf` blijft geaccepteerd;
- de geselecteerde bestandsnaam en validatiefouten blijven zichtbaar;
- de bestaande NDJSON-stream en de stappen `extract`, `waarden`, `status` en
  `trend` blijven functioneel identiek;
- de redirect naar de detail/reviewpagina blijft ongewijzigd;
- bevestigde waarden blijven de enige downstream-trustbasis.

Er verandert niets aan controllers, routes, opslag, extractie, autorisatie,
owner scoping of het `confirmed_at`-contract.

## Responsiviteit en toegankelijkheid

- De compositie blijft één kolom en werkt zonder horizontale overflow.
- De uploadmarkering is decoratief en krijgt geen concurrerende toegankelijke
  naam.
- De zichtbare knop blijft een echte `button`; de bestaande file-input blijft
  beschikbaar voor browser- en toetsenbordinteractie.
- Drag-and-drop is een aanvulling, geen vereiste: bestandsselectie blijft de
  primaire universele fallback.
- Tekstcontrast, focusgedrag, foutmelding en donkere modus blijven aansluiten
  op de bestaande interfacepatronen.
- Animatie blijft beperkt tot de bestaande overgangs- en voortgangsstaten en
  respecteert de huidige applicatiestijl.

## Privacy en medische productgrens

- Geen bestand, bestandsnaam, biomarkerwaarde of andere gezondheidsdata verlaat
  de lokale/private applicatiegrens.
- Er wordt geen Kokonut-, React-, Motion- of andere nieuwe dependency
  geïnstalleerd.
- De copy blijft beschrijvend en bevat geen diagnose, advies,
  gezondheidsscore, urgentie of aanmoediging tot extra testen.
- De vertrouwenssignalen verkorten de huidige uitleg, maar veranderen de
  betekenis niet: geen externe verwerking en bevestiging vóór
  downstreamgebruik blijven expliciet zichtbaar.

## Waarschijnlijke implementatiebestanden

- `resources/views/blood-tests/_upload-dropzone.blade.php`
- `tests/Feature/BloodTests/BloodTestPdfUploadTest.php`

Alleen wanneer live browser-QA een concrete regressie aantoont, mag een reeds
bestaande browsertest gericht worden aangepast. Geen andere intake- of
dashboardbestanden horen bij deze slice.

## Acceptatiecriteria

- De dropzone gebruikt één visueel oppervlak zonder geneste omlijnde kaart.
- De uploadactie en de drie vertrouwenssignalen zijn direct zichtbaar.
- De pagina gebruikt aantoonbaar minder verticale ruimte dan de huidige
  `min-h-[22rem]`-compositie.
- Bestandsselectie en drag-and-drop starten nog steeds automatisch dezelfde
  uploadflow.
- De bestandsnaam, validatiefouten en alle vier voortgangsstappen blijven
  zichtbaar op de juiste momenten.
- De bestaande privacy-, owner-scope-, confirmed-only- en medische-copygrenzen
  blijven groen.
- Desktop- en mobiele browser-QA tonen geen horizontale overflow en geen
  afgesneden uploadactie.

## Verificatie

De implementatie volgt test-first:

1. Voeg één gerichte presentatieassertie toe die faalt op de huidige geneste
   kaart/hoogte en het nieuwe trust-signaalcontract beschrijft.
2. Voer de gefocuste upload-featuretest uit en leg de verwachte rode fase vast.
3. Pas alleen de dropzone-partial minimaal aan.
4. Voer de gefocuste test opnieuw uit.
5. Draai `sh scripts/validate.sh`.
6. Controleer `/blood-tests` met synthetische QA-data op desktop en mobiel.
7. Upload een verse synthetische PDF en controleer selectie, voortgang,
   redirect, confirmed/draft-uitkomst en zichtbaarheid van het brondocument.

## Buiten scope

- Kokonut UI of andere frontendpakketten installeren.
- Upload-, extractie-, trust- of bevestigingslogica wijzigen.
- Nieuwe metadata-invoer toevoegen vóór de upload.
- Gezondheidsscores, activity rings, AI-interpretatie of advies toevoegen.
- De vergelijkingskaart, recente bloedtesten of andere paginaonderdelen
  herontwerpen.

## Context gebruikt

- `AGENTS.md`: privacy-, confirmed-only-, workflow- en validatiegrenzen.
- `resources/views/AGENTS.md`: compacte operationele layouts en
  toegankelijkheidscontracten.
- `tests/AGENTS.md`: test-first en verplichte browser-QA voor intakewijzigingen.
- `resources/views/blood-tests/_upload-dropzone.blade.php`: huidig gedrag en
  presentatie.
- `tests/Feature/BloodTests/BloodTestPdfUploadTest.php`: bestaand uploadcontract.
- Live lokale `/blood-tests` met uitsluitend de synthetische QA-seed.
- Kokonut UI File Upload: visuele referentie, niet als technische dependency.

## Contextgaten

Geen build-veranderende context ontbreekt. De huidige werkboom bevat veel
andere, reeds bestaande wijzigingen; daarom moet de implementatie uitsluitend
de twee hierboven genoemde bestanden raken en alleen eigen bestanden stagen.
