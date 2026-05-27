<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony form type placement and base class.
 *
 * Classes named `*Type` must live under `<root>\Form`, and every class in that folder must
 * extend `Symfony\Component\Form\AbstractType`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(FormTypeTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class FormTypeTest
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
            [self::selectByClassnameSuffix('Type')],
            'Form',
            'Form types',
        );

        yield self::buildMustExtendRule(
            Selector::inNamespace($this->root . '\Form'),
            'Symfony\Component\Form\AbstractType',
            'Form types must extend Symfony\Component\Form\AbstractType.',
        );
    }
}
