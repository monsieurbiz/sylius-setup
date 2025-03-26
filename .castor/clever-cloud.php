<?php

namespace MonsieurBiz\SyliusSetup\Castor\Clevercloud;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use Symfony\Component\Console\Question\ChoiceQuestion;
use function Castor\context;
use function Castor\io;
use function Castor\run;
use const MonsieurBiz\SyliusSetup\Castor\SUGGESTED_DEFAULT_ENV;
use const MonsieurBiz\SyliusSetup\Castor\SUGGESTED_ENVS;

#[AsTask(name: 'setup', namespace: 'clevercloud', description: 'Init Clever Cloud application and addons')]
function cleverSetup(
    #[AsOption(description: 'Code of your application')] string $code = 'sylius',
    #[AsOption(description: 'ID of your organisation on Clever Cloud')] ?string $org = null,
    #[AsOption(description: 'Region of your infrastructure on Clever Cloud')] ?string $region = null,
    #[AsOption(description: 'Environment (prod or staging)')] ?string $env = null,
    #[AsOption(description: 'PHP Plan')] ?string $php = null,
    #[AsOption(description: 'MySQL plan')] ?string $mysql = null,
    #[AsOption(description: 'Htpasswd username')] ?string $username = null,
    #[AsOption(description: 'Htpasswd password')] ?string $password = null,
    #[AsOption(description: 'Hostname (use %s to use project ID: {code}-{env})')] string $hostname = null,
): void {
    cleverIsRequired();

    $buckets = [
        'media' => '/apps/sylius/public/media',
        'log' => '/apps/sylius/var/log',
        'private' => '/apps/sylius/private',
    ];
    $project = initProject($code, $org, $region, $env, $hostname, $buckets);
    if (io()->confirm("Do you want to setup credentials for protected environment?", null !== $username || null !== $password)) {
        setupHtpasswd($username, $password);
    }
    setupPHP($project, $php);
    setupMySQL($project, $mysql);
    createFSBuckets($project);
    setupEnv($project);
    setupClevercloudFiles($project);
    setupDomain($project, $hostname);
    if (io()->confirm("Do you want to setup credentials for protected environment?", null !== $username || null !== $password)) {
        setupHtpasswd($username, $password);
    }

    io()->success('Your project is ready!');
}

function initProject(string $type, ?string $org = null, ?string $region = null, ?string $env = null, ?string $hostname = null, ?array $buckets = null): object
{
    $project = new class {
        public string $org;
        public string $region;
        public string $env;
        public string $id;
        public string $hostname;
        public array $buckets;
    };

    $project->org = $org ?? io()->ask('What is the organization ID from Clever cloud (its name or its code starting with org_)?');
    $project->region = $region ?? io()->ask('What is the region for you infrastructure?', 'par');
    $env = $env ?? io()->askQuestion(new ChoiceQuestion('Which environment?', SUGGESTED_ENVS, SUGGESTED_DEFAULT_ENV));
    if ($env === 'production') {
        $env = 'prod';
    }
    $project->env = $env;
    $project->id = sprintf('%s-%s', $type, $project->env);
    $suggestedHostName = $env === 'prod' ? 'project.preprod.monsieurbiz.cloud' : 'project.staging.monsieurbiz.cloud';
    $project->hostname = $hostname ?? io()->ask('What is the hostname of your project?', $suggestedHostName);
    $project->buckets = $buckets ?? [];

    return $project;
}

