#
# Makefile
#

.PHONY: help
.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# ------------------------------------------------------------------------------------------------------------

install: ## Installs all production dependencies
	@composer validate
	@composer install --no-dev
	cd src/Resources/app/administration && npm install --production
	cd src/Resources/app/storefront && npm install --production

dev: ## Installs all dev dependencies
	@composer validate
	@composer install
	cd src/Resources/app/administration && npm install
	cd src/Resources/app/storefront && npm install

clean: ## Cleans all dependencies
	rm -rf vendor

# ------------------------------------------------------------------------------------------------------------

phpunit: ## Starts all PHPUnit Tests
	php ./vendor/bin/phpunit --configuration=./phpunit.xml

stan: ## Starts the PHPStan Analyser
	php ./vendor/bin/phpstan --memory-limit=1G analyse -c ./.phpstan.neon

ecs: ## Starts the ESC checker
	php ./vendor/bin/ecs check . --config easy-coding-standard.php

csfix: ## Starts the PHP CS Fixer
	php ./vendor/bin/ecs check . --config easy-coding-standard.php --fix
# ------------------------------------------------------------------------------------------------------------

review: ## Starts the full review pipeline
	make ecs -B
	make stan -B
	make phpunit -B
