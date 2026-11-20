<?php

declare(strict_types=1);

use Brnshkr\Config\Mate\DisableToolsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @internal
 */
return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    if (DisableToolsCompilerPass::isNeeded()) {
        $containerBuilder->addCompilerPass(new DisableToolsCompilerPass());

        return;
    }

    $containerConfigurator->parameters()
        ->set('env(BRNSHKR_TARGET_PREFIX)', '')
        ->set('mate.invocation', 'make %env(BRNSHKR_TARGET_PREFIX)%mate --')
        ->set('matesofmate_composer.custom_command', [
            'make',
            '--no-print-directory',
            'NO_COLOR=1',
            '%env(BRNSHKR_TARGET_PREFIX)%composer',
            '--',
        ])
        ->set('matesofmate_phpstan.custom_command', [
            'make',
            '--no-print-directory',
            'NO_COLOR=1',
            '%env(BRNSHKR_TARGET_PREFIX)%phpstan-dry-run',
            '_COMMAND=',
            '--',
        ])
        ->set('matesofmate_phpunit.custom_command', [
            'make',
            '--no-print-directory',
            'NO_COLOR=1',
            '%env(BRNSHKR_TARGET_PREFIX)%phpunit',
            '--',
        ])
        ->set('matesofmate_rector.custom_command', [
            'make',
            '--no-print-directory',
            'NO_COLOR=1',
            '%env(BRNSHKR_TARGET_PREFIX)%rector',
            '_COMMAND=',
            '--',
        ])
    ;
};
