<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Context;
use Symfony\Component\Console\Completion\CompletionInput;

use function Castor\capture;
use function Castor\fs;
use function Castor\import;
use function Castor\io;
use function Castor\run;

import(__DIR__ . '/.castor/');

const DEFAULT_TIMEOUT_COMPOSER_PROCESS = 120;
const SUGGESTED_PHP_VERSION = '8.3';
const SUGGESTED_SYLIUS_VERSION = '2.0';

#[AsTask(namespace: 'local', description: 'Reset local project. Be careful!')]
function reset(): void
{
    if (io()->confirm('Are you sure? This is a destructive action!', false)) {
        run('make down docker.destroy || true');
        run('rm -rf apps/sylius');
        io()->success('Reset done!');
    }
}

function autocomple_php_version(CompletionInput $input): array
{
    return [SUGGESTED_PHP_VERSION];
}
function autocomple_sylius_version(CompletionInput $input): array
{
    return [SUGGESTED_SYLIUS_VERSION];
}

#[AsTask(namespace: 'local', description: 'Init project')]
function setup(
    #[AsOption(description: 'PHP Version', autocomplete: 'MonsieurBiz\SyliusSetup\Castor\autocomple_php_version')] ?string $php = null,
    #[AsOption(description: 'Sylius major version', autocomplete: 'MonsieurBiz\SyliusSetup\Castor\autocomple_sylius_version')] ?string $sylius = null,
): void {
    # PHP Version
    $phpVersion = $php ?? io()->ask('Which PHP do you want?', SUGGESTED_PHP_VERSION);
    file_put_contents('.php-version', $phpVersion);

    # .gitignore
    $ignore = explode('# To keep', file_get_contents('.gitignore'));
    if (isset($ignore[1])) {
        file_put_contents('.gitignore', $ignore[1]);
    }

    # Some contexts
    $noTimeoutContext = new Context(timeout: false);
    $composerContext = new Context(
        workingDirectory: 'apps/sylius/',
        timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS,
    );

    # sylius
    $syliusVersion = $sylius ?? io()->ask('Which Sylius version do you want?', SUGGESTED_SYLIUS_VERSION);
    run('symfony composer create-project --no-scripts sylius/sylius-standard=~' . $syliusVersion . '.0 apps/sylius', context: $noTimeoutContext);
    file_put_contents('apps/sylius/.env.dev', 'MAILER_DSN=smtp://localhost:1025');
    file_put_contents('apps/sylius/.php-version', $phpVersion);

    # Cleanup the composer.json
    $repo = strtolower(capture('gh repo view --json nameWithOwner --jq .nameWithOwner', onFailure: 'monsieurbiz/project'));
    run('symfony composer config name "' . $repo . '"', context: $composerContext);
    run('symfony composer config description "' . $repo . '"', context: $composerContext);
    run('symfony composer config license proprietary', context: $composerContext);
    run('symfony composer config --unset homepage', context: $composerContext);
    run('symfony composer config --unset authors', context: $composerContext);
    run('symfony composer config --unset keywords', context: $composerContext);
    run('symfony composer config extra.symfony.allow-contrib true', context: $composerContext);
    run('symfony composer require --no-scripts php="^' . $phpVersion . '"', context: $composerContext);

    # Add scripts in composer.json
    run('symfony composer config scripts.phpcs "php-cs-fixer fix --allow-risky=yes"', context: $composerContext);
    run('symfony composer config scripts.phpmd "phpmd src/,plugins/ ansi phpmd.xml --exclude src/Migrations"', context: $composerContext);

    # Allow plugins
    run('symfony composer config allow-plugins.symfony/flex true', context: $composerContext);
    run('symfony composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true', context: $composerContext);
    run('symfony composer config allow-plugins.phpstan/extension-installer true', context: $composerContext);
    run('symfony composer config allow-plugins.symfony/thanks true', context: $composerContext);
    run('symfony composer config allow-plugins.symfony/runtime true', context: $composerContext);
    run('symfony composer config allow-plugins.cweagans/composer-patches true', context: $composerContext);
    run('symfony composer config allow-plugins.szeidler/composer-patches-cli true', context: $composerContext);

    # Add or update packages
    run('symfony composer require --dev --no-scripts phpmd/phpmd="*"', context: $composerContext);
    run('symfony composer require --dev --no-scripts phpunit/phpunit --with-all-dependencies', context: $composerContext);
    run('symfony composer require --dev --no-scripts friendsofphp/php-cs-fixer', context: $composerContext);
    run('symfony composer require --no-scripts cweagans/composer-patches', context: $composerContext);
    run('symfony composer require --dev --no-scripts szeidler/composer-patches-cli', context: $composerContext);

    # Fix for sylius and doctrine conflict, for Sylius 1.x only
    if (io()->confirm('Do you want to fix a conflict with doctrine? Highly recommended for Sylius 1.x ONLY!', false)) {
        io()->info('Add conflict for doctrine/orm in order to fix an issue in Sylius.');
        run("cat composer.json | jq --indent 4 '.conflict += {\"doctrine/orm\": \">= 2.15.2\"}' > composer.json.tmp", context: $composerContext);
        run('mv composer.json.tmp composer.json', context: $composerContext);
        io()->info('Run composer update after updating the composer.json file.');
        run('symfony composer update', context: $composerContext);
    }

    # Copy dist files
    run('cp -Rv dist/sylius/ apps/sylius'); // We have hidden files in dist/sylius
    run('rm -rf dist');

    # Add missing directories
    fs()->mkdir('apps/sylius/plugins');
    fs()->touch('apps/sylius/plugins/.gitignore');
    fs()->mkdir('apps/sylius/src/Resources/config');
    fs()->touch('apps/sylius/src/Resources/config/.gitignore');

    # Ignore .php-cs-fixer.cache and _themes in public
    fs()->appendToFile('apps/sylius/.gitignore', '/.php-cs-fixer.cache' . PHP_EOL);
    fs()->appendToFile('apps/sylius/.gitignore', '/public/_themes' . PHP_EOL);

    # We want to commit the composer.lock and yarn.lock
    run('sed -i "" -e "/composer.lock/d" .gitignore', workingDirectory: 'apps/sylius/', allowFailure: true);
    run('sed -i "" -e "/yarn.lock/d" .gitignore', workingDirectory: 'apps/sylius/', allowFailure: true);

    # install
    run('make install', context: $noTimeoutContext);

    # GHA
    run('cp -Rv _.github/* .github/');
    run('rm -rf _.github');
    run('rm -rf apps/sylius/.github');

    # Clean up Sylius
    run('rm -rf apps/sylius/Dockerfile');
    run('rm -rf apps/sylius/src/Entity/.gitignore');

    # Fix PHP CS
    run('make test.phpcs.fix', context: $noTimeoutContext);

    io()->success('Your project has been setup!');
    io()->comment('You can now commit the changes!');
    io()->info('And setup your themes! (see the README.md)');
}

#[AsTask(namespace: 'local', description: 'Clean up files used for setup')]
function cleanUp(): void
{
    if (io()->confirm('Are you sure? This is a destructive action!', false)) {
        run('rm -rf castor');
        run('rm castor.php');
        io()->success('Clean up done!');
    }
}
