<?php

declare(strict_types=1);

namespace Brnshkr\Config\Rector;

use Brnshkr\Config\Rector\Rule\FencedCodeRector;
use Rector\Application\RectorRegistry;
use Rector\Config\RectorConfig;
use Rector\Contract\Rector\RectorInterface;
use Rector\Rector\RectorRunner;

/**
 * @internal Brnshkr\Config
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(FencedCodeRector::class);

    /** @disregard P1013 Rector's prefixed container is not picked up by intelephense */
    $rectorConfig->afterResolving(
        FencedCodeRector::class,
        static function (FencedCodeRector $fencedCodeRector) use ($rectorConfig): void {
            /** @disregard P1013 Rector's prefixed container is not picked up by intelephense */
            $fencedCodeRector->setRectors(
                $rectorConfig->make(RectorRunner::class),
                clone $rectorConfig->make(RectorRegistry::class),
                $rectorConfig->findByContract(RectorInterface::class),
            );
        },
    );
};
