#!/bin/bash -l

set -o errexit -o nounset -o xtrace

source ${APP_HOME}/clevercloud/functions.sh

deploy_artifact
install_dependencies
