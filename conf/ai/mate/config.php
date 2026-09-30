<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @internal
 */
return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('mate.invocation', './scripts/mate.php')
        ->set('matesofmate_composer.custom_command', [
            'make',
            'NO_COLOR=1',
            'composer',
            '--',
        ])
        ->set('matesofmate_phpstan.custom_command', [
            'make',
            'NO_COLOR=1',
            'phpstan',
            '_COMMAND=',
            '--',
        ])
        ->set('matesofmate_phpunit.custom_command', [
            'make',
            'NO_COLOR=1',
            'pest',
            '--',
        ])
        ->set('matesofmate_rector.custom_command', [
            'make',
            'NO_COLOR=1',
            'rector',
            '_COMMAND=',
            '--',
        ])
    ;
};
