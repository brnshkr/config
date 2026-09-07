<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<NamedArgumentsUsageRule>
 */
#[CoversNothing]
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
