<?php

declare(strict_types=1);

use Brnshkr\Config\Rector\Rule\FencedCodeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;

return RectorConfig::configure()
    ->withFileExtensions(['php', 'md'])
    ->withSets([__DIR__ . '/../../../../../../src/php/Rector/config/fenced-code.php'])
    ->withRules([
        RemoveAlwaysElseRector::class,
        RemoveEmptyClassMethodRector::class,
        SimplifyIfReturnBoolRector::class,
    ])
    ->withConfiguredRule(FencedCodeRector::class, [
        FencedCodeRector::SKIPS => [
            RemoveAlwaysElseRector::class,
        ],
    ])
;
