<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InterfaceSuffixRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InterfaceSuffixRule>
 */
#[CoversNothing]
final class InterfaceSuffixRuleTest extends AbstractRuleTestCase
{
    /**
     * @throws InvalidFixtureFile
     */
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
