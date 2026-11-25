<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\DevFunctionUsageRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use function realpath;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<DevFunctionUsageRule>
 */
#[CoversClass(DevFunctionUsageRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class DevFunctionUsageRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule/DevFunctionUsage';

    public function testRule(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/packages/acme/dumper/functions.php',
            self::FIXTURE_DIRECTORY . '/src/ConsumeDevFunction.php',
            self::FIXTURE_DIRECTORY . '/tests/ConsumeDevFunctionFromTests.php',
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new DevFunctionUsageRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            ['acme/dumper' => (string) realpath(self::FIXTURE_DIRECTORY . '/packages/acme/dumper')],
            [(string) realpath(self::FIXTURE_DIRECTORY . '/tests')],
        );
    }
}
