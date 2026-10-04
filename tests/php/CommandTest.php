<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\Command\BrnshkrConfigCommand;
use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Command\ExtractPharCommand;
use Brnshkr\Config\Composer\Command\PrintModuleConfigCommand;
use Brnshkr\Config\Composer\Command\SetupCommand;
use Brnshkr\Config\Composer\Command\UpdatePhpExtensionsCommand;
use Brnshkr\Config\Composer\ComposerJsonManipulator;
use Brnshkr\Config\Composer\Console;
use Brnshkr\Config\Composer\Installer;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\ProjectDirectory;
use Brnshkr\Config\Str;
use Composer\Console\Application;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function fopen;
use function fwrite;
use function rewind;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversClass(BrnshkrConfigCommand::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(ProjectDirectory::class)]
#[UsesClass(PrintModuleConfigCommand::class)]
#[UsesClass(ComposerJsonManipulator::class)]
#[UsesClass(Console::class)]
#[UsesClass(Installer::class)]
#[UsesClass(Json::class)]
#[UsesClass(Module::class)]
#[UsesClass(Package::class)]
#[UsesClass(Str::class)]
#[CoversClass(CommandProvider::class)]
#[CoversClass(ExtractPharCommand::class)]
#[CoversClass(SetupCommand::class)]
#[CoversClass(UpdatePhpExtensionsCommand::class)]
final class CommandTest extends TestCase
{
    private const string INTERACTION_VARIABLE_NAME = 'COMPOSER_TESTS_ARE_RUNNING';

    private Application $application;

    public function testMainCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new BrnshkrConfigCommand()->getName(),
            '-vvv',
        ]);

        $bufferedOutput                   = new BufferedOutput();
        $exitCode                         = $this->application->run($arrayInput, $bufferedOutput);
        $outputString                     = s($bufferedOutput->fetch())->replaceMatches('/^Running.+\n/', '')->toString();
        $composerJson                     = ComposerJson::forThisLibrary();
        $packageVersion                   = $composerJson->getPackageVersion();
        $packageName                      = $composerJson->getPackageName();
        $packageOrganization              = $composerJson->getPackageOrganization();
        $firstLetterOfPackageName         = s($packageName)->slice(0, 1)->toString();
        $firstLetterOfPackageOrganization = s($packageOrganization)->slice(0, 1)->toString();
        $titleCasePackageName             = s($packageName)->title()->toString();

        // @phpstan-ignore symplify.forbiddenNode (Use of encapsed strings to preserve easy readability and adjustability here)
        $expectedOutput = <<<EOF
   ___               __   __
  / _ )_______  ____/ /  / /__ ____
 / _  / __/ _ \\(_--/ _ \\/  ´_// __/
/____/_/ /_//_/___/_//_/_/\\_\\/_/

{$titleCasePackageName} ({$packageVersion})

Description:
  Displays {$packageOrganization}/{$packageName} composer plugin overview

Usage:
  {$packageOrganization}:{$packageName}
  {$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}

Options:
  -h, --help                     Display help for the given command. When no command is given display help for the list command
      --silent                   Do not output any message
  -q, --quiet                    Only errors are displayed. All other output is suppressed
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --profile                  Display timing and memory usage information
      --no-plugins               Whether to disable plugins.
      --no-scripts               Skips the execution of all scripts defined in composer.json file.
  -d, --working-dir=WORKING-DIR  If specified, use the given directory as working directory.
      --no-cache                 Prevent use of the cache
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  {$packageOrganization}:{$packageName}                        [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}] Displays {$packageOrganization}/{$packageName} composer plugin overview
  {$packageOrganization}:{$packageName}:extract-phar           [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:ep] Extracts a .phar file from a given vendor package
  {$packageOrganization}:{$packageName}:print-module-config    [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:pmc] Prints the resolved configuration as JSON for any supported {$packageOrganization}/{$packageName} module
  {$packageOrganization}:{$packageName}:setup                  [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:s] Installs the packages the {$packageOrganization}/{$packageName} modules you pick need
  {$packageOrganization}:{$packageName}:update-php-extensions  [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:upe] Updates required PHP extensions in composer.json based on installed vendor files

