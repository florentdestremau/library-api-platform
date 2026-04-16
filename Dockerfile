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
# Stage 2 : Builder PHP — installation des dépendances Symfony
# ============================================================
FROM php:8.4-cli-alpine AS php-builder

# Utiliser install-php-extensions pour un install rapide des extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_sqlite zip intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/backend

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY backend/ ./
# Supprimer les clés JWT de dev — elles seront régénérées au premier démarrage
RUN rm -f config/jwt/private.pem config/jwt/public.pem
RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 3 : Image finale — PHP-FPM + Nginx
# ============================================================
FROM php:8.4-fpm-alpine AS final

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_sqlite zip intl opcache

RUN apk add --no-cache nginx supervisor openssl su-exec

# Configuration OPcache
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
} >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

COPY --from=php-builder /app/backend ./backend

RUN mkdir -p /app/backend/public/app
COPY --from=frontend-builder /app/frontend/dist/ ./backend/public/app/

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

VOLUME ["/storage"]
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
