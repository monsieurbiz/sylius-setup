###
### DEVELOPMENT
### ¯¯¯¯¯¯¯¯¯¯¯

ifeq (${BUILD_THEME_IN_DOCKER},0)
define yarn
    (cd ${APP_DIR}; yarn $(1))
endef
else
define yarn
	$(call docker-compose,run --rm --user node node sh -c "cd ${APP_DIR}; yarn $(1)")
endef
endif

install: docker.pull docker.build platform sylius ## Install the project
.PHONY: install

up: docker.up server.start ## Up the project (start docker, start symfony server)
stop: server.stop docker.stop ## Stop the project (stop docker, stop symfony server)
down: server.stop docker.down ## Down the project (removes docker containers, stop symfony server)

reset: docker.down ## Stop docker and remove dependencies
	rm -rf .cache .node-gyp .yarn
	rm -rf ${APP_DIR}/node_modules
	rm -rf ${APP_DIR}/vendor
	rm -rf ${APP_DIR}/var/cache
.PHONY: reset

dependencies: .php-version ${APP_DIR}/vendor ${APP_DIR}/node_modules ## Setup the dependencies
.PHONY: dependencies

${APP_DIR}/vendor: ${APP_DIR}/composer.lock ## Install the PHP dependencies using composer
	$(call symfony.composer,install --prefer-dist)

${APP_DIR}/composer.lock: ${APP_DIR}/composer.json

yarn.install: ${APP_DIR}/node_modules

${APP_DIR}/node_modules: ${APP_DIR}/yarn.lock
	$(call yarn,install)

${APP_DIR}/yarn.lock: ${APP_DIR}/package.json
