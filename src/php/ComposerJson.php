<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\ComposerJsonTest;
use Composer\InstalledVersions;
use JsonException;
use RuntimeException;

use function array_all;
use function array_any;
use function array_diff;
use function array_filter;
use function array_find;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function getenv;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function realpath;
use function reset;
use function sprintf;
use function Symfony\Component\String\s;

use const PHP_EOL;

/**
 * @internal
 *
 * @see ComposerJsonTest
 */
final class ComposerJson
{
    private const int INDENT = 2;

    private const array AUTOLOAD_SECTIONS = ['autoload', 'autoload-dev'];

    private const array PREFIX_STANDARDS = ['psr-4', 'psr-0'];

    /**
     * @phpstan-var array<non-empty-string, non-empty-list<non-empty-string>>
     */
    private const array HOST_PROVIDED_NAMESPACES = [
        'composer-plugin-api'  => ['Composer'],
        'composer-runtime-api' => [InstalledVersions::class],
    ];

    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array ALLOWED_PACKAGE_DIFFERENCES = [
        'composer/composer',
        'dave-liddament/phpstan-rule-test-helper',
        'ext-ctype',
        'ext-date',
        'ext-dom',
        'ext-fileinfo',
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
        'helgesverre/toon',
        'matesofmate/composer-extension',
        'matesofmate/phpstan-extension',
        'matesofmate/phpunit-extension',
        'pestphp/pest',
        'phpunit/phpunit',
        'sebastian/diff',
        'spatie/phpunit-snapshot-assertions',
        'symfony/ai-mate',
        'symfony/process',
    ];

    /**
     * @var non-empty-string
     */
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

    private static ?self $libraryInstance = null;

    /**
     * @var array<non-empty-string, self>
     */
    private static array $projectInstances = [];

    /**
     * @param non-empty-string $path
     *
     * @throws RuntimeException
     */
    private function __construct(
        public readonly string $path,
    ) {
        $this->lockFilePath = Str::trimSuffix($path, '.json') . '.lock';
    }

    /**
     * @throws RuntimeException
     */
    public static function forThisLibrary(): self
    {
        return self::$libraryInstance ??= new self(__DIR__ . '/../../composer.json');
    }

    /**
     * @param non-empty-string $path
     *
     * @throws RuntimeException
     */
    public static function forPath(string $path): self
    {
        return new self($path);
    }

    /**
     * @throws RuntimeException
     */
    public static function forProjectUsingThisLibrary(): self
    {
        $composer = Str::trim(match (true) {
            is_string($_SERVER['COMPOSER'] ?? null) => $_SERVER['COMPOSER'],
            is_string($_ENV['COMPOSER'] ?? null)    => $_ENV['COMPOSER'],
            default                                 => (string) getenv('COMPOSER'),
        });

        if (!Str::isEmpty($composer) && is_dir($composer)) {
            throw new RuntimeException(sprintf(
                'The COMPOSER environment variable is set to "%s" which is a directory, this variable should point to a composer.json file or be left unset.',
                $composer,
            ));
        }

        $path = Str::isEmpty($composer) ? 'composer.json' : $composer;

        if ($path[0] !== '/') {
            $path = (getcwd() ?: '.') . '/' . $path;
        }

        if (!Str::isNonDecimalIntString($path)) {
            return new self($path);
        }

        self::$projectInstances[$path] ??= new self($path);

        return self::$projectInstances[$path];
    }

    /**
     * @return ?non-empty-string
     *
     * @throws RuntimeException
     */
    public function getPackageFullName(): ?string
    {
        $data = $this->read();

        return isset($data['name']) && is_string($data['name']) && !Str::isEmpty($data['name'])
            ? $data['name']
            : null;
    }

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException
     */
    public function getPackageName(): string
    {
        return (explode('/', $this->getPackageFullName() ?? '')[1] ?? '')
            ?: throw new RuntimeException('Failed to read package name from composer.json file.');
    }

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException
     */
    public function getPackageOrganization(): string
    {
        return explode('/', $this->getPackageFullName() ?? '')[0]
            ?: throw new RuntimeException('Failed to read package organization from composer.json file.');
    }

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException
     */
    public function getPackageVersion(): string
    {
        $data = $this->read();

        return isset($data['version']) && is_string($data['version']) && !Str::isEmpty($data['version'])
            ? $data['version']
            : throw new RuntimeException('Failed to read package version from composer.json file.');
    }

