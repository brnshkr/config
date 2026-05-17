<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Enforce Symfony event subscriber placement and contract.
 *
 * Classes named `*Subscriber` must live under `<root>\EventSubscriber`, and every class in
 * that folder must implement `Symfony\Component\EventDispatcher\EventSubscriberInterface`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(SubscriberTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class SubscriberTest
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
            [self::selectByClassnameSuffix('Subscriber')],
            'EventSubscriber',
            'Event subscribers',
        );

        yield self::buildMustImplementRule(
            Selector::inNamespace($this->root . '\EventSubscriber'),
            EventSubscriberInterface::class,
            'Event subscribers must implement Symfony\Component\EventDispatcher\EventSubscriberInterface.',
        );
    }
}
