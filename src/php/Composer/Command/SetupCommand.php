<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\ComposerJsonManipulator;
use Brnshkr\Config\Composer\Installer;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\Str;
use Composer\Installer as ComposerInstaller;
use Exception;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function array_filter;
use function array_first;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function file_get_contents;
use function in_array;
use function is_file;
use function is_readable;
use function sprintf;

/**
 * @internal Brnshkr\Config\Composer
 */
final class SetupCommand extends AbstractCommand
{
    private const string ANSWER_ALL  = 'all';
    private const string ANSWER_NONE = 'none';

    private Installer $installer;

    private Filesystem $filesystem;

    private ComposerJson $projectComposerJson;

    private bool $doForceUpdate = false;

    private bool $doInstallExactVersions = false;

    private bool $doIncludeOptionalPackagesAutomatically = false;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Installs the packages the {{ package_full_name }} modules you pick need';
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedInitialize(): void
    {
        $this->filesystem          = new Filesystem();
        $this->projectComposerJson = ComposerJson::forProjectUsingThisLibrary();

        $this->installer = new Installer(
            $this->composer,
            $this->console,
            $this->libraryComposerJson,
            ComposerJsonManipulator::forComposerJson($this->projectComposerJson),
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedConfigure(): void
    {
        $this
            ->addArgument('modules', InputArgument::IS_ARRAY, 'The modules to install <fg=yellow>(' . Str::joinAsQuotedList(Module::values()) . ')</fg=yellow>')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Install all modules')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update to the latest package versions from ' . $this->libraryComposerJson->getPackageFullName())
            ->addOption('exact', 'e', InputOption::VALUE_NONE, 'Install exact versions of dependencies')
            ->addOption('optional', 'o', InputOption::VALUE_NONE, 'Automatically include all optional packages')
        ;
    }

    /**
     * @return self::SUCCESS|self::FAILURE
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $this->doForceUpdate                          = $this->isBoolOptionEnabled('force');
        $this->doInstallExactVersions                 = $this->isBoolOptionEnabled('exact');
        $this->doIncludeOptionalPackagesAutomatically = $this->isBoolOptionEnabled('optional');

        $modules             = $this->getStringListArgument('modules');
        $doInstallAllModules = $this->isBoolOptionEnabled('all');
        $modulesToInstall    = [];

        $this->console->writeLogo();

        if ($modules === []) {
            $modulesToInstall = $doInstallAllModules
                ? Module::cases()
                : Module::fromValues($this->getModuleNamesToInstall());
        } elseif ($doInstallAllModules) {
            throw new InvalidArgumentException('The <fg=cyan>--all</fg=cyan> option is not allowed when specifying modules via the arguments.');
        } else {
            foreach ($modules as $moduleName) {
                $module = Module::tryFrom($moduleName);

                if ($module === null) {
                    throw new InvalidArgumentException(sprintf(
                        'Unknown module "%s". Allowed modules are: %s.',
                        $moduleName,
                        Str::joinAsQuotedList(Module::values()),
                    ));
                }

                $modulesToInstall[] = $module;
            }
        }

        $composerJsonFileContent = $this->getFileContent($this->projectComposerJson->path);
        $lockFileContent         = $this->getFileContent($this->projectComposerJson->lockFilePath);

        $packagesToInstall = array_merge(...array_map(
            $this->getPackagesToInstall(...),
            $modulesToInstall,
        ));

        if ($packagesToInstall === []) {
            $this->console->writeNotice(sprintf(
                'All packages are already installed. Use the <fg=cyan>--force</fg=cyan> option to force an update to the latest package versions from %s.',
                $this->libraryComposerJson->getPackageFullName(),
            ));

            return self::SUCCESS;
        }

        $exitCode = null;

        try {
            $exitCode = $this->installer->install($packagesToInstall, $this->doInstallExactVersions);
        } catch (Exception $exception) {
            $this->console->writeError($exception);
        }

        if ($exitCode !== ComposerInstaller::ERROR_NONE) {
            if ($exitCode !== null) {
                $this->console->writeError(sprintf(
                    'Composer update failed with exit code %d (%s)',
                    $exitCode,
                    [
                        ComposerInstaller::ERROR_GENERIC_FAILURE                 => 'ERROR_GENERIC_FAILURE',
                        ComposerInstaller::ERROR_NO_LOCK_FILE_FOR_PARTIAL_UPDATE => 'ERROR_NO_LOCK_FILE_FOR_PARTIAL_UPDATE',
                        ComposerInstaller::ERROR_LOCK_FILE_INVALID               => 'ERROR_LOCK_FILE_INVALID',
                        ComposerInstaller::ERROR_DEPENDENCY_RESOLUTION_FAILED    => 'ERROR_DEPENDENCY_RESOLUTION_FAILED',
                        ComposerInstaller::ERROR_AUDIT_FAILED                    => 'ERROR_AUDIT_FAILED',
                        ComposerInstaller::ERROR_PSR_AUTOLOAD_VIOLATION          => 'ERROR_PSR_AUTOLOAD_VIOLATION',
                        ComposerInstaller::ERROR_TRANSPORT_EXCEPTION             => 'ERROR_TRANSPORT_EXCEPTION',
                        -1                                                       => 'ERROR_UNKNOWN',
                    ][$exitCode],
                ));
            }

            if ($composerJsonFileContent !== null) {
                $this->revertFileContent($this->projectComposerJson->path, $composerJsonFileContent);
            }

            if ($lockFileContent !== null) {
                $this->revertFileContent($this->projectComposerJson->lockFilePath, $lockFileContent);
            }

            return self::FAILURE;
        }

        $this->console->writeNotice('Setup process completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return list<value-of<Module>>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getModuleNamesToInstall(): array
    {
        $isAnswerValid        = false;
        $moduleNamesToInstall = [];

        while (!$isAnswerValid) {
            $moduleNamesToInstall = $this->console->select(
                question: 'Which modules would you like to install?',
                choices: [
                    ...Module::values(),
                    self::ANSWER_ALL,
                    self::ANSWER_NONE,
                ],
                default: self::ANSWER_ALL,
                isMultiselect: true,
            );

            $isAnswerValid = $this->isMultipleChoiceAnswerWithAllAndNoneValid($moduleNamesToInstall);
        }

        return array_values(array_filter(
            array_first($moduleNamesToInstall) === self::ANSWER_ALL ? Module::values() : $moduleNamesToInstall,
            $this->isNotAllOrNoneAnswer(...),
        ));
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getPackagesToInstall(Module $module): array
    {
        $packages = self::toPackageNames(array_filter(
            $module->getRequiredPackages(),
            fn (Package $package): bool => $this->doForceUpdate ? true : !$package->isInstalled(),
        ));

        $allOptionalPackages = $module->getOptionalPackages();

        if ($this->doForceUpdate && $this->doIncludeOptionalPackagesAutomatically) {
            $optionalPackagesToInstall = self::toPackageNames($allOptionalPackages);
        } elseif ($this->doForceUpdate) {
            $optionalPackagesToInstall = self::toPackageNames(array_filter(
                $allOptionalPackages,
                static fn (Package $package): bool => $package->isInstalled(),
            ));
        } elseif ($this->doIncludeOptionalPackagesAutomatically) {
            $optionalPackagesToInstall = self::toPackageNames(array_filter(
                $allOptionalPackages,
                static fn (Package $package): bool => !$package->isInstalled(),
            ));
        } else {
            $optionalPackagesToInstall = $this->promptForOptionalPackages(
                $module,
                self::toPackageNames(array_filter(
                    $allOptionalPackages,
                    static fn (Package $package): bool => !$package->isInstalled(),
                )),
            );
        }

        return [...$packages, ...$optionalPackagesToInstall];
    }

    /**
     * @param list<non-empty-string> $packages
     *
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function promptForOptionalPackages(Module $module, array $packages): array
    {
        $packageCount = count($packages);

        if ($packageCount === 0) {
            return [];
        }

        if ($packageCount === 1) {
            $optionalPackage = array_first($packages);

            $doInstallOptionalPackage = $this->console->isConfirmed(sprintf(
                'Install optional dependency "%s" for module "%s"?',
                $optionalPackage,
                $module->value,
            ));

            return $doInstallOptionalPackage ? [$optionalPackage] : [];
        }

        $isAnswerValid    = false;
        $selectedPackages = [];

        while (!$isAnswerValid) {
            $selectedPackages = $this->console->select(
                question: sprintf(
                    'Select optional dependencies to install for module "%s".',
                    $module->value,
                ),
                choices: [
                    ...$packages,
                    self::ANSWER_ALL,
                    self::ANSWER_NONE,
                ],
                default: self::ANSWER_ALL,
                isMultiselect: true,
            );

            $isAnswerValid = $this->isMultipleChoiceAnswerWithAllAndNoneValid($selectedPackages);
        }

        return array_values(array_filter(
            array_first($selectedPackages) === self::ANSWER_ALL ? $packages : $selectedPackages,
            $this->isNotAllOrNoneAnswer(...),
        ));
    }

    /**
     * @param array<array-key, Package> $packages
     *
     * @return list<non-empty-string>
     */
    private static function toPackageNames(array $packages): array
    {
        return array_values(array_map(static fn (Package $package): string => $package->value, $packages));
    }

    /**
     * @param non-empty-string $path
     *
     * @return ?non-empty-string
     *
     * @throws RuntimeException
     */
    private function getFileContent(string $path): ?string
    {
        return (is_file($path) && is_readable($path))
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using Filesystem::readFile since this method is newer and does no necessarily exist in the version bundled with composer)
            ? (file_get_contents($path) ?: null)
            : null;
    }

    /**
     * @param non-empty-string $path
     * @param non-empty-string $content
     *
     * @throws IOException
     * @throws RuntimeException
     */
    private function revertFileContent(string $path, string $content): void
    {
        $this->console->writeWarning(sprintf(
            'Reverting "%s" to its original content.',
            $path,
        ));

        $this->filesystem->dumpFile($path, $content);
    }

    /**
     * @param list<mixed> $answers
     *
     * @throws RuntimeException
     */
    private function isMultipleChoiceAnswerWithAllAndNoneValid(array $answers): bool
    {
        if ((in_array(self::ANSWER_ALL, $answers, true) || in_array(self::ANSWER_NONE, $answers, true)) && count($answers) > 1) {
            $this->console->writeError(sprintf(
                'The options "%s" and "%s" cannot be combined with each other or any other ones.',
                self::ANSWER_ALL,
                self::ANSWER_NONE,
            ));

            return false;
        }

        return true;
    }

    /**
     * @phpstan-assert-if-true !(self::ANSWER_ALL|self::ANSWER_NONE) $answer
     */
    private function isNotAllOrNoneAnswer(string $answer): bool
    {
        return $answer !== self::ANSWER_ALL && $answer !== self::ANSWER_NONE;
    }
}
