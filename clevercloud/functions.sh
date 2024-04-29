#!/bin/bash -l

set -o errexit -o nounset -o xtrace

function deploy_artifact() {
  tar xvzf application.tgz
  rm -f application.tgz
}
export -f deploy_artifact

function install_dependencies() {
  mkdir -p ~/.local/bin
}
export -f install_dependencies

function copy_dist_files() {
  return 0
}
export -f copy_dist_files

function protect_application() {
  # Append the content of security file in `.htaccess` if protected
  if [ "${IS_PROTECTED}" == "true" ]; then
    echo -e "$(cat ${APP_HOME}/clevercloud/security_htaccess)\n\n$(cat ${APP_HOME}${CC_WEBROOT}/.htaccess)" >${APP_HOME}${CC_WEBROOT}/.htaccess
    if [ -n "${HTTP_AUTH_USERNAME:-}" ] && [ -n "${HTTP_AUTH_PASSWORD:-}" ]; then
      if [ "${HTTP_AUTH_CLEAR:-false}" == "true" ]; then
        echo "" > ${APP_HOME}/clevercloud/.htpasswd
      fi
      htpasswd -b ${APP_HOME}/clevercloud/.htpasswd ${HTTP_AUTH_USERNAME} ${HTTP_AUTH_PASSWORD}
    fi
  fi
}
export -f protect_application

function build_sylius() {
  cd ${APP_HOME}/apps/sylius
  php -d memory_limit=-1 ./bin/console sylius:install:assets -v
  php -d memory_limit=-1 ./bin/console doctrine:migr:migr -n -v --all-or-nothing
  php -d memory_limit=-1 ./bin/console messenger:setup-transports -n -v
}
export -f build_sylius

function run_sylius() {
  cd ${APP_HOME}/apps/sylius
  php -d memory_limit=-1 ./bin/console cache:warmup -v
}
export -f run_sylius
