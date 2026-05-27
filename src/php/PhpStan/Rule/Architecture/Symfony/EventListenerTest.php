<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
     * @param non-empty-string $root root application namespace
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildPlacementRule(
            $this->root,
            [self::selectByClassnameSuffix('Listener'), Selector::appliesAttribute(AsEventListener::class)],
            'EventListener',
            'Event listeners',
        );
    }
}
