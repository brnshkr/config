<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Override;
use PhpCsFixer\Config as PhpCsFixerConfig;
use Rector\Configuration\RectorConfigBuilder;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use TwigCsFixer\Config\Config as TwigCsFixerConfig;
use TwigCsFixer\Ruleset\Ruleset;

use function array_key_exists;
use function array_keys;
use function array_map;
use function get_debug_type;
use function is_array;
use function is_iterable;
use function is_object;
use function iterator_to_array;
use function max;
use function sprintf;

/**
 * @internal Brnshkr\Config\Composer
 *
 * @phpstan-import-type ModuleName from Module
 */
final class PrintModuleConfigCommand extends AbstractCommand
{
    /**
     * @phpstan-var array<ModuleName, array{
     *     defaultConfigFile: non-empty-string,
     *     expectedTypeLabel: non-empty-string,
     * }>
     */
    private const array MODULE_RESOLUTION_MAP = [
        Module::NAME_PHP_CS_FIXER => [
            'defaultConfigFile' => 'conf/php-cs-fixer.dist.php',
            'expectedTypeLabel' => 'instance of ' . PhpCsFixerConfig::class,
        ],
        Module::NAME_PHP_STAN => [
            'defaultConfigFile' => 'conf/phpstan.dist.php',
            'expectedTypeLabel' => 'array',
        ],
        Module::NAME_RECTOR => [
            'defaultConfigFile' => 'conf/rector.dist.php',
            'expectedTypeLabel' => 'instance of ' . RectorConfigBuilder::class,
        ],
        Module::NAME_TWIG_CS_FIXER => [
            'defaultConfigFile' => 'conf/twig-cs-fixer.dist.php',
            'expectedTypeLabel' => 'instance of ' . TwigCsFixerConfig::class,
        ],
    ];

    private Filesystem $filesystem;

    private ?string $outputFilePath = null;

    private bool $isForceEnabled = false;

    #[Override]
    protected function getDescriptionTemplate(): string
    {
        return 'Prints the resolved configuration as JSON for any supported {{ package_full_name }} module';
    }

    #[Override]
    protected function wrappedInitialize(): void
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function wrappedConfigure(): void
    {
        $this
            ->addArgument('module', InputArgument::OPTIONAL, 'The module to print the config of <fg=yellow>(' . Str::joinAsQuotedList(array_keys(Module::MAP), 'disjunction') . ')</fg=yellow>. May be omitted when --path is given, in which case the module is auto-detected from the value returned by the config file.')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to the config file to load. Defaults to <fg=yellow>./conf/{module}.dist.php</fg=yellow> when omitted; required when no module is given.')
            ->addOption('output-file', 'o', InputOption::VALUE_REQUIRED, 'Write the JSON to this file instead of stdout. When the target file exists, --force overwrites without prompting.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'When used with --output-file, overwrite an existing target without asking for confirmation.')
        ;
    }

    /**
     * @return self::SUCCESS
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws LogicException
     * @throws RuntimeException
     */
    #[Override]
    protected function wrappedExecute(): int
    {
        $module         = $this->getOptionalStringArgument('module');
        $path           = $this->getOptionalStringOption('path');
        $outputFilePath = $this->getOptionalStringOption('output-file');
        $isForceEnabled = $this->isBoolOptionEnabled('force');

        if ($module !== null && !array_key_exists($module, self::MODULE_RESOLUTION_MAP)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown module "%s". Allowed modules are: %s.',
                $module,
                Str::joinAsQuotedList(array_keys(self::MODULE_RESOLUTION_MAP)),
            ));
        }

        if ($module === null && $path === null) {
            throw new InvalidArgumentException(
                'Either the "module" argument or the --path option must be provided.',
            );
        }

        $resolvedPath = $path ?? self::MODULE_RESOLUTION_MAP[$module]['defaultConfigFile'];
        $absolutePath = Str::toAbsolutePath($this->getCwd() . '/', $resolvedPath);

        if (!$this->filesystem->exists($absolutePath)) {
            throw new RuntimeException(sprintf(
                'Config file "%s" does not exist.',
                $absolutePath,
            ));
        }

        $rawConfig = include $absolutePath;
        $module ??= self::detectModule($rawConfig);

        if ($module === null) {
            throw new RuntimeException(sprintf(
                'Could not auto-detect the module from the config file "%s". Got %s. Pass the "module" argument explicitly.',
                $absolutePath,
                get_debug_type($rawConfig),
            ));
        }

        $this->outputFilePath = $outputFilePath;
        $this->isForceEnabled = $isForceEnabled;

        $configArray = match ($module) {
            Module::NAME_PHP_CS_FIXER  => self::normalizeForPhpCsFixer($rawConfig, $absolutePath),
            Module::NAME_PHP_STAN      => self::normalizeForPhpStan($rawConfig, $absolutePath),
            Module::NAME_RECTOR        => self::normalizeForRector($rawConfig, $absolutePath),
            Module::NAME_TWIG_CS_FIXER => self::normalizeForTwigCsFixer($rawConfig, $absolutePath),
        };

