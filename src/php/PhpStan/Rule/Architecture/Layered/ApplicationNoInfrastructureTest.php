<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Layered;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class ApplicationNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $application
     * @param non-empty-string $infrastructure
     */
    public function __construct(
        private string $application,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->application,
            $this->infrastructure,
            'Application must not depend on Infrastructure.',
        );
    }
}
