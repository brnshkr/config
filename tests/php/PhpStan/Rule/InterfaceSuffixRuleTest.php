<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InterfaceSuffixRule;
use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

use function sprintf;

/**
 * @internal
 *
 * @extends RuleTestCase<InterfaceSuffixRule>
 */
#[CoversNothing]
final class InterfaceSuffixRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../../Fixtures/Rule/InterfaceSuffixRuleFixture.php'], [
            [sprintf('Class `%s` implements `%s` and must end with suffix `%s`.', 'User', 'UserServiceInterface', 'UserService'), 61],
            [sprintf('Class `%s` implements `%s` and must end with suffix `%s`.', 'BadListener', 'EventSubscriberInterface', 'EventSubscriber'), 71],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new InterfaceSuffixRule();
    }
}