        $this->writeOutput($configArray);

        return self::SUCCESS;
    }

    /**
     * @return ?ModuleName
     */
    private static function detectModule(mixed $rawConfig): ?string
    {
        return match (true) {
            $rawConfig instanceof PhpCsFixerConfig    => Module::NAME_PHP_CS_FIXER,
            $rawConfig instanceof RectorConfigBuilder => Module::NAME_RECTOR,
            $rawConfig instanceof TwigCsFixerConfig   => Module::NAME_TWIG_CS_FIXER,
            is_array($rawConfig)                      => Module::NAME_PHP_STAN,
            default                                   => null,
        };
    }

    /**
     * @return array<non-empty-string, mixed>
     *
     * @throws RuntimeException
     */
    private static function normalizeForPhpCsFixer(mixed $rawConfig, string $path): array
    {
        if (!$rawConfig instanceof PhpCsFixerConfig) {
            throw self::createTypeMismatchException(Module::NAME_PHP_CS_FIXER, $rawConfig, $path);
        }

        return self::normalizeObjectProperties($rawConfig);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private static function normalizeForPhpStan(mixed $rawConfig, string $path): array
    {
        if (!is_array($rawConfig)) {
            throw self::createTypeMismatchException(Module::NAME_PHP_STAN, $rawConfig, $path);
        }

        if (array_key_exists('parameters', $rawConfig) && is_array($rawConfig['parameters'])) {
            unset($rawConfig['parameters']['editorUrl']);
        }

        return $rawConfig;
    }

    /**
     * @return array<non-empty-string, mixed>
     *
     * @throws RuntimeException
     */
    private static function normalizeForRector(mixed $rawConfig, string $path): array
    {
        if (!$rawConfig instanceof RectorConfigBuilder) {
            throw self::createTypeMismatchException(Module::NAME_RECTOR, $rawConfig, $path);
        }

        $configArray              = self::normalizeObjectProperties($rawConfig);
        $configArray['editorUrl'] = null;

        return $configArray;
    }

    /**
     * @return array<non-empty-string, mixed>
     *
     * @throws RuntimeException
     */
    private static function normalizeForTwigCsFixer(mixed $rawConfig, string $path): array
    {
        if (!$rawConfig instanceof TwigCsFixerConfig) {
            throw self::createTypeMismatchException(Module::NAME_TWIG_CS_FIXER, $rawConfig, $path);
        }

        return self::normalizeObjectProperties($rawConfig);
    }

    /**
     * @param ModuleName $module
     */
    private static function createTypeMismatchException(string $module, mixed $rawConfig, string $path): RuntimeException
    {
        return new RuntimeException(sprintf(
            'Config file "%s" returned %s but module "%s" expects %s.',
            $path,
            get_debug_type($rawConfig),
            $module,
            self::MODULE_RESOLUTION_MAP[$module]['expectedTypeLabel'],
        ));
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    private static function normalizeObjectProperties(object $rawConfig): array
    {
        $reflectionClass = new ReflectionClass($rawConfig);
        $configArray     = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (!Str::isNonDecimalIntString($name)) {
                continue;
            }

            $configArray[$name] = self::normalizePropertyValue($name, $reflectionProperty->getValue($rawConfig));
        }

        return $configArray;
    }

    /**
     * @param non-empty-string $name
     */
    private static function normalizePropertyValue(string $name, mixed $value): mixed
    {
        if ($name === 'finder' && $value instanceof Finder) {
            return array_keys([...$value]);
        }

        if ($value instanceof Ruleset) {
            $value = $value->getRules();
        }

        if (is_iterable($value)) {
            return array_map(
                static fn (mixed $item): mixed => is_object($item) ? $item::class : $item,
                iterator_to_array($value),
            );
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $configArray
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    private function writeOutput(array $configArray): void
    {
        $json = Str::replaceMatches(
            Json::encode($configArray),
            '/^ +/m',
            static fn (array $matches): string => Str::repeat(' ', max(0, (int) (Str::length($matches[0] ?? '') / 2))),
        );

        if ($this->outputFilePath === null) {
            $this->console->writeRaw($json);

            return;
        }

        $doOverwrite = !$this->filesystem->exists($this->outputFilePath)
            || $this->isForceEnabled
            || $this->console->isConfirmed(sprintf(
                'File "%s" already exists. Do you want to overwrite it?',
                $this->outputFilePath,
            ));

        if ($doOverwrite) {
            $this->filesystem->dumpFile($this->outputFilePath, $json);
            $this->console->writeInfo(sprintf('Dumped config to "%s".', $this->outputFilePath));
        }
    }
}
