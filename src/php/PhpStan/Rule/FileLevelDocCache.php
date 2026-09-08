<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;

use function array_key_exists;
use function is_array;

/**
 * @internal
 */
final class FileLevelDocCache
{
    /**
     * @var array<non-empty-string, ?Doc>
     */
    private static array $cache = [];

    /**
     * @param non-empty-string $filePath
     */
    public static function get(string $filePath): ?Doc
    {
        return self::$cache[$filePath] ?? null;
    }

    /**
     * @param non-empty-string $filePath
     */
    public static function captureFrom(Node $candidate, string $filePath): void
    {
        if (array_key_exists($filePath, self::$cache) || !self::canCarryFileLevelDoc($candidate)) {
            return;
        }

        $comments = $candidate->getAttribute('comments') ?? [];
        $comments = is_array($comments) ? $comments : [];

        foreach ($comments as $comment) {
            if ($comment instanceof Doc) {
                self::$cache[$filePath] = $comment;

                return;
            }
        }
    }

    public static function clear(): void
    {
        self::$cache = [];
    }

    private static function canCarryFileLevelDoc(Node $node): bool
    {
        return $node instanceof Declare_
            || $node instanceof Namespace_
            || $node instanceof Use_
            || $node instanceof GroupUse
            || $node instanceof Return_;
    }
}
