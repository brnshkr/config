<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Enforce Symfony event listener placement.
 *
 * Classes named `*Listener` or annotated with `#[AsEventListener]` must live under
 * `<root>\EventListener`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(EventListenerTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class EventListenerTest
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
                [self::selectByClassnameSuffix('Listener'), Selector::appliesAttribute(AsEventListener::class)],
                'EventListener',
                'Event listeners',
            );
        }
    }
}
