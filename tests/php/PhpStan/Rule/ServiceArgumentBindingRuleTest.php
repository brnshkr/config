<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\ServiceArgumentBindingRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<ServiceArgumentBindingRule>
 */
#[CoversClass(ServiceArgumentBindingRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class ServiceArgumentBindingRuleTest extends AbstractRuleTestCase
{
    public function testRule(): void
    {
        $this->assertIssuesReported(
            __DIR__ . '/../../Fixtures/PhpStan/Rule/ServiceArgumentBinding/Services.php',
        );
    }

    #[Override]
    protected function getErrorFormatter(): string
    {
        return 'Service argument `${0}` is bound by name, but `{1}` declares no such parameter.';
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ServiceArgumentBindingRule(self::getContainer()->getByType(ReflectionProvider::class));
    }
}
