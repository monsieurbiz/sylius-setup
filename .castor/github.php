<?php

namespace MonsieurBiz\SyliusSetup\Castor\Github;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Completion\CompletionInput;

use function Castor\io;
use function Castor\run;
use const MonsieurBiz\SyliusSetup\Castor\SUGGESTED_PHP_VERSION;

const SUGGESTED_ENVS = ['staging', 'prod'];

function autocomple_suggested_env(CompletionInput $input): array
{
    return SUGGESTED_ENVS;
}

#[AsTask(name: 'init', namespace: 'github:project', description: 'Init project configuration')]
function initGithubProjectConfig(
    #[AsOption(description: 'Default branch name')] ?string $defaultBranch = 'develop',
    #[AsOption(description: 'PHP Version', autocomplete: 'MonsieurBiz\SyliusSetup\Castor\autocomple_php_version')] ?string $php = null,
    #[AsOption(description: 'Approving review count')] int $approvingReviewCount = 1,
    #[AsOption(description: 'Autolink prefix, example: TICKET-')] ?string $autoLinkPrefix = null,
    #[AsOption(description: 'Autolink url template, example: https://example.com/issues/<num>')] ?string $autoLinkUrlTemplate = null,
): void
{
    // Try to read the `.php-version` file
    if (!$php && file_exists('.php-version')) {
        $php = trim(file_get_contents('.php-version'));
    }
    // Ask for PHP version if not provided
    $phpVersion = $php ?? io()->ask('Which PHP do you want?', SUGGESTED_PHP_VERSION);

    // Github details for API calls
    $ghToken = trim(capture('gh auth token', allowFailure: false));
    $ghRepo = trim(capture('gh repo view --json nameWithOwner --jq .nameWithOwner | cat', allowFailure: false));

    // Check if autolink reference exists and create it if not
    if ($autoLinkPrefix && $autoLinkUrlTemplate) {
        $autolink = capture(sprintf('gh api /repos/%s/autolinks --jq \'.[] | select(.key_prefix=="%s") | .id\'', $ghRepo, $autoLinkPrefix), allowFailure: true);
        if (!$autolink) {
            run(sprintf('gh api --silent --method POST -f key_prefix="%s" -f url_template="%s" -F is_alphanumeric=true "/repos/%s/autolinks"', $autoLinkPrefix, $autoLinkUrlTemplate, $ghRepo));
        }
    }

    // Create "develop" branch, push it and change default branch to "develop"
    run(sprintf('git checkout -b %s', $defaultBranch), quiet: true, allowFailure: true); // Allow failure in case the branch already exists
    run(sprintf('git push -u origin %s', $defaultBranch), quiet: true);
    $ghDefaultBranch = capture(sprintf('gh api --method PATCH -f default_branch=%s "/repos/%s" --jq .default_branch', $defaultBranch, $ghRepo));
    if ($ghDefaultBranch !== $defaultBranch) {
        io()->error('Default branch not updated!');
        return;
    }

    // Protect "develop" branch
    $command = <<<CMD
    curl --silent -L \
        curl -L \
        -X PUT \
        -H "Accept: application/vnd.github+json" \
        -H "Authorization: Bearer $ghToken" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        https://api.github.com/repos/$ghRepo/branches/$defaultBranch/protection \
        -d '{"required_status_checks":{"strict":true,"contexts":["Tests (PHP $phpVersion)"]}, "enforce_admins": null, "required_pull_request_reviews": {"required_approving_review_count":$approvingReviewCount}, "restrictions": null,"required_conversation_resolution":true}'
    CMD;
    run($command, quiet: true);
    run(sprintf('gh api --silent --method POST "/repos/%s/branches/%s/protection/required_signatures"', $ghRepo, $defaultBranch));

    // Allow "Auto-merge" and "Automatically delete head branches"
    run(sprintf('gh api --silent --method PATCH -f allow_auto_merge=true -f delete_branch_on_merge=true "/repos/%s"', $ghRepo));

    io()->success('Project configuration done!');
}

#[AsTask(name: 'init', namespace: 'github:env', description: 'Init environment (and variables if needed)')]
function initGithubEnv(
    #[AsOption(description: 'Kind of environment', autocomplete: 'MonsieurBiz\SyliusSetup\Castor\Github\autocomple_suggested_env')] ?string $environment = null,
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

    run('gh secret set -e ' . $environment . ' CLEVER_TOKEN -b ' . ($token ?? io()->ask('CLEVER_TOKEN?')));
    run('gh secret set -e ' . $environment . ' CLEVER_SECRET -b ' . ($secret ?? io()->ask('CLEVER_SECRET?')));

    if (null !== $branch || null !== $url || true === $setupEnvs || io()->confirm('Would you like to setup the repository variables?', false)) {
        initGithubVariables($environment, $branch, $url);
    }
}

#[AsTask(name: 'init', namespace: 'github:variables', description: 'Init repository variables')]
function initGithubVariables(
    #[AsOption(description: 'Kind of environment', autocomplete: 'MonsieurBiz\SyliusSetup\Castor\Github\autocomple_suggested_env')] ?string $environment = null,
    #[AsOption(description: 'Production branch name')] ?string $branch = null,
    #[AsOption(description: 'Production URL')] ?string $url = null,
): void {
    run('gh variable set PRODUCTION_BRANCH -b ' . ($branch ?? io()->ask('PRODUCTION_BRANCH?', 'master')));
    run('gh variable set PRODUCTION_URL -b ' . ($url ?? io()->ask('PRODUCTION_URL?', 'https://www.monsieurbiz.com')));
}
