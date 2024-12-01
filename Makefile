# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
ifeq ($(HOME),/home/php)
	# we are running inside the container
	PHP_CONT =
else ifeq ($(HOME),/root)
	# we are running inside the container (i hope you don't use your machine as root)
	PHP_CONT =
else
	PHP_CONT = $(DOCKER_COMP) exec -u php php
endif

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP_CONT) bin/console
PHPUNIT  = $(DOCKER_COMP) exec -e XDEBUG_MODE=develop,coverage php vendor/bin/phpunit
CS_FIXER = $(PHP_CONT) vendor/bin/php-cs-fixer
PHPSTAN  = $(PHP_CONT) vendor/bin/phpstan

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc css-install

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull

rebuild: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

shell: ## Connect to the PHP FPM container
	@$(PHP_CONT) ash

shell-root:
	@$(DOCKER_COMP) exec php ash

shell-cli:
	@$(DOCKER_COMP) exec php-cli ash

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

database:
database: c="doctrine:database:create" --if-not-exists
database: sf

migration:
migration: c="doctrine:migrations:migrate"
migration: sf

cc: c=c:c ## Clear the cache
cc: sf
