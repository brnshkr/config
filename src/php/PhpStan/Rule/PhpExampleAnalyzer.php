<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\FencedCodeRuleTest;
use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeFinder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

use function array_any;
use function array_key_exists;
use function array_map;
use function count;
use function in_array;
use function is_string;
use function sprintf;

use const PHP_INT_MAX;

/**
 * @internal
 *
 * @phpstan-type ExampleMessage array{
 *     message: non-empty-string,
 *     identifier: non-empty-string,
 * }
 * @phpstan-type DocumentedMethod array{
 *     className: string,
 *     methodName: string,
 * }
 * @phpstan-import-type ExampleError from FencedCodeRule
 *
 * @see FencedCodeRuleTest
 */
final class PhpExampleAnalyzer
{
    use RuleTrait;

    private const string IDENTIFIER_ARGUMENT_NAMED           = 'argument.named';
    private const string IDENTIFIER_ARGUMENT_UNKNOWN         = 'argument.unknown';
    private const string IDENTIFIER_ARGUMENTS_COUNT          = 'arguments.count';
    private const string IDENTIFIER_CLASS_CONSTANT_NOT_FOUND = 'classConstant.notFound';
    private const string IDENTIFIER_CLASS_NOT_FOUND          = 'class.notFound';
    private const string IDENTIFIER_METHOD_NOT_FOUND         = 'method.notFound';
    private const string IDENTIFIER_STATIC_METHOD_NOT_FOUND  = 'staticMethod.notFound';

    /**
     * @var array<string, ?ClassReflection>
     */
    private array $classesByVariable = [];

    /**
     * @var list<string>
     */
    private array $declaredClassNames = [];

    /**
     * @param Closure(Name $name): string $resolveClassName
     * @param list<string> $placeholderNamespaces
     * @param ?DocumentedMethod $documentedMethod
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly Closure $resolveClassName,
        private readonly array $placeholderNamespaces,
        private readonly ?array $documentedMethod,
    ) {}

    /**
     * @param array<array-key, Stmt> $statements
     *
     * @return list<ExampleError>
     *
     * @throws ReflectionException
     */
    public function analyze(array $statements): array
    {
        $exampleErrors = [];
        $nodeFinder    = new NodeFinder();

        foreach ($nodeFinder->findInstanceOf($statements, ClassLike::class) as $classLike) {
            if ($classLike->name !== null) {
                $this->declaredClassNames[] = Str::toLowerCase($classLike->name->toString());
            }
        }

        foreach ($nodeFinder->findInstanceOf($statements, Expr::class) as $expr) {
            if ($expr instanceof Assign
                && $expr->var instanceof Variable
                && is_string($expr->var->name)
                && Str::isNonDecimalIntString($expr->var->name)) {
                $this->classesByVariable[$expr->var->name] = $this->inferClass($expr->expr);
            }

            $exampleError = match (true) {
                $expr instanceof New_            => $this->processNew($expr),
                $expr instanceof StaticCall      => $this->processStaticCall($expr),
                $expr instanceof ClassConstFetch => $this->processClassConstFetch($expr),
                $expr instanceof MethodCall,
                $expr instanceof NullsafeMethodCall => $this->processMethodCall($expr),
                default                             => null,
            };

            if ($exampleError !== null) {
                $exampleErrors[] = $exampleError;
            }
        }

        return $exampleErrors;
    }

    /**
     * @return ?ExampleError
     *
     * @throws ReflectionException
     */
    private function processNew(New_ $new): ?array
    {
        if (!$new->class instanceof Name || $this->isUncheckedName($new->class)) {
            return null;
        }

        $classReflection = $this->findClass($new->class);

        if (!$classReflection instanceof ClassReflection) {
            return $this->buildMissingClassError($new->class);
        }

        return $classReflection->hasMethod('__construct')
            ? $this->processCall($classReflection, '__construct', $new)
            : null;
    }

    /**
     * @return ?ExampleError
     *
     * @throws ReflectionException
     */
    private function processStaticCall(StaticCall $staticCall): ?array
    {
        if (!$staticCall->class instanceof Name
            || !$staticCall->name instanceof Identifier
            || $this->isUncheckedName($staticCall->class)) {
            return null;
        }

        $classReflection = $this->findClass($staticCall->class);

        if (!$classReflection instanceof ClassReflection) {
            return $this->buildMissingClassError($staticCall->class);
        }

        return $this->processCall($classReflection, $staticCall->name->toString(), $staticCall);
    }

