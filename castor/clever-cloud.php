<?php

namespace MonsieurBiz\SyliusSetup\Castor\Clevercloud;

use Castor\Attribute\AsTask;
use Castor\Attribute\AsContext;

use Castor\Context;
use Symfony\Component\Console\Question\ChoiceQuestion;
use function Castor\io;
use function Castor\get_context;
use function Castor\run;
use MonsieurBiz\Castor\Clevercloud\Project;

#[AsTask(name: 'setup', namespace: 'clevercloud', description: 'Init Clever Cloud application and addons')]
function cleverSetup(): void
{
    cleverIsRequired();

    $project = initProject('sylius');
    setupPHP($project);
    setupMySQL($project);
    setupFSBucket($project);
    $hostname = setupEnv($project);
    setupClevercloudFiles($project);
    setupDomain($project, $hostname);

    io()->success('Your project is ready!');
}

function initProject(string $type): object
{
    $project = new class {
        public string $name;
        public string $org;
        public string $env;
        public string $id;
    };
    $project->name = io()->ask('What is the name of your project?');
    $project->org = io()->ask('What is the organization ID from Clever cloud (its name or its code starting with org_)?');
    $project->env = io()->askQuestion(new ChoiceQuestion('Which environment?', ['staging', 'prod'], 'prod'));
    $project->id = sprintf('%s-%s', $type, $project->env);

    return $project;
}

function setupPHP(object $project): void
{
    io()->title('PHP application');
    io()->table([
        'Plan',
        'vCPUs',
        'RAM',
    ], $plans = [
        ['nano', '1 shared', '512 Mio'],
        ['XS', '1', '1 Gio'],
        ['S', '2', '2 Gio'],
        ['M', '4', '4 Gio'],
        ['L', '6', '8 Gio'],
        ['XL', '8', '16 Gio'],
        ['2XL', '12', '24 Gio'],
        ['3XL', '16', '32 Gio'],
    ]);
    $plan = io()->askQuestion(new ChoiceQuestion(
        'Which plan?',
        array_reduce($plans, fn ($carry, $plan) => $carry + [$plan[0] => $plan[0]], []),
        'XS'
    ));

    run(sprintf(
        'clever create --type php --region par --org "%1$s" --alias "%2$s" "%3$s"',
        $project->org,
        $project->env,
        $project->id
    ));
    run(sprintf(
        'clever scale --alias "%1$s" --flavor %2$s',
        $project->env,
        $plan
    ));
}

function setupMySQL(object $project): void
{
    io()->title('MySQL addon');
    io()->table([
        'Plan',
        'Plan\'s name',
        'vCPUs',
        'RAM',
        'Disk size',
        'Connexions',
    ], $plans = [
        ['dev', 'DEV', 'shared', 'shared', '10 Mio', '5'],
        ['xxs_sml', 'XXS Small Space', '1', '512 Mio', '512 Mio', '15'],
        ['xxs_med', 'XXS Medium Space', '1', '512 Mio', '1 Gio', '15'],
        ['xxs_big', 'XXS Big Space', '1', '512 Mio', '2 Gio', '15'],
        ['xs_tny', 'XS Tiny Space', '1', '1 Gio', '2 Gio', '75'],
        ['xs_sml', 'XS Small Space', '1', '1 Gio', '5 Gio', '75'],
        ['xs_med', 'XS Medium Space', '1', '1 Gio', '10 Gio', '75'],
        ['xs_big', 'XS Big Space', '1', '1 Gio', '15 Gio', '75'],
        ['s_sml', 'S Small Space', '2', '2 Gio', '10 Gio', '125'],
        ['s_med', 'S Medium Space', '2', '2 Gio', '15 Gio', '125'],
        ['s_big', 'S Big Space', '2', '2 Gio', '20 Gio', '125'],
    ]);
    $plan = io()->askQuestion(new ChoiceQuestion(
        'Which plan?',
        array_reduce($plans, fn ($carry, $plan) => $carry + [$plan[0] => $plan[0]], []),
        'xs_tny'
    ));

    run(sprintf(
        'clever addon create mysql-addon --plan "%1$s" --org "%2$s" --link "%3$s" "%4$s-db"',
        $plan,
        $project->org,
        $project->env,
        $project->id
    ));
}

