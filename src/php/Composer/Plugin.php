<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\ComposerJson;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Override;
use RuntimeException;

/**
 * @internal Brnshkr\Config\Composer
 */
final class Plugin implements Capable, EventSubscriberInterface, PluginInterface
{
    private Console $console;

    private ComposerJson $libraryComposerJson;

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->libraryComposerJson = ComposerJson::forThisLibrary();
        $this->console             = new Console($io, $this->libraryComposerJson);
    }

    #[Override]
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    #[Override]
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    #[Override]
    public function getCapabilities(): array
    {
        return [
            BaseCommandProvider::class => CommandProvider::class,
        ];
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
        ];
    }

    /**
     * @throws RuntimeException
     */
    public function onPostInstall(): void
    {
        $this->console->writeNotice('Composer plugin activated.');
    }
}
