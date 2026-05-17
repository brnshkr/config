<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(VoterTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class VoterTest
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
            [self::selectByClassnameSuffix('Voter')],
            'Security\Voter',
            'Voters',
        );

        yield self::buildMustExtendRule(
            Selector::inNamespace($this->root . '\Security\Voter'),
            'Symfony\Component\Security\Core\Authorization\Voter\Voter',
            'Voters must extend Symfony\Component\Security\Core\Authorization\Voter\Voter.',
        );
    }
}
