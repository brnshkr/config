<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\TagDescriptionRuleTest;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

/**
 * Requires every tag description to take one shape, on `@api` and `@internal` symbols alike.
 *
 * `@param`, `@property` and `@template` put a dash between the declaration and the description,
 * `@return` and `@throws` take none, and a description is a lowercase fragment without a closing period.
 * A `@throws` description names the condition, opening with `when` or `unless`.
 *
 * A capital letter counts as a sentence start only when a lowercase letter or a space follows it,
 * so `URL` and `PHPStan` pass, and so does a backticked first word.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @phpstan-import-type DocTag from RuleTrait
 *
 * @implements Rule<NodeAbstract>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/TagDescriptionRule.md
 * @see TagDescriptionRuleTest
 */
final readonly class TagDescriptionRule implements Rule
{
    use RuleTrait;

    public const array DESCRIPTION_DASHES = [
        'param'    => true,
        'property' => true,
        'template' => true,
        'return'   => false,
        'throws'   => false,
    ];

    public const array THROWS_DESCRIPTION_WORDS = [
        'when',
        'unless',
    ];

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @return list<IdentifierRuleError>
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $isDocumentable = ($node instanceof Stmt || $node instanceof Param) && !$node instanceof VirtualNode;
        $doc            = $isDocumentable ? $node->getDocComment() : null;

        if (!$doc instanceof Doc) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn (array $tag): ?IdentifierRuleError => self::checkTag($tag, $doc->getStartLine()),
                self::getDocTags($doc->getText()),
            ),
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @param DocTag $tag
     */
    private static function checkTag(array $tag, int $docLine): ?IdentifierRuleError
    {
        if (Str::isEmpty($tag['description'])) {
            return null;
        }

        $isDashed = Str::startsWith($tag['description'], '-');
        $prose    = $isDashed ? Str::trim($tag['description'], '- ', 'start') : $tag['description'];

        $isWordMissing = $tag['kind'] === 'throws' && !Str::startsWithAny(
            $prose,
            array_map(static fn (string $word): string => $word . ' ', self::THROWS_DESCRIPTION_WORDS),
        );

        $isSentence = Str::match($prose, '/^\p{Lu}[\p{Ll}\s]/') !== [] || Str::endsWith($prose, '.');

        $problem = match (true) {
            $isDashed !== self::DESCRIPTION_DASHES[$tag['kind']] => $isDashed ? 'takes no leading dash' : 'needs a leading dash',
            Str::isEmpty($prose)                                 => null,
            $isWordMissing                                       => sprintf('starts with %s', Str::joinAsQuotedList(self::THROWS_DESCRIPTION_WORDS, 'disjunction', '`')),
            $isSentence                                          => 'starts lowercase and ends without a period',
            default                                              => null,
        };

        return $problem === null
            ? null
            : self::buildRuleError(sprintf('The `%s` description %s.', $tag['tag'], $problem), $docLine + $tag['line'] - 1);
    }
}
