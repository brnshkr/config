<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use InvalidArgumentException;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_any;
use function array_find;
use function array_is_list;
use function is_string;
use function sprintf;

/**
 * Reports calls into another package's `@internal` symbols.
 *
 * By default an `@internal` symbol may only be used from inside its own declaring namespace or
 * any sub-namespace of it; callers outside that subtree are flagged, the global namespace
 * included. The tag also accepts an optional FQCN or namespace argument (`@internal Acme\Foo`)
 * that replaces the declaring namespace as the reachable subtree, useful when an internal symbol
 * should be reachable from one specific namespace but no other — naming the bare vendor namespace
 * (`@internal Acme`) opens the symbol to every sibling package of the same organization.
 *
 * Four allow-lists widen what counts as a legitimate caller — `allowedCallingNamespaces` exempts
 * callers (useful for test suites), `allowedDeclaringNamespaces` exempts whole declaring packages,
 * `allowedInternalTargets` exempts groups of symbols that share the same target, and
 * `allowedSymbols` exempts individual symbols by their fully-qualified name. Each entry is either
 * a plain prefix (matches the exact value or anything below it) or a delimited regex pattern,
 * recognized by an opening delimiter the entry also closes with.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/InternalUsageRule.md
 *
 * @example
 * ```php
 * PhpStan::getConfig(null, true)
 *     ->removeRules([InternalUsageRule::class])
 *     ->setRules([
 *         PhpStan::configureRule(InternalUsageRule::class, [
 *             'allowedCallingNamespaces'   => ['Acme\Tests'],
 *             'allowedDeclaringNamespaces' => ['/^Acme\\\Shared/'],
 *             'allowedInternalTargets'     => ['/^Acme\\\User$/'],
 *             'allowedSymbols'             => ['Acme\User\Internal\PasswordHasher::hash'],
 *         ]),
 *     ])
 * ;
 * ```
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<Stmt>
 */
final class InternalUsageRule implements Rule
{
    use RuleTrait;

