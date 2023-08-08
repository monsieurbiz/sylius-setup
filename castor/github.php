<?php

namespace MonsieurBiz\SyliusSetup\Castor\Github;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

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
