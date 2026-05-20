<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\PublicApiDocumentation;

/**
 * Function description.
 *
 * @api
 *
 * @example
 * ```php
 * publicApiDocsGoodFunction('x');
 * ```
 *
 * @param non-empty-string $arg The argument
 */
function publicApiDocsGoodFunction(string $arg): void {}

/**
 * @api
 */
function publicApiDocsMissingDescriptionFunction(): void {}
