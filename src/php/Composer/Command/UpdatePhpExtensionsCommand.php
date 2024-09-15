<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\ComposerJsonManipulator;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use Composer\Json\JsonManipulator;
use Composer\Package\BasePackage;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Repository\PlatformRepository;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use ValueError;

use function array_combine;
use function array_filter;
use function array_find;
use function array_keys;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal Brnshkr\Config\Composer
 */
final class UpdatePhpExtensionsCommand extends AbstractCommand
{
    private const string UNUSED_EXTENSION_MESSAGE_TEMPLATE = 'Extension%s %s %s not needed by any of your %srequirements%s. Please check if your code actively depends on %s. To suppress this warning, re-run this command with the <fg=cyan>--allow%s</fg=cyan> option.';

    private const string SECTION_REQUIRE     = 'require';
    private const string SECTION_REQUIRE_DEV = 'require-dev';

    private ComposerJson $projectComposerJson;

    private ComposerJsonManipulator $projectComposerJsonManipulator;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Updates required PHP extensions in composer.json based on installed vendor files';
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedInitialize(): void
    {
        $this->projectComposerJson            = ComposerJson::forProjectUsingThisLibrary();
        $this->projectComposerJsonManipulator = ComposerJsonManipulator::forComposerJson($this->projectComposerJson);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function wrappedConfigure(): void
    {
        $this
            ->addOption('allow', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of package names to allow as unused')
            ->addOption('allow-dev', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of dev package names to allow as unused')
        ;
    }

    /**
     * @phpstan-return self::SUCCESS
     *
     * @throws RuntimeException
     * @throws ValueError
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $this->console->writeNotice('Updating required PHP extensions.');

        $currentMainRequires = array_values(array_filter(array_keys($this->projectComposerJson->getRequires()), self::isPhpExtension(...)));
        $currentMainRequires = array_combine($currentMainRequires, $currentMainRequires);
        $unusedMainRequires  = $currentMainRequires;

        $currentDevRequires = array_values(array_filter(array_keys($this->projectComposerJson->getDevRequires()), self::isPhpExtension(...)));
        $currentDevRequires = array_combine($currentDevRequires, $currentDevRequires);
        $unusedDevRequires  = $currentDevRequires;

        $this->projectComposerJsonManipulator->manipulate(function (JsonManipulator $jsonManipulator) use (
            $currentMainRequires,
            $currentDevRequires,
            &$unusedMainRequires,
            &$unusedDevRequires,
        ): void {
            $doSortPackages       = $this->composer->getConfig()->get('sort-packages');
            $rootPackage          = $this->composer->getPackage();
            $relevantMainRequires = $this->getRelevantRequires($rootPackage->getRequires());
            $relevantDevRequires  = $this->getRelevantRequires($rootPackage->getDevRequires());

            foreach (array_keys($relevantMainRequires) as $relevantMainRequire) {
                unset($unusedMainRequires[$relevantMainRequire], $relevantDevRequires[$relevantMainRequire]);

                if (isset($currentMainRequires[$relevantMainRequire])) {
                    $this->console->writeInfo(sprintf('Skipping "%s" — already present in composer.json (%s).', $relevantMainRequire, self::SECTION_REQUIRE));
                } else {
                    $this->console->writeNotice(sprintf('Adding "%s" to composer.json (%s).', $relevantMainRequire, self::SECTION_REQUIRE));
                    $jsonManipulator->addLink(self::SECTION_REQUIRE, $relevantMainRequire, '*', $doSortPackages);
                }
            }

            foreach (array_keys($relevantDevRequires) as $relevantDevRequire) {
                unset($unusedDevRequires[$relevantDevRequire]);

                if (isset($currentMainRequires[$relevantDevRequire])) {
                    unset($unusedMainRequires[$relevantDevRequire]);

                    if (isset($currentDevRequires[$relevantDevRequire])) {
                        $this->console->writeNotice(sprintf('Removing "%s" from composer.json (%s) because it is already listed under requirements.', $relevantDevRequire, self::SECTION_REQUIRE_DEV));
                        $jsonManipulator->removeSubNode(self::SECTION_REQUIRE_DEV, $relevantDevRequire);
                    } else {
                        $this->console->writeInfo(sprintf('Skipping "%s" — already listed under requirements.', $relevantDevRequire));
                    }
                } elseif (isset($currentDevRequires[$relevantDevRequire])) {
                    $this->console->writeInfo(sprintf('Skipping "%s" — already present in composer.json (%s).', $relevantDevRequire, self::SECTION_REQUIRE_DEV));
                } else {
                    $this->console->writeNotice(sprintf('Adding "%s" to composer.json (%s).', $relevantDevRequire, self::SECTION_REQUIRE_DEV));
                    $jsonManipulator->addLink(self::SECTION_REQUIRE_DEV, $relevantDevRequire, '*', $doSortPackages);
                }
            }

            $jsonManipulator->removeMainKeyIfEmpty(self::SECTION_REQUIRE);
            $jsonManipulator->removeMainKeyIfEmpty(self::SECTION_REQUIRE_DEV);
        });

        $allowedUnusedMainRequires = $this->input->getOption('allow') ?? [];
        $allowedUnusedMainRequires = is_array($allowedUnusedMainRequires) ? $allowedUnusedMainRequires : [];

        foreach ($unusedMainRequires as $key => $unusedMainRequire) {
            if (in_array($unusedMainRequire, $allowedUnusedMainRequires, true)) {
                unset($unusedMainRequires[$key]);
            }
        }

        $allowedUnusedDevRequires = $this->input->getOption('allow-dev') ?? [];
        $allowedUnusedDevRequires = is_array($allowedUnusedDevRequires) ? $allowedUnusedDevRequires : [];

        foreach ($unusedDevRequires as $key => $unusedDevRequire) {
            if (in_array($unusedDevRequire, $allowedUnusedDevRequires, true)) {
                unset($unusedDevRequires[$key]);
            }
        }

        $this->warnUnusedPackages($unusedMainRequires, $unusedDevRequires);

        return self::SUCCESS;
    }

    /**
     * @param array<string, Link> $packages
     *
     * @return array<string, true>
     *
     * @throws RuntimeException
     */
    private function getRelevantRequires(array $packages): array
    {
        $installedRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $allPackages         = $installedRepository->getPackages();
        $relevantRequires    = [];
        $visitedPackageNames = [];

        $findPackage = static fn (string $packageName): ?BasePackage => array_find(
            $allPackages,
            static fn (PackageInterface $package): bool => $package->getName() === $packageName,
        );

        /**
         * @param list<string> $packageNameTrail
         */
        $walkPackages = function (?PackageInterface $package, array $packageNameTrail = []) use (
            &$walkPackages,
            &$findPackage,
            &$visitedPackageNames,
            &$relevantRequires,
        ): void {
            $packageName = $package?->getName();

            if (!((bool) $packageName) || isset($visitedPackageNames[$packageName])) {
                return;
            }

            $packageNameTrail[]                = $packageName;
            $visitedPackageNames[$packageName] = true;

            foreach (array_keys($package->getRequires()) as $childPackageName) {
                if (self::isPhpExtension($childPackageName) && !isset($relevantRequires[$childPackageName])) {
                    $this->console->writeDebug(implode(' —> ', [...$packageNameTrail, '<fg=cyan>' . $childPackageName . '</>']));

                    $relevantRequires[$childPackageName] = true;
                }

                $walkPackages($findPackage($childPackageName), $packageNameTrail);
            }
        };

        foreach (array_keys($packages) as $packageName) {
            $walkPackages($findPackage($packageName));
        }

        return $relevantRequires;
    }

    /**
     * @param array<string, string> $potentiallyUnusedMainRequires
     * @param array<string, string> $potentiallyUnusedDevRequires
     *
     * @throws RuntimeException
     */
    private function warnUnusedPackages(array $potentiallyUnusedMainRequires, array $potentiallyUnusedDevRequires): void
    {
        $potentiallyUnusedMainRequiresCount = count($potentiallyUnusedMainRequires);
        $potentiallyUnusedDevRequiresCount  = count($potentiallyUnusedDevRequires);

        if ($potentiallyUnusedMainRequiresCount > 0) {
            $this->console->writeWarning(sprintf(
                self::UNUSED_EXTENSION_MESSAGE_TEMPLATE,
                $potentiallyUnusedMainRequiresCount > 1 ? 's' : '',
                Str::joinAsQuotedList(array_keys($potentiallyUnusedMainRequires)),
                $potentiallyUnusedMainRequiresCount > 1 ? 'are' : 'is',
                '',
                '',
                $potentiallyUnusedMainRequiresCount > 1 ? 'them' : 'it',
                '',
            ));
        }

        if ($potentiallyUnusedDevRequiresCount > 0) {
            $this->console->writeWarning(sprintf(
                self::UNUSED_EXTENSION_MESSAGE_TEMPLATE,
                $potentiallyUnusedDevRequiresCount > 1 ? 's' : '',
                Str::joinAsQuotedList(array_keys($potentiallyUnusedDevRequires)),
                $potentiallyUnusedDevRequiresCount > 1 ? 'are' : 'is',
                'dev-',
                ' or ' . ($potentiallyUnusedDevRequiresCount > 1 ? 'are' : 'is') . ' already present in your requirements',
                $potentiallyUnusedDevRequiresCount > 1 ? 'them' : 'it',
                '-dev',
            ));
        }
    }

    private static function isPhpExtension(string $package): bool
    {
        return PlatformRepository::isPlatformPackage($package) && s($package)->startsWith('ext-');
    }
}
