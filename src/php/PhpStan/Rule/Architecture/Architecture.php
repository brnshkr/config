<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\ApplicationNoInterfaceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\DomainEventImmutableTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\DomainNoFrameworkTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\DomainNoInterfaceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\InfrastructureNoInterfaceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\InterfaceNoDomainTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\InterfaceNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\ModuleApplicationIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\ModuleDomainIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Ddd\ValueObjectImmutableTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine\EntityAndRepositoryTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine\MigrationIsolationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Doctrine\RoleFoldersExhaustiveTest as DoctrineRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Invariant\ExceptionPlacementTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Invariant\NoDevelopmentDependencyTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Invariant\NoTestDependencyTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\CastTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ChannelTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\CommandTest as LaravelCommandTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ControllerTest as LaravelControllerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\EventTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\JobTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ListenerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\MiddlewareTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ModelTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\NotificationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ObserverTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\PolicyTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\RequestTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ResourceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\RoleFoldersExhaustiveTest as LaravelRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ScopeTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ServiceProviderTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ValidationRuleTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\ApplicationNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoApplicationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Library\ExceptionInterfaceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Library\FacadeIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Library\ModelNoFrameworkTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Library\NoApplicationDependencyTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Modular\ModuleIsolatedTest as ModularModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\AuthenticatorTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\CommandTest as SymfonyCommandTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\ControllerTest as SymfonyControllerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\DataFixtureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\DependencyInjectionTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\EntityNoHttpFoundationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\EventListenerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\FormTypeTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\MessageHandlerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\MessageTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\NormalizerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\RepositoryTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\RoleFoldersExhaustiveTest as SymfonyRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\ServiceTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\SubscriberTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\TwigExtensionTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\VoterTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\ConsoleNoHttpTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\ModuleIsolatedTest as TempestModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\RoleFoldersExhaustiveTest as TempestRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\RouteNoDatabaseTest;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\Str;
use Closure;
use InvalidArgumentException;
use RuntimeException;

use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * Hands you a ready-made bundle of architecture rules for the layout your project already uses.
 *
 * Each public method returns a list of PHPat service definitions that can be passed straight to
 * {@see PhpStan::setArchitecture()}. Presets cover the layered and DDD arrangements, flat
 * modular layouts, and the standard framework folder conventions. Framework presets accept an
 * optional `modules` list, in which case the rules are scoped per module-root and a
 * `RoleFoldersExhaustiveTest` is added so stray top-level folders outside the canonical role
 * names are flagged.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/phpstan/rules/architecture/index.md
 *
 * @api
 *
 * @named-arguments
 *
 * @phpstan-import-type PhpAtService from PhpStan
 */
final class Architecture
{
    use ArchitectureRuleTrait;

    private const string DEFAULT_ROOT = 'App';

    private function __construct() {}

