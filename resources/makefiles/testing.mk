###
### TESTS
### ¯¯¯¯¯

test.all: test.composer test.phpstan test.phpunit test.phpspec test.phpcs test.phpmd test.yaml test.schema test.twig test.container ## Run all tests in once

test.composer: ## Validate composer.json
	$(call symfony.composer,validate --strict)

test.phpstan: ## Run PHPStan
	${PHPSTAN} analyse -c phpstan.neon src/ plugins/

test.phpunit: ## Run PHPUnit
	${PHPUNIT}

test.phpspec: ## Run PHPSpec
	${PHPSPEC} run

test.phpcs: ## Run PHP CS Fixer in dry-run
	$(call symfony.composer,run -- phpcs --dry-run -v)

test.phpcs.fix: ## Run PHP CS Fixer and fix issues if possible
	$(call symfony.composer,run -- phpcs -v)

test.phpmd: ## Run PHP Mass Detector
	$(call symfony.composer,run -- phpmd)

test.container: ## Lint the symfony container
	$(call symfony.console,lint:container)

test.yaml: ## Lint the symfony Yaml files
	$(call symfony.console,lint:yaml  --parse-tags src/Resources/config config)

test.schema: ## Validate MySQL Schema
	$(call symfony.console,doctrine:schema:validate)

test.twig: ## Validate Twig templates
	${CONSOLE} lint:twig -e prod --no-debug templates/

test.duplicated-templates:
	@resources/bin/check-templates.sh ${APP_DIR}/templates

test.duplicated-templates.fix:
	@resources/bin/check-templates.sh ${APP_DIR}/templates --fix