    /**
     * @return ?ExampleError
     */
    private function processClassConstFetch(ClassConstFetch $classConstFetch): ?array
    {
        if (!$classConstFetch->class instanceof Name
            || !$classConstFetch->name instanceof Identifier
            || $this->isUncheckedName($classConstFetch->class)) {
            return null;
        }

        $classReflection = $this->findClass($classConstFetch->class);
        $constantName    = $classConstFetch->name->toString();

        if (!$classReflection instanceof ClassReflection) {
            return $this->buildMissingClassError($classConstFetch->class);
        }

        if (Str::toLowerCase($constantName) === 'class' || $classReflection->hasConstant($constantName)) {
            return null;
        }

        return [
            'message'    => self::getMissingMessage(self::KIND_CONSTANT, $classReflection->getName() . '::' . $constantName),
            'identifier' => self::IDENTIFIER_CLASS_CONSTANT_NOT_FOUND,
            'line'       => $classConstFetch->getStartLine(),
        ];
    }

    /**
     * @return ?ExampleError
     *
     * @throws ReflectionException
     */
    private function processMethodCall(MethodCall|NullsafeMethodCall $methodCall): ?array
    {
        $classReflection = $this->inferReceiverClass($methodCall);

        if (!$classReflection instanceof ClassReflection || !$methodCall->name instanceof Identifier) {
            return null;
        }

        return $this->processCall($classReflection, $methodCall->name->toString(), $methodCall);
    }

    /**
     * @return ?ExampleError
     *
     * @throws ReflectionException
     */
    private function processCall(ClassReflection $classReflection, string $methodName, CallLike $callLike): ?array
    {
        $arguments       = $callLike->getArgs();
        $line            = $callLike->getStartLine();
        $methodReference = sprintf('%s::%s()', $classReflection->getName(), $methodName);

        $hasMagicMethods = $classReflection->hasNativeMethod('__call')
            || $classReflection->hasNativeMethod('__callStatic');

        if (!$classReflection->hasMethod($methodName) && !$hasMagicMethods) {
            return [
                'message'    => self::getMissingMessage(self::KIND_METHOD, $methodReference),
                'identifier' => $callLike instanceof StaticCall
                    ? self::IDENTIFIER_STATIC_METHOD_NOT_FOUND
                    : self::IDENTIFIER_METHOD_NOT_FOUND,
                'line' => $line,
            ];
        }

        if (!$classReflection->hasNativeMethod($methodName)) {
            return null;
        }

        $reflectionMethod = $classReflection->getNativeReflection()->getMethod($methodName);

        $exampleMessage = $this->getStanceMessage(
            $classReflection,
            $reflectionMethod->getName(),
            $arguments,
            $methodReference,
        ) ?? self::getArgumentMessage($reflectionMethod, $arguments, $methodReference);

        return $exampleMessage === null
            ? null
            : [
                ...$exampleMessage,
                'line' => $line,
            ];
    }

    /**
     * @param array<array-key, Arg|VariadicPlaceholder> $arguments
     *
     * @return ?ExampleMessage
     *
     * @throws ReflectionException
     */
    private function getStanceMessage(
        ClassReflection $classReflection,
        string $methodName,
        array $arguments,
        string $methodReference,
    ): ?array {
        $hasNamedArgument = array_any(
            $arguments,
            static fn (Arg|VariadicPlaceholder $argument): bool => $argument instanceof Arg
                && $argument->name instanceof Identifier,
        );

        $hasPositionalArgument = array_any(
            $arguments,
            static fn (Arg|VariadicPlaceholder $argument): bool => $argument instanceof Arg
                && !$argument->name instanceof Identifier
                && !$argument->unpack,
        );

        if ($hasNamedArgument && $classReflection->getNativeMethod($methodName)->acceptsNamedArguments()->no()) {
            return [
                'message' => sprintf(
                    'Call to `%s` must not name its arguments, because it is tagged `@no-named-arguments`.',
                    $methodReference,
                ),
                'identifier' => self::IDENTIFIER_ARGUMENT_NAMED,
            ];
        }

        if ($hasPositionalArgument && self::requiresNamedArguments($classReflection, $methodName)) {
            return [
                'message'    => self::getPositionalArgumentMessage($methodReference),
                'identifier' => NamedArgumentsUsageRule::getIdentifier(),
            ];
        }

        return null;
    }

