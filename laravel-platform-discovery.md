# Laravel Platform Discovery Notes

Doel: inspiratie verzamelen voor een relevant Laravel-project. Niet zomaar Laravel leren via syntax of CRUD, maar echte platformen analyseren: wat is gebouwd, voor wie, welke workflows zitten erin, en welke Laravel-patronen komen terug?

## Werkwijze

- Eerst verkennen, dan pas bouwen.
- Belgische/Vlaamse context krijgt voorrang.
- Niet alleen technische signalen noteren, maar ook het productidee en de waarde.
- Onderscheid maken tussen:
  - sterk gevalideerd: publieke Laravel/Livewire/Filament/Inertia-signalen zichtbaar;
  - aannemelijk: betrouwbare case of portfolio zegt het, maar publieke site toont weinig;
  - niet gevalideerd: interessant product, maar technisch onvoldoende bewijs.

## Technische herkenningssignalen

- `XSRF-TOKEN` cookie.
- Laravel session cookie, bijvoorbeeld `*_session`.
- `<meta name="csrf-token">`.
- `/livewire/update`.
- `wire:*` attributen.
- Livewire scripts of styles.
- `/build/assets/...` Vite assets.
- Ziggy route export.
- `X-Inertia` header of Inertia payload.
- Filament routes, assets of componentnamen.
- Laravel-ecosysteemsignalen zoals Passport, Pulse, Mailcoach, Mollie-integraties.

## Platformen En Cases

### Relaxy

- URL: https://www.relaxy.be/
- Status: sterk gevalideerd.
- Type: wellness/booking-platform.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `relaxy_session`.
  - CSRF meta tag.
  - Livewire config met `/livewire/update`.
  - `wire:*` en `/build/assets`.
- Wat is gebouwd:
  - platform rond wellness/relaxatie;
  - booking/aanbod-flow;
  - publieke aanbodpagina's plus dynamische interacties.
- Relevante lessen:
  - Laravel + Livewire is bruikbaar voor booking-achtige flows;
  - het platform hoeft niet zwaar frontend-first te zijn om toch dynamisch te voelen.

### Spot Workshops

- URL: https://spotworkshops.be/
- Status: sterk gevalideerd.
- Type: workshop-booking marketplace in Vlaanderen.
- Stack-signalen:
  - `spot_workshops_session`.
  - `X-Inertia`.
  - Ziggy routes.
  - routes naar `livewire/update`.
  - Filament-routes.
  - Laravel Passport, Pulse, Mailcoach en Mollie-signalen.
- Wat is gebouwd:
  - marketplace voor workshops;
  - zoek- en filterflow;
  - workshopgevers;
  - login/accountzone;
  - wishlists;
  - checkout;
  - giftcards;
  - Mollie-betalingen.
- Relevante lessen:
  - Laravel kan publieke marketplace, accountzone, checkout en admin combineren;
  - Inertia/Vue kan de publieke app rijker maken, terwijl Filament/Livewire in admin/backoffice meedraait.

### Spatie

- URL: https://spatie.be/
- Status: sterk gevalideerd als Laravel/Livewire referentie, minder als SaaS-platform.
- Type: Belgische Laravel agency/productstudio.
- Stack-signalen:
  - Spatie sessie-cookie.
  - Livewire styles/scripts.
  - `wire:*`.
  - `/livewire/update`.
  - `/build/assets`.
- Wat is gebouwd:
  - agency/productwebsite;
  - portfolio/product ecosystem;
  - Laravel/Livewire expliciet als kerntechnologie.
- Relevante lessen:
  - Spatie is vooral nuttig als kwaliteitsreferentie en Laravel-cultuuranker;
  - hun producten tonen dat Laravel ook voor commerciële SaaS en devtools wordt gebruikt.

### Mailcoach

- URL: https://www.mailcoach.app/
- Status: sterk gevalideerd; ook vermeld op Built with Laravel.
- Type: emailmarketing SaaS.
- Stack-signalen:
  - Livewire styles.
  - `wire:navigate`.
  - `/livewire/update`.
  - `/build/assets`.
  - Built with Laravel vermeldt Spatie/Mailcoach als Laravel-project.
- Wat is gebouwd:
  - campagnes;
  - automations;
  - transactional email;
  - API;
  - pricing/trial/onboarding.
- Relevante lessen:
  - SaaS hoeft niet alleen CRUD te zijn: workflows, segmentatie, analytics en API zijn productwaarde;
  - Laravel + Livewire kan een commerciële SaaS dragen.

### Oh Dear

- URL: https://ohdear.app/
- Status: sterk aannemelijk/technisch publiek herkenbaar als Laravel.
- Type: monitoring SaaS.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `oh_dear_session`.
  - `/build/assets`.
  - Laravel-achtige security headers en appstructuur.
- Wat is gebouwd:
  - uptime monitoring;
  - AI monitoring;
  - ping/TCP checks;
  - SSL/certificate monitoring;
  - broken link scanning;
  - Lighthouse checks;
  - scheduled task monitoring;
  - application health;
  - DNS/domain monitoring;
  - notifications en status pages.
- Relevante lessen:
  - sterke SaaS-producten hebben vaak veel kleine checks rond een duidelijke kernwaarde;
  - notificaties, status, histories en dashboards zijn centrale SaaS-bouwstenen.

### Kadee

- URL: https://usekadee.com/
- Status: sterk gevalideerd als Laravel-app; Belgische maker.
- Type: AI devtool voor Laravel teams.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `kadee-session`.
  - `/build/assets`.
- Wat is gebouwd:
  - ontvangt error-webhooks van Flare;
  - analyseert errors met AI;
  - maakt GitHub pull requests met fixes;
  - team reviewt en merged.
- Relevante lessen:
  - interessant Laravel-project kan rond integraties draaien, niet alleen rond content;
  - webhooks, externe APIs, async verwerking en reviewflows zijn waardevol.

### Surf King

- URL: https://surfking.be/
- Status: sterk gevalideerd.
- Type: multi-tenant CMS/platform voor websites en webshops.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `surf_king_session`.
  - CSRF meta tag.
  - Livewire styles/scripts.
- Wat is gebouwd:
  - platform waarmee klanten eigen websites/webshops beheren;
  - multi-tenant CMS;
  - SEO-tools;
  - automatische scans;
  - ondersteuning voor betaalbare websites.
- Relevante lessen:
  - multi-tenancy is een belangrijk SaaS-patroon;
  - CMS + klantportaal + templates kan een concreet Laravel-product zijn.

### wij.leveren

