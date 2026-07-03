# Sauerland Games

Signal-gestuurd teamspel voor een mannenweekend: spelers melden zich aan via
een Signal-appje, komen random in een van twee teams, krijgen opdrachten
toegestuurd en sturen foto-bewijs terug naar hun teamgroep. Alleen de
organizers kunnen die met een 👍-reactie goedkeuren — de app herkent dat
automatisch, kent punten toe en deelt de nieuwe tussenstand met de hele groep.

Gebouwd voor het weekend in Assinghausen (Sauerland), maar generiek genoeg
voor elk weekend met twee teams en een jury.

[![CI](https://github.com/arjankapteijn/sauerland-games/actions/workflows/ci.yml/badge.svg)](https://github.com/arjankapteijn/sauerland-games/actions/workflows/ci.yml)
[![Publish Docker image](https://github.com/arjankapteijn/sauerland-games/actions/workflows/docker-publish.yml/badge.svg)](https://github.com/arjankapteijn/sauerland-games/actions/workflows/docker-publish.yml)
[![Versie](https://img.shields.io/github/v/tag/arjankapteijn/sauerland-games?sort=semver&label=versie&logo=github)](https://github.com/arjankapteijn/sauerland-games/tags)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6?logo=livewire&logoColor=white)
![PHPUnit](https://img.shields.io/badge/tested-PHPUnit-3776AB?logo=php&logoColor=white)
![Container](https://img.shields.io/badge/image-ghcr.io-2496ED?logo=docker&logoColor=white)

Deze repo is bewust **publiek-veilig**: alle persoonsgegevens (telefoon­nummers,
namen) komen uit `.env` of ontstaan pas in de database zodra iemand zich
aanmeldt. Er staat niets gevoeligs in git.

---

## Spelconcept

```
Aanmelden ──▶ Random team ──▶ Teamgroep + ──▶ Opdracht ──▶ Foto + #nummer ──▶ 👍 van ──▶ Score-update
(Signal-DM)    (Rood/Blauw)   hoofdgroep       binnen        in teamgroep      organizer   in hoofdgroep
```

- **Aanmelden**: stuur `MEEDOEN <naam>` als DM naar het bot-nummer. De app
  verdeelt automatisch in balans over de twee teams en voegt je toe aan de
  teamgroep + hoofdgroep.
- **Opdrachten** hebben elk een uniek nummer, punten en categorie. Zet je
  `#nummer` in het bijschrift van je foto zodat de app weet welke opdracht
  het is.
- **Goedkeuren**: alleen de organizers (jury) kunnen met een 👍-reactie op de
  foto punten toekennen. Dat gebeurt automatisch — geen aparte app of
  formulier nodig.
- **`STAND`** stuur je op elk moment naar de groep voor de actuele tussenstand
  en de nog openstaande opdrachten.
- **Snelheidsbonus**: het eerste team dat een opdracht binnen 30 minuten na
  vrijgave laat goedkeuren, krijgt +5 punten extra.
- **Geheime opdrachten** (`is_secret`) gaan maar naar één team tegelijk — het
  andere team weet niet dat ze bestaan.

### Opdrachten

24 opdrachten, geseed in [`ChallengeSeeder`](database/seeders/ChallengeSeeder.php):

| # | Categorie | Opdracht | Punten |
|---|-----------|----------|-------:|
| 1 | Eten & drinken | McDrive te voet | 10 |
| 2 | Eten & drinken | Onbekend biertje | 10 |
| 3 | Eten & drinken | Sauerlandse snack | 15 |
| 4 | Eten & drinken | Geen handen | 5 |
| 5 | Sportief & buiten | Bergtop selfie | 15 |
| 6 | Sportief & buiten | Blinde gids | 10 |
| 7 | Sportief & buiten | Bank af | 10 |
| 8 | Sportief & buiten | Zaklamptikkertje | 15 |
| 9 | Sociaal | Handtekeningenjacht | 10 |
| 10 | Sociaal | Straatzanger | 15 |
| 11 | Sociaal | Drie keer ruilen | 20 |
| 12 | Sociaal | Woordje Duits | 10 |
| 13 | Sociaal | Verkooppraatje | 15 |
| 14 | Creatief & in huis | Twijgentoren | 10 |
| 15 | Creatief & in huis | Levend standbeeld | 15 |
| 16 | Creatief & in huis | Linkshandig portret | 5 |
| 17 | Creatief & in huis | Recordpoging opruimen | 10 |
| 18 | Creatief & in huis | Groepschoreografie | 15 |
| 19 | Tussen de teams | Verrassingsontbijt | 10 |
| 20 | Tussen de teams | Pokerface | 10 |
| 21 | Nacht & verrassing | Middernachtmars | 20 |
| 22 | Nacht & verrassing | Geheime bonusopdracht A (alleen Rood) | 25 |
| 23 | Nacht & verrassing | Geheime bonusopdracht B (alleen Blauw) | 25 |
| 24 | Nacht & verrassing | Fotobom | 15 |

Nieuwe opdrachten toevoegen: rij in `ChallengeSeeder` + `php artisan db:seed --class=ChallengeSeeder`
(idempotent, dubbele nummers worden overgeslagen).

---

## Hoe werkt het onder de motorkap

| Onderdeel | Rol |
|-----------|-----|
| [`SignalGateway`](app/Services/Signal/SignalGateway.php) | HTTP-client rond [signal-cli-rest-api](https://github.com/bbernhard/signal-cli-rest-api): berichten/groepen versturen, inkomende berichten long-pollen |
| [`SignalMessageProcessor`](app/Signal/SignalMessageProcessor.php) + handlers | Herkennen van meedoen-appjes, foto-inzendingen, 👍-reacties en het `STAND`-commando |
| [`ScoringService`](app/Services/Game/ScoringService.php) | Punten toekennen/intrekken, snelheidsbonus, tussenstand-bericht |
| `signal:listen` | Langlopend Artisan-commando dat long-polt tegen `/v1/receive` |
| `game:release-due` / `game:expire-overdue` | Elke minuut via de scheduler: opdrachten op tijd vrijgeven/laten verlopen |
| Livewire-dashboard (`/dashboard`) | Live scorebord + handmatige goedkeur-fallback, PIN-gated |

**De kern van de goedkeuring**: een Signal-reactie verwijst zelf al naar het
exacte bronbericht (`targetAuthorNumber` + `targetSentTimestamp`). Bij een
foto-inzending slaat de app die twee velden op; komt er een 👍 van een
organizer-nummer binnen, dan wordt precies dat bericht opgezocht — geen
tekstherkenning in de reactie zelf nodig.

---

## Configuratie

Kopieer `.env.example` naar `.env` en vul in. `.env` staat in `.gitignore` —
**commit hem nooit**.

```bash
cp .env.example .env
php artisan key:generate
```

| Variabele | Omschrijving |
|-----------|--------------|
| `SIGNAL_API_URL` | Basis-URL van je signal-cli-rest-api-instantie |
| `SIGNAL_BOT_NUMBER` | Het Signal-nummer dat als bot optreedt (geregistreerd via `/v1/register`) |
| `SIGNAL_MAIN_GROUP_ID` | Group-id van de hoofdgroep — leeg totdat je `game:setup` hebt gedraaid |
| `SIGNAL_ORGANIZERS` | Telefoonnummers van de jury, comma-separated. **Alleen** reacties van deze nummers tellen mee |
| `SIGNAL_DASHBOARD_PIN` | Pincode voor `/dashboard` |

> **Privacy:** telefoonnummers zijn persoonsgegevens. Zet ze alleen in `.env`
> / de container-omgeving, nooit in code, commits of seeders.

---

## Lokaal draaien / testen

```bash
composer install
npm install && npm run build   # of: npm run dev
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev               # server + queue + vite, gelijktijdig
```

Tests + lint:

```bash
php artisan test --compact
vendor/bin/pint
```

Het Signal-gedeelte lokaal testen tegen een echte signal-cli-rest-api-instantie:

```bash
php artisan signal:listen --once   # haalt één keer nieuwe berichten op en verwerkt ze
```

Zet je eigen nummer in `SIGNAL_ORGANIZERS` om ook de 👍-goedkeuring te kunnen
testen, en stuur zelf `MEEDOEN <naam>` naar het bot-nummer om de aanmeldflow
te proberen.

---

## Deployen op TrueNAS (Docker + Nginx Proxy Manager)

Eén container, drie processen via `supervisord` (zie [Dockerfile](Dockerfile)):
de webserver (dashboard), de Laravel-scheduler en `signal:listen`. Anders dan
bij [arjankapteijn.nl](https://github.com/arjankapteijn/website) en
[lovebox](https://github.com/arjankapteijn/lovebox) is `read_only` hier niet
haalbaar — Laravel heeft `storage/`, `bootstrap/cache/` en de sqlite-db nodig
als schrijfbare paden; die staan in named volumes.

Het image wordt door **GitHub Actions** gebouwd en naar **ghcr.io** gepusht
(`ghcr.io/arjankapteijn/sauerland-games`, zie
[docker-publish.yml](.github/workflows/docker-publish.yml)).

### Eenmalig: inloggen bij ghcr.io

```bash
echo <TOKEN> | docker login ghcr.io -u arjankapteijn --password-stdin
```

Personal Access Token (classic) met alleen scope `read:packages`.

### Uitrollen als custom app

> **Valkuil (uit ervaring):** gebruik een **absoluut** `env_file`-pad, niet
> `.env`. TrueNAS draait "Install via YAML" vanuit een andere working
> directory (`/tmp`), dus een relatief pad geeft
> `env file /tmp/.env not found`. Maak eerst de map + `.env` aan:
>
> ```bash
> mkdir -p /mnt/<pool>/apps/sauerland-games
> cp .env.example /mnt/<pool>/apps/sauerland-games/.env
> nano /mnt/<pool>/apps/sauerland-games/.env   # SIGNAL_*, APP_KEY, DASHBOARD_PIN invullen
> ```

**Apps → Discover Apps → ⋮ → Install via YAML**, naam `sauerland-games`:

```yaml
services:
  sauerland-games:
    image: ghcr.io/arjankapteijn/sauerland-games:latest
    container_name: sauerland-games
    pull_policy: always
    restart: unless-stopped
    ports:
      - '8091:8080'
    env_file: /mnt/<pool>/apps/sauerland-games/.env   # absoluut pad!
    volumes:
      - sauerland-games-storage:/var/www/html/storage
      - sauerland-games-database:/var/www/html/database
    cap_drop: [ALL]
    security_opt:
      - no-new-privileges:true
    tmpfs:
      - /tmp:size=16m
    mem_limit: 512m
    pids_limit: 256

volumes:
  sauerland-games-storage:
  sauerland-games-database:
```

Na het opstarten éénmalig de Signal-groepen aanmaken:

```bash
docker compose exec sauerland-games php artisan game:setup
```

Zet de getoonde hoofdgroep-id daarna in `SIGNAL_MAIN_GROUP_ID` in de `.env`
en herstart de app.

### Custom-icoon in de Apps-UI

Het icoon ([`docs/icon.svg`](docs/icon.svg)) hoort in het **per-app**-bestand
`/mnt/.ix-apps/app_configs/sauerland-games/metadata.yaml`. **Niet** in het
globale `/mnt/.ix-apps/metadata.yaml` — dat wordt bij elke deploy/update
opnieuw opgebouwd en wist een handmatige icon-regel (zie de lovebox-fix).

Gebruik [`docs/set-truenas-icon.sh`](docs/set-truenas-icon.sh) — als root op
de TrueNAS-host zelf:

```bash
./set-truenas-icon.sh sauerland-games
```

Het script maakt eerst een backup, zoekt zelf de juiste plek (of die nu wel
of niet al een `icon`-regel heeft), en valideert de YAML vóór het toepast.
Herbruikbaar voor andere custom apps: `./set-truenas-icon.sh <app> <icon-url>`.

Daarna in de TrueNAS-UI: Apps → **sauerland-games** → **Edit** → **Save**
(zonder wijzigingen) om de metadata opnieuw te laten inlezen.

### Achter Nginx Proxy Manager (Let's Encrypt)

1. NPM → **Hosts → Proxy Hosts → Add**: domein bijv.
   `sauerland.arjankapteijn.nl`, scheme `http`, forward host = IP van je
   TrueNAS, forward port `8091`.
2. Tab **SSL**: *Request a new SSL certificate*, **Force SSL** aan.
3. DNS van het domein → je publieke IP, poort 80/443 geforward naar NPM.

### Updaten

Push naar `main` → GitHub Actions bouwt automatisch een nieuw `:latest`-image.
Klik daarna op **Update** bij de app in de TrueNAS-UI, of:

```bash
docker compose pull && docker compose up -d
```

---

## CI/CD & versiebeheer

Zelfde opzet als arjankapteijn.nl en lovebox:

- **`.github/workflows/ci.yml`** — bij elke push/PR: Pint + de volledige
  PHPUnit-suite.
- **`.github/workflows/docker-publish.yml`** — bij push naar `main` wordt
  automatisch de volgende **semver** afgeleid uit de commit-messages
  ([conventional commits](https://www.conventionalcommits.org/): `feat` →
  minor, `!`/`BREAKING CHANGE` → major, anders patch), een **git-tag** +
  **GitHub Release** aangemaakt, en het **image naar ghcr.io** gepusht.

Schrijf dus commit-messages als `feat: ...`, `fix: ...`, `chore: ...` — de
versie-bump hangt daarvan af. `composer.json`-versie wordt bewust niet
bijgewerkt; de git-tag is de single source of truth.
