#!/bin/bash -l

set -o errexit -o nounset -o xtrace

source ${APP_HOME}/clevercloud/functions.sh

install_dependencies
copy_dist_files
protect_application
run_sylius
