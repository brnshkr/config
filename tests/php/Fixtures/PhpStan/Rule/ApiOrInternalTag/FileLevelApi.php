<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ApiOrInternalTag;

final class InheritsFileLevelApi {}

function inheritsFileLevelApiFunction(): void {}

const INHERITS_FILE_LEVEL_API = 'value';
