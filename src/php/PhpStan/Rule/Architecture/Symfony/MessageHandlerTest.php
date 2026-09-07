<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony Messenger handler placement and HTTP isolation.
 *
 * Classes named `*Handler` or annotated with `#[AsMessageHandler]` must live under
 * `<root>\MessageHandler`, and handlers may not depend on `Symfony\Component\HttpFoundation` —
 * handlers run outside the HTTP lifecycle (workers, schedulers, etc.).
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(MessageHandlerTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class MessageHandlerTest
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
                [
                    self::selectByClassnameSuffix('Handler'),
                    Selector::appliesAttribute('Symfony\Component\Messenger\Attribute\AsMessageHandler'),
                ],
                'MessageHandler',
                'Messenger handlers',
            );

            yield self::buildNamespaceIsolationRule(
                $root . '\MessageHandler',
                'Symfony\Component\HttpFoundation',
                'Messenger handlers must not depend on Symfony\Component\HttpFoundation.',
            );
        }
    }
}
