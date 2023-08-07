#!/bin/bash -l

set -o errexit -o nounset -o xtrace

cd ${APP_HOME}/apps/sylius
${APP_HOME}/clevercloud/command.sh -m -- php -d memory_limit=-1 ./bin/console "$@"
