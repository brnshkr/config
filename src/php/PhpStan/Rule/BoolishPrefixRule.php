<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Override;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_ as ConstStmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use RuntimeException;

use function array_any;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
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
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/BoolishPrefixRule.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<NodeAbstract>
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

    private const string TYPE_BOOL     = 'bool';
    private const string TYPE_NON_BOOL = 'non-bool';
    private const string TYPE_UNKNOWN  = 'unknown';

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
     *
     * @throws RuntimeException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        return array_values(array_filter(
            match (true) {
                $node instanceof Function_                                 => self::processFunctionOrClassMethod($node, self::KIND_FUNCTION, $scope),
                $node instanceof ClassMethod                               => self::processFunctionOrClassMethod($node, self::KIND_METHOD, $scope),
                $node instanceof Closure || $node instanceof ArrowFunction => self::processParams($node),
                $node instanceof ClassConst                                => self::processConsts($node),
                $node instanceof ConstStmt                                 => self::processConsts($node),
                $node instanceof Property                                  => self::processProperty($node),
                $node instanceof Assign                                    => [self::processAssign($node, $scope)],
                default                                                    => [],
            },
            static fn (?IdentifierRuleError $identifierRuleError): bool => $identifierRuleError instanceof IdentifierRuleError,
        ));
    }

    /**
     * @param self::KIND_FUNCTION|self::KIND_METHOD $kind
     *
     * @return list<?IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processFunctionOrClassMethod(Function_|ClassMethod $functionOrMethod, string $kind, Scope $scope): array
    {
        if ($functionOrMethod instanceof ClassMethod && self::isMethodLocked($functionOrMethod, $scope)) {
            return [];
        }

        return [
            self::checkSymbol(
                $kind,
                $functionOrMethod->name->toString(),
                self::classifyTypeNode($functionOrMethod->returnType),
                $functionOrMethod->getStartLine(),
            ),
            ...self::processParams($functionOrMethod),
        ];
    }

    /**
     * @return list<?IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processParams(FunctionLike $functionLike): array
    {
        return array_values(array_map(self::processParam(...), $functionLike->getParams()));
    }

    /**
     * @return list<?IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processConsts(ClassConst|ConstStmt $node): array
    {
        $type = $node instanceof ClassConst ? $node->type : null;

        return array_values(array_map(
            static fn (Const_ $const): ?IdentifierRuleError => self::checkSymbol(
                self::KIND_CONSTANT,
                $const->name->toString(),
                $type instanceof Node
                    ? self::classifyTypeNode($type)
                    : self::classifyConstValue($const),
                $const->getStartLine(),
            ),
            $node->consts,
        ));
    }

    /**
     * @return list<?IdentifierRuleError>
     *
     * @throws RuntimeException
     */
    private static function processProperty(Property $property): array
    {
        $type = self::classifyTypeNode($property->type);

        return array_values(array_map(
            static fn (PropertyItem $propertyItem): ?IdentifierRuleError => self::checkSymbol(
                self::KIND_PROPERTY,
                $propertyItem->name->toString(),
                $type,
                $propertyItem->getStartLine(),
            ),
            $property->props,
        ));
    }

    /**
     * @throws RuntimeException
     */
    private static function processParam(Param $param): ?IdentifierRuleError
    {
        if (!$param->var instanceof Variable || !is_string($param->var->name)) {
            return null;
        }

        return self::checkSymbol(
            $param->flags === 0 ? self::KIND_PARAMETER : self::KIND_PROPERTY,
            $param->var->name,
            self::classifyTypeNode($param->type),
            $param->getStartLine(),
        );
    }

    /**
     * @throws RuntimeException
     */
    private static function processAssign(Assign $assign, Scope $scope): ?IdentifierRuleError
    {
        if (!$assign->var instanceof Variable
            || !is_string($assign->var->name)
            || $scope->hasVariableType($assign->var->name)->yes()) {
            return null;
        }

        $trinaryLogic = $scope->getType($assign->expr)->isBoolean();

        $type = match (true) {
            $trinaryLogic->yes() => self::TYPE_BOOL,
            $trinaryLogic->no()  => self::TYPE_NON_BOOL,
            default              => self::TYPE_UNKNOWN,
        };

        return self::checkSymbol(self::KIND_VARIABLE, $assign->var->name, $type, $assign->getStartLine());
    }

    /**
     * @param self::KIND_* $kind
     * @param self::TYPE_* $type
     *
     * @throws RuntimeException
     */
    private static function checkSymbol(string $kind, string $name, string $type, int $line): ?IdentifierRuleError
    {
        if ($type === self::TYPE_BOOL) {
            return self::isBoolishName($name, $kind)
                ? null
                : self::buildMissingPrefixError($kind, $name, $line);
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
        return Str::toLowerCase(Str::match($name, '/^(?:[a-z0-9]+|[A-Z0-9]+)/')[0] ?? '');
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
    private static function classifyTypeNode(?Node $node): string
    {
        return match (true) {
            !$node instanceof Node        => self::TYPE_UNKNOWN,
            $node instanceof NullableType => self::classifyTypeNode($node->type),
            $node instanceof Identifier   => self::isBoolName($node) ? self::TYPE_BOOL : self::TYPE_NON_BOOL,
            $node instanceof UnionType    => self::classifyUnionType($node),
            default                       => self::TYPE_NON_BOOL,
        };
    }

    /**
     * @return self::TYPE_*
     */
    private static function classifyUnionType(UnionType $unionType): string
    {
        $hasBool  = array_any($unionType->types, static fn (Node $member): bool => self::isBoolName($member));
        $hasOther = array_any(
            $unionType->types,
            static fn (Node $member): bool => !self::isBoolName($member) && !self::isNullName($member),
        );

        return match (true) {
            $hasBool && !$hasOther => self::TYPE_BOOL,
            $hasBool               => self::TYPE_UNKNOWN,
            default                => self::TYPE_NON_BOOL,
        };
    }

    /**
     * @return self::TYPE_*
     */
    private static function classifyConstValue(Const_ $const): string
    {
        return match (true) {
            $const->value instanceof ConstFetch
                && in_array(Str::toLowerCase($const->value->name->toString()), ['true', 'false'], true) => self::TYPE_BOOL,
            $const->value instanceof Scalar || $const->value instanceof Array_                          => self::TYPE_NON_BOOL,
            default                                                                                     => self::TYPE_UNKNOWN,
        };
    }

    private static function isBoolName(Node $node): bool
    {
        return $node instanceof Identifier
            && in_array(Str::toLowerCase($node->name), ['bool', 'true', 'false'], true);
    }

    private static function isNullName(Node $node): bool
    {
        return $node instanceof Identifier
            && Str::toLowerCase($node->name) === 'null';
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
     *
     * @throws RuntimeException
     */
    private static function buildMissingPrefixError(string $kind, string $name, int $line): IdentifierRuleError
    {
        return self::buildRuleError(sprintf(
            '%s name `%s` must have one of the following prefixes: %s.',
            $kind,
            $name,
            implode(', ', self::getPrefixesForKind($kind)),
        ), $line);
    }

    /**
     * @param self::KIND_* $kind
     *
     * @throws RuntimeException
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
