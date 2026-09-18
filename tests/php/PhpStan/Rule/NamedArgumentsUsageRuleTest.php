<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<NamedArgumentsUsageRule>
 */
#[CoversClass(NamedArgumentsUsageRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Str::class)]
final class NamedArgumentsUsageRuleTest extends AbstractRuleTestCase
{
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NamedArgumentsUsage/Calls.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): string
    {
        return 'Call to `{0}` must name its arguments, because it is tagged `@named-arguments`.';
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NamedArgumentsUsageRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