- Case URL: https://www.statik.be/projecten/wij-leveren
- Live URL: https://www.wijleveren.be/
- Status: case sterk, live site niet bezocht door certificaatwaarschuwing.
- Type: logistiek platform voor Stad Leuven en handelaars.
- Stack-signalen:
  - Statik-case zegt expliciet: gebouwd in Filament.
  - Case zegt expliciet: Filament gebruikt Laravel Livewire.
- Wat is gebouwd:
  - handelaars kunnen pakjes aanmelden/beheren;
  - verzendmethode en logistieke partner selecteren;
  - labels aanmaken;
  - verzendingen aanmaken;
  - verzendstatussen ophalen;
  - uitbreidbaarheid naar andere logistieke partners;
  - dashboard met statistieken als volgende stap.
- Relevante lessen:
  - Filament is geschikt voor administratieve applicaties met veel data en workflows;
  - integratie-architectuur is cruciaal wanneer meerdere externe partners kunnen aansluiten.

### DAS Media B2B Bestelplatform

- URL: https://www.dasmedia.be/nl/e-commerce/b2b-bestelplatform
- Case: https://www.dasmedia.be/nl/cases/b2b-bestelplatform-vander-zijpen
- Status: sterk gevalideerd als Laravel/TALL-stack agency platform.
- Type: B2B e-commerce en ERP-gekoppeld bestelplatform.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `dasmedia_session`.
  - CSRF meta tag.
  - Livewire styles/scripts.
  - `/build/assets`.
  - expliciete TALL-stack: Tailwind, Alpine, Laravel, Livewire.
- Wat is gebouwd:
  - B2B bestelplatform;
  - ERP-koppelingen;
  - klantafhankelijke prijzen;
  - klantafhankelijk assortiment;
  - favorietenlijsten;
  - orderhistoriek/facturen als klantenportaal;
  - real-time sync;
  - offline/local-storage bestelmodus in case Vander Zijpen.
- Relevante lessen:
  - B2B SaaS zit vaak in regels, prijzen, rollen en integraties;
  - een platform kan erg waardevol zijn zonder flashy frontend.

### Gezinssport Vlaanderen Aanbod

- URL: https://aanbod.gezinssportvlaanderen.be/
- Case URL: https://www.statik.be/projecten/reisplatform-gezinssport-vlaanderen
- Status: sterk Laravel-signaal op live platform; case noemt niet expliciet Laravel op de detailpagina.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `gezinssport_aanbod_session`.
  - CSRF meta tag.
- Wat is gebouwd:
  - reisaanbod;
  - zoeken/filteren;
  - inschrijfflow;
  - online betalen;
  - automatische communicatie;
  - beheer van reizen, activiteiten en begeleiding.
- Relevante lessen:
  - boekingsplatformen vragen meer dan listings: betalingen, kortingen, communicatie, begeleiders en rapportage tellen mee.

### Unia Meldingsformulier

- Case URL: https://www.statik.be/projecten/unia
- Status: case-gevalideerd.
- Type: dynamisch meldingsformulier met CRM-integratie.
- Stack-signalen:
  - case vermeldt Laravel Nova als CMS-systeem.
- Wat is gebouwd:
  - dynamisch meerstapsformulier;
  - thematiek/context bepaalt velden;
  - persoonsgegevens losgekoppeld van inhoudelijke melding;
  - inzending naar Microsoft Dynamics;
  - bijlagen naar Microsoft Azure;
  - vertalingen in vier talen.
- Relevante lessen:
  - formulieren kunnen echte platformen zijn wanneer ze regels, routing, integraties en opvolging bevatten;
  - gevoelige workflows vragen goede UX en duidelijke scheiding van data.

### Samensterker

- Case URL: https://www.statik.be/projecten/samensterker
- Status: functioneel interessant, technologie niet publiek gevalideerd als Laravel.
- Type: platform voor duurzame groepsaankopen.
- Wat is gebouwd:
  - groepsacties per regio;
  - postcode-gebaseerde beschikbaarheid;
  - accounts/profielen;
  - inschrijvingen;
  - leveranciersflow;
  - follow-up mails;
  - rapporten voor lopende acties.
- Relevante lessen:
  - regio, beschikbaarheid en doelgroepregels kunnen een platform onderscheidend maken;
  - het kernproduct is matching tussen gebruiker, actie en leverancier.

### SamAc

- URL: https://samac.be/
- Case/portfolio URL: https://www.koenkicken.be/
- Status: sterk gevalideerd via live site en portfolio.
- Type: verenigingsplatform voor SamAc vzw in Diest.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `samac_session`.
  - PHP 8.3.
  - `/build/assets`.
  - portfolio vermeldt expliciet Laravel 12, Filament v4, Livewire, Tailwind CSS, Mollie Payments, Spatie Permissions, Pest, Docker/Laravel Sail en Vite.
- Wat is gebouwd:
  - publieke website met activiteiten en nieuws;
  - ledenportaal;
  - activiteitenbeheer;
  - online betalingen via Mollie;
  - gestructureerde overschrijvingen;
  - tickets met unieke QR-code;
  - toegangsbeheer via scans;
  - Filament adminpaneel;
  - scheiding tussen personen, leden en accounts;
  - gelinkte personen en kinderen die ledenrechten kunnen erven;
  - async job queue, model observers en services.
- Relevante lessen:
  - een Laravel-platform wordt interessant wanneer het domeinregels respecteert in plaats van alles in standaard user/account-tabellen te forceren;
  - verenigingen zijn sterke SaaS-kandidaten omdat ze leden, activiteiten, betalingen, communicatie en admin combineren.

### iKot

- URL: https://ikot.be/
- Status: sterk gevalideerd.
- Type: Belgische marketplace voor studentenkoten.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `ikotbe_session`.
  - `Vary: X-Inertia`.
  - `/build/assets`.
  - Ziggy route export.
  - Inertia page payload.
  - Laravel Horizon routes.
  - Laravel Nova routes.
  - payment routes.
  - manager/moderation routes.
- Wat is gebouwd:
  - koten zoeken per stad/postcode/straat;
  - listing cards met prijs, beschikbaarheid, type, oppervlakte en locatie;
  - verhuurder-accountflow;
  - kot toevoegen en beheren;
  - zoekmeldingen/alerts;
  - favorieten;
  - berichten/reacties;
  - reactiepunten en spotlight-upsells;
  - checkout/betaalflow;
  - moderatie en supportmanager;
  - meertaligheid: NL, EN, FR;
  - AI-training/moderatie-signalen in routes.
- Relevante lessen:
  - marketplaces vragen veel meer dan listings: moderation, messaging, monetization, trust, accountflows en search alerts zijn kernfunctionaliteit;
  - Inertia past goed wanneer de publieke app veel filtering en accountinteractie heeft.

