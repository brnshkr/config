<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

/**
 * Enforce Symfony Messenger message placement and immutability.
 *
 * Classes named `*Message` must live under `<root>\Message`, and every class in that folder
 * must be `final` and `readonly` — messages are sealed, immutable data bags passed on the bus.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(MessageTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class MessageTest
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
            [self::selectByClassnameSuffix('Message')],
            'Message',
            'Messenger messages',
        );

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\Message'))
            ->should()
            ->beFinal()
            ->because('Messenger messages must be final; they are sealed value objects.')
        ;

        yield PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\Message'))
            ->should()
            ->beReadonly()
            ->because('Messenger messages must be readonly; they are immutable data bags passed on the bus.')
        ;
    }
}
