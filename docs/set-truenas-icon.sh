#!/bin/sh
# Zet het custom-app-icoon op de JUISTE (per-app) plek op TrueNAS SCALE.
#
# Custom apps hebben geen icoon-veld in de compose-YAML; de Apps-UI leest het
# uit /mnt/.ix-apps/app_configs/<app>/metadata.yaml. NIET het globale
# /mnt/.ix-apps/metadata.yaml gebruiken: dat wordt bij elke deploy/update
# opnieuw opgebouwd en wist een handmatige icon-regel weer (zie de
# lovebox-fix in PR arjankapteijn/lovebox#10).
#
# Draai dit ALS ROOT op de TrueNAS-host zelf (bijv. via de web-shell of ssh).
#
# Gebruik:
#   ./set-truenas-icon.sh [app-naam] [icoon-url]
#
# Standaard app-naam: sauerland-games
# Standaard icoon-url: docs/icon.svg uit deze repo op main
#
# Herbruikbaar voor elke andere custom app door de argumenten mee te geven,
# bijv.: ./set-truenas-icon.sh lovebox https://.../lovebox/main/docs/icon.png

set -eu

APP="${1:-sauerland-games}"
ICON_URL="${2:-https://raw.githubusercontent.com/arjankapteijn/sauerland-games/main/docs/icon.svg}"
METADATA="/mnt/.ix-apps/app_configs/${APP}/metadata.yaml"

if [ ! -f "$METADATA" ]; then
    echo "Kan $METADATA niet vinden — bestaat de app '$APP' al (minstens één keer opgestart)?" >&2
    exit 1
fi

BACKUP="${METADATA}.bak.$(date +%Y%m%d%H%M%S)"
cp "$METADATA" "$BACKUP"
echo "Backup weggeschreven naar $BACKUP"

TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

# Puur awk (geen sed \s/GNU-extensies) zodat dit hetzelfde gedrag heeft op elk
# systeem. De indentatie van de bestaande "metadata":/"icon":-regel wordt
# overgenomen, in plaats van een vaste inspringing aan te nemen — de exacte
# structuur van dit per-app-bestand kan per TrueNAS-versie verschillen.
if grep -q '"icon":' "$METADATA"; then
    HAS_ICON=1
else
    HAS_ICON=0
fi

awk -v icon="${ICON_URL}" -v has_icon="${HAS_ICON}" '
    has_icon && /"icon":/ {
        match($0, /^[ \t]*/)
        print substr($0, RSTART, RLENGTH) "\"icon\": \"" icon "\""
        done = 1
        next
    }
    !has_icon && /"metadata":[ \t]*$/ {
        print
        match($0, /^[ \t]*/)
        print substr($0, RSTART, RLENGTH) "  \"icon\": \"" icon "\""
        done = 1
        next
    }
    { print }
    END { if (!done) exit 1 }
' "$METADATA" > "$TMP" || {
    echo "Kon geen \"metadata\":- of \"icon\":-regel vinden om te wijzigen in $METADATA." >&2
    echo "Niets toegepast — zet het icoon handmatig, backup staat op $BACKUP" >&2
    exit 1
}

if ! grep -qF "\"icon\": \"${ICON_URL}\"" "$TMP"; then
    echo "Onverwacht: de icon-regel staat niet in het resultaat — niets toegepast. Backup staat op $BACKUP" >&2
    exit 1
fi

if ! python3 -c "import sys, yaml; yaml.safe_load(open(sys.argv[1]))" "$TMP" 2>/dev/null; then
    echo "ONGELDIGE YAML na de wijziging — niets toegepast. Backup staat nog op $BACKUP" >&2
    exit 1
fi

cp "$TMP" "$METADATA"
echo "Icoon gezet op ${ICON_URL} in ${METADATA}."
echo "Ga nu in de TrueNAS-UI naar Apps -> ${APP} -> Edit -> Save (zonder wijzigingen)"
echo "zodat de middleware de metadata opnieuw inleest. Daarna evt. een harde"
echo "browser-refresh (Ctrl/Cmd+Shift+R) tegen de icoon-cache."
