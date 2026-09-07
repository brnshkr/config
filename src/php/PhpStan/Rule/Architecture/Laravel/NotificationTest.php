<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel notification placement and base class.
 *
 * Classes named `*Notification` or extending `Illuminate\Notifications\Notification` must
 * live under `<root>\Notifications`, and every `*Notification` in that folder must extend
 * the base notification.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(NotificationTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class NotificationTest
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
                [self::selectByClassnameSuffix('Notification'), Selector::extends('Illuminate\Notifications\Notification')],
                'Notifications',
                'Notifications',
            );

            yield self::buildMustExtendRule(
                Selector::AllOf(
                    Selector::inNamespace($root . '\Notifications'),
                    self::selectByClassnameSuffix('Notification'),
                ),
                'Illuminate\Notifications\Notification',
                'Notifications must extend Illuminate\Notifications\Notification.',
            );
        }
    }
}
