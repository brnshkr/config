<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\Plugin;
use Composer\Composer;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use LogicException;
use Override;
use Symfony\Component\Console\Command\Command;

use function array_values;

/**
 * @internal Brnshkr\Config\Composer
 */
final class CommandProvider implements BaseCommandProvider
{
    /**
     * @throws LogicException
     */
    #[Override]
    public function getCommands(): array
    {
        return [
            new BrnshkrConfigCommand(),
            new ExtractPharCommand(),
            new SetupCommand(),
            new UpdatePhpExtensionsCommand(),
        ];
    }

    /**
     * @return list<Command>
     */
    public static function getCommandInstances(Composer $composer): array
    {
        /**
         * @var ?BaseCommandProvider $provider
         */
        $provider = $composer->getPluginManager()->getPluginCapability(new Plugin(), BaseCommandProvider::class);

        return array_values($provider?->getCommands() ?? []);
    }
}
