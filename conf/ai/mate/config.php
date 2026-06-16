<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @internal
 */
return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('matesofmate_phpunit.custom_command', [
            './vendor/bin/pest',
            '--configuration',
            './conf/phpunit.dist.xml',
        ])
    ;
};
