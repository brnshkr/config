<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use LogicException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Descriptor\TextDescriptor;

/**
 * @internal Brnshkr\Config\Composer
 */
final class BrnshkrConfigCommand extends AbstractCommand
{
    // NOTICE: We use the internal TextDescriptor here since we want the exact same formatting as the default symfony/console output.
    // This avoids code duplication and keeps the output consistent. Potential future BC breaks in symfony/console might need to be addressed.
    private TextDescriptor $textDescriptor;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Displays {{ package_full_name }} composer plugin overview';
    }

    #[Override]
    protected function wrappedInitialize(): void
    {
        $this->textDescriptor = new TextDescriptor();
    }

    /**
     * @phpstan-return self::SUCCESS
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $this->console->writeLogo();
        $this->textDescriptor->describe($this->output, $this);
        $this->console->writeRaw('');
        $this->console->writeHelp(CommandProvider::getCommandInstances($this->composer));

        return self::SUCCESS;
    }
}
