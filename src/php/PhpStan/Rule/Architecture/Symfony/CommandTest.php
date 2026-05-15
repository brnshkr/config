<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Symfony\Component\Console\Command\Command;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class CommandTest
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
            [self::selectByClassnameSuffix('Command')],
            'Command',
            'Console commands',
        );

        yield self::buildMustExtendRule(
            Selector::inNamespace($this->root . '\Command'),
            Command::class,
            'Console commands must extend Symfony\Component\Console\Command\Command.',
        );

        yield self::buildNamespaceIsolationRule(
            $this->root . '\Command',
            'Symfony\Component\HttpFoundation',
            'Console commands must not depend on Symfony\Component\HttpFoundation.',
        );
    }
}
