<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony form type placement and base class.
 *
 * Classes named `*Type` that extend `Symfony\Component\Form\AbstractType` must live under
 * `<root>\Form\Type`, and every class in that folder must extend AbstractType.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(FormTypeTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class FormTypeTest
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
                [self::selectByClassnameSuffix('Type'), Selector::extends('Symfony\Component\Form\AbstractType')],
                'Form\Type',
                'Form types',
            );

            yield self::buildMustExtendRule(
                Selector::inNamespace($root . '\Form\Type'),
                'Symfony\Component\Form\AbstractType',
                'Form types must extend Symfony\Component\Form\AbstractType.',
            );
        }
    }
}
