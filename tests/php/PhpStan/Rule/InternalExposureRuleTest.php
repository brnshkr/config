<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InternalExposureRule;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InternalExposureRule>
 */
#[CoversNothing]
final class InternalExposureRuleTest extends AbstractRuleTestCase
{
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/InternalExposure/Symbols.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): string
    {
        return '{0} is `@api` but names `{1}`, which is `@internal`.';
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new InternalExposureRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
