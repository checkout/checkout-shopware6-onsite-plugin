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

core: ## Installs all core dependencies
	cd vendor/shopware/administration/Resources/app/administration && npm install
	cd vendor/shopware/storefront/Resources/app/storefront && npm install

clean: ## Cleans all dependencies
	rm -rf vendor

build: ## Installs the plugin, and builds
	cd /var/www/html && php bin/console plugin:refresh
	cd /var/www/html && php bin/console plugin:install CkoShopware6 --activate | true
	cd /var/www/html && php bin/console plugin:refresh
	cd /var/www/html && php bin/console theme:dump
	cd /var/www/html && PUPPETEER_SKIP_DOWNLOAD=1 ./bin/build-js.sh
	cd /var/www/html && php bin/console theme:refresh
	cd /var/www/html && php bin/console theme:compile
	cd /var/www/html && php bin/console theme:refresh

phpunit: ## Starts all PHPUnit Tests
	php ./vendor/bin/phpunit --configuration=./phpunit.xml

stan: ## Starts the PHPStan Analyser
	php ./vendor/bin/phpstan --memory-limit=1G analyse -c ./phpstan.neon

ecs: ## Starts the ESC checker
	php ./vendor/bin/ecs check . --config easy-coding-standard.php

csfix: ## Starts the PHP CS Fixer
	php ./vendor/bin/ecs check . --config easy-coding-standard.php --fix

jest: ## Starts all Jest tests
	cd ./src/Resources/app/administration && ./node_modules/.bin/jest --config=jest.config.js
	cd ./src/Resources/app/storefront && ./node_modules/.bin/jest --config=jest.config.js

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
	npm run --prefix src/Resources/app/storefront/ lint

lint-js-fix: ## Runs eslint and fix
	npm run --prefix src/Resources/app/administration/ lint -- --fix
	npm run --prefix src/Resources/app/storefront/ lint -- --fix

lint-scss: ## Runs scss stylelint
	npm run --prefix src/Resources/app/administration/ lint:scss
	npm run --prefix src/Resources/app/storefront/ lint:scss-all

lint-scss-fix: ## Runs scss stylelint and fix
	npm run --prefix src/Resources/app/administration/ lint:scss-fix
	npm run --prefix src/Resources/app/storefront/ lint:scss-all:fix

lint-twig: ## Runs twig lint
	npm run --prefix src/Resources/app/administration/ lint:twig

lint-twig-fix: ## Runs twig lint and fix
	npm run --prefix src/Resources/app/administration/ lint:twig -- --fix
# ------------------------------------------------------------------------------------------------------------

review: ## Starts the full review pipeline
	make ecs -B
	make stan -B
	make phpunit -B
	make jest -B
	make lint-js -B
	make lint-scss -B
	make lint-twig -B
