# Modulo CMS — contenuti, struttura editoriale e media

## In parole semplici

Il modulo **CMS** (Content Management System) è l’area di Laraplate dedicata a **creare, organizzare e pubblicare** contenuti: testi, relazioni tra categorie e tag, modelli di pagina (“template”), contributori, luoghi geografici e preset riutilizzabili. È pensato per team editoriali e per sviluppatori che devono esporre contenuti strutturati senza riscrivere ogni volta form e tabelle da zero.

Il CMS si appoggia al **Core** per utenti, permessi e infrastruttura comune.

## A chi serve

- **Redazione / marketing**: gestisce categorie, tag, schede contenuto, autori e luoghi; lavora principalmente tramite **Filament** nel cluster del modulo CMS (raggruppamento navigazione gestito dal plugin `Coolsam\Modules` nel panel `admin`).
- **Sviluppatore**: estende modelli, relazioni, policy e risorse Filament; collega il geocoding e la media library (Spatie Media Library, FFmpeg per elaborazioni avanzate dove abilitato).
- **Utente tecnico**: configura servizi di geocodifica e dipendenze PHP (estensioni `gd`, `exif`, opzionalmente `imagick`).

## Funzionalità principali

### Gestione contenuti e tassonomia

Attraverso le risorse Filament del modulo (cartella `Modules/CMS/app/Filament/Resources`) si gestiscono tipicamente:

- **Entities** — definizione di tipi di contenuto o schemi riusabili collegati al modello dinamico del dominio.
- **Contents** — istanze di contenuto basate su entity / template.
- **Templates** — modelli di presentazione o struttura per uniformare le pubblicazioni.
- **Categories**, **Tags** — organizzazione gerarchica o trasversale dei contenuti.
- **Contributors** — autori o firme editoriali.
- **Presets** — configurazioni rapide o bundle di campi predefiniti per accelerare la creazione contenuti.
- **Locations** — schede legate a indirizzi o coordinate, con supporto a servizi di geocodifica.

È presente anche un widget di riepilogo statistiche CMS (`CMSStatsWidget`) dove previsto dalla configurazione del panel.

### Geocodifica

Il modulo espone un contratto `IGeocodingService` e implementazioni come **Nominatim** (OpenStreetMap), con possibilità di estendere verso altri provider (es. Google Maps) seguendo la stessa interfaccia. Dal punto di vista operativo: quando un contenuto o una sede richiede coordinate, il sistema interroga il servizio configurato e memorizza il risultato nei modelli di dominio previsti.

### Media e file

Le dipendenze Composer del modulo includono **Spatie Laravel Media Library** e **php-ffmpeg**: ciò consente allegati, conversioni video/audio e derivazioni immagine in pipeline asincrone o controllate, secondo le convenzioni del progetto (spesso tramite **code** per non bloccare l’interfaccia).

### Integrazione Filament

`CMSPlugin` (`Modules\CMS\Filament\CMSPlugin`) implementa il plugin Filament del modulo così che risorse e pagine CMS vengano registrate nel panel principale in modo modulare, rispettando la configurazione `config/filament-modules.php` (modalità plugin/pannelli e cluster di navigazione).

## Come si usa in pratica

1. **Configurazione iniziale**: definire entity e template che riflettono i tipi di pubblicazione richiesti dal progetto (news, landing, schede prodotto, ecc.).
2. **Creazione contenuti**: redazione crea record in **Contents** scegliendo entity/template corretti; associa categorie, tag, autori e allegati.
3. **Luoghi**: per sedi o eventi, usare **Locations** e verificare che il provider di geocodifica sia raggiungibile e rispetti i termini di servizio del fornitore (Nominatim richiede uso corretto e caching lato applicazione dove possibile).
4. **Sviluppo front-end**: consumare i contenuti tramite API o viste del tema applicativo (il percorso esatto dipende dal tema e dalle route del progetto che espongono i modelli CMS).

## Dipendenze

- **Core**: obbligatorio per autenticazione, permessi e servizi trasversali.
- Estensioni PHP consigliate: `ext-gd`, `ext-exif`; `imagick` opzionale per elaborazioni avanzate.

## Nota per chi integra da zero

Il CMS di Laraplate è modulare: non è “solo un editor WYSIWYG”, ma un **sistema di tipi di contenuto** e relazioni. Prima di importare migliaia di record legacy, conviene allineare **entity**, **template** e **preset** al modello informativo desiderato, per evitare contenuti “orfani” o campi inconsistenti.
