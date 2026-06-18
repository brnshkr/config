<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\NoNamedArgumentsTagRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<NoNamedArgumentsTagRule>
 */
#[CoversNothing]
final class NoNamedArgumentsTagRuleTest extends AbstractRuleTestCase
{
    /**
     * @throws InvalidFixtureFile
     */
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/Classes.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/Functions.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/AnonymousClasses.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/Interfaces.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/Enums.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/NoNamedArgumentsTag/Traits.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): string
    {
        return '{0} `{1}` must be annotated with @no-named-arguments.';
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new NoNamedArgumentsTagRule();
    }
}
