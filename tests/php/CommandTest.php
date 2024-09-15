<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\Command\BrnshkrConfigCommand;
use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Command\ExtractPharCommand;
use Brnshkr\Config\Composer\Command\SetupCommand;
use Brnshkr\Config\Composer\Command\UpdatePhpExtensionsCommand;
use Brnshkr\Config\ComposerJson;
use Composer\Composer;
use Composer\Console\Application;
use Composer\Json\JsonValidationException;
use Exception;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversClass(BrnshkrConfigCommand::class)]
#[CoversClass(CommandProvider::class)]
#[CoversClass(ExtractPharCommand::class)]
#[CoversClass(SetupCommand::class)]
#[CoversClass(UpdatePhpExtensionsCommand::class)]
final class CommandTest extends TestCase
{
    private Application $application;

    /**
     * @throws JsonValidationException
     * @throws LogicException
     */
    #[Override]
    protected function setUp(): void
    {
        $application = new Application();
        $composer    = $application->getComposer(true);

        self::assertInstanceOf(Composer::class, $composer);

        $application->setAutoExit(false);
        $application->addCommands(CommandProvider::getCommandInstances($composer));

        $this->application = $application;
    }

    /**
     * @throws Exception
     * @throws LogicException
     */
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
        $title                            = s($titleCasePackageName)->append(sprintf(' (%s)', $packageVersion))->toString();
        $line                             = s('‾')->repeat(s($title)->length())->toString();

        // @phpstan-ignore symplify.forbiddenNode (Use of encapsed strings to preserve easy readability and adjustability here)
        $expectedOutput = <<<EOL
   ___               __   __
  / _ )_______  ____/ /  / /__ ____
 / _  / __/ _ \\(_--/ _ \\/  ´_// __/
/____/_/ /_//_/___/_//_/_/\\_\\/_/

{$titleCasePackageName} ({$packageVersion})
{$line}
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
  {$packageOrganization}:{$packageName}:setup                  [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:s] Runs the {$packageOrganization}/{$packageName} setup process
  {$packageOrganization}:{$packageName}:update-php-extensions  [{$firstLetterOfPackageOrganization}:{$firstLetterOfPackageName}:upe] Updates required PHP extensions in composer.json based on installed vendor files

EOL;

        self::assertSame(0, $exitCode);
        self::assertStringContainsStringIgnoringLineEndings($expectedOutput, $outputString);
    }

    /**
     * @throws Exception
     * @throws LogicException
     */
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

    /**
     * @throws Exception
     * @throws LogicException
     */
    public function testSetupCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new SetupCommand()->getName(),
            '-vvv',
            '--all'      => true,
            '--force'    => true,
            '--exact'    => true,
            '--optional' => true,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);
        $outputString   = $bufferedOutput->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Setup process completed successfully.', $outputString);
        self::assertStringContainsString('You have installed "ticketswap/phpstan-error-formatter". Ensure that parameters.errorFormat is set to "ticketswap" in the phpstan.dist.neon file.', $outputString);
    }

    /**
     * @throws Exception
     * @throws LogicException
     */
    public function testUpdatePhpExtensionsCommand(): void
    {
        $arrayInput = new ArrayInput([
            'command' => new UpdatePhpExtensionsCommand()->getName(),
            '-vvv',
            '--allow'     => true,
            '--allow-dev' => true,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $this->application->run($arrayInput, $bufferedOutput);
        $outputString   = $bufferedOutput->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Updating required PHP extensions.', $outputString);
        self::assertStringContainsString('Skipping "ext-filter" — already present in composer.json (require-dev).', $outputString);
        self::assertStringContainsString('Skipping "ext-json" — already listed under requirements.', $outputString);
    }
}
