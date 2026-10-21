#!/bin/sh
set -e

# FPM workers and CLI both run as root in dev (see dev-fpm.conf). Normalize
# ownership of files left behind by earlier runs under another user.
chown -R root:root storage bootstrap/cache 2>/dev/null || true
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

exec docker-php-entrypoint "$@"
