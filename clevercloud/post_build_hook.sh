#!/bin/bash

set -o errexit -o nounset -o xtrace

source ${APP_HOME}/clevercloud/functions.sh

install_dependencies
copy_dist_files
build_sylius
