<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ValidationRuleTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ValidationRuleTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root Root application namespace
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
            [self::selectByClassnameSuffix('Rule'), Selector::implements('Illuminate\Contracts\Validation\ValidationRule')],
            'Rules',
            'Validation rules',
        );

        yield self::buildMustImplementRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Rules'),
                self::selectByClassnameSuffix('Rule'),
            ),
            'Illuminate\Contracts\Validation\ValidationRule',
            'Validation rules must implement Illuminate\Contracts\Validation\ValidationRule.',
        );
    }
}
