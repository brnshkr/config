<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Composer\InstalledVersions;
use JsonException;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Path;

use function array_all;
use function array_any;
use function array_diff;
use function array_diff_key;
use function array_filter;
use function array_find;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function getenv;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function mb_trim;
use function pathinfo;
use function preg_replace;
use function reset;
use function sprintf;
use function Symfony\Component\String\s;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * @internal
 */
final class ComposerJson
{
    private const int INDENT = 2;

    private const array ALLOWED_PACKAGE_DIFFERENCES = [
        'composer/composer',
        'ext-ctype',
        'ext-date',
        'ext-dom',
        'ext-filter',
        'ext-hash',
        'ext-iconv',
        'ext-libxml',
        'ext-openssl',
        'ext-pcre',
        'ext-phar',
        'ext-reflection',
        'ext-simplexml',
        'ext-tokenizer',
        'ext-xml',
        'ext-xmlwriter',
        'pestphp/pest',
        'phpunit/phpunit',
        'spatie/phpunit-snapshot-assertions',
    ];

    public readonly string $lockFilePath;

    /**
     * @var array<array-key, mixed>
     */
    private ?array $data = null;

    /**
     * @var ?list<array{
     *     root: array{
     *         name: string,
     *         pretty_version: string,
     *         version: string,
     *         reference: string|null,
     *         type: string,
     *         install_path: string,
     *         aliases: array<array-key, string>,
     *         dev: bool,
     *     },
     *     versions: array<string, array{
     *         pretty_version?: string,
     *         version?: string,
     *         reference?: string|null,
     *         type?: string,
     *         install_path?: string,
     *         aliases?: array<array-key, string>,
     *         dev_requirement: bool,
     *         replaced?: array<array-key, string>,
     *         provided?: array<array-key, string>,
     *     }>,
     * }>
     */
    private ?array $rawInstalledVersionData = null;

    /**
     * @throws RuntimeException
     */
    private function __construct(
        public readonly string $path,
    ) {
        $this->lockFilePath = pathinfo($path, PATHINFO_EXTENSION) === 'json'
            // @phpstan-ignore-next-line symplify.forbiddenFuncCall (Avoid using symfony/string since this class is shared by all modules and not all of them rely on it)
            ? (preg_replace('/\.json$/', '.lock', $path) ?: '')
            : $path . '.lock';
    }

