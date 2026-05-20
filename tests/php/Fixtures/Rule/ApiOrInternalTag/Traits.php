<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\Rule\ApiOrInternalTag;

/**
 * @api
 */
trait TraitWithApiTag {}

/**
 * @internal
 */
trait TraitWithInternalTag {}

trait TraitWithoutTag {}
