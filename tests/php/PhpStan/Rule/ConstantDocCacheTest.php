<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\ConstantDocCache;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConstantDocCache::class)]
#[UsesClass(Str::class)]
final class ConstantDocCacheTest extends TestCase
{
    private const string INTERNAL_DOCBLOCK = "/**\n * @internal\n */";

    public function testAPathThatCannotBeReadDocumentsNoConstant(): void
    {
        self::assertNull(ConstantDocCache::get(__DIR__, 'ACME_CONSTANT'));
        self::assertNull(ConstantDocCache::get('1', 'ACME_CONSTANT'));
    }

    public function testADocblockIsFoundAboveConstAndDefineAlike(): void
    {
        $filePath = __DIR__ . '/../../Fixtures/PhpStan/Rule/Internal/InternalConstants.php';
        $prefix   = 'Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\\';

        self::assertSame(self::INTERNAL_DOCBLOCK, ConstantDocCache::get($filePath, $prefix . 'INTERNAL_CONSTANT'));
        self::assertSame(self::INTERNAL_DOCBLOCK, ConstantDocCache::get($filePath, $prefix . 'DEFINED_INTERNAL_CONSTANT'));
        self::assertNull(ConstantDocCache::get($filePath, $prefix . 'PUBLIC_CONSTANT'));
        self::assertNull(ConstantDocCache::get($filePath, 'precision'));
    }
}
