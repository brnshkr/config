<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
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
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ControllerTest as LaravelControllerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\ModelTest as LaravelModelTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Laravel\RoleFoldersExhaustiveTest as LaravelRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\ApplicationNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoApplicationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Modular\ModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\ControllerTest as SymfonyControllerTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Symfony\RoleFoldersExhaustiveTest as SymfonyRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\ConsoleNoHttpTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\ModuleIsolatedTest as TempestModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\RoleFoldersExhaustiveTest as TempestRoleFoldersExhaustiveTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Tempest\RouteNoDatabaseTest;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Throwable;

use function array_column;
use function array_filter;
use function array_first;
use function array_map;
use function array_values;
use function count;
use function getcwd;
use function preg_quote;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
final class PhpStanTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @throws DirectoryNotFoundException
     * @throws JsonException
     * @throws RuntimeException
     */
    public function testExpectedPhpstanConfig(): void
    {
        $config = include __DIR__ . '/../../conf/phpstan.dist.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('parameters', $config);
        self::assertIsArray($config['parameters']);
        unset($config['parameters']['editorUrl']);

        $cwd    = getcwd() ?: '.';
        $result = s(Json::encode($config))->replaceMatches(sprintf('/%s/', preg_quote($cwd, '/')), '.')->toString();

        $this->assertMatchesJsonSnapshot($result);
    }

    /**
     * @throws Throwable
     */
    public function testLayeredPresetReturnsListOfServiceDefs(): void
    {
        $services = Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure');

        self::assertSame([
            DomainNoApplicationTest::class,
            DomainNoInfrastructureTest::class,
            ApplicationNoInfrastructureTest::class,
        ], array_column($services, 'class'));

        self::assertCount(3, $services);

        self::assertSame(
            ['domain' => 'App\Domain', 'application' => 'App\Application'],
            array_first($services)['arguments'] ?? null,
        );
    }

    /**
     * @throws Throwable
     */
    public function testDddComposesLayered(): void
    {
        $layeredClasses = array_column(Architecture::layered(), 'class');
        $dddClasses     = array_column(Architecture::ddd(), 'class');

        foreach ($layeredClasses as $layeredClass) {
            self::assertContains($layeredClass, $dddClasses);
        }
    }

    /**
     * @throws Throwable
     */
    public function testDddAttachesInterfaceIsolationWhenInterfaceProvided(): void
    {
        $services = Architecture::ddd(interface: 'App\Interface');
        $classes  = array_column($services, 'class');

        self::assertContains(DomainNoInterfaceTest::class, $classes);
        self::assertContains(ApplicationNoInterfaceTest::class, $classes);
        self::assertContains(InfrastructureNoInterfaceTest::class, $classes);
        self::assertContains(InterfaceNoDomainTest::class, $classes);
        self::assertContains(InterfaceNoInfrastructureTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testDddOmitsInterfaceIsolationWhenInterfaceMissing(): void
    {
        $services = Architecture::ddd();
        $classes  = array_column($services, 'class');

        self::assertNotContains(DomainNoInterfaceTest::class, $classes);
        self::assertNotContains(InterfaceNoDomainTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testDddAttachesValueObjectAndDomainEventImmutabilityRules(): void
    {
        $services = Architecture::ddd(
            valueObject: 'App\Domain\ValueObject',
            domainEvent: 'App\Domain\Event',
        );

        $classes = array_column($services, 'class');

        self::assertContains(ValueObjectImmutableTest::class, $classes);
        self::assertContains(DomainEventImmutableTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testDddAttachesDomainFrameworkIsolationWhenIsolatedFromProvided(): void
    {
        $services = Architecture::ddd(isolatedFrom: ['Doctrine\ORM', 'Symfony\Component\HttpFoundation']);
        $classes  = array_column($services, 'class');

        self::assertContains(DomainNoFrameworkTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testDddEmitsModuleIsolationRulesWhenMultipleModulesProvided(): void
    {
        $services = Architecture::ddd(modules: ['Blog', 'News']);

        $domainIsolations = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === ModuleDomainIsolatedTest::class,
        ));

        $applicationIsolations = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === ModuleApplicationIsolatedTest::class,
        ));

        self::assertCount(2, $domainIsolations);
        self::assertCount(2, $applicationIsolations);
        self::assertSame(['News'], array_first($domainIsolations)['arguments']['siblings'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testDddSkipsIsolationRulesForSingleModule(): void
    {
        $services = Architecture::ddd(modules: ['Blog']);
        $classes  = array_map(static fn (array $service): string => $service['class'], $services);

        self::assertNotContains(ModuleDomainIsolatedTest::class, $classes);
        self::assertNotContains(ModuleApplicationIsolatedTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testModularExpandsModulesIntoServices(): void
    {
        $services = Architecture::modular(['Blog', 'Billing', 'Shipping'], 'Acme\{name}');

        self::assertCount(3, $services);

        foreach ($services as $service) {
            self::assertSame(ModuleIsolatedTest::class, $service['class']);
        }

        $labels = array_map(static fn (array $service): mixed => $service['arguments']['label'] ?? null, $services);

        self::assertSame(['Blog', 'Billing', 'Shipping'], $labels);

        $firstArguments = array_first($services)['arguments'] ?? [];

        self::assertArrayHasKey('module', $firstArguments);
        self::assertSame('Acme\Blog', $firstArguments['module']);
        self::assertSame(['Acme\Billing', 'Acme\Shipping'], $firstArguments['siblings'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testModularThrowsOnSingleModule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Architecture::modular(['Blog'], 'Acme\{name}');
    }

    /**
     * @throws Throwable
     */
    public function testModularThrowsWhenPatternMissesNamePlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Architecture::modular(['Blog', 'News'], 'Acme\Module');
    }

    /**
     * @throws Throwable
     */
    public function testSymfonyFlatModeProducesSingleRootRules(): void
    {
        $services = Architecture::symfony('App');

        $rootArguments = array_map(
            static fn (array $service): mixed => $service['arguments']['root'] ?? null,
            $services,
        );

        foreach ($rootArguments as $rootArgument) {
            self::assertSame('App', $rootArgument);
        }

        $classes = array_column($services, 'class');

        self::assertNotContains(SymfonyRoleFoldersExhaustiveTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testSymfonyModularModeScopesRulesPerModule(): void
    {
        $services = Architecture::symfony('App', ['Blog', 'News']);

        $controllerInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === SymfonyControllerTest::class,
        ));

        self::assertCount(2, $controllerInstances);
        self::assertSame('App\Blog', $controllerInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $controllerInstances[1]['arguments']['root'] ?? null);

        $exhaustiveInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === SymfonyRoleFoldersExhaustiveTest::class,
        ));

        self::assertCount(2, $exhaustiveInstances);
        self::assertSame('App\Blog', $exhaustiveInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $exhaustiveInstances[1]['arguments']['root'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testLaravelFlatModeProducesSingleRootRules(): void
    {
        $services = Architecture::laravel('App');

        $rootArguments = array_map(
            static fn (array $service): mixed => $service['arguments']['root'] ?? null,
            $services,
        );

        foreach ($rootArguments as $rootArgument) {
            self::assertSame('App', $rootArgument);
        }

        $classes = array_column($services, 'class');

        self::assertContains(LaravelControllerTest::class, $classes);
        self::assertContains(LaravelModelTest::class, $classes);
        self::assertNotContains(LaravelRoleFoldersExhaustiveTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testLaravelModularModeScopesRulesPerModule(): void
    {
        $services = Architecture::laravel('App', ['Blog', 'News']);

        $controllerInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === LaravelControllerTest::class,
        ));

        self::assertCount(2, $controllerInstances);
        self::assertSame('App\Blog', $controllerInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $controllerInstances[1]['arguments']['root'] ?? null);

        $exhaustiveInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === LaravelRoleFoldersExhaustiveTest::class,
        ));

        self::assertCount(2, $exhaustiveInstances);
    }

    /**
     * @throws Throwable
     */
    public function testDoctrineFlatModeEmitsMigrationsAndEntityRules(): void
    {
        $services = Architecture::doctrine('App', 'App\Migrations');
        $classes  = array_column($services, 'class');

        self::assertContains(MigrationIsolationTest::class, $classes);
        self::assertContains(EntityAndRepositoryTest::class, $classes);
        self::assertNotContains(DoctrineRoleFoldersExhaustiveTest::class, $classes);

        $migrationInstance = array_first(array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === MigrationIsolationTest::class,
        )));

        self::assertSame('App', $migrationInstance['arguments']['root'] ?? null);
        self::assertSame('App\Migrations', $migrationInstance['arguments']['migrationsNamespace'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testDoctrineModularModeKeepsGlobalMigrationsButScopesEntitiesPerModule(): void
    {
        $services = Architecture::doctrine('App', 'App\Migrations', ['Blog', 'News']);

        $migrationInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === MigrationIsolationTest::class,
        ));

        $entityInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === EntityAndRepositoryTest::class,
        ));

        $exhaustiveInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === DoctrineRoleFoldersExhaustiveTest::class,
        ));

        self::assertCount(1, $migrationInstances);
        self::assertCount(2, $entityInstances);
        self::assertCount(2, $exhaustiveInstances);
        self::assertSame('App\Blog', $entityInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $entityInstances[1]['arguments']['root'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testTempestFlatModeProducesPurityRulesOnly(): void
    {
        $services = Architecture::tempest('App');
        $classes  = array_column($services, 'class');

        self::assertContains(RouteNoDatabaseTest::class, $classes);
        self::assertContains(ConsoleNoHttpTest::class, $classes);
        self::assertNotContains(TempestModuleIsolatedTest::class, $classes);
        self::assertNotContains(TempestRoleFoldersExhaustiveTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testTempestModularModeClonesPurityRulesPerModule(): void
    {
        $services = Architecture::tempest('App', ['Blog', 'News']);

        $routeInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === RouteNoDatabaseTest::class,
        ));

        $consoleInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === ConsoleNoHttpTest::class,
        ));

        $isolationInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === TempestModuleIsolatedTest::class,
        ));

        self::assertCount(2, $routeInstances);
        self::assertSame('App\Blog', $routeInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $routeInstances[1]['arguments']['root'] ?? null);
        self::assertCount(2, $consoleInstances);
        self::assertCount(2, $isolationInstances);
    }

    /**
     * @throws Throwable
     */
    public function testTempestSkipsIsolationRulesForSingleModule(): void
    {
        $services = Architecture::tempest('App', ['Blog']);
        $classes  = array_map(static fn (array $service): string => $service['class'], $services);

        self::assertNotContains(TempestModuleIsolatedTest::class, $classes);
        self::assertContains(TempestRoleFoldersExhaustiveTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testNamespaceNormalizationTrimsAndConvertsSlashes(): void
    {
        $services = Architecture::layered(' /App/Domain\ ', 'App\Application/', '\App\Infrastructure');

        self::assertSame(
            ['domain' => 'App\Domain', 'application' => 'App\Application'],
            array_first($services)['arguments'] ?? null,
        );
    }

    /**
     * @throws Throwable
     */
    public function testModuleNameNormalizationTrimsAndConvertsSlashes(): void
    {
        $services = Architecture::symfony('App', [' /Blog\ ', 'News']);

        $controllerInstances = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === SymfonyControllerTest::class,
        ));

        self::assertSame('App\Blog', $controllerInstances[0]['arguments']['root'] ?? null);
        self::assertSame('App\News', $controllerInstances[1]['arguments']['root'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testThrowsOnEmptyRootNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Architecture::symfony('  ');
    }

    /**
     * @throws Throwable
     */
    public function testThrowsOnEmptyModuleName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Architecture::symfony('App', ['Blog', '   ']);
    }

    /**
     * @throws Throwable
     */
    public function testThrowsOnDuplicateModuleName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Architecture::symfony('App', ['Blog', 'Blog']);
    }

    /**
     * @throws Throwable
     */
    public function testSetArchitectureAcceptsCombinedPolicies(): void
    {
        $custom = PhpStan::configurePhpAtTest(DomainNoApplicationTest::class, [
            'domain'      => 'App\Domain',
            'application' => 'App\Application',
        ]);

        $config = PhpStan::getConfig(null, true)
            ->setArchitecture([
                $custom,
                ...Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'),
            ])
            ->toArray()
        ;

        $phpatServices = array_values(array_filter(
            $config['services'],
            static fn (array $service): bool => ($service['tags'] ?? null) === ['phpat.test'],
        ));

        self::assertGreaterThanOrEqual(3, count($phpatServices));
    }

    /**
     * @throws Throwable
     */
    public function testSetArchitectureAcceptsDirectPresetCall(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture(Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'))
            ->toArray()
        ;

        $classes = array_column($config['services'], 'class');

        self::assertContains(DomainNoApplicationTest::class, $classes);
        self::assertContains(DomainNoInfrastructureTest::class, $classes);
        self::assertContains(ApplicationNoInfrastructureTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testSetArchitectureAcceptsWrappedPresetCall(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture([
                Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'),
            ])
            ->toArray()
        ;

        $classes = array_column($config['services'], 'class');

        self::assertContains(DomainNoApplicationTest::class, $classes);
        self::assertContains(DomainNoInfrastructureTest::class, $classes);
        self::assertContains(ApplicationNoInfrastructureTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testSetArchitectureAcceptsMultiplePresets(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture([
                Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'),
                Architecture::modular(['Blog', 'News'], 'App\{name}'),
            ])
            ->toArray()
        ;

        $classes = array_column($config['services'], 'class');

        self::assertContains(DomainNoApplicationTest::class, $classes);
        self::assertContains(ModuleIsolatedTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testSetArchitectureKeepsSameClassWithDifferentArguments(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture(Architecture::symfony('App', ['Blog', 'News']))
            ->toArray()
        ;

        $controllerInstances = array_values(array_filter(
            $config['services'],
            static fn (array $service): bool => $service['class'] === SymfonyControllerTest::class,
        ));

        self::assertCount(2, $controllerInstances, 'Per-module instantiations must survive setServices dedup');
    }

    /**
     * @throws Throwable
     */
    public function testRemoveArchitectureByClassString(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture(Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'))
            ->removeArchitecture([DomainNoApplicationTest::class])
            ->toArray()
        ;

        $classes = array_column($config['services'], 'class');

        self::assertNotContains(DomainNoApplicationTest::class, $classes);
        self::assertContains(DomainNoInfrastructureTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveArchitectureByServiceDef(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture([
                ...Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'),
            ])
            ->removeArchitecture([
                [
                    'class'     => DomainNoInfrastructureTest::class,
                    'tags'      => ['phpat.test'],
                    'arguments' => [
                        'domain'         => 'App\Domain',
                        'infrastructure' => 'App\Infrastructure',
                    ],
                ],
            ])
            ->toArray()
        ;

        $classes = array_column($config['services'], 'class');

        self::assertNotContains(DomainNoInfrastructureTest::class, $classes);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveArchitectureByServiceDefRequiresMatchingArguments(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture(Architecture::symfony('App', ['Blog', 'News']))
            ->removeArchitecture([
                [
                    'class'     => SymfonyControllerTest::class,
                    'tags'      => ['phpat.test'],
                    'arguments' => ['root' => 'App\Blog'],
                ],
            ])
            ->toArray()
        ;

        $controllerInstances = array_values(array_filter(
            $config['services'],
            static fn (array $service): bool => $service['class'] === SymfonyControllerTest::class,
        ));

        self::assertCount(1, $controllerInstances);
        self::assertSame('App\News', array_first($controllerInstances)['arguments']['root'] ?? null);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveArchitectureByPolicyList(): void
    {
        $config = PhpStan::getConfig(null, true)
            ->setArchitecture(Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'))
            ->removeArchitecture([
                Architecture::layered('App\Domain', 'App\Application', 'App\Infrastructure'),
            ])
            ->toArray()
        ;

        $layeredClasses = [
            DomainNoApplicationTest::class,
            DomainNoInfrastructureTest::class,
            ApplicationNoInfrastructureTest::class,
        ];

        $classes = array_column($config['services'], 'class');

        foreach ($layeredClasses as $layeredClass) {
            self::assertNotContains($layeredClass, $classes);
        }
    }
}
