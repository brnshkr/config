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
final readonly class DomainNoInfrastructureTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $domain
     * @param non-empty-string $infrastructure
     */
    public function __construct(
        private string $domain,
        private string $infrastructure,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->domain,
            $this->infrastructure,
            'Domain must not depend on Infrastructure.',
        );
    }
}
