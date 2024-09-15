<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\ComposerJsonManipulator;
use Brnshkr\Config\Composer\Installer;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Module;
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
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function current;
use function file_get_contents;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
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

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Runs the {{ package_full_name }} setup process';
    }

    /**
     * @throws InvalidArgumentException
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
            ->addArgument('modules', InputArgument::IS_ARRAY, 'The modules to install <fg=yellow>(' . Str::joinAsQuotedList(array_keys(Module::NAME_TO_MODULE_MAP)) . ')</fg=yellow>')
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
     * @phpstan-return self::SUCCESS|self::FAILURE
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $this->console->writeLogo();

        $modules              = $this->input->getArgument('modules');
        $modules              = is_array($modules) && $modules !== [] ? $modules : null;
        $doInstallAllModules  = $this->input->getOption('all') !== false;
        $moduleNamesToInstall = [];

        if ($modules === null) {
            $moduleNamesToInstall = $doInstallAllModules ? array_keys(Module::NAME_TO_MODULE_MAP) : $this->getModuleNamesToInstall();
        } elseif ($doInstallAllModules) {
            throw new InvalidArgumentException('The <fg=cyan>--all</fg=cyan> option is not allowed when specifing modules via the arguments.');
        } else {
            foreach ($modules as $module) {
                if (!is_string($module)) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid module name, expected string, but got %s.',
                        get_debug_type($module),
                    ));
                }

                if (!array_key_exists($module, Module::NAME_TO_MODULE_MAP)) {
                    throw new InvalidArgumentException(sprintf(
                        'Unknown module "%s". Allowed modules are: %s.',
                        $module,
                        Str::joinAsQuotedList(array_keys(Module::NAME_TO_MODULE_MAP)),
                    ));
                }

                $moduleNamesToInstall[] = $module;
            }
        }

        $modulesToInstall = array_map(
            static fn (string $name): array => Module::NAME_TO_MODULE_MAP[$name],
            $moduleNamesToInstall,
        );

        $doForceUpdate                          = $this->input->getOption('force') !== false;
        $doInstallExactVersions                 = $this->input->getOption('exact') !== false;
        $doIncludeOptionalPackagesAutomatically = $this->input->getOption('optional') !== false;
        $doCopyConfigFilesAutomatically         = $this->input->getOption('copy') !== false;
        $doCreateMakeFileAutomatically          = $this->input->getOption('make') !== false;
        $doCreateGitignoreFileAutomatically     = $this->input->getOption('gitignore') !== false;
        $composerJsonFileContent                = $this->getFileContent($this->projectComposerJson->path);
        $lockFileContent                        = $this->getFileContent($this->projectComposerJson->lockFilePath);

        $packagesToInstall = array_merge(...array_map(
            fn (array $moduleInfo): array => $this->getPackagesToInstall($moduleInfo, $doForceUpdate, $doIncludeOptionalPackagesAutomatically),
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
            $exitCode = $this->installer->install($packagesToInstall, $doInstallExactVersions);
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

        $this->copyFilesIfApplicable($modulesToInstall, $doCopyConfigFilesAutomatically, $doCreateMakeFileAutomatically, $doCreateGitignoreFileAutomatically);
        $this->console->writeNotice('Setup process completed successfully.');

        if (in_array(Module::PACKAGE_PHP_STAN_ERROR_FORMATTER, $packagesToInstall, true)
            && Module::isPackageInstalled(Module::PACKAGE_PHP_STAN_ERROR_FORMATTER)) {
            $this->console->writeWarning(sprintf(
                'You have installed "%s". Ensure that parameters.errorFormat is set to "ticketswap" in the phpstan.dist.neon file.',
                Module::PACKAGE_PHP_STAN_ERROR_FORMATTER,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return list<key-of<Module::NAME_TO_MODULE_MAP>>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getModuleNamesToInstall(): array
    {
        $moduleNames   = array_keys(Module::NAME_TO_MODULE_MAP);
        $isAnswerValid = false;

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
            current($moduleNamesToInstall) === self::ANSWER_ALL ? $moduleNames : $moduleNamesToInstall,
            $this->isNotAllOrNoneAnswer(...),
        ));
    }

    /**
     * @phpstan-param Module::MODULE_* $moduleInfo
     *
     * @return list<Module::PACKAGE_*>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function getPackagesToInstall(array $moduleInfo, bool $doForceUpdate, bool $doIncludeOptionalPackagesAutomatically): array
    {
        $packages = array_values(array_filter(
            $moduleInfo['packages']['requiredAll'],
            static fn (string $package): bool => $doForceUpdate ? true : !Module::isPackageInstalled($package),
        ));

        $optionalPackages = array_values(array_filter(
            count($moduleInfo['packages']['optional'] ?? []) === 1
                ? [current($moduleInfo['packages']['optional'])]
                : $moduleInfo['packages']['optional'] ?? [],
            static fn (string $package): bool => $doForceUpdate ? true : !Module::isPackageInstalled($package),
        ));

        $optionalPackageCount      = count($optionalPackages);
        $optionalPackagesToInstall = [];
        $isAnswerValid             = false;

        while (!$isAnswerValid) {
            if ($doIncludeOptionalPackagesAutomatically) {
                $optionalPackagesToInstall = [self::ANSWER_ALL];

                break;
            }

            if ($optionalPackageCount === 0) {
                break;
            }

            if ($optionalPackageCount === 1) {
                $optionalPackage = current($optionalPackages);

                $doInstallOptionalPackage = $doForceUpdate ?: $this->console->isConfirmed(sprintf(
                    'Module "%s" includes an optional dependency that is not yet installed ("%s"). Do you want to install it as well?',
                    $moduleInfo['name'],
                    $optionalPackage,
                ));

                if ($doInstallOptionalPackage) {
                    $optionalPackagesToInstall = [$optionalPackage];
                }

                break;
            }

            $optionalPackagesToInstall = $this->console->select(
                question: sprintf(
                    $doForceUpdate
                        ? 'Select the optional dependencies for module "%s" that you want to force update (%s).'
                        : 'Module "%s" includes some optional dependencies that are not yet installed (%s). Select the ones you want install as well.',
                    $moduleInfo['name'],
                    Str::joinAsQuotedList($optionalPackages),
                ),
                choices: [
                    ...$optionalPackages,
                    self::ANSWER_ALL,
                    self::ANSWER_NONE,
                ],
                default: self::ANSWER_ALL,
                isMultiselect: true,
            );

            $isAnswerValid = $this->isMultipleChoiceAnswerWithAllAndNoneValid($optionalPackagesToInstall);
        }

        return [
            ...$packages,
            ...array_values(array_filter(
                current($optionalPackagesToInstall) === self::ANSWER_ALL ? $optionalPackages : $optionalPackagesToInstall,
                $this->isNotAllOrNoneAnswer(...),
            )),
        ];
    }

    /**
     * @phpstan-param list<Module::MODULE_*> $moduleInfos
     *
     * @throws IOException
     * @throws RuntimeException
     */
    private function copyFilesIfApplicable(array $moduleInfos, bool $doCopyConfigFilesAutomatically, bool $doCreateMakeFileAutomatically, bool $doCreateGitignoreFileAutomatically): void
    {
        if (!$doCopyConfigFilesAutomatically && !$doCreateMakeFileAutomatically && !$doCreateGitignoreFileAutomatically) {
            return;
        }

        $projectRootPath = Path::getDirectory($this->projectComposerJson->path);
        $libraryRootPath = Path::getDirectory($this->libraryComposerJson->path);

        if ($doCreateMakeFileAutomatically) {
            $this->console->writeNotice('Copying Makefile');
            $this->copyFile($libraryRootPath . '/conf/Makefile.example', $projectRootPath . '/Makefile');
        }

        if ($doCreateGitignoreFileAutomatically) {
            $this->console->writeNotice('Copying .gitignore file');
            $this->copyFile($libraryRootPath . '/conf/.gitignore.example', $projectRootPath . '/.gitignore');
        }

        if (!$doCopyConfigFilesAutomatically) {
            return;
        }

        foreach ($moduleInfos as $moduleInfo) {
            $files = match ($moduleInfo['name']) {
                Module::NAME_PHP_CS_FIXER => [[
                    'source'      => $libraryRootPath . '/conf/.php-cs-fixer.dist.php.example',
                    'target'      => $projectRootPath . '/conf/.php-cs-fixer.dist.php',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/.php-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/.php-cs-fixer.php.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/.php-cs-fixer.php.example',
                    'target'      => $projectRootPath . '/conf/.php-cs-fixer.php',
                    'isVersioned' => false,
                ]],
                Module::NAME_PHP_STAN => [[
                    'source'      => $libraryRootPath . '/conf/phpstan.dist.neon.example',
                    'target'      => $projectRootPath . '/conf/phpstan.dist.neon',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/phpstan.neon.example',
                    'target'      => $projectRootPath . '/conf/phpstan.neon.example',
                    'isVersioned' => true,
                ], [
                    'source'      => $libraryRootPath . '/conf/phpstan.neon.example',
                    'target'      => $projectRootPath . '/conf/phpstan.neon',
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
            };

            $this->console->writeNotice(sprintf('Copying files for module "%s".', $moduleInfo['name']));

            foreach ($files as $file) {
                $this->copyFile($file['source'], $file['target'], $file['isVersioned']);
            }
        }
    }

    /**
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
     * @throws RuntimeException
     */
    private function getFileContent(string $path): ?string
    {
        return ($this->filesystem->exists($path) && is_file($path))
            // @phpstan-ignore-next-line symplify.forbiddenFuncCall (Avoid using Filesystem::readFile since this method is newer and does no necessarily exist in the version bundled with composer)
            ? (file_get_contents($path) ?: null)
            : null;
    }

    /**
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
