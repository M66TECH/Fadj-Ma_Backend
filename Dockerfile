# syntax=docker/dockerfile:1

# --- Stage 1: PHP dependencies (Composer) ---
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# --- Stage 2: Node build (si assets Vite, optionnel) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* yarn.lock* pnpm-lock.yaml* ./
RUN npm ci || npm i
COPY resources/ resources/
COPY vite.config.js .
RUN npm run build || echo "Skip assets build"

# --- Stage 3: Runtime PHP 8.2 Apache ---
FROM ghcr.io/railwayapp-templates/heroku-php-apache:8.2

# Paquets système utiles
RUN install-packages git unzip libpq-dev libzip-dev && \
    docker-php-ext-install pdo pdo_pgsql

WORKDIR /app

# Copie de l'application
COPY . /app

# Copie des vendors composer depuis l'étape vendor
COPY --from=vendor /app/vendor /app/vendor

# Copie des assets (si construits)
COPY --from=assets /app/public/build /app/public/build

# Permissions storage/cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Variables d'environnement par défaut
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_DRIVER=file \
    QUEUE_CONNECTION=sync \
    SESSION_DRIVER=cookie \
    FILESYSTEM_DISK=public

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Exposer le port Apache
EXPOSE 8080

# Démarrage
CMD ["/entrypoint.sh"] 