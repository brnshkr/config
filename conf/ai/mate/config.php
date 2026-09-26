<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @internal
 */
return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('matesofmate_composer.custom_command', [
            'composer',
            '--working-dir=.',
        ])
        ->set('matesofmate_phpstan.custom_command', [
            './vendor/bin/phpstan',
            '--configuration=./conf/phpstan.dist.php',
        ])
        ->set('matesofmate_phpunit.custom_command', [
            './vendor/bin/pest',
            '--configuration=./conf/phpunit.xml',
        ])
        ->set('matesofmate_rector.custom_command', [
            './vendor/bin/rector',
        ])
    ;
};
