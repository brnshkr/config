<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\Trait\RuleTrait;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\DevFunctionUsageRuleTest;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function array_any;
use function sprintf;

/**
 * Reports production code calling a function only a development-only package declares.
 *
 * A package that autoloads functions through `autoload.files` adds them to the global namespace, so the
 * namespace-based architecture rules cannot forbid them. This rule compares where the called function
 * is declared instead: a `dd()` from `symfony/var-dumper` passes in the tests and breaks in production.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @implements Rule<FuncCall>
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/DevFunctionUsageRule.md
 * @see DevFunctionUsageRuleTest
 *
 * @example
 * ```php
 * PhpStan::getBuilder()
 *     ->replaceRule(DevFunctionUsageRule::class, [
 *         'developmentPackageDirectories' => ['symfony/var-dumper' => __DIR__ . '/vendor/symfony/var-dumper'],
 *         'developmentDirectories'        => [__DIR__ . '/tests'],
 *     ])
 * ;
 * ```
 */
final readonly class DevFunctionUsageRule implements Rule
{
    use RuleTrait;

    /**
     * @internal invoked by PHPStan
     *
     * @param ReflectionProvider $reflectionProvider - PHPStan reflection provider (auto-wired)
     * @param array<string, string> $developmentPackageDirectories - where each development-only package lies, keyed by its name
     * @param list<string> $developmentDirectories - directories whose code may call them
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $developmentPackageDirectories = [],
        private array $developmentDirectories = [],
    ) {}

    /**
     * @internal invoked by PHPStan
     */
    #[Override]
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @internal invoked by PHPStan
     *
     * @return list<IdentifierRuleError>
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name || self::isWithinAny($scope->getFile(), $this->developmentDirectories)) {
            return [];
        }

        if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [];
        }

        $functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
        $fileName           = $functionReflection->getFileName() ?? '';

        foreach ($this->developmentPackageDirectories as $package => $directory) {
            if (self::isWithinAny($fileName, [$directory])) {
                return [self::buildRuleError(sprintf(
                    'Function `%s()` comes from development-only package `%s` and must not be called from production code.',
                    $functionReflection->getName(),
                    $package,
                ), $node->getStartLine())];
            }
        }

        return [];
    }

    /**
     * @param list<string> $directories
     */
    private static function isWithinAny(string $filePath, array $directories): bool
    {
        return array_any($directories, static fn (string $directory): bool => Str::startsWith($filePath, $directory . '/'));
    }
}
