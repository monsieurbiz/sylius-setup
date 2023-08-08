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
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-anti-spam-plugin' => function () {
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run plugin migrations
            // Update Customer Entity - User operation
            io()->info('Implements the interface `\MonsieurBiz\SyliusAntiSpamPlugin\Entity\QuarantineItemAwareInterface` in your Customer entity.');
            io()->info('Use the trait `\MonsieurBiz\SyliusAntiSpamPlugin\Entity\QuarantineItemAwareTrait` in your Customer entity.');
            while (!io()->confirm('Have you changed your Customer entity correctly ?', false));
            run('symfony console  doctrine:migrations:diff --namespace="App\Migrations" || true', path: 'apps/sylius'); // Generate app migration
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run app migrations
        },
        'monsieurbiz/sylius-cms-page-plugin' => function () {
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-coliship-plugin' => function () {},
        'monsieurbiz/sylius-contact-request-plugin' => function () {},
        'monsieurbiz/sylius-homepage-plugin' => function () {
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-media-manager-plugin' => function () {},
        'monsieurbiz/sylius-menu-plugin' => function () {
            run('symfony console doctrine:migrations:migrate', path: 'apps/sylius'); // Run plugin migrations
        },
        'monsieurbiz/sylius-no-commerce-plugin' => function () {},
        'monsieurbiz/sylius-order-history-plugin' => function () {},
        'monsieurbiz/sylius-rich-editor-plugin' => function () {},
        'monsieurbiz/sylius-sales-reports-plugin' => function () {},
        'monsieurbiz/sylius-search-plugin' => function () {},
        'monsieurbiz/sylius-settings-plugin' => function () {},
        'monsieurbiz/sylius-shipping-slot-plugin' => function () {},
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
        io()->info('Installed ' . $selectedPlugin . '…');
    }

    run('rm -rf apps/sylius/var/cache');
}
