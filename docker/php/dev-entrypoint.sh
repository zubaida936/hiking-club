#!/bin/sh
set -e

# FPM workers and CLI both run as root in dev (see dev-fpm.conf). Normalize
# ownership of files left behind by earlier runs under another user.
chown -R root:root storage bootstrap/cache 2>/dev/null || true
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

# vendor/ is a named volume (see compose.yaml) shared by app/queue/scheduler;
# fill it on first start, letting only one container install at a time.
(
    flock 9
    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist
    fi
) 9>vendor/.install.lock

exec docker-php-entrypoint "$@"
