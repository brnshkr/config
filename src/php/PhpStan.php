<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\PhpStan\ProjectKernel;
use Brnshkr\Config\PhpStan\Rule\ApiOrInternalTagRule;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\BoolishPrefixRule;
use Brnshkr\Config\PhpStan\Rule\InterfaceSuffixRule;
use Brnshkr\Config\PhpStan\Rule\InternalExposureRule;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsTagRule;
use Brnshkr\Config\PhpStan\Rule\NamedArgumentsUsageRule;
use Brnshkr\Config\PhpStan\Rule\PublicApiDocumentationRule;
use Brnshkr\Config\PhpStan\Rule\ResolvableDocReferenceRule;
use Brnshkr\Config\PhpStan\Rule\ServiceArgumentBindingRule;
use Brnshkr\Config\PhpStan\ThrowTypeExtension\FileFinderThrowTypeExtension;
use Brnshkr\Config\Tests\PhpStanTest;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Composer\InstalledVersions;
use DateTimeImmutable;
use InvalidArgumentException;
use OutOfBoundsException;
use PhpCsFixer\Finder as PhpCsFixerFinder;
use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo as SymfonySplFileInfo;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\String\AbstractString;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symplify\PHPStanRules\Rules as SymplifyPhpStanRules;
use Throwable;

use function array_any;
use function array_diff;
use function array_filter;
use function array_find;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function class_exists;
use function dirname;
use function explode;
use function get_debug_type;
use function getcwd;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function iterator_to_array;
use function ksort;
use function serialize;
use function sprintf;
use function Symfony\Component\String\s;

// @codeCoverageIgnoreStart
// NOTICE: Ignored so a random test is not charged with this file-level statement
Module::warnMissingPackages(Module::MODULE_PHP_STAN);

// @phpstan-ignore symplify.forbiddenFuncCall (Guards the class declaration so this self-returning config can be safely required more than once, e.g. via PHPStan's 'includes')
if (class_exists(PhpStan::class)) {
    return PhpStan::getConfig();
}

// @codeCoverageIgnoreEnd

/**
 * Builds the @brnshkr PHPStan configuration through a chainable, opt-in API.
 *
 * {@see self::getConfig()} returns the project-wide baseline — max level, strict exception
 * checking, this package's custom rules, editor-URL handling — and downstream projects layer
 * their own adjustments on top through the fluent setters before calling {@see self::build()}
 * to obtain the final config array.
 *
 * The three `configure*()` helpers ({@see self::configureRule()},
 * {@see self::configureStaticThrowTypeExtension()}, {@see self::configurePhpAtTest()}) produce
 * properly-tagged service definitions so callers do not have to type the PHPStan or PHPat tag
 * strings by hand. Setters for optional integrations (Symfony, Doctrine, Strict-Rules, etc.)
 * throw a {@see RuntimeException} when the corresponding package is not installed, so missing
 * dependencies surface immediately rather than as cryptic errors at analysis time.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/index.md
 *
 * @no-named-arguments
 *
 * @phpstan-type Service array{
 *     class: class-string,
 *     tags?: non-empty-list<non-empty-string>,
 *     arguments?: array<array-key, mixed>,
 * }
 * @phpstan-type RuleService array{
 *     class: class-string,
 *     tags: self::TAG_RULE,
 *     arguments?: array<array-key, mixed>,
 * }
 * @phpstan-type StaticThrowTypeExtensionService array{
 *     class: class-string,
 *     tags: self::TAG_STATIC_THROW_TYPE_EXTENSION,
 *     arguments?: array<array-key, mixed>,
 * }
 * @phpstan-type PhpAtService array{
 *     class: class-string,
 *     tags: self::TAG_PHP_AT_TEST,
 *     arguments?: array<array-key, mixed>,
 * }
 * @phpstan-type Config array{
 *     includes: list<non-empty-string>,
 *     parameters: array<non-empty-string, mixed>,
 *     rules: list<class-string>,
 *     services: list<Service>,
 * }
 *
 * @see PhpStanTest
 */
final class PhpStan
{
    private const string UNCHECKED_EXCEPTIONS_PATH = 'conf/phpstan/unchecked-exceptions.php';

    private const string TYPE_SYMFONY_BUNDLE = 'symfony-bundle';

    private const string LOADER_CONSOLE_APPLICATION = 'console-application';
    private const string LOADER_OBJECT_MANAGER      = 'object-manager';

    private const array EXCLUDE_PATH_GROUPS = ['analyse', 'analyseAndScan'];

    private const array TAG_PHP_AT_TEST                 = ['phpat.test'];
    private const array TAG_RULE                        = ['phpstan.rules.rule'];
    private const array TAG_STATIC_THROW_TYPE_EXTENSION = ['phpstan.dynamicStaticMethodThrowTypeExtension'];

    /**
     * @var array<non-empty-string, list<class-string<Throwable>>>
     */
    private static array $uncheckedExceptionCache = [];

    /**
     * @param Config $config
     */
    private function __construct(
        private array $config = [
            'includes'   => [],
            'parameters' => [],
            'rules'      => [],
            'services'   => [],
        ],
    ) {}

    /**
     * Build the project's baseline PHPStan configuration.
     *
     * Pre-configures level=max, strict exception checking, every custom rule this package ships
     * (see `docs/php/phpstan/rules/`), the {@see FileFinderThrowTypeExtension} dynamic throw-type
     * extension, and editor-URL handling. Conditionally enables strict rules, type-perfect and
     * Symplify rules when their packages are installed.
     *
     * @example
     * ```php
     * // conf/phpstan.php
     * return PhpStan::getConfig();
     * ```
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return Config finalized config array
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws InvalidArgumentException when a resolved path is not a non-empty string
     * @throws RuntimeException when a required optional PHPStan extension is missing
     */
    public static function getConfig(?Finder $finder = null): array
    {
        return self::getBuilder($finder)->build();
    }

    /**
     * A config another file already built, as a builder to add to.
     *
     * This is what a private `conf/phpstan.php` reaches for: the tracked config it includes stays the
     * baseline, and every verb here adds to it rather than replacing what that file configured.
     *
     * @example
     * ```php
     * // conf/phpstan.php
     * $config = include __DIR__ . '/phpstan.dist.php';
     *
     * return PhpStan::from($config)
     *     ->addIgnoredErrors(['ternary.shortNotAllowed'])
     *     ->build()
     * ;
     * ```
     *
     * @param Config $config config to extend
     *
     * @return self the builder, holding that config
     */
    public static function from(array $config): self
    {
        return new self($config);
    }