    /**
     * Build the rules every preset carries.
     *
     * Every namespace comes from the project's own `composer.json`, and passing one explicitly
     * overrides the derived value. What the runtime host provides is exempt without configuration.
     *
     * @example
     * ```php
     * $baseline = Architecture::baseline();
     * $exempted = Architecture::baseline(except: ['Composer']);
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param ?list<non-empty-string> $developmentNamespaces development-only namespaces, or null to read them from `autoload-dev`
     * @param ?list<non-empty-string> $developmentPackages namespaces of development-only packages, or null to derive them
     * @param list<non-empty-string> $except further namespaces the runtime host provides
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function baseline(
        ?string $root = null,
        ?array $developmentNamespaces = null,
        ?array $developmentPackages = null,
        array $except = [],
    ): array {
        $composerJson           = ComposerJson::forProjectUsingThisLibrary();
        $root                   = self::resolveRoot($root);
        $developmentNamespaces  = self::normalizeNamespaces($developmentNamespaces ?? $composerJson->getDevelopmentNamespaces());
        $developmentPackages    = self::normalizeNamespaces($developmentPackages ?? $composerJson->getDevelopmentOnlyPackageNamespaces());
        $hostProvidedNamespaces = $composerJson->getHostProvidedNamespaces();

        $forbiddenNamespaces = array_values(array_diff(
            $developmentPackages,
            self::normalizeNamespaces([...$hostProvidedNamespaces, ...$except]),
        ));

        $services = [
            PhpStan::configurePhpAtTest(ExceptionPlacementTest::class, [
                'root'               => $root,
                'excludedNamespaces' => $developmentNamespaces,
            ]),
        ];

        if ($developmentNamespaces !== []) {
            $services[] = PhpStan::configurePhpAtTest(NoTestDependencyTest::class, [
                'root'                  => $root,
                'developmentNamespaces' => $developmentNamespaces,
            ]);
        }

        if ($forbiddenNamespaces !== []) {
            $services[] = PhpStan::configurePhpAtTest(NoDevelopmentDependencyTest::class, [
                'root'                => $root,
                'forbiddenNamespaces' => $forbiddenNamespaces,
                'excludedNamespaces'  => $developmentNamespaces,
            ]);
        }

        return $services;
    }

    /**
     * Build the rules a published package should hold to.
     *
     * @example
     * ```php
     * $library = Architecture::library(
     *     exceptionInterface: 'Acme\Exception\ExceptionInterface',
     *     model: 'Acme\Model',
     *     isolatedFrom: ['PhpParser', 'Symfony'],
     *     facades: ['Filter' => 'Acme\Filter'],
     * );
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param ?non-empty-string $exceptionInterface the package's own exception interface, or null to skip the rule
     * @param ?non-empty-string $model namespace the libraries populating it must not reach
     * @param list<non-empty-string> $isolatedFrom namespaces the model must not depend on
     * @param array<non-empty-string, non-empty-string> $facades map of label to facade class
     * @param list<non-empty-string> $except namespaces the runtime host provides
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function library(
        ?string $root = null,
        ?string $exceptionInterface = null,
        ?string $model = null,
        array $isolatedFrom = [],
        array $facades = [],
        array $except = [],
    ): array {
        $root     = self::resolveRoot($root);
        $services = self::baseline($root, except: $except);

        if ($exceptionInterface !== null) {
            $services[] = PhpStan::configurePhpAtTest(ExceptionInterfaceTest::class, [
                'root'      => $root,
                'interface' => self::normalizeNonEmptyNamespace($exceptionInterface, 'exceptionInterface'),
            ]);
        }

        if ($model !== null && $isolatedFrom !== []) {
            $services[] = PhpStan::configurePhpAtTest(ModelNoFrameworkTest::class, [
                'model'        => self::normalizeNonEmptyNamespace($model, 'model'),
                'isolatedFrom' => self::normalizeNamespaces($isolatedFrom),
            ]);
        }

        foreach ($facades as $label => $facade) {
            $services[] = PhpStan::configurePhpAtTest(FacadeIsolatedTest::class, [
                'facade'    => self::normalizeNonEmptyNamespace($facade, 'facades'),
                'namespace' => self::normalizeNonEmptyNamespace($facade, 'facades'),
                'label'     => $label,
            ]);
        }

        return $services;
    }

    /**
     * Build layered architecture rules enforcing dependency direction constraints.
     *
     * Creates PHPStan architecture tests that prevent:
     *   - Domain depending on Application
     *   - Domain depending on Infrastructure
     *   - Application depending on Infrastructure
     *
     * Intended for classic layered architectures with strict inward dependency flow.
     *
     * @example
     * ```php
     * $layered = Architecture::layered(
     *     domain: 'Acme\Domain',
     *     application: 'Acme\Application',
     *     infrastructure: 'Acme\Infrastructure',
     * );
     * ```
     *
     * @param ?non-empty-string $domain domain layer namespace, or null for `<root>\Domain`
     * @param ?non-empty-string $application application layer namespace, or null for `<root>\Application`
     * @param ?non-empty-string $infrastructure infrastructure layer namespace, or null for `<root>\Infrastructure`
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function layered(
        ?string $domain = null,
        ?string $application = null,
        ?string $infrastructure = null,
        ?string $root = null,
        array $except = [],
    ): array {
        $root           = self::resolveRoot($root);
        $domain         = self::resolveLayer($domain, $root, 'Domain', 'domain');
        $application    = self::resolveLayer($application, $root, 'Application', 'application');
        $infrastructure = self::resolveLayer($infrastructure, $root, 'Infrastructure', 'infrastructure');

        return [
            ...self::baseline($root, except: $except),
            PhpStan::configurePhpAtTest(DomainNoApplicationTest::class, [
                'domain'      => $domain,
                'application' => $application,
            ]),
            PhpStan::configurePhpAtTest(DomainNoInfrastructureTest::class, [
                'domain'         => $domain,
                'infrastructure' => $infrastructure,
            ]),
            PhpStan::configurePhpAtTest(ApplicationNoInfrastructureTest::class, [
                'application'    => $application,
                'infrastructure' => $infrastructure,
            ]),
        ];
    }

    /**
     * Build Domain-Driven Design architecture rules.
     *
     * Extends layered architecture rules with optional DDD-specific constraints:
     *   - Interface layer isolation
     *   - Module isolation
     *   - Immutable value objects
     *   - Immutable domain events
     *   - Domain isolation from framework namespaces
     *
     * Module isolation rules are only generated when at least two modules exist.
     *
     * @example
     * ```php
     * $ddd = Architecture::ddd(
     *     domain: 'Acme\Domain',
     *     application: 'Acme\Application',
     *     infrastructure: 'Acme\Infrastructure',
     *     interface: 'Acme\Interface',
     *     valueObject: 'Acme\Domain\ValueObject',
     *     domainEvent: 'Acme\Domain\Event',
     *     isolatedFrom: ['Doctrine\ORM', 'Symfony\Component\HttpFoundation'],
     *     modules: ['User', 'Email'],
     * );
     * ```
     *
     * @param ?non-empty-string $domain domain layer namespace, or null for `<root>\Domain`
     * @param ?non-empty-string $application application layer namespace, or null for `<root>\Application`
     * @param ?non-empty-string $infrastructure infrastructure layer namespace, or null for `<root>\Infrastructure`
     * @param ?non-empty-string $interface interface layer namespace
     * @param ?non-empty-string $valueObject value object namespace requiring immutability
     * @param ?non-empty-string $domainEvent domain event namespace requiring immutability
     * @param list<non-empty-string> $isolatedFrom framework namespaces forbidden in the domain layer
     * @param list<non-empty-string> $modules module names participating in isolation rules
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when namespaces or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function ddd(
        ?string $domain = null,
        ?string $application = null,
        ?string $infrastructure = null,
        ?string $interface = null,
        ?string $valueObject = null,
        ?string $domainEvent = null,
        array $isolatedFrom = [],
        array $modules = [],
        ?string $root = null,
        array $except = [],
    ): array {
        $root           = self::resolveRoot($root);
        $domain         = self::resolveLayer($domain, $root, 'Domain', 'domain');
        $application    = self::resolveLayer($application, $root, 'Application', 'application');
        $infrastructure = self::resolveLayer($infrastructure, $root, 'Infrastructure', 'infrastructure');
        $interface      = $interface !== null ? self::normalizeNonEmptyNamespace($interface, 'interface') : null;
        $valueObject    = $valueObject !== null ? self::normalizeNonEmptyNamespace($valueObject, 'valueObject') : null;
        $domainEvent    = $domainEvent !== null ? self::normalizeNonEmptyNamespace($domainEvent, 'domainEvent') : null;
        $modules        = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        $services = self::layered($domain, $application, $infrastructure, $root, $except);

        if ($interface !== null) {
            $services[] = PhpStan::configurePhpAtTest(DomainNoInterfaceTest::class, [
                'domain'    => $domain,
                'interface' => $interface,
            ]);

            $services[] = PhpStan::configurePhpAtTest(ApplicationNoInterfaceTest::class, [
                'application' => $application,
                'interface'   => $interface,
            ]);

            $services[] = PhpStan::configurePhpAtTest(InfrastructureNoInterfaceTest::class, [
                'infrastructure' => $infrastructure,
                'interface'      => $interface,
            ]);

            $services[] = PhpStan::configurePhpAtTest(InterfaceNoDomainTest::class, [
                'interface' => $interface,
                'domain'    => $domain,
            ]);

            $services[] = PhpStan::configurePhpAtTest(InterfaceNoInfrastructureTest::class, [
                'interface'      => $interface,
                'infrastructure' => $infrastructure,
            ]);
        }

        if (count($modules) >= 2) {
            $isolations = self::buildModuleIsolations($modules);

            $services[] = PhpStan::configurePhpAtTest(ModuleDomainIsolatedTest::class, [
                'domain'  => $domain,
                'modules' => $isolations,
            ]);

            $services[] = PhpStan::configurePhpAtTest(ModuleApplicationIsolatedTest::class, [
                'application' => $application,
                'modules'     => $isolations,
            ]);
        }

        if ($valueObject !== null) {
            $services[] = PhpStan::configurePhpAtTest(ValueObjectImmutableTest::class, [
                'valueObject' => $valueObject,
            ]);
        }

        if ($domainEvent !== null) {
            $services[] = PhpStan::configurePhpAtTest(DomainEventImmutableTest::class, [
                'domainEvent' => $domainEvent,
            ]);
        }

        if ($isolatedFrom !== []) {
            $services[] = PhpStan::configurePhpAtTest(DomainNoFrameworkTest::class, [
                'domain'       => $domain,
                'isolatedFrom' => $isolatedFrom,
            ]);
        }

        return $services;
    }

    /**
     * Build modular isolation rules.
     *
     * Creates architecture tests that ensure modules cannot depend on sibling modules.
     * Each module namespace is generated from the provided pattern using the `{name}` placeholder.
     *
     * Requires at least two modules.
     *
     * @example
     * ```php
     * $modular = Architecture::modular(modules: ['User', 'Email'], pattern: 'Acme\{name}');
     * ```
     *
     * @param non-empty-list<non-empty-string> $modules module names
     * @param ?non-empty-string $pattern namespace pattern containing the "{name}" placeholder, or null for `<root>\{name}`
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured module isolation rule services
     *
     * @throws InvalidArgumentException when the pattern is invalid or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function modular(array $modules, ?string $pattern = null, ?string $root = null, array $except = []): array
    {
        $root    = self::resolveRoot($root);
        $pattern = self::normalizeNamespace($pattern ?? $root . '\{name}');

        if (!Str::contains($pattern, '{name}')) {
            throw new InvalidArgumentException(sprintf(
                'Modular architecture pattern "%s" must contain the "{name}" placeholder.',
                $pattern,
            ));
        }

        $modules = self::normalizeModuleNames($modules);

        self::assertAtLeastTwoModules($modules);
        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return [
            ...self::baseline($root, except: $except),
            PhpStan::configurePhpAtTest(ModularModuleIsolatedTest::class, [
                'modules' => array_map(
                    static fn (string $module): array => [
                        'module'   => self::applyNamePlaceholder($pattern, $module),
                        'label'    => $module,
                        'siblings' => array_map(
                            static fn (string $sibling): string => self::applyNamePlaceholder($pattern, $sibling),
                            self::findSiblingsOf($module, $modules),
                        ),
                    ],
                    $modules,
                ),
            ]),
        ];
    }

    /**
     * Build Symfony architecture rules.
     *
     * Creates framework-specific architecture tests for Symfony applications.
     * Optionally supports modular Symfony structures by applying rules per module root.
     *
     * Generated rules validate conventions for:
     *   - Controllers
     *   - Repositories
     *   - Commands
     *   - Voters
     *   - Form types
     *   - Subscribers
     *   - Twig extensions
     *   - Message handlers
     *   - Services
     *   - Event listeners
     *   - Authenticators
     *   - Fixtures
     *   - Dependency injection
     *
     * @example
     * ```php
     * $symfonyDefault = Architecture::symfony(root: 'Acme');
     * $symfonyModular = Architecture::symfony(root: 'Acme', modules: ['User', 'Email']);
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $modules optional module names
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured Symfony architecture rule services
     *
     * @throws InvalidArgumentException when namespaces or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function symfony(?string $root = null, array $modules = [], array $except = []): array
    {
        $root    = self::resolveRoot($root);
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return [...self::baseline($root, except: $except), ...self::buildPresetServices(
            $root,
            $modules,
            self::symfonyApplicationBase(...),
            static fn (array $moduleRoots): array => [
                PhpStan::configurePhpAtTest(SymfonyRoleFoldersExhaustiveTest::class, ['roots' => $moduleRoots]),
            ],
        )];
    }

    /**
     * Build the rules a reusable Symfony bundle should hold to.
     *
     * The application-only fixture placement is dropped, and the bundle may not depend on the
     * application that installs it.
     *
     * @example
     * ```php
     * $bundle = Architecture::symfonyBundle();
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param non-empty-string $application namespace of the installing application
     * @param list<non-empty-string> $except further namespaces the runtime host provides
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function symfonyBundle(
        ?string $root = null,
        string $application = self::DEFAULT_ROOT,
        array $except = [],
    ): array {
        $root = self::resolveRoot($root);

        return [
            ...self::packageBase($root, $application, $except),
            ...self::symfonyBase([$root]),
        ];
    }

    /**
     * Build the rules a reusable Laravel package should hold to.
     *
     * @example
     * ```php
     * $package = Architecture::laravelPackage();
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param non-empty-string $application namespace of the installing application
     * @param list<non-empty-string> $except further namespaces the runtime host provides
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function laravelPackage(
        ?string $root = null,
        string $application = self::DEFAULT_ROOT,
        array $except = [],
    ): array {
        $root = self::resolveRoot($root);

        return [
            ...self::packageBase($root, $application, $except),
            ...self::laravelBase([$root]),
        ];
    }

    /**
     * Build the rules a reusable Tempest package should hold to.
     *
     * @example
     * ```php
     * $package = Architecture::tempestPackage();
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param non-empty-string $application namespace of the installing application
     * @param list<non-empty-string> $except further namespaces the runtime host provides
     *
     * @return non-empty-list<PhpAtService> configured architecture rule services
     *
     * @throws InvalidArgumentException when a namespace is empty after normalization
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function tempestPackage(
        ?string $root = null,
        string $application = self::DEFAULT_ROOT,
        array $except = [],
    ): array {
        $root = self::resolveRoot($root);

        return [
            ...self::packageBase($root, $application, $except),
            ...self::tempestBase([$root]),
        ];
    }

    /**
     * Build Doctrine architecture rules.
     *
     * Creates Doctrine-specific architecture tests validating:
     *   - Migration namespace isolation
     *   - Entity and repository placement
     *   - Module role folder structure
     *
     * When modules are provided, rules are generated per module root.
     *
     * @example
     * ```php
     * $doctrineDefault = Architecture::doctrine(root: 'Acme', migrationsNamespace: 'Acme\Migrations');
     * $doctrineModular = Architecture::doctrine(root: 'Acme', modules: ['User', 'Email']);
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param ?non-empty-string $migrationsNamespace doctrine migrations namespace, or null when the package ships none
     * @param list<non-empty-string> $modules optional module names
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured Doctrine architecture rule services
     *
     * @throws InvalidArgumentException when namespaces or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function doctrine(
        ?string $root = null,
        ?string $migrationsNamespace = 'DoctrineMigrations',
        array $modules = [],
        array $except = [],
    ): array {
        $root    = self::resolveRoot($root);
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        $migrations = $migrationsNamespace === null ? [] : [
            PhpStan::configurePhpAtTest(MigrationIsolationTest::class, [
                'root'                => $root,
                'migrationsNamespace' => self::normalizeNonEmptyNamespace($migrationsNamespace, 'migrationsNamespace'),
            ]),
        ];

        return [
            ...self::baseline($root, except: $except),
            ...$migrations,
            ...self::buildPresetServices(
                $root,
                $modules,
                static fn (array $namespaceRoots): array => [
                    PhpStan::configurePhpAtTest(EntityAndRepositoryTest::class, ['roots' => $namespaceRoots]),
                ],
                static fn (array $moduleRoots): array => [
                    PhpStan::configurePhpAtTest(DoctrineRoleFoldersExhaustiveTest::class, ['roots' => $moduleRoots]),
                ],
            ),
        ];
    }

    /**
     * Build Laravel architecture rules.
     *
     * Creates framework-specific architecture tests for Laravel applications.
     * Optionally supports modular Laravel structures by applying rules per module root.
     *
     * Generated rules validate conventions for:
     *   - Controllers
     *   - Models
     *   - Requests
     *   - Resources
     *   - Middleware
     *   - Policies
     *   - Jobs
     *   - Notifications
     *   - Validation rules
     *   - Commands
     *   - Service providers
     *   - Events
     *   - Listeners
     *   - Observers
     *   - Channels
     *   - Scopes
     *   - Casts
     *
     * @example
     * ```php
     * $laravelDefault = Architecture::laravel(root: 'Acme');
     * $laravelModular = Architecture::laravel(root: 'Acme', modules: ['User', 'Email']);
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $modules optional module names
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured Laravel architecture rule services
     *
     * @throws InvalidArgumentException when namespaces or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function laravel(?string $root = null, array $modules = [], array $except = []): array
    {
        $root    = self::resolveRoot($root);
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return [...self::baseline($root, except: $except), ...self::buildPresetServices(
            $root,
            $modules,
            self::laravelBase(...),
            static fn (array $moduleRoots): array => [
                PhpStan::configurePhpAtTest(LaravelRoleFoldersExhaustiveTest::class, ['roots' => $moduleRoots]),
            ],
        )];
    }

    /**
     * Build Tempest architecture rules.
     *
     * Creates Tempest-specific architecture tests validating:
     *   - Routes do not access the database directly
     *   - Console layer does not depend on HTTP layer
     *   - Module isolation
     *   - Role folder exhaustiveness
     *
     * Module isolation rules are only generated when at least two modules exist.
     *
     * @example
     * ```php
     * $tempestDefault = Architecture::tempest(root: 'Acme');
     * $tempestModular = Architecture::tempest(root: 'Acme', modules: ['User', 'Email']);
     * ```
     *
     * @param ?non-empty-string $root root namespace, or null to read it from `autoload`
     * @param list<non-empty-string> $modules optional module names
     * @param list<non-empty-string> $except namespaces the runtime host provides, exempt from the development-dependency rule
     *
     * @return non-empty-list<PhpAtService> configured Tempest architecture rule services
     *
     * @throws InvalidArgumentException when namespaces or module names are invalid
     * @throws RuntimeException when the project's `composer.json` cannot be read
     */
    public static function tempest(?string $root = null, array $modules = [], array $except = []): array
    {
        $root    = self::resolveRoot($root);
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return [...self::baseline($root, except: $except), ...self::buildPresetServices(
            $root,
            $modules,
            self::tempestBase(...),
            static function (array $moduleRoots, array $allModules) use ($root): array {
                $services = [
                    PhpStan::configurePhpAtTest(TempestRoleFoldersExhaustiveTest::class, ['roots' => $moduleRoots]),
                ];

                if (count($allModules) >= 2) {
                    $services[] = PhpStan::configurePhpAtTest(TempestModuleIsolatedTest::class, [
                        'root'    => $root,
                        'modules' => self::buildModuleIsolations($allModules),
                    ]);
                }

                return $services;
            },
        )];
    }

