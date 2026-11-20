<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProjectDirectory::class)]
#[UsesClass(Str::class)]
final class ProjectDirectoryTest extends TestCase
{
    #[After]
    public function removeTheCacheDirectoryVariable(): void
    {
        unset($_SERVER['BRNSHKR_CACHE_DIR']);
    }

    public function testTheOwnCacheFollowsTheCacheDirectory(): void
    {
        $_SERVER['BRNSHKR_CACHE_DIR'] = './acme-cache';

        self::assertSame('acme-cache/brnshkr.cache', ProjectDirectory::getOwnCache());
    }
}