### Diaz

- URL: https://www.diaz.be/nl
- Configurator URL: https://www.diaz.be/nl/configurator
- Status: sterk gevalideerd.
- Type: Belgische productwebsite met webshop/configurator voor raamdecoratie op maat.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `diaz_session`.
  - PHP 8.3.
  - `/build/assets`.
  - Livewire styles/scripts.
  - `/livewire/update`.
- Wat is gebouwd:
  - productcatalogus;
  - configurator per producttype;
  - winkelmand/samenvatting;
  - dealers/verdelers;
  - advies/opmeting/plaatsing/levering services;
  - DiazPro aparte B2B/pro omgeving;
  - content/inspiratie rond producten.
- Relevante lessen:
  - productconfiguratoren zijn interessante Laravel-projecten omdat ze regels, keuzes, prijslogica, cart-state en later eventueel B2B-portalen combineren;
  - Livewire is logisch voor stapsgewijze configuratie waar server-side regels belangrijk blijven.

### SweepBright

- Case URL: https://madewithlove.com/about/our-work/sweepbright/
- Status: case-gevalideerd.
- Type: SaaS/platform voor vastgoedkantoren.
- Stack-signalen:
  - madewithlove-case vermeldt expliciet een JSON API in PHP met Laravel.
  - React web app, native iOS app en statisch gehoste agency websites consumeren die API.
- Wat is gebouwd:
  - listings aanmaken en publiceren;
  - leads beheren;
  - automatische matching tussen listings en leads;
  - publicatie naar vastgoedportalen en social media;
  - agency websites genereren met listings;
  - integraties met derde partijen en Zapier;
  - schaalbare API-first architectuur.
- Relevante lessen:
  - Laravel hoeft niet altijd de volledige UI te renderen; het kan de API-kern zijn achter web, mobiel en gegenereerde sites;
  - vastgoedplatformen draaien rond matching, publicatiekanalen, leadopvolging en dataconsistentie.

### Contractify

- Case URL: https://madewithlove.com/about/our-work/contractify/
- Status: case-gevalideerd.
- Type: contract management SaaS.
- Stack-signalen:
  - case vermeldt upgrades van PHP en Laravel.
  - case vermeldt nieuwe backoffice met Laravel Nova.
  - infrastructuur met Redis queue, managed database, backups en cloud file storage.
- Wat is gebouwd:
  - contractbeheer;
  - e-signing;
  - taken/flows;
  - nieuwe contractflows;
  - backoffice;
  - stabiliteits- en performance-refactor;
  - CI/CD met statische analyse, formatting en end-to-end tests.
- Relevante lessen:
  - schaalbare Laravel-producten vragen niet alleen features maar ook technische maturiteit: tests, CI/CD, queues, storage en documentatie;
  - voor een SaaS-project is een goed backoffice vaak even belangrijk als de klantinterface.

### EventPay

- URL: https://eventpay.be/
- Status: functioneel sterk; publieke marketing site lijkt WordPress, Laravel-stack vooral gevalideerd via vacaturetekst.
- Type: digitaal betaalplatform voor events, clubs en horeca.
- Stack-signalen:
  - publieke site toont WordPress-marketinglaag.
  - vacaturetekst vermeldt Laravel, PHP, SQL/Postgres, Laravel Livewire, Tailwind CSS, Alpine.js, Laravel Vite en Mollie.
- Wat is gebouwd:
  - cashless kassasysteem;
  - QR-bestellen;
  - realtime rapportering;
  - stockbeheer;
  - support voor events;
  - integraties met ticketing, access control, accreditatie, cupmanagement, boekhouding/CRM/marketing, connectiviteit en betaalverwerkers;
  - sectorflows voor festivals, horeca, sportclubs en concertzalen.
- Relevante lessen:
  - eventplatformen combineren verkoop, betalingen, voorraad, toegang, rapportage en integraties;
  - soms is de publieke site niet de app; je moet onderscheid maken tussen marketinglaag en productplatform.

### Ticketmatic

- URL: https://www.ticketmatic.com/
- Status: functioneel sterk; marketing site is Webflow, Laravel vooral afgeleid uit vacaturetekst rond custom plugins.
- Type: ticketing en marketingplatform voor culturele organisaties.
- Stack-signalen:
  - publieke marketing site is Webflow.
  - vacaturetekst vermeldt PHP/Laravel plugins bovenop het Ticketmatic-platform.
- Wat is gebouwd:
  - online ticketshop;
  - fysieke verkoop;
  - upselling en donaties;
  - peak sales;
  - betalingen;
  - Audience CRM;
  - dashboards;
  - event- en productbeheer;
  - seating plans;
  - subscriptions/bundles;
  - membership management;
  - ticket delivery, secure tickets, access control;
  - marketingcampagnes, vouchers, passes en loyalty;
  - platformpersonalisatie via ticketmatic Studio.
- Relevante lessen:
  - ticketingplatformen zijn rijke domeinen met veel randflows: CRM, toegang, zaalplannen, piekverkoop, vouchers, reporting en integraties;
  - Laravel kan ook relevant zijn als extensie-/pluginlaag bovenop een groter product.

### Sorcerers at the Core Ranking

- Project URL: https://xewl.dev/projects/sorcerers-at-the-core-ranking
- Live URL: https://ranking.sorcerersatthecore.com/
- Status: sterk gevalideerd.
- Type: community ranking, tournament en leaderboard platform.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `satc_ranking_session`.
  - `/build/assets`.
  - Livewire styles/scripts.
  - `wire:navigate`.
  - `wire:snapshot`.
  - projectpagina vermeldt Laravel, Livewire, Filament, Tailwind CSS, MySQL en Pest.
- Wat is gebouwd:
  - publieke landing;
  - leaderboards;
  - tournaments;
  - login/register;
  - ELO tracking;
  - role-based access;
  - localization;
  - Filament administration.
- Relevante lessen:
  - community platforms hoeven niet groot te zijn om interessant te zijn: rankings, permissions, toernooien en berekeningen geven genoeg domeincomplexiteit;
  - ELO/rankinglogica is een goed voorbeeld van business rules die Laravel goed kan dragen.

### FixForm

