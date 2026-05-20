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
        $this->analyse([
            __DIR__ . '/../../Fixtures/Rule/InterfaceSuffix/PassingSuffix.php',
            __DIR__ . '/../../Fixtures/Rule/InterfaceSuffix/MismatchedSuffix.php',
            __DIR__ . '/../../Fixtures/Rule/InterfaceSuffix/NonSuffixInterface.php',
            __DIR__ . '/../../Fixtures/Rule/InterfaceSuffix/MultipleInterfaces.php',
        ], [
            [sprintf('Class `%s` implements `%s` and must end with suffix `%s`.', 'User', 'UserServiceInterface', 'UserService'), 28],
            [sprintf('Class `%s` implements `%s` and must end with suffix `%s`.', 'BadListener', 'EventSubscriberInterface', 'EventSubscriber'), 38],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new InterfaceSuffixRule();
    }
}
