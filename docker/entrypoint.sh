#!/bin/sh
set -e

# Configurer le répertoire de données SQLite dans /storage
DB_PATH="/storage/library.db"
export DATABASE_URL="sqlite:///${DB_PATH}"

# Créer le répertoire de stockage si nécessaire
mkdir -p /storage
chown www-data:www-data /storage
chmod 0755 /storage

# Copier le .env.dist si .env.local n'existe pas
if [ ! -f "/app/backend/.env.local" ]; then
    # Générer APP_SECRET si non défini
    if [ -z "$APP_SECRET" ]; then
        export APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    fi

    cat > /app/backend/.env.local << EOF
APP_ENV=prod
APP_SECRET=${APP_SECRET}
DATABASE_URL=${DATABASE_URL}
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=${JWT_PASSPHRASE:-$(php -r "echo bin2hex(random_bytes(16));")}
CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-'^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'}
MAILER_DSN=${MAILER_DSN:-null://null}
LIBRARY_NAME=${LIBRARY_NAME:-Bibliothèque}
LIBRARY_EMAIL=${LIBRARY_EMAIL:-noreply@bibliotheque.fr}
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
EOF
fi

# Générer les clés JWT si elles n'existent pas
JWT_DIR="/app/backend/config/jwt"
mkdir -p "${JWT_DIR}"
if [ ! -f "${JWT_DIR}/private.pem" ]; then
    echo "Génération des clés JWT..."
    PASSPHRASE=$(grep JWT_PASSPHRASE /app/backend/.env.local | cut -d= -f2)
    openssl genrsa -passout "pass:${PASSPHRASE}" -out "${JWT_DIR}/private.pem" 4096
    openssl rsa -passin "pass:${PASSPHRASE}" -pubout -in "${JWT_DIR}/private.pem" -out "${JWT_DIR}/public.pem"
    chown www-data:www-data "${JWT_DIR}/private.pem" "${JWT_DIR}/public.pem"
    chmod 640 "${JWT_DIR}/private.pem"
    chmod 644 "${JWT_DIR}/public.pem"
fi

# S'assurer que www-data peut lire les clés JWT
chown www-data:www-data "${JWT_DIR}/private.pem" "${JWT_DIR}/public.pem" 2>/dev/null || true

# Configurer le cache Symfony
mkdir -p /app/backend/var/cache/prod /app/backend/var/log
chown -R www-data:www-data /app/backend/var

# Exécuter les migrations en tant que www-data
echo "Exécution des migrations Doctrine..."
su-exec www-data php /app/backend/bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>/dev/null || true

# Créer l'admin si la base est vide
DB_USER_COUNT=$(su-exec www-data php /app/backend/bin/console doctrine:query:sql "SELECT COUNT(*) FROM user" --env=prod 2>/dev/null | grep -o '[0-9]\+' | tail -1 || echo "0")
if [ "${DB_USER_COUNT:-0}" = "0" ]; then
    echo "Création de l'utilisateur admin..."
    su-exec www-data php /app/backend/bin/console app:create-admin \
        --email="${ADMIN_EMAIL:-admin@bibliotheque.fr}" \
        --password="${ADMIN_PASSWORD:-Admin1234!}" \
        --env=prod
fi

# Warm-up du cache Symfony
echo "Chargement du cache Symfony..."
su-exec www-data php /app/backend/bin/console cache:warmup --env=prod 2>/dev/null || true

echo "Démarrage des services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
