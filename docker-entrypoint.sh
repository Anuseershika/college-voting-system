#!/bin/sh
set -e

PORT="${PORT:-80}"

# Configure Apache to listen on dynamic port specified by Render ($PORT)
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec "$@"
