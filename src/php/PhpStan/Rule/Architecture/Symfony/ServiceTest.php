<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

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
     * @param non-empty-list<non-empty-string> $roots root namespaces, one per module
     */
    public function __construct(
        private array $roots,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        foreach ($this->roots as $root) {
            yield self::buildNamespaceIsolationRule(
                $root . '\Service',
                'Symfony\Component\HttpFoundation',
                'Services must not depend on Symfony\Component\HttpFoundation; services must be HTTP-agnostic.',
            );

            yield self::buildNamespaceIsolationRule(
                $root . '\Service',
                $root . '\Controller',
                sprintf('Services must not depend on %s\Controller\*; services are not called upward.', $root),
            );
        }
    }
}
