#!/bin/sh
set -e

# Ensure log dir exists with correct permissions
mkdir -p /var/log
chown -R www-data:www-data /var/log

# Run the original entrypoint (if any) or PHP-FPM
exec "$@"