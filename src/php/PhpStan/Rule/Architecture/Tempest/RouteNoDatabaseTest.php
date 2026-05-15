<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class RouteNoDatabaseTest
{
    /**
     * @param non-empty-string $root
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::inNamespace($this->root),
                Selector::AnyOf(
                    Selector::appliesAttribute('Tempest\Http\Get'),
                    Selector::appliesAttribute('Tempest\Http\Post'),
                    Selector::appliesAttribute('Tempest\Http\Put'),
                    Selector::appliesAttribute('Tempest\Http\Patch'),
                    Selector::appliesAttribute('Tempest\Http\Delete'),
                ),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('Tempest\Database'))
            ->because('Route handlers must not depend on Tempest\Database.')
        ;
    }
}
