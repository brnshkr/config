<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Enforce Symfony Serializer normalizer placement and contract.
 *
 * Classes named `*Normalizer` must live under `<root>\Serializer`, and every class in that
 * folder must implement `Symfony\Component\Serializer\Normalizer\NormalizerInterface`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(NormalizerTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class NormalizerTest
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
            [self::selectByClassnameSuffix('Normalizer')],
            'Serializer',
            'Normalizers',
        );

        yield self::buildMustImplementRule(
            Selector::inNamespace($this->root . '\Serializer'),
            NormalizerInterface::class,
            'Serializer classes must implement Symfony\Component\Serializer\Normalizer\NormalizerInterface.',
        );
    }
}
