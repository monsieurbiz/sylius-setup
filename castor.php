<?php

namespace MonsieurBiz\SyliusSetup\Castor;

use Castor\Attribute\AsTask;
use Castor\GlobalHelper;
use Symfony\Component\Console\Question\Question;

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

    # Fix for sylius and doctrine conflict
    io()->info('Add conflict for doctrine/orm in order to fix an issue in Sylius.');
    run("cat composer.json | jq --indent 4 '.conflict += {\"doctrine/orm\": \">= 2.15.2\"}' > composer.json.tmp", path: 'apps/sylius/');
    run('mv composer.json.tmp composer.json', path: 'apps/sylius/');
    io()->info('Run composer update after updating the composer.json file.');
    run('symfony composer update', path: 'apps/sylius/', timeout: false);

    # install
    run('make install', timeout: false);

    # GHA
    run('rm -rf .github');
    run('mv _.github .github');

    io()->success('Your project has been setup!');
    io()->comment('You can now commit the changes!');
}

#[AsTask(name: 'init', namespace: 'github:env', description: 'Init environment (and variables if needed)')]
function initGithubEnv(): void
{
    $token = trim(run('gh auth token', quiet: true)->getOutput());
    $repo = trim(run('gh repo view --json nameWithOwner --jq .nameWithOwner | cat', quiet: true)->getOutput());
    $environment = io()->ask('Which environment?', 'staging');
    $command = <<<CMD
    curl --silent -L \
    -X PUT \
    -H "Accept: application/vnd.github+json" \
    -H "Authorization: Bearer $token" \
    -H "X-GitHub-Api-Version: 2022-11-28" \
    https://api.github.com/repos/$repo/environments/$environment \
    -d '{"deployment_branch_policy":{"protected_branches":false,"custom_branch_policies":true}}'
    CMD;
    run($command, quiet: true);

    $branch = io()->ask('Deployment branch?', 'master');
    run(sprintf(
        'gh api --silent --method POST -H "Accept: application/vnd.github+json" "/repos/%1$s/environments/%2$s/deployment-branch-policies" -f name=%3$s',
        $repo,
        $environment,
        $branch
    ));

    $variables = [
        'CLEVER_SECRET',
        'CLEVER_TOKEN',
    ];
    foreach ($variables as $variable) {
        run('gh secret set -e ' . $environment . ' ' . $variable . ' -b ' . io()->ask("$variable?"));
    }

    if (io()->confirm('Would you like to setup the repository variables?', true)) {
        initGithubVariables();
    }
}

#[AsTask(name: 'init', namespace: 'github:variables', description: 'Init repository variables')]
function initGithubVariables(): void
{
    $variables = [
        'PRODUCTION_BRANCH' => 'master',
        'PRODUCTION_URL' => null,
    ];
    foreach ($variables as $variable => $value) {
        run('gh variable set ' . $variable . ' -b ' . io()->ask($variable . '?', $value));
    }
}
