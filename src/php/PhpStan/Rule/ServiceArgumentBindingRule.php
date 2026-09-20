<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\ServiceArgumentBindingRuleTest;
use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use ReflectionException;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator;

use function array_any;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function sprintf;

/**
 * Requires every service argument bound by name to name a parameter that exists.
 *
 * `->arg('$dsn', …)` in a service definition makes that parameter name part of the contract,
 * and nothing checks it: rename the parameter and the definition still says the old name.
 * The container is the first to object, at compile time, in whichever application installs the package.
 *
 * `->args()`, `->bind()` and `->call()` bind by name as well.
 * A `->call()` binds to the method it names, and a `->factory()` or `->constructor()`
 * replaces the constructor with the callable it names.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<Expression>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/ServiceArgumentBindingRule.md
 * @see ServiceArgumentBindingRuleTest
 */
final readonly class ServiceArgumentBindingRule implements Rule
{
    use RuleTrait;

    private const string CONSTRUCTOR             = '__construct';
    private const string METHOD_ARG              = 'arg';
    private const string METHOD_ARGS             = 'args';
    private const string METHOD_BIND             = 'bind';
    private const string METHOD_CALL             = 'call';
    private const string METHOD_CONSTRUCTOR      = 'constructor';
    private const string METHOD_FACTORY          = 'factory';
    private const string METHOD_SET              = 'set';
    private const string CONFIGURATOR_CLASS      = AbstractConfigurator::class;
    private const string INLINE_SERVICE_FUNCTION = 'Symfony\Component\DependencyInjection\Loader\Configurator\inline_service';

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
        return Expression::class;
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
        return $this->processChain($node->expr, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private function processChain(Expr $expr, Scope $scope): array
    {
        $calls  = self::collectChainCalls($expr);
        $errors = [];

        foreach ($calls as $call) {
            foreach ($call->getArgs() as $argument) {
                $errors = [
                    ...$errors,
                    ...$this->processChain($argument->value, $scope),
                ];
            }
        }

        $className = $this->resolveDefinedClass($calls, $scope);

        if ($className === null) {
            return $errors;
        }

        $factory = $this->resolveFactory($calls, $className, $scope);

        if ($factory === false) {
            return $errors;
        }

        foreach ($calls as $call) {
            $errors = [
                ...$errors,
                ...$this->processCall($call, $factory ?? [$className, self::CONSTRUCTOR], $scope),
            ];
        }

        return $errors;
    }

    /**
     * @return list<MethodCall>
     */
    private static function collectChainCalls(Expr $expr): array
    {
        $calls = [];

        while ($expr instanceof MethodCall) {
            $calls[] = $expr;
            $expr    = $expr->var;
        }

        return $calls;
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return ?class-string
     */
    private function resolveDefinedClass(array $calls, Scope $scope): ?string
    {
        foreach ($calls as $call) {
            if (!$this->isServiceConfigurator($scope->getType($call->var))) {
                continue;
            }

            if (self::isNamed($call, self::METHOD_SET)) {
                return $this->readClassArgument(array_values($call->getArgs()), $scope);
            }
        }

        $root = $calls === [] ? null : $calls[count($calls) - 1]->var;

        return $root instanceof FuncCall
            && $root->name instanceof Name
            && $scope->resolveName($root->name) === self::INLINE_SERVICE_FUNCTION
            ? $this->readClassArgument(array_values($root->getArgs()), $scope)
            : null;
    }

    /**
     * The callable a `->factory()` moves the bindings onto, `null` when there is none,
     * or `false` when one is present but the analysis cannot resolve it.
     *
     * @param list<MethodCall> $calls
     * @param class-string $definedClass
     *
     * @return array{class-string, non-empty-string}|false|null
     */
    private function resolveFactory(array $calls, string $definedClass, Scope $scope): array|false|null
    {
        foreach ($calls as $call) {
            $arguments = array_values($call->getArgs());
            $callable  = $arguments === [] ? null : $arguments[0]->value;

            if (self::isNamed($call, self::METHOD_CONSTRUCTOR)) {
                return $callable instanceof String_ ? self::toCallable($definedClass, $callable->value) : false;
            }

            if (self::isNamed($call, self::METHOD_FACTORY)) {
                return $this->readCallable($callable, $definedClass, $scope);
            }
        }

        return null;
    }

    /**
     * A `[Class::class, 'method']` pair, a `Class::method(...)` first-class callable, a
     * `'Class::method'` string, or a `[null, 'method']` pair naming a static method on the
     * defined class. Anything else the analysis cannot follow is `false`.
     *
     * @param class-string $definedClass
     *
     * @return array{class-string, non-empty-string}|false
     */
    private function readCallable(?Expr $expr, string $definedClass, Scope $scope): array|false
    {
        if ($expr instanceof Array_) {
            $items  = array_values($expr->items);
            $method = $items[1]->value ?? null;
            $class  = $items[0]->value ?? null;

            if (!$method instanceof String_) {
                return false;
            }

            return $class instanceof ConstFetch && $class->name->toLowerString() === 'null'
                ? self::toCallable($definedClass, $method->value)
                : self::toCallable($this->readClassName($class, $scope), $method->value);
        }

        if ($expr instanceof StaticCall) {
            return $expr->isFirstClassCallable() && $expr->name instanceof Identifier && $expr->class instanceof Name
                ? self::toCallable($this->resolveClassName($scope->resolveName($expr->class)), $expr->name->toString())
                : false;
        }

        if (!$expr instanceof String_ || !Str::contains($expr->value, '::')) {
            return false;
        }

        return self::toCallable(
            $this->resolveClassName(Str::beforeLast($expr->value, '::')),
            Str::afterLast($expr->value, '::'),
        );
    }

    /**
     * @param ?class-string $className
     *
     * @return array{class-string, non-empty-string}|false
     */
    private static function toCallable(?string $className, string $methodName): array|false
    {
        return $className !== null && $methodName !== '' ? [$className, $methodName] : false;
    }

    /**
     * @param array{class-string, non-empty-string} $target
     *
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private function processCall(MethodCall $methodCall, array $target, Scope $scope): array
    {
        if (!$this->isServiceConfigurator($scope->getType($methodCall->var))) {
            return [];
        }

        $arguments = array_values($methodCall->getArgs());

        if ($arguments === []) {
            return [];
        }

        if (self::isNamed($methodCall, self::METHOD_CALL)) {
            $method = $arguments[0]->value;

            return $method instanceof String_ && $method->value !== ''
                ? $this->reportUnknownNames($target[0], $method->value, self::readArrayKeys($arguments, 1))
                : [];
        }

        $boundNames = match (true) {
            self::isNamed($methodCall, self::METHOD_ARG),
            self::isNamed($methodCall, self::METHOD_BIND) => self::readLiteralName($arguments),
            self::isNamed($methodCall, self::METHOD_ARGS) => self::readArrayKeys($arguments, 0),
            default                                       => [],
        };

        return $this->reportUnknownNames($target[0], $target[1], $boundNames);
    }

    /**
     * @param list<array{
     *     name: non-empty-string,
     *     line: int,
     * }> $boundNames
     *
     * @return list<IdentifierRuleError>
     *
     * @throws ReflectionException
     */
    private function reportUnknownNames(string $className, string $methodName, array $boundNames): array
    {
        if ($boundNames === []) {
            return [];
        }

        $parameters = $this->getParameterNames($className, $methodName);

        if ($parameters === null) {
            return [];
        }

        $errors = [];

        foreach ($boundNames as $boundName) {
            if (in_array($boundName['name'], $parameters, true)) {
                continue;
            }

            $errors[] = self::buildRuleError(sprintf(
                'Service argument `$%s` is bound by name, but `%s::%s()` declares no such parameter.',
                $boundName['name'],
                $className,
                $methodName,
            ), $boundName['line']);
        }

        return $errors;
    }

    /**
     * @return ?list<string>
     *
     * @throws ReflectionException
     */
    private function getParameterNames(string $className, string $methodName): ?array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $nativeReflection = $this->reflectionProvider->getClass($className)->getNativeReflection();

        if ($methodName === self::CONSTRUCTOR) {
            return self::toParameterNames($nativeReflection->getConstructor()?->getParameters() ?? []);
        }

        return $nativeReflection->hasMethod($methodName)
            ? self::toParameterNames($nativeReflection->getMethod($methodName)->getParameters())
            : null;
    }

    /**
     * @param array<array-key, ReflectionParameter> $parameters
     *
     * @return list<string>
     */
    private static function toParameterNames(array $parameters): array
    {
        return array_values(array_map(
            static fn (ReflectionParameter $reflectionParameter): string => $reflectionParameter->getName(),
            $parameters,
        ));
    }

    /**
     * @param list<Arg> $arguments
     *
     * @return list<array{
     *     name: non-empty-string,
     *     line: int,
     * }>
     */
    private static function readLiteralName(array $arguments): array
    {
        $value = $arguments === [] ? null : $arguments[0]->value;

        if (!$value instanceof String_) {
            return [];
        }

        $name = self::toParameterName($value->value);

        return $name === null
            ? []
            : [[
                'name' => $name,
                'line' => $value->getStartLine(),
            ]];
    }

    /**
     * @param list<Arg> $arguments
     *
     * @return list<array{
     *     name: non-empty-string,
     *     line: int,
     * }>
     */
    private static function readArrayKeys(array $arguments, int $position): array
    {
        $value = $arguments[$position]->value ?? null;

        if (!$value instanceof Array_) {
            return [];
        }

        $names = [];

        foreach ($value->items as $item) {
            $name = $item->key instanceof String_ ? self::toParameterName($item->key->value) : null;

            if ($name !== null && $item->key !== null) {
                $names[] = [
                    'name' => $name,
                    'line' => $item->key->getStartLine(),
                ];
            }
        }

        return $names;
    }

    /**
     * @return ?non-empty-string
     */
    private static function toParameterName(string $value): ?string
    {
        if (!Str::startsWith($value, '$')) {
            return null;
        }

        $name = Str::trim($value, '$', 'start');

        return $name === '' ? null : $name;
    }

    private static function isNamed(MethodCall $methodCall, string $methodName): bool
    {
        return $methodCall->name instanceof Identifier
            && $methodCall->name->toString() === $methodName;
    }

    private function isServiceConfigurator(Type $type): bool
    {
        if (!$this->reflectionProvider->hasClass(self::CONFIGURATOR_CLASS)) {
            return false;
        }

        $configurator = $this->reflectionProvider->getClass(self::CONFIGURATOR_CLASS);

        return array_any(
            $type->getObjectClassReflections(),
            static fn (ClassReflection $classReflection): bool => $classReflection->getName() === self::CONFIGURATOR_CLASS
                || $classReflection->isSubclassOfClass($configurator),
        );
    }

    /**
     * @param list<Arg> $arguments
     *
     * @return ?class-string
     */
    private function readClassArgument(array $arguments, Scope $scope): ?string
    {
        foreach ($arguments as $argument) {
            $className = $this->readClassName($argument->value, $scope);

            if ($className !== null) {
                return $className;
            }
        }

        return null;
    }

    /**
     * @return ?class-string
     */
    private function readClassName(?Expr $expr, Scope $scope): ?string
    {
        if (!$expr instanceof ClassConstFetch || !$expr->class instanceof Name) {
            return null;
        }

        return $this->resolveClassName($scope->resolveName($expr->class));
    }

    /**
     * @return ?class-string
     */
    private function resolveClassName(string $className): ?string
    {
        return $this->reflectionProvider->hasClass($className) ? $className : null;
    }
}
