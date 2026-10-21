#!/bin/sh
set -e

# Bind-mounted source is owned by root; php-fpm workers run as www-data and
# must be able to write compiled views, cache and logs.
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

exec docker-php-entrypoint "$@"
