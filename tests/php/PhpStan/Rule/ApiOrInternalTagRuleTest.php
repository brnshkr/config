<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\ApiOrInternalTagRule;
use Brnshkr\Config\PhpStan\Rule\FileLevelDocCache;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\ErrorMessageFormatter;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use function count;
use function explode;
use function sprintf;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<ApiOrInternalTagRule>
 */
#[CoversClass(ApiOrInternalTagRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FileLevelDocCache::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
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
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/Conflicts.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ApiOrInternalTag/FileLevelConflict.php',
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

                if ($parts[0] === 'conflict') {
                    return sprintf(
                        '%s must carry exactly one visibility tag, but has both `@api` and `@internal`.',
                        $parts[1] ?? '',
                    );
                }

                return count($parts) === 2
                    ? sprintf('%s `%s` must carry either `@api` or `@internal`.', $parts[0], $parts[1])
                    : 'Top-level `return` must carry either `@api` or `@internal` (either on the `return` statement or on the file).';
            }
        };
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ApiOrInternalTagRule();
    }
}
