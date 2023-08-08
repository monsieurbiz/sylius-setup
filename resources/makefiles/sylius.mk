###
### SYLIUS
### ¯¯¯¯¯¯

sylius: dependencies sylius.database symfony.messenger.setup sylius.api.generate-keypair sylius.theming.build sylius.fixtures sylius.assets ## Install Sylius
.PHONY: sylius

sylius.database: ## Setup the database
	test -f ${NO_FIXTURES_FILE} || ($(call symfony.console,doctrine:database:drop --if-exists --force))
	test -f ${NO_FIXTURES_FILE} || ($(call symfony.console,doctrine:database:create --if-not-exists))
	$(call symfony.console,doctrine:migr:migr -n)

sylius.fixtures: ## Run the fixtures
	test -f ${NO_FIXTURES_FILE} || (cd ${APP_DIR} && rm -Rf private/invoices)
	test -f ${NO_FIXTURES_FILE} || (${MAKE} sylius.fixtures.suite)

sylius.fixtures.suite:
	test -f ${NO_FIXTURES_FILE} || ($(call symfony.console,sylius:fixtures:load -n ${SYLIUS_FIXTURES_SUITE} -v))

sylius.assets: ## Install all assets with symlinks
	$(call symfony.console,sylius:install:assets)
	$(call symfony.console,assets:install --symlink --relative)

.PHONY: sylius.theming.build
sylius.theming.build: yarn.install ## Build the themes
	$(call yarn,encore prod)

.PHONY: sylius.theming.watch
sylius.theming.watch: yarn.install ## Build the themes
	$(call yarn,encore dev --watch)

sylius.cache.clean: FORCE=1
sylius.cache.clean: ## Remove application's cache (use FORCE=0 to use the console)
ifeq (${FORCE},0)
	$(call symfony.console,cache:clear --no-warmup)
else
	cd apps/sylius; rm -rf var/cache/*
endif

.PHONY: sylius.api.generate-keypair
sylius.api.generate-keypair: ## Generate the API Private/Public keys
	$(call symfony.console,lexik:jwt:generate-keypair --skip-if-exists)