- Project URL: https://www.fixform.com/
- App URL: https://app.fixform.com/login
- Case URL: https://madewithlove.com/about/our-work/fixform/
- Status: sterk gevalideerd.
- Type: facility management SaaS voor meldingen, taken, gebouwen en assets.
- Stack-signalen:
  - publieke marketing site draait op Webflow, dus die alleen is geen Laravel-bewijs;
  - de app-login toont `XSRF-TOKEN`, `fixform_session`, CSRF meta tag, Inertia title, `/build/assets`, Ziggy routes, Filament backoffice routes, Horizon routes, Passport/Sanctum routes en Livewire-routes;
  - de madewithlove-case vermeldt expliciet Inertia.js, Vue en Laravel.
- Wat is gebouwd:
  - probleemmeldingen vanuit gebouwen/ruimtes;
  - QR-code flows voor meldingen;
  - taken, statusflows en comments;
  - gebouwen, sites, spaces en assets;
  - documenten en files;
  - rollen en rechten;
  - customer/tenant APIs;
  - backoffice met Filament;
  - queues/monitoring via Horizon;
  - integraties/webhooks zoals SendGrid en TOPdesk;
  - SSO/login via WorkOS.
- Relevante lessen:
  - een SaaS hoeft niet te beginnen met "AI"; een heel concreet operationeel probleem zoals gebouwmeldingen geeft al veel diepte;
  - Laravel past hier goed omdat het domein veel resources, rollen, policies, workflows, API-routes, queues en adminschermen heeft;
  - QR-codes zijn een slimme ingang: anonieme of laagdrempelige intake voor externe gebruikers, daarna opvolging in een authenticated platform.

### Impact Us Today

- App URL: https://app.impactus.today/
- Case URL: https://madewithlove.com/about/our-work/impact-us-today/
- Status: sterk technisch gevalideerd via app-login; productcontext gevalideerd via case.
- Type: renovatie- en energieplatform dat huiseigenaars verbindt met aannemers via partners, steden, cooperaties en bedrijven.
- Stack-signalen:
  - `XSRF-TOKEN`.
  - `impact_us_today_session`.
  - `/build/assets`.
  - CSRF meta tag.
  - Filament CSS/JS.
  - Livewire styles/scripts.
  - `wire:snapshot`, `wire:submit`, `wire:model`.
  - `/livewire/update`.
- Wat is gebouwd:
  - login/adminomgeving;
  - lead- en aanvraagflows rond energetische renovaties;
  - partnergedreven websites/platformen;
  - offerteaanvragen;
  - matching met gekwalificeerde aannemers;
  - opvolging van renovatieproces;
  - backoffice voor operationele opvolging;
  - duidelijke scheiding tussen eindgebruikers, aannemers, partners en interne opvolging.
- Relevante lessen:
  - dit is een goed voorbeeld van een "workflow-platform": waarde zit in intake, matching, statusopvolging en vertrouwen;
  - Laravel + Filament/Livewire lijkt hier heel geschikt voor snelle backoffice-ontwikkeling;
  - interessant voor ons: een platform kan starten als gestructureerde intake + admin, en pas later complexere automatisering toevoegen.

### JUCE

- Case URL: https://madewithlove.com/about/our-work/juce/
- Status: case-gevalideerd.
- Type: IoT/backoffice platform voor slimme docking stations in vergaderruimtes.
- Stack-signalen:
  - madewithlove-case vermeldt expliciet Laravel Nova;
  - case vermeldt Google IoT en Cronofy voor calendar management.
- Wat is gebouwd:
  - backoffice voor hardware/fleet management;
  - planning en schedules naar IoT-devices;
  - calendar management;
  - meeting room/device synchronisatie;
  - alerting;
  - time management;
  - veilige IoT-architectuur rond fysieke hardware.
- Relevante lessen:
  - Laravel is niet alleen CRUD: het kan ook een centrale orchestration layer zijn tussen hardware, calendars, externe APIs en admin users;
  - Nova/Filament-achtige adminpanelen zijn sterk wanneer het product vooral interne beheerslogica nodig heeft;
  - een klein fysiek probleem kan een heel interessant softwareplatform opleveren als er scheduling, devices en integraties bij komen.

### Phished Academy

- Marketing URL: https://phished.io/
- App URL: https://phishedacademy.io/en/auth
- Status: sterk technisch gevalideerd voor Academy; marketing site zelf is Craft CMS.
- Type: cybersecurity awareness SaaS met training, simulaties en veilige leeromgeving.
- Stack-signalen:
  - marketing site gebruikt `CraftSessionId`, dus die is geen Laravel-bewijs;
  - Academy-login toont `XSRF-TOKEN`, `academy_laravel_session`, CSRF meta tags, `/build/assets`, Livewire styles/scripts, `wire:snapshot`, `wire:model`, `wire:submit` en `/livewire/update`;
  - vacatures en externe jobposts vermelden Laravel backend engineering voor het Phished-platform.
- Wat is gebouwd:
  - Phished Academy login;
  - e-mail/code loginflow;
  - Microsoft login;
  - meertalige academy;
  - security awareness trainingen;
  - gamified sessions;
  - phishing simulaties;
  - rapportering en dashboards voor organisaties;
  - reseller/partnercontext;
  - veilige omgeving rond Zero Incident Mail.
- Relevante lessen:
  - dit toont hoe Laravel/Livewire ook kan werken voor een high-trust, security-gerichte SaaS;
  - multi-tenant learning platforms combineren content, gebruikers, progress, events, rapportering en compliance;
  - voor ons eigen project is dit vooral inspiratie voor onboarding, user progress, notificaties en organization-level reporting.

### YOUCA Jobbank

- Case URL: https://www.statik.be/projecten/youca
- Publieke URL: https://www.youca.be/jobbank
- App URL: https://jobbank.youca.be/crm/login
- Status: sterk gevalideerd.
- Type: jobbank en administratief platform voor de YOUCA Action Day.
- Stack-signalen:
  - de Statik-case vermeldt Laravel Filament voor de vernieuwde jobbank;
  - `jobbank.youca.be/crm/login` toont `XSRF-TOKEN`, `youca_session`, CSRF meta tag, `/build/assets`, Filament CSS/JS, Livewire styles/scripts, `wire:snapshot`, `wire:model`, `wire:submit`, `wire:navigate` en `/livewire/update`.
- Wat is gebouwd:
  - registratie voor leerlingen;
  - registratie voor leerkrachten/scholen;
  - registratie voor werkgevers;
  - jobposting door werkgevers;
  - sollicitaties door jongeren;
  - matching tussen leerlingen en jobs;
  - contracten/facturen/administratieve opvolging;
  - dashboards voor verschillende doelgroepen;
  - e-mail automation via Campaign Monitor;
  - statistieken en beheer voor het YOUCA-team.
