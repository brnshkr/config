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
 * @api
 *
 * @no-named-arguments
 */
final readonly class NormalizerTest
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
