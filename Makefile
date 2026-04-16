.PHONY: install dev test seed reset-db build-docker run-docker stop-docker

# ── Installation complète ──────────────────────────────────────────────────
install:
	@echo "==> Installation du backend..."
	cd backend && composer install
	@echo "==> Installation du frontend..."
	cd frontend && pnpm install
	@echo "==> Génération des clés JWT..."
	cd backend && php bin/console lexik:jwt:generate-keypair --skip-if-exists
	@echo "==> Création de la base de données..."
	cd backend && php bin/console doctrine:database:create --if-not-exists
	@echo "==> Exécution des migrations..."
	cd backend && php bin/console doctrine:migrations:migrate --no-interaction
	@echo "==> Chargement des fixtures..."
	cd backend && php bin/console doctrine:fixtures:load --no-interaction
	@echo "✓ Installation terminée"
	@echo ""
	@echo "Comptes de démo :"
	@echo "  Admin      : admin@bibliotheque.fr / Admin1234!"
	@echo "  Bibliot.   : bibliothecaire@bibliotheque.fr / Biblio1234!"
	@echo "  Adhérent   : alice.martin@example.com / password123"

# ── Développement ──────────────────────────────────────────────────────────
dev:
	@echo "==> Démarrage des serveurs de développement..."
	@trap 'kill %1; kill %2' EXIT INT; \
	(cd backend && symfony serve --no-tls --port=8000 2>&1 | sed 's/^/[backend] /') & \
	(cd frontend && pnpm dev 2>&1 | sed 's/^/[frontend] /') & \
	wait

# ── Tests ──────────────────────────────────────────────────────────────────
test:
	@echo "==> Tests backend (PHPUnit)..."
	cd backend && php bin/phpunit
	@echo "==> Tests frontend (Vitest)..."
	cd frontend && pnpm vitest run

test-backend:
	cd backend && php bin/phpunit --coverage-html coverage/

test-frontend:
	cd frontend && pnpm vitest run

# ── Base de données ────────────────────────────────────────────────────────
seed:
	@echo "==> Chargement des fixtures..."
	cd backend && php bin/console doctrine:fixtures:load --no-interaction

reset-db:
	@echo "==> Réinitialisation de la base de données..."
	cd backend && rm -f var/data.db var/data-test*.db
	cd backend && php bin/console doctrine:database:create --if-not-exists
	cd backend && php bin/console doctrine:migrations:migrate --no-interaction
	cd backend && php bin/console doctrine:fixtures:load --no-interaction

migrate:
	cd backend && php bin/console doctrine:migrations:migrate --no-interaction

# ── Docker ─────────────────────────────────────────────────────────────────
build-docker:
	@echo "==> Build de l'image Docker..."
	docker build -t library-app:local .

run-docker: build-docker
	@echo "==> Lancement du conteneur..."
	docker run --rm -d \
		--name library-app \
		-p 80:80 \
		-v /tmp/library-storage:/storage \
		-e CORS_ALLOW_ORIGIN='.*' \
		library-app:local
	@echo "==> Attente du démarrage..."
	@sleep 5
	@curl -sf http://localhost/up && echo "✓ Application démarrée sur http://localhost" || echo "✗ Erreur de démarrage"

stop-docker:
	docker stop library-app || true
	docker rm library-app || true

# ── Qualité de code ────────────────────────────────────────────────────────
cs-check:
	cd backend && ./vendor/bin/php-cs-fixer check src/ --diff

cs-fix:
	cd backend && ./vendor/bin/php-cs-fixer fix src/

tsc:
	cd frontend && pnpm exec tsc --noEmit

# ── Utilitaires ────────────────────────────────────────────────────────────
logs-docker:
	docker logs library-app --tail 50 -f

backup-db:
	cp backend/var/data.db backend/var/data.db.backup.$(shell date +%Y%m%d_%H%M%S)
	@echo "✓ Sauvegarde créée"
