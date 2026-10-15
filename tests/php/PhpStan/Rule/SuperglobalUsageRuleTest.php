<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\SuperglobalUsageRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<SuperglobalUsageRule>
 */
#[CoversClass(SuperglobalUsageRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class SuperglobalUsageRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule/SuperglobalUsage';

    public function testRule(): void
    {
        $this->assertIssuesReported(self::FIXTURE_DIRECTORY . '/ReadRequest.php');
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new SuperglobalUsageRule([
            '$GLOBALS'  => 'Pass the value explicitly instead.',
            '$_ENV'     => 'Read the environment in one place and inject the value instead.',
            '$_SERVER'  => 'Read the environment in one place and inject the value instead.',
            '$_SESSION' => 'Use the session instead.',
        ]);
    }
}
