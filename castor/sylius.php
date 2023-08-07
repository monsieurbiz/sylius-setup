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
        'monsieurbiz/sylius-admin-better-login-plugin',
        'monsieurbiz/sylius-advanced-option-plugin',
        'monsieurbiz/sylius-alert-message-plugin',
        'monsieurbiz/sylius-anti-spam-plugin',
        'monsieurbiz/sylius-cms-page-plugin',
        'monsieurbiz/sylius-coliship-plugin',
        'monsieurbiz/sylius-contact-request-plugin',
        'monsieurbiz/sylius-homepage-plugin',
        'monsieurbiz/sylius-media-manager-plugin',
        'monsieurbiz/sylius-menu-plugin',
        'monsieurbiz/sylius-no-commerce-plugin',
        'monsieurbiz/sylius-order-history-plugin',
        'monsieurbiz/sylius-rich-editor-plugin',
        'monsieurbiz/sylius-sales-reports-plugin',
        'monsieurbiz/sylius-search-plugin',
        'monsieurbiz/sylius-settings-plugin',
        'monsieurbiz/sylius-shipping-slot-plugin',
    ];

    $question = new ChoiceQuestion(
        'Please select the plugins you want to install',
        $plugins
    );
    $question->setMultiselect(true);

    $selectedPlugins = io()->askQuestion($question);

    io()->info('Installing ' . implode(', ', $selectedPlugins) . '…');
    $pluginsAsString = implode(' ', $selectedPlugins);
    run('symfony composer require ' . $pluginsAsString, path: 'apps/sylius');
}
