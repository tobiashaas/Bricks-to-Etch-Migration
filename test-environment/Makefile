# ⚠️ DEPRECATED ⚠️
# This Makefile is no longer actively maintained.
# Please use npm scripts instead.
#
# See ../bricks-etch-migration/package.json for available commands.
#
# For new development, use:
#   cd ../bricks-etch-migration
#   npm run dev
#
# This file is retained for reference only.
#
.PHONY: help setup start stop restart clean logs-bricks logs-etch shell-bricks shell-etch wp-bricks wp-etch test-migration sync-plugin composer-install create-test-content validate debug quick-test

DOCKER_COMPOSE := docker-compose
SCRIPTS_DIR := ./scripts

help:
	@echo "Legacy Docker Makefile - wp-env via npm is now the recommended workflow"
	@echo ""
	@echo "  npm run dev             - Start wp-env environments (preferred)"
	@echo ""
	@echo "Legacy targets (Docker Compose):"
	@echo "  make setup              - Complete setup (start containers, install WordPress, create test data)"
	@echo "  make start              - Start all containers"
	@echo "  make stop               - Stop all containers"
	@echo "  make restart            - Restart all containers"
	@echo "  make clean              - Remove all containers and volumes (WARNING: deletes all data)"
	@echo ""
	@echo "  make logs-bricks        - Show Bricks WordPress logs"
	@echo "  make logs-etch          - Show Etch WordPress logs"
	@echo "  make shell-bricks       - Open shell in Bricks container"
	@echo "  make shell-etch         - Open shell in Etch container"
	@echo ""
	@echo "  make wp-bricks ARGS='...'  - Run WP-CLI command on Bricks instance"
	@echo "  make wp-etch ARGS='...'    - Run WP-CLI command on Etch instance"
	@echo ""
	@echo "  make test-migration     - Run automated migration test"
	@echo "  make sync-plugin        - Manually sync plugin to both instances"
	@echo "  make composer-install   - Install Composer dependencies in plugin"
	@echo "  make create-test-content - Create test posts/pages in Bricks instance"
	@echo ""
	@echo "  make validate           - Validate complete setup (containers, WordPress, plugin)"
	@echo "  make debug              - Collect debug information for troubleshooting"
	@echo "  make quick-test         - Quick connection test (API key validation, token generation)"
	@echo ""
	@echo "Access URLs:"
	@echo "  Bricks Source: http://localhost:8080"
	@echo "  Etch Target:   http://localhost:8081"
	@echo "  Admin User:    admin / admin"

setup: start
	@echo "[setup] Waiting for containers to be ready..."
	@sleep 10
	@echo "[setup] Installing Composer dependencies..."
	@bash $(SCRIPTS_DIR)/install-composer-deps.sh || { echo "[setup] ERROR: Composer installation failed" >&2; exit 1; }
	@echo "[setup] Running WordPress setup script..."
	@bash $(SCRIPTS_DIR)/setup-wordpress.sh || { echo "[setup] ERROR: WordPress setup failed" >&2; exit 1; }
	@echo "[setup] Creating test content..."
	@bash $(SCRIPTS_DIR)/create-test-content.sh || echo "[setup] WARNING: Test content creation failed (non-critical)"
	@echo "[setup] Running validation..."
	@bash $(SCRIPTS_DIR)/validate-setup.sh || echo "[setup] WARNING: Some validation checks failed"
	@echo "[setup] Setup complete!"

start:
	@echo "[start] Starting Docker containers..."
	$(DOCKER_COMPOSE) up -d
	@echo "[start] Containers started."

stop:
	@echo "[stop] Stopping Docker containers..."
	$(DOCKER_COMPOSE) down
	@echo "[stop] Containers stopped."

restart: stop start

clean:
	@echo "[clean] WARNING: This will delete all containers and volumes!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		$(DOCKER_COMPOSE) down -v; \
		echo "[clean] All containers and volumes removed."; \
	else \
		echo "[clean] Aborted."; \
	fi

logs-bricks:
	$(DOCKER_COMPOSE) logs -f bricks-wp

logs-etch:
	$(DOCKER_COMPOSE) logs -f etch-wp

shell-bricks:
	$(DOCKER_COMPOSE) exec bricks-wp bash

shell-etch:
	$(DOCKER_COMPOSE) exec etch-wp bash

wp-bricks:
	$(DOCKER_COMPOSE) exec wpcli wp --path=/var/www/html/bricks $(ARGS)

wp-etch:
	$(DOCKER_COMPOSE) exec wpcli wp --path=/var/www/html/etch $(ARGS)

test-migration:
	@bash $(SCRIPTS_DIR)/test-migration.sh

sync-plugin:
	@bash $(SCRIPTS_DIR)/sync-plugin.sh

composer-install:
	@bash $(SCRIPTS_DIR)/install-composer-deps.sh

create-test-content:
	@bash $(SCRIPTS_DIR)/create-test-content.sh

validate:
	@echo "[validate] Running setup validation..."
	@bash $(SCRIPTS_DIR)/validate-setup.sh

debug:
	@echo "[debug] Collecting debug information..."
	@bash $(SCRIPTS_DIR)/debug-info.sh

quick-test:
	@echo "[quick-test] Running quick connection test..."
	@bash $(SCRIPTS_DIR)/test-connection.sh
