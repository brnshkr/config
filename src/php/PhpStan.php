<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

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
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
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

use function array_any;
use function array_diff;
use function array_filter;
use function array_find;
use function array_is_list;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function class_exists;
use function explode;
use function getcwd;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_string;
use function iterator_to_array;
use function ksort;
use function serialize;
use function sprintf;
use function Symfony\Component\String\s;

Module::warnMissingPackages(Module::MODULE_PHP_STAN);

// @phpstan-ignore symplify.forbiddenFuncCall (Guards the class declaration so this self-returning config can be safely required more than once, e.g. via PHPStan's 'includes')
if (class_exists(PhpStan::class)) {
    return PhpStan::getConfig();
}

/**
 * Builds the @brnshkr PHPStan configuration through a chainable, opt-in API.
 *
 * {@see self::getConfig()} returns the project-wide baseline — max level, strict exception
 * checking, this package's custom rules, editor-URL handling — and downstream projects layer
 * their own adjustments on top through the fluent setters before calling {@see self::toArray()}
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
 */
final class PhpStan
{
    private const array TAG_PHP_AT_TEST                 = ['phpat.test'];
    private const array TAG_RULE                        = ['phpstan.rules.rule'];
    private const array TAG_STATIC_THROW_TYPE_EXTENSION = ['phpstan.dynamicStaticMethodThrowTypeExtension'];

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
     * Returns the raw config array by default; pass `$asInstance: true` to get the builder
     * instance for further chaining (used by `conf/phpstan.dist.php` to add architecture rules).
     *
     * @example
     * ```php
     * // conf/phpstan.php
     *
     * // plain config
     * return PhpStan::getConfig();
     *
     * // extend before returning
     * return PhpStan::getConfig(null, true)
     *     ->setArchitecture(Architecture::symfony('Acme'))
     *     ->toArray();
     * ```
     *
     * @template TAsInstance of bool
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     * @param TAsInstance $asInstance when true return the builder, otherwise the config array
     *
     * @return (TAsInstance is true ? self : Config) builder instance or finalized config array
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws RuntimeException when a required optional PHPStan extension is missing
     */
    public static function getConfig(?Finder $finder = null, bool $asInstance = false): self|array
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
            ->setIgnoredErrors([
                [
                    'message'         => '/^Short ternary operator is not allowed. Use null coalesce operator if applicable or consider using long ternary.$/',
                    'reportUnmatched' => false,
                ],
            ])
            ->setRules([
                ApiOrInternalTagRule::class,
                BoolishPrefixRule::class,
                InterfaceSuffixRule::class,
                InternalExposureRule::class,
                NamedArgumentsTagRule::class,
                NamedArgumentsUsageRule::class,
                PublicApiDocumentationRule::class,
                ResolvableDocReferenceRule::class,
                self::configureRule(InternalUsageRule::class, [
                    'allowedCallingNamespaces' => [
                        'Brnshkr\Config\Tests',
                    ],
                ]),
            ])
            ->setServices([
                self::configureStaticThrowTypeExtension(FileFinderThrowTypeExtension::class),
            ])
        ;

        if (Package::DependencyInjection->isInstalled()) {
            $phpStanConfig->setRules([
                ServiceArgumentBindingRule::class,
            ]);
        }

        if (Package::PhpStanStrictRules->isInstalled()) {
            $phpStanConfig->setStrictRules([
                'allRules' => true,
            ]);
        }

        if (Package::TypePerfect->isInstalled()) {
            $phpStanConfig->setTypePerfect([
                'narrow_return'   => true,
                'no_mixed'        => true,
                'null_over_false' => true,
            ]);
        }

        if (Package::PhpStanRules->isInstalled()) {
            $phpStanConfig->setRules(self::getSymplifyRules());
        }