    /**
     * The same baseline as {@see self::getConfig()}, as a builder to extend before
     * {@see self::build()} finalizes it.
     *
     * @example
     * ```php
     * // conf/phpstan.php
     * return PhpStan::getBuilder()
     *     ->addArchitecture(Architecture::symfony('Acme'))
     *     ->build()
     * ;
     * ```
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return self the builder, pre-configured with the baseline
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws InvalidArgumentException when a resolved path is not a non-empty string
     * @throws RuntimeException when a required optional PHPStan extension is missing
     */
    public static function getBuilder(?Finder $finder = null): self
    {
        $finder ??= new Finder();

        $finder->notPath('config/preload.php');

        $analysisPaths = self::getAnalysisPaths($finder);

        $phpStanConfig = new self()
            ->setLevel('max')
            ->setPaths($analysisPaths['paths'], $analysisPaths['excludedPaths'])
            ->setTemporaryDirectory('.cache/phpstan.cache')
            ->setParameters([
                'editorUrl'                                          => EditorUrl::forPhpStan(),
                'editorUrlTitle'                                     => '%%relFile%%:%%line%%',
                'errorFormat'                                        => Package::PhpStanErrorFormatter->isInstalled() ? 'ticketswap' : null,
                'checkBenevolentUnionTypes'                          => true,
                'checkImplicitMixed'                                 => true,
                'checkMissingCallableSignature'                      => true,
                'checkMissingOverrideMethodAttribute'                => true,
                'checkMissingOverridePropertyAttribute'              => true,
                'checkStrictPrintfPlaceholderTypes'                  => true,
                'checkTooWideReturnTypesInProtectedAndPublicMethods' => true,
                'checkTooWideThrowTypesInProtectedAndPublicMethods'  => false,
                'rememberPossiblyImpureFunctionValues'               => false,
                'reportAlwaysTrueInLastCondition'                    => true,
                'reportAnyTypeWideningInVarTag'                      => true,
                'reportIgnoresWithoutComments'                       => true,
                'reportNonIntStringArrayKey'                         => true,
                'reportPossiblyNonexistentConstantArrayOffset'       => true,
                'reportPossiblyNonexistentGeneralArrayOffset'        => true,
                'reportUnsafeArrayStringKeyCasting'                  => 'prevent',
            ])
            ->setFeatureToggles([
                'checkParameterCastableToNumberFunctions'     => true,
                'reportPreciseLineForUnusedFunctionParameter' => true,
                'stricterFunctionMap'                         => true,
            ])
            ->setExceptions([
                'uncheckedExceptionRegexes' => [
                    '/\\\Exception\\\UnreachableException$/',
                ],
                'check' => [
                    'missingCheckedExceptionInThrows' => true,
                    'throwTypeCovariance'             => true,
                    'tooWideImplicitThrowType'        => true,
                    'tooWideThrowType'                => true,
                ],
            ])
            ->addUncheckedExceptions(self::getRootUncheckedExceptions())
            ->addIgnoredErrors([
                'ternary.shortNotAllowed' => false,
            ])
            ->addRules([
                ApiOrInternalTagRule::class,
                BoolishPrefixRule::class,
                InterfaceSuffixRule::class,
                InternalExposureRule::class,
                NamedArgumentsTagRule::class,
                NamedArgumentsUsageRule::class,
                PublicApiDocumentationRule::class,
                ResolvableDocReferenceRule::class,
                self::configureRule(InternalUsageRule::class, [
                    'allowedCallers' => self::getDevelopmentNamespaceExemptions(),
                ]),
            ])
            ->addServices([
                self::configureStaticThrowTypeExtension(FileFinderThrowTypeExtension::class),
            ])
        ;

        $developmentDirectories = ComposerJson::forProjectUsingThisLibrary()->getDevelopmentDirectories();

        if ($developmentDirectories !== []) {
            $phpStanConfig->addIgnoredErrors([
                [
                    'identifier'      => 'missingType.checkedException',
                    'paths'           => $developmentDirectories,
                    'reportUnmatched' => false,
                ],
            ]);
        }

        if (Package::DependencyInjection->isInstalled()) {
            $phpStanConfig->addRules([ServiceArgumentBindingRule::class]);
        }

        if (Package::PhpStanSymfony->isInstalled()) {
            $symfonyDefaults = self::getSymfonyDefaults();

            if ($symfonyDefaults !== []) {
                $phpStanConfig->setSymfony($symfonyDefaults);
            }
        }

        if (ComposerJson::forProjectUsingThisLibrary()->getPackageType() === self::TYPE_SYMFONY_BUNDLE) {
            $phpStanConfig->addIgnoredErrors([
                [
                    'identifier'      => 'symfony.preferAutowireAttributeOverConfigParam',
                    'reportUnmatched' => false,
                ],
            ]);
        }

        if (Package::PhpStanDoctrine->isInstalled()) {
            $phpStanConfig->setDoctrine(self::getDoctrineDefaults());
        }

        if (Package::PhpStanPhpUnit->isInstalled()) {
            $phpStanConfig->setPhpUnit([
                'reportMissingDataProviderReturnType' => true,
            ]);
        }

        if (Package::PhpStanStrictRules->isInstalled()) {
            $phpStanConfig->setStrictRules([
                'allRules' => true,
            ]);
        }

        if (Package::TypeCoverage->isInstalled()) {
            $phpStanConfig
                ->setTypeCoverage([
                    'constant_type' => 100,
                    'declare'       => 100,
                    'param_type'    => 100,
                    'property_type' => 100,
                    'return_type'   => 100,
                ])
                ->setTypePerfect([
                    'narrow_return'         => true,
                    'no_empty_on_object'    => true,
                    'no_isset_on_object'    => true,
                    'no_mixed'              => true,
                    'no_param_type_removal' => true,
                    'null_over_false'       => true,
                ])
            ;
        }

        if (Package::PhpStanRules->isInstalled()) {
            $phpStanConfig
                ->addRules(self::getSymplifyRules())
                ->setSymplify([
                    'ctor'              => true,
                    'laravelReturnType' => Package::Laravel->isInstalled(),
                    'mocks'             => Package::PhpStanPhpUnit->isInstalled(),
                    'symfonyReturnType' => Package::DependencyInjection->isInstalled(),
                ])
            ;
        }

        return $phpStanConfig;
    }

    /**
     * Finalize the builder into the raw PHPStan config array.
     *
     * @return Config finalized config with the four top-level sections (includes, parameters, rules, services)
     */
    public function build(): array
    {
        return $this->config;
    }

    /**
     * Merge additional `includes` paths into the config, neon or php, keeping their order and
     * dropping duplicates.
     *
     * @param list<non-empty-string> $includePaths absolute or relative paths to merge in
     */
    public function addIncludes(array $includePaths): self
    {
        $this->config['includes'] = [...$this->config['includes'], ...$includePaths]
            |> array_unique(...)
            |> array_values(...);

        return $this;
    }

    /**
     * Replace the `includes` paths outright.
     *
     * @param list<non-empty-string> $includePaths neon or php paths, replacing any already merged in
     */
    public function setIncludes(array $includePaths): self
    {
        $this->config['includes'] = [];

        return $this->addIncludes($includePaths);
    }

    /**
     * Set PHPStan parameters, keeping the ones not named.
     *
     * Nested option maps merge key by key; a list replaces the list already there.
     *
     * @param array<non-empty-string, mixed> $parameters map of parameter name to value
     */
    public function setParameters(array $parameters): self
    {
        foreach ($parameters as $key => $value) {
            $this->config['parameters'][$key] = self::mergeOption($this->config['parameters'][$key] ?? null, $value);
        }

        return $this;
    }

    /**
     * Set a single PHPStan parameter by key.
     *
     * Merges the way {@see self::setParameters()} does, which it calls: a nested option map merges
     * key by key, a list replaces the list already there. Prefer the named setters
     * ({@see self::setLevel()}, {@see self::setPaths()} etc.) where one exists, and
     * {@see self::removeParameter()} first where a nested map has to go rather than merge.
     *
     * @param non-empty-string&non-decimal-int-string $key parameter name as it appears under the `parameters:` section
     * @param mixed $value parameter value
     */
    public function setParameter(string $key, mixed $value): self
    {
        return $this->setParameters([$key => $value]);
    }

    /**
     * Drop one `parameters` key, leaving PHPStan on its own default for it.
     *
     * Removing and setting again is how a nested value is replaced rather than merged into.
     *
     * @param non-empty-string $key parameter key to drop
     */
    public function removeParameter(string $key): self
    {
        return $this->removeParameters([$key]);
    }

    /**
     * Drop `parameters` keys, leaving PHPStan on its own defaults for them.
     *
     * @param list<non-empty-string> $keys parameter keys to drop
     */
    public function removeParameters(array $keys): self
    {
        foreach ($keys as $key) {
            unset($this->config['parameters'][$key]);
        }

        return $this;
    }

    /**
     * Register additional rules with PHPStan.
     *
     * Plain class-string entries land under `rules:`; service-array entries (from
     * {@see self::configureRule()}) land under `services:` with the correct tag.
     *
     * @param list<class-string|RuleService> $rules rule class-strings or pre-configured rule services
     */
    public function addRules(array $rules): self
    {
        $this->config['rules'] = [...$this->config['rules'], ...array_filter($rules, is_string(...))]
            |> array_unique(...)
            |> array_values(...);

        return $this->addServices(array_values(array_filter($rules, is_array(...))));
    }

    /**
     * Replace the registered rules outright.
     *
     * Clears the `rules` list only; services registered by other means are left in place.
     *
     * @param list<class-string|RuleService> $rules rule class-strings or service definitions
     */
    public function setRules(array $rules): self
    {
        $this->config['rules'] = [];

        return $this->addRules($rules);
    }

    /**
     * Replace one registered rule with the same rule under the caller's own arguments.
     *
     * The baseline registers its rules as services, so overriding one means dropping that service
     * and registering it again; this does both.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()
     *     ->replaceRule(InternalUsageRule::class, ['allowedCallers' => ['Acme\\Testing']])
     *     ->build()
     * ;
     * ```
     *
     * @template TNode of Node
     *
     * @param class-string<Rule<TNode>> $rule rule class implementing PHPStan's Rule interface
     * @param array<array-key, mixed> $arguments constructor arguments keyed by parameter name
     */
    public function replaceRule(string $rule, array $arguments = []): self
    {
        return $this
            ->removeRules([$rule])
            ->addRules([self::configureRule($rule, $arguments)])
        ;
    }

    /**
     * Remove previously registered rules by class-string.
     *
     * Removes matching entries from both the `rules:` and `services:` sections.
     *
     * @param list<class-string> $rules rule class-strings to drop
     */
    public function removeRules(array $rules): self
    {
        $this->config['rules'] = array_values(array_filter(
            $this->config['rules'],
            static fn (string $existingRule): bool => !in_array($existingRule, $rules, true),
        ));

        return $this->removeServices($rules);
    }

    /**
     * Register additional service definitions, deduplicating by (class + arguments).
     *
     * Service entries usually come from {@see self::configureRule()},
     * {@see self::configureStaticThrowTypeExtension()} or {@see self::configurePhpAtTest()}.
     *
     * @param list<Service> $services service definitions to merge
     */
    public function addServices(array $services): self
    {
        $mergedServices = [];

        foreach ([...$this->config['services'], ...$services] as $service) {
            $mergedServices[self::getServiceKey($service)] ??= $service;
        }

        $this->config['services'] = array_values($mergedServices);

        return $this;
    }

    /**
     * Replace the registered services outright.
     *
     * @param list<Service> $services service definitions, replacing every service already registered
     */
    public function setServices(array $services): self
    {
        $this->config['services'] = [];

        return $this->addServices($services);
    }

    /**
     * Remove previously registered services by class-string or full service definition.
     *
     * When a class-string is passed, every service with that `class` key is removed.
     * When a service array is passed, removal matches on (class + arguments).
     *
     * @param list<class-string|Service> $services services to drop
     */
    public function removeServices(array $services): self
    {
        $this->config['services'] = array_values(array_filter(
            $this->config['services'],
            static function (array $existingService) use ($services): bool {
                $existingKey = self::getServiceKey($existingService);

                foreach ($services as $service) {
                    if (is_string($service)) {
                        if ($existingService['class'] === $service) {
                            return false;
                        }

                        continue;
                    }

                    if (self::getServiceKey($service) === $existingKey) {
                        return false;
                    }
                }

                return true;
            },
        ));

        return $this;
    }

