<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use Override;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InternalUsageRule>
 */
#[CoversNothing]
final class InternalUsageRuleTest extends AbstractRuleTestCase
{
    /**
     * @throws InvalidFixtureFile
     */
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/Internal/InternalClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/Internal/ScopedInternalClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InternalUsage/ConsumeInternalClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InternalUsage/ConsumeScopedInternalClass.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleReportsFromGlobalNamespace(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/Internal/InternalClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InternalUsage/ConsumeInternalClassFromGlobalNamespace.php',
        );
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
