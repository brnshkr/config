<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\BoolishPrefixRuleTest;
use Override;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_ as ConstStmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_all;
use function array_any;
use function array_filter;
use function array_first;
use function array_map;
use function array_values;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Keeps boolean-ness and names aligned in both directions.
 *
 * A `bool`-typed symbol must start with a boolish prefix — {@see self::PREDICATE_PREFIXES} and
 * {@see self::FLAG_PREFIXES} — and a non-boolean one must not start with a reserved prefix —
 * {@see self::RESERVED_METHOD_PREFIXES} and {@see self::RESERVED_VALUE_PREFIXES}.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/BoolishPrefixRule.md
 * @see BoolishPrefixRuleTest
 */
final readonly class BoolishPrefixRule implements Rule
{
    use RuleTrait;

    /**
     * Prefixes a non-boolean value-holder must not start with.
     * The auxiliary, capability, and object-relation verbs — each reads as a boolean flag on a value.
     *
     * @internal
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    public const array RESERVED_VALUE_PREFIXES = [
        ...self::AUXILIARY_PREFIXES,
        ...self::CAPABILITY_PREFIXES,
        ...self::RELATIONAL_PREFIXES,
    ];

    /**
     * Prefixes a non-boolean method or function must not start with.
     * The value-reserved set plus the colliders — they read as a predicate on a method, data on a value.
     *
     * @internal
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    public const array RESERVED_METHOD_PREFIXES = [
        ...self::RESERVED_VALUE_PREFIXES,
        ...self::COLLIDER_PREFIXES,
    ];

    /**
     * Prefixes a `bool`-returning method or function may start with.
     * The method-reserved set plus the `do` directive, which commands may also use on non-bool returns.
     *
     * @internal
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    public const array PREDICATE_PREFIXES = [
        ...self::RESERVED_METHOD_PREFIXES,
        ...self::DIRECTIVE_PREFIXES,
    ];

    /**
     * Prefixes a boolean value-holder may start with.
     * The value-reserved set plus the `do` directive and the representation flag `as`.
     *
     * @internal
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    public const array FLAG_PREFIXES = [
        ...self::RESERVED_VALUE_PREFIXES,
        ...self::DIRECTIVE_PREFIXES,
        'as',
    ];

    /**
     * Auxiliary, modal, and copula verbs.
     * Boolish in either direction, on any kind.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array AUXILIARY_PREFIXES = [
        'are',
        'can',
        'did',
        'does',
        'has',
        'is',
        'may',
        'should',
        'was',
        'will',
    ];

    /**
     * Capability and need verbs.
     * Read as boolean value flags too, so boolish in either direction, on any kind.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array CAPABILITY_PREFIXES = [
        'expects',
        'needs',
        'prefers',
        'requires',
        'supports',
        'wants',
    ];

    /**
     * Object-relation predicate verbs.
     * Boolish in either direction, on any kind — `$allowsNull` reads as a flag, `equals()` as a predicate.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array RELATIONAL_PREFIXES = [
        'accepts',
        'allows',
        'belongs',
        'contains',
        'covers',
        'denies',
        'depends',
        'disallows',
        'equals',
        'excludes',
        'exists',
        'extends',
        'handles',
        'ignores',
        'implements',
        'includes',
        'intersects',
        'owns',
        'provides',
        'rejects',
        'satisfies',
        'uses',
    ];

    /**
     * Verbs whose third-person form is a canonical non-boolean value (`$matches`, `$startsAt`,
     * `$endsAt`) — reserved on methods and functions only, never on value-holders.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array COLLIDER_PREFIXES = [
        'ends',
        'matches',
        'starts',
    ];

    /**
     * Command verbs — forward-allowed on a `bool` return, never reserved, since `doReset(): void`
     * and the like legitimately return non-bool.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array DIRECTIVE_PREFIXES = [
        'do',
    ];

    private const string TYPE_BOOL      = 'bool';
    private const string TYPE_NON_BOOL  = 'non-bool';
    private const string TYPE_PREDICATE = 'predicate';
    private const string TYPE_UNKNOWN   = 'unknown';

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider - PHPStan reflection provider (auto-wired)
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @internal
     */
    #[Override]
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }

    /**
     * @internal
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return array_values(array_filter(
            match (true) {
                $node instanceof Function_                                 => $this->processFunction($node, $scope),
                $node instanceof ClassMethod                               => self::processClassMethod($node, $scope),
                $node instanceof Closure || $node instanceof ArrowFunction => self::processClosure($node, $scope),
                $node instanceof ClassConst || $node instanceof ConstStmt  => $this->processConsts($node, $scope),
                $node instanceof Property                                  => self::processProperty($node, $scope),
                $node instanceof Assign                                    => [self::processAssign($node, $scope)],
                default                                                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private function processFunction(Function_ $function, Scope $scope): array
    {
        $name = new Name($function->namespacedName?->toString() ?? $function->name->toString());

        if (!$this->reflectionProvider->hasFunction($name, $scope)) {
            return [];
        }

        return self::processFunctionLike(
            $function,
            self::KIND_FUNCTION,
            array_first($this->reflectionProvider->getFunction($name, $scope)->getVariants()),
            $scope,
        );
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private static function processClassMethod(ClassMethod $classMethod, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        $methodName      = $classMethod->name->toString();

        if (!$classReflection instanceof ClassReflection
            || !$classReflection->hasNativeMethod($methodName)
            || self::isMethodLocked($classMethod, $scope)) {
            return [];
        }

        return self::processFunctionLike(
            $classMethod,
            self::KIND_METHOD,
            array_first($classReflection->getNativeMethod($methodName)->getVariants()),
            $scope,
        );
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private static function processClosure(Closure|ArrowFunction $closure, Scope $scope): array
    {
        $acceptor = array_first($scope->getType($closure)->getCallableParametersAcceptors($scope));

        return $acceptor instanceof ParametersAcceptor ? self::processParams($closure, $acceptor, $scope) : [];
    }

    /**
     * @param self::KIND_FUNCTION|self::KIND_METHOD $kind
     *
     * @return list<?IdentifierRuleError>
     */
    private static function processFunctionLike(
        Function_|ClassMethod $functionOrMethod,
        string $kind,
        ?ParametersAcceptor $parametersAcceptor,
        Scope $scope,
    ): array {
        if (!$parametersAcceptor instanceof ParametersAcceptor) {
            return [];
        }

        $returnType = self::classifyType($parametersAcceptor->getReturnType(), $scope);

        return [
            self::checkSymbol(
                $kind,
                $functionOrMethod->name->toString(),
                $returnType === self::TYPE_PREDICATE ? self::TYPE_NON_BOOL : $returnType,
                $functionOrMethod->getStartLine(),
            ),
            ...self::processParams($functionOrMethod, $parametersAcceptor, $scope),
        ];
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private static function processParams(
        FunctionLike $functionLike,
        ParametersAcceptor $parametersAcceptor,
        Scope $scope,
    ): array {
        $parameterReflections = $parametersAcceptor->getParameters();
        $errors               = [];

        foreach ($functionLike->getParams() as $index => $param) {
            $parameterReflection = $parameterReflections[$index] ?? null;

            if (!$param->var instanceof Variable
                || !is_string($param->var->name)
                || !$parameterReflection instanceof ParameterReflection) {
                continue;
            }

            $errors = [...$errors, ...self::checkValue(
                $param,
                $param->var->name,
                $parameterReflection->getType(),
                $scope,
            )];
        }

        return $errors;
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private static function processProperty(Property $property, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        $errors          = [];

        foreach ($property->props as $propertyItem) {
            $name = $propertyItem->name->toString();

            if (!$classReflection instanceof ClassReflection || !$classReflection->hasNativeProperty($name)) {
                continue;
            }

            $errors = [...$errors, ...self::checkValue(
                $propertyItem,
                $name,
                $classReflection->getNativeProperty($name)->getReadableType(),
                $scope,
            )];
        }

        return $errors;
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private static function checkValue(Param|PropertyItem $node, string $name, Type $type, Scope $scope): array
    {
        $kind   = $node instanceof Param && $node->flags === 0 ? self::KIND_PARAMETER : self::KIND_PROPERTY;
        $errors = [self::checkSymbol($kind, $name, self::classifyType($type, $scope), $node->getStartLine())];

        if (!$type->isCallable()->yes()) {
            return $errors;
        }

        foreach ($type->getCallableParametersAcceptors($scope) as $callableParametersAcceptor) {
            foreach ($callableParametersAcceptor->getParameters() as $callableParameter) {
                if (Str::isEmpty($callableParameter->getName())) {
                    continue;
                }

                $errors[] = self::checkSymbol(
                    self::KIND_PARAMETER,
                    $callableParameter->getName(),
                    self::classifyType($callableParameter->getType(), $scope),
                    $node->getStartLine(),
                );
            }
        }

        return $errors;
    }

    /**
     * @return list<?IdentifierRuleError>
     */
    private function processConsts(ClassConst|ConstStmt $node, Scope $scope): array
    {
        return array_values(array_map(
            fn (Const_ $const): ?IdentifierRuleError => self::checkSymbol(
                self::KIND_CONSTANT,
                $const->name->toString(),
                self::classifyType($this->getConstantType($node, $const, $scope), $scope),
                $const->getStartLine(),
            ),
            $node->consts,
        ));
    }

    private function getConstantType(ClassConst|ConstStmt $node, Const_ $const, Scope $scope): Type
    {
        $name = $const->name->toString();

        if ($node instanceof ClassConst) {
            $classReflection = $scope->getClassReflection();

            return $classReflection instanceof ClassReflection && $classReflection->hasConstant($name)
                ? $classReflection->getConstant($name)->getValueType()
                : new MixedType();
        }

        $constantName = new Name($const->namespacedName?->toString() ?? $name);

        return $this->reflectionProvider->hasConstant($constantName, $scope)
            ? $this->reflectionProvider->getConstant($constantName, $scope)->getValueType()
            : new MixedType();
    }

    private static function processAssign(Assign $assign, Scope $scope): ?IdentifierRuleError
    {
        if (!$assign->var instanceof Variable
            || !is_string($assign->var->name)
            || $scope->hasVariableType($assign->var->name)->yes()) {
            return null;
        }

        return self::checkSymbol(
            self::KIND_VARIABLE,
            $assign->var->name,
            self::classifyType($scope->getType($assign->expr), $scope),
            $assign->getStartLine(),
        );
    }

    /**
     * @param self::KIND_* $kind
     * @param self::TYPE_* $type
     */
    private static function checkSymbol(string $kind, string $name, string $type, int $line): ?IdentifierRuleError
    {
        if ($type === self::TYPE_PREDICATE) {
            return in_array(self::getFirstWord($name), self::PREDICATE_PREFIXES, true)
                ? null
                : self::buildMissingPrefixError($kind, $name, self::PREDICATE_PREFIXES, $line);
        }

        if ($type === self::TYPE_BOOL) {
            return self::isBoolishName($name, $kind)
                ? null
                : self::buildMissingPrefixError($kind, $name, self::getPrefixesForKind($kind), $line);
        }

        if ($type === self::TYPE_NON_BOOL) {
            $reservedToken = self::getReservedToken($name, $kind);

            return $reservedToken === null
                ? null
                : self::buildReservedPrefixError($kind, $name, $reservedToken, $line);
        }

        return null;
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function isBoolishName(string $name, string $kind): bool
    {
        if (self::hasBoolishPrefix($name, $kind)) {
            return true;
        }

        return self::isBooleanConverterMethod($name, $kind);
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function getReservedToken(string $name, string $kind): ?string
    {
        return self::isBooleanConverterMethod($name, $kind)
            ? self::getBooleanConverterToken($name)
            : self::getReservedPrefix($name, $kind);
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function hasBoolishPrefix(string $name, string $kind): bool
    {
        return in_array(self::getFirstWord($name), self::getPrefixesForKind($kind), true);
    }

    /**
     * @param self::KIND_* $kind
     *
     * @return ?non-empty-string
     */
    private static function getReservedPrefix(string $name, string $kind): ?string
    {
        $firstWord = self::getFirstWord($name);

        return in_array($firstWord, self::getReservedPrefixesForKind($kind), true)
            ? $firstWord
            : null;
    }

    private static function getFirstWord(string $name): string
    {
        return Str::toLowerCase(Str::match($name, '/^(?:[0-9a-z]+|[0-9A-Z]+)/')[0] ?? '');
    }

    /**
     * @param self::KIND_* $kind
     *
     * @return non-empty-list<non-empty-string>
     */
    private static function getPrefixesForKind(string $kind): array
    {
        return self::isMethodOrFunction($kind)
            ? self::PREDICATE_PREFIXES
            : self::FLAG_PREFIXES;
    }

    /**
     * @param self::KIND_* $kind
     *
     * @return non-empty-list<non-empty-string>
     */
    private static function getReservedPrefixesForKind(string $kind): array
    {
        return self::isMethodOrFunction($kind)
            ? self::RESERVED_METHOD_PREFIXES
            : self::RESERVED_VALUE_PREFIXES;
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function isBooleanConverterMethod(string $name, string $kind): bool
    {
        return self::isMethodOrFunction($kind) && self::getBooleanConverterToken($name) !== null;
    }

    private static function getBooleanConverterToken(string $name): ?string
    {
        return Str::match($name, '/^(?:as|to)(?:boolean|bool)/i')[0] ?? null;
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function isMethodOrFunction(string $kind): bool
    {
        return $kind === self::KIND_METHOD || $kind === self::KIND_FUNCTION;
    }

    /**
     * @return self::TYPE_*
     */
    private static function classifyType(Type $type, Scope $scope): string
    {
        $type = TypeCombinator::removeNull($type);

        if ($type->isCallable()->yes()) {
            $isPredicate = array_all(
                $type->getCallableParametersAcceptors($scope),
                static fn (ParametersAcceptor $parametersAcceptor): bool => $parametersAcceptor
                    ->getReturnType()
                    ->isBoolean()
                    ->yes(),
            );

            return $isPredicate ? self::TYPE_PREDICATE : self::TYPE_NON_BOOL;
        }

        $trinaryLogic = $type->isBoolean();

        return match (true) {
            $trinaryLogic->yes() => self::TYPE_BOOL,
            $trinaryLogic->no()  => self::TYPE_NON_BOOL,
            default              => self::TYPE_UNKNOWN,
        };
    }

    private static function isMethodLocked(ClassMethod $classMethod, Scope $scope): bool
    {
        $methodName = $classMethod->name->toString();

        if ($methodName === '__construct') {
            return false;
        }

        if (Str::startsWith($methodName, '__')) {
            return true;
        }

        $classReflection = $scope->getClassReflection();

        if (!$classReflection instanceof ClassReflection
            || $classReflection->isTrait()
            || $classReflection->isInterface()) {
            return false;
        }

        return $scope->getFile() === $classReflection->getFileName()
            && self::hasExternalUpstreamMethod($methodName, $classReflection);
    }

    private static function hasExternalUpstreamMethod(string $methodName, ClassReflection $classReflection): bool
    {
        $parentClass = $classReflection->getParentClass();

        while ($parentClass instanceof ClassReflection) {
            if ($parentClass->hasNativeMethod($methodName) && self::isExternal($parentClass)) {
                return true;
            }

            $parentClass = $parentClass->getParentClass();
        }

        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasMethod($methodName) && self::isExternal($interface)) {
                return true;
            }
        }

        return array_any(
            $classReflection->getTraits(recursive: true),
            static fn (ClassReflection $classReflection): bool => $classReflection->hasNativeMethod($methodName) && self::isExternal($classReflection),
        );
    }

    private static function isExternal(ClassReflection $classReflection): bool
    {
        $fileName = $classReflection->getFileName();

        return $fileName === null || Str::contains($fileName, '/vendor/');
    }

    /**
     * @param self::KIND_* $kind
     * @param non-empty-list<non-empty-string> $prefixes
     */
    private static function buildMissingPrefixError(string $kind, string $name, array $prefixes, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s name `%s` must have one of the following prefixes: %s.',
            $kind,
            $name,
            Str::join($prefixes, ', '),
        ), $line);
    }

    /**
     * @param self::KIND_* $kind
     */
    private static function buildReservedPrefixError(string $kind, string $name, string $prefix, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s name `%s` must not start with the boolish prefix `%s` because it is not boolean.',
            $kind,
            $name,
            $prefix,
        ), $line);
    }
}
