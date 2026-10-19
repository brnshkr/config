<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Invariant;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function sprintf;

/**
 * Require every enum, interface and trait to sit in a namespace named for its kind.
 *
 * Any depth is accepted, so a package may keep one such namespace per area
 * rather than a single one at its root. PHPat never visits a trait declaration,
 * so a misplaced trait is reported on each production class using it.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpStan;
 *
 * PhpStan::configurePhpAtTest(KindPlacementTest::class, [
 *     'root'     => 'Acme',
 *     'segments' => ['Interface' => 'Contract', 'Trait' => null],
 * ]);
 * ```
 */
final readonly class KindPlacementTest
{
    use ArchitectureRuleTrait;
    use RuleTrait;

    public const array DEFAULT_SEGMENTS = [
        self::KIND_ENUM      => self::KIND_ENUM,
        self::KIND_INTERFACE => self::KIND_INTERFACE,
        self::KIND_TRAIT     => self::KIND_TRAIT,
    ];

    /**
     * @var array<key-of<self::DEFAULT_SEGMENTS>, ?non-empty-string>
     */
    private array $segments;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root - production namespace
     * @param array<key-of<self::DEFAULT_SEGMENTS>, ?non-empty-string> $segments - namespace segment per kind, merged over the defaults; null leaves the kind unchecked
     * @param list<non-empty-string> $excludedNamespaces - namespaces autoloaded for development only, which the rule does not cover
     */
    public function __construct(
        private string $root,
        array $segments = [],
        private array $excludedNamespaces = [],
    ) {
        $this->segments = [...self::DEFAULT_SEGMENTS, ...$segments];
    }

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        $selectorsByKind = [
            self::KIND_ENUM      => Selector::isEnum(),
            self::KIND_INTERFACE => Selector::isInterface(),
        ];

        foreach ($this->segments as $kind => $segment) {
            if ($segment === null) {
                continue;
            }

            yield $kind === self::KIND_TRAIT
                ? $this->buildTraitPlacementRule($segment)
                : self::buildNestedPlacementRule(
                    $this->root,
                    [self::selectProductionClassesIn($this->root, $this->excludedNamespaces), $selectorsByKind[$kind]],
                    $segment,
                    sprintf('%ss', $kind),
                );
        }
    }

    /**
     * @param non-empty-string $segment
     */
    private function buildTraitPlacementRule(string $segment): BuildStep
    {
        return PHPat::rule()
            ->classes(self::selectProductionClassesIn($this->root, $this->excludedNamespaces))
            ->shouldNot()
            ->include()
            ->classes(Selector::AllOf(
                Selector::inNamespace($this->root),
                Selector::isTrait(),
                Selector::Not(Selector::classname(self::buildNestedSegmentPattern($this->root, $segment), regex: true)),
            ))
            ->because(sprintf('Traits must reside in a %s namespace.', $segment))
        ;
    }
}