function setupFSBucket(object $project): void
{
    run(sprintf(
        'clever addon create fs-bucket --plan s --org "%1$s" --link "%2$s" "%3$s-fs"',
        $project->org,
        $project->env,
        $project->id
    ));
}

function setupEnv(object $project): string
{
    $setEnv = function ($name, $value) use ($project): void {
        run(sprintf(
            'clever env -a %1$s set %2$s %3$s',
            $project->env,
            $name,
            $value
        ));
    };

    $bucketHost = run(sprintf('clever env -a %1$s | grep BUCKET_HOST | cut -d"=" -f2', $project->env), quiet: true)->getOutput();
    $databaseUri = run(sprintf('clever env -a %1$s | grep MYSQL_ADDON_URI | cut -d"=" -f2', $project->env), quiet: true)->getOutput();
    $hostname = io()->ask('Which domain for fixtures?', default: sprintf('%s.cleverapps.io', $project->id));

    $setEnv('CC_PHP_VERSION', '8.2');
    $setEnv('CC_FS_BUCKET', '/apps/sylius/public/media:' . $bucketHost);
    $setEnv('CC_WEBROOT', '/apps/sylius/public');
    $setEnv('CC_POST_BUILD_HOOK', './clevercloud/post_build_hook.sh');
    $setEnv('CC_PRE_BUILD_HOOK', './clevercloud/pre_build_hook.sh');
    $setEnv('CC_PRE_RUN_HOOK', './clevercloud/pre_run_hook.sh');
    $setEnv('CC_RUN_SUCCEEDED_HOOK', './clevercloud/run_succeeded_hook.sh');
    $setEnv('CC_TROUBLESHOOT', 'false');
    $setEnv('CC_WORKER_COMMAND_1', '"clevercloud/symfony_console.sh messenger:consume main --time-limit=300 --failure-limit=1 --memory-limit=512M --sleep=5"');
    $setEnv('CC_WORKER_RESTART', 'always');
    $setEnv('CC_WORKER_RESTART_DELAY', '5');
    $setEnv('APP_ENV', $project->env);
    $setEnv('APP_DEBUG', '0');
    $setEnv('DATABASE_URI', $databaseUri);
    $setEnv('MAINTENANCE', 'false');
    $setEnv('IS_PROTECTED', 'true');
    $setEnv('APP_SECRET', md5(random_bytes(32)));
    $setEnv('HTTPS', 'on');
    $setEnv('MEMORY_LIMIT', '256M');
    $setEnv('SYLIUS_FIXTURES_HOSTNAME', $hostname);
    $setEnv('APP_JPEGOPTIM_BINARY', '/usr/host/bin/jpegoptim');
    $setEnv('APP_PNGQUANT_BINARY', '/usr/host/bin/pngquant');
    $setEnv('WKHTMLTOIMAGE_PATH', '/usr/host/bin/wkhtmltoimage');
    $setEnv('WKTHMLTOPDF_PATH', '/usr/host/bin/wkhtmltopdf');

    return $hostname;
}

function setupClevercloudFiles(object $project): void
{

}

function setupDomain(object $project, string $hostname): void
{
    run(sprintf(
        'clever domain add --alias "%2$s" "%1$s"',
        $hostname,
        $project->env
    ));
}

function cleverIsRequired(): void
{
    $clever = run('clever --version', quiet: true, allowFailure: true);
    if (!$clever->isSuccessful()) {
        io()->error('You should install the Clever Cloud CLI first: https://www.clever-cloud.com/doc/clever-tools/getting_started/');
        exit(1);
    }

    $profile = run('clever profile', quiet: true, allowFailure: true);
    if (!$profile->isSuccessful()) {
        io()->error('You should login to your Clever Cloud account first using the "clever login" command.');
        exit(1);
    }
}
