<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\DependencyInjection;

use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * @internal
 *
 * Passes — an abstract base cannot be final
 */
abstract class AbstractUserExtension extends Extension {}

/**
 * @internal
 *
 * Fails — a concrete extension must be final
 */
class UserExtension extends AbstractUserExtension
{
    /**
     * @param array<mixed> $configs
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void {}
}
