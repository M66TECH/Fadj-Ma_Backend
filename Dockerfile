# syntax=docker/dockerfile:1

# --- Stage 1: PHP dependencies (Composer) ---
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# --- Stage 2: Node build (si assets Vite, optionnel) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* yarn.lock* pnpm-lock.yaml* ./
RUN if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then npm ci; else npm i --no-audit --no-fund; fi || true
COPY resources/ resources/
COPY vite.config.js .
RUN npm run build || echo "Skip assets build"

# --- Stage 3: Runtime PHP 8.2 Apache (public) ---
FROM php:8.2-apache

# Système & extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev netcat-traditional \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Config Apache: DocumentRoot -> /app/public
ENV APACHE_DOCUMENT_ROOT=/app/public
ENV APACHE_SERVER_NAME=localhost
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf && \
    printf "<Directory /app/public>\n    AllowOverride All\n    Require all granted\n</Directory>\nServerName ${APACHE_SERVER_NAME}\n" > /etc/apache2/conf-available/laravel.conf && \
    a2enconf laravel

# Config Apache: écouter sur $PORT (Railway/Render fournit $PORT)
ENV PORT=8080
RUN sed -ri -e 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -ri -e 's/:80>/:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

WORKDIR /app

# Copie de l'application
COPY . /app

# Vendors de l'étape vendor
COPY --from=vendor /app/vendor /app/vendor

# Assets (si construits)
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

EXPOSE 8080

CMD ["/entrypoint.sh"]