    /**
     * @return ?non-empty-string
     *
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

        return is_string($firstAutoloadDir) && !Str::isEmpty($firstAutoloadDir)
            ? $firstAutoloadDir
            : null;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getRequires(): array
    {
        $data = $this->read();

        /**
         * @var array<non-empty-string, non-empty-string> $requirements
         */
        $requirements = isset($data['require'])
            && is_array($data['require'])
            && array_all($data['require'], static fn (mixed $key, mixed $value): bool => is_string($key) && !Str::isEmpty($key) && is_string($value) && !Str::isEmpty($value))
            ? $data['require']
            : [];

        return $requirements;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getDevRequires(): array
    {
        $data = $this->read();

        /**
         * @var array<non-empty-string, non-empty-string> $devRequirements
         */
        $devRequirements = isset($data['require-dev'])
            && is_array($data['require-dev'])
            && array_all($data['require-dev'], static fn (mixed $key, mixed $value): bool => is_string($key) && !Str::isEmpty($key) && is_string($value) && !Str::isEmpty($value))
            ? $data['require-dev']
            : [];

        return $devRequirements;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getNamespaceMap(): array
    {
        $namespaceMap = [];

        foreach (self::AUTOLOAD_SECTIONS as $section) {
            $namespaceMap = [...$namespaceMap, ...$this->readNamespaceMap($section)];
        }

        return $namespaceMap;
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getDevelopmentNamespaces(): array
    {
        return self::toNamespaces(array_keys($this->readNamespaceMap('autoload-dev')));
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getDevelopmentDirectories(): array
    {
        $directory   = Str::trimSuffix($this->path, 'composer.json');
        $directories = [];

        foreach ($this->readNamespaceMap('autoload-dev') as $relative) {
            $absolute = realpath($directory . Str::trimSuffix($relative, '/'));

            if ($absolute !== false && is_dir($absolute)) {
                $directories[] = $absolute;
            }
        }

        return $directories
            |> array_unique(...)
            |> array_values(...);
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getDeclaredPackages(): array
    {
        return array_keys(array_merge($this->getRequires(), $this->getDevRequires()));
    }

    /**
     * @return ?non-empty-string
     *
     * @throws RuntimeException
     */
    public function getPackageType(): ?string
    {
        $type = $this->read()['type'] ?? null;

        return is_string($type) && !Str::isEmpty($type) ? $type : null;
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getHostProvidedNamespaces(): array
    {
        $packages   = $this->getRequires();
        $namespaces = [];

        foreach (self::HOST_PROVIDED_NAMESPACES as $package => $provided) {
            $namespaces = isset($packages[$package]) ? [...$namespaces, ...$provided] : $namespaces;
        }

        return $namespaces;
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getNamespacePrefixes(): array
    {
        return array_keys($this->getNamespaceMap());
    }

    /**
     * @return ?non-empty-string
     *
     * @throws RuntimeException
     */
    public function getRootNamespace(): ?string
    {
        return self::toNamespaces(array_keys($this->readNamespaceMap('autoload')))[0] ?? null;
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getDevelopmentOnlyPackageNamespaces(): array
    {
        $developmentOnly = $this->getDevelopmentOnlyPackages();
        $shipped         = array_values(array_diff($this->getInstalledPackageNames(), $this->getDevelopmentPackageNames()));

        return self::withoutShippedNamespaces(
            $this->readPackageNamespaces($developmentOnly),
            $this->readPackageNamespaces($shipped),
        );
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getVersionConstraintsOfOptionalPackages(): array
    {
        $data       = $this->read();
        $requireDev = (isset($data['require-dev']) && is_array($data['require-dev'])) ? $data['require-dev'] : [];
        $suggests   = (isset($data['suggest']) && is_array($data['suggest'])) ? $data['suggest'] : [];
        $conflicts  = (isset($data['conflict']) && is_array($data['conflict'])) ? $data['conflict'] : [];

        $differences = array_diff(
            array_keys(array_merge($requireDev, $suggests, $conflicts)),
            array_keys(array_intersect_key($requireDev, $suggests, $conflicts)),
        );

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

        if (array_any($conflicts, static fn (mixed $package, mixed $version): bool => !is_string($package) || Str::isEmpty($package) || !is_string($version) || Str::isEmpty($version))) {
            throw new RuntimeException('Expected conflicts to be an array of non-empty strings to non-empty strings.');
        }

        /**
         * @var array<non-empty-string, non-empty-string> $conflictsCasted
         */
        $conflictsCasted = $conflicts;

        return array_map(
            static function (string $version): string {
                $flipped = s($version)
                    ->replaceMatches('/<|>=/', static fn (array $matches): string => (isset($matches[0]) && $matches[0] === '<') ? '>=' : '<')
                    ->toString()
                ;

                return Str::isEmpty($flipped)
                    ? throw new RuntimeException(sprintf('Flipped version constraint for "%s" is empty.', $version))
                    : $flipped;
            },
            $conflictsCasted,
        );
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    public function getInstalledPackages(): array
    {
        $this->rawInstalledVersionData ??= InstalledVersions::getAllRawData();
        $packageFullName = $this->getPackageFullName() ?? '__root__';

        $data = array_find(
            $this->rawInstalledVersionData,
            static fn (array $installed): bool => $installed['root']['name'] === $packageFullName,
        );

        return array_values(array_filter(
            array_keys($data['versions'] ?? []),
            static fn (string $name): bool => !Str::isEmpty($name),
        ));
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
     * @param list<non-empty-string> $namespaces
     * @param list<non-empty-string> $shipped
     *
     * @return list<non-empty-string>
     */
    private static function withoutShippedNamespaces(array $namespaces, array $shipped): array
    {
        return array_values(array_filter(
            $namespaces,
            static fn (string $namespace): bool => !array_any(
                $shipped,
                static fn (string $shippedNamespace): bool => self::intersects($namespace, $shippedNamespace),
            ),
        ));
    }

    private static function intersects(string $namespace, string $other): bool
    {
        if ($namespace === $other) {
            return true;
        }

        if (s($namespace)->startsWith($other . '\\')) {
            return true;
        }

        return s($other)->startsWith($namespace . '\\');
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function getDevelopmentOnlyPackages(): array
    {
        $data     = $this->read();
        $suggests = (isset($data['suggest']) && is_array($data['suggest'])) ? $data['suggest'] : [];
        $declared = array_unique([...array_keys($this->getDevRequires()), ...$this->getDevelopmentPackageNames()]);

        return array_values(array_diff($declared, array_keys($suggests)));
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function getDevelopmentPackageNames(): array
    {
        $installed = $this->readInstalled();
        $names     = $installed['dev-package-names'] ?? [];

        return self::toNonEmptyStrings(is_array($names) ? $names : []);
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function getInstalledPackageNames(): array
    {
        return self::toNonEmptyStrings(array_map(
            static fn (array $package): mixed => $package['name'] ?? null,
            $this->readInstalledPackages(),
        ));
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<non-empty-string>
     */
    private static function toNonEmptyStrings(array $values): array
    {
        $strings = [];

        foreach ($values as $value) {
            if (is_string($value) && !Str::isEmpty($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }

    /**
     * @param list<non-empty-string> $packages
     *
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function readPackageNamespaces(array $packages): array
    {
        return [
            ...$this->readDeclaredPackageNamespaces($packages),
            ...$this->readClassmappedPackageNamespaces($packages),
        ]
            |> array_unique(...)
            |> array_values(...);
    }

    /**
     * @param list<non-empty-string> $packages
     *
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function readDeclaredPackageNamespaces(array $packages): array
    {
        $namespaces = [];

        foreach ($this->readInstalledPackages() as $package) {
            if (in_array($package['name'] ?? null, $packages, true)) {
                $namespaces = [...$namespaces, ...self::readAutoloadNamespaces($package)];
            }
        }

        return $namespaces;
    }

    /**
     * @param array<array-key, mixed> $package
     *
     * @return list<non-empty-string>
     */
    private static function readAutoloadNamespaces(array $package): array
    {
        $autoload = $package['autoload'] ?? null;

        if (!is_array($autoload)) {
            return [];
        }

        $prefixes = [];

        foreach (self::PREFIX_STANDARDS as $standard) {
            $declared = $autoload[$standard] ?? null;
            $prefixes = [...$prefixes, ...(is_array($declared) ? array_keys($declared) : [])];
        }

        return self::toNamespaces($prefixes);
    }

    /**
     * @param array<array-key, mixed> $prefixes
     *
     * @return list<non-empty-string>
     */
    private static function toNamespaces(array $prefixes): array
    {
        $namespaces = [];

        foreach ($prefixes as $prefix) {
            $namespace = is_string($prefix) ? Str::trimSuffix($prefix, '\\') : '';

            if (!Str::isEmpty($namespace)) {
                $namespaces[] = $namespace;
            }
        }

        return $namespaces;
    }

    /**
     * @param list<non-empty-string> $packages
     *
     * @return list<non-empty-string>
     *
     * @throws RuntimeException
     */
    private function readClassmappedPackageNamespaces(array $packages): array
    {
        $vendorDirectory = $this->getVendorDirectory();
        $classmapPath    = $vendorDirectory . '/composer/autoload_classmap.php';

        if (!is_file($classmapPath) || !is_readable($classmapPath)) {
            return [];
        }

        $classmap   = include $classmapPath;
        $namespaces = [];

        foreach (is_array($classmap) ? $classmap : [] as $className => $path) {
            if (!is_string($className)) {
                continue;
            }

            if (!is_string($path)) {
                continue;
            }

            $segments = explode('/', s($path)->after($vendorDirectory . '/')->toString());

            if (!in_array($segments[0] . '/' . ($segments[1] ?? ''), $packages, true)) {
                continue;
            }

            $namespace = s($className)->containsAny('\\') ? s($className)->before('\\')->toString() : '';

            if (!Str::isEmpty($namespace)) {
                $namespaces[] = $namespace;
            }
        }

        return $namespaces;
    }

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException
     */
    private function getVendorDirectory(): string
    {
        $data       = $this->read();
        $config     = (isset($data['config']) && is_array($data['config'])) ? $data['config'] : [];
        $configured = $config['vendor-dir'] ?? null;
        $directory  = is_string($configured) && !Str::isEmpty($configured) ? $configured : 'vendor';

        return Str::trimSuffix($this->path, 'composer.json') . $directory;
    }

    /**
     * @return list<array<array-key, mixed>>
     *
     * @throws RuntimeException
     */
    private function readInstalledPackages(): array
    {
        $packages = $this->readInstalled()['packages'] ?? [];

        if (!is_array($packages)) {
            return [];
        }

        return array_values(array_filter($packages, is_array(...)));
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private function readInstalled(): array
    {
        $installedPath = $this->getVendorDirectory() . '/composer/installed.json';

        return is_file($installedPath) && is_readable($installedPath)
            ? $this->readJsonFile($installedPath)
            : [];
    }

    /**
     * @param non-empty-string $section
     *
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws RuntimeException
     */
    private function readNamespaceMap(string $section): array
    {
        $data         = $this->read();
        $namespaceMap = [];

        foreach (self::PREFIX_STANDARDS as $standard) {
            if (!isset($data[$section])) {
                continue;
            }

            if (!is_array($data[$section])) {
                continue;
            }

            if (!isset($data[$section][$standard])) {
                continue;
            }

            if (!is_array($data[$section][$standard])) {
                continue;
            }

            foreach ($data[$section][$standard] as $namespacePrefix => $directory) {
                $directory = is_array($directory) ? reset($directory) : $directory;

                if (!is_string($namespacePrefix)) {
                    continue;
                }

                if (Str::isEmpty($namespacePrefix)) {
                    continue;
                }

                if (!Str::isNonDecimalIntString($namespacePrefix)) {
                    continue;
                }

                if (!is_string($directory)) {
                    continue;
                }

                if (Str::isEmpty($directory)) {
                    continue;
                }

                $namespaceMap[$namespacePrefix] ??= $directory;
            }
        }

        return $namespaceMap;
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

        $this->data = $stringToRead === null
            ? $this->readJsonFile($this->path)
            : $this->decodeJson($stringToRead, $this->path);

        return $this->data;
    }

    /**
     * @param non-empty-string $path
     *
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private function readJsonFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/finder since this class is shared by all modules and not all of them rely on it)
        return $this->decodeJson(file_get_contents($path) ?: '[]', $path);
    }

    /**
     * @param non-empty-string $path
     *
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private function decodeJson(string $contents, string $path): array
    {
        try {
            return Json::decode($contents);
        } catch (JsonException $jsonException) {
            throw new RuntimeException(sprintf(
                'Failed to read composer.json file at path "%s": %s',
                $path,
                $jsonException->getMessage(),
            ), $jsonException->getCode(), $jsonException);
        }
    }
}
