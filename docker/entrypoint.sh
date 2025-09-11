#!/usr/bin/env bash
set -euo pipefail

# Attendre la DB si variables présentes
if [[ -n "${DB_HOST:-}" ]]; then
  echo "Attente de la base de données ${DB_HOST}:${DB_PORT:-5432}..."
  for i in {1..30}; do
    if nc -z ${DB_HOST} ${DB_PORT:-5432}; then
      echo "Base de données disponible."
      break
    fi
    sleep 1
  done
fi

# Génération clé si manquante (non-interactif)
if [[ -z "${APP_KEY:-}" || "${APP_KEY}" == "" ]]; then
  php artisan key:generate --force --no-interaction || true
fi

# Cache de config/routes/views
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan optimize || true

# Lien storage si absent
php artisan storage:link || true

# Migrations
php artisan migrate --force || true

# Lancer Apache (image Railway heroku-php-apache)
exec /usr/local/bin/heroku-php-apache2 public/ 