    /**
     * @param non-empty-list<non-empty-string> $roots
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function symfonyApplicationBase(array $roots): array
    {
        return [
            ...self::symfonyBase($roots),
            PhpStan::configurePhpAtTest(DataFixtureTest::class, ['roots' => $roots]),
        ];
    }

    /**
     * @param non-empty-list<non-empty-string> $roots
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function symfonyBase(array $roots): array
    {
        return [
            PhpStan::configurePhpAtTest(SymfonyControllerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(RepositoryTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(SymfonyCommandTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(VoterTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(FormTypeTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(SubscriberTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(TwigExtensionTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(MessageHandlerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(MessageTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(EntityNoHttpFoundationTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ServiceTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(EventListenerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(AuthenticatorTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(NormalizerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(DependencyInjectionTest::class, ['roots' => $roots]),
        ];
    }

    /**
     * @param non-empty-list<non-empty-string> $roots
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function laravelBase(array $roots): array
    {
        return [
            PhpStan::configurePhpAtTest(LaravelControllerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ModelTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(RequestTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ResourceTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(MiddlewareTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(PolicyTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(JobTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(NotificationTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ValidationRuleTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(LaravelCommandTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ServiceProviderTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(EventTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ListenerTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ObserverTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ChannelTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ScopeTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(CastTest::class, ['roots' => $roots]),
        ];
    }

    /**
     * @param non-empty-list<non-empty-string> $roots
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function tempestBase(array $roots): array
    {
        return [
            PhpStan::configurePhpAtTest(RouteNoDatabaseTest::class, ['roots' => $roots]),
            PhpStan::configurePhpAtTest(ConsoleNoHttpTest::class, ['roots' => $roots]),
        ];
    }

    /**
     * @param non-empty-string $root
     * @param list<non-empty-string> $modules
     * @param Closure(non-empty-list<non-empty-string> $roots): non-empty-list<PhpAtService> $buildFlatRules
     * @param Closure(non-empty-list<non-empty-string> $roots, list<non-empty-string> $modules): list<PhpAtService> $buildPerModuleRules
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function buildPresetServices(
        string $root,
        array $modules,
        Closure $buildFlatRules,
        Closure $buildPerModuleRules,
    ): array {
        if ($modules === []) {
            return $buildFlatRules([$root]);
        }

        $moduleRoots = array_map(
            static fn (string $module): string => self::getModuleRoot($root, $module),
            $modules,
        );

        return [
            ...$buildFlatRules($moduleRoots),
            ...$buildPerModuleRules($moduleRoots, $modules),
        ];
    }

    /**
     * @param non-empty-string $root
     * @param non-empty-string $module
     *
     * @return non-empty-string
     */
    private static function getModuleRoot(string $root, string $module): string
    {
        return $root . '\\' . $module;
    }

