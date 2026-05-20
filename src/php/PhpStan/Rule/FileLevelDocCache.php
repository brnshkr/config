<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use PhpParser\Comment\Doc;
use PhpParser\Node;

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
        if (array_key_exists($filePath, self::$cache)) {
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

        self::$cache[$filePath] = null;
    }

    public static function clear(): void
    {
        self::$cache = [];
    }
}
