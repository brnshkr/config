<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\InterfaceSuffixRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InterfaceSuffixRule>
 */
#[CoversClass(InterfaceSuffixRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class InterfaceSuffixRuleTest extends AbstractRuleTestCase
{
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InterfaceSuffix/PassingSuffix.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InterfaceSuffix/MismatchedSuffix.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InterfaceSuffix/NonSuffixInterface.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InterfaceSuffix/MultipleInterfaces.php',
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new InterfaceSuffixRule();
    }
}
