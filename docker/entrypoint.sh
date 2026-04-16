#!/bin/sh
set -e

export DATABASE_URL="sqlite:////storage/library.db"
export APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"
export APP_ENV=prod
export JWT_SECRET_KEY=/storage/jwt/private.pem
export JWT_PUBLIC_KEY=/storage/jwt/public.pem

# Validation des variables requises
if [ -z "$APP_SECRET" ]; then
    echo "ERREUR: APP_SECRET non défini" >&2
    exit 1
fi
if [ -z "$JWT_PASSPHRASE" ]; then
    echo "ERREUR: JWT_PASSPHRASE non défini" >&2
    exit 1
fi

# Créer les répertoires persistants
mkdir -p /storage/jwt
chown -R www-data:www-data /storage
chmod 0755 /storage

# Générer les clés JWT si absentes (persistent dans /storage)
if [ ! -f "/storage/jwt/private.pem" ]; then
    echo "Génération des clés JWT..."
    openssl genrsa -passout "pass:${JWT_PASSPHRASE}" -out "/storage/jwt/private.pem" 4096
    openssl rsa -passin "pass:${JWT_PASSPHRASE}" -pubout \
        -in "/storage/jwt/private.pem" -out "/storage/jwt/public.pem"
    chown www-data:www-data /storage/jwt/private.pem /storage/jwt/public.pem
    chmod 640 /storage/jwt/private.pem
fi

# Permissions Symfony
mkdir -p /app/backend/var/cache/prod /app/backend/var/log
chown -R www-data:www-data /app/backend/var

# Migrations
echo "Migrations..."
su-exec www-data php /app/backend/bin/console doctrine:migrations:migrate \
    --no-interaction --env=prod 2>/dev/null || true

# Seeder si base vide
DB_USER_COUNT=$(su-exec www-data php /app/backend/bin/console \
    doctrine:query:sql "SELECT COUNT(*) FROM user" --env=prod 2>/dev/null \
    | grep -o '[0-9]\+' | tail -1 || echo "0")
if [ "${DB_USER_COUNT:-0}" = "0" ]; then
    echo "Initialisation des données..."
    su-exec www-data php /app/backend/bin/console app:create-admin \
        --email="${ADMIN_EMAIL:-admin@bibliotheque.fr}" \
        --password="${ADMIN_PASSWORD:-Admin1234!}" \
        --env=prod || true
fi

# Cache warmup
echo "Cache warmup..."
su-exec www-data php /app/backend/bin/console cache:warmup --env=prod 2>/dev/null || true

echo "Démarrage FrankenPHP..."
exec su-exec www-data frankenphp run --config /app/Caddyfile
