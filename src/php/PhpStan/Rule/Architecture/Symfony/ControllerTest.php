<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
     * @param non-empty-string $root Root application namespace
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
        yield self::buildPlacementRule(
            $this->root,
            [self::selectByClassnameSuffix('Controller')],
            'Controller',
            'Controllers',
        );

        yield self::buildClassIsolationRule(
            $this->root . '\Controller',
            'Doctrine\ORM\EntityManagerInterface',
            'Controllers must not depend on Doctrine\ORM\EntityManagerInterface.',
        );
    }
}
