<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\PublicApiDocumentation;

/**
 * Abstract class description.
 *
 * @api
 */
abstract class AbstractWithUnimplementedMethod
{
    /**
     * Abstract method, has description and `@param` prose. No `@example` required.
     *
     * @param non-empty-string $arg The argument
     */
    abstract public function abstractMethod(string $arg): void;
}
