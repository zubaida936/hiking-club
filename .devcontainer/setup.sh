#!/usr/bin/env bash
# Runs once when the codespace is created: .env, app key, database with demo data, built assets.
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || cp .env.example .env

# The demo link is public: never keep the default admin password.
if ! grep -q '^ADMIN_PASSWORD=' .env; then
    echo "ADMIN_PASSWORD=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 16)" >> .env
fi

bash .devcontainer/start.sh

# app, queue and scheduler share the vendor volume and run composer install on first start.
echo "Waiting for composer install..."
for _ in $(seq 1 120); do
    if docker compose exec -T app test -f vendor/autoload.php 2>/dev/null \
        && ! docker compose exec -T app php artisan --version 2>&1 | grep -q 'Failed opening'; then
        busy=0
        for service in app queue scheduler; do
            docker compose exec -T "$service" pgrep -f 'composer install' >/dev/null 2>&1 && busy=1
        done
        [ "$busy" = 0 ] && break
    fi
    sleep 5
done

if ! grep -q '^APP_KEY=base64:' .env; then
    docker compose exec -T app php artisan key:generate --force
fi
docker compose exec -T app php artisan migrate --seed --force
docker compose exec -T app php artisan storage:link 2>/dev/null || true

docker compose run --rm -T node sh -c "npm ci --no-audit --no-fund && npm run build"
rm -f public/hot

docker compose exec -T app php artisan config:clear >/dev/null
docker compose restart queue scheduler

echo
echo "Demo ready."
echo "  Website: $(grep '^APP_URL=' .env | cut -d= -f2-)   (PORTS tab -> 8080 -> Port Visibility -> Public)"
echo "  CMS:     /admin   admin@hiking.test / $(grep '^ADMIN_PASSWORD=' .env | cut -d= -f2-)"
