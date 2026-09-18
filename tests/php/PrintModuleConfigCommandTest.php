<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Command\PrintModuleConfigCommand;
use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
use Brnshkr\Config\Testing\JsonSnapshotDriver;
use Composer\Console\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use stdClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

use function array_unique;
use function array_values;
use function dirname;
use function explode;
use function get_object_vars;
use function getcwd;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversClass(PrintModuleConfigCommand::class)]
#[UsesClass(JsonSnapshotDriver::class)]
final class PrintModuleConfigCommandTest extends TestCase
{
    use MatchesSnapshots;

    private const array DEPENDENCY_DIRECTORIES = [
        './node_modules/',
        './vendor/',
    ];

    /**
     * @var ?list<string>
     */
    private static ?array $unignoredPaths = null;

    public function testPrintsExpectedPhpCsFixerConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_PHP_CS_FIXER), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedPhpStanConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_PHP_STAN), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedRectorConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_RECTOR), new JsonSnapshotDriver());
    }

    public function testPrintsExpectedTwigCsFixerConfig(): void
    {
        $this->assertMatchesSnapshot($this->runForModule(Module::NAME_TWIG_CS_FIXER), new JsonSnapshotDriver());
    }

    public function testAutoDetectsModuleFromPath(): void
    {
        self::assertSame(
            $this->runForModule(Module::NAME_PHP_CS_FIXER),
            $this->runCommand(['--path' => 'conf/php-cs-fixer.dist.php']),
        );
    }

    public function testErrorsWhenModuleAndPathBothOmitted(): void
    {
        $output = $this->runCommand([], isSuccessExpected: false);

        self::assertStringContainsString(
            'Either the "module" argument or the --path option must be provided.',
            $output,
        );
    }

    public function testErrorsOnUnknownModule(): void
    {
        $output = $this->runCommand(['module' => 'unknown-tool'], isSuccessExpected: false);

        self::assertStringContainsString('Unknown module "unknown-tool"', $output);
    }

    public function testErrorsOnTypeMismatch(): void
    {
        $output = $this->runCommand(
            [
                'module' => Module::NAME_PHP_STAN,
                '--path' => 'conf/php-cs-fixer.dist.php',
            ],
            isSuccessExpected: false,
        );

        self::assertStringContainsString(
            sprintf('but module "%s" expects', Module::NAME_PHP_STAN),
            $output,
        );
    }

    public function testErrorsOnMissingConfigPath(): void
    {
        $output = $this->runCommand(
            ['--path' => 'conf/does-not-exist.dist.php'],
            isSuccessExpected: false,
        );

        self::assertStringContainsString('does not exist', $output);
    }

    private function runForModule(string $module): string
    {
        return $this->runCommand(['module' => $module]);
    }

    private static function removeIgnoredPaths(mixed $config): mixed
    {
        if ($config instanceof stdClass) {
            $keptProperties = [];

            foreach (get_object_vars($config) as $name => $value) {
                if (Str::isNonDecimalIntString((string) $name)) {
                    $keptProperties[$name] = self::removeIgnoredPaths($value);
                }
            }

            return (object) $keptProperties;
        }

        if (!is_array($config)) {
            return $config;
        }

        $keptValues = [];

        foreach ($config as $value) {
            if (!is_string($value) || !self::isIgnoredProjectPath($value)) {
                $keptValues[] = self::removeIgnoredPaths($value);
            }
        }

        return $keptValues;
    }

    private static function isIgnoredProjectPath(string $value): bool
    {
        if (!Str::startsWith($value, './') || Str::startsWithAny($value, self::DEPENDENCY_DIRECTORIES)) {
            return false;
        }

        return !in_array(Str::trimSuffix($value, '/'), self::getUnignoredPaths(), true);
    }

    /**
     * @return list<string>
     */
    private static function getUnignoredPaths(): array
    {
        if (self::$unignoredPaths !== null) {
            return self::$unignoredPaths;
        }

        $process = new Process(['git', 'ls-files', '--cached', '--others', '--exclude-standard']);

        $process->mustRun();

        $unignoredPaths = [];

        foreach (explode("\n", Str::trim($process->getOutput())) as $unignoredFile) {
            $path = './' . $unignoredFile;

            while ($path !== '.') {
                $unignoredPaths[] = $path;
                $path             = dirname($path);
            }
        }

        self::$unignoredPaths = $unignoredPaths
            |> array_unique(...)
            |> array_values(...);

        return self::$unignoredPaths;
    }

    /**
     * @param array<string, string> $arguments
     */
    private function runCommand(array $arguments, bool $isSuccessExpected = true): string
    {
        $application = new Application();

        $application->setAutoExit(false);
        $application->addCommands(new CommandProvider()->getCommands());

        $arrayInput = new ArrayInput([
            'command' => new PrintModuleConfigCommand()->getName(),
            ...$arguments,
        ]);

        $bufferedOutput = new BufferedOutput();
        $exitCode       = $application->run($arrayInput, $bufferedOutput);

        $output = s($bufferedOutput->fetch())
            ->replaceMatches(sprintf('/%s/', Str::quoteRegex(getcwd() ?: '.')), '.')
            ->toString()
        ;

        if (!$isSuccessExpected) {
            self::assertNotSame(0, $exitCode);

            return $output;
        }

        self::assertSame(0, $exitCode);

        return Json::decode($output, isAssociative: false)
            |> self::removeIgnoredPaths(...)
            |> Json::encode(...);
    }
}
