<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Trait;

use Brnshkr\Config\ComposerJson;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @phpstan-require-extends TestCase
 */
trait PackageNameTrait
{
    private static function getPackageFullName(): string
    {
        /** @disregard P1013 \@phpstan-require-extends is not recognized by intelephense (See: https://github.com/bmewburn/vscode-intelephense/issues/3256) */
        return ComposerJson::forThisLibrary()->getPackageFullName()
            ?? self::fail('Package name not found in "composer.json".');
    }
}
