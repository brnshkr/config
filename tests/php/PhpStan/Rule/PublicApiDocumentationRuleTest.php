<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\PublicApiDocumentationRule;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

use function sprintf;

/**
 * @internal
 *
 * @extends RuleTestCase<PublicApiDocumentationRule>
 */
#[CoversNothing]
final class PublicApiDocumentationRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/GoodClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/MissingClassDescription.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/MethodDocblockProblems.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/AbstractClass.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/Interface.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/Functions.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/FileLevel.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/BareReturn.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/BareReturnUndocumented.php',
            __DIR__ . '/../../Fixtures/PhpStan/Rule/PublicApiDocumentation/InheritedDocumentation.php',
        ], [
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Class', 'MissingClassDescription'), 10],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Method', 'missingDescription'), 17],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'missingDescription'), 17],
            [sprintf('%s `%s` is `@api`; parameter `$%s` must have an `@param` tag with a description.', 'Method', 'missingParamProse', 'arg'), 29],
            [sprintf('%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.', 'Method', 'missingReturnProse'), 39],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'missingExample'), 49],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Function', 'publicApiDocsMissingDescriptionFunction'), 24],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Class', 'FileLevelApiMissingDescription'), 14],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Method', 'method'), 16],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Class', 'UndocumentedReturnTarget'), 14],
            ['Top-level `return` in an `@api` file must carry a docblock with a description (either on the `return` statement or on its returned source).', 16],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Method', 'own'), 60],
            [sprintf('%s `%s` is `@api`; parameter `$%s` must have an `@param` tag with a description.', 'Method', 'own', 'argument'), 60],
            [sprintf('%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.', 'Method', 'own'), 60],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'own'), 60],
            [sprintf('%s `%s` is `@api` and must carry a description before the first PHPDoc tag.', 'Method', 'render'), 83],
            [sprintf('%s `%s` is `@api`; parameter `$%s` must have an `@param` tag with a description.', 'Method', 'render', 'markdown'), 83],
            [sprintf('%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.', 'Method', 'render'), 83],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'render'), 83],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new PublicApiDocumentationRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }
}
