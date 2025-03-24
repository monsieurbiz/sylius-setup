<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use function Castor\import;
use Symfony\Component\Console\Completion\CompletionInput;

import(__DIR__ . '/.castor/');

function autocomple_php_version(CompletionInput $input): array
{
    return [SUGGESTED_PHP_VERSION];
}
function autocomple_sylius_version(CompletionInput $input): array
{
    return [SUGGESTED_SYLIUS_VERSION];
}
