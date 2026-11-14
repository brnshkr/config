<?php

declare(strict_types=1);

namespace Brnshkr\Config\TwigCsFixer\Rule;

use Brnshkr\Config\ProperNames;
use Brnshkr\Config\Tests\TwigCsFixer\Rule\ProperNameRuleTest;
use Override;
use TwigCsFixer\Rules\AbstractFixableRule;
use TwigCsFixer\Token\Token;
use TwigCsFixer\Token\Tokens;

/**
 * Spells names in Twig comments the way their own projects spell them.
 *
 * This rule corrects each name with the list and the exceptions `ProperNameFixer` uses, e.g. a name in all-lowercase.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/TwigCsFixer.md
 * @see ProperNameRuleTest
 *
 * @example
 * ```php
 * use Brnshkr\Config\TwigCsFixer;
 * use Brnshkr\Config\TwigCsFixer\Rule\ProperNameRule;
 *
 * TwigCsFixer::getBuilder()
 *     ->removeRules([ProperNameRule::class])
 *     ->build()
 * ;
 * ```
 */
final class ProperNameRule extends AbstractFixableRule
{
    /**
     * @internal invoked by twig-cs-fixer
     */
    #[Override]
    protected function process(int $tokenIndex, Tokens $tokens): void
    {
        $token = $tokens->get($tokenIndex);

        if (!$token->isMatching([Token::COMMENT_TEXT_TYPE, Token::INLINE_COMMENT_TEXT_TYPE])) {
            return;
        }

        $correctedText = ProperNames::correct($token->getValue());

        if ($correctedText === $token->getValue()) {
            return;
        }

        $this->addFixableError('Spell a name the way its own project spells it.', $token)
            ?->replaceToken($tokenIndex, $correctedText)
        ;
    }
}