function setupPHP(object $project, ?string $plan = null): void
{
    if (null === $plan) {
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
        $plan = io()->askQuestion(
            new ChoiceQuestion(
                'Which plan?',
                array_reduce($plans, fn($carry, $plan) => $carry + [$plan[0] => $plan[0]], []),
                'XS'
            )
        );
    }

    run(sprintf(
        'clever create --type php --region "%1$s" --org "%2$s" --alias "%3$s" "%4$s"',
        $project->region,
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

function setupMySQL(object $project, ?string $plan = null): void
{
    if (null === $plan) {
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
        $plan = io()->askQuestion(
            new ChoiceQuestion(
                'Which plan?',
                array_reduce($plans, fn($carry, $plan) => $carry + [$plan[0] => $plan[0]], []),
                'xs_tny'
            )
        );
    }

    run(sprintf(
        'clever addon create mysql-addon --plan "%1$s" --org "%2$s" --link "%3$s" "%4$s-db"',
        $plan,
        $project->org,
        $project->env,
        $project->id
    ));
}


function createFSBuckets(object $project): void
{
    $buckets = $project->buckets;
    foreach ($buckets as $bucketName => $bucketPath) {
        // Uncomment if you need the bucket path
        // $bucketPath = sprintf($bucketPath, $project->id);
        createFSBucket($project, $bucketName);
    }
}

function createFSBucket(object $project, string $bucket): void
{
    run(sprintf(
        'clever addon create fs-bucket --region "%1$s" --plan "s" --org "%2$s" --link "%3$s" "%4$s-fs-%5$s"',
        $project->region,
        $project->org,
        $project->env,
        $project->id,
        $bucket
    ));
}

function getFsBucketHost(object $project, string $bucketName): ?string
{
    // List addons
    $addons = run(sprintf('clever addon list -o %1$s --format=json', $project->org), context: context()->withQuiet())->getOutput();
    $addons = json_decode($addons, true);


    if (!is_array($addons)) {
        io()->warning('No addon found at all for this organisation');
        return null;
    }

    $addonName = sprintf('%s-fs-%s', $project->id, $bucketName);

    // Retrieve addons for the current application
    $addons = array_filter($addons, function ($addon) use ($addonName) {
        return str_starts_with($addon['name'], $addonName);
    });

    if (empty($addons)) {
        io()->warning('No addon found starting with the application name : ' . $addonName);
        return null;
    }

    $bucketAddon = current($addons);
    $bucketAddonEnvs = run(sprintf('clever addon env %1$s --format=json', $bucketAddon['addonId']), context: context()->withQuiet())->getOutput();
    $bucketAddonEnvs = json_decode($bucketAddonEnvs, true);

    if (!isset($bucketAddonEnvs['BUCKET_HOST'])) {
        io()->warning('No BUCKET_HOST found for the bucket ' . $bucketName);
        return null;
    }

    return $bucketAddonEnvs['BUCKET_HOST'];
}

function setupEnv(object $project): void
{
    $setEnv = function ($name, $value) use ($project): void {
        run(sprintf(
            'clever env --alias %1$s set %2$s "%3$s"',
            $project->env,
            $name,
            $value
        ));
    };

    $bucketHost = trim(run(sprintf('clever env --alias %1$s | grep BUCKET_HOST | cut -d"=" -f2', $project->env), context: context()->withQuiet())->getOutput());
    $databaseUri = trim(run(sprintf('clever env --alias %1$s | grep MYSQL_ADDON_URI | cut -d"=" -f2', $project->env), context: context()->withQuiet())->getOutput());
    $runFixtures = io()->confirm('Do you want to run fixtures?', true);
    $phpVersion = io()->ask('Which PHP version?', default: '8.3');

    // Buckets env vars
    if (!empty($project->buckets)) {
        $bucketCount = 0;
        foreach ($project->buckets as $bucketName => $bucketPath) {
            $bucketPath = sprintf($bucketPath, $project->id);
            $bucketHost = (string) getFsBucketHost($project, $bucketName);
            $setEnv($bucketCount === 0 ? 'CC_FS_BUCKET' : 'CC_FS_BUCKET_' . $bucketCount, $bucketPath . ':' . $bucketHost);
            $bucketCount++;
        }
    }

    $setEnv('CC_PHP_VERSION', $phpVersion);
    $setEnv('CC_FS_BUCKET', '/apps/sylius/public/media:' . $bucketHost);
    $setEnv('CC_WEBROOT', '/apps/sylius/public');
    $setEnv('CC_POST_BUILD_HOOK', './clevercloud/post_build_hook.sh');
    $setEnv('CC_PRE_BUILD_HOOK', './clevercloud/pre_build_hook.sh');
    $setEnv('CC_PRE_RUN_HOOK', './clevercloud/pre_run_hook.sh');
    $setEnv('CC_RUN_SUCCEEDED_HOOK', './clevercloud/run_succeeded_hook.sh');
    $setEnv('CC_TROUBLESHOOT', 'true');
    $setEnv('CC_WORKER_COMMAND_1', 'clevercloud/symfony_console.sh messenger:consume main --time-limit=300 --failure-limit=1 --memory-limit=512M --sleep=5');
    $setEnv('CC_WORKER_RESTART', 'always');
    $setEnv('CC_WORKER_RESTART_DELAY', '5');
    $setEnv('APP_ENV', $project->env);
    $setEnv('APP_DEBUG', '0');
    $setEnv('DATABASE_URL', $databaseUri);
    $setEnv('ENABLE_APCU', 'true');
    $setEnv('MAINTENANCE', 'false');
    $setEnv('IS_PROTECTED', 'true');
    $setEnv('RUN_FIXTURES', $runFixtures ? 'true' : 'false');
    $setEnv('APP_SECRET', md5(random_bytes(32)));
    $setEnv('HTTPS', 'on');
    $setEnv('HTTP_AUTH_CLEAR', 'false');
    $setEnv('HTTP_AUTH_USERNAME', '');
    $setEnv('HTTP_AUTH_PASSWORD', '');
    $setEnv('MEMORY_LIMIT', '256M');
    $setEnv('SYLIUS_FIXTURES_HOSTNAME', $project->hostname);
    $setEnv('APP_JPEGOPTIM_BINARY', '/usr/host/bin/jpegoptim');
    $setEnv('APP_PNGQUANT_BINARY', '/usr/host/bin/pngquant');
    $setEnv('WKHTMLTOIMAGE_PATH', '/usr/host/bin/wkhtmltoimage');
    $setEnv('WKHTMLTOPDF_PATH', '/usr/host/bin/wkhtmltopdf');
}

function setupClevercloudFiles(object $project): void
{

}

function setupDomain(object $project): void
{
    run(
        sprintf(
            'clever domain add --alias "%2$s" "%1$s"',
            $project->hostname,
            $project->env
        )
    );
}

#[AsTask(name: 'htpasswd', namespace: 'clevercloud', description: 'Fill the htpasswd file with new credentials')]
function setupHtpasswd(
    #[AsOption] ?string $username = null,
    #[AsOption] ?string $password = null,
): void
{
    $username = $username ?? io()->ask("Username") ?? 'admin';
    $password = $password ?? io()->askHidden("Password") ?? 'password';
    run(sprintf('htpasswd -b clevercloud/.htpasswd "%s" "%s"', $username, $password));
}

function cleverIsRequired(): void
{
    $clever = run('clever --version', context: context()->withQuiet()->withAllowFailure());
    if (!$clever->isSuccessful()) {
        io()->error('You should install the Clever Cloud CLI first: https://www.clever-cloud.com/doc/clever-tools/getting_started/');
        exit(1);
    }

    $profile = run('clever profile', context: context()->withQuiet()->withAllowFailure());
    if (!$profile->isSuccessful()) {
        io()->error('You should login to your Clever Cloud account first using the "clever login" command.');
        exit(1);
    }
}
