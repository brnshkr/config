<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Ddd;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class InterfaceNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $interface
     * @param non-empty-string $infrastructure
     */
    public function __construct(
        private string $interface,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->interface,
            $this->infrastructure,
            'Interface must not depend on Infrastructure.',
        );
    }
}
