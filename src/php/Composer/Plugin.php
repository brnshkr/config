<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\ComposerJson;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Script\ScriptEvents;
use DateTimeImmutable;
use Override;
use RuntimeException;

use function sprintf;

/**
 * @internal Brnshkr\Config\Composer
 */
final class Plugin implements Capable, EventSubscriberInterface, PluginInterface
{
    private Composer $composer;

    private Console $console;

    private ComposerJson $libraryComposerJson;

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer            = $composer;
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
            PluginEvents::PRE_POOL_CREATE  => 'onPrePoolCreate',
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

    /**
     * @throws RuntimeException
     */
    public function onPrePoolCreate(PrePoolCreateEvent $prePoolCreateEvent): void
    {
        $releaseAge = ReleaseAge::fromExtra(
            $this->composer->getPackage()->getExtra(),
            $this->libraryComposerJson->getPackageOrganization(),
            $this->libraryComposerJson->getPackageName(),
        );

        $packages = $prePoolCreateEvent->getPackages();
        $now      = new DateTimeImmutable();

        $this->reportPackages('Held back as younger than the minimum release age:', $releaseAge->getHeldBackPackages($packages, $now));
        $this->reportPackages('Taken unchecked, as they have no release time:', $releaseAge->getPackagesWithoutReleaseTime($packages));

        $prePoolCreateEvent->setPackages($releaseAge->getAcceptedPackages($packages, $now));
    }

    /**
     * @param list<BasePackage> $packages
     *
     * @throws RuntimeException
     */
    private function reportPackages(string $heading, array $packages): void
    {
        if ($packages === []) {
            return;
        }

        $this->console->writeInfo($heading);

        foreach ($packages as $package) {
            $this->console->writeInfo(sprintf('  %s %s', $package->getPrettyName(), $package->getPrettyVersion()));
        }
    }
}
