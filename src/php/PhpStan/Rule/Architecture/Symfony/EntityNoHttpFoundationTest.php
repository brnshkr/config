<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid Doctrine entities from depending on `Symfony\Component\HttpFoundation`.
 *
 * Entities are persistence-layer types; coupling them to HTTP request/response classes
 * smuggles transport concerns into the model and breaks framework upgrades.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EntityNoHttpFoundationTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class EntityNoHttpFoundationTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<non-empty-string> $roots root namespaces, one per module containing the `Entity` folder
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
                $root . '\Entity',
                'Symfony\Component\HttpFoundation',
                'Entities must not depend on Symfony\Component\HttpFoundation.',
            );
        }
    }
}