    /**
     * @param list<non-empty-string> $modules
     *
     * @return list<non-empty-string>
     */
    private static function findSiblingsOf(string $module, array $modules): array
    {
        return array_values(array_filter($modules, static fn (string $candidate): bool => $candidate !== $module));
    }

    private static function normalizeNamespace(string $namespace): string
    {
        $normalized = Str::trim($namespace);
        $normalized = Str::replace($normalized, '/', '\\');

        while (Str::contains($normalized, '\\\\')) {
            $normalized = Str::replace($normalized, '\\\\', '\\');
        }

        return Str::trim($normalized, '\\');
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    /**
     * @param non-empty-list<non-empty-string> $modules
     *
     * @return non-empty-list<array{module: non-empty-string, label: non-empty-string, siblings: list<non-empty-string>}>
     */
    private static function buildModuleIsolations(array $modules): array
    {
        return array_map(
            static fn (string $module): array => [
                'module'   => $module,
                'label'    => $module,
                'siblings' => self::findSiblingsOf($module, $modules),
            ],
            $modules,
        );
    }

    /**
     * @param non-empty-string $root
     * @param non-empty-string $application
     * @param list<non-empty-string> $except
     *
     * @return non-empty-list<PhpAtService>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function packageBase(string $root, string $application, array $except): array
    {
        return [
            ...self::baseline($root, except: $except),
            PhpStan::configurePhpAtTest(NoApplicationDependencyTest::class, [
                'root'        => $root,
                'application' => self::normalizeNonEmptyNamespace($application, 'application'),
            ]),
        ];
    }

    /**
     * @param ?non-empty-string $layer
     * @param non-empty-string $root
     * @param non-empty-string $segment
     * @param non-empty-string $paramName
     *
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function resolveLayer(?string $layer, string $root, string $segment, string $paramName): string
    {
        return self::normalizeNonEmptyNamespace($layer ?? $root . '\\' . $segment, $paramName);
    }

    /**
     * @param ?non-empty-string $root
     *
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function resolveRoot(?string $root): string
    {
        return self::normalizeNonEmptyNamespace(
            $root ?? ComposerJson::forProjectUsingThisLibrary()->getRootNamespace() ?? self::DEFAULT_ROOT,
            'root',
        );
    }

    /**
     * @param list<non-empty-string> $namespaces
     *
     * @return list<non-empty-string>
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeNamespaces(array $namespaces): array
    {
        return array_map(
            static fn (string $namespace): string => self::normalizeNonEmptyNamespace($namespace, 'namespaces'),
            $namespaces,
        );
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $paramName
     *
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeNonEmptyNamespace(string $namespace, string $paramName): string
    {
        $normalized = self::normalizeNamespace($namespace);

        if (Str::isEmpty($normalized)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" namespace must not be empty.',
                $paramName,
            ));
        }

        return $normalized;
    }

    /**
     * @param list<string> $modules
     *
     * @return list<string>
     */
    private static function normalizeModuleNames(array $modules): array
    {
        return array_map(self::normalizeNamespace(...), $modules);
    }

    private static function applyNamePlaceholder(string $pattern, string $name): string
    {
        return Str::replace($pattern, '{name}', $name);
    }

    /**
     * @param list<string> $modules
     *
     * @phpstan-assert list<non-empty-string> $modules
     *
     * @throws InvalidArgumentException
     */
    private static function assertNonEmptyModuleNames(array $modules): void
    {
        foreach ($modules as $index => $module) {
            if (Str::isEmpty($module)) {
                throw new InvalidArgumentException(sprintf(
                    'Module name at index %d must not be empty.',
                    $index,
                ));
            }
        }
    }

    /**
     * @param list<string> $modules
     *
     * @throws InvalidArgumentException
     */
    private static function assertUniqueModuleNames(array $modules): void
    {
        $seen = [];

        foreach ($modules as $module) {
            if (isset($seen[$module])) {
                throw new InvalidArgumentException(sprintf(
                    'Duplicate module name "%s".',
                    $module,
                ));
            }

            $seen[$module] = true;
        }
    }

    /**
     * @param list<string> $modules
     *
     * @phpstan-assert non-empty-list<string> $modules
     *
     * @throws InvalidArgumentException
     */
    private static function assertAtLeastTwoModules(array $modules): void
    {
        if (count($modules) < 2) {
            throw new InvalidArgumentException(sprintf(
                'At least 2 modules required for isolation rules; got %d.',
                count($modules),
            ));
        }
    }
}
