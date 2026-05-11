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
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_filter;
use function array_find;
use function array_is_list;
use function is_string;
use function sprintf;

/**
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 */
final class InternalUsageRule implements Rule
{
    use RuleTrait;

    private const string AT_INTERNAL = '@internal';

    private const string KIND_CLASS     = 'Class';
    private const string KIND_CONSTANT  = 'Constant';
    private const string KIND_ENUM      = 'Enum';
    private const string KIND_FUNCTION  = 'Function';
    private const string KIND_INTERFACE = 'Interface';
    private const string KIND_METHOD    = 'Method';
    private const string KIND_PROPERTY  = 'Property';
    private const string KIND_TRAIT     = 'Trait';

    /**
     * @var list<string>
     */
    private array $allowedInternalTargets;

    /**
     * @var list<string>
     */
    private array $allowedDeclaringNamespaces;

    /**
     * @var list<string>
     */
    private array $allowedCallingNamespaces;

    /**
     * @param ?list<string> $allowedInternalTargets
     * @param ?list<string> $allowedDeclaringNamespaces
     * @param ?list<string> $allowedCallingNamespaces
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        ?array $allowedInternalTargets = null,
        ?array $allowedDeclaringNamespaces = null,
        ?array $allowedCallingNamespaces = null,
    ) {
        $this->setAllowedInternalTargets($allowedInternalTargets);
        $this->setAllowedDeclaringNamespaces($allowedDeclaringNamespaces);
        $this->setAllowedCallingNamespaces($allowedCallingNamespaces);
    }

    #[Override]
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $callerNamespace = $scope->getNamespace() ?? '';
        $line            = $node->getStartLine();

        return array_filter(
            match (true) {
                $node instanceof New_            => [$this->processNameLikeNode($node->class, $scope, $callerNamespace, $line)],
                $node instanceof Name            => [$this->processNameLikeNode($node, $scope, $callerNamespace, $line)],
                $node instanceof StaticCall      => [$this->processStaticCall($node, $scope, $callerNamespace, $line)],
                $node instanceof ClassConstFetch => [$this->processClassConstFetch($node, $scope, $callerNamespace, $line)],
                $node instanceof MethodCall      => [$this->processMethodCall($node, $scope, $callerNamespace, $line)],
                $node instanceof PropertyFetch   => [$this->processPropertyFetch($node, $scope, $callerNamespace, $line)],
                $node instanceof FuncCall        => [$this->processFunctionCall($node, $scope, $callerNamespace, $line)],
                $node instanceof ConstFetch      => [$this->processConstantFetch($node, $scope, $callerNamespace, $line)],
                default                          => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        );
    }

    /**
     * @param ?list<string> $allowedInternalTargets
     *
     * @throws InvalidArgumentException
     */
    private function setAllowedInternalTargets(?array $allowedInternalTargets): void
    {
        $this->allowedInternalTargets = self::getValidatedStringList('allowedInternalTargets', $allowedInternalTargets);
    }

    /**
     * @param ?list<string> $allowedDeclaringNamespaces
     *
     * @throws InvalidArgumentException
     */
    private function setAllowedDeclaringNamespaces(?array $allowedDeclaringNamespaces): void
    {
        $this->allowedDeclaringNamespaces = self::getValidatedStringList('allowedDeclaringNamespaces', $allowedDeclaringNamespaces);
    }

    /**
     * @param ?list<string> $allowedCallingNamespaces
     *
     * @throws InvalidArgumentException
     */
    private function setAllowedCallingNamespaces(?array $allowedCallingNamespaces): void
    {
        $this->allowedCallingNamespaces = self::getValidatedStringList('allowedCallingNamespaces', $allowedCallingNamespaces);
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
            $funcCall->name->toString(),
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
        if (!$staticCall->class instanceof Name) {
            return null;
        }

        $resolvedName = $scope->resolveName($staticCall->class);

        if (!$this->reflectionProvider->hasClass($resolvedName)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($resolvedName);
        $classNamespace  = $classReflection->getNativeReflection()->getNamespaceName();
        $internalTarget  = self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        $classError = $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::getKindForClassReflection($classReflection),
            $resolvedName,
            $line,
        );

        if ($classError instanceof IdentifierRuleError) {
            return $classError;
        }

        if (!$staticCall->name instanceof Identifier || !$classReflection->hasMethod($staticCall->name->toString())) {
            return null;
        }

        $methodName               = $staticCall->name->toString();
        $extendedMethodReflection = $classReflection->getMethod($methodName, $scope);
        $internalTarget           = self::resolveInternalTarget($extendedMethodReflection->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
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
        $classNamespace  = $classReflection->getNativeReflection()->getNamespaceName();
        $internalTarget  = self::resolveInternalTarget($classReflection->getNativeReflection()->getDocComment());

        $classError = $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
            $callerNamespace,
            self::getKindForClassReflection($classReflection),
            $className,
            $line,
        );

        if ($classError instanceof IdentifierRuleError) {
            return $classError;
        }

        if (!$classReflection->hasConstant($constName)) {
            return null;
        }

        $internalTarget = self::resolveInternalTarget($classReflection->getConstant($constName)->getDocComment());

        return $this->buildViolationIfDisallowed(
            $internalTarget,
            $classNamespace,
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

    private function isAllowedInCaller(string $internalTarget, string $declaringNamespace, string $callerNamespace): bool
    {
        $patternsByValue = [
            $internalTarget     => $this->allowedInternalTargets,
            $declaringNamespace => $this->allowedDeclaringNamespaces,
            $callerNamespace    => $this->allowedCallingNamespaces,
        ];

        foreach ($patternsByValue as $value => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::match($value, $pattern) !== []) {
                    return true;
                }
            }
        }

        return $internalTarget === self::AT_INTERNAL
            ? Str::doesStartWith($callerNamespace, $declaringNamespace)
            : ($callerNamespace === '' || Str::doesContain($callerNamespace, $internalTarget));
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
        return $internalTarget !== null && !$this->isAllowedInCaller($internalTarget, $declaringNamespace, $callerNamespace)
            ? self::buildError($kind, $symbol, $internalTarget ?: self::AT_INTERNAL, $callerNamespace, $line)
            : null;
    }

    /**
     * @throws RuntimeException
     */
    private static function buildError(string $kind, string $symbol, string $internalTarget, string $callerNamespace, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s `%s` is internal%sand must not be used from `%s`.',
            $kind,
            $symbol,
            $internalTarget === self::AT_INTERNAL ? ' ' : sprintf(' to `%s` ', $internalTarget),
            $callerNamespace,
        ), $line);
    }

    /**
     * @param ?array<array-key, mixed> $input
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    private static function getValidatedStringList(string $optionName, ?array $input): array
    {
        if ($input === null) {
            return [];
        }

        if (!array_is_list($input) || array_find($input, static fn ($item): bool => !is_string($item))) {
            throw new InvalidArgumentException(sprintf(
                'Value for option "%s" must be a list of strings.',
                $optionName,
            ));
        }

        /**
         * @var list<string> $inputCasted
         */
        $inputCasted = $input;

        return $inputCasted;
    }

    private static function getKindForClassReflection(ClassReflection $classReflection): string
    {
        return match (true) {
            $classReflection->isEnum()      => self::KIND_ENUM,
            $classReflection->isInterface() => self::KIND_INTERFACE,
            $classReflection->isTrait()     => self::KIND_TRAIT,
            default                         => self::KIND_CLASS,
        };
    }
}
