<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * @no-named-arguments
 */
final class FileLevelApiMissingDescription
{
    public function method(): void {}
}
