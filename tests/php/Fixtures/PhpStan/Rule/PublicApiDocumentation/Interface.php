<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\PublicApiDocumentation;

/**
 * Interface description — `@example` not required on interface methods.
 *
 * @api
 */
interface InterfaceWithMethod
{
    /**
     * Interface method description, with `@param` prose.
     *
     * @param non-empty-string $arg The argument
     */
    public function interfaceMethod(string $arg): void;
}
