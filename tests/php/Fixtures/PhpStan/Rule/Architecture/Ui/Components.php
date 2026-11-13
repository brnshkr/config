<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\Ui;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @internal
 *
 * Fails — a component must live in the components folder
 */
#[AsTwigComponent]
final class EmailBadge {}
