#!/bin/bash
set -e

SERVICE_ROOT=/app
SELF_SIGNED_KEY=/etc/ssl/private/apache-selfsigned.key
SELF_SIGNED_CERT=/etc/ssl/certs/apache-selfsigned.crt

if [ ! -f "$SELF_SIGNED_KEY" ] || [ ! -f "$SELF_SIGNED_CERT" ]; then
    # Generate a self-signed cert to support SSL connections
    openssl req -x509 -nodes -days 358000 -newkey rsa:2048 -keyout "$SELF_SIGNED_KEY" -out "$SELF_SIGNED_CERT" -subj "/O=Your vACC/CN=Stands"
fi

if [ -z "$APP_KEY" ] && [ ! -f "$SERVICE_ROOT/.env" ]; then
    cp container/default.env .env
    php artisan key:generate
fi

# Adopt the Passport signing key into storage/oauth-keys, or create the first key
# on a fresh install. It does nothing once the store holds keys. Run as root, it
# can read a root-owned 3.x key, and it gives the new files to the owner of the
# storage directory so Apache can read them.
php artisan signing-keys:import

exec docker-php-entrypoint "$@"
