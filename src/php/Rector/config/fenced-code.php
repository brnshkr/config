<?php

declare(strict_types=1);

namespace Brnshkr\Config\Rector;

use Brnshkr\Config\Rector\Rule\FencedCodeRector;
use Rector\Config\RectorConfig;
use Rector\PhpParser\NodeTraverser\RectorNodeTraverser;

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
            // @phpstan-ignore brnshkr.internalUsage (Rector offers no public way to run its rules on a code block)
            $fencedCodeRector->setRectorNodeTraverser($rectorConfig->make(RectorNodeTraverser::class));
        },
    );
};