- Relevante lessen:
  - dit is een sterk voorbeeld van een multi-role platform: leerling, leerkracht, werkgever en admin hebben elk eigen flows;
  - Laravel/Filament/Livewire is hier vooral waardevol voor procesautomatisering, niet voor content;
  - e-mail automation is geen bijzaak: het ondersteunt gewenst gedrag, bijvoorbeeld jongeren motiveren om te solliciteren.

### Museumpas

- Case URL: https://www.statik.be/projecten/museumpas
- Live URL: https://www.museumpassmusees.be/
- Status: sterk gevalideerd.
- Type: e-commerce, abonnementenplatform en klantenzone rond Belgische museumbezoeken.
- Stack-signalen:
  - Statik-case vermeldt expliciet Laravel en Vue.js;
  - live site toont `XSRF-TOKEN`, `museumpassmusees_session`, CSRF meta tag, `/build/assets`, Livewire script en `/nl/livewire/update`;
  - live site bevat checkout/login/registratieflows en betaalmethodes.
- Wat is gebouwd:
  - aankoopflow voor museumpas;
  - cadeau-/voucherflow;
  - registratie van fysieke passen;
  - klantenzone "Mijn museumpas";
  - aanbod van musea en tentoonstellingen;
  - integratie met de museumpas API;
  - redactionele admininterface;
  - online betalingen;
  - marketing automation en data dashboards;
  - hoge piekbelasting bij lancering met caching/load balancing.
- Relevante lessen:
  - een abonnementenproduct vraagt meer dan checkout: activatie, accountbeheer, voordelen, aanbod en verlenging vormen samen het platform;
  - Laravel is sterk wanneer e-commerce, content, externe API, klantenzone en marketingdata elkaar raken;
  - schaalbaarheid en caching zijn relevant zodra campagnes of lanceringen veel bezoekers tegelijk brengen.

### KU Leuven CHAT Tool

- Case URL: https://www.statik.be/projecten/combat-harassment-tool
- Live URL: https://www.kuleuven.be/chat/
- Status: sterk gevalideerd.
- Type: gevoelige screening- en rapportagetool voor organisaties rond grensoverschrijdend gedrag op het werk.
- Stack-signalen:
  - Statik-case vermeldt een beheerportaal in Laravel Nova;
  - live site toont `XSRF-TOKEN` en `chat_live_session`;
  - case vermeldt een externe survey tool, API-integratie en PDF-generatie.
- Wat is gebouwd:
  - licentieaanvraag/aankoop voor organisaties;
  - organisatieaccounts;
  - teams en bevragingen;
  - unieke links naar surveys;
  - geaggregeerde resultaatverwerking;
  - PDF-rapporten;
  - beheerportaal voor organisaties, gebruikers, licenties en teksten;
  - GDPR- en anonimiseringsaanpak.
- Relevante lessen:
  - Laravel kan een veilige orchestrator zijn rond externe surveytools en gevoelige rapportage;
  - minimumdrempels, aggregatie en anonimisering zijn productregels, geen randdetails;
  - dit is inspiratie voor tools waar vertrouwen, privacy en procescontrole belangrijker zijn dan visuele flair.

### Repair Connects

- Case URL: https://www.statik.be/projecten/repair-connects
- Live URL: https://www.repairconnects.org/
- Status: case-gevalideerd.
- Type: community repair en matchingplatform.
- Stack-signalen:
  - Statik-case vermeldt Laravel, InertiaJS en Vuetify;
  - live URL bestaat nog, maar de huidige technische stack van de live site is publiek minder duidelijk dan de case.
- Wat is gebouwd:
  - registratie van kapotte toestellen;
  - repair list/wachtrij;
  - herstellersaccounts;
  - matching tussen indieners en herstellers;
  - contactgegevens uitwisselen;
  - herstelregistraties;
  - opbouw van databank rond toestellen, merken, modellen en herstelbaarheid.
- Relevante lessen:
  - een MVP kan sterk zijn als het een fysieke community-flow digitaal maakt;
  - Laravel + Inertia is nuttig wanneer je een app-gevoel wilt zonder volledig aparte API/front-end architectuur;
  - repair-data kan later waardevol worden voor dashboards, inzichten en circulariteit.

### Ik schrijf in

- Case URL: https://www.statik.be/projecten/ik-schrijf-in
- Live URL: https://www.ikschrijfin.be/
- Status: sterk gevalideerd.
- Type: gedeeld leden-, vormings- en betaalplatform voor meerdere professionele organisaties.
- Stack-signalen:
  - Statik-case vermeldt expliciet Laravel;
  - live site redirect naar `wvv.ikschrijfin.be` en toont `XSRF-TOKEN` en `wvv_ikschrijfin_session`;
  - case vermeldt online betalingen via Mollie.
- Wat is gebouwd:
  - ledenbeheer;
  - lidgeld en online betalingen;
  - organisatiebetalingen voor meerdere personen;
  - goedkeuring van organisaties;
  - kortingstarieven;
  - vormingen en activiteiten;
  - inschrijvingen per sessie of reeks;
  - profiel voor leden;
  - attesten en presentaties downloaden;
  - aanwezigheden beheren;
  - exports voor RIZIV/accreditering;
  - centrale backend over organisaties heen.
- Relevante lessen:
  - gedeelde platformen zijn interessant wanneer meerdere organisaties vergelijkbare maar net afwijkende flows hebben;
  - Laravel is geschikt voor uitzonderingsregels, rollen, betalingen, exports en jaarlijkse campagnes;
  - dit ligt dicht bij een realistisch eerste Laravel-project: veel business rules, maar geen exotische technologie nodig.

### Persoonlijk bloedwaarden-dashboard

- Voorbeelden:
  - Hemeify: https://hemeify.com/
  - TrackMyLabs: https://www.trackmylabs.app/
  - getbased: https://getbased.health/
  - Guava Health: https://guavahealth.com/
  - InsideTracker Blood Results Upload: https://info.insidetracker.com/blood-results-upload
  - Apple Health Records: https://support.apple.com/en-ie/105016
- Status: bestaand productpatroon; niet Laravel-gevalideerd, wel zeer relevant als platformconcept.
- Type: persoonlijk gezondheidsdashboard rond laboresultaten, biomarker tracking en consultvoorbereiding.
- Wat bestaande tools doen:
  - PDF's, foto's of screenshots van laboresultaten uploaden;
  - biomarkers automatisch uit documenten halen;
  - manuele invoer ondersteunen;
  - waarden structureren per datum, biomarker, eenheid en referentierange;
  - trends tonen over tijd;
  - afwijkende waarden markeren;
  - context geven bij biomarkers;
  - rapporten of exports maken voor arts/consult;
  - soms supplementen, lifestyle, medicatie of wearable-data koppelen;
  - soms AI gebruiken om resultaten te verklaren;
  - privacy-first varianten bewaren data lokaal of strippen persoonlijke gegevens.
