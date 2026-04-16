# Bibliothèque — Application de gestion

> **URL de production** : https://library-api.once.florent.cc
> **Swagger UI** : https://library-api.once.florent.cc/api/docs

Application de gestion de bibliothèque municipale. Stack : **React 19 + API Platform 3 (Symfony 7) + SQLite**.

## Prérequis de développement

- PHP >= 8.3 (avec extensions : `pdo_sqlite`, `intl`, `zip`)
- Composer >= 2.6
- Node.js >= 22 + pnpm
- Symfony CLI (optionnel, recommandé)
- OpenSSL (pour la génération des clés JWT)

## Installation rapide (< 10 minutes)

```bash
# 1. Cloner le projet
git clone https://github.com/votre-org/library-api-platform.git
cd library-api-platform

# 2. Tout installer en une commande
make install

# 3. Lancer en développement
make dev
```

L'application sera accessible sur :
- **Frontend** : http://localhost:5173
- **API** : https://localhost:8000/api
- **Swagger UI** : https://localhost:8000/api/docs

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Super Admin | admin@bibliotheque.fr | Admin1234! |
| Bibliothécaire | bibliothecaire@bibliotheque.fr | Biblio1234! |
| Adhérent | alice.martin@example.com | password123 |

## Commandes disponibles

```bash
make install      # Installation complète
make dev          # Serveurs de développement
make test         # Lancer tous les tests
make seed         # Recharger les données de démo
make reset-db     # Réinitialiser la base de données
make build-docker # Builder l'image Docker
make run-docker   # Lancer en Docker localement
make backup-db    # Sauvegarder la base SQLite
```

## Configuration

Copiez `backend/.env` en `backend/.env.local` pour surcharger les variables :

```bash
cp backend/.env backend/.env.local
```

Variables importantes dans `.env.local` :

```env
APP_ENV=prod
APP_SECRET=votre_secret_32_chars
DATABASE_URL="sqlite:////storage/library.db"
JWT_PASSPHRASE=votre_passphrase
MAILER_DSN=smtp://user:pass@smtp.example.com:465
LIBRARY_NAME="Ma Bibliothèque"
LIBRARY_EMAIL=contact@ma-bibliotheque.fr
CORS_ALLOW_ORIGIN='^https?://votre-domaine\.com$'
```

## Architecture

```
library-api-platform/
├── backend/          # Symfony 7 + API Platform 3
│   ├── src/
│   │   ├── Entity/         # Entités Doctrine + ApiResource
│   │   ├── State/          # Processors + Providers
│   │   ├── Service/        # Logique métier
│   │   ├── Security/Voter/ # Voters d'autorisation
│   │   └── Scheduler/      # Tâches planifiées
│   └── var/data.db   # Base SQLite (gitignored)
├── frontend/         # React 19 + Vite 6
│   ├── src/
│   │   ├── api/            # Wrappers fetch axios
│   │   ├── hooks/          # TanStack Query hooks
│   │   ├── pages/          # Pages admin + portail adhérent
│   │   └── components/     # Composants UI (shadcn/ui)
│   └── dist/         # Build de production
├── docker/           # Config nginx, supervisor, entrypoint
├── Dockerfile        # Build multi-stage
└── Makefile          # Commandes raccourcies
```

## Déploiement (Once / Docker)

L'application est déployée sur un serveur Once avec Docker.

### Build et push de l'image

```bash
# L'image est pushée automatiquement par GitHub Actions
# Sur push sur master → ghcr.io/votre-org/library-api-platform:master
```

### Déploiement initial sur le serveur Once

```bash
ssh ubuntu@ssh.once.florent.cc

once deploy ghcr.io/florentdestremau/library-api-platform:master \
  --host library-api.once.florent.cc \
  --env CORS_ALLOW_ORIGIN='.*' \
  --env ADMIN_EMAIL=admin@bibliotheque.fr \
  --env ADMIN_PASSWORD=Admin1234!

curl https://library-api.once.florent.cc/up
```

### Mise à jour

```bash
ssh ubuntu@ssh.once.florent.cc

once update library-api.once.florent.cc --image ghcr.io/florentdestremau/library-api-platform:master
```

**Note** : Il y a un downtime de quelques secondes pendant le redémarrage (pas de rolling update avec Docker simple). Pour un déploiement sans coupure, utiliser Docker Swarm ou Kubernetes.

## Sauvegarde SQLite

La base de données est dans `/storage/library.db` (volume Docker).

```bash
# Sur le serveur
cp /storage/library.db /storage/library.db.backup.$(date +%Y%m%d)

# Depuis le local
ssh ubuntu@ssh.once.florent.cc "cat /storage/library.db" > library-backup-$(date +%Y%m%d).db
```

## Tests

```bash
# Backend
cd backend && php bin/phpunit --coverage-text

# Frontend
cd frontend && pnpm vitest run

# Tout en une fois
make test
```

Couverture cible : ≥ 80% sur `backend/src/`.

## Risques connus (déploiement)

1. **Migrations SQLite irréversibles** : certaines migrations nécessitent de recréer les tables (limitation SQLite). Toujours tester les migrations en dev avant de déployer.

2. **Downtime pendant redémarrage** : le `docker stop` + `docker run` prend quelques secondes. Pour la production, prévoir une fenêtre de maintenance.

3. **Variables d'environnement** : au premier démarrage, `APP_SECRET` et `JWT_PASSPHRASE` sont générés aléatoirement si non fournis. Ils doivent être persistants entre les redémarrages — passer `-e APP_SECRET=xxx -e JWT_PASSPHRASE=yyy` explicitement.

4. **Clés JWT** : générées au premier démarrage et stockées dans le conteneur. Elles sont perdues si le conteneur est recréé. Solution : monter un volume pour `/app/backend/config/jwt/`.
