<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @internal
 *
 * Passes — a component in place
 */
#[AsTwigComponent]
final class UserCard {}

/**
 * @internal
 *
 * Passes — a live component in place
 */
#[AsLiveComponent]
final class EmailCounter {}

/**
 * @internal
 *
 * Passes — an abstract base needs no attribute
 */
abstract class AbstractUserComponent {}

/**
 * @internal
 *
 * Fails — a class in the components folder must be a component
 */
final class UserFormatter {}
