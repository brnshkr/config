<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class ModelTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $root
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        yield self::buildPlacementRule(
            $this->root,
            [Selector::extends('Illuminate\Database\Eloquent\Model')],
            'Models',
            'Eloquent models',
        );

        yield self::buildMustExtendRule(
            Selector::inNamespace($this->root . '\Models'),
            'Illuminate\Database\Eloquent\Model',
            'Eloquent models must extend Illuminate\Database\Eloquent\Model.',
        );

        yield self::buildNamespaceIsolationRule(
            $this->root . '\Models',
            'Illuminate\Http',
            'Eloquent models must not depend on Illuminate\Http; models are persistence objects.',
        );
    }
}
