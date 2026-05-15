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
final readonly class ApplicationNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $application
     * @param non-empty-string $interface
     */
    public function __construct(
        private string $application,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->application,
            $this->interface,
            'Application must not depend on Interface.',
        );
    }
}