    /**
     * @param array<array-key, Arg|VariadicPlaceholder> $arguments
     *
     * @return ?ExampleMessage
     */
    private static function getArgumentMessage(
        ReflectionMethod $reflectionMethod,
        array $arguments,
        string $methodReference,
    ): ?array {
        $parameterNames = array_map(
            static fn (ReflectionParameter $reflectionParameter): string => $reflectionParameter->getName(),
            $reflectionMethod->getParameters(),
        );

        foreach ($arguments as $argument) {
            if ($argument instanceof Arg
                && $argument->name instanceof Identifier
                && !$reflectionMethod->isVariadic()
                && !in_array($argument->name->toString(), $parameterNames, true)) {
                return [
                    'message' => sprintf(
                        'Call to `%s` names unknown parameter `$%s`.',
                        $methodReference,
                        $argument->name->toString(),
                    ),
                    'identifier' => self::IDENTIFIER_ARGUMENT_UNKNOWN,
                ];
            }
        }

        $argumentCount = count($arguments);
        $requiredCount = $reflectionMethod->getNumberOfRequiredParameters();
        $maximumCount  = $reflectionMethod->isVariadic() ? PHP_INT_MAX : $reflectionMethod->getNumberOfParameters();

        $isUnpacked = array_any(
            $arguments,
            static fn (Arg|VariadicPlaceholder $argument): bool => !$argument instanceof Arg || $argument->unpack,
        );

        if ($isUnpacked || ($argumentCount >= $requiredCount && $argumentCount <= $maximumCount)) {
            return null;
        }

        $message = $requiredCount === $maximumCount
            ? sprintf(
                'Call to `%s` passes %d arguments, but it takes %d.',
                $methodReference,
                $argumentCount,
                $requiredCount,
            )
            : sprintf(
                'Call to `%s` passes %d arguments, but it takes %d to %d.',
                $methodReference,
                $argumentCount,
                $requiredCount,
                $maximumCount,
            );

        return [
            'message'    => $message,
            'identifier' => self::IDENTIFIER_ARGUMENTS_COUNT,
        ];
    }

    private function inferClass(Expr $expr): ?ClassReflection
    {
        if ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall) {
            return $expr->name instanceof Identifier
                ? $this->findReturnedClass($this->inferReceiverClass($expr), $expr->name->toString())
                : null;
        }

        if ($expr instanceof Variable) {
            return is_string($expr->name) ? $this->classesByVariable[$expr->name] ?? null : null;
        }

        if ((!$expr instanceof New_ && !$expr instanceof StaticCall)
            || !$expr->class instanceof Name
            || $this->isUncheckedName($expr->class)) {
            return null;
        }

        $classReflection = $this->findClass($expr->class);

        return $expr instanceof StaticCall && $expr->name instanceof Identifier
            ? $this->findReturnedClass($classReflection, $expr->name->toString())
            : $classReflection;
    }

    private function inferReceiverClass(MethodCall|NullsafeMethodCall $methodCall): ?ClassReflection
    {
        $receiver = $methodCall->var;

        $isDocumentedReceiver = $receiver instanceof Variable
            && is_string($receiver->name)
            && !array_key_exists($receiver->name, $this->classesByVariable)
            && $methodCall->name instanceof Identifier
            && $methodCall->name->toString() === ($this->documentedMethod['methodName'] ?? null);

        if (!$isDocumentedReceiver) {
            return $this->inferClass($receiver);
        }

        return $this->findClassByName($this->documentedMethod['className'] ?? '');
    }

    private function findReturnedClass(?ClassReflection $classReflection, string $methodName): ?ClassReflection
    {
        if (!$classReflection instanceof ClassReflection || !$classReflection->hasNativeMethod($methodName)) {
            return null;
        }

        $methodVariant    = $classReflection->getNativeMethod($methodName)->getVariants()[0] ?? null;
        $returnClassNames = $methodVariant?->getReturnType()->getObjectClassNames() ?? [];

        return count($returnClassNames) === 1 ? $this->findClassByName($returnClassNames[0]) : null;
    }

    private function findClass(Name $name): ?ClassReflection
    {
        return $this->findClassByName(($this->resolveClassName)($name));
    }

    private function findClassByName(string $className): ?ClassReflection
    {
        return $this->reflectionProvider->hasClass($className)
            ? $this->reflectionProvider->getClass($className)
            : null;
    }

    private function isUncheckedName(Name $name): bool
    {
        $isDeclaredHere = in_array(Str::toLowerCase($name->getLast()), $this->declaredClassNames, true);
        $className      = ($this->resolveClassName)($name);

        $isPlaceholderName = array_any(
            $this->placeholderNamespaces,
            static fn (string $namespace): bool => Str::startsWith($className, $namespace . '\\'),
        );

        return $name->isSpecialClassName()
            || (!$name->isFullyQualified() && $isDeclaredHere)
            || $isPlaceholderName;
    }

    /**
     * @return ExampleError
     */
    private function buildMissingClassError(Name $name): array
    {
        return [
            'message'    => self::getMissingMessage(self::KIND_CLASS, ($this->resolveClassName)($name)),
            'identifier' => self::IDENTIFIER_CLASS_NOT_FOUND,
            'line'       => $name->getStartLine(),
        ];
    }
}
