<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Make\Trait;

use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

use function array_diff;
use function array_map;
use function dirname;
use function fclose;
use function flock;
use function fopen;
use function getenv;
use function is_dir;
use function is_file;
use function is_link;
use function mkdir;
use function rmdir;
use function scandir;
use function sprintf;
use function Symfony\Component\String\s;
use function unlink;

use const LOCK_EX;
use const LOCK_UN;

/**
 * @internal
 *
 * @phpstan-require-extends TestCase
 */
trait MakeTrait
{
    private const string MAKEFILE_PATH      = __DIR__ . '/../../../../conf/Makefile';
    private const string FIXTURES_DIRECTORY = __DIR__ . '/../../Fixtures/Make/Help';
    private const string CONFIGS_DIRECTORY  = __DIR__ . '/../../Fixtures/Make/Configs';
    private const string TSCONFIG_DIRECTORY = __DIR__ . '/../../Fixtures/Make/Typescript';
    private const string CACHES_DIRECTORY   = __DIR__ . '/../../Fixtures/Make/Caches';
    private const string FALLBACK_DIRECTORY = __DIR__ . '/../../Fixtures/Make/ConfigFallback';
    private const string VENDOR_DIRECTORY   = __DIR__ . '/../../Fixtures/Make/ConfigVendor';
    private const string STARTUP_DIRECTORY  = __DIR__ . '/../../Fixtures/Make/Startup';
    private const string FIXTURE_LOCK_PATH  = __DIR__ . '/../../../../.cache/make-fixtures.lock';

    private const array CONFIG_DIRECTORIES = [
        self::CONFIGS_DIRECTORY,
        self::FALLBACK_DIRECTORY,
        self::VENDOR_DIRECTORY,
        self::STARTUP_DIRECTORY,
        self::TSCONFIG_DIRECTORY,
    ];

    private const array BASELINE_ENV = [
        'MAKEFLAGS'         => '',
        'NO_ANSI'           => '1',
        'WSL_DISTRO_NAME'   => '',
        'TERM_PROGRAM'      => '',
        'TERMINAL_EMULATOR' => '',
        'EDITOR'            => '',
        'LANG'              => '',
    ];

    /**
     * @var ?resource
     */
    private $fixtureLockHandle;

    #[Before]
    public function claimTheFixturesForThisTest(): void
    {
        $fixtureLockDirectory = dirname(self::FIXTURE_LOCK_PATH);

        if (!is_dir($fixtureLockDirectory)) {
            mkdir($fixtureLockDirectory, recursive: true);
        }

        $fixtureLockHandle = fopen(self::FIXTURE_LOCK_PATH, 'c');

        if ($fixtureLockHandle === false) {
            /** @disregard P1013 \@phpstan-require-extends is not recognized by intelephense (See: https://github.com/bmewburn/vscode-intelephense/issues/3256) */
            self::fail('The fixture lock could not be opened.');
        }

        $this->fixtureLockHandle = $fixtureLockHandle;

        flock($fixtureLockHandle, LOCK_EX);
        $this->removeWhatTheFixturesWrote();
    }

    #[After]
    public function releaseTheFixturesAfterThisTest(): void
    {
        $this->removeWhatTheFixturesWrote();

        if ($this->fixtureLockHandle === null) {
            return;
        }

        flock($this->fixtureLockHandle, LOCK_UN);
        fclose($this->fixtureLockHandle);

        $this->fixtureLockHandle = null;
    }

    private function removeWhatTheFixturesWrote(): void
    {
        $writtenPaths = [
            self::CONFIGS_DIRECTORY . '/.gitignore',
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/php-cs-fixer.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/phpstan.php',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.dist.xml',
            self::CONFIGS_DIRECTORY . '/conf/phpunit.xml',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.dist.php',
            self::CONFIGS_DIRECTORY . '/conf/twig-cs-fixer.php',
            self::FALLBACK_DIRECTORY . '/.gitignore',
            self::FALLBACK_DIRECTORY . '/conf/phpstan.dist.php',
            self::FALLBACK_DIRECTORY . '/conf/phpstan.php',
            self::VENDOR_DIRECTORY . '/.gitignore',
            self::VENDOR_DIRECTORY . '/conf/phpstan.dist.php',
            self::VENDOR_DIRECTORY . '/conf/phpstan.php',
            self::STARTUP_DIRECTORY . '/.gitignore',
            self::TSCONFIG_DIRECTORY . '/.gitignore',
            self::TSCONFIG_DIRECTORY . '/tsconfig.json',
            self::TSCONFIG_DIRECTORY . '/conf/tsconfig.json',
        ];

        foreach (self::CONFIG_DIRECTORIES as $fixtureDirectory) {
            $writtenPaths = [
                ...$writtenPaths,
                $fixtureDirectory . '/.editorconfig',
                $fixtureDirectory . '/.gitattributes',
                $fixtureDirectory . '/bunfig.toml',
            ];
        }

        foreach ($writtenPaths as $writtenPath) {
            if (is_file($writtenPath) || is_link($writtenPath)) {
                unlink($writtenPath);
            }
        }

        foreach (self::CONFIG_DIRECTORIES as $fixtureDirectory) {
            self::removeDirectory($fixtureDirectory . '/.vscode');
        }

        self::removeDirectory(self::TSCONFIG_DIRECTORY . '/conf');
        self::removeDirectory(self::CACHES_DIRECTORY . '/.cache');
    }

    private static function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entryName) {
            $childPath = $path . '/' . $entryName;

            is_dir($childPath) ? self::removeDirectory($childPath) : unlink($childPath);
        }

        rmdir($path);
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private function runMake(
        array $args = [],
        array $env = [],
        ?string $directory = null,
        bool $doExpectFailure = false,
    ): string {
        $process = new Process([
            'make',
            '--no-print-directory',
            ...($directory === null ? ['-f', self::MAKEFILE_PATH] : []),
            '-C',
            $directory ?? self::FIXTURES_DIRECTORY,
            ...$args,
        ], env: [
            ...self::getBaselineEnvironment(),
            ...$env,
        ]);

        $process->run();

        if (!$doExpectFailure && !$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "make help failed:\n%s\n%s",
                $process->getOutput(),
                $process->getErrorOutput(),
            ));
        }

        return $this->normalizeOutput($process->getOutput() . $process->getErrorOutput());
    }

    private function normalizeOutput(string $output): string
    {
        return s($output)
            ->replaceMatches(sprintf('/%s/', Str::quoteRegex(dirname(self::MAKEFILE_PATH, 2))), '.')
            ->toString()
        ;
    }

    /**
     * @return array<string, false|string>
     */
    private static function getBaselineEnvironment(): array
    {
        return [
            ...array_map(static fn (): false => false, getenv()),
            'HOME' => getenv('HOME'),
            'PATH' => getenv('PATH') ?: throw new RuntimeException('`PATH` is not set.'),
            ...self::BASELINE_ENV,
        ];
    }
}
