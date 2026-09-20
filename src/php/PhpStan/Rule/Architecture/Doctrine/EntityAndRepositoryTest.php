<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Doctrine entity and repository placement conventions.
 *
 * Two checks:
 *   - Classes carrying `#[Doctrine\ORM\Mapping\Entity]` must live under `<root>\Entity`.
 *   - Classes under `<root>\Repository` ending in `Repository` must extend `Doctrine\ORM\EntityRepository`.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EntityAndRepositoryTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class EntityAndRepositoryTest
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
                [Selector::appliesAttribute('Doctrine\ORM\Mapping\Entity')],
                'Entity',
                'Doctrine entities',
            );

            yield self::buildMustExtendRule(
                Selector::AllOf(
                    Selector::inNamespace($root . '\Repository'),
                    self::selectByClassnameSuffix('Repository'),
                ),
                'Doctrine\ORM\EntityRepository',
                'Repositories must extend Doctrine\ORM\EntityRepository.',
            );
        }
    }
}
