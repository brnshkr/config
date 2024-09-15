<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Module;
use Composer\Composer;
use Composer\Factory;
use Composer\Installer as ComposerInstaller;
use Composer\Json\JsonManipulator;
use Composer\Package\PackageInterface;
use Composer\Semver\VersionParser;
use Exception;
use RuntimeException;

use function array_filter;
use function array_map;
use function array_values;
use function is_string;
use function sprintf;
use function Symfony\Component\String\s;
use function usort;
use function version_compare;

/**
 * @internal Brnshkr\Config\Composer
 */
final readonly class Installer
{
    private const int STABILTY_RANK_STABLE = 4;

    private const array STABILITY_RANKS = [
        'dev'    => 0,
        'alpha'  => 1,
        'a'      => 1,
        'beta'   => 2,
        'b'      => 2,
        'RC'     => 3,
        'rc'     => 3,
        'stable' => self::STABILTY_RANK_STABLE,
    ];

    public function __construct(
        private Composer $composer,
        private Console $console,
        private ComposerJson $libraryComposerJson,
        private ComposerJsonManipulator $projectComposerJsonManipulator,
    ) {}

    /**
     * @phpstan-param list<Module::PACKAGE_*> $packages
     *
     * @phpstan-return ComposerInstaller::ERROR_*
     *
     * @throws Exception
     * @throws RuntimeException
     */
    public function install(array $packages, bool $doInstallExactVersions): int
    {
        $installablePackages = $this->getInstallablePackages($packages);

        if ($installablePackages === []) {
            return ComposerInstaller::ERROR_NONE;
        }

        $this->projectComposerJsonManipulator->manipulate(function (JsonManipulator $jsonManipulator) use (
            $installablePackages,
            $doInstallExactVersions,
        ): void {
            foreach ($installablePackages as $installablePackage) {
                $jsonManipulator->addLink(
                    type: 'require-dev',
                    package: $installablePackage->getName(),
                    constraint: ($doInstallExactVersions ? '' : '^')
                        . s($installablePackage->getPrettyVersion())->trimStart('v')->toString(),
                    sortPackages: $this->composer->getConfig()->get('sort-packages'),
                );
            }
        });

        $config = $this->composer->getConfig();

        return ComposerInstaller::create($this->console->io, Factory::create($this->console->io))
            ->setUpdate(true)
            ->setDevMode(true)
            ->setDumpAutoloader(true)
            ->setOptimizeAutoloader($config->get('optimize-autoloader'))
            ->setPreferStable((bool) $config->get('prefer-stable'))
            ->setPreferDist($config->get('preferred-install') === 'dist')
            ->setPreferSource($config->get('preferred-install') === 'source')
            ->run()
        ;
    }

    /**
     * @phpstan-param list<Module::PACKAGE_*> $packages
     *
     * @return list<PackageInterface>
     *
     * @throws RuntimeException
     */
    private function getInstallablePackages(array $packages): array
    {
        $versionConstraints = $this->libraryComposerJson->getVersionConstraintsOfOptionalPackages();

        return array_values(array_filter(
            array_map(function (string $package) use ($versionConstraints): ?PackageInterface {
                $versionConstraint = $versionConstraints[$package] ?? throw new RuntimeException(sprintf('No version constraint given for package "%s".', $package));
                $packageToInstall  = $this->findPackage($package, $versionConstraint);

                if (!$packageToInstall instanceof PackageInterface) {
                    $this->console->writeError(sprintf(
                        'Package <info>%s</info> not found.',
                        $package,
                    ));

                    return null;
                }

                $this->console->writeInfo(sprintf(
                    'Added package "%s" to the list of packages to install.',
                    $package,
                ));

                return $packageToInstall;
            }, $packages),
            static fn (?PackageInterface $package): bool => $package instanceof PackageInterface,
        ));
    }

    /**
     * @throws RuntimeException
     */
    private function findPackage(string $package, string $versionConstraint): ?PackageInterface
    {
        $minimumStability     = $this->composer->getConfig()->get('minimum-stability');
        $minimumStabilityRank = is_string($minimumStability) ? self::getStabilityRank($minimumStability) : self::STABILTY_RANK_STABLE;

        $packagesToInstall = array_filter(
            $this->composer->getRepositoryManager()->findPackages($package, $versionConstraint),
            static fn (
                PackageInterface $package,
            ): bool => self::getStabilityRank(VersionParser::parseStability($package->getVersion())) >= $minimumStabilityRank,
        );

        usort($packagesToInstall, static function (PackageInterface $package1, PackageInterface $package2): int {
            $stability1 = self::getStabilityRank(VersionParser::parseStability($package1->getVersion()));
            $stability2 = self::getStabilityRank(VersionParser::parseStability($package2->getVersion()));

            return $stability1 === $stability2
                ? version_compare($package2->getVersion(), $package1->getVersion())
                : $stability1 <=> $stability2;
        });

        return $packagesToInstall[0] ?? null;
    }

    private static function getStabilityRank(string $stability): int
    {
        return self::STABILITY_RANKS[$stability] ?? self::STABILTY_RANK_STABLE;
    }
}