- Mogelijke Laravel-interpretatie:
  - persoonlijke data-kluis;
  - uploads van labo-PDF's;
  - biomarker database;
  - metingen per datum;
  - referentiewaarden en eenheden;
  - grafieken en trends;
  - notities rond slaap, voeding, training, supplementen of klachten;
  - reminders voor opvolgtest;
  - consultvoorbereiding;
  - export naar PDF/CSV;
  - duidelijke privacy- en veiligheidslaag.
- Relevante lessen:
  - "mijn bloedwaarden" is data, maar wordt pas een platform wanneer er opvolging, context, documenten, grafieken, reminders en exports bijkomen;
  - voor persoonlijk gebruik hoeft de eerste versie geen medische SaaS te zijn;
  - privacy is hier geen extra feature maar de basis van het product;
  - de waarde zit niet in diagnose, maar in ordenen, begrijpen, opvolgen en voorbereiden.

#### Feature teardown: Hemeify

- Bron: https://hemeify.com/
- Positionering: "Your blood work, in Plain-English."
- Kernbelofte:
  - laboresultaten begrijpelijk maken zonder medisch jargon;
  - verspreide resultaten samenbrengen in een doorzoekbare historiek;
  - biomarkers automatisch uit PDF/JPG/PNG halen;
  - trends zichtbaar maken;
  - PDF-rapport, secure share link en CSV-export aanbieden.
- Sterke features:
  - plain-English uitleg per marker;
  - visuele status per waarde: laag, normaal, hoog of optimaal;
  - marker-detailpagina met trendgrafiek;
  - supplement-insights;
  - gender-aware reference ranges, ook bij GAHT-transities;
  - unit converter voor 75+ markers.
- Wat wij hiervan zouden willen lenen:
  - simpele taal als UX-principe;
  - per biomarker: "wat is dit?", "waar sta ik?", "wat verandert?";
  - CSV/PDF-export voor consult of eigen archief;
  - eenheid-conversie als nuttige tool;
  - document upload + automatische extractie als latere feature.
- Wat waarschijnlijk niet in v1 moet:
  - supplement-aanbevelingen;
  - complexe gender-/hormoonrange-logica;
  - secure share links met externe toegang.

#### Feature teardown: TrackMyLabs

- Bron: https://www.trackmylabs.app/
- Positionering: "All your lab results, organized. Spot trends and act sooner."
- Kernbelofte:
  - labresultaten van eender welk labo uploaden;
  - biomarkers automatisch extraheren;
  - trends en referentieranges bekijken;
  - resultaten exporteren of delen met arts;
  - mobiele app met sync tussen apparaten.
- Sterke features:
  - onboarding in vier stappen: installeren, upload/connect, biomarker extractie, trends delen;
  - automatische lab entry via OCR plus manuele invoer;
  - ondersteuning voor 1.000+ biomarkers;
  - encryptie, data ownership, export en deletion options;
  - voorbeelden van concrete biomarkers en statussen op de marketingpagina.
- Wat wij hiervan zouden willen lenen:
  - "upload of manueel invoeren" als robuuste fallback;
  - biomarker catalogus met eenheden en referentieranges;
  - statuslabels zoals normal, borderline, abnormal;
  - eenvoudige trendgrafieken;
  - duidelijke data-portability: export en verwijderen.
- Wat waarschijnlijk niet in v1 moet:
  - native iOS/Android app;
  - lab-provider connecties;
  - 1.000+ biomarkers meteen ondersteunen.

#### Feature teardown: getbased

- Bron: https://getbased.health/ en https://app.getbased.health/
- Positionering: open-source, no account required, privacy-first blood work dashboard.
- Kernbelofte:
  - lab-PDF's omzetten naar interactieve charts;
  - trends en "optimal ranges" tonen;
  - AI laten redeneren met labwaarden plus persoonlijke context;
  - lokale opslag en privacy-first verwerking.
- Sterke features:
  - demo-data zonder account;
  - PDF/image/screenshot import;
  - AI mapping naar bekende markers en onbekende markers bewaren;
  - search markers;
  - dashboard, correlations en compare dates;
  - context cards rond dieet, slaap, licht, beweging, stress, omgeving, doelen en aandoeningen;
  - protocol/supplement/medicatie overlays op grafieken;
  - derived markers en ratios;
  - wearables naast labwaarden: HRV, sleep, recovery, body composition;
  - privacy: lokaal bewaren, persoonlijke info strippen, lokale AI-optie.
- Wat wij hiervan zouden willen lenen:
  - demo-data om het product direct begrijpelijk te maken;
  - compare dates als kernfeature;
  - context-notities naast bloedwaarden;
  - "protocol overlay": wanneer begon ik met supplement/medicatie/interventie en veranderde een waarde daarna?
  - privacy-first ontwerp als productwaarde;
  - geen account als concept voor persoonlijke/local-first versie, of minstens heel duidelijke data-controle.
- Wat waarschijnlijk niet in v1 moet:
  - AI-persona's of debatten;
  - DNA-import;
  - wearable-integraties;
  - multi-device encrypted sync;
  - 287+ markers volledig uitwerken.

#### Feature teardown: Guava Health

- Bron: https://guavahealth.com/
- Positionering: bredere personal health tracker, niet alleen bloedwaarden.
- Kernbelofte:
  - alle health records op een plek;
  - data uit patient portals, PDFs/foto's, symptomen, medicatie en devices combineren;
  - patronen en triggers ontdekken;
  - consulten voorbereiden met een printbare samenvatting.
- Sterke features:
  - lab results, symptoms, medications, cycle/pregnancy, imaging, doctor notes, device metrics en emergency info;
  - upload van foto's/PDF's of sync uit health portals;
  - extractie uit duizenden formats en meerdere talen;
  - health insights: triggers en treatment evaluation;
  - visit prep: samenvatting voor arts;
  - quick search door hele health history;
  - koppeling met veel health providers en fitness devices.
- Wat wij hiervan zouden willen lenen:
  - consultvoorbereiding als duidelijke workflow;
  - notities rond symptomen, medicatie en levensstijl;
  - snelle zoekfunctie door resultaten/documenten/notities;
  - "timeline" van health events, niet alleen grafieken;
  - printbare samenvatting.
- Wat waarschijnlijk niet in v1 moet:
  - provider portal integrations;
  - imaging/MRI/CT;
  - emergency card;
  - volledige chronic illness suite.

