<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\PhpStan\Rule\BoolishPrefixRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\ErrorMessageFormatter;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use function explode;
use function implode;
use function in_array;
use function sprintf;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<BoolishPrefixRule>
 */
#[CoversClass(BoolishPrefixRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Str::class)]
final class BoolishPrefixRuleTest extends AbstractRuleTestCase
{
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
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/NonBoolean.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/BoolishPrefix/Types.php',
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

                $type   = $parts[0];
                $kind   = $parts[1] ?? '';
                $name   = $parts[2] ?? '';
                $prefix = $parts[3] ?? '';

                if ($type === 'reserved') {
                    return sprintf(
                        '%s name `%s` must not start with the boolish prefix `%s` because it is not boolean.',
                        $kind,
                        $name,
                        $prefix,
                    );
                }

                $prefixes = in_array($kind, ['Method', 'Function'], true)
                    ? BoolishPrefixRule::PREDICATE_PREFIXES
                    : BoolishPrefixRule::FLAG_PREFIXES;

                return sprintf(
                    '%s name `%s` must have one of the following prefixes: %s.',
                    $kind,
                    $name,
                    implode(', ', $prefixes),
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
