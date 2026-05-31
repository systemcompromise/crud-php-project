#!/bin/bash
set -e

# Railway injects PORT env variable dynamically
# Update Apache to listen on that port
PORT=${PORT:-80}

echo "Starting PHP CRUD App on port $PORT"

# Update Apache ports configuration
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/\*:80/\*:$PORT/" /etc/apache2/sites-available/000-default.conf

# Execute the CMD
exec "$@"