#### Feature teardown: InsideTracker

- Bron: https://info.insidetracker.com/blood-results-upload
- Positionering: biomarkeranalyse met persoonlijke optimalisatie en Action Plan.
- Kernbelofte:
  - bestaande bloedtestdata uploaden;
  - decennia aan bloeddata tracken;
  - tot 48 biomarkers analyseren;
  - persoonlijke optimale zones tonen, niet alleen generieke normaalranges;
  - nutrition, exercise, supplement en lifestyle action plan maken.
- Sterke features:
  - duidelijke stapflow: subscription, profiel, upload/manual entry, analyse/action plan;
  - profiel met voeding, supplementen, beweging en lifestyle;
  - optimal/suboptimal markers;
  - healthspan-categorieën zoals heart health, inflammation, metabolism, hormone balance, endurance;
  - wearable sync en DNA-kit als uitbreidingen.
- Wat wij hiervan zouden willen lenen:
  - categorieën/groepen voor biomarkers;
  - persoonlijk profiel als contextlaag;
  - "actieplan" als niet-medische opvolglijst: vragen voor arts, te bespreken waarden, volgende testdatum;
  - optimal vs normal als concept, maar voorzichtig en transparant.
- Wat waarschijnlijk niet in v1 moet:
  - aanbevelingen over supplementen/fitness als medisch advies;
  - DNA-kit;
  - commercieel subscription-model;
  - volledige healthspan scoring.

#### Feature teardown: Apple Health Records

- Bron: https://support.apple.com/en-ie/105016
- Positionering: officiële health records in de Health app.
- Kernbelofte:
  - gegevens van healthcare provider veilig en automatisch naar iPhone downloaden;
  - records bekijken per categorie of organisatie;
  - belangrijke labresultaten pinnen;
  - derde apps gecontroleerd toegang geven.
- Sterke features:
  - provider-account als bron van waarheid;
  - lab results als aparte health category;
  - pin important lab results;
  - permissions per categorie;
  - keuze tussen current records en current+future records;
  - mogelijkheid om organisatie en records te verwijderen.
- Wat wij hiervan zouden willen lenen:
  - "pinnen" van belangrijke waarden;
  - records groeperen per bron/labo/organisatie;
  - granular permissions als we ooit delen bouwen;
  - verwijderen/exporteren als kernrecht van de gebruiker;
  - duidelijke scheiding tussen bron-document en afgeleide gestructureerde waarden.
- Wat waarschijnlijk niet in v1 moet:
  - provider-integraties;
  - toegang voor third-party apps;
  - automatische health-record downloads.

#### Beste features om zelf te bouwen

- V1 kern:
  - login of local-first persoonlijke omgeving;
  - laboresultaat aanmaken met datum, labo, document en notities;
  - biomarkerwaarden manueel invoeren;
  - biomarker catalogus met naam, categorie, eenheid en referentierange;
  - status per waarde: laag, normaal, hoog, onbekend;
  - trendgrafiek per biomarker;
  - compare dates tussen twee bloedtesten;
  - pin/favoriet belangrijke biomarkers;
  - notities rond context: slaap, voeding, training, supplementen, medicatie, klachten;
  - PDF/CSV export voor consult;
  - reminder voor volgende bloedtest;
  - privacy: alles achter login, exporteerbaar en verwijderbaar.
- V2:
  - PDF upload en semi-automatische extractie;
  - unit converter;
  - interventie/protocol overlays op grafieken;
  - consultvoorbereiding met vragenlijst;
  - document OCR review screen: gebruiker bevestigt waarden voor opslag;
  - taggen van waarden als "bespreken met arts".
- Later:
  - AI-uitleg per marker;
  - wearable import;
  - secure share link;
  - multi-profile voor familie;
  - geavanceerde privacy/local-first sync;
  - provider-integraties.

#### Productprincipe voor ons

We bouwen geen diagnosemachine.

We bouwen een persoonlijk ordenings- en opvolgsysteem:

- begrijpen wat er gemeten is;
- zien wat verandert;
- context bewaren;
- vragen voorbereiden;
- documenten en waarden terugvinden;
- controle houden over gevoelige data.

## Terugkerende Productpatronen

- Marketplace:
  - aanbod;
  - aanbieders;
  - zoek/filter;
  - detailpagina;
  - booking/aanvraag/checkout;
  - reviews of trust-signalen;
  - adminmoderatie.

- SaaS:
  - onboarding;
  - teams/accounts;
  - billing/trial;
  - dashboard;
  - notificaties;
  - API/webhooks;
  - usage/history/logs.

- Admin/backoffice:
  - rollen en rechten;
  - CRUD/resources;
  - statusflows;
  - exports/imports;
  - rapportage;
  - audit/history.

- Integratieplatform:
  - externe API;
  - webhooks;
  - retries;
  - queues;
  - status tracking;
  - mapping/config per klant of partner.

- B2B-platform:
  - klantafhankelijke prijzen;
  - klantgroepen;
  - assortiment per klant;
  - orderhistoriek;
  - facturen;
  - ERP/CRM/PIM sync.

- Verenigingsplatform:
  - leden;
  - gelinkte personen/gezinsleden;
  - activiteiten;
  - inschrijvingen;
  - betalingen;
  - tickets/QR-codes;
  - aanwezigheden/toegangscontrole;
  - adminpaneel.

- Productconfigurator:
  - producttypes;
  - stapsgewijze keuzes;
  - regels/validatie;
  - prijsberekening;
  - winkelmand;
  - dealer/B2B flow;
  - later offerte/orderverwerking.

- Ticketing/eventplatform:
  - event/productbeheer;
  - ticketshop;
  - seating plans;
  - betalingen;
  - QR/access control;
  - piekverkoop/waiting room;
  - vouchers/donaties;
  - CRM en segmentatie;
  - rapportering;
  - externe integraties.

- API-first SaaS:
  - Laravel API als kern;
  - web app als aparte frontend;
  - mobiele app;
  - externe kanalen;
  - webhooks/Zapier;
  - data consistency;
  - team- en releaseprocessen.

- Community/rankingplatform:
  - deelnemers;
  - wedstrijden/toernooien;
  - scores;
  - ELO/rankingregels;
  - rollen en rechten;
  - publieke leaderboards;
  - adminpaneel.

- Facility/workflow platform:
  - meldingen;
  - QR-code intake;
  - sites/gebouwen/ruimtes/assets;
  - taken en statussen;
  - comments en notificaties;
  - service providers;
  - documenten;
  - rapportering;
  - externe integraties/webhooks.

