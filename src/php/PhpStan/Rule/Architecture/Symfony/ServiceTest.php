<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class ServiceTest
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
        yield self::buildNamespaceIsolationRule(
            $this->root . '\Service',
            'Symfony\Component\HttpFoundation',
            'Services must not depend on Symfony\Component\HttpFoundation; services must be HTTP-agnostic.',
        );

        yield self::buildNamespaceIsolationRule(
            $this->root . '\Service',
            $this->root . '\Controller',
            sprintf('Services must not depend on %s\Controller\*; services are not called upward.', $this->root),
        );
    }
}