    private const string AT_INTERNAL = '@internal';

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider (auto-wired)
     * @param ?list<non-empty-string> $allowedInternalTargets plain namespace prefixes or delimited regex patterns matched against `@internal <target>` values to whitelist
     * @param ?list<non-empty-string> $allowedDeclaringNamespaces plain namespace prefixes or delimited regex patterns matched against the declaring namespace to whitelist
     * @param ?list<non-empty-string> $allowedCallingNamespaces plain namespace prefixes or delimited regex patterns matched against the caller's namespace to whitelist
     * @param ?list<non-empty-string> $allowedSymbols plain prefixes or delimited regex patterns matched against the fully-qualified symbol name to whitelist
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private ?array $allowedInternalTargets = null {
            /**
             * @throws InvalidArgumentException
             */
            set(?array $allowedInternalTargets) {
                $this->allowedInternalTargets = self::getValidatedStringList('allowedInternalTargets', $allowedInternalTargets);
            }
        },
        private ?array $allowedDeclaringNamespaces = null {
            /**
             * @throws InvalidArgumentException
             */
            set(?array $allowedDeclaringNamespaces) {
                $this->allowedDeclaringNamespaces = self::getValidatedStringList('allowedDeclaringNamespaces', $allowedDeclaringNamespaces);
            }
        },
        private ?array $allowedCallingNamespaces = null {
            /**
             * @throws InvalidArgumentException
             */
            set(?array $allowedCallingNamespaces) {
                $this->allowedCallingNamespaces = self::getValidatedStringList('allowedCallingNamespaces', $allowedCallingNamespaces);
            }
        },
        private ?array $allowedSymbols = null {
            /**
             * @throws InvalidArgumentException
             */
            set(?array $allowedSymbols) {
                $this->allowedSymbols = self::getValidatedStringList('allowedSymbols', $allowedSymbols);
            }
        },
    ) {}

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Use_ || $node instanceof GroupUse || $node instanceof TraitUse) {
            return [];
        }

        $callerNamespace = $scope->getNamespace() ?? '';

        foreach (self::collectNodesWithinStatement($node) as $subNode) {
            $error = $this->processSubNode($subNode, $scope, $callerNamespace);

            if ($error instanceof IdentifierRuleError) {
                return [$error];
            }
        }

        return [];
    }

    /**
     * @return list<Node>
     */
    private static function collectNodesWithinStatement(Stmt $stmt): array
    {
        $visitor = new class extends NodeVisitorAbstract {
            /**
             * @var list<Node>
             */
            public array $collectedNodes = [];

            private bool $isRoot = true;

            #[Override]
            public function enterNode(Node $node): ?int
            {
                if ($this->isRoot) {
                    $this->isRoot = false;
                } elseif ($node instanceof Stmt) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                $this->collectedNodes[] = $node;

                return null;
            }
        };

        new NodeTraverser($visitor)->traverse([$stmt]);

        return $visitor->collectedNodes;
    }

    /**
     * @throws RuntimeException
     */
    private function processSubNode(Node $node, Scope $scope, string $callerNamespace): ?IdentifierRuleError
    {
        $line = $node->getStartLine();

        return match (true) {
            $node instanceof New_            => $this->processNameLikeNode($node->class, $scope, $callerNamespace, $line),
            $node instanceof Name            => $this->processNameLikeNode($node, $scope, $callerNamespace, $line),
            $node instanceof StaticCall      => $this->processStaticCall($node, $scope, $callerNamespace, $line),
            $node instanceof ClassConstFetch => $this->processClassConstFetch($node, $scope, $callerNamespace, $line),
            $node instanceof MethodCall      => $this->processMethodCall($node, $scope, $callerNamespace, $line),
            $node instanceof PropertyFetch   => $this->processPropertyFetch($node, $scope, $callerNamespace, $line),
            $node instanceof FuncCall        => $this->processFunctionCall($node, $scope, $callerNamespace, $line),
            $node instanceof ConstFetch      => $this->processConstantFetch($node, $scope, $callerNamespace, $line),
            default                          => null,
        };
    }

    /**
     * @throws RuntimeException
     */
    private function processNameLikeNode(Node|Name $name, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$name instanceof Name) {
            return null;
        }

        $resolvedName = $scope->resolveName($name);

        if (!$this->reflectionProvider->hasClass($resolvedName)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($resolvedName);
        $classNamespace  = $classReflection->getNativeReflection()->getNamespaceName();
        $internalTarget  = self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment() ?: null);

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::getKindForClassReflection($classReflection),
            $resolvedName,
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processFunctionCall(FuncCall $funcCall, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$funcCall->name instanceof Name || !$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
            return null;
        }

        $functionReflection = $this->reflectionProvider->getFunction($funcCall->name, $scope);
        $internalTarget     = self::resolveInternalTarget($functionReflection->getDocComment());
        $declaringNamespace = Str::match($functionReflection->getName(), '/^(.+)\\\[^\\\]+$/')[1] ?? '';

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $declaringNamespace,
            $callerNamespace,
            self::KIND_FUNCTION,
            $functionReflection->getName(),
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processConstantFetch(ConstFetch $constFetch, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$this->reflectionProvider->hasConstant($constFetch->name, $scope)) {
            return null;
        }

        $constantReflection = $this->reflectionProvider->getConstant($constFetch->name, $scope);

        if (!$constantReflection instanceof ClassMemberReflection) {
            return null;
        }

        $internalTarget = self::resolveInternalTarget($constantReflection->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $constantReflection->getDeclaringClass()->getNativeReflection()->getNamespaceName(),
            $callerNamespace,
            self::KIND_CONSTANT,
            $constFetch->name->toString(),
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processStaticCall(StaticCall $staticCall, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$staticCall->class instanceof Name || !$staticCall->name instanceof Identifier) {
            return null;
        }

        $resolvedName = $scope->resolveName($staticCall->class);

        if (!$this->reflectionProvider->hasClass($resolvedName)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($resolvedName);

        if (!$classReflection->hasMethod($staticCall->name->toString())) {
            return null;
        }

        $methodName               = $staticCall->name->toString();
        $extendedMethodReflection = $classReflection->getMethod($methodName, $scope);

        $internalTarget = self::resolveInternalTarget($extendedMethodReflection->getDocComment())
            ?? self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classReflection->getNativeReflection()->getNamespaceName(),
            $callerNamespace,
            self::KIND_METHOD,
            $resolvedName . '::' . $methodName,
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processMethodCall(MethodCall $methodCall, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$methodCall->name instanceof Identifier) {
            return null;
        }

        $method = $scope->getMethodReflection($scope->getType($methodCall->var), $methodCall->name->toString());

        if (!$method instanceof ExtendedMethodReflection) {
            return null;
        }

        $classReflection = $method->getDeclaringClass();
        $classNamespace  = $classReflection->getNativeReflection()->getNamespaceName();

        $internalTarget = self::resolveInternalTarget($method->getDocComment())
            ?? self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::KIND_METHOD,
            $classReflection->getName() . '::' . $method->getName(),
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processPropertyFetch(PropertyFetch $propertyFetch, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$propertyFetch->name instanceof Identifier) {
            return null;
        }

        $property = $scope->getInstancePropertyReflection($scope->getType($propertyFetch->var), $propertyFetch->name->toString());

        if (!$property instanceof ExtendedPropertyReflection) {
            return null;
        }

        $classReflection = $property->getDeclaringClass();
        $classNamespace  = $classReflection->getNativeReflection()->getNamespaceName();

        $internalTarget = self::resolveInternalTarget($property->getDocComment())
            ?? self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::KIND_PROPERTY,
            $classReflection->getName() . '::$' . $propertyFetch->name->toString(),
            $line,
        );
    }

    /**
     * @throws RuntimeException
     */
    private function processClassConstFetch(ClassConstFetch $classConstFetch, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$classConstFetch->name instanceof Identifier) {
            return null;
        }

        $constName = $classConstFetch->name->toString();

        if ($classConstFetch->class instanceof Name) {
            return $this->processClassAndConst($scope->resolveName($classConstFetch->class), $constName, $callerNamespace, $line);
        }

        foreach ($scope->getType($classConstFetch->class)->getObjectClassNames() as $className) {
            $error = $this->processClassAndConst($className, $constName, $callerNamespace, $line);

            if ($error instanceof IdentifierRuleError) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @throws RuntimeException
     */
    private function processClassAndConst(string $className, string $constName, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasConstant($constName)) {
            return null;
        }

        $internalTarget = self::resolveInternalTarget($classReflection->getConstant($constName)->getDocComment())
            ?? self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classReflection->getNativeReflection()->getNamespaceName(),
            $callerNamespace,
            self::KIND_CONSTANT,
            $className . '::' . $constName,
            $line,
        );
    }

    private static function resolveInternalTarget(string|false|null $docComment): ?string
    {
        $matches = Str::match($docComment ?: '', '/\*\s+@internal\s*([\w\\\]*)\s*\n/');

        return isset($matches[1])
            ? ($matches[1] ?: self::AT_INTERNAL)
            : null;
    }

    private function isAllowedInCaller(string $internalTarget, string $declaringNamespace, string $callerNamespace, string $symbol): bool
    {
        $patternsPerValue = [
            [$internalTarget, $this->allowedInternalTargets],
            [$declaringNamespace, $this->allowedDeclaringNamespaces],
            [$callerNamespace, $this->allowedCallingNamespaces],
            [$symbol, $this->allowedSymbols],
        ];

        foreach ($patternsPerValue as [$value, $patterns]) {
            if (self::isAllowedByAny($value, $patterns)) {
                return true;
            }
        }

        return self::isInSubtree(
            $callerNamespace,
            $internalTarget === self::AT_INTERNAL ? $declaringNamespace : $internalTarget,
        );
    }

    /**
     * @param ?list<non-empty-string> $patterns
     */
    private static function isAllowedByAny(string $value, ?array $patterns): bool
    {
        return array_any(
            $patterns ?? [],
            static fn (string $pattern): bool => self::isAllowed($value, $pattern),
        );
    }

    private static function isAllowed(string $value, string $pattern): bool
    {
        return self::isRegexPattern($pattern)
            ? Str::match($value, $pattern) !== []
            : self::isInSubtree($value, $pattern);
    }

    private static function isRegexPattern(string $pattern): bool
    {
        return Str::match($pattern, '/^([^\w\\\]).*\1[A-Za-z]*$/s') !== [];
    }

    private static function isInSubtree(string $value, string $prefix): bool
    {
        return $value === $prefix || Str::startsWithAny($value, [$prefix . '\\', $prefix . '::']);
    }

    /**
     * @throws RuntimeException
     */
    private function buildViolationIfDisallowed(
        ?string $internalTarget,
        string $declaringNamespace,
        string $callerNamespace,
        string $kind,
        string $symbol,
        int $line,
    ): ?IdentifierRuleError {
        return $internalTarget !== null && !$this->isAllowedInCaller($internalTarget, $declaringNamespace, $callerNamespace, $symbol)
            ? self::buildError($kind, $symbol, $internalTarget ?: self::AT_INTERNAL, $callerNamespace, $line)
            : null;
    }

    /**
     * @throws RuntimeException
     */
    private static function buildError(string $kind, string $symbol, string $internalTarget, string $callerNamespace, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` is internal%sand must not be used from %s.',
            $kind,
            $symbol,
            $internalTarget === self::AT_INTERNAL ? ' ' : sprintf(' to `%s` ', $internalTarget),
            Str::isEmpty($callerNamespace) ? 'the global namespace' : sprintf('`%s`', $callerNamespace),
        ), $line);
    }

    /**
     * @param non-empty-string $optionName
     * @param ?array<array-key, mixed> $input
     *
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     */
    private static function getValidatedStringList(string $optionName, ?array $input): array
    {
        if ($input === null) {
            return [];
        }

        if (!array_is_list($input) || array_any($input, static fn (mixed $item): bool => !is_string($item) || Str::isEmpty($item))) {
            throw new InvalidArgumentException(sprintf(
                'Value for option "%s" must be a list of non-empty strings.',
                $optionName,
            ));
        }

        /**
         * @var list<non-empty-string> $inputCasted
         */
        $inputCasted = $input;

        $malformedPattern = array_find(
            $inputCasted,
            static fn (string $pattern): bool => Str::match($pattern, '/^[\w\\\]/') === [] && !self::isRegexPattern($pattern),
        );

        if ($malformedPattern !== null) {
            throw new InvalidArgumentException(sprintf(
                'Entry "%s" for option "%s" is neither a namespace prefix nor a delimited regex pattern.',
                $malformedPattern,
                $optionName,
            ));
        }

        return $inputCasted;
    }
}