        return $asInstance ? $phpStanConfig : $phpStanConfig->toArray();
    }

    /**
     * Serialize the builder to its raw PHPStan config array.
     *
     * @return Config finalized config with the four top-level sections (includes, parameters, rules, services)
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Merge additional `includes` neon paths into the config (dedup-preserving order).
     *
     * @param list<non-empty-string> $includePaths absolute or relative paths to neon files to merge in
     */
    public function setIncludes(array $includePaths): self
    {
        $this->config['includes'] = array_values(array_unique([...$this->config['includes'], ...$includePaths]));

        return $this;
    }

    /**
     * Merge multiple PHPStan parameters at once, overwriting existing keys.
     *
     * @param array<non-empty-string, mixed> $parameters map of parameter name to value
     */
    public function setParameters(array $parameters): self
    {
        $this->config['parameters'] = array_merge($this->config['parameters'], $parameters);

        return $this;
    }

    /**
     * Set a single PHPStan parameter by key, overwriting any existing value.
     *
     * Prefer the named setters ({@see self::setLevel()}, {@see self::setPaths()} etc.) where
     * one exists; use this only for parameters without a dedicated wrapper.
     *
     * @param non-empty-string $key parameter name as it appears under the `parameters:` section
     * @param mixed $value parameter value
     */
    public function setParameter(string $key, mixed $value): self
    {
        $this->config['parameters'][$key] = $value;

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
    public function setRules(array $rules): self
    {
        $this->config['rules'] = [...array_values(array_unique([...$this->config['rules'], ...array_filter($rules, is_string(...))]))];

        return $this->setServices(array_values(array_filter($rules, is_array(...))));
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
    public function setServices(array $services): self
    {
        $mergedServices = [];

        foreach ([...$this->config['services'], ...$services] as $service) {
            $mergedServices[self::getServiceKey($service)] ??= $service;
        }

        $this->config['services'] = array_values($mergedServices);

        return $this;
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
     * $config->setPaths(['src', 'tests'], ['src/legacy']);
     * $config->setPaths(['src'], ['analyse' => ['src/runtime-only']]);
     * ```
     *
     * @param list<non-empty-string> $paths paths to analyze
     * @param list<non-empty-string>|array{
     *     analyse?: list<non-empty-string>,
     *     analyseAndScan?: list<non-empty-string>,
     * } $excludedPaths Excluded paths (flat list or structured)
     */
    public function setPaths(array $paths, array $excludedPaths = []): self
    {
        $this->setParameter('paths', $paths);

        if ($excludedPaths !== []) {
            $this->setExcludedPaths($excludedPaths);
        }

        return $this;
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
     */
    public function setExcludedPaths(array $excludedPaths): self
    {
        return $this->setParameter(
            'excludePaths',
            array_is_list($excludedPaths)
                ? ['analyseAndScan' => $excludedPaths]
                : $excludedPaths,
        );
    }

    /**
     * Set the list of bootstrap files PHPStan should require before analysis.
     *
     * @param list<non-empty-string> $bootstrapFiles paths to bootstrap PHP files
     */
    public function setBootstrapFiles(array $bootstrapFiles): self
    {
        return $this->setParameter('bootstrapFiles', $bootstrapFiles);
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
     * Define ignore patterns for known/expected PHPStan errors.
     *
     * Each entry is either a raw regex string or the structured `{message, identifier?, count?, path?, reportUnmatched?}` shape.
     *
     * @param list<non-empty-string|array{
     *     message: non-empty-string,
     *     identifier?: non-empty-string,
     *     count?: positive-int,
     *     path?: non-empty-string,
     *     reportUnmatched?: bool,
     * }> $ignoredErrors Ignored-error definitions
     *
     * @see https://phpstan.org/user-guide/ignoring-errors#ignoring-in-configuration-file
     */
    public function setIgnoredErrors(string|array $ignoredErrors): self
    {
        return $this->setParameter('ignoreErrors', $ignoredErrors);
    }

    /**
     * Toggle PHPStan feature flags by name.
     *
     * @param array<non-empty-string, bool> $featureToggles map of feature-toggle name to enable/disable
     */
    public function setFeatureToggles(array $featureToggles): self
    {
        return $this->setParameter('featureToggles', $featureToggles);
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
        return $this->setParameter('exceptions', $exceptions);
    }

    /**
     * Configure the `phpstan/phpstan-strict-rules` extension.
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

        return $this->setParameter('strictRules', $strictRules);
    }

    /**
     * Configure the `rector/type-perfect` extension.
     *
     * @param array<non-empty-string, bool> $options map of type-perfect option name to enabled flag
     *
     * @see https://github.com/rectorphp/type-perfect
     *
     * @throws RuntimeException when `rector/type-perfect` is not installed
     */
    public function setTypePerfect(array $options): self
    {
        Module::warnMissingPackages(Package::TypePerfect);

        return $this->setParameter('type_perfect', $options);
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
     * Configure the `phpstan/phpstan-symfony` extension.
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

        return $this->setParameter('symfony', $options);
    }

    /**
     * Configure the `phpstan/phpstan-doctrine` extension.
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

        return $this->setParameter('doctrine', $options);
    }

    /**
     * Register PHPat architecture-test services.
     *
     * Accepts either flat service definitions or nested lists (the latter is the shape returned
     * by the {@see Architecture} factory methods), and flattens them before registration.
     *
     * @example
     * ```php
     * $config->setArchitecture([
     *     ...Architecture::laravel('Acme'),
     *     ...Architecture::doctrine('Acme'),
     * ]);
     * ```
     *
     * @param list<PhpAtService|list<PhpAtService>> $architecture PHPat services or nested service lists
     *
     * @throws RuntimeException when `phpat/phpat` is not installed
     */
    public function setArchitecture(array $architecture): self
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

        return $this->setServices($services);
    }

    /**
     * Remove previously registered architecture rules.
     *
     * Mirrors {@see self::setArchitecture()}: accepts class-strings, service definitions, or
     * nested lists of either, and flattens before delegating to {@see self::removeServices()}.
     * Use this to opt out of selected rules from an {@see Architecture} preset.
     *
     * @example
     * ```php
     * $config->setArchitecture(Architecture::laravel('Acme'));
     * $config->removeArchitecture([ServiceProviderTest::class]);
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
     * passed to {@see self::setRules()}.
     *
     * @example
     * ```php
     * $config->setRules([
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
            // @phpstan-ignore class.notFound (See ->), class.notFound (nesbot/carbon is not a dependency of brnshkr/config)
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
            SymplifyPhpStanRules\Symfony\NoAbstractControllerConstructorRule::class,
            SymplifyPhpStanRules\Symfony\NoBareAndSecurityIsGrantedContentsRule::class,
            SymplifyPhpStanRules\Symfony\NoClassLevelRouteRule::class,
            SymplifyPhpStanRules\Symfony\NoConstructorAndRequiredTogetherRule::class,
            SymplifyPhpStanRules\Symfony\NoControllerMethodInjectionRule::class,
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
                'str?*'                 => sprintf('Use "%s" instead. If using this function is really the only option, please disable this rule for this line.', $stringFunction),
                'mb_str?*'              => sprintf('Use "%s instead.', $stringFunction),
                'preg_match_all'        => sprintf('Use "%s::match()" instead.', $stringFunction),
                'preg_match'            => sprintf('Use "%s::match()" instead.', $stringFunction),
                'preg_replace_callback' => sprintf('Use "%s::replaceMatches()" instead.', $stringFunction),
                'preg_replace'          => sprintf('Use "%s::replaceMatches()" instead.', $stringFunction),
            ];
        }

        /** @disregard P1009 symfony/http-client-contracts is not a dependency of brnshkr/config */
        // @phpstan-ignore symplify.forbiddenFuncCall (symfony/http-client-contracts is not a dependency of brnshkr/config)
        if (interface_exists(HttpClientInterface::class)) {
            // @phpstan-ignore class.notFound (symfony/http-client-contracts is not a dependency of brnshkr/config)
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
