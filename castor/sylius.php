<?php

namespace MonsieurBiz\SyliusSetup\Castor\Sylius;

use Castor\Attribute\AsTask;

use Symfony\Component\Console\Question\ChoiceQuestion;
use function Castor\get_application;
use function Castor\get_output;
use function Castor\io;
use function Castor\run;

function getPlugins(): array
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
        'monsieurbiz/sylius-order-history-plugin' => function () {
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-rich-editor-plugin' => function () {},
        'monsieurbiz/sylius-sales-reports-plugin' => function () {},
        'monsieurbiz/sylius-search-plugin' => function () {
            io()->info('Install Elasticseach with analysis-icu and analysis-phonetic plugins, or add it to your docker stack');
            io()->info('Implement the interface `\MonsieurBiz\SyliusSearchPlugin\Entity\Product\SearchableInterface` in your ProductAttribute and ProductOption entities.');
            io()->info('Use the trait `\MonsieurBiz\SyliusSearchPlugin\Model\Product\SearchableTrait` in your ProductAttribute and ProductOption entities.');
            while (!io()->confirm('Have you updated your Customer entity correctly?', false));
            run('symfony console doctrine:migrations:diff --namespace="App\Migrations" || true', path: 'apps/sylius'); // Generate app migration
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run app migrations
            io()->info('Run `monsieurbiz:search:populate` symfony command to populate ES and/or add this command in `clevercloud/functions.sh`');
        },
        'monsieurbiz/sylius-settings-plugin' => function () {},
        'monsieurbiz/sylius-shipping-slot-plugin' => function () {
             // Update Entities - User operation
             io()->info('Implement the interface `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\OrderInterface` in your Order entity.');
             io()->info('Use the trait `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\OrderTrait` in your Order entity.');
             io()->info('Implement the interface `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ProductVariantInterface` in your ProductVariant entity.');
             io()->info('Use the trait `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ProductVariantTrait` in your ProductVariant entity.');
             io()->info('Implement the interface `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ShipmentInterface` in your Shipment entity.');
             io()->info('Use the trait `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ShipmentTrait` in your Shipment entity.');
             io()->info('Implement the interface `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ShippingMethodInterface` in your ShippingMethod entity.');
             io()->info('Use the trait `\MonsieurBiz\SyliusShippingSlotPlugin\Entity\ShippingMethodTrait` in your ShippingMethod entity.');
             while (!io()->confirm('Have you updated your entities correctly?', false));
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-theme-companion-plugin' => function () {
            run('symfony composer patch-add sylius/theme-bundle "Remove performNoDeepMerging to authorise theme folder" "https://patch-diff.githubusercontent.com/raw/Sylius/SyliusThemeBundle/pull/128.patch"', path: 'apps/sylius');
            run('symfony composer update sylius/theme-bundle', path: 'apps/sylius');
        },
        'monsieurbiz/sylius-tailwind-theme' => function () {
            run('symfony composer require monsieurbiz/sylius-tailwind-theme', path: 'apps/sylius');
            run('yarn install --force', path: 'apps/sylius');
            run('yarn encore prod', path: 'apps/sylius');
        },
        'synolia/sylius-scheduler-command-plugin' => function () {
            io()->info('Update your clevercloud/cron.json by adding this new line:');
            io()->block('"0 0 * * *      $ROOT/clevercloud/symfony_console.sh synolia:scheduler-run",');
            while (!io()->confirm('Have you updated your cron.json?', false));
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'stefandoorn/sitemap-plugin' => function () {
            io()->info('Follow the installation guide: https://github.com/stefandoorn/sitemap-plugin#installation');
            io()->info('Update your clevercloud/cron.json by adding this new line:');
            io()->block('"0 2 * * *      $ROOT/clevercloud/symfony_console.sh sylius:sitemap:generate",');
            while (!io()->confirm('Did you follow the installation guide and updated your cron.json?', false));
        },
        'synolia/sylius-gdpr-plugin' => function () {
            io()->info('Follow the installation guide: https://github.com/synolia/SyliusGDPRPlugin#installation');
            while (!io()->confirm('Did you follow the installation guide?', false));
        },
        'sylius/admin-order-creation-plugin' => function () {
            io()->info('Follow the installation guide: https://github.com/Sylius/AdminOrderCreationPlugin#installation');
            while (!io()->confirm('Did you follow the installation guide?', false));
        },
        'sylius/invoicing-plugin' => function () {
            io()->info('Follow the installation guide: https://github.com/Sylius/InvoicingPlugin?tab=readme-ov-file#installation');
            while (!io()->confirm('Did you follow the installation guide?', false));
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
        'sylius/refund-plugin' => function () {
            io()->info('Follow the installation guide: https://github.com/Sylius/RefundPlugin?tab=readme-ov-file#installation');
            while (!io()->confirm('Did you follow the installation guide?', false));
            run('symfony console doctrine:migrations:migrate -n', path: 'apps/sylius'); // Run plugin migrations
        },
    ];
    ksort($plugins);

    return $plugins;
}

#[AsTask(name: 'plugins:list', namespace: 'sylius', description: 'List Sylius plugins')]
function listPlugins(): void
{
    $plugins = getPlugins();

    $rows = [];
    foreach ($plugins as $pluginIdentifier => $callback) {
        $rows[] = [$pluginIdentifier];
    }

    io()->table(['Plugin\'s composer identifier'], $rows);
}

#[AsTask(name: 'plugins:install', namespace: 'sylius', description: 'Install Sylius plugins locally')]
function installPlugins(): void
{
    $plugins = getPlugins();

    $question = new ChoiceQuestion(
        'Please select the plugins you want to install',
        array_keys($plugins)
    );
    $question->setMultiselect(true);

    $selectedPlugins = io()->askQuestion($question);

    run('symfony composer config --no-plugins --json extra.symfony.endpoint \'["https://api.github.com/repos/Sylius/SyliusRecipes/contents/index.json?ref=flex/main","https://api.github.com/repos/monsieurbiz/symfony-recipes/contents/index.json?ref=flex/master","flex://defaults"]\'', path: 'apps/sylius');

    foreach ($selectedPlugins as $selectedPlugin) {
        io()->info('Installing ' . $selectedPlugin . '…');
        run('symfony composer require ' . $selectedPlugin, path: 'apps/sylius', timeout: 120);
        $plugins[$selectedPlugin]();
        io()->success('Successful installation of ' . $selectedPlugin);
    }

    run('rm -rf apps/sylius/var/cache');

    if (io()->ask('Would you like to re-run the fixtures?')) {
        run('make sylius.fixtures');
    }
}