    /**
     * Set the PHPStan analysis rule level.
     *
     * @param int<0, 10>|'max' $level numeric level 0-10 or the string "max"
     *
     * @see https://phpstan.org/user-guide/rule-levels
     */
    public function setLevel(int|string $level): self
    {
        return $this->setParameter('level', $level);
    }

    /**
     * Set the paths PHPStan analyzes, optionally with exclusions.
     *
     * Exclusions accept either a flat list (treated as `analyseAndScan`) or the structured
     * `{analyse, analyseAndScan}` shape PHPStan understands.
     *
     * @example
     * ```php
     * $builder->setPaths(['src', 'tests'], ['src/legacy']);
     * $builder->setPaths(['src'], ['analyse' => ['src/runtime-only']]);
     * ```
     *
     * @param list<non-empty-string> $paths paths to analyze
     * @param list<non-empty-string>|array{
     *     analyse?: list<non-empty-string>,
     *     analyseAndScan?: list<non-empty-string>,
     * } $excludedPaths Excluded paths (flat list or structured)
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function setPaths(array $paths, array $excludedPaths = []): self
    {
        self::assertPathList($paths);

        $this->setParameter('paths', $paths);

        if ($excludedPaths !== []) {
            $this->setExcludedPaths($excludedPaths);
        }

        return $this;
    }

    /**
     * Add paths to analyze, keeping the ones already configured.
     *
     * @param list<non-empty-string> $paths absolute or relative paths to append
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function addPaths(array $paths): self
    {
        self::assertPathList($paths);

        $existing = $this->config['parameters']['paths'] ?? [];

        return $this->setParameter('paths', self::appendUnique(
            is_array($existing) ? $existing : [],
            $paths,
        ));
    }

    /**
     * Drop paths from the analyzed set.
     *
     * @param list<non-empty-string> $paths paths to stop analyzing
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function removePaths(array $paths): self
    {
        self::assertPathList($paths);

        $existing = $this->config['parameters']['paths'] ?? [];

        if (!is_array($existing)) {
            return $this;
        }

        return $this->setParameter('paths', array_values(array_filter(
            $existing,
            static fn (mixed $path): bool => !in_array($path, $paths, true),
        )));
    }

    /**
     * Set the excluded-paths parameter independently of paths.
     *
     * A flat list is treated as `analyseAndScan`; a structured array is passed through verbatim.
     *
     * @param list<non-empty-string>|array{
     *     analyse?: list<non-empty-string>,
     *     analyseAndScan?: list<non-empty-string>,
     * } $excludedPaths Excluded paths (flat list or structured)
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function setExcludedPaths(array $excludedPaths): self
    {
        self::assertExcludedPathList($excludedPaths);

        return $this->removeParameter('excludePaths')->setParameter(
            'excludePaths',
            array_is_list($excludedPaths)
                ? ['analyseAndScan' => $excludedPaths]
                : $excludedPaths,
        );
    }

    /**
     * Add excluded paths, keeping the ones already configured.
     *
     * A flat list is appended to `analyseAndScan`; a structured value is appended per group.
     *
     * @param list<non-empty-string>|array{
     *     analyse?: list<non-empty-string>,
     *     analyseAndScan?: list<non-empty-string>,
     * } $excludedPaths Excluded paths to append (flat list or structured)
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function addExcludedPaths(array $excludedPaths): self
    {
        self::assertExcludedPathList($excludedPaths);

        $added       = array_is_list($excludedPaths) ? ['analyseAndScan' => $excludedPaths] : $excludedPaths;
        $existing    = $this->config['parameters']['excludePaths'] ?? [];
        $existing    = is_array($existing) ? $existing : [];
        $mergedPaths = [];

        foreach (self::EXCLUDE_PATH_GROUPS as $group) {
            $paths = [
                ...self::toPathList($existing[$group] ?? []),
                ...self::toPathList($added[$group] ?? []),
            ]
                |> array_unique(...)
                |> array_values(...);

            if ($paths !== []) {
                $mergedPaths[$group] = $paths;
            }
        }

        return $mergedPaths === []
            ? $this->removeParameter('excludePaths')
            : $this->setExcludedPaths($mergedPaths);
    }

    /**
     * Drop paths from every exclusion group they appear in.
     *
     * @param list<non-empty-string> $excludedPaths paths to stop excluding
     *
     * @throws InvalidArgumentException when a path is not a non-empty string
     */
    public function removeExcludedPaths(array $excludedPaths): self
    {
        self::assertPathList($excludedPaths);

        $existing  = $this->config['parameters']['excludePaths'] ?? [];
        $existing  = is_array($existing) ? $existing : [];
        $keptPaths = [];

        foreach (self::EXCLUDE_PATH_GROUPS as $group) {
            $paths = array_values(array_filter(
                self::toPathList($existing[$group] ?? []),
                static fn (string $path): bool => !in_array($path, $excludedPaths, true),
            ));

            if ($paths !== []) {
                $keptPaths[$group] = $paths;
            }
        }

        return $keptPaths === []
            ? $this->removeParameter('excludePaths')
            : $this->setExcludedPaths($keptPaths);
    }

    /**
     * Add bootstrap files PHPStan requires before analysis, keeping the ones already there.
     *
     * @param list<non-empty-string> $bootstrapFiles paths to bootstrap PHP files
     */
    public function addBootstrapFiles(array $bootstrapFiles): self
    {
        $existing = $this->config['parameters']['bootstrapFiles'] ?? [];

        return $this->setParameter('bootstrapFiles', self::appendUnique(
            is_array($existing) ? $existing : [],
            $bootstrapFiles,
        ));
    }

    /**
     * Replace the bootstrap files outright.
     *
     * @param list<non-empty-string> $bootstrapFiles paths to the files PHPStan loads before analysis
     */
    public function setBootstrapFiles(array $bootstrapFiles): self
    {
        return $this->removeParameter('bootstrapFiles')->addBootstrapFiles($bootstrapFiles);
    }

    /**
     * Drop bootstrap files, leaving the rest loaded.
     *
     * @param list<non-empty-string> $bootstrapFiles paths to stop loading
     */
    public function removeBootstrapFiles(array $bootstrapFiles): self
    {
        $existing = $this->config['parameters']['bootstrapFiles'] ?? [];

        if (!is_array($existing)) {
            return $this;
        }

        return $this->setParameter('bootstrapFiles', array_values(array_filter(
            $existing,
            static fn (mixed $file): bool => !in_array($file, $bootstrapFiles, true),
        )));
    }

    /**
     * Override the cache directory PHPStan writes to.
     *
     * @param ?non-empty-string $temporaryDirectory cache directory path, or null to use the PHPStan default
     *
     * @see https://phpstan.org/config-reference#caching
     */
    public function setTemporaryDirectory(?string $temporaryDirectory): self
    {
        return $this->setParameter('tmpDir', $temporaryDirectory);
    }

    /**
     * Add ignore patterns for known or expected PHPStan errors, keeping the ones already there.
     *
     * Each entry is either a raw regular-expression string or a structured entry. A structured
     * entry has to carry a `message`, an `identifier`, or both; PHPStan reports one carrying
     * neither. The type cannot state that, because a union of two shapes each requiring one key
     * collapses into a single shape with both optional, so it only rules out an empty entry.
     *
     * An entry is a bare identifier, an `identifier => reportUnmatched` pair, a delimited regular
     * expression matched against the message, or the full array shape.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()->addIgnoredErrors([
     *     'ternary.shortNotAllowed',
     *     'missingType.checkedException' => false,
     *     ['identifier' => 'brnshkr.internalUsage', 'paths' => ['src/Legacy.php']],
     * ]);
     * ```
     *
     * @param array<array-key, non-empty-string|bool|non-empty-array{
     *     message?: non-empty-string,
     *     identifier?: non-empty-string,
     *     count?: positive-int,
     *     path?: non-empty-string,
     *     paths?: list<non-empty-string>,
     *     reportUnmatched?: bool,
     * }>|non-empty-string $ignoredErrors Ignored-error definitions
     *
     * @see https://phpstan.org/user-guide/ignoring-errors#ignoring-in-configuration-file
     */
    public function addIgnoredErrors(string|array $ignoredErrors): self
    {
        $existing = $this->config['parameters']['ignoreErrors'] ?? [];

        return $this->setParameter('ignoreErrors', self::appendUnique(
            is_array($existing) ? $existing : [],
            self::normalizeIgnoredErrors(is_string($ignoredErrors) ? [$ignoredErrors] : $ignoredErrors),
        ));
    }

    /**
     * Replace the ignored errors outright.
     *
     * Takes the same shapes as {@see self::addIgnoredErrors()}, dropping every entry the baseline
     * configured rather than appending to it.
     *
     * @param array<array-key, non-empty-string|bool|non-empty-array{
     *     message?: non-empty-string,
     *     identifier?: non-empty-string,
     *     count?: positive-int,
     *     path?: non-empty-string,
     *     paths?: list<non-empty-string>,
     *     reportUnmatched?: bool,
     * }>|non-empty-string $ignoredErrors Ignored-error definitions, replacing every one already configured
     */
    public function setIgnoredErrors(string|array $ignoredErrors): self
    {
        return $this->removeParameter('ignoreErrors')->addIgnoredErrors($ignoredErrors);
    }

