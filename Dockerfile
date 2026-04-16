# ============================================================
# Stage 1 : Builder Node — build du frontend React
# ============================================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app/frontend

RUN corepack enable && corepack prepare pnpm@latest --activate

COPY frontend/package.json frontend/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY frontend/ ./
ENV VITE_API_URL=""
RUN pnpm run build

# ============================================================
# Stage 2 : Builder PHP — dépendances Symfony
# ============================================================
FROM php:8.4-cli-alpine AS php-builder

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_sqlite zip intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/backend

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY backend/ ./
# Supprimer les clés JWT de dev — régénérées au démarrage
RUN rm -f config/jwt/private.pem config/jwt/public.pem
RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 3 : Image finale — FrankenPHP
# ============================================================
FROM dunglas/frankenphp:php8.4-alpine AS final

# Extensions PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_sqlite zip intl opcache

RUN apk add --no-cache openssl su-exec

# OPcache
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
} >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

# Backend Symfony
COPY --from=php-builder /app/backend ./backend

# Frontend buildé
RUN mkdir -p /app/backend/public/app
COPY --from=frontend-builder /app/frontend/dist/ ./backend/public/app/

# Caddyfile
COPY docker/Caddyfile /app/Caddyfile

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/storage"]
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
