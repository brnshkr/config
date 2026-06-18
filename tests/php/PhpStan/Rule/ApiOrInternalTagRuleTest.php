<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\ApiOrInternalTagRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\ErrorMessageFormatter;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

use function count;
use function explode;
use function sprintf;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<ApiOrInternalTagRule>
 */
#[CoversNothing]
final class ApiOrInternalTagRuleTest extends AbstractRuleTestCase
{
    /**
     * @throws InvalidFixtureFile
     */
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Classes.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Functions.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Constants.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Interfaces.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Enums.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Traits.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/FileLevelApi.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/FileLevelInternal.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/BareReturn.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): ErrorMessageFormatter
    {
        return new class extends ErrorMessageFormatter {
            #[Override]
            public function getErrorMessage(string $errorContext): string
            {
                $parts = explode('|', $errorContext);

                return count($parts) === 2
                    ? sprintf('%s `%s` must be annotated with either @internal or @api.', $parts[0], $parts[1])
                    : 'Top-level `return` must be annotated with either @internal or @api (either on the `return` statement or on the file).';
            }
        };
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ApiOrInternalTagRule();
    }
}
