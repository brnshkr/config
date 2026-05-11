<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use InvalidArgumentException;
use Override;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

use function sprintf;

/**
 * @internal
 *
 * @extends RuleTestCase<InternalUsageRule>
 */
#[CoversNothing]
final class InternalUsageRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $internalNamespace = 'Brnshkr\Config\Tests\Fixtures\Rule\Internal';
        $callerNamespace   = 'External\Consumer';

        $this->analyse([
            __DIR__ . '/../../Fixtures/Rule/Internal/InternalClass.php',
            __DIR__ . '/../../Fixtures/Rule/Internal/ScopedInternalClass.php',
            __DIR__ . '/../../Fixtures/Rule/InternalUsageRuleFixture.php',
        ], [
            [sprintf('Class `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass', $callerNamespace), 12],
            [sprintf('Method `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::doSomething', $callerNamespace), 14],
            [sprintf('Property `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::$value', $callerNamespace), 16],
            [sprintf('Class `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass', $callerNamespace), 17],
            [sprintf('Class `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass', $callerNamespace), 17],
            [sprintf('Class `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass', $callerNamespace), 19],
            [sprintf('Class `%s` is internal to `%s` and must not be used from `%s`.', $internalNamespace . '\ScopedInternalClass', 'Brnshkr\Config', $callerNamespace), 24],
        ]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws MissingServiceException
     */
    #[Override]
    protected function getRule(): Rule
    {
        return new InternalUsageRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }
}