    /**
     * Drop ignored-error entries the baseline configured, by identifier or message.
     *
     * An entry is matched on its `identifier`, or on its `message` when it has none, so the short forms
     * and the full shape are removed the same way.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()->removeIgnoredErrors(['ternary.shortNotAllowed']);
     * ```
     *
     * @param list<non-empty-string> $ignoredErrors identifiers or messages to stop ignoring
     */
    public function removeIgnoredErrors(array $ignoredErrors): self
    {
        $existing = $this->config['parameters']['ignoreErrors'] ?? [];

        return $this->setParameter('ignoreErrors', array_values(array_filter(
            is_array($existing) ? $existing : [],
            static fn (mixed $entry): bool => !in_array(self::getIgnoredErrorKey($entry), $ignoredErrors, true),
        )));
    }

    /**
     * Drop included configuration files, by path.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()->removeIncludes(['vendor/phpstan/phpstan-strict-rules/rules.neon']);
     * ```
     *
     * @param list<non-empty-string> $includePaths paths to stop including
     */
    public function removeIncludes(array $includePaths): self
    {
        $this->config['includes'] = array_values(array_filter(
            $this->config['includes'],
            static fn (string $existing): bool => !in_array($existing, $includePaths, true),
        ));

        return $this;
    }

    /**
     * Toggle PHPStan feature flags by name.
     *
     * @param array<non-empty-string, bool> $featureToggles map of feature-toggle name to enable/disable
     */
    public function setFeatureToggles(array $featureToggles): self
    {
        return $this->setParameters(['featureToggles' => $featureToggles]);
    }

    /**
     * Drop named feature toggles, leaving the options not named.
     *
     * @param list<non-empty-string> $keys toggle names to drop, leaving PHPStan on its own default for them
     */
    public function removeFeatureToggles(array $keys): self
    {
        return $this->removeParameterKeys('featureToggles', $keys);
    }

    /**
     * Configure PHPStan exception-checking parameters.
     *
     * @param array<non-empty-string, mixed> $exceptions exception-handling configuration (uncheckedExceptionRegexes, check, etc.)
     *
     * @see https://phpstan.org/config-reference#exceptions
     */
    public function setExceptions(array $exceptions): self
    {
        return $this->setParameters(['exceptions' => $exceptions]);
    }

    /**
     * Drop named exception-checking keys, leaving the options not named.
     *
     * @param list<non-empty-string> $keys exception-handling keys to drop
     */
    public function removeExceptions(array $keys): self
    {
        return $this->removeParameterKeys('exceptions', $keys);
    }

    /**
     * Add exceptions the analysis must not treat as checked.
     *
     * An entry is a class name — covering the class and everything extending it, with none of the
     * escaping a pattern needs — or a delimited regular expression matched against the class name.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()->addUncheckedExceptions([
     *     UnreachableException::class,
     *     '/\\\\Exception\\\\Unreachable[A-Za-z]*$/',
     * ]);
     * ```
     *
     * @param list<non-empty-string> $exceptions class names or delimited patterns
     *
     * @see https://phpstan.org/config-reference#exceptions
     */
    public function addUncheckedExceptions(array $exceptions): self
    {
        $classes  = [];
        $patterns = [];

        foreach ($exceptions as $exception) {
            if (Str::isRegex($exception)) {
                $patterns[] = $exception;
            } else {
                $classes[] = $exception;
            }
        }

        return $this
            ->appendException('uncheckedExceptionRegexes', $patterns)
            ->appendException('uncheckedExceptionClasses', $classes)
        ;
    }

    /**
     * Replace the unchecked exceptions outright.
     *
     * Clears both the class list and the pattern list before adding, so the declaration is exactly
     * what is passed here.
     *
     * @param list<non-empty-string> $exceptions class names or delimited patterns
     */
    public function setUncheckedExceptions(array $exceptions): self
    {
        return $this
            ->removeParameterKeys('exceptions', ['uncheckedExceptionClasses', 'uncheckedExceptionRegexes'])
            ->addUncheckedExceptions($exceptions)
        ;
    }

    /**
     * Drop unchecked exceptions, leaving the rest declared.
     *
     * An entry is matched as it was given: a class name drops that class, a pattern drops that
     * pattern. Dropping a class name does not drop a pattern that happens to match it.
     *
     * @param list<non-empty-string> $exceptions class names or delimited patterns to stop declaring
     */
    public function removeUncheckedExceptions(array $exceptions): self
    {
        return $this
            ->removeException('uncheckedExceptionRegexes', $exceptions)
            ->removeException('uncheckedExceptionClasses', $exceptions)
        ;
    }

    /**
     * Add the unchecked exceptions a package declares for itself.
     *
     * Whether an exception is checked is part of a package's contract, and PHPStan cannot read it from
     * the package, so without this every consumer restates the list and drifts from it. A package
     * declares its own by shipping `conf/phpstan/unchecked-exceptions.php` returning a list of class
     * names.
     *
     * @example
     * ```php
     * PhpStan::getBuilder()->addUncheckedExceptionsFrom('brnshkr/doxter');
     * ```
     *
     * @param non-empty-string $package the Composer package name to read the declaration from
     *
     * @throws RuntimeException when the package is not installed or declares nothing
     *
     * @see https://phpstan.org/config-reference#exceptions
     */
    public function addUncheckedExceptionsFrom(string $package): self
    {
        return $this->addUncheckedExceptions(self::readUncheckedExceptions($package));
    }

    /**
     * Drop the unchecked exceptions a package declares for itself.
     *
     * The inverse of {@see self::addUncheckedExceptionsFrom()}, for dropping a package's declaration
     * wholesale when the consuming project wants those exceptions checked after all.
     *
     * @param non-empty-string $package the Composer package name to read the declaration from
     *
     * @throws RuntimeException when the package is not installed or declares nothing
     */
    public function removeUncheckedExceptionsFrom(string $package): self
    {
        return $this->removeUncheckedExceptions(self::readUncheckedExceptions($package));
    }

    /**
     * Configure the `phpstan/phpstan-strict-rules` extension, keeping the options not named.
     *
     * @param array<non-empty-string, bool> $strictRules map of strict-rule name to enabled flag
     *
     * @see https://github.com/phpstan/phpstan-strict-rules
     *
     * @throws RuntimeException when `phpstan/phpstan-strict-rules` is not installed
     */
    public function setStrictRules(array $strictRules): self
    {
        Module::warnMissingPackages(Package::PhpStanStrictRules);

        return $this->setParameters(['strictRules' => $strictRules]);
    }

    /**
     * Drop named strict rules, leaving the options not named.
     *
     * @param list<non-empty-string> $keys strict-rule names to drop
     */
    public function removeStrictRules(array $keys): self
    {
        return $this->removeParameterKeys('strictRules', $keys);
    }

    /**
     * Configure the coverage thresholds of `tomasvotruba/type-coverage`, keeping the options not named.
     *
     * @param array<non-empty-string, bool|float|int|null> $options map of type-coverage option name to its value
     *
     * @see https://github.com/TomasVotruba/type-coverage
     *
     * @throws RuntimeException when `tomasvotruba/type-coverage` is not installed
     */
    public function setTypeCoverage(array $options): self
    {
        Module::warnMissingPackages(Package::TypeCoverage);

        return $this->setParameters(['type_coverage' => $options]);
    }

    /**
     * Drop named type-coverage options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys type-coverage option names to drop
     */
    public function removeTypeCoverage(array $keys): self
    {
        return $this->removeParameterKeys('type_coverage', $keys);
    }

    /**
     * Configure the type-perfect rules of `tomasvotruba/type-coverage`, keeping the options not named.
     *
     * @param array<non-empty-string, bool> $options map of type-perfect option name to enabled flag
     *
     * @see https://github.com/TomasVotruba/type-coverage
     *
     * @throws RuntimeException when `tomasvotruba/type-coverage` is not installed
     */
    public function setTypePerfect(array $options): self
    {
        Module::warnMissingPackages(Package::TypeCoverage);

        return $this->setParameters(['type_perfect' => $options]);
    }

    /**
     * Drop named type-perfect options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys type-perfect option names to drop
     */
    public function removeTypePerfect(array $keys): self
    {
        return $this->removeParameterKeys('type_perfect', $keys);
    }

    /**
     * Set the editor-URL template used for clickable error locations.
     *
     * @param EditorUrl::EDITOR_* $editor editor identifier (e.g. `vscode`, `phpstorm`)
     * @param ?non-empty-string $currentWorkingDirectory override for the path prefix; null uses the runtime cwd
     */
    public function setEditor(string $editor, ?string $currentWorkingDirectory = null): self
    {
        return $this->setParameter('editorUrl', EditorUrl::forPhpStan($editor, $currentWorkingDirectory));
    }

    /**
     * Configure the `phpstan/phpstan-symfony` extension, keeping the options not named.
     *
     * @param array<non-empty-string, mixed> $options Symfony-extension options (containerXmlPath, consoleApplicationLoader, etc.)
     *
     * @see https://github.com/phpstan/phpstan-symfony
     *
     * @throws RuntimeException when `phpstan/phpstan-symfony` is not installed
     */
    public function setSymfony(array $options): self
    {
        Module::warnMissingPackages(Package::PhpStanSymfony);

        return $this->setParameters(['symfony' => $options]);
    }

    /**
     * Drop named Symfony extension options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys Symfony option names to drop
     */
    public function removeSymfony(array $keys): self
    {
        return $this->removeParameterKeys('symfony', $keys);
    }

    /**
     * Configure the `phpstan/phpstan-doctrine` extension, keeping the options not named.
     *
     * @param array<non-empty-string, mixed> $options Doctrine-extension options (objectManagerLoader, queryBuilderClass, etc.)
     *
     * @throws RuntimeException when `phpstan/phpstan-doctrine` is not installed
     *
     * @see https://github.com/phpstan/phpstan-doctrine
     */
    public function setDoctrine(array $options): self
    {
        Module::warnMissingPackages(Package::PhpStanDoctrine);

        return $this->setParameters(['doctrine' => $options]);
    }

