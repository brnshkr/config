<?php

/**
 * @internal
 */

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\ApiOrInternalTag;

final class InheritsFileLevelInternal {}

function inheritsFileLevelInternalFunction(): void {}

const INHERITS_FILE_LEVEL_INTERNAL = 'value';
