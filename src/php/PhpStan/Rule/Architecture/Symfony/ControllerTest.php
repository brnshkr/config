<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony controller placement and EntityManager isolation.
 *
 * Classes named `*Controller` must live under `<root>\Controller`, and controllers may not
 * depend on `Doctrine\ORM\EntityManagerInterface` — use repositories or services instead.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ControllerTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ControllerTest
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
            yield self::buildPlacementRule(
                $root,
                [self::selectByClassnameSuffix('Controller')],
                'Controller',
                'Controllers',
            );

            yield self::buildClassIsolationRule(
                $root . '\Controller',
                'Doctrine\ORM\EntityManagerInterface',
                'Controllers must not depend on Doctrine\ORM\EntityManagerInterface.',
            );
        }
    }
}