EOF;

        self::assertSame(0, $exitCode);
        self::assertStringContainsStringIgnoringLineEndings($expectedOutput, $outputString);
    }

    public function testExtractPharCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new ExtractPharCommand()->getName(),
            '-vvv',
            'package' => 'phpstan/phpstan',
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);
        $outputString   = $bufferedOutput->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Extracting ./vendor/phpstan/phpstan/phpstan.phar to ./vendor/phpstan/phpstan/.extracted-phar', $outputString);
    }

    public function testSetupCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new SetupCommand()->getName(),
            '-vvv',
            '--all'      => true,
            '--exact'    => true,
            '--optional' => true,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);
        $outputString   = $bufferedOutput->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('All packages are already installed.', $outputString);
    }

    public function testSetupCommandInstallsNothingForAnInstalledModule(): void
    {
        $result = $this->runSetupCommand(['modules' => ['rector']]);

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('All packages are already installed.', $result['output']);
    }

    public function testSetupCommandRejectsAnUnknownModule(): void
    {
        $result = $this->runSetupCommand(['modules' => ['acme']]);

        self::assertSame(1, $result['exitCode']);

        self::assertStringContainsString(
            'Unknown module "acme". Allowed modules are: "phpcsfixer", "phpstan", "rector" and "twigcsfixer".',
            $result['output'],
        );
    }

    public function testSetupCommandRejectsAllBesideNamedModules(): void
    {
        $result = $this->runSetupCommand([
            'modules' => ['rector'],
            '--all'   => true,
        ]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('The --all option is not allowed when specifying modules via the arguments.', $result['output']);
        self::assertStringNotContainsString('All packages are already installed.', $result['output']);
    }

    public function testSetupCommandAsksAgainUntilTheModuleAnswerIsValid(): void
    {
        $result = $this->runSetupCommand([], "all,none\nrector\n");

        self::assertSame(0, $result['exitCode']);
        self::assertStringContainsString('The options "all" and "none" cannot be combined with each other or any other ones.', $result['output']);
        self::assertStringContainsString('All packages are already installed.', $result['output']);
    }

    public function testUpdatePhpExtensionsCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new UpdatePhpExtensionsCommand()->getName(),
            '-vvv',
            '--allow'     => [],
            '--allow-dev' => [],
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);
        $outputString   = $bufferedOutput->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Updating required PHP extensions.', $outputString);
        self::assertStringContainsString('Skipping "ext-filter" — already present in composer.json (require-dev).', $outputString);
        self::assertStringContainsString('Skipping "ext-json" — already listed under requirements.', $outputString);
    }

    #[Before]
    public function createApplication(): void
    {
        $application = new Application();

        $application->setAutoExit(false);
        $application->addCommands(new CommandProvider()->getCommands());

        $this->application = $application;
    }

    #[After]
    public function removeTheInteractionVariable(): void
    {
        unset($_SERVER[self::INTERACTION_VARIABLE_NAME]);
    }

    /**
     * @param array<string, bool|list<string>> $arguments
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     */
    private function runSetupCommand(array $arguments, ?string $answers = null): array
    {
        $arrayInput = new ArrayInput([
            'command' => new SetupCommand()->getName(),
            ...$arguments,
        ]);

        if ($answers !== null) {
            $_SERVER[self::INTERACTION_VARIABLE_NAME] = '1';

            $stream = fopen('php://memory', 'r+');

            self::assertNotFalse($stream);

            fwrite($stream, $answers);
            rewind($stream);

            $arrayInput->setStream($stream);
        }

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);

        return [
            'exitCode' => $exitCode,
            'output'   => $bufferedOutput->fetch(),
        ];
    }
}
