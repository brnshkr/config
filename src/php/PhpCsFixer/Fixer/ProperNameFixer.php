<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpCsFixer\Fixer;

use Brnshkr\Config\PhpCsFixer\Fixer\Trait\FixerTrait;
use Brnshkr\Config\ProperNames;
use Brnshkr\Config\Tests\PhpCsFixer\Fixer\ProperNameFixerTest;
use Override;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

use const T_COMMENT;
use const T_DOC_COMMENT;

/**
 * Spells names in comments the way their own projects spell them.
 *
 * This fixer corrects each name from the list the Markdown and script checks use, e.g. `PHPStan` and `GitHub`.
 * A name in all-lowercase stays as package names, paths and commands write it,
 * and so do code spans, code blocks, URLs, inline tags and the type part of a docblock tag.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/php-cs-fixer/rules/ProperNameFixer.md
 * @see ProperNameFixerTest
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpCsFixer;
 * use Brnshkr\Config\PhpCsFixer\Fixer\ProperNameFixer;
 *
 * PhpCsFixer::getBuilder()
 *     ->removeRules([ProperNameFixer::name()])
 *     ->build()
 * ;
 * ```
 */
final class ProperNameFixer implements FixerInterface
{
    use FixerTrait;

    #[Override]
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Names in comments are spelled the way their own projects spell them.',
            [new CodeSample("<?php\n\n// Runs Phpstan on Github.\n")],
        );
    }

    /**
     * @param Tokens<Token> $tokens
     */
    #[Override]
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    #[Override]
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @param Tokens<Token> $tokens
     */
    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens->findGivenKind([T_COMMENT, T_DOC_COMMENT]) as $kind => $commentTokens) {
            foreach ($commentTokens as $index => $commentToken) {
                $correctedComment = ProperNames::correct($commentToken->getContent());

                if ($correctedComment !== $commentToken->getContent()) {
                    $tokens[$index] = new Token([$kind, $correctedComment]);
                }
            }
        }
    }

    #[Override]
    public function getPriority(): int
    {
        return 0;
    }

    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }
}
