<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\ThrowTypeExtension;

use Brnshkr\Config\FileFinder;
use InvalidArgumentException;
use Override;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function array_any;
use function array_map;
use function array_merge;
use function count;
use function in_array;

/**
 * Drops `InvalidArgumentException` from the inferred throw-type of {@see FileFinder::get()} and
 * {@see FileFinder::getFilePaths()} when every passed extension is one of
 * {@see FileFinder::EXTENSIONS}.
 *
 * The exception is only ever thrown for unsupported extensions, so call sites that pass a
 * supported literal (e.g. `EXTENSION_PHP` or `EXTENSION_TWIG`) do not need to carry it in their
 * own `@throws` signatures.
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class GetConfigThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->getName() === FileFinder::class
            && ($methodReflection->getName() === 'get' || $methodReflection->getName() === 'getFilePaths');
    }

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $staticCall,
        Scope $scope,
    ): ?Type {
        $arguments          = $staticCall->getArgs();
        $extensionsArgument = count($arguments) < 2 || !isset($arguments[1]) ? null : $arguments[1];

        if ($extensionsArgument === null) {
            return $methodReflection->getThrowType()?->equals(new ObjectType(InvalidArgumentException::class)) === true
                ? null
                : $methodReflection->getThrowType();
        }

        $type = $scope->getType($extensionsArgument->value);

        $values = array_map(
            static fn (ConstantStringType $constantStringType): string => $constantStringType->getValue(),
            $type->getConstantStrings(),
        );

        foreach ($type->getConstantArrays() as $constantArrayType) {
            foreach ($constantArrayType->getValueTypes() as $valueType) {
                $values = array_merge($values, array_map(
                    static fn (ConstantStringType $constantStringType): string => $constantStringType->getValue(),
                    $valueType->getConstantStrings(),
                ));
            }
        }

        return array_any(
            $values,
            static fn (mixed $value): bool => !in_array($value, FileFinder::EXTENSIONS, true),
        )
            ? new ObjectType(InvalidArgumentException::class)
            : null;
    }
}
