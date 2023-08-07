#!/bin/bash -l

set -o errexit -o xtrace

CHECK_MAINTENANCE=0

# Get the options
while getopts ":m" option; do
    case $option in
        m) # test maintennce
            CHECK_MAINTENANCE=1
            shift
            break
            ;;
    esac
done

if [ $CHECK_MAINTENANCE -eq 1 ]; then
    test "${APP_ENV}" == "prod" \
        -a "${INSTANCE_NUMBER}" == "0" \
        -a "${MAINTENANCE}" != "true" && \
    eval "$@"
else
    test "${APP_ENV}" == "prod" \
        -a "${INSTANCE_NUMBER}" == "0" && \
    eval "$@"
fi
