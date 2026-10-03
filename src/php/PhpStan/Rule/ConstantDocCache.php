<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\ConstantDocCacheTest;
use LogicException;
use Override;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RuntimeException;
use SplFileObject;

use function max;

/**
 * @internal
 *
 * @see ConstantDocCacheTest
 */
final class ConstantDocCache
{
    /**
     * @var array<string, array<string, ?string>>
     */
    private static array $cache = [];

    public static function get(string $filePath, string $constantName): ?string
    {
        if (!Str::isNonDecimalIntString($filePath)) {
            return null;
        }

        self::$cache[$filePath] ??= self::readDocComments($filePath);

        return self::$cache[$filePath][$constantName] ?? null;
    }

    /**
     * @return array<string, ?string>
     */
    private static function readDocComments(string $filePath): array
    {
        try {
            $file       = new SplFileObject($filePath);
            $statements = new ParserFactory()->createForHostVersion()->parse($file->fread(max(1, $file->getSize())) ?: '') ?? [];
        } catch (Error|LogicException|RuntimeException) {
            return [];
        }

        $visitor = new class extends NodeVisitorAbstract {
            /**
             * @var array<string, ?string>
             */
            public array $docComments = [];

            #[Override]
            public function enterNode(Node $node): null
            {
                if ($node instanceof Const_) {
                    foreach ($node->consts as $const) {
                        $this->remember(($const->namespacedName ?? $const->name)->toString(), $node);
                    }

                    return null;
                }

                $definedName = $node instanceof Expression ? self::getDefinedName($node->expr) : null;

                if ($definedName !== null) {
                    $this->remember($definedName, $node);
                }

                return null;
            }

            private function remember(string $constantName, Node $declaration): void
            {
                if (Str::isNonDecimalIntString($constantName)) {
                    $this->docComments[$constantName] = $declaration->getDocComment()?->getText();
                }
            }

            private static function getDefinedName(Node $expr): ?string
            {
                if (!$expr instanceof FuncCall || !$expr->name instanceof Name || $expr->name->toLowerString() !== 'define') {
                    return null;
                }

                $firstArgument = $expr->args[0] ?? null;

                return $firstArgument instanceof Arg && $firstArgument->value instanceof String_
                    ? Str::trim($firstArgument->value->value, '\\')
                    : null;
            }
        };

        new NodeTraverser(new NameResolver(), $visitor)->traverse($statements);

        return $visitor->docComments;
    }
}
