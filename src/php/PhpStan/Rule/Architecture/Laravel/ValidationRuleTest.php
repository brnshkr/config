<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel validation rule placement and contract.
 *
 * Classes named `*Rule` or implementing `Illuminate\Contracts\Validation\ValidationRule`
 * must live under `<root>\Rules`, and every `*Rule` in that folder must implement the contract.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ValidationRuleTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class ValidationRuleTest
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
                [self::selectByClassnameSuffix('Rule'), Selector::implements('Illuminate\Contracts\Validation\ValidationRule')],
                'Rules',
                'Validation rules',
            );

            yield self::buildMustImplementRule(
                Selector::AllOf(
                    Selector::inNamespace($root . '\Rules'),
                    self::selectByClassnameSuffix('Rule'),
                ),
                'Illuminate\Contracts\Validation\ValidationRule',
                'Validation rules must implement Illuminate\Contracts\Validation\ValidationRule.',
            );
        }
    }
}