    /**
     * @throws RuntimeException
     */
    public static function forThisLibrary(): self
    {
        return new self(__DIR__ . '/../../composer.json');
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function forProjectUsingThisLibrary(): self
    {
        $composer = mb_trim(match (true) {
            is_string($_SERVER['COMPOSER'] ?? null) => $_SERVER['COMPOSER'],
            is_string($_ENV['COMPOSER'] ?? null)    => $_ENV['COMPOSER'],
            default                                 => (string) getenv('COMPOSER'),
        });

        if ($composer !== '' && is_dir($composer)) {
            throw new RuntimeException(sprintf(
                'The COMPOSER environment variable is set to "%s" which is a directory, this variable should point to a composer.json or be left unset.',
                $composer,
            ));
        }

        $path = $composer ?: './composer.json';
        $path = Path::isAbsolute($path) ? $path : Path::makeAbsolute($path, getcwd() ?: '.');

        return new self($path);
    }

    /**
     * @throws RuntimeException
     */
    public function getPackageFullName(): string
    {
        $data = $this->read();

        return isset($data['name']) && is_string($data['name'])
            ? $data['name']
            : throw new RuntimeException('Failed to read full package name from composer.json file.');
    }

    /**
     * @throws RuntimeException
     */
    public function getPackageName(): string
    {
        $packageName = explode('/', $this->getPackageFullName())[1] ?? null;

        return is_string($packageName)
            ? $packageName
            : throw new RuntimeException('Failed to read package name from composer.json file.');
    }

    /**
     * @throws RuntimeException
     */
    public function getPackageOrganization(): string
    {
        return explode('/', $this->getPackageFullName())[0]
            ?: throw new RuntimeException('Failed to read package organization from composer.json file.');
    }

    /**
     * @throws RuntimeException
     */
    public function getPackageVersion(): string
    {
        $data = $this->read();

        return isset($data['version']) && is_string($data['version'])
            ? $data['version']
            : throw new RuntimeException('Failed to read package version from composer.json file.');
    }

    /**
     * @throws RuntimeException
     */
    public function getFirstAutoloadDirectory(): ?string
    {
        $data = $this->read();

        if (!isset($data['autoload'])
            || !is_array($data['autoload'])
            || !isset($data['autoload']['psr-4'])
            || !is_array($data['autoload']['psr-4'])) {
            return null;
        }

        $firstAutoloadDir = reset($data['autoload']['psr-4']);

        return is_string($firstAutoloadDir)
            ? $firstAutoloadDir
            : null;
    }

    /**
     * @return array<string, string>
     *
     * @throws RuntimeException
     */
    public function getRequires(): array
    {
        $data = $this->read();

        /**
         * @var array<string, string> $requires
         */
        $requires = isset($data['require'])
            && is_array($data['require'])
            && array_all($data['require'], static fn (mixed $key, mixed $value): bool => is_string($key) && is_string($value))
            ? $data['require']
            : [];

        return $requires;
    }

    /**
     * @return array<string, string>
     *
     * @throws RuntimeException
     */
    public function getDevRequires(): array
    {
        $data = $this->read();

        /**
         * @var array<string, string> $devRequires
         */
        $devRequires = isset($data['require-dev'])
            && is_array($data['require-dev'])
            && array_all($data['require-dev'], static fn (mixed $key, mixed $value): bool => is_string($key) && is_string($value))
            ? $data['require-dev']
            : [];

        return $devRequires;
    }

    /**
     * @phpstan-return array<string, string>
     *
     * @throws RuntimeException
     */
    public function getVersionConstraintsOfOptionalPackages(): array
    {
        $data       = $this->read();
        $requireDev = (isset($data['require-dev']) && is_array($data['require-dev'])) ? $data['require-dev'] : [];
        $suggests   = (isset($data['suggest']) && is_array($data['suggest'])) ? $data['suggest'] : [];
        $conflicts  = (isset($data['conflict']) && is_array($data['conflict'])) ? $data['conflict'] : [];

        $differences = [
            ...array_keys(array_diff_key($requireDev, $suggests, $conflicts)),
            ...array_keys(array_diff_key($conflicts, $requireDev, $suggests)),
            ...array_keys(array_diff_key($suggests, $conflicts, $requireDev)),
        ];

        $invalidDifferences = array_values(array_filter(
            array_map(
                static fn (int|string $difference): int|string|null => in_array($difference, self::ALLOWED_PACKAGE_DIFFERENCES, true)
                    ? null
                    : $difference,
                $differences,
            ),
            is_string(...),
        ));

        if ($invalidDifferences !== []) {
            throw new RuntimeException(sprintf(
                'Expected only allowed differences in packages but more were found (%s).',
                Str::joinAsQuotedList(array_values(array_diff($invalidDifferences, self::ALLOWED_PACKAGE_DIFFERENCES))),
            ));
        }

        if (array_any($conflicts, static fn (mixed $package, mixed $version): bool => !is_string($package) || !is_string($version))) {
            throw new RuntimeException('Expected conflicts to be an array of strings to strings.');
        }

        /**
         * @var array<string, string> $conflictsCasted
         */
        $conflictsCasted = $conflicts;

        return array_map(
            static fn (string $version): string => s($version)
                ->replaceMatches('/(<|>=)/', static fn (array $matches): string => (isset($matches[0]) && $matches[0] === '<') ? '>=' : '<')
                ->toString(),
            $conflictsCasted,
        );
    }

    /**
     * @phpstan-return list<string>
     *
     * @throws RuntimeException
     */
    public function getInstalledPackages(): array
    {
        $this->rawInstalledVersionData ??= InstalledVersions::getAllRawData();
        $packageFullName = $this->getPackageFullName();

        $data = array_find(
            $this->rawInstalledVersionData,
            static fn (array $installed): bool => $installed['root']['name'] === $packageFullName,
        );

        return array_keys($data['versions'] ?? []);
    }

    /**
     * @throws RuntimeException
     */
    public function getContent(): string
    {
        $data = $this->read();

        try {
            return Json::encode($data, 0);
        } catch (JsonException $jsonException) {
            throw new RuntimeException(sprintf(
                'Failed to encode composer.json data to JSON: %s',
                $jsonException->getMessage(),
            ), $jsonException->getCode(), $jsonException);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function setContent(string $content): void
    {
        $previousData = $this->data;

        $this->read($content);

        try {
            $data = Json::encode($this->data);
        } catch (JsonException $jsonException) {
            $this->data = $previousData;

            throw new RuntimeException(sprintf(
                'Failed to encode composer.json data to JSON: %s',
                $jsonException->getMessage(),
            ), $jsonException->getCode(), $jsonException);
        }

        $data = s($data)
            ->replaceMatches(
                fromRegexp: '/^ {4,}/m',
                to: static fn (array $matches): string => s(' ')
                    ->repeat((int) (s(isset($matches[0]) && is_string($matches[0]) ? $matches[0] : '')->length() / 4 * self::INDENT))
                    ->toString(),
            )
            ->append(PHP_EOL)
            ->toString()
        ;

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/finder since this class is shared by all modules and not all of them rely on it)
        if (file_put_contents($this->path, $data) === false) {
            $this->data = $previousData;

            throw new RuntimeException(sprintf(
                'Failed to write composer.json content to file "%s".',
                $this->path,
            ));
        }

        $this->data = null;

        $this->read();
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private function read(?string $stringToRead = null): array
    {
        if ($stringToRead === null && $this->data !== null) {
            return $this->data;
        }

        try {
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/finder since this class is shared by all modules and not all of them rely on it)
            $this->data = Json::decode($stringToRead ?? file_get_contents($this->path) ?: '[]');
        } catch (JsonException $jsonException) {
            throw new RuntimeException(sprintf(
                'Failed to read composer.json file at path "%s": %s',
                $this->path,
                $jsonException->getMessage(),
            ), $jsonException->getCode(), $jsonException);
        }

        return $this->data;
    }
}
