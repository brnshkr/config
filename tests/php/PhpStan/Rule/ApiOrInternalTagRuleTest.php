<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\ApiOrInternalTagRule;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

use function sprintf;

/**
 * @internal
 *
 * @extends RuleTestCase<ApiOrInternalTagRule>
 */
#[CoversNothing]
final class ApiOrInternalTagRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Classes.php',
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Functions.php',
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Constants.php',
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Interfaces.php',
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Enums.php',
            __DIR__ . '/../../Fixtures/Rule/ApiOrInternalTag/Traits.php',
        ], [
            [sprintf('Class `%s` must be annotated with either @internal or @api.', 'ClassWithoutTag'), 17],
            [sprintf('Function `%s` must be annotated with either @internal or @api.', 'functionWithoutTag'), 17],
            [sprintf('Constant `%s` must be annotated with either @internal or @api.', 'CONSTANT_WITHOUT_TAG_A'), 17],
            [sprintf('Constant `%s` must be annotated with either @internal or @api.', 'CONSTANT_WITHOUT_TAG_B'), 18],
            [sprintf('Interface `%s` must be annotated with either @internal or @api.', 'InterfaceWithoutTag'), 17],
            [sprintf('Enum `%s` must be annotated with either @internal or @api.', 'EnumWithoutTag'), 17],
            [sprintf('Trait `%s` must be annotated with either @internal or @api.', 'TraitWithoutTag'), 17],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ApiOrInternalTagRule();
    }
}