    /**
     * Drop named Doctrine extension options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys Doctrine option names to drop
     */
    public function removeDoctrine(array $keys): self
    {
        return $this->removeParameterKeys('doctrine', $keys);
    }

    /**
     * Configure the `phpstan/phpstan-phpunit` extension, keeping the options not named.
     *
     * @param array<non-empty-string, mixed> $options PHPUnit-extension options (reportMissingDataProviderReturnType, etc.)
     *
     * @see https://github.com/phpstan/phpstan-phpunit
     *
     * @throws RuntimeException when `phpstan/phpstan-phpunit` is not installed
     */
    public function setPhpUnit(array $options): self
    {
        Module::warnMissingPackages(Package::PhpStanPhpUnit);

        return $this->setParameters(['phpunit' => $options]);
    }

    /**
     * Drop named PHPUnit extension options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys PHPUnit option names to drop
     */
    public function removePhpUnit(array $keys): self
    {
        return $this->removeParameterKeys('phpunit', $keys);
    }

    /**
     * Configure the opt-in rule groups and return type extensions of `symplify/phpstan-rules`, keeping the options not named.
     *
     * @param array<non-empty-string, bool> $options map of symplify option name to enabled flag
     *
     * @see https://github.com/symplify/phpstan-rules
     *
     * @throws RuntimeException when `symplify/phpstan-rules` is not installed
     */
    public function setSymplify(array $options): self
    {
        Module::warnMissingPackages(Package::PhpStanRules);

        return $this->setParameters(['symplify' => $options]);
    }

    /**
     * Drop named symplify options, leaving the options not named.
     *
     * @param list<non-empty-string> $keys symplify option names to drop
     */
    public function removeSymplify(array $keys): self
    {
        return $this->removeParameterKeys('symplify', $keys);
    }

    /**
     * Register PHPat architecture-test services.
     *
     * Accepts either flat service definitions or nested lists (the latter is the shape returned
     * by the {@see Architecture} factory methods), and flattens them before registration.
     *
     * @example
     * ```php
     * $builder->addArchitecture([
     *     ...Architecture::laravel('Acme'),
     *     ...Architecture::doctrine('Acme'),
     * ]);
     * ```
     *
     * @param list<PhpAtService|list<PhpAtService>> $architecture PHPat services or nested service lists
     *
     * @throws InvalidArgumentException when a test class is configured twice with different arguments
     * @throws RuntimeException when `phpat/phpat` is not installed
     */
    public function addArchitecture(array $architecture): self
    {
        Module::warnMissingPackages(Package::PhpAt);

        $services = [];

        foreach ($architecture as $value) {
            if (self::isPhpAtServiceList($value)) {
                $services = [...$services, ...$value];

                continue;
            }

            $services[] = $value;
        }

        self::assertNoConflictingPhpAtTests([...$this->config['services'], ...$services]);

        return $this->addServices($services);
    }

    /**
     * Replace the registered architecture rules outright.
     *
     * Drops every PHPat test already registered before adding, so the architecture is exactly what is
     * passed here. Services that are not PHPat tests are left in place.
     *
     * @param list<PhpAtService|list<PhpAtService>> $architecture PHPat services or nested service lists
     *
     * @throws InvalidArgumentException when a test class is configured twice with different arguments
     * @throws RuntimeException when `phpat/phpat` is not installed
     */
    public function setArchitecture(array $architecture): self
    {
        $this->config['services'] = array_values(array_filter(
            $this->config['services'],
            static fn (array $service): bool => ($service['tags'] ?? []) !== self::TAG_PHP_AT_TEST,
        ));

        return $this->addArchitecture($architecture);
    }

    /**
     * Remove previously registered architecture rules.
     *
     * Mirrors {@see self::addArchitecture()}: accepts class-strings, service definitions, or
     * nested lists of either, and flattens before delegating to {@see self::removeServices()}.
     * Use this to opt out of selected rules from an {@see Architecture} preset.
     *
     * @example
     * ```php
     * $builder->addArchitecture(Architecture::laravel('Acme'));
     * $builder->removeArchitecture([ServiceProviderTest::class]);
     * ```
     *
     * @param list<class-string|PhpAtService|list<class-string|PhpAtService>> $architecture rules to drop
     */
    public function removeArchitecture(array $architecture): self
    {
        $servicesToRemove = [];

        foreach ($architecture as $value) {
            if (self::isNestedPhpAtServiceList($value)) {
                $servicesToRemove = [...$servicesToRemove, ...$value];

                continue;
            }

            $servicesToRemove[] = $value;
        }

        return $this->removeServices($servicesToRemove);
    }

    /**
     * Build a tagged service definition for a custom PHPStan rule with constructor arguments.
     *
     * Use when a rule needs configuration that cannot be expressed as a bare class-string
     * passed to {@see self::addRules()}.
     *
     * @example
     * ```php
     * $builder->addRules([
     *     PhpStan::configureRule(MyRule::class, ['allowedNamespaces' => ['Acme\\']]),
     * ]);
     * ```
     *
     * @template TNode of Node
     *
     * @param class-string<Rule<TNode>> $class rule class implementing PHPStan's Rule interface
     * @param array<array-key, mixed> $arguments constructor arguments keyed by parameter name
     *
     * @return RuleService tagged service definition ready for `services:`
     */
    public static function configureRule(string $class, array $arguments = []): array
    {
        $service = [
            'class' => $class,
            'tags'  => self::TAG_RULE,
        ];

        if ($arguments !== []) {
            $service['arguments'] = $arguments;
        }

        return $service;
    }

    /**
     * Build a tagged service definition for a dynamic static-method throw-type extension.
     *
     * @example
     * ```php
     * PhpStan::configureStaticThrowTypeExtension(FileFinderThrowTypeExtension::class);
     * ```
     *
     * @param class-string<DynamicStaticMethodThrowTypeExtension> $class extension class
     * @param array<array-key, mixed> $arguments constructor arguments keyed by parameter name
     *
     * @return StaticThrowTypeExtensionService tagged service definition ready for `services:`
     */
    public static function configureStaticThrowTypeExtension(string $class, array $arguments = []): array
    {
        $service = [
            'class' => $class,
            'tags'  => self::TAG_STATIC_THROW_TYPE_EXTENSION,
        ];

        if ($arguments !== []) {
            $service['arguments'] = $arguments;
        }

        return $service;
    }

    /**
     * Build a tagged service definition for a PHPat architecture test class.
     *
     * Used internally by {@see Architecture} factory methods and rarely called directly.
     *
     * @example
     * ```php
     * PhpStan::configurePhpAtTest(EntityAndRepositoryTest::class, ['root' => 'Acme']);
     * ```
     *
     * @param class-string $class PHPat `*Test` class
     * @param array<array-key, mixed> $arguments constructor arguments keyed by parameter name
     *
     * @return PhpAtService tagged service definition ready for `services:`
     */
    public static function configurePhpAtTest(string $class, array $arguments = []): array
    {
        $service = [
            'class' => $class,
            'tags'  => self::TAG_PHP_AT_TEST,
        ];

        if ($arguments !== []) {
            $service['arguments'] = $arguments;
        }

        return $service;
    }

    /**
     * Build the project-wide "preferred class" replacement map for Symplify's PreferredClassRule.
     *
     * Always maps `DateTime` to {@see DateTimeImmutable} and `SplFileInfo` to its Symfony Finder
     * equivalent. Conditionally adds entries for `nesbot/carbon` and the php-cs-fixer Finder
     * when those packages are installed.
     *
     * @return non-empty-array<class-string, class-string> map of legacy class to preferred replacement
     *
     * @throws RuntimeException when `symplify/phpstan-rules` is not installed
     */
    public static function getPreferredClassesMap(): array
    {
        Module::warnMissingPackages(Package::PhpStanRules);

        $preferredClassesMap = [
            // NOTICE: Explicit use of 'DateTime' as a string to prevent php-cs-fixer from fixing this to 'DateTimeImmutable'
            'DateTime' => DateTimeImmutable::class,
            // @phpstan-ignore symplify.preferredClass (We need to disable this rules here of course)
            SplFileInfo::class => SymfonySplFileInfo::class,
        ];

        /** @disregard P1009 nesbot/carbon is not a dependency of brnshkr/config */
        // @phpstan-ignore symplify.forbiddenFuncCall (nesbot/carbon is not a dependency of brnshkr/config)
        if (class_exists(Carbon::class)) {
            /** @disregard P1009 nesbot/carbon is not a dependency of brnshkr/config */
            // @phpstan-ignore class.notFound (nesbot/carbon is not a dependency of brnshkr/config)
            $preferredClassesMap[Carbon::class] = CarbonImmutable::class;
        }

        // @phpstan-ignore symplify.preferredClass (See ->), symplify.forbiddenFuncCall (We need to disable both these rules here of course)
        if (class_exists(PhpCsFixerFinder::class)) {
            // @phpstan-ignore symplify.preferredClass (We need to disable this rules here of course)
            $preferredClassesMap[PhpCsFixerFinder::class] = Finder::class;
        }

        return $preferredClassesMap;
    }

