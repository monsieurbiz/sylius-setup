<?php

namespace MonsieurBiz\SyliusSetup\Castor\Github;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Castor\io;
use function Castor\run;

const SUGGESTED_ENVS = ['staging', 'prod'];

#[AsTask(name: 'init', namespace: 'github:env', description: 'Init environment (and variables if needed)')]
function initGithubEnv(
    #[AsOption(description: 'Kind of environment', suggestedValues: SUGGESTED_ENVS)] ?string $environment = null,
    #[AsOption(description: 'CLEVER_TOKEN value')] ?string $token = null,
    #[AsOption(description: 'CLEVER_SECRET value')] ?string $secret = null,
    #[AsOption(description: 'Setup the repository variables')] ?bool $setupEnvs = null,
    #[AsOption(description: 'Production branch name')] ?string $branch = null,
    #[AsOption(description: 'Production URL')] ?string $url = null,
): void {
    // Github details for API calls
    $ghToken = trim(run('gh auth token', quiet: true, allowFailure: false)->getOutput());
    $ghRepo = trim(run('gh repo view --json nameWithOwner --jq .nameWithOwner | cat', quiet: true)->getOutput());

    $environment = $environment ?? io()->ask('Which kind of environment?', 'staging');
    $command = <<<CMD
    curl --silent -L \
    -X PUT \
    -H "Accept: application/vnd.github+json" \
    -H "Authorization: Bearer $ghToken" \
    -H "X-GitHub-Api-Version: 2022-11-28" \
    https://api.github.com/repos/$ghRepo/environments/$environment \
    -d '{"deployment_branch_policy":{"protected_branches":false,"custom_branch_policies":true}}'
    CMD;
    run($command, quiet: true);

    $deployBranch = $branch ?? io()->ask('Deployment branch?', 'master');
    run(sprintf(
        'gh api --silent --method POST -H "Accept: application/vnd.github+json" "/repos/%1$s/environments/%2$s/deployment-branch-policies" -f name=%3$s',
        $ghRepo,
        $environment,
        $deployBranch
    ));

    run('gh secret set -e ' . $environment . ' CLEVER_TOKEN -b ' . ($token) ?? io()->ask('CLEVER_TOKEN?'));
    run('gh secret set -e ' . $environment . ' CLEVER_SECRET -b ' . ($secret) ?? io()->ask('CLEVER_SECRET?'));

    if (null !== $branch || null !== $url || true === $setupEnvs || io()->confirm('Would you like to setup the repository variables?', false)) {
        initGithubVariables($environment, $branch, $url);
    }
}

#[AsTask(name: 'init', namespace: 'github:variables', description: 'Init repository variables')]
function initGithubVariables(
    #[AsOption(description: 'Kind of environment', suggestedValues: SUGGESTED_ENVS)] ?string $environment = null,
    #[AsOption(description: 'Production branch name')] ?string $branch = null,
    #[AsOption(description: 'Production URL')] ?string $url = null,
): void {
    run('gh variable set PRODUCTION_BRANCH -b ' . ($branch ?? io()->ask('PRODUCTION_BRANCH?', 'master')));
    run('gh variable set PRODUCTION_URL -b ' . ($url ?? io()->ask('PRODUCTION_URL?', 'https://www.monsieurbiz.com')));
}
