<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\EditorUrl;
use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\ProjectKernel;
use Brnshkr\Config\PhpStan\Rule\Architecture\Modular\ModuleIsolatedTest;
use Brnshkr\Config\PhpStan\Rule\BoolishPrefixRule;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use Brnshkr\Config\PhpStan\ThrowTypeExtension\FileFinderThrowTypeExtension;
use Brnshkr\Config\Str;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symplify\PHPStanRules\Rules\Symfony\NoFindTaggedServiceIdsCallRule;

use function array_filter;
use function array_values;
use function count;

/**
 * @internal
 */
#[CoversClass(PhpStan::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(EditorUrl::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Json::class)]
#[UsesClass(Module::class)]
#[UsesClass(Package::class)]
#[UsesClass(ProjectKernel::class)]
#[UsesClass(Str::class)]
final class PhpStanTest extends TestCase
{
    public function testAnOptionMapKeepsTheKeysTheCallDoesNotName(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->setExceptions(['check' => ['tooWideThrowType' => false]])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);
        self::assertNotEmpty($exceptions['uncheckedExceptionRegexes'] ?? null);

        $check = $exceptions['check'] ?? null;

        self::assertIsArray($check);
        self::assertFalse($check['tooWideThrowType'] ?? null);
        self::assertTrue($check['throwTypeCovariance'] ?? null);
    }

    public function testFromAddsToAConfigAnotherFileBuilt(): void
    {
        $baseline = PhpStan::getConfig();

        $ignoredErrors = $baseline['parameters']['ignoreErrors'] ?? null;

        self::assertIsArray($ignoredErrors);

        $config = PhpStan::from($baseline)
            ->addIgnoredErrors([['identifier' => 'acme.rule']])
            ->build()
        ;

        $extended = $config['parameters']['ignoreErrors'] ?? null;

        self::assertIsArray($extended);
        self::assertCount(count($ignoredErrors) + 1, $extended);
        self::assertSame($baseline['parameters']['level'] ?? null, $config['parameters']['level'] ?? null);
    }

    public function testAListReplacesRatherThanMerges(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->setExceptions(['uncheckedExceptionRegexes' => ['/^Acme/']])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);
        self::assertSame(['/^Acme/'], $exceptions['uncheckedExceptionRegexes'] ?? null);
    }

    public function testAPackageDeclaresItsOwnUncheckedExceptions(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->addUncheckedExceptionsFrom('brnshkr/config')
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);

        $classes = $exceptions['uncheckedExceptionClasses'] ?? null;

        self::assertIsArray($classes);
        self::assertContains(UnreachableException::class, $classes);
    }

    public function testAPackageThatDeclaresNoneIsAnError(): void
    {
        $this->expectException(RuntimeException::class);

        PhpStan::getBuilder()->addUncheckedExceptionsFrom('phpstan/phpstan');
    }

    public function testSetParameterMergesLikeItsPluralForm(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->setParameter('exceptions', ['check' => ['tooWideThrowType' => false]])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);

        $check = $exceptions['check'] ?? null;

        self::assertIsArray($check);
        self::assertFalse($check['tooWideThrowType'] ?? null);
        self::assertTrue($check['throwTypeCovariance'] ?? null);
    }

    public function testRemovingAParameterFirstReplacesItOutright(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->removeParameter('exceptions')
            ->setParameter('exceptions', ['check' => ['tooWideThrowType' => false]])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertSame(['check' => ['tooWideThrowType' => false]], $exceptions);
    }

    public function testIgnoredErrorsAreAppendedAndDeduplicated(): void
    {
        $entry    = ['identifier' => 'acme.rule'];
        $baseline = PhpStan::getConfig()['parameters']['ignoreErrors'] ?? null;

        self::assertIsArray($baseline);

        $ignoredErrors = PhpStan::getBuilder()
            ->addIgnoredErrors([$entry])
            ->addIgnoredErrors([$entry])
            ->build()['parameters']['ignoreErrors'] ?? null
        ;

        self::assertIsArray($ignoredErrors);
        self::assertCount(count($baseline) + 1, $ignoredErrors);
    }

    public function testAnIgnoredErrorTakesFourShapes(): void
    {
        $ignoredErrors = PhpStan::getBuilder()
            ->addIgnoredErrors([
                'acme.bare',
                'acme.mapped' => false,
                '/^A message$/',
                ['identifier' => 'acme.full', 'paths' => ['src/Legacy.php']],
            ])
            ->build()['parameters']['ignoreErrors'] ?? null
        ;

        self::assertIsArray($ignoredErrors);
        self::assertContains(['identifier' => 'acme.bare'], $ignoredErrors);
        self::assertContains(['identifier' => 'acme.mapped', 'reportUnmatched' => false], $ignoredErrors);
        self::assertContains('/^A message$/', $ignoredErrors);
        self::assertContains(['identifier' => 'acme.full', 'paths' => ['src/Legacy.php']], $ignoredErrors);
    }

    public function testAnIgnoredErrorTheBaselineSetCanBeRemoved(): void
    {
        $ignoredErrors = PhpStan::getBuilder()
            ->removeIgnoredErrors(['ternary.shortNotAllowed'])
            ->build()['parameters']['ignoreErrors'] ?? null
        ;

        self::assertIsArray($ignoredErrors);
        self::assertNotContains(['identifier' => 'ternary.shortNotAllowed', 'reportUnmatched' => false], $ignoredErrors);
    }

    public function testReplacingARuleLeavesOneRegistrationUnderTheNewArguments(): void
    {
        $services = PhpStan::getBuilder()
            ->replaceRule(InternalUsageRule::class, ['allowedCallers' => ['Acme\Tests']])
            ->build()['services']
        ;

        $registrations = array_values(array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === InternalUsageRule::class,
        ));

        self::assertCount(1, $registrations);
        self::assertSame(['allowedCallers' => ['Acme\Tests']], $registrations[0]['arguments'] ?? null);
    }

    public function testAnIncludeCanBeRemoved(): void
    {
        $paths = PhpStan::getBuilder()
            ->addIncludes(['first.neon', 'second.neon'])
            ->removeIncludes(['first.neon'])
            ->build()['includes']
        ;

        self::assertSame(['second.neon'], $paths);
    }

    public function testBootstrapFilesAreAppended(): void
    {
        $bootstrapFiles = PhpStan::getBuilder()
            ->addBootstrapFiles(['first.php'])
            ->addBootstrapFiles(['second.php'])
            ->build()['parameters']['bootstrapFiles'] ?? null
        ;

        self::assertSame(['first.php', 'second.php'], $bootstrapFiles);
    }

    public function testTheContainerConfiguratorIsScannedWhereItsPackageIsInstalled(): void
    {
        self::assertTrue(Package::DependencyInjection->isInstalled());
        self::assertFalse(Package::FrameworkBundle->isInstalled());

        $scanFiles = PhpStan::getConfig()['parameters']['scanFiles'] ?? null;

        self::assertIsArray($scanFiles);

        $scanFile = $scanFiles[0] ?? null;

        self::assertIsString($scanFile);
        self::assertStringEndsWith('Loader/Configurator/ContainerConfigurator.php', $scanFile);
    }

    public function testScannedFilesAreAppendedReplacedAndDropped(): void
    {
        $parameters = PhpStan::getBuilder()
            ->setScanFiles(['first.php'])
            ->addScanFiles(['second.php'])
            ->removeScanFiles(['first.php'])
            ->addScanDirectories(['stubs'])
            ->build()['parameters']
        ;

        self::assertSame(['second.php'], $parameters['scanFiles'] ?? null);
        self::assertSame(['stubs'], $parameters['scanDirectories'] ?? null);
    }

    public function testTheSamePhpAtTestRegisteredTwiceIdenticallyIsOneService(): void
    {
        $rule = PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
            'module'   => 'Acme\User',
            'label'    => 'User',
            'siblings' => ['Acme\Email'],
        ]);

        $services = PhpStan::getBuilder()
            ->addArchitecture([$rule, $rule])
            ->build()['services']
        ;

        self::assertCount(1, array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === ModuleIsolatedTest::class,
        ));
    }

    public function testTheSamePhpAtTestRegisteredTwiceWithDifferentArgumentsFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(ModuleIsolatedTest::class);

        PhpStan::getBuilder()->addArchitecture([
            PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
                'module'   => 'Acme\User',
                'label'    => 'User',
                'siblings' => ['Acme\Email'],
            ]),
            PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
                'module'   => 'Acme\Email',
                'label'    => 'Email',
                'siblings' => ['Acme\User'],
            ]),
        ]);
    }

    public function testRemovingAnOptionMapKeyLeavesTheRestOfTheMap(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->removeExceptions(['check'])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);
        self::assertArrayNotHasKey('check', $exceptions);
        self::assertNotEmpty($exceptions['uncheckedExceptionRegexes'] ?? null);
    }

    public function testRemovingAParameterLeavesPhpStanOnItsOwnDefault(): void
    {
        $parameters = PhpStan::getBuilder()
            ->removeParameters(['exceptions', 'bootstrapFiles'])
            ->build()['parameters']
        ;

        self::assertArrayNotHasKey('exceptions', $parameters);
        self::assertArrayNotHasKey('bootstrapFiles', $parameters);
    }

    public function testPathsAreAddedAndRemovedWithoutTouchingTheRest(): void
    {
        $paths = PhpStan::getBuilder()
            ->setPaths(['src', 'tests'])
            ->addPaths(['stubs', 'src'])
            ->removePaths(['tests'])
            ->build()['parameters']['paths'] ?? null
        ;

        self::assertSame(['src', 'stubs'], $paths);
    }

    public function testExcludedPathsAreAddedPerGroupAndRemovedFromEveryGroup(): void
    {
        $phpStan = PhpStan::getBuilder()
            ->setExcludedPaths(['src/Legacy.php'])
            ->addExcludedPaths(['analyse' => ['src/Generated.php']])
        ;

        $excluded = $phpStan->build()['parameters']['excludePaths'] ?? null;

        self::assertSame([
            'analyse'        => ['src/Generated.php'],
            'analyseAndScan' => ['src/Legacy.php'],
        ], $excluded);

        $excluded = $phpStan
            ->removeExcludedPaths(['src/Legacy.php'])
            ->build()['parameters']['excludePaths'] ?? null
        ;

        self::assertSame(['analyse' => ['src/Generated.php']], $excluded);
    }

    public function testUncheckedExceptionsAreReplacedAndDroppedByTheFormTheyWereGivenIn(): void
    {
        $phpStan = PhpStan::getBuilder()
            ->setUncheckedExceptions([
                UnreachableException::class,
                '/Unreachable$/',
            ])
        ;

        $exceptions = $phpStan->build()['parameters']['exceptions'] ?? null;

        self::assertIsArray($exceptions);
        self::assertSame([UnreachableException::class], $exceptions['uncheckedExceptionClasses'] ?? null);
        self::assertSame(['/Unreachable$/'], $exceptions['uncheckedExceptionRegexes'] ?? null);

        $exceptions = $phpStan
            ->removeUncheckedExceptions([UnreachableException::class])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);
        self::assertSame([], $exceptions['uncheckedExceptionClasses'] ?? null);
        self::assertSame(['/Unreachable$/'], $exceptions['uncheckedExceptionRegexes'] ?? null);
    }

    public function testPlainParametersArePassedThrough(): void
    {
        $parameters = PhpStan::getBuilder()
            ->setLevel(9)
            ->setTemporaryDirectory('.cache/elsewhere')
            ->setParameters(['treatPhpDocTypesAsCertain' => false])
            ->setIncludes(['only.neon'])
            ->setIgnoredErrors(['acme.only'])
            ->setBootstrapFiles(['first.php'])
            ->addBootstrapFiles(['second.php'])
            ->removeBootstrapFiles(['first.php'])
            ->setFeatureToggles(['acme' => true])
            ->build()
        ;

        self::assertSame(9, $parameters['parameters']['level'] ?? null);
        self::assertSame('.cache/elsewhere', $parameters['parameters']['tmpDir'] ?? null);
        self::assertFalse($parameters['parameters']['treatPhpDocTypesAsCertain'] ?? null);
        self::assertSame(['only.neon'], $parameters['includes']);
        self::assertSame([['identifier' => 'acme.only']], $parameters['parameters']['ignoreErrors'] ?? null);
        self::assertSame(['second.php'], $parameters['parameters']['bootstrapFiles'] ?? null);

        $featureToggles = $parameters['parameters']['featureToggles'] ?? null;

        self::assertIsArray($featureToggles);
        self::assertTrue($featureToggles['acme'] ?? null);

        $withoutToggle = PhpStan::getBuilder()
            ->setFeatureToggles(['acme' => true])
            ->removeFeatureToggles(['acme'])
            ->build()['parameters']['featureToggles'] ?? []
        ;

        self::assertIsArray($withoutToggle);
        self::assertArrayNotHasKey('acme', $withoutToggle);
    }

    public function testOptionalExtensionOptionsMergeAndDrop(): void
    {
        /**
         * @var array<non-empty-string, array<non-empty-string, mixed>> $parameters
         */
        $parameters = PhpStan::getBuilder()
            ->setStrictRules(['allRules' => false])
            ->setTypePerfect(['null_over_false' => true])
            ->setTypeCoverage(['return_type' => 90])
            ->setSymplify(['ctor' => false])
            ->setSymfony(['containerXmlPath' => 'var/container.xml'])
            ->setDoctrine(['literalString' => false])
            ->setPhpUnit(['reportMissingDataProviderReturnType' => false])
            ->build()['parameters']
        ;

        self::assertFalse($parameters['strictRules']['allRules'] ?? null);
        self::assertTrue($parameters['type_perfect']['null_over_false'] ?? null);
        self::assertSame(90, $parameters['type_coverage']['return_type'] ?? null);
        self::assertFalse($parameters['symplify']['ctor'] ?? null);
        self::assertSame('var/container.xml', $parameters['symfony']['containerXmlPath'] ?? null);
        self::assertFalse($parameters['doctrine']['literalString'] ?? null);
        self::assertFalse($parameters['phpunit']['reportMissingDataProviderReturnType'] ?? null);

        /**
         * @var array<non-empty-string, array<non-empty-string, mixed>> $dropped
         */
        $dropped = PhpStan::getBuilder()
            ->removeStrictRules(['allRules'])
            ->removeTypePerfect(['null_over_false'])
            ->removeTypeCoverage(['return_type'])
            ->removeSymplify(['ctor'])
            ->removeSymfony(['containerXmlPath'])
            ->removeDoctrine(['literalString'])
            ->removePhpUnit(['reportMissingDataProviderReturnType'])
            ->build()['parameters']
        ;

        self::assertArrayNotHasKey('allRules', $dropped['strictRules'] ?? []);
        self::assertArrayNotHasKey('null_over_false', $dropped['type_perfect'] ?? []);
        self::assertArrayNotHasKey('return_type', $dropped['type_coverage'] ?? []);
        self::assertArrayNotHasKey('ctor', $dropped['symplify'] ?? []);
        self::assertArrayNotHasKey('containerXmlPath', $dropped['symfony'] ?? []);
        self::assertArrayNotHasKey('literalString', $dropped['doctrine'] ?? []);
        self::assertArrayNotHasKey('reportMissingDataProviderReturnType', $dropped['phpunit'] ?? []);
    }

    public function testTheBaselineTurnsOnTypeChecksAndTheSymplifyGroupsTheProjectCanUse(): void
    {
        $config = PhpStan::getConfig();

        self::assertSame(
            [
                'constant_type' => 100,
                'declare'       => 100,
                'param_type'    => 100,
                'property_type' => 100,
                'return_type'   => 100,
            ],
            $config['parameters']['type_coverage'] ?? null,
        );

        self::assertSame(
            [
                'narrow_return'         => true,
                'no_empty_on_object'    => true,
                'no_isset_on_object'    => true,
                'no_mixed'              => true,
                'no_param_type_removal' => true,
                'null_over_false'       => true,
            ],
            $config['parameters']['type_perfect'] ?? null,
        );

        self::assertSame(
            [
                'ctor'              => true,
                'laravelReturnType' => Package::Laravel->isInstalled(),
                'mocks'             => Package::PhpStanPhpUnit->isInstalled(),
                'symfonyReturnType' => Package::DependencyInjection->isInstalled(),
            ],
            $config['parameters']['symplify'] ?? null,
        );

        self::assertContains(NoFindTaggedServiceIdsCallRule::class, $config['rules']);
    }

    public function testServicesAreAddedReplacedAndRemoved(): void
    {
        $extension = PhpStan::configureStaticThrowTypeExtension(FileFinderThrowTypeExtension::class);

        self::assertSame(FileFinderThrowTypeExtension::class, $extension['class']);

        $replaced = PhpStan::getBuilder()->setServices([$extension])->build()['services'];

        self::assertSame([$extension], $replaced);

        $removed = PhpStan::getBuilder()
            ->setServices([$extension])
            ->addServices([$extension])
            ->removeServices([FileFinderThrowTypeExtension::class])
            ->build()['services']
        ;

        self::assertSame([], $removed);
    }

    public function testArchitectureRulesAreRemovedByClassName(): void
    {
        $services = PhpStan::getBuilder()
            ->addArchitecture([PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
                'module'   => 'Acme\User',
                'label'    => 'User',
                'siblings' => ['Acme\Email'],
            ])])
            ->removeArchitecture([ModuleIsolatedTest::class])
            ->build()['services']
        ;

        self::assertSame([], array_filter(
            $services,
            static fn (array $service): bool => $service['class'] === ModuleIsolatedTest::class,
        ));
    }

    public function testConfigureRuleTagsTheDefinition(): void
    {
        $rule = PhpStan::configureRule(BoolishPrefixRule::class, ['allowedNames' => ['acme']]);

        self::assertSame(BoolishPrefixRule::class, $rule['class']);
        self::assertSame(['allowedNames' => ['acme']], $rule['arguments'] ?? null);

        self::assertArrayNotHasKey('arguments', PhpStan::configureRule(BoolishPrefixRule::class));
    }

    public function testThePreferredClassesMapNamesReplacements(): void
    {
        $preferred = PhpStan::getPreferredClassesMap();

        self::assertSame(DateTimeImmutable::class, $preferred['DateTime'] ?? null);
    }

    public function testTheEditorTemplateIsBuiltFromTheEditorName(): void
    {
        $editorUrl = PhpStan::getBuilder()
            ->setEditor('phpstorm', '/repo')
            ->build()['parameters']['editorUrl'] ?? null
        ;

        self::assertSame('phpstorm://open?file=/repo/%%relFile%%&line=%%line%%', $editorUrl);
    }

    public function testUncheckedExceptionsAddedByHandAndByPackage(): void
    {
        $exceptions = PhpStan::getBuilder()
            ->addUncheckedExceptions([InvalidArgumentException::class])
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($exceptions);

        $classes = $exceptions['uncheckedExceptionClasses'] ?? [];

        self::assertIsArray($classes);
        self::assertContains(InvalidArgumentException::class, $classes);

        $dropped = PhpStan::getBuilder()
            ->addUncheckedExceptionsFrom('brnshkr/config')
            ->removeUncheckedExceptionsFrom('brnshkr/config')
            ->build()['parameters']['exceptions'] ?? null
        ;

        self::assertIsArray($dropped);

        $remaining = $dropped['uncheckedExceptionClasses'] ?? [];

        self::assertIsArray($remaining);
        self::assertNotContains(UnreachableException::class, $remaining);
    }

    public function testAPathThatIsNotANonEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('non-empty string');

        // @phpstan-ignore argument.type (the guard exists for a caller who ignores the signature, so the test has to)
        PhpStan::getBuilder()->setPaths(['src', '']);
    }

    public function testSetArchitectureDropsThePhpAtTestsAlreadyRegistered(): void
    {
        $services = PhpStan::getBuilder()
            ->addArchitecture([PhpStan::configurePhpAtTest(ModuleIsolatedTest::class, [
                'module'   => 'Acme\User',
                'label'    => 'User',
                'siblings' => ['Acme\Email'],
            ])])
            ->setArchitecture([])
            ->build()['services']
        ;

        $phpAtTests = array_filter(
            $services,
            static fn (array $service): bool => ($service['tags'] ?? []) === ['phpat.test'],
        );

        self::assertSame([], $phpAtTests);
        self::assertNotSame([], $services);
    }
}
