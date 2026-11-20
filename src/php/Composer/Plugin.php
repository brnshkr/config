<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\ProjectDirectory;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as BaseCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use JsonException;
use Override;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function is_array;
use function is_file;
use function is_string;

use const PHP_BINARY;

/**
 * @internal
 */
final class Plugin implements Capable, EventSubscriberInterface, PluginInterface
{
    private const string DISABLED_SKILLS_RECORD = 'mate-disabled-skills';

    private Composer $composer;

    private IOInterface $io;

    private Console $console;

    private ComposerJson $libraryComposerJson;

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer            = $composer;
        $this->io                  = $io;
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
            ScriptEvents::POST_INSTALL_CMD => [
                ['onPostInstall', 0],
                ['disableMateSkillsWithoutMakefile', -10],
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                ['disableMateSkillsWithoutMakefile', -10],
            ],
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
     * @throws IOException
     * @throws JsonException
     * @throws RuntimeException
     */
    public function disableMateSkillsWithoutMakefile(): void
    {
        $rootDirectory   = Platform::getCwd();
        $mateBinary      = $this->composer->getConfig()->get('bin-dir') . '/mate';
        $packageFullName = $this->libraryComposerJson->getPackageFullName();

        if (!is_file($mateBinary)
            || !is_file($rootDirectory . '/mate/extensions.php')
            || !Project::isMakefileMissing($this->composer->getPackage()->getName(), $rootDirectory)) {
            return;
        }

        $processExecutor = new ProcessExecutor($this->io);
        $skillList       = '';

        $processExecutor->execute([
            PHP_BINARY,
            $mateBinary,
            'skills:list',
            '--format=json',
        ], $skillList, $rootDirectory);

        $skills = Json::decode(is_string($skillList) ? $skillList : '{}')['skills'] ?? [];
        $record = '';

        foreach (is_array($skills) ? $skills : [] as $skill) {
            $skillName = is_array($skill) ? $skill['original_name'] ?? null : null;

            if (!is_array($skill)
                || !is_string($skillName)
                || ($skill['package'] ?? null) !== $packageFullName
                || ($skill['enabled'] ?? null) !== true) {
                continue;
            }

            $skillDisableExitCode = $processExecutor->execute([
                PHP_BINARY,
                $mateBinary,
                'skills:disable',
                '--quiet',
                $skillName,
            ], $ignoredOutput, $rootDirectory);

            if ($skillDisableExitCode === 0) {
                $record .= $skillName . " makefile\n";
            }
        }

        if ($record !== '') {
            new Filesystem()->appendToFile(
                $rootDirectory . '/' . ProjectDirectory::getOwnCache() . '/' . self::DISABLED_SKILLS_RECORD,
                $record,
            );
        }
    }
}
