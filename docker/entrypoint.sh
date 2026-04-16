#!/bin/sh
set -e

# Configurer le répertoire de données SQLite dans /storage
DB_PATH="/storage/library.db"
export DATABASE_URL="sqlite:///${DB_PATH}"
export APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

# Créer le répertoire de stockage persistant
mkdir -p /storage/jwt
chown -R www-data:www-data /storage
chmod 0755 /storage

# Créer .env.local si absent (stocké dans /storage pour persister entre restarts)
ENV_FILE="/storage/.env.local"
if [ ! -f "${ENV_FILE}" ]; then
    if [ -z "$APP_SECRET" ]; then
        export APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    fi
    PASSPHRASE="${JWT_PASSPHRASE:-$(php -r "echo bin2hex(random_bytes(16));")}";

    cat > "${ENV_FILE}" << EOF
APP_ENV=prod
APP_SECRET=${APP_SECRET}
APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime
DATABASE_URL=${DATABASE_URL}
JWT_SECRET_KEY=/storage/jwt/private.pem
JWT_PUBLIC_KEY=/storage/jwt/public.pem
JWT_PASSPHRASE=${PASSPHRASE}
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-'^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'}
MAILER_DSN=${MAILER_DSN:-null://null}
LIBRARY_NAME=${LIBRARY_NAME:-Bibliothèque}
LIBRARY_EMAIL=${LIBRARY_EMAIL:-noreply@bibliotheque.fr}
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
EOF
fi

# Lier .env.local vers /app/backend
ln -sf "${ENV_FILE}" /app/backend/.env.local

# Générer les clés JWT dans /storage/jwt si absentes (persistent across restarts)
if [ ! -f "/storage/jwt/private.pem" ]; then
    echo "Génération des clés JWT (persistantes)..."
    PASSPHRASE=$(grep JWT_PASSPHRASE "${ENV_FILE}" | cut -d= -f2)
    openssl genrsa -passout "pass:${PASSPHRASE}" -out "/storage/jwt/private.pem" 4096
    openssl rsa -passin "pass:${PASSPHRASE}" -pubout -in "/storage/jwt/private.pem" -out "/storage/jwt/public.pem"
    chown www-data:www-data /storage/jwt/private.pem /storage/jwt/public.pem
    chmod 640 /storage/jwt/private.pem
fi

# Permissions cache/log Symfony
mkdir -p /app/backend/var/cache/prod /app/backend/var/log
chown -R www-data:www-data /app/backend/var

# Migrations
echo "Migrations..."
su-exec www-data php /app/backend/bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>/dev/null || true

# Créer admin si base vide
DB_USER_COUNT=$(su-exec www-data php /app/backend/bin/console doctrine:query:sql "SELECT COUNT(*) FROM user" --env=prod 2>/dev/null | grep -o '[0-9]\+' | tail -1 || echo "0")
if [ "${DB_USER_COUNT:-0}" = "0" ]; then
    echo "Création de l'admin..."
    su-exec www-data php /app/backend/bin/console app:create-admin \
        --email="${ADMIN_EMAIL:-admin@bibliotheque.fr}" \
        --password="${ADMIN_PASSWORD:-Admin1234!}" \
        --env=prod || true
fi

# Warm-up cache
echo "Cache warmup..."
su-exec www-data php /app/backend/bin/console cache:warmup --env=prod 2>/dev/null || true

echo "Démarrage FrankenPHP..."
exec su-exec www-data frankenphp run --config /app/Caddyfile
