<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use ReflectionException;

use function array_any;
use function array_values;
use function sprintf;

/**
 * Requires a call to a `@named-arguments` symbol to name every argument it passes.
 *
 * The payoff is that a parameter can be inserted _before_ an existing one without breaking a caller,
 * since no caller depends on position. That only holds while every argument is named, so a single
 * positional argument is reported. PHPStan already reports the opposite mistake
 * — naming an argument of a `@no-named-arguments` symbol.
 *
 * `@internal` does not excuse a call, and a method may override its class either way
 * — the nearer declaration wins.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/NamedArgumentsUsageRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final readonly class NamedArgumentsUsageRule implements Rule
{
    use RuleTrait;

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider (auto-wired)
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

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
     * @throws ReflectionException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof New_ && !$node instanceof StaticCall && !$node instanceof MethodCall) {
            return [];
        }

        $target = match (true) {
            $node instanceof New_       => $this->resolveNew($node, $scope),
            $node instanceof StaticCall => $this->resolveStaticCall($node, $scope),
            default                     => $this->resolveMethodCall($node, $scope),
        };

        if ($target === null || !self::hasPositionalArgument(array_values($node->getArgs()))) {
            return [];
        }

        return [self::buildRuleError(sprintf(
            'Call to `%s` must name its arguments, because it is tagged `@named-arguments`.',
            $target,
        ), $node->getStartLine())];
    }

    /**
     * @return ?non-empty-string
     *
     * @throws ReflectionException
     */
    private function resolveNew(New_ $new, Scope $scope): ?string
    {
        if (!$new->class instanceof Name) {
            return null;
        }

        $classReflection = $this->findClass($scope->resolveName($new->class));

        return $classReflection instanceof ClassReflection
            && self::requiresNamedArguments($classReflection, '__construct')
            && self::isCallerBound($classReflection, $scope, '__construct')
            ? sprintf('%s::__construct()', $classReflection->getName())
            : null;
    }

    /**
     * @return ?non-empty-string
     *
     * @throws ReflectionException
     */
    private function resolveStaticCall(StaticCall $staticCall, Scope $scope): ?string
    {
        if (!$staticCall->class instanceof Name || !$staticCall->name instanceof Node\Identifier) {
            return null;
        }

        $classReflection = $this->findClass($scope->resolveName($staticCall->class));

        return $classReflection instanceof ClassReflection
            && self::requiresNamedArguments($classReflection, $staticCall->name->toString())
            && self::isCallerBound($classReflection, $scope, $staticCall->name->toString())
            ? sprintf('%s::%s()', $classReflection->getName(), $staticCall->name->toString())
            : null;
    }

    /**
     * @return ?non-empty-string
     *
     * @throws ReflectionException
     */
    private function resolveMethodCall(MethodCall $methodCall, Scope $scope): ?string
    {
        if (!$methodCall->name instanceof Node\Identifier) {
            return null;
        }

        foreach ($scope->getType($methodCall->var)->getObjectClassNames() as $className) {
            $classReflection = $this->findClass($className);

            if ($classReflection instanceof ClassReflection
                && self::requiresNamedArguments($classReflection, $methodCall->name->toString())
                && self::isCallerBound($classReflection, $scope, $methodCall->name->toString())
            ) {
                return sprintf('%s::%s()', $classReflection->getName(), $methodCall->name->toString());
            }
        }

        return null;
    }

    /**
     * @throws ReflectionException
     */
    private static function requiresNamedArguments(ClassReflection $classReflection, string $methodName): bool
    {
        $methodDoc = $classReflection->getNativeReflection()->hasMethod($methodName)
            ? $classReflection->getNativeReflection()->getMethod($methodName)->getDocComment() ?: ''
            : '';

        if (self::hasTagInText($methodDoc, self::TAG_NAMED_ARGUMENTS)) {
            return true;
        }

        if (self::hasTagInText($methodDoc, self::TAG_NO_NAMED_ARGUMENTS)) {
            return false;
        }

        return self::hasClassTag($classReflection, self::TAG_NAMED_ARGUMENTS);
    }

    /**
     * @throws ReflectionException
     */
    private static function isCallerBound(ClassReflection $classReflection, Scope $scope, ?string $methodName): bool
    {
        if ($scope->getClassReflection()?->getName() === $classReflection->getName()) {
            return false;
        }

        if ($methodName === null) {
            return true;
        }

        $nativeReflection = $classReflection->getNativeReflection();

        if (!$nativeReflection->hasMethod($methodName)) {
            return true;
        }

        return $nativeReflection->getMethod($methodName)->isPublic();
    }

    private function findClass(string $className): ?ClassReflection
    {
        return $this->reflectionProvider->hasClass($className)
            ? $this->reflectionProvider->getClass($className)
            : null;
    }

    /**
     * @param list<Arg> $arguments
     */
    private static function hasPositionalArgument(array $arguments): bool
    {
        return array_any($arguments, static fn (Arg $arg): bool => !$arg->name instanceof Node\Identifier);
    }
}
