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
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Script\ScriptEvents;
use DateTimeImmutable;
use Override;
use RuntimeException;

use function array_any;
use function array_filter;
use function array_values;
use function in_array;
use function is_array;
use function sprintf;

/**
 * @internal Brnshkr\Config\Composer
 */
final class Plugin implements Capable, EventSubscriberInterface, PluginInterface
{
    private const array LOCK_REFRESH_PACKAGES = [
        'lock',
        'mirrors',
        'nothing',
    ];

    private bool $isLockRefresh = false;

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
            PluginEvents::COMMAND          => 'onCommand',
            PluginEvents::PRE_POOL_CREATE  => 'onPrePoolCreate',
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstall',
        ];
    }

    public function onCommand(CommandEvent $commandEvent): void
    {
        if ($commandEvent->getCommandName() !== 'update') {
            return;
        }

        $input    = $commandEvent->getInput();
        $packages = $input->getArgument('packages');

        $this->isLockRefresh = $input->getOption('lock') === true
            || (is_array($packages) && array_any(
                $packages,
                static fn (mixed $package): bool => in_array($package, self::LOCK_REFRESH_PACKAGES, true),
            ));
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
        if ($this->isLockRefresh) {
            return;
        }

        $releaseAge = ReleaseAge::fromExtra(
            $this->composer->getPackage()->getExtra(),
            $this->libraryComposerJson->getPackageOrganization(),
            $this->libraryComposerJson->getPackageName(),
        );

        $request  = $prePoolCreateEvent->getRequest();
        $packages = $prePoolCreateEvent->getPackages();

        $unpinnedPackages = array_filter(
            $packages,
            static fn (BasePackage $basePackage): bool => !$request->isFixedPackage($basePackage)
                && !$request->isLockedPackage($basePackage),
        );

        $heldBackPackages  = $releaseAge->getHeldBackPackages($unpinnedPackages, new DateTimeImmutable());
        $uncheckedPackages = $releaseAge->getPackagesWithoutReleaseTime($unpinnedPackages);

        $this->reportPackages('Held back as younger than the minimum release age:', $heldBackPackages);
        $this->reportPackages('Taken unchecked, as they have no release time:', $uncheckedPackages);

        $prePoolCreateEvent->setPackages(array_values(array_filter(
            $packages,
            static fn (BasePackage $basePackage): bool => !in_array($basePackage, $heldBackPackages, true),
        )));
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
