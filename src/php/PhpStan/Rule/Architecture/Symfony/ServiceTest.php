<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

use function sprintf;

/**
 * Enforce Symfony service layer isolation from HTTP and controllers.
 *
 * Two checks under `<root>\Service`:
 *   - Services may not depend on `Symfony\Component\HttpFoundation` — services are HTTP-agnostic.
 *   - Services may not depend on `<root>\Controller\*` — services are not called upward.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ServiceTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ServiceTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace
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
