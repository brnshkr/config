<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_any;
use function array_filter;
use function sprintf;

/**
 * Requires every public-facing declaration that exposes parameters to carry `@no-named-arguments`.
 *
 * PHP's named-argument syntax silently turns parameter names into part of the public contract —
 * once a caller writes `someFunction(name: 'foo')`, the parameter cannot be renamed without
 * breaking that caller. The tag keeps parameter names out of the contract, leaving them free to
 * be renamed without a backwards-compatibility break.
 *
 * Symbols tagged `@internal` are exempt; anonymous classes are skipped.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class NoNamedArgumentsTagRule implements Rule
{
    use RuleTrait;

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
     *
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return array_filter(
            match (true) {
                $node instanceof ClassLike => [self::processClassLike($node)],
                $node instanceof Function_ => [self::processFunction($node)],
                default                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        );
    }

    /**
     * @throws RuntimeException
     */
    private static function processClassLike(ClassLike $classLike): ?IdentifierRuleError
    {
        if (self::isAnonymousClass($classLike) || !self::hasMethodsWithParameters($classLike)) {
            return null;
        }

        $doc = $classLike->getDocComment();

        if (self::hasTag($doc, 'internal') || self::hasTag($doc, 'no-named-arguments')) {
            return null;
        }

        return self::buildError(
            self::getKindForClassLike($classLike),
            self::getClassLikeName($classLike),
            $classLike->getStartLine(),
        );
    }

    private static function hasMethodsWithParameters(ClassLike $classLike): bool
    {
        return array_any(
            $classLike->getMethods(),
            static fn (ClassMethod $classMethod): bool => !$classMethod->isPrivate() && $classMethod->getParams() !== [],
        );
    }

    /**
     * @throws RuntimeException
     */
    private static function processFunction(Function_ $function): ?IdentifierRuleError
    {
        if ($function->getParams() === []) {
            return null;
        }

        $doc = $function->getDocComment();

        if (self::hasTag($doc, 'internal') || self::hasTag($doc, 'no-named-arguments')) {
            return null;
        }

        return self::buildError(self::KIND_FUNCTION, $function->name->toString(), $function->getStartLine());
    }

    /**
     * @param self::KIND_* $kind
     *
     * @throws RuntimeException
     */
    private static function buildError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` must be annotated with @no-named-arguments.',
            $kind,
            $name,
        ), $line);
    }
}
