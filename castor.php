<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use Castor\Attribute\AsTask;
use Castor\GlobalHelper;
use Symfony\Component\Console\Question\Question;

use function Castor\fs;
use function Castor\io;
use function Castor\run;

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
function setup(): void
{
    # PHP Version
    $phpVersion = io()->ask('Which PHP do you want?', '8.2');
    file_put_contents('.php-version', $phpVersion);

    # .gitignore
    $ignore = explode('# To keep', file_get_contents('.gitignore'));
    if (isset($ignore[1])) {
        file_put_contents('.gitignore', $ignore[1]);
    }

    # sylius
    run('symfony composer create-project --no-scripts sylius/sylius-standard apps/sylius', timeout: false);
    file_put_contents('apps/sylius/.env.dev', 'MAILER_DSN=smtp://localhost:1025');
    file_put_contents('apps/sylius/.php-version', $phpVersion);

    # Cleanup the composer.json
    $repo = trim(run('gh repo view --json nameWithOwner --jq .nameWithOwner | cat', quiet: true)->getOutput());
    run('symfony composer config name ' . $repo, path: 'apps/sylius/');
    run('symfony composer config description ' . $repo, path: 'apps/sylius/');
    run('symfony composer config license proprietary', path: 'apps/sylius/');
    run('symfony composer config --unset homepage', path: 'apps/sylius/');
    run('symfony composer config --unset authors', path: 'apps/sylius/');
    run('symfony composer config --unset keywords', path: 'apps/sylius/');
    run('symfony composer config require.php "^' . $phpVersion . '"', path: 'apps/sylius/');

    # Add scripts in composer.json
    run('symfony composer config scripts.phpcs "php-cs-fixer fix --allow-risky=yes"', path: 'apps/sylius/');
    run('symfony composer config scripts.phpmd "phpmd src/,plugins/ ansi phpmd.xml --exclude src/Migrations"', path: 'apps/sylius/');

    # Add or update packages
    run('symfony composer require --dev phpmd/phpmd="*"', path: 'apps/sylius/');
    run('symfony composer require --dev phpunit/phpunit="^9.5" --with-all-dependencies', path: 'apps/sylius/');
    run('symfony composer require --dev friendsofphp/php-cs-fixer', path: 'apps/sylius/');

    # Fix for sylius and doctrine conflict
    io()->info('Add conflict for doctrine/orm in order to fix an issue in Sylius.');
    run("cat composer.json | jq --indent 4 '.conflict += {\"doctrine/orm\": \">= 2.15.2\"}' > composer.json.tmp", path: 'apps/sylius/');
    run('mv composer.json.tmp composer.json', path: 'apps/sylius/');
    io()->info('Run composer update after updating the composer.json file.');
    run('symfony composer update', path: 'apps/sylius/', timeout: false);

    # Copy dist files
    run('cp -Rv dist/sylius/* apps/sylius/');
    run('rm -rf dist');

    # Add missing directories
    fs()->mkdir('apps/sylius/plugins');
    fs()->touch('apps/sylius/plugins/.gitignore');
    fs()->mkdir('apps/sylius/src/Resources/config');
    fs()->touch('apps/sylius/src/Resources/config/.gitignore');

    # Ignore .php-cs-fixer.cache
    fs()->appendToFile('apps/sylius/.gitignore', '.php-cs-fixer.cache');

    # install
    run('make install', timeout: false);

    # GHA
    run('cp -Rv _.github/* .github/');
    run('rm -rf _.github');
    run('rm -rf apps/sylius/.github');

    # Clean up Sylius
    run('rm -rf apps/sylius/Dockerfile');
    run('rm -rf apps/sylius/themes/.gitignore');
    run('rm -rf apps/sylius/src/Entity/.gitignore');

    # Fix PHP CS
    run('make test.phpcs.fix', timeout: false);

    io()->success('Your project has been setup!');
    io()->comment('You can now commit the changes!');
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
