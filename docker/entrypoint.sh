#!/bin/bash
set -e

# ============================================
# Entrypoint — Railway Dynamic PORT Support
# Railway meng-inject $PORT secara otomatis
# ============================================

PORT="${PORT:-80}"

echo "[entrypoint] Starting on PORT=$PORT"

# Update Apache ports.conf
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

# Update VirtualHost port
sed -i "s/*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground