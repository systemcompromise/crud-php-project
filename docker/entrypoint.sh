#!/bin/bash
set -e

# ============================================
# Entrypoint — Railway Dynamic PORT Support
# ============================================

PORT="${PORT:-80}"
echo "[entrypoint] Starting on PORT=$PORT"

# --------------------------------------------
# FIX: Apache MPM conflict
# Hapus semua symlink MPM yang aktif, lalu
# aktifkan hanya mpm_prefork (wajib untuk mod_php)
# --------------------------------------------
echo "[entrypoint] Fixing Apache MPM..."

# Hapus semua load file MPM yang mungkin aktif
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf
rm -f /etc/apache2/mods-enabled/mpm_prefork.load
rm -f /etc/apache2/mods-enabled/mpm_prefork.conf

# Aktifkan HANYA mpm_prefork
ln -sf /etc/apache2/mods-available/mpm_prefork.load \
       /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf \
       /etc/apache2/mods-enabled/mpm_prefork.conf

echo "[entrypoint] MPM prefork activated."

# --------------------------------------------
# Update Apache port sesuai Railway $PORT
# --------------------------------------------
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

echo "[entrypoint] Apache configured on PORT=$PORT"

exec apache2-foreground