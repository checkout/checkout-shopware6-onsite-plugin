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

lint: ## Runs eslint
	make lint-js -B
	make lint-scss -B
	make lint-twig -B

lint-fix: ## Runs eslint and fix
	make lint-js-fix -B
	make lint-scss-fix -B
	make lint-twig-fix -B

lint-js: ## Runs js eslint
	npm run --prefix src/Resources/app/administration/ lint

lint-js-fix: ## Runs eslint and fix
	npm run --prefix src/Resources/app/administration/ lint -- --fix

lint-scss: ## Runs scss stylelint
	npm run --prefix src/Resources/app/administration/ lint:scss

lint-scss-fix: ## Runs scss stylelint and fix
	npm run --prefix src/Resources/app/administration/ lint:scss-fix

lint-twig: ## Runs twig lint
	npm run --prefix src/Resources/app/administration/ lint:twig

lint-twig-fix: ## Runs twig lint and fix
	npm run --prefix src/Resources/app/administration/ lint:twig -- --fix
# ------------------------------------------------------------------------------------------------------------

review: ## Starts the full review pipeline
	make ecs -B
	make stan -B
	make phpunit -B
	make lint-js -B
	make lint-scss -B
	make lint-twig -B
