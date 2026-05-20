<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\NoNamedArgumentsTagRule;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

use function sprintf;

/**
 * @internal
 *
 * @extends RuleTestCase<NoNamedArgumentsTagRule>
 */
#[CoversNothing]
final class NoNamedArgumentsTagRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/Classes.php',
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/Functions.php',
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/AnonymousClasses.php',
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/Interfaces.php',
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/Enums.php',
            __DIR__ . '/../../Fixtures/Rule/NoNamedArgumentsTag/Traits.php',
        ], [
            [sprintf('Class `%s` must be annotated with @no-named-arguments.', 'ClassWithoutTag'), 23],
            [sprintf('Class `%s` must be annotated with @no-named-arguments.', 'ClassWithoutTagPrivateAndPublicParameters'), 48],
            [sprintf('Class `%s` must be annotated with @no-named-arguments.', 'ClassWithoutTagConstructorParameters'), 55],
            [sprintf('Function `%s` must be annotated with @no-named-arguments.', 'functionWithoutTag'), 17],
            [sprintf('Interface `%s` must be annotated with @no-named-arguments.', 'InterfaceWithoutTag'), 23],
            [sprintf('Enum `%s` must be annotated with @no-named-arguments.', 'EnumWithoutTag'), 27],
            [sprintf('Trait `%s` must be annotated with @no-named-arguments.', 'TraitWithoutTag'), 23],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NoNamedArgumentsTagRule();
    }
}
