<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\GlobalHelper;
use Symfony\Component\Console\Question\Question;

use function Castor\capture;
use function Castor\fs;
use function Castor\io;
use function Castor\run;

const DEFAULT_TIMEOUT_COMPOSER_PROCESS = 120;
const SUGGESTED_PHP_VERSION = '8.2';
const SUGGESTED_SYLIUS_VERSION = '1.12';

#[AsTask(namespace: 'local', description: 'Reset local project. Be careful!')]
function reset(): void
{
    if (io()->confirm('Are you sure? This is a destructive action!', false)) {
        run('make down docker.destroy || true');
        run('rm -rf apps/sylius');
        io()->success('Reset done!');
    }
}

#[AsTask(namespace: 'local', description: 'Init project')]
function setup(
    #[AsOption(description: 'PHP Version', suggestedValues: [SUGGESTED_PHP_VERSION])] ?string $php = null,
    #[AsOption(description: 'Sylius major version', suggestedValues: [SUGGESTED_SYLIUS_VERSION])] ?string $sylius = null,
): void {
    # PHP Version
    $phpVersion = $php ?? io()->ask('Which PHP do you want?', SUGGESTED_PHP_VERSION);
    file_put_contents('.php-version', $phpVersion);

    # .gitignore
    $ignore = explode('# To keep', file_get_contents('.gitignore'));
    if (isset($ignore[1])) {
        file_put_contents('.gitignore', $ignore[1]);
    }

    # sylius
    $syliusVersion = $sylius ?? io()->ask('Which Sylius version do you want?', SUGGESTED_SYLIUS_VERSION);
    run('symfony composer create-project --no-scripts sylius/sylius-standard=^' . $syliusVersion . '.0 apps/sylius', timeout: false);
    file_put_contents('apps/sylius/.env.dev', 'MAILER_DSN=smtp://localhost:1025');
    file_put_contents('apps/sylius/.php-version', $phpVersion);

    # Cleanup the composer.json
    $repo = strtolower(capture('gh repo view --json nameWithOwner --jq .nameWithOwner | cat'));
    if ($repo === 'no git remotes found') {
        $repo = "monsieurbiz/project";
    }
    run('symfony composer config name "' . $repo . '"', path: 'apps/sylius/');
    run('symfony composer config description "' . $repo . '"', path: 'apps/sylius/');
    run('symfony composer config license proprietary', path: 'apps/sylius/');
    run('symfony composer config --unset homepage', path: 'apps/sylius/');
    run('symfony composer config --unset authors', path: 'apps/sylius/');
    run('symfony composer config --unset keywords', path: 'apps/sylius/');
    run('symfony composer config extra.symfony.allow-contrib true', path: 'apps/sylius/');
    run('symfony composer require --no-scripts php="^' . $phpVersion . '"', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);

    # Add scripts in composer.json
    run('symfony composer config scripts.phpcs "php-cs-fixer fix --allow-risky=yes"', path: 'apps/sylius/');
    run('symfony composer config scripts.phpmd "phpmd src/,plugins/ ansi phpmd.xml --exclude src/Migrations"', path: 'apps/sylius/');

    # Allow plugins
    run('symfony composer config allow-plugins.symfony/flex true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.phpstan/extension-installer true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.symfony/thanks true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.symfony/runtime true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.cweagans/composer-patches true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer config allow-plugins.szeidler/composer-patches-cli true', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);

    # Add or update packages
    run('symfony composer require --dev --no-scripts phpmd/phpmd="*"', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer require --dev --no-scripts phpunit/phpunit="^9.5" --with-all-dependencies', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer require --dev --no-scripts friendsofphp/php-cs-fixer', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer require --no-scripts cweagans/composer-patches', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);
    run('symfony composer require --dev --no-scripts szeidler/composer-patches-cli', path: 'apps/sylius/', timeout: DEFAULT_TIMEOUT_COMPOSER_PROCESS);

    # Fix for sylius and doctrine conflict
    io()->info('Add conflict for doctrine/orm in order to fix an issue in Sylius.');
    run("cat composer.json | jq --indent 4 '.conflict += {\"doctrine/orm\": \">= 2.15.2\"}' > composer.json.tmp", path: 'apps/sylius/');
    run('mv composer.json.tmp composer.json', path: 'apps/sylius/');
    io()->info('Run composer update after updating the composer.json file.');
    run('symfony composer update', path: 'apps/sylius/', timeout: false);

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
    run('sed -i "" -e "/composer.lock/d" .gitignore', path: 'apps/sylius/', allowFailure: true);
    run('sed -i "" -e "/yarn.lock/d" .gitignore', path: 'apps/sylius/', allowFailure: true);

    # install
    run('make install', timeout: false);

    # GHA
    run('cp -Rv _.github/* .github/');
    run('rm -rf _.github');
    run('rm -rf apps/sylius/.github');

    # Clean up Sylius
    run('rm -rf apps/sylius/Dockerfile');
    run('rm -rf apps/sylius/src/Entity/.gitignore');

    # Fix PHP CS
    run('make test.phpcs.fix', timeout: false);

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
