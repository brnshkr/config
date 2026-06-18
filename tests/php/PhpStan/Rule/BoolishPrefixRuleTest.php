<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\BoolishPrefixRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\ErrorMessageFormatter;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

use function explode;
use function implode;
use function sprintf;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<BoolishPrefixRule>
 */
#[CoversNothing]
final class BoolishPrefixRuleTest extends AbstractRuleTestCase
{
    /**
     * @throws InvalidFixtureFile
     */
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/MainClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/InterfaceImpl.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/OverrideClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/TraitUser.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/MagicMethods.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/ExternalImpl.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/Functions.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): ErrorMessageFormatter
    {
        return new class extends ErrorMessageFormatter {
            #[Override]
            public function getErrorMessage(string $errorContext): string
            {
                [, $kind, $name] = explode('|', $errorContext);

                return sprintf(
                    '%s name `%s` must have one of the following prefixes: %s',
                    $kind,
                    $name,
                    implode(', ', BoolishPrefixRule::BOOLISH_PREFIXES),
                );
            }
        };
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new BoolishPrefixRule();
    }
}
