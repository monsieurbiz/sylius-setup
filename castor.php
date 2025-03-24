<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use function Castor\import;
use Symfony\Component\Console\Completion\CompletionInput;

import(__DIR__ . '/.castor/');

const SUGGESTED_PHP_VERSION = '8.3';
const SUGGESTED_SYLIUS_VERSION = '2.0';
const SUGGESTED_SYLIUS_APPLICATION_NAME = 'sylius';

function autocomple_php_version(CompletionInput $input): array
{
    return [SUGGESTED_PHP_VERSION];
}
function autocomple_sylius_version(CompletionInput $input): array
{
    return [SUGGESTED_SYLIUS_VERSION];
}
function autocomple_sylius_application_name(CompletionInput $input): array
{
    return [SUGGESTED_SYLIUS_APPLICATION_NAME];
}
