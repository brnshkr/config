<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony Security voter placement and base class.
 *
 * Classes named `*Voter` must live under `<root>\Security\Voter`, and every class in that
 * folder must extend `Symfony\Component\Security\Core\Authorization\Voter\Voter`.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(VoterTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class VoterTest
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
                [self::selectByClassnameSuffix('Voter')],
                'Security\Voter',
                'Voters',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Security\Voter'),
                'Symfony\Component\Security\Core\Authorization\Voter\Voter',
                'Voters must extend Symfony\Component\Security\Core\Authorization\Voter\Voter.',
            );
        }
    }
}
