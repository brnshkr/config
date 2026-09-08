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
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function array_first;
use function array_key_exists;
use function array_keys;
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
 *
 * @phpstan-import-type ModuleName from Module
 * @phpstan-import-type ModuleInfo from Module
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

    private bool $doCopyConfigFilesAutomatically = false;

    private bool $doCreateMakeFileAutomatically = false;

    private bool $doCreateGitignoreFileAutomatically = false;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Runs the {{ package_full_name }} setup process';
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
            ->addArgument('modules', InputArgument::IS_ARRAY, 'The modules to install <fg=yellow>(' . Str::joinAsQuotedList(array_keys(Module::MAP)) . ')</fg=yellow>')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Install all modules')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update to the latest package versions from ' . $this->libraryComposerJson->getPackageFullName())
            ->addOption('exact', 'e', InputOption::VALUE_NONE, 'Install exact versions of dependencies')
            ->addOption('optional', 'o', InputOption::VALUE_NONE, 'Automatically include all optional packages')
            ->addOption('copy', 'c', InputOption::VALUE_NONE, 'Automatically copy config files for selected modules')
            ->addOption('make', 'm', InputOption::VALUE_NONE, 'Automatically create Makefile')
            ->addOption('gitignore', 'g', InputOption::VALUE_NONE, 'Automatically create .gitignore file')
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
        $this->doCopyConfigFilesAutomatically         = $this->isBoolOptionEnabled('copy');
        $this->doCreateMakeFileAutomatically          = $this->isBoolOptionEnabled('make');
        $this->doCreateGitignoreFileAutomatically     = $this->isBoolOptionEnabled('gitignore');

        $modules              = $this->getStringListArgument('modules');
        $doInstallAllModules  = $this->isBoolOptionEnabled('all');
        $moduleNamesToInstall = [];

        $this->console->writeLogo();

        if ($modules === []) {
            $moduleNamesToInstall = $doInstallAllModules ? array_keys(Module::MAP) : $this->getModuleNamesToInstall();
        } elseif ($doInstallAllModules) {
            throw new InvalidArgumentException('The <fg=cyan>--all</fg=cyan> option is not allowed when specifing modules via the arguments.');
        } else {
            foreach ($modules as $module) {
                if (!array_key_exists($module, Module::MAP)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unknown module "%s". Allowed modules are: %s.',
                        $module,
                        Str::joinAsQuotedList(array_keys(Module::MAP)),
                    ));
                }

                $moduleNamesToInstall[] = $module;
            }
        }

        $modulesToInstall = array_map(
            static fn (string $name): array => Module::MAP[$name],
            $moduleNamesToInstall,
        );

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

            $this->copyFilesIfApplicable($modulesToInstall);

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

        $this->copyFilesIfApplicable($modulesToInstall);
        $this->console->writeNotice('Setup process completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return list<ModuleName>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getModuleNamesToInstall(): array
    {
        $moduleNames          = array_keys(Module::MAP);
        $isAnswerValid        = false;
        $moduleNamesToInstall = [];

        while (!$isAnswerValid) {
            $moduleNamesToInstall = $this->console->select(
                question: 'Which modules would you like to install?',
                choices: [
                    ...$moduleNames,
                    self::ANSWER_ALL,
                    self::ANSWER_NONE,
                ],
                default: self::ANSWER_ALL,
                isMultiselect: true,
            );

            $isAnswerValid = $this->isMultipleChoiceAnswerWithAllAndNoneValid($moduleNamesToInstall);
        }

        return array_values(array_filter(
            array_first($moduleNamesToInstall) === self::ANSWER_ALL ? $moduleNames : $moduleNamesToInstall,
            $this->isNotAllOrNoneAnswer(...),
        ));
    }

    /**
     * @param ModuleInfo $moduleInfo
     *
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getPackagesToInstall(array $moduleInfo): array
    {
        $packages = self::toPackageNames(array_filter(
            $moduleInfo['packages']['requiredAll'],
            fn (Package $package): bool => $this->doForceUpdate ? true : !$package->isInstalled(),
        ));

        $allOptionalPackages = $moduleInfo['packages']['optional'] ?? [];

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
                $moduleInfo,
                self::toPackageNames(array_filter(
                    $allOptionalPackages,
                    static fn (Package $package): bool => !$package->isInstalled(),
                )),
            );
        }

        return [...$packages, ...$optionalPackagesToInstall];
    }

    /**
     * @param ModuleInfo $moduleInfo
     * @param list<non-empty-string> $packages
     *
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function promptForOptionalPackages(array $moduleInfo, array $packages): array
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
                $moduleInfo['name'],
            ));

            return $doInstallOptionalPackage ? [$optionalPackage] : [];
        }

        $isAnswerValid    = false;
        $selectedPackages = [];

        while (!$isAnswerValid) {
            $selectedPackages = $this->console->select(
                question: sprintf(
                    'Select optional dependencies to install for module "%s".',
                    $moduleInfo['name'],
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
     * @param list<ModuleInfo> $moduleInfos
     *
     * @throws IOException
     * @throws RuntimeException
     */
    private function copyFilesIfApplicable(array $moduleInfos): void
    {
        if (!$this->doCopyConfigFilesAutomatically && !$this->doCreateMakeFileAutomatically && !$this->doCreateGitignoreFileAutomatically) {
            return;
        }

        $projectRootPath = Path::getDirectory($this->projectComposerJson->path);
        $libraryRootPath = Path::getDirectory($this->libraryComposerJson->path);

        if ($this->doCreateMakeFileAutomatically) {
            $this->console->writeNotice('Copying Makefile');
            $this->copyFile($libraryRootPath . '/conf/Makefile.example', $projectRootPath . '/Makefile');
        }

        if ($this->doCreateGitignoreFileAutomatically) {
            $this->console->writeNotice('Copying .gitignore file');
            $this->copyFile($libraryRootPath . '/conf/.gitignore.example', $projectRootPath . '/.gitignore');
        }

        if (!$this->doCopyConfigFilesAutomatically) {
            return;
        }

        foreach ($moduleInfos as $moduleInfo) {
            $files = match ($moduleInfo['name']) {
                Module::NAME_PHP_CS_FIXER => [[
                    'source'      => $libraryRootPath . '/conf/php-cs-fixer.dist.php.example',
                    'target'      => $projectRootPath . '/conf/php-cs-fixer.dist.php',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/php-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/php-cs-fixer.php.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/php-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/php-cs-fixer.php',
                    'isVersioned' => false,
                ]],
                Module::NAME_PHP_STAN => [[
                    'source'      => $libraryRootPath . '/conf/phpstan.dist.php.example',
                    'target'      => $projectRootPath . '/conf/phpstan.dist.php',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/phpstan.php.example',
                    'target'      => $projectRootPath . '/conf/phpstan.php.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/phpstan.php.example',
                    'target'      => $projectRootPath . '/conf/phpstan.php',
                    'isVersioned' => false,
                ]],
                Module::NAME_RECTOR => [[
                    'source'      => $libraryRootPath . '/conf/rector.dist.php.example',
                    'target'      => $projectRootPath . '/conf/rector.dist.php',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/rector.php.example',
                    'target'      => $projectRootPath . '/conf/rector.php.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/rector.php.example',
                    'target'      => $projectRootPath . '/conf/rector.php',
                    'isVersioned' => false,
                ]],
                Module::NAME_TWIG_CS_FIXER => [[
                    'source'      => $libraryRootPath . '/conf/twig-cs-fixer.dist.php.example',
                    'target'      => $projectRootPath . '/conf/twig-cs-fixer.dist.php',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/twig-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/twig-cs-fixer.php.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/twig-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/twig-cs-fixer.php',
                    'isVersioned' => false,
                ]],
            };

            $this->console->writeNotice(sprintf('Copying files for module "%s".', $moduleInfo['name']));

            foreach ($files as $file) {
                $this->copyFile($file['source'], $file['target'], $file['isVersioned']);
            }
        }
    }

    /**
     * @param non-empty-string $sourceFilePath
     * @param non-empty-string $targetFilePath
     *
     * @throws RuntimeException
     */
    private function copyFile(string $sourceFilePath, string $targetFilePath, bool $isVersioned = true): void
    {
        if ($this->isCopyAllowed($targetFilePath)) {
            $this->filesystem->copy($sourceFilePath, $targetFilePath, true);
            $this->console->writeInfo(sprintf('Copied file "%s" to "%s".', $sourceFilePath, $targetFilePath));

            if (!$isVersioned) {
                $this->console->writeWarning(sprintf(
                    'Make sure to exclude %s from versioning.',
                    $targetFilePath,
                ));
            }
        }
    }

    /**
     * @param non-empty-string $targetFilePath
     *
     * @throws RuntimeException
     */
    private function isCopyAllowed(string $targetFilePath): bool
    {
        return $this->filesystem->exists($targetFilePath)
            ? $this->console->isConfirmed(sprintf(
                'File "%s" already exists. Do you want to overwrite it?',
                $targetFilePath,
            ), isDefaultAnswerYes: false)
            : true;
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
