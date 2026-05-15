<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class RepositoryTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $root
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
            [self::selectByClassnameSuffix('Repository')],
            'Repository',
            'Repositories',
        );

        yield self::buildMustExtendRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Repository'),
                self::selectByClassnameSuffix('Repository'),
            ),
            'Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository',
            'Repositories must extend Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository.',
        );
    }
}
