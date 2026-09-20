<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

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
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(SubscriberTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class SubscriberTest
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
                [self::selectByClassnameSuffix('Subscriber')],
                'EventSubscriber',
                'Event subscribers',
            );

            yield self::buildMustImplementRule(
                Selector::inNamespace($root . '\EventSubscriber'),
                EventSubscriberInterface::class,
                'Event subscribers must implement Symfony\Component\EventDispatcher\EventSubscriberInterface.',
            );
        }
    }
}
