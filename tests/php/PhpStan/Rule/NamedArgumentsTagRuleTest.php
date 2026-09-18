<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsTagRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<NamedArgumentsTagRule>
 */
#[CoversClass(NamedArgumentsTagRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Str::class)]
final class NamedArgumentsTagRuleTest extends AbstractRuleTestCase
{
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/Classes.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/Functions.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/AnonymousClasses.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/Interfaces.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/Enums.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/Traits.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsTag/NamedArguments.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): string
    {
        return '{0} `{1}` must carry either `@named-arguments` or `@no-named-arguments`.';
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NamedArgumentsTagRule();
    }
}
