<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid Doctrine entities from depending on `Symfony\Component\HttpFoundation`.
 *
 * Entities are persistence-layer types; coupling them to HTTP request/response classes
 * smuggles transport concerns into the model and breaks framework upgrades.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EntityNoHttpFoundationTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class EntityNoHttpFoundationTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root root application namespace containing the `Entity` folder
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespaceIsolationRule(
            $this->root . '\Entity',
            'Symfony\Component\HttpFoundation',
            'Entities must not depend on Symfony\Component\HttpFoundation.',
        );
    }
}
