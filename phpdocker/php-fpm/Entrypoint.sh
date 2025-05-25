#!/bin/sh
set -e

# Ensure log dir exists with correct permissions
mkdir -p /var/log
chown -R www-data:www-data /var/log

# Ensure cache dir exists with correct permissions
mkdir -p /application/var/cache
chown -R www-data:www-data /application/var/cache

# Ensure correct permissions for config dir (so that caching works)
chmod -R 777 /application/config

# Add safe directory to git config to prevent composer dump-autoload warnings
git config --global --add safe.directory /application

# Run the original entrypoint (if any) or PHP-FPM
exec "$@"