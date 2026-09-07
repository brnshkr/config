<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Invariant;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Require every exception to sit in an `Exception` namespace.
 *
 * Any depth is accepted, so a package may keep one `Exception` namespace per area rather than a
 * single one at its root. Part of every preset.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ExceptionPlacementTest::class, [
 *     'root' => 'Acme',
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ExceptionPlacementTest
{
    use ArchitectureRuleTrait;

    private const string SEGMENT = 'Exception';

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root production namespace
     * @param list<non-empty-string> $excludedNamespaces namespaces autoloaded for development only, which the rule does not cover
     */
    public function __construct(
        private string $root,
        private array $excludedNamespaces = [],
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNestedPlacementRule(
            $this->root,
            [self::selectProductionClassesIn($this->root, $this->excludedNamespaces), Selector::isException()],
            self::SEGMENT,
            'Exceptions',
        );
    }
}