    /**
     * Reject a path entry that is not a non-empty string, rather than dropping it silently.
     *
     * @param array<array-key, mixed> $paths
     *
     * @throws InvalidArgumentException when an entry is not a non-empty string
     */
    private static function assertPathList(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path) || Str::isEmpty($path)) {
                throw new InvalidArgumentException(sprintf(
                    'Every path must be a non-empty string, got %s.',
                    is_string($path) ? 'an empty one' : sprintf('"%s"', get_debug_type($path)),
                ));
            }
        }
    }

    /**
     * Reject a path entry in either shape the exclusion setters accept.
     *
     * @param array<array-key, mixed> $excludedPaths
     *
     * @throws InvalidArgumentException when an entry is not a non-empty string
     */
    private static function assertExcludedPathList(array $excludedPaths): void
    {
        if (array_is_list($excludedPaths)) {
            self::assertPathList($excludedPaths);

            return;
        }

        foreach ($excludedPaths as $excludedPath) {
            self::assertPathList(is_array($excludedPath) ? $excludedPath : [$excludedPath]);
        }
    }

    /**
     * Read one exclusion group as a list of paths, whatever shape the config happens to hold.
     *
     * @return list<non-empty-string>
     */
    private static function toPathList(mixed $paths): array
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        if (!is_array($paths)) {
            return [];
        }

        return array_values(array_filter(
            $paths,
            static fn (mixed $path): bool => is_string($path) && $path !== '',
        ));
    }

    private static function getIgnoredErrorKey(mixed $entry): ?string
    {
        if (is_string($entry)) {
            return $entry;
        }

        if (!is_array($entry)) {
            return null;
        }

        $key = $entry['identifier'] ?? $entry['message'] ?? null;

        return is_string($key) ? $key : null;
    }

    /**
     * @param non-empty-string&non-decimal-int-string $key
     * @param list<non-empty-string> $values
     */
    private function appendException(string $key, array $values): self
    {
        if ($values === []) {
            return $this;
        }

        $exceptions = $this->config['parameters']['exceptions'] ?? [];
        $existing   = is_array($exceptions) ? $exceptions[$key] ?? [] : [];

        return $this->setExceptions([
            $key => self::appendUnique(is_array($existing) ? $existing : [], $values),
        ]);
    }

    /**
     * Drop values out of one of the `exceptions` lists.
     *
     * @param non-empty-string&non-decimal-int-string $key the exceptions key holding the list
     * @param list<non-empty-string> $values values to drop out of it
     */
    private function removeException(string $key, array $values): self
    {
        $exceptions = $this->config['parameters']['exceptions'] ?? [];
        $existing   = is_array($exceptions) ? $exceptions[$key] ?? [] : [];

        if (!is_array($existing) || $existing === []) {
            return $this;
        }

        return $this->setExceptions([
            $key => array_values(array_filter(
                $existing,
                static fn (mixed $value): bool => !in_array($value, $values, true),
            )),
        ]);
    }

    /**
     * Drop named keys out of a map-valued `parameters` entry, leaving the rest of the map intact.
     *
     * @param non-empty-string $parameter the parameter holding the map
     * @param list<non-empty-string> $keys keys to drop out of it
     */
    private function removeParameterKeys(string $parameter, array $keys): self
    {
        $existing = $this->config['parameters'][$parameter] ?? null;

        if (!is_array($existing)) {
            return $this;
        }

        foreach ($keys as $key) {
            unset($existing[$key]);
        }

        $this->config['parameters'][$parameter] = $existing;

        return $this;
    }

    /**
     * @param array<array-key, mixed> $ignoredErrors
     *
     * @return list<mixed>
     */
    private static function normalizeIgnoredErrors(array $ignoredErrors): array
    {
        $normalized = [];

        foreach ($ignoredErrors as $key => $entry) {
            if (is_string($key)) {
                $normalized[] = [
                    'identifier'      => $key,
                    'reportUnmatched' => (bool) $entry,
                ];

                continue;
            }

            $normalized[] = is_string($entry) && !Str::isRegex($entry)
                ? ['identifier' => $entry]
                : $entry;
        }

        return $normalized;
    }

    /**
     * @return non-empty-list<class-string|RuleService>
     *
     * @throws RuntimeException
     */
    private static function getSymplifyRules(): array
    {
        return [
            SymplifyPhpStanRules\Complexity\ForbiddenArrayMethodCallRule::class,
            SymplifyPhpStanRules\Complexity\ForeachCeptionRule::class,
            SymplifyPhpStanRules\Complexity\NoArrayMapWithArrayCallableRule::class,
            SymplifyPhpStanRules\Complexity\NoJustPropertyAssignRule::class,
            SymplifyPhpStanRules\Doctrine\NoDoctrineListenerWithoutContractRule::class,
            SymplifyPhpStanRules\Doctrine\NoGetRepositoryOnServiceRepositoryEntityRule::class,
            SymplifyPhpStanRules\Doctrine\NoGetRepositoryOutsideServiceRule::class,
            SymplifyPhpStanRules\Doctrine\NoParentRepositoryRule::class,
            SymplifyPhpStanRules\Doctrine\NoRepositoryCallInDataFixtureRule::class,
            SymplifyPhpStanRules\Doctrine\RequireQueryBuilderOnRepositoryRule::class,
            SymplifyPhpStanRules\Doctrine\RequireServiceRepositoryParentRule::class,
            SymplifyPhpStanRules\Domain\RequireAttributeNamespaceRule::class,
            SymplifyPhpStanRules\Domain\RequireExceptionNamespaceRule::class,
            SymplifyPhpStanRules\Enum\RequireUniqueEnumConstantRule::class,
            SymplifyPhpStanRules\Explicit\ExplicitClassPrefixSuffixRule::class,
            SymplifyPhpStanRules\Explicit\NoMissingVariableDimFetchRule::class,
            SymplifyPhpStanRules\Explicit\NoProtectedClassStmtRule::class,
            SymplifyPhpStanRules\ForbiddenExtendOfNonAbstractClassRule::class,
            SymplifyPhpStanRules\ForbiddenMultipleClassLikeInOneFileRule::class,
            SymplifyPhpStanRules\ForbiddenStaticClassConstFetchRule::class,
            SymplifyPhpStanRules\NoDynamicNameRule::class,
            SymplifyPhpStanRules\NoEntityOutsideEntityNamespaceRule::class,
            SymplifyPhpStanRules\NoGlobalConstRule::class,
            SymplifyPhpStanRules\NoMissnamedDocTagRule::class,
            SymplifyPhpStanRules\NoReferenceRule::class,
            SymplifyPhpStanRules\PHPUnit\NoAssertFuncCallInTestsRule::class,
            SymplifyPhpStanRules\PHPUnit\NoMockObjectAndRealObjectPropertyRule::class,
            SymplifyPhpStanRules\PHPUnit\PublicStaticDataProviderRule::class,
            SymplifyPhpStanRules\PreventParentMethodVisibilityOverrideRule::class,
            SymplifyPhpStanRules\Rector\AvoidFeatureSetAttributeInRectorRule::class,
            SymplifyPhpStanRules\Rector\PreferDirectIsNameRule::class,
            SymplifyPhpStanRules\RequireAttributeNameRule::class,
            SymplifyPhpStanRules\StringFileAbsolutePathExistsRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\AlreadyRegisteredAutodiscoveryServiceRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\NoBundleResourceConfigRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\NoDuplicateArgAutowireByTypeRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\NoDuplicateArgsAutowireByTypeRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\NoServiceSameNameSetClassRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\NoSetClassServiceDuplicationRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\PreferAutowireAttributeOverConfigParamRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\ServicesExcludedDirectoryMustExistRule::class,
            SymplifyPhpStanRules\Symfony\ConfigClosure\TaggedIteratorOverRepeatedServiceCallRule::class,
            SymplifyPhpStanRules\Symfony\FormTypeClassNameRule::class,
            SymplifyPhpStanRules\Symfony\NoAbstractControllerConstructorRule::class,
            SymplifyPhpStanRules\Symfony\NoBareAndSecurityIsGrantedContentsRule::class,
            SymplifyPhpStanRules\Symfony\NoClassLevelRouteRule::class,
            SymplifyPhpStanRules\Symfony\NoConstructorAndRequiredTogetherRule::class,
            SymplifyPhpStanRules\Symfony\NoControllerMethodInjectionRule::class,
            SymplifyPhpStanRules\Symfony\NoFindTaggedServiceIdsCallRule::class,
            SymplifyPhpStanRules\Symfony\NoGetDoctrineInControllerRule::class,
            SymplifyPhpStanRules\Symfony\NoGetInCommandRule::class,
            SymplifyPhpStanRules\Symfony\NoGetInControllerRule::class,
            SymplifyPhpStanRules\Symfony\NoListenerWithoutContractRule::class,
            SymplifyPhpStanRules\Symfony\NoRouteTrailingSlashPathRule::class,
            SymplifyPhpStanRules\Symfony\NoRoutingPrefixRule::class,
            SymplifyPhpStanRules\Symfony\NoServiceAutowireDuplicateRule::class,
            SymplifyPhpStanRules\Symfony\NoStringInGetSubscribedEventsRule::class,
            SymplifyPhpStanRules\Symfony\RequiredOnlyInAbstractRule::class,
            SymplifyPhpStanRules\Symfony\RequireIsGrantedEnumRule::class,
            SymplifyPhpStanRules\Symfony\RequireRouteNameToGenerateControllerRouteRule::class,
            SymplifyPhpStanRules\Symfony\SingleArgEventDispatchRule::class,
            SymplifyPhpStanRules\Symfony\SingleRequiredMethodRule::class,
            SymplifyPhpStanRules\UppercaseConstantRule::class,
            self::configureRule(SymplifyPhpStanRules\ForbiddenNodeRule::class, [
                'forbiddenNodes' => self::getForbiddenNodes(),
            ]),
            self::configureRule(SymplifyPhpStanRules\PreferredClassRule::class, [
                'oldToPreferredClasses' => self::getPreferredClassesMap(),
            ]),
            self::configureRule(SymplifyPhpStanRules\ForbiddenFuncCallRule::class, [
                'forbiddenFunctions' => self::getForbiddenFunctions(),
            ]),
        ];
    }

    /**
     * @return non-empty-list<class-string>
     */
    private static function getForbiddenNodes(): array
    {
        return [
            Node\Expr\Empty_::class,
            Node\Expr\ErrorSuppress::class,
            Node\Expr\PostDec::class,
            Node\Expr\PostInc::class,
            Node\Expr\PreDec::class,
            Node\Expr\PreInc::class,
            Node\InterpolatedStringPart::class,
            Node\Scalar\InterpolatedString::class,
            Node\Stmt\Switch_::class,
        ];
    }

    /**
     * @return non-empty-array<non-empty-string, non-empty-string>
     */
    private static function getForbiddenFunctions(): array
    {
        $forbiddenFunctions = [
            'eval'              => 'Usage of this function is strongly discouraged. If using this function is really the only option, please disable this rule for this line.',
            'compact'           => 'Explicitly assign to keys in the array.',
            'extract'           => 'Explicitly define variables for the entries of the array.',
            'method_exists'     => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'property_exists'   => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'class_exists'      => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'interface_exists'  => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'trait_exists'      => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'enum_exists'       => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'spl_autoload'      => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'spl_autoload_*'    => 'Usage of this function is discouraged. If using this function is really the only option, please disable this rule for this line.',
            'var_dump'          => 'Please remove all debug functions. Use a logger if needed.',
            'dd'                => 'Please remove all debug functions. Use a logger if needed.',
            'dump'              => 'Please remove all debug functions. Use a logger if needed.',
            'debug'             => 'Please remove all debug functions. Use a logger if needed.',
            'file_get_contents' => sprintf('Use "%s::readFile()" instead.', Filesystem::class),
            'file_put_contents' => sprintf('Use "%1$s::dumpFile()" or "%1$s::appendToFile()" instead.', Filesystem::class),
        ];

        // @phpstan-ignore symplify.forbiddenFuncCall (This is the only way to achieve what we need here)
        if (class_exists(AbstractString::class)) {
            $stringFunction = s(AbstractString::class)
                ->beforeLast('\\')
                ->append('\s')
                ->toString()
            ;

            $forbiddenFunctions = [
                ...$forbiddenFunctions,
                'ucfirst'               => sprintf('Use "%s::title()" instead.', $stringFunction),
                'mb_ucfirst'            => sprintf('Use "%s::title()" instead.', $stringFunction),
                'ucwords'               => sprintf('Use "%s::title()" instead.', $stringFunction),
                'str_pad'               => sprintf('Use "%s::{padBoth,padEnd,padStart}()" instead.', $stringFunction),
                'mb_str_pad'            => sprintf('Use "%s::{padBoth,padEnd,padStart}()" instead.', $stringFunction),
                'trim'                  => sprintf('Use "%s::trim()" instead.', $stringFunction),
                'mb_trim'               => sprintf('Use "%s::trim()" instead.', $stringFunction),
                'ltrim'                 => sprintf('Use "%s::trimStart()" instead.', $stringFunction),
                'mb_ltrim'              => sprintf('Use "%s::trimStart()" instead.', $stringFunction),
                'rtrim'                 => sprintf('Use "%s::trimEnd()" instead.', $stringFunction),
                'mb_rtrim'              => sprintf('Use "%s::trimEnd()" instead.', $stringFunction),
                'str_split'             => sprintf('Use "%s::chunk()" instead.', $stringFunction),
                'mb_str_split'          => sprintf('Use "%s::chunk()" instead.', $stringFunction),
                'mb_split'              => sprintf('Use "%s::split()" instead.', $stringFunction),
                'strlen'                => sprintf('Use "%s::length()" instead.', $stringFunction),
                'mb_strlen'             => sprintf('Use "%s::length()" instead.', $stringFunction),
                'strtolower'            => sprintf('Use "%s::lower()" instead.', $stringFunction),
                'mb_strtolower'         => sprintf('Use "%s::lower()" instead.', $stringFunction),
                'strtoupper'            => sprintf('Use "%s::upper()" instead.', $stringFunction),
                'mb_strtoupper'         => sprintf('Use "%s::upper()" instead.', $stringFunction),
                'substr'                => sprintf('Use "%s::slice()" instead.', $stringFunction),
                'mb_substr'             => sprintf('Use "%s::slice()" instead.', $stringFunction),
                'str_contains'          => sprintf('Use "%s::containsAny()" instead.', $stringFunction),
                'str_starts_with'       => sprintf('Use "%s::startsWith()" instead.', $stringFunction),
                'str_ends_with'         => sprintf('Use "%s::endsWith()" instead.', $stringFunction),
                'str_replace'           => sprintf('Use "%s::replace()" instead.', $stringFunction),
                'str_ireplace'          => sprintf('Use "%s::replace()" instead.', $stringFunction),
                'substr_replace'        => sprintf('Use "%s::replace()" instead.', $stringFunction),
                'str_repeat'            => sprintf('Use "%s::repeat()" instead.', $stringFunction),
                'strpos'                => sprintf('Use "%s::indexOf()" instead.', $stringFunction),
                'mb_strpos'             => sprintf('Use "%s::indexOf()" instead.', $stringFunction),
                'stripos'               => sprintf('Use "%s::ignoreCase()->indexOf()" instead.', $stringFunction),
                'mb_stripos'            => sprintf('Use "%s::ignoreCase()->indexOf()" instead.', $stringFunction),
                'strrpos'               => sprintf('Use "%s::indexOfLast()" instead.', $stringFunction),
                'mb_strrpos'            => sprintf('Use "%s::indexOfLast()" instead.', $stringFunction),
                'strripos'              => sprintf('Use "%s::ignoreCase()->indexOfLast()" instead.', $stringFunction),
                'mb_strripos'           => sprintf('Use "%s::ignoreCase()->indexOfLast()" instead.', $stringFunction),
                'strstr'                => sprintf('Use "%s::{after,before}()" instead.', $stringFunction),
                'mb_strstr'             => sprintf('Use "%s::{after,before}()" instead.', $stringFunction),
                'stristr'               => sprintf('Use "%s::ignoreCase()->{after,before}()" instead.', $stringFunction),
                'mb_stristr'            => sprintf('Use "%s::ignoreCase()->{after,before}()" instead.', $stringFunction),
                'strrchr'               => sprintf('Use "%s::afterLast()" instead.', $stringFunction),
                'mb_strrchr'            => sprintf('Use "%s::afterLast()" instead.', $stringFunction),
                'strrev'                => sprintf('Use "%s::reverse()" instead.', $stringFunction),
                'strtr'                 => sprintf('Use "%s::replace()" instead.', $stringFunction),
                'wordwrap'              => sprintf('Use "%s::wordwrap()" instead.', $stringFunction),
                'mb_strwidth'           => sprintf('Use "%s::width()" instead.', $stringFunction),
                'mb_strimwidth'         => sprintf('Use "%s::truncate()" instead.', $stringFunction),
                'preg_match_all'        => sprintf('Use "%s::match()" instead.', $stringFunction),
                'preg_match'            => sprintf('Use "%s::match()" instead.', $stringFunction),
                'preg_replace_callback' => sprintf('Use "%s::replaceMatches()" instead.', $stringFunction),
                'preg_replace'          => sprintf('Use "%s::replaceMatches()" instead.', $stringFunction),
            ];
        }

        /** @disregard P1009 symfony/http-client-contracts is not a dependency of brnshkr/config */
        // @phpstan-ignore symplify.forbiddenFuncCall (symfony/http-client-contracts is not a dependency of brnshkr/config)
        if (interface_exists(HttpClientInterface::class)) {
            $forbiddenFunctions['curl_*'] = sprintf('Use an implementation of "%s" or any alternative HTTP client instead.', HttpClientInterface::class);
        }

        /** @disregard P1009 symfony/serializer is not a dependency of brnshkr/config */
        // @phpstan-ignore symplify.forbiddenFuncCall (symfony/serializer is not a dependency of brnshkr/config)
        if (class_exists(JsonEncoder::class)) {
            $forbiddenFunctions['json_decode'] = sprintf('Use "%s::decode()" instead.', JsonEncoder::class);
            $forbiddenFunctions['json_encode'] = sprintf('Use "%s::encode()" instead.', JsonEncoder::class);
        }

        return $forbiddenFunctions;
    }

    /**
     * @return array{
     *     paths: list<non-empty-string>,
     *     excludedPaths: list<non-empty-string>,
     * }
     *
     * @throws DirectoryNotFoundException
     */
    private static function getAnalysisPaths(Finder $finder): array
    {
        $finderResults = FileFinder::get($finder) |> iterator_to_array(...);
        $analyzedFiles = array_keys($finderResults);
        $directories   = $analyzedFiles |> self::convertFilesToMinimalAnalysisPaths(...);

        if ($directories === []) {
            return [
                'paths'         => [],
                'excludedPaths' => [],
            ];
        }

        $allFilesInDirectories = FileFinder::get(new Finder()->in($directories))
            |> iterator_to_array(...)
            |> array_keys(...);

        $excludedPaths = array_diff($allFilesInDirectories, $analyzedFiles)
            |> array_values(...)
            |> (static fn (array $excludedFiles): array => self::convertFilesToMinimalExcludedPaths($excludedFiles, $analyzedFiles));

        $cwd = (getcwd() ?: '.') . '/';

        return [
            'paths'         => array_map(static fn (string $path): string => Str::toAbsolutePath($cwd, $path), $directories),
            'excludedPaths' => array_map(static fn (string $path): string => Str::toAbsolutePath($cwd, $path), $excludedPaths),
        ];
    }

    /**
     * @param list<non-empty-string> $files
     *
     * @return list<non-empty-string>
     */
    private static function convertFilesToMinimalAnalysisPaths(array $files): array
    {
        return array_map(
            static function (string $file): string {
                $segments = explode('/', $file);

                array_pop($segments);

                return implode('/', $segments);
            },
            $files,
        )
            |> array_unique(...)
            |> (static fn (array $paths): array => array_filter($paths, static fn (string $path): bool => !Str::isEmpty($path)))
            |> array_values(...)
            |> (static fn (array $paths): array => array_filter($paths, static fn (string $path): bool => !array_any(
                $paths,
                static fn (string $ancestor): bool => $ancestor !== $path
                    && Str::startsWith($path, Str::trim($ancestor, '/', 'end') . '/'),
            )))
            |> array_values(...);
    }

    /**
     * @param list<non-empty-string> $excludedFiles
     * @param list<non-empty-string> $analyzedFiles
     *
     * @return list<non-empty-string>
     */
    private static function convertFilesToMinimalExcludedPaths(array $excludedFiles, array $analyzedFiles): array
    {
        $protectedDirectories = array_map(self::getAncestorDirectories(...), $analyzedFiles)
            |> (static fn (array $directories): array => array_merge([], ...$directories))
            |> array_flip(...);

        return array_map(
            static fn (string $excludedFile): string => array_find(
                self::getAncestorDirectories($excludedFile),
                static fn (string $directory): bool => !isset($protectedDirectories[$directory]),
            ) ?: $excludedFile,
            $excludedFiles,
        )
            |> array_unique(...)
            |> array_values(...);
    }

    /**
     * @param non-empty-string $file
     *
     * @return list<non-empty-string>
     */
    private static function getAncestorDirectories(string $file): array
    {
        $segments = explode('/', $file);

        array_pop($segments);

        $directories = [];
        $directory   = '';

        foreach ($segments as $segment) {
            $directory = $directory === '' ? $segment : $directory . '/' . $segment;

            if ($directory !== '') {
                $directories[] = $directory;
            }
        }

        return $directories;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws DirectoryNotFoundException when the cache directory disappears mid-scan
     * @throws RuntimeException when the environment names a kernel class that cannot be located
     */
    private static function getSymfonyDefaults(): array
    {
        $defaults         = [];
        $containerXmlPath = ProjectKernel::locateContainerXml();
        $kernelPath       = ProjectKernel::locate();

        if ($kernelPath !== null) {
            $defaults['consoleApplicationLoader'] = ProjectKernel::getLoaderPath(self::LOADER_CONSOLE_APPLICATION);
        }

        if ($containerXmlPath !== null) {
            $defaults['containerXmlPath'] = $containerXmlPath;
        }

        if ($kernelPath === null && $containerXmlPath !== null) {
            Logger::log('notice', sprintf(
                'A compiled container was found but the kernel class could not be resolved. Set %s to it so console commands can be analyzed.',
                ProjectKernel::CLASS_ENVIRONMENT_VARIABLE,
            ));
        }

        return $defaults;
    }

    /**
     * @return array<non-empty-string, bool|non-empty-string>
     *
     * @throws RuntimeException when the environment names a kernel class that cannot be located
     */
    private static function getDoctrineDefaults(): array
    {
        $defaults = [
            'literalString'              => true,
            'reportDynamicQueryBuilders' => true,
            'reportUnknownTypes'         => true,
        ];

        if (ProjectKernel::locate() !== null) {
            $defaults['objectManagerLoader'] = ProjectKernel::getLoaderPath(self::LOADER_OBJECT_MANAGER);
        }

        return $defaults;
    }

    /**
     * @param list<Service> $services
     *
     * @throws InvalidArgumentException
     */
    private static function assertNoConflictingPhpAtTests(array $services): void
    {
        $keysByClass = [];

        foreach ($services as $service) {
            if (($service['tags'] ?? []) !== self::TAG_PHP_AT_TEST) {
                continue;
            }

            $key = self::getServiceKey($service);

            if (($keysByClass[$service['class']] ?? $key) !== $key) {
                throw new InvalidArgumentException(sprintf(
                    'Architecture test "%s" is configured twice with different arguments. PHPat registers one instance per test class and drops the second without warning, so the two configurations need two test classes.',
                    $service['class'],
                ));
            }

            $keysByClass[$service['class']] = $key;
        }
    }

    /**
     * @param non-empty-string $package
     *
     * @return list<class-string<Throwable>>
     *
     * @throws RuntimeException
     */
    private static function readUncheckedExceptions(string $package): array
    {
        try {
            $installPath = InstalledVersions::getInstallPath($package);
        } catch (OutOfBoundsException $outOfBoundsException) {
            throw new RuntimeException(sprintf('Package "%s" is not installed.', $package), 0, $outOfBoundsException);
        }

        $path = ($installPath ?? '') . '/' . self::UNCHECKED_EXCEPTIONS_PATH;

        if ($installPath === null || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException(sprintf(
                'Package "%s" declares no unchecked exceptions, expected them in "%s".',
                $package,
                self::UNCHECKED_EXCEPTIONS_PATH,
            ));
        }

        return self::readDeclaredExceptions($path);
    }

    /**
     * @param non-empty-string $path
     *
     * @return list<class-string<Throwable>>
     */
    private static function readDeclaredExceptions(string $path): array
    {
        if (array_key_exists($path, self::$uncheckedExceptionCache)) {
            return self::$uncheckedExceptionCache[$path];
        }

        $declared = require $path;

        /**
         * @var list<class-string<Throwable>> $classes
         */
        $classes = is_array($declared) ? array_values(array_filter($declared, self::isNonEmptyString(...))) : [];

        if (Str::isNonDecimalIntString($path)) {
            self::$uncheckedExceptionCache[$path] = $classes;
        }

        return $classes;
    }

    private static function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && !Str::isEmpty($value);
    }

    /**
     * Reads the consuming project's own declaration, so a package never names itself. Only a _dependency's_
     * list needs {@see self::addUncheckedExceptionsFrom()}, which is the case that argument exists for.
     *
     * The project is found the way {@see self::getDevelopmentNamespaceExemptions()} finds it rather than
     * through `InstalledVersions::getRootPackage()` — PHPStan evaluates this config from inside its own
     * phar, where the root package is `phpstan/phpstan-src` and the project is nowhere in sight.
     *
     * @return list<class-string<Throwable>>
     *
     * @throws RuntimeException
     */
    private static function getRootUncheckedExceptions(): array
    {
        $path = dirname(ComposerJson::forProjectUsingThisLibrary()->path) . '/' . self::UNCHECKED_EXCEPTIONS_PATH;

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        return self::readDeclaredExceptions($path);
    }

    /**
     * @return array<non-empty-string, non-empty-list<non-empty-string>>
     *
     * @throws RuntimeException
     */
    private static function getDevelopmentNamespaceExemptions(): array
    {
        $composerJson  = ComposerJson::forProjectUsingThisLibrary();
        $rootNamespace = $composerJson->getRootNamespace();

        if ($rootNamespace === null) {
            return [];
        }

        $exemptions = [];

        foreach ($composerJson->getDevelopmentNamespaces() as $namespace) {
            if (!Str::isNonDecimalIntString($namespace)) {
                continue;
            }

            $exemptions[$namespace] = [$rootNamespace];
        }

        return $exemptions;
    }

    private static function mergeOption(mixed $current, mixed $override): mixed
    {
        if (!is_array($override) || array_is_list($override) || !is_array($current)) {
            return $override;
        }

        foreach ($override as $key => $value) {
            $current[$key] = self::mergeOption($current[$key] ?? null, $value);
        }

        return $current;
    }

    /**
     * @param array<array-key, mixed> $existing
     * @param array<array-key, mixed> $additional
     *
     * @return list<mixed>
     */
    private static function appendUnique(array $existing, array $additional): array
    {
        $uniqueEntries = [];

        foreach ([...array_values($existing), ...array_values($additional)] as $entry) {
            $uniqueEntries[serialize($entry)] ??= $entry;
        }

        return array_values($uniqueEntries);
    }

    /**
     * @param Service $service
     *
     * @return non-empty-string
     */
    private static function getServiceKey(array $service): string
    {
        $arguments = $service['arguments'] ?? [];

        ksort($arguments);

        return $service['class'] . '|' . serialize($arguments);
    }

    /**
     * @param class-string|PhpAtService|list<class-string|PhpAtService> $value
     *
     * @phpstan-assert-if-true list<class-string|PhpAtService> $value
     * @phpstan-assert-if-false class-string|PhpAtService $value
     */
    private static function isNestedPhpAtServiceList(string|array $value): bool
    {
        return !is_string($value) && self::isPhpAtServiceList($value);
    }

    /**
     * @param PhpAtService|list<PhpAtService> $value
     *
     * @phpstan-assert-if-true list<PhpAtService> $value
     * @phpstan-assert-if-false PhpAtService $value
     */
    private static function isPhpAtServiceList(array $value): bool
    {
        return !isset($value['class']);
    }
}

return PhpStan::getConfig();
