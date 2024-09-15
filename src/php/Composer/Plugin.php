<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\ComposerJson;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
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
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
        ];
    }

    /**
     * @throws RuntimeException
     */
    public function onPostPackageInstall(PackageEvent $packageEvent): void
    {
        foreach ($packageEvent->getOperations() as $operation) {
            if (!$operation instanceof InstallOperation) {
                continue;
            }

            if ($this->libraryComposerJson->getPackageFullName() === $operation->getPackage()->getName()) {
                $this->console->writeNotice('Composer plugin activated.');

                break;
            }
        }
    }
}
