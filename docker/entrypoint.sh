#!/usr/bin/env bash
set -euo pipefail

# Attendre la DB si variables présentes
if [[ -n "${DB_HOST:-}" ]]; then
  echo "Attente de la base de données ${DB_HOST}:${DB_PORT:-5432}..."
  for i in {1..30}; do
    if getent hosts "${DB_HOST}" >/dev/null 2>&1; then
      if nc -z "${DB_HOST}" "${DB_PORT:-5432}"; then
        echo "Base de données disponible."
        break
      fi
    fi
    sleep 1
  done
fi

# Génération clé si manquante (non-interactif)
if [[ -z "${APP_KEY:-}" || "${APP_KEY}" == "" ]]; then
  php artisan key:generate --force --no-interaction || true
fi

# Créer les répertoires de cache manquants
mkdir -p /app/storage/framework/cache/data
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/views
mkdir -p /app/bootstrap/cache

# Permissions storage/cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Clear caches
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Rebuild caches sans forcer view:cache
php artisan config:cache || true
php artisan route:cache || true
# Ne pas exécuter view:cache pour éviter "View path not found" si pas de vues

# Lien storage si absent
php artisan storage:link || true

# Migrations
php artisan migrate --force || true

# Lancer Apache sur le port fourni
export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data
export APACHE_PID_FILE=/var/run/apache2/apache2.pid
export APACHE_LOCK_DIR=/var/lock/apache2
export APACHE_LOG_DIR=/var/log/apache2

exec apache2-foreground
