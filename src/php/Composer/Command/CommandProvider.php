<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use LogicException;
use Override;

/**
 * @internal Brnshkr\Config\Composer
 */
final class CommandProvider implements BaseCommandProvider
{
    /**
     * @return list<BaseCommand>
     *
     * @throws LogicException
     */
    #[Override]
    public function getCommands(): array
    {
        return [
            new BrnshkrConfigCommand(),
            new ExtractPharCommand(),
            new PrintModuleConfigCommand(),
            new SetupCommand(),
            new UpdatePhpExtensionsCommand(),
        ];
    }
}
