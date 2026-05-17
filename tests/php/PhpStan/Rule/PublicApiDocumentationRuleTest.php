<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\PublicApiDocumentationRule;
use Override;
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
        $this->analyse([__DIR__ . '/../../Fixtures/Rule/PublicApiDocumentationRuleFixture.php'], [
            [sprintf('%s `%s` is `@api` and must carry a description before the first doc-tag.', 'Class', 'MissingClassDescription'), 52],
            [sprintf('%s `%s` is `@api` and must carry a description before the first doc-tag.', 'Method', 'missingDescription'), 77],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'missingDescription'), 77],
            [sprintf('%s `%s` is `@api`; parameter `$%s` must have an `@param` tag with a description.', 'Method', 'missingParamProse', 'arg'), 89],
            [sprintf('%s `%s` is `@api` and returns a non-void type; an `@return` tag with a description is required.', 'Method', 'missingReturnProse'), 99],
            [sprintf('%s `%s` is `@api` and accepts parameters; an `@example` tag is required.', 'Method', 'missingExample'), 109],
            [sprintf('%s `%s` is `@api` and must carry a description before the first doc-tag.', 'Function', 'publicApiDocsMissingDescriptionFunction'), 171],
        ]);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new PublicApiDocumentationRule();
    }
}
