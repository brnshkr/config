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
final readonly class DomainNoInterfaceTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $domain
     * @param non-empty-string $interface
     */
    public function __construct(
        private string $domain,
        private string $interface,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->domain,
            $this->interface,
            'Domain must not depend on Interface.',
        );
    }
}
