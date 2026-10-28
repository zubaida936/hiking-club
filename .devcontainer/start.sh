#!/usr/bin/env bash
# Runs on every codespace start: fix Docker networking, point APP_URL at this codespace, start the stack.
set -euo pipefail
cd "$(dirname "$0")/.."

# The Codespaces image keeps legacy iptables rules that drop traffic from Compose networks (only docker0
# is allowed), so containers can't reach the internet. The rules are lost on restart: add them each time.
if command -v iptables-legacy >/dev/null && sudo iptables-legacy -S FORWARD 2>/dev/null | grep -q '^-P FORWARD DROP'; then
    for dir in -i -o; do
        sudo iptables-legacy -C FORWARD "$dir" br-+ -j ACCEPT 2>/dev/null \
            || sudo iptables-legacy -I FORWARD 1 "$dir" br-+ -j ACCEPT
    done
fi

[ -f .env ] || cp .env.example .env

if [ -n "${CODESPACE_NAME:-}" ]; then
    url="https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
    sed -i "s#^APP_URL=.*#APP_URL=${url}#" .env
fi

# Everything except "node": the demo serves built assets, visitors can't reach the Vite dev server.
docker compose up -d app queue scheduler nginx mysql redis mailpit phpmyadmin

# A cached config would keep an old APP_URL.
if [ -f vendor/autoload.php ]; then
    docker compose exec -T app php artisan config:clear >/dev/null || true
fi

echo "Website: $(grep '^APP_URL=' .env | cut -d= -f2-)  (set port 8080 to Public in the PORTS tab to share it)"
