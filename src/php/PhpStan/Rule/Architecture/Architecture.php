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
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\ApplicationNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoApplicationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Modular\ModuleIsolatedTest as ModularModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\Str;
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

        do {
            $previous   = $normalized;
            $normalized = Str::replace($normalized, '\\\\', '\\');
        } while ($normalized !== $previous);

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
