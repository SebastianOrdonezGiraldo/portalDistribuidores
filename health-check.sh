#!/usr/bin/env bash
# =============================================================================
# health-check.sh - Portal Distribuidores
# Verifica que la aplicación Laravel responde correctamente.
#
# Uso:
#   bash health-check.sh [URL]
#
# Ejemplo:
#   bash health-check.sh https://pedidos.importcorporalmedical.com
#   bash health-check.sh https://staging-pedidos.importcorporalmedical.com
#
# Retorna 0 si la aplicación está sana, 1 si no lo está.
# =============================================================================

set -euo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_URL="${1:-}"
MAX_RETRIES=10
RETRY_DELAY=10
TIMEOUT=20

if [[ -z "$APP_URL" ]]; then
    # Intentar leer la URL desde .env si existe
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    if [[ -f "$SCRIPT_DIR/.env" ]]; then
        APP_URL="$(grep -E '^APP_URL=' "$SCRIPT_DIR/.env" | head -n1 | cut -d= -f2- | tr -d '"' || true)"
    fi
fi

[[ -n "$APP_URL" ]] || { echo -e "${RED}ERROR: Debes proporcionar una URL. Uso: bash health-check.sh <url>${NC}" >&2; exit 1; }

echo -e "${YELLOW}Verificando salud de: ${APP_URL}${NC}"
echo "Máximo ${MAX_RETRIES} intentos con ${RETRY_DELAY}s de espera entre cada uno."
echo ""

for attempt in $(seq 1 "$MAX_RETRIES"); do
    status_code="$(curl \
        --silent \
        --show-error \
        --output /dev/null \
        --write-out "%{http_code}" \
        --max-time "$TIMEOUT" \
        --location \
        "$APP_URL" 2>/dev/null || echo "000")"

    echo -e "Intento ${attempt}/${MAX_RETRIES}: HTTP ${status_code}"

    case "$status_code" in
        200|301|302|303)
            echo -e "\n${GREEN}✓ Aplicación respondiendo correctamente (HTTP ${status_code})${NC}"
            exit 0
            ;;
        000)
            echo -e "  ${YELLOW}→ Sin respuesta (timeout o error de red)${NC}"
            ;;
        5*)
            echo -e "  ${RED}→ Error del servidor (HTTP ${status_code})${NC}"
            ;;
        *)
            echo -e "  ${YELLOW}→ Estado inesperado (HTTP ${status_code})${NC}"
            ;;
    esac

    if [[ "$attempt" -lt "$MAX_RETRIES" ]]; then
        sleep "$RETRY_DELAY"
    fi
done

echo -e "\n${RED}✗ Health check fallido: ${APP_URL} no respondió correctamente después de ${MAX_RETRIES} intentos.${NC}"
exit 1
