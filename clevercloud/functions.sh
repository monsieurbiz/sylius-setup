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
  if [ "${IS_PROTECTED:-false}" == "true" ]; then
    echo -e "$(cat ${APP_HOME}/clevercloud/security_htaccess)\n\n$(cat ${APP_HOME}${CC_WEBROOT}/.htaccess)" >${APP_HOME}${CC_WEBROOT}/.htaccess
    if [ -n "${HTTP_AUTH_USERNAME:-}" ] && [ -n "${HTTP_AUTH_PASSWORD:-}" ]; then
      if [ "${HTTP_AUTH_CLEAR:-false}" == "true" ]; then
        echo "" > ${APP_HOME}/clevercloud/.htpasswd
      fi
      htpasswd -b ${APP_HOME}/clevercloud/.htpasswd ${HTTP_AUTH_USERNAME} ${HTTP_AUTH_PASSWORD}
    fi
  fi

  # Append the content of internal host file after the like contains "RewriteEngine On" in `.htaccess` if a proxy is used
  # In clevercloud, you can link the php app to the proxy app and add `USE_INTERNAL_HOST` in the exposed configuration on the proxy app
  if [ "${USE_INTERNAL_HOST:-false}" == "true" ]; then
    awk '
    /RewriteEngine On/ {
        print $0;
        system("cat ${APP_HOME}/clevercloud/internal_host_htaccess");
        next;
    }
    { print }
    ' ${APP_HOME}${CC_WEBROOT}/.htaccess > /tmp/.htaccess.new && mv /tmp/.htaccess.new ${APP_HOME}${CC_WEBROOT}/.htaccess
  fi
}
export -f protect_application

function build_sylius() {
  cd ${APP_HOME}/apps/sylius
  php -d memory_limit=-1 ./bin/console sylius:install:assets -v
  php -d memory_limit=-1 ./bin/console doctrine:migr:migr -n -v
  php -d memory_limit=-1 ./bin/console messenger:setup-transports -n -v
  if [ "${RUN_FIXTURES+x}" ] && [ "${RUN_FIXTURES}" == "true" ]; then
    php -d memory_limit=-1 ./bin/console sylius:fixtures:load -n ${SYLIUS_FIXTURES_SUITE} -v
  fi
}
export -f build_sylius

function run_sylius() {
  cd ${APP_HOME}/apps/sylius
  php -d memory_limit=-1 ./bin/console cache:warmup -v
}
export -f run_sylius
