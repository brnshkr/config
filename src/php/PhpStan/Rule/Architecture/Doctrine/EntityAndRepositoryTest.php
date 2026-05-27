<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EntityAndRepositoryTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class EntityAndRepositoryTest
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
        yield self::buildPlacementRule(
            $this->root,
            [Selector::appliesAttribute('Doctrine\ORM\Mapping\Entity')],
            'Entity',
            'Doctrine entities',
        );

        yield self::buildMustExtendRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Repository'),
                self::selectByClassnameSuffix('Repository'),
            ),
            'Doctrine\ORM\EntityRepository',
            'Repositories must extend Doctrine\ORM\EntityRepository.',
        );
    }
}
