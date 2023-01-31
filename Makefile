.DEFAULT_GOAL := help
SHELL=/bin/bash
APP_DIR=apps/sylius
FIXTURES_DIR=src/Resources/fixtures
DOMAINS=${APP_DIR}:sylius
SYLIUS_FIXTURES_SUITE=default
SYMFONY=cd ${APP_DIR} && symfony
COMPOSER=${SYMFONY} composer
CONSOLE=${SYMFONY} console
PHPSTAN=${SYMFONY} php vendor/bin/phpstan
PHPUNIT=${SYMFONY} php vendor/bin/phpunit
PHPSPEC=${SYMFONY} php vendor/bin/phpspec
BASH_CONTAINER=php
NO_FIXTURES_FILE=.no-fixtures
BUILD_THEME_IN_DOCKER:=0

export USER_UID=$(shell id -u)

DC_DIR=infra/dev
DC_PREFIX=sylius
APP_ENV=dev

ifndef DC_PREFIX
  $(error Please define DC_PREFIX before running make)
endif

include resources/makefiles/development.mk
include resources/makefiles/sylius.mk
include resources/makefiles/symfony.mk
include resources/makefiles/platform.mk
include resources/makefiles/testing.mk
include resources/makefiles/help.mk
