<?php

namespace MonsieurBiz\SyliusSetup\Castor\Sylius;

use Castor\Attribute\AsTask;

use Symfony\Component\Console\Question\ChoiceQuestion;
use function Castor\get_application;
use function Castor\get_output;
use function Castor\io;
use function Castor\run;

#[AsTask(name: 'plugins', namespace: 'sylius', description: 'Install Sylius plugins locally')]
function installPlugins(): void
{
    $plugins = [
        'monsieurbiz/sylius-admin-better-login-plugin' => function () {},
        // 'monsieurbiz/sylius-advanced-option-plugin' => function () {}, // Compatibility <1.11
        'monsieurbiz/sylius-alert-message-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-anti-spam-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
            // Update Customer Entity - User operation
            io()->info('Implement the interface `\MonsieurBiz\SyliusAntiSpamPlugin\Entity\QuarantineItemAwareInterface` in your Customer entity.');
            io()->info('Use the trait `\MonsieurBiz\SyliusAntiSpamPlugin\Entity\QuarantineItemAwareTrait` in your Customer entity.');
            while (!io()->confirm('Have you updated your Customer entity correctly?', false));
            run('symfony console doctrine:migrations:diff --namespace="App\Migrations" || true', path: 'apps/sylius'); // Generate app migration
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run app migrations
        },
        'monsieurbiz/sylius-cms-page-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-coliship-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius');
            run('symfony console doctrine:migrations:diff --namespace="App\Migrations" || true', path: 'apps/sylius'); // Generate plugin migration - Not in plugin
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run generated migration
        },
        'monsieurbiz/sylius-contact-request-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-homepage-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-media-manager-plugin' => function () {},
        'monsieurbiz/sylius-menu-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-no-commerce-plugin' => function () {},
        'monsieurbiz/sylius-order-history-plugin' => function () {},
        'monsieurbiz/sylius-rich-editor-plugin' => function () {},
        'monsieurbiz/sylius-sales-reports-plugin' => function () {},
        'monsieurbiz/sylius-search-plugin' => function () {},
        'monsieurbiz/sylius-settings-plugin' => function () {},
        'monsieurbiz/sylius-shipping-slot-plugin' => function () {

        },
        'monsieurbiz/sylius-theme-companion-plugin' => function () {
            run('symfony composer patch-add sylius/theme-bundle "Remove performNoDeepMerging to authorise theme folder" "https://patch-diff.githubusercontent.com/raw/Sylius/SyliusThemeBundle/pull/128.patch"', path: 'apps/sylius');
            run('symfony composer update sylius/theme-bundle', path: 'apps/sylius');
        },
        'monsieurbiz/sylius-tailwind-theme' => function () {
            run('symfony composer require monsieurbiz/sylius-tailwind-theme', path: 'apps/sylius');
            io()->info('Update the module.exports statement as well by adding the syliusTailwindThemeConfig variable.');
            while (!io()->confirm('Did you update your webpack.config.js file?', false));
            run('yarn install --force', path: 'apps/sylius');
            run('yarn encore prod', path: 'apps/sylius');
        },
        'synolia/sylius-scheduler-command-plugin' => function () {
            io()->info('Update your clevercloud/cron.json by adding this new line:');
            io()->block('"0 0 * * *      $ROOT/clevercloud/symfony_console.sh synolia:scheduler-run",');
            while (!io()->confirm('Have you updated your cron.json?', false));
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
    ];

    $question = new ChoiceQuestion(
        'Please select the plugins you want to install',
        array_keys($plugins)
    );
    $question->setMultiselect(true);

    $selectedPlugins = io()->askQuestion($question);

    run('symfony composer config --no-plugins --json extra.symfony.endpoint \'["https://api.github.com/repos/Sylius/SyliusRecipes/contents/index.json?ref=flex/main","https://api.github.com/repos/monsieurbiz/symfony-recipes/contents/index.json?ref=flex/master","flex://defaults"]\'', path: 'apps/sylius');

    foreach ($selectedPlugins as $selectedPlugin) {
        io()->info('Installing ' . $selectedPlugin . '…');
        run('symfony composer require ' . $selectedPlugin, path: 'apps/sylius');
        $plugins[$selectedPlugin]();
        io()->success('Successful installation of ' . $selectedPlugin);
    }

    run('rm -rf apps/sylius/var/cache');

    if (io()->ask('Would you like to re-run the fixtures?')) {
        run('make sylius.fixtures');
    }
}
