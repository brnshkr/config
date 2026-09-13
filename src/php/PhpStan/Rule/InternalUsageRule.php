<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\InternalUsageRuleTest;
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

use function array_any;
use function array_is_list;
use function array_map;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Reports calls into another package's `@internal` symbols.
 *
 * An `@internal` symbol may only be used from its own declaring namespace or below, unless the tag
 * names another subtree (`@internal Acme\Foo`). Two allow-lists widen that: `allowedInternals`
 * names what may be reached, `allowedCallers` names who may reach it, and each entry is a plain
 * prefix or a delimited pattern.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/InternalUsageRule.md
 *
 * @example
 * ```php
 * PhpStan::getBuilder()
 *     ->replaceRule(InternalUsageRule::class, [
 *         'allowedCallers'   => ['Acme\Tests'],
 *         'allowedInternals' => ['Acme\User\Internal\PasswordHasher::hash()' => ['Acme\Security']],
 *     ])
 * ;
 * ```
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<Stmt>
 *
 * @see InternalUsageRuleTest
 */
final readonly class InternalUsageRule implements Rule
{
    use RuleTrait;

    private const string AT_API      = '@api';
    private const string AT_INTERNAL = '@internal';

    /**
     * @var array<array-key, non-empty-string|list<non-empty-string>>
     */
    private array $allowedInternals;

    /**
     * @var array<array-key, non-empty-string|list<non-empty-string>>
     */
    private array $allowedCallers;

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider PHPStan reflection provider (auto-wired)
     * @param ?array<array-key, non-empty-string|list<non-empty-string>> $allowedInternals what may be reached
     * @param ?array<array-key, non-empty-string|list<non-empty-string>> $allowedCallers who may reach it
     *
     * @throws InvalidArgumentException when an entry is neither a namespace prefix nor a delimited regex pattern
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        ?array $allowedInternals = null,
        ?array $allowedCallers = null,
    ) {
        $this->allowedInternals = self::getValidatedStringList('allowedInternals', $allowedInternals);
        $this->allowedCallers   = self::getValidatedStringList('allowedCallers', $allowedCallers);
    }

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
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $callerNamespace = self::resolveCallerNamespace($node, $scope);

        if ($node instanceof Use_ || $node instanceof GroupUse || $node instanceof TraitUse) {
            return [];
        }

        foreach (self::collectNodesWithinStatement($node) as $subNode) {
            $error = $this->processSubNode($subNode, $scope, $callerNamespace);

            if ($error instanceof IdentifierRuleError) {
                return [$error];
            }
        }

        return [];
    }

    private static function resolveCallerNamespace(Node $node, Scope $scope): string
    {
        $namespace = $scope->getNamespace() ?? '';
        $fileDoc   = self::resolveFileLevelDoc($node, $scope);
        $target    = self::resolveInternalTarget($fileDoc?->getText());

        return $target === null || $target === self::AT_INTERNAL ? $namespace : $target;
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

    private function processFunctionCall(FuncCall $funcCall, Scope $scope, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$funcCall->name instanceof Name || !$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
            return null;
        }

        $functionReflection = $this->reflectionProvider->getFunction($funcCall->name, $scope);
        $internalTarget     = self::resolveInternalTarget($functionReflection->getDocComment());
        $declaringNamespace = Str::beforeLast($functionReflection->getName(), '\\');

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $declaringNamespace,
            $callerNamespace,
            self::KIND_FUNCTION,
            $functionReflection->getName() . '()',
            $line,
        );
    }

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

        $internalTarget = self::resolveEffectiveInternalTarget(
            $extendedMethodReflection->getDocComment(),
            $classReflection->getNativeReflection()->getDocComment(),
        );

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classReflection->getNativeReflection()->getNamespaceName(),
            $callerNamespace,
            self::KIND_METHOD,
            $resolvedName . '::' . $methodName . '()',
            $line,
        );
    }

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

        $internalTarget = self::resolveEffectiveInternalTarget(
            $method->getDocComment(),
            $classReflection->getNativeReflection()->getDocComment(),
        );

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::KIND_METHOD,
            $classReflection->getName() . '::' . $method->getName() . '()',
            $line,
        );
    }

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

        $internalTarget = self::resolveEffectiveInternalTarget(
            $property->getDocComment(),
            $classReflection->getNativeReflection()->getDocComment(),
        );

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::KIND_PROPERTY,
            $classReflection->getName() . '::$' . $propertyFetch->name->toString(),
            $line,
        );
    }

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

    private function processClassAndConst(string $className, string $constName, string $callerNamespace, int $line): ?IdentifierRuleError
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasConstant($constName)) {
            return null;
        }

        $internalTarget = self::resolveEffectiveInternalTarget(
            $classReflection->getConstant($constName)->getDocComment(),
            $classReflection->getNativeReflection()->getDocComment(),
        );

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
        $matches = Str::match($docComment ?: '', '/\*\s+@internal(?=\s|$)(?<target>[^\n]*)(?:\n|$)/');

        if (!isset($matches['target'])) {
            return null;
        }

        $target = self::trimBackslashes(Str::trim($matches['target']));

        return Str::match($target, '/^[\w\\\]+$/') === [] ? self::AT_INTERNAL : $target;
    }

    private static function resolveVisibility(string|false|null $docComment): ?string
    {
        return self::resolveInternalTarget($docComment)
            ?? (self::hasTagInText($docComment ?: '', self::TAG_API) ? self::AT_API : null);
    }

    private static function resolveEffectiveInternalTarget(string|false|null $memberDocComment, string|false|null $containerDocComment): ?string
    {
        $visibility = self::resolveVisibility($memberDocComment) ?? self::resolveVisibility($containerDocComment);

        return $visibility === self::AT_API ? null : $visibility;
    }

    private static function trimBackslashes(string $namespace): string
    {
        return Str::trim($namespace, '\\');
    }

    private function isAllowedInCaller(string $internalTarget, string $declaringNamespace, string $callerNamespace, string $symbol): bool
    {
        $callerValues   = [$callerNamespace];
        $internalValues = [$internalTarget, $declaringNamespace, $symbol];

        if (self::isAllowedByAny($internalValues, $this->allowedInternals, $callerValues)) {
            return true;
        }

        if (self::isAllowedByAny($callerValues, $this->allowedCallers, $internalValues)) {
            return true;
        }

        return self::isInSubtree(
            $callerNamespace,
            $internalTarget === self::AT_INTERNAL ? $declaringNamespace : $internalTarget,
        );
    }

    /**
     * @param non-empty-list<string> $values
     * @param ?array<array-key, non-empty-string|list<non-empty-string>> $entries
     * @param non-empty-list<string> $counterparts
     */
    private static function isAllowedByAny(array $values, ?array $entries, array $counterparts): bool
    {
        foreach ($entries ?? [] as $key => $entry) {
            $pattern = is_int($key) ? $entry : $key;

            if (!is_string($pattern)) {
                continue;
            }

            if (!self::isAllowedByAnyValue($values, $pattern)) {
                continue;
            }

            if (is_int($key) || self::isAllowedByAnyCounterpart($counterparts, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-list<string> $values
     */
    private static function isAllowedByAnyValue(array $values, string $pattern): bool
    {
        return array_any($values, static fn (string $value): bool => self::isAllowed($value, $pattern));
    }

    /**
     * @param non-empty-list<string> $counterparts
     * @param non-empty-string|list<non-empty-string> $patterns
     */
    private static function isAllowedByAnyCounterpart(array $counterparts, string|array $patterns): bool
    {
        return is_array($patterns) && array_any(
            $patterns,
            static fn (string $pattern): bool => array_any(
                $counterparts,
                static fn (string $counterpart): bool => self::isAllowed($counterpart, $pattern),
            ),
        );
    }

    private static function isAllowed(string $value, string $pattern): bool
    {
        return Str::isRegex($pattern)
            ? Str::match($value, $pattern) !== []
            : self::isInSubtree($value, $pattern);
    }

    private static function isInSubtree(string $value, string $prefix): bool
    {
        return $value === $prefix || Str::startsWithAny($value, [$prefix . '\\', $prefix . '::']);
    }

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
     * @return array<array-key, non-empty-string|list<non-empty-string>>
     *
     * @throws InvalidArgumentException
     */
    private static function getValidatedStringList(string $optionName, ?array $input): array
    {
        $entries = [];

        foreach ($input ?? [] as $key => $value) {
            if (is_int($key)) {
                $entries[] = self::getValidatedPattern($optionName, $value);

                continue;
            }

            if (!is_array($value) || !array_is_list($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Entry "%s" for option "%s" must map to a list of non-empty strings.',
                    $key,
                    $optionName,
                ));
            }

            $entries[self::getValidatedPattern($optionName, $key)] = array_map(
                static fn (mixed $counterpart): string => self::getValidatedPattern($optionName, $counterpart),
                $value,
            );
        }

        return $entries;
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function getValidatedPattern(string $optionName, mixed $pattern): string
    {
        if (!is_string($pattern) || Str::isEmpty($pattern)) {
            throw new InvalidArgumentException(sprintf(
                'Value for option "%s" must be a list of non-empty strings.',
                $optionName,
            ));
        }

        if (Str::isRegex($pattern)) {
            return $pattern;
        }

        $namespacePrefix = self::trimBackslashes($pattern);

        if (Str::match($pattern, '/^[\w\\\]/') === [] || Str::isEmpty($namespacePrefix)) {
            throw new InvalidArgumentException(sprintf(
                'Entry "%s" for option "%s" is neither a namespace prefix nor a delimited regex pattern.',
                $pattern,
                $optionName,
            ));
        }

        return $namespacePrefix;
    }
}
