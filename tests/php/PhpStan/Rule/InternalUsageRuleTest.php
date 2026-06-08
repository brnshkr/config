<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
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
            __DIR__ . '/../../Fixtures/Rule/InternalUsage/ConsumeInternalClass.php',
            __DIR__ . '/../../Fixtures/Rule/InternalUsage/ConsumeScopedInternalClass.php',
        ], [
            [sprintf('Class `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass', $callerNamespace), 11],
            [sprintf('Method `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::doSomething', $callerNamespace), 13],
            [sprintf('Property `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::$value', $callerNamespace), 15],
            [sprintf('Constant `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::SOME_CONSTANT', $callerNamespace), 16],
            [sprintf('Method `%s` is internal and must not be used from `%s`.', $internalNamespace . '\InternalClass::staticMethod', $callerNamespace), 18],
            [sprintf('Class `%s` is internal to `%s` and must not be used from `%s`.', $internalNamespace . '\ScopedInternalClass', 'Brnshkr\Config', $callerNamespace), 11],
        ]);
    }

    public function testRuleReportsFromGlobalNamespace(): void
    {
        $internalNamespace = 'Brnshkr\Config\Tests\Fixtures\Rule\Internal';

        $this->analyse([
            __DIR__ . '/../../Fixtures/Rule/Internal/InternalClass.php',
            __DIR__ . '/../../Fixtures/Rule/InternalUsage/ConsumeInternalClassFromGlobalNamespace.php',
        ], [
            [sprintf('Class `%s` is internal and must not be used from the global namespace.', $internalNamespace . '\InternalClass'), 9],
            [sprintf('Method `%s` is internal and must not be used from the global namespace.', $internalNamespace . '\InternalClass::doSomething'), 10],
            [sprintf('Property `%s` is internal and must not be used from the global namespace.', $internalNamespace . '\InternalClass::$value'), 12],
            [sprintf('Constant `%s` is internal and must not be used from the global namespace.', $internalNamespace . '\InternalClass::SOME_CONSTANT'), 14],
            [sprintf('Method `%s` is internal and must not be used from the global namespace.', $internalNamespace . '\InternalClass::staticMethod'), 16],
        ]);
    }

    /**
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
