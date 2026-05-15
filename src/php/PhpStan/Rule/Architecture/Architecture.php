<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture;

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
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\Str;
use Closure;
use InvalidArgumentException;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * @api
 *
 * @phpstan-import-type PhpAtService from PhpStan
 *
 * @phpstan-ignore brnshkr.noNamedArgumentsTag (Explicitly allow named arguments here)
 */
final class Architecture
{
    use ArchitectureRuleTrait;

    public const string DEFAULT_ROOT = 'App';

    private function __construct() {}

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
     * @param non-empty-string $domain Domain layer namespace
     * @param non-empty-string $application Application layer namespace
     * @param non-empty-string $infrastructure Infrastructure layer namespace
     *
     * @return non-empty-list<PhpAtService> Configured architecture rule services
     *
     * @throws InvalidArgumentException When a namespace is empty after normalization
     */
    public static function layered(
        string $domain = self::DEFAULT_ROOT . '\Domain',
        string $application = self::DEFAULT_ROOT . '\Application',
        string $infrastructure = self::DEFAULT_ROOT . '\Infrastructure',
    ): array {
        $domain         = self::normalizeNonEmptyNamespace($domain, 'domain');
        $application    = self::normalizeNonEmptyNamespace($application, 'application');
        $infrastructure = self::normalizeNonEmptyNamespace($infrastructure, 'infrastructure');

        return [
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
     *     modules: ['Blog', 'News'],
     * );
     * ```
     *
     * @param non-empty-string $domain Domain layer namespace
     * @param non-empty-string $application Application layer namespace
     * @param non-empty-string $infrastructure Infrastructure layer namespace
     * @param ?non-empty-string $interface Interface layer namespace
     * @param ?non-empty-string $valueObject Value object namespace requiring immutability
     * @param ?non-empty-string $domainEvent Domain event namespace requiring immutability
     * @param list<non-empty-string> $isolatedFrom Framework namespaces forbidden in the domain layer
     * @param list<non-empty-string> $modules Module names participating in isolation rules
     *
     * @return non-empty-list<PhpAtService> Configured architecture rule services
     *
     * @throws InvalidArgumentException When namespaces or module names are invalid
     */
    public static function ddd(
        string $domain = self::DEFAULT_ROOT . '\Domain',
        string $application = self::DEFAULT_ROOT . '\Application',
        string $infrastructure = self::DEFAULT_ROOT . '\Infrastructure',
        ?string $interface = null,
        ?string $valueObject = null,
        ?string $domainEvent = null,
        array $isolatedFrom = [],
        array $modules = [],
    ): array {
        $domain         = self::normalizeNonEmptyNamespace($domain, 'domain');
        $application    = self::normalizeNonEmptyNamespace($application, 'application');
        $infrastructure = self::normalizeNonEmptyNamespace($infrastructure, 'infrastructure');
        $interface      = $interface !== null ? self::normalizeNonEmptyNamespace($interface, 'interface') : null;
        $valueObject    = $valueObject !== null ? self::normalizeNonEmptyNamespace($valueObject, 'valueObject') : null;
        $domainEvent    = $domainEvent !== null ? self::normalizeNonEmptyNamespace($domainEvent, 'domainEvent') : null;
        $modules        = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        $services = self::layered($domain, $application, $infrastructure);

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
            foreach ($modules as $module) {
                $siblings = self::findSiblingsOf($module, $modules);

                $services[] = PhpStan::configurePhpAtTest(ModuleDomainIsolatedTest::class, [
                    'domain'   => $domain,
                    'module'   => $module,
                    'siblings' => $siblings,
                ]);

                $services[] = PhpStan::configurePhpAtTest(ModuleApplicationIsolatedTest::class, [
                    'application' => $application,
                    'module'      => $module,
                    'siblings'    => $siblings,
                ]);
            }
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
     * $modular = Architecture::modular(['Blog', 'News'], 'Acme\{name}');
     * ```
     *
     * @param non-empty-list<non-empty-string> $modules Module names
     * @param non-empty-string $pattern Namespace pattern containing the "{name}" placeholder
     *
     * @return non-empty-list<PhpAtService> Configured module isolation rule services
     *
     * @throws InvalidArgumentException When the pattern is invalid or module names are invalid
     */
    public static function modular(array $modules, string $pattern = self::DEFAULT_ROOT . '\{name}'): array
    {
        $pattern = self::normalizeNamespace($pattern);

        if (!Str::doesContain($pattern, '{name}')) {
            throw new InvalidArgumentException(sprintf(
                'Modular architecture pattern "%s" must contain the "{name}" placeholder.',
                $pattern,
            ));
        }

        $modules = self::normalizeModuleNames($modules);

        self::assertAtLeastTwoModules($modules);
        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return array_map(
            static fn (string $module): array => PhpStan::configurePhpAtTest(ModularModuleIsolatedTest::class, [
                'module'   => self::applyNamePlaceholder($pattern, $module),
                'label'    => $module,
                'siblings' => array_map(
                    static fn (string $sibling): string => self::applyNamePlaceholder($pattern, $sibling),
                    self::findSiblingsOf($module, $modules),
                ),
            ]),
            $modules,
        );
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
     * $symfonyDefault = Architecture::symfony('Acme');
     * $symfonyModular = Architecture::symfony('Acme', modules: ['Blog', 'News']);
     * ```
     *
     * @param non-empty-string $root Root application namespace
     * @param list<non-empty-string> $modules Optional module names
     *
     * @return non-empty-list<PhpAtService> Configured Symfony architecture rule services
     *
     * @throws InvalidArgumentException When namespaces or module names are invalid
     */
    public static function symfony(string $root = self::DEFAULT_ROOT, array $modules = []): array
    {
        $root    = self::normalizeNonEmptyNamespace($root, 'root');
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return self::buildPresetServices(
            $root,
            $modules,
            self::symfonyBase(...),
            static fn (string $module, string $moduleRoot, array $allModules): array => [
                PhpStan::configurePhpAtTest(SymfonyRoleFoldersExhaustiveTest::class, ['root' => $moduleRoot]),
            ],
        );
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
     * $doctrineDefault = Architecture::doctrine('Acme', 'Acme\Migrations');
     * $doctrineModular = Architecture::doctrine('Acme', modules: ['Blog', 'News']);
     * ```
     *
     * @param non-empty-string $root Root application namespace
     * @param non-empty-string $migrationsNamespace Doctrine migrations namespace
     * @param list<non-empty-string> $modules Optional module names
     *
     * @return non-empty-list<PhpAtService> Configured Doctrine architecture rule services
     *
     * @throws InvalidArgumentException When namespaces or module names are invalid
     */
    public static function doctrine(
        string $root = self::DEFAULT_ROOT,
        string $migrationsNamespace = 'DoctrineMigrations',
        array $modules = [],
    ): array {
        $root                = self::normalizeNonEmptyNamespace($root, 'root');
        $migrationsNamespace = self::normalizeNonEmptyNamespace($migrationsNamespace, 'migrationsNamespace');
        $modules             = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return [
            PhpStan::configurePhpAtTest(MigrationIsolationTest::class, [
                'root'                => $root,
                'migrationsNamespace' => $migrationsNamespace,
            ]),
            ...self::buildPresetServices(
                $root,
                $modules,
                static fn (string $namespaceRoot): array => [
                    PhpStan::configurePhpAtTest(EntityAndRepositoryTest::class, ['root' => $namespaceRoot]),
                ],
                static fn (string $module, string $moduleRoot, array $allModules): array => [
                    PhpStan::configurePhpAtTest(DoctrineRoleFoldersExhaustiveTest::class, ['root' => $moduleRoot]),
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
     * $laravelDefault = Architecture::laravel('Acme');
     * $laravelModular = Architecture::laravel('Acme', ['Blog', 'News']);
     * ```
     *
     * @param non-empty-string $root Root application namespace
     * @param list<non-empty-string> $modules Optional module names
     *
     * @return non-empty-list<PhpAtService> Configured Laravel architecture rule services
     *
     * @throws InvalidArgumentException When namespaces or module names are invalid
     */
    public static function laravel(string $root = self::DEFAULT_ROOT, array $modules = []): array
    {
        $root    = self::normalizeNonEmptyNamespace($root, 'root');
        $modules = self::normalizeModuleNames($modules);

        self::assertNonEmptyModuleNames($modules);
        self::assertUniqueModuleNames($modules);

        return self::buildPresetServices(
            $root,
            $modules,
            self::laravelBase(...),
            static fn (string $module, string $moduleRoot, array $allModules): array => [
                PhpStan::configurePhpAtTest(LaravelRoleFoldersExhaustiveTest::class, ['root' => $moduleRoot]),
            ],
        );
    }

     * @param non-empty-string $root
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function symfonyBase(string $root): array
    {
        return [
            PhpStan::configurePhpAtTest(SymfonyControllerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(RepositoryTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(SymfonyCommandTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(VoterTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(FormTypeTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(SubscriberTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(TwigExtensionTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(MessageHandlerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(MessageTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(EntityNoHttpFoundationTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ServiceTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(EventListenerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(AuthenticatorTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(DataFixtureTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(NormalizerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(DependencyInjectionTest::class, ['root' => $root]),
        ];
    }

    /**
     * @param non-empty-string $root
     *
     * @return non-empty-list<PhpAtService>
     */
    private static function laravelBase(string $root): array
    {
        return [
            PhpStan::configurePhpAtTest(LaravelControllerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ModelTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(RequestTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ResourceTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(MiddlewareTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(PolicyTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(JobTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(NotificationTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ValidationRuleTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(LaravelCommandTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ServiceProviderTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(EventTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ListenerTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ObserverTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ChannelTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(ScopeTest::class, ['root' => $root]),
            PhpStan::configurePhpAtTest(CastTest::class, ['root' => $root]),
        ];
    }

    /**
     * @param non-empty-string $root
     * @param list<non-empty-string> $modules
     * @param Closure(non-empty-string $moduleRoot): non-empty-list<PhpAtService> $buildFlatRules
     * @param Closure(non-empty-string $module, non-empty-string $moduleRoot, list<non-empty-string> $modules): list<PhpAtService> $buildPerModuleRules
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
            return $buildFlatRules($root);
        }

        $services = [];

        foreach ($modules as $module) {
            $moduleRoot = self::getModuleRoot($root, $module);

            $services = [
                ...$services,
                ...$buildFlatRules($moduleRoot),
                ...$buildPerModuleRules($module, $moduleRoot, $modules),
            ];
        }

        return $services;
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
     * @param list<string> $modules
     *
     * @return list<string>
     */
    private static function findSiblingsOf(string $module, array $modules): array
    {
        return array_values(array_filter($modules, static fn (string $candidate): bool => $candidate !== $module));
    }

    private static function normalizeNamespace(string $namespace): string
    {
        $normalized = Str::trim($namespace);
        $normalized = Str::replace($normalized, '/', '\\');

        while (Str::doesContain($normalized, '\\\\')) {
            $normalized = Str::replace($normalized, '\\\\', '\\');
        }

        return Str::trim($normalized, '\\');
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeNonEmptyNamespace(string $namespace, string $paramName): string
    {
        $normalized = self::normalizeNamespace($namespace);

        if ($normalized === '') {
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
            if ($module === '') {
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
