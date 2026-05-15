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
final readonly class InfrastructureNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $infrastructure
     * @param non-empty-string $interface
     */
    public function __construct(
        private string $infrastructure,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->infrastructure,
            $this->interface,
            'Infrastructure must not depend on Interface.',
        );
    }
}
