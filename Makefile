# .env.local (non versionné) surcharge .env pour l'interpolation Compose (ports, etc.)
ENV_FILES   = --env-file .env $(if $(wildcard .env.local),--env-file .env.local)
DOCKER_COMP = docker compose $(ENV_FILES)
PHP_CONT    = $(DOCKER_COMP) exec php
PHP         = $(PHP_CONT) php
COMPOSER    = $(PHP_CONT) composer
SYMFONY     = $(PHP) bin/console

.DEFAULT_GOAL = help
.PHONY: help build up start down logs sh composer sf cc test phpstan cs cs-fix qa

help: ## Liste des cibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build des images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Démarre la stack en arrière-plan
	@$(DOCKER_COMP) up --detach --wait

start: build up ## Build + démarrage

down: ## Arrête la stack
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Logs du conteneur php
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Shell dans le conteneur php
	@$(PHP_CONT) sh

composer: ## Composer, ex: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

sf: ## bin/console, ex: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Vide le cache Symfony
cc: sf

test: ## PHPUnit, ex: make test c='--filter=Homepage'
	@$(eval c ?=)
	@$(PHP_CONT) vendor/bin/phpunit $(c)

phpstan: ## Analyse statique PHPStan
	@$(PHP_CONT) vendor/bin/phpstan analyse --memory-limit=1G

cs: ## Vérifie le code style (PHP-CS-Fixer, dry-run)
	@$(PHP_CONT) vendor/bin/php-cs-fixer check --diff

cs-fix: ## Corrige le code style
	@$(PHP_CONT) vendor/bin/php-cs-fixer fix

qa: cs phpstan test ## Lance tous les contrôles qualité
