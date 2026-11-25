<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\ResolvableDocReferenceRule;
use Brnshkr\Config\Str;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<ResolvableDocReferenceRule>
 */
#[CoversClass(ResolvableDocReferenceRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class ResolvableDocReferenceRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule/ResolvableDocReference';

    public function testRule(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Target.php',
            self::FIXTURE_DIRECTORY . '/BaseReference.php',
            self::FIXTURE_DIRECTORY . '/lowercaseTarget.php',
            self::FIXTURE_DIRECTORY . '/Symbols.php',
            self::FIXTURE_DIRECTORY . '/Reference.php',
        );
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ResolvableDocReferenceRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            self::getContainer()->getByType(FileTypeMapper::class),
        );
    }
}
