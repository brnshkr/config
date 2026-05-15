<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class ValidationRuleTest
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