- Renovatie/lead-matching platform:
  - intakevragen;
  - partnercontext;
  - matching met leveranciers/aannemers;
  - offerteflow;
  - opvolging per dossier;
  - interne backoffice;
  - communicatie en trust-signalen.

- IoT/backoffice platform:
  - devices;
  - ruimtes/locaties;
  - schedules;
  - externe API-integraties;
  - event/alert handling;
  - firmware/status monitoring;
  - admin tools;
  - queues en retries.

- Learning/security awareness platform:
  - organisaties/teams;
  - gebruikers;
  - training modules;
  - progress tracking;
  - simulaties;
  - scores/risicoprofielen;
  - rapportering;
  - SSO;
  - partner/reseller beheer.

- Jobbank/sollicitatieplatform:
  - doelgroepen met aparte rollen;
  - organisaties/werkgevers;
  - vacatures/jobs;
  - sollicitaties;
  - matching/statussen;
  - contracten of afspraken;
  - facturatie;
  - e-mail automation;
  - dashboards per rol.

- Abonnementen- en pasplatform:
  - aankoop/checkout;
  - activatie;
  - fysieke en digitale passen;
  - klantenzone;
  - aanbod/catalogus;
  - voordelen;
  - verlengingen;
  - marketing automation;
  - data dashboards.

- Survey/assessment/reporting platform:
  - licenties;
  - organisaties en teams;
  - vragenlijsten;
  - externe survey-integratie;
  - drempelregels;
  - aggregatie/anonimisering;
  - PDF-rapporten;
  - beheerportaal.

- Community matching platform:
  - intake van vraag/probleem;
  - wachtrij;
  - vrijwilligers/experten;
  - matching;
  - contactflow;
  - resultaatsregistratie;
  - kennisdatabank;
  - community beheer.

- Personal health data dashboard:
  - laboresultaten;
  - documenten/PDF uploads;
  - biomarker normalisatie;
  - eenheden en referentieranges;
  - trends/grafieken;
  - notities en context;
  - reminders;
  - rapport/export voor consult;
  - privacy en lokale/versleutelde opslag.

## Mogelijke Projectrichtingen

- Belgisch niche-bookingplatform.
- Marketplace rond lokale diensten/workshops/experten.
- B2B bestelportaal met simpele ERP-achtige backend.
- Multi-tenant mini-CMS voor lokale ondernemers.
- AI-tool die Laravel/dev workflows automatiseert.
- Meldings- of intakeplatform met slimme routing.
- Logistiek of planning-platform rond lokale processen.
- Verenigingsplatform voor leden, activiteiten en betalingen.
- Niche-marketplace met credits, spotlight en moderatie.
- Productconfigurator met cart en offerteflow.
- Ticketing/eventplatform met QR-toegang en rapportering.
- Community rankingplatform met toernooien en ELO.
- API-first vastgoed/lead-matching platform.
- Facility meldingsplatform met QR-codes, taken en assets.
- Renovatie- of offerteplatform met partnerflows en aannemer-matching.
- IoT/backoffice platform dat externe apparaten en agenda's aanstuurt.
- Learning/progress-platform met modules, scores en organisatie-rapportering.
- Jobbank voor een lokale actie met leerlingen, scholen, werkgevers en admins.
- Abonnementenplatform met activatie, klantenzone en voordelen.
- Survey/reporting tool met licenties, anonimiseren en PDF-rapporten.
- Repair/matching platform voor lokale vrijwilligers en herstellers.
- Gedeeld leden- en vormingsplatform met betalingen, attesten en exports.
- Persoonlijk bloedwaarden-dashboard met uploads, trends, notities, reminders en consult-export.

## Belangrijk Voor Ons Eigen Project

Het project moet relevant zijn omdat het een echt probleem en een echte workflow heeft. Laravel is vooral interessant wanneer er achter de schermen regels, rollen, data, integraties en processen zitten. De beste inspiratie komt dus niet uit "mooie websites", maar uit platformen waar gebruikers iets moeten beheren, boeken, aanvragen, betalen, opvolgen of automatiseren.

## Laravel Capability Lens

Kernzin:

Met Laravel kan je een digitaal systeem bouwen dat mensen, data, regels en acties samenbrengt in een beheerbaar platform.

Nog scherper:

Laravel wordt interessant wanneer er achter een simpele voorkant een administratieve motor moet draaien.

Een goed Laravel-probleem heeft meestal deze ingrediënten:

- data die gestructureerd moet worden;
- gebruikers met verschillende rollen;
- regels die bepalen wie wat mag doen;
- formulieren of intakeflows;
- statussen en opvolging;
- communicatie via e-mail of notificaties;
- bestanden, attesten, exports of rapporten;
- betalingen of facturatie;
- koppelingen met externe systemen;
- automatische taken op de achtergrond.

Met Laravel kun je onder andere:

- gebruikers en organisaties beheren;
- rollen en rechten instellen;
- login, registratie, wachtwoordreset en SSO ondersteunen;
- formulieren en intakeflows bouwen;
- dashboards en adminpanelen maken;
- CRUD/backoffice workflows bouwen;
- statusflows modelleren;
- betalingen integreren;
- facturen, attesten, exports en PDF's genereren;
- notificaties en e-mails automatiseren;
- API's bouwen;
- externe systemen koppelen;
- webhooks ontvangen en verwerken;
- wachtrijen en achtergrondtaken draaien;
- bestanden en uploads beheren;
- rapportering en dataverwerking opzetten;
- multi-tenant of multi-organisatie platformen maken.

Wanneer Laravel logisch voelt:

- iemand beheert iets;
- iemand vraagt iets aan;
- iemand keurt iets goed;
- iemand betaalt iets;
- iemand krijgt automatisch een mail, attest, factuur of rapport;
- iemand moet een status kunnen opvolgen;
- verschillende gebruikers zien andere schermen;
- een admin moet uitzonderingen kunnen oplossen;
- data moet later exporteerbaar of rapporteerbaar zijn;
- het proces is nu verspreid over Excel, e-mail, WhatsApp, formulieren of losse tools.

Laravel is minder interessant wanneer:

- het alleen een statische marketingwebsite is;
- er geen login nodig is;
- er geen echte workflow achter zit;
- er geen rollen, statussen, betalingen, rapporten of integraties zijn;
- de inhoud alleen gepubliceerd moet worden zoals in een klassieke CMS-site.

Projecttest:

Als we een projectidee kunnen omschrijven als:

"Mensen doen nu X via losse tools, maar eigenlijk moet dat een platform worden waar rollen, data, regels, opvolging, communicatie en automatisering samenkomen."

Dan zitten we in Laravel-terrein.
