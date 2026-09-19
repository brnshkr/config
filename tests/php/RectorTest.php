<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\EditorUrl;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Json;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\Rector;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\Configuration\RectorConfigBuilder;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
#[CoversClass(Rector::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(EditorUrl::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Json::class)]
#[UsesClass(Module::class)]
#[UsesClass(Package::class)]
#[UsesClass(Str::class)]
final class RectorTest extends TestCase
{
    public function testRulesAreAddedOnTopOfThePreparedSets(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()
            ->addRules([SimplifyIfReturnBoolRector::class])
            ->build()
        ;

        self::assertSame([SimplifyIfReturnBoolRector::class], self::readRectorState($rectorConfigBuilder, 'rules'));
    }

    public function testSetRulesReplacesWhatWasAdded(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()
            ->addRules([SimplifyIfReturnBoolRector::class])
            ->setRules([])
            ->build()
        ;

        self::assertSame([], self::readRectorState($rectorConfigBuilder, 'rules'));
    }

    public function testSkipsAreAddedToTheBaseline(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()->addSkips(['src/legacy'])->build();

        self::assertContains('src/legacy', self::readRectorState($rectorConfigBuilder, 'skip'));
    }

    public function testSkipsAreReplacedAndDropped(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()
            ->setSkips(['only/this'])
            ->removeSkips(['only/this'])
            ->build()
        ;

        self::assertNotContains('only/this', self::readRectorState($rectorConfigBuilder, 'skip'));
    }

    public function testRemoveRulesSkipsInstead(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()->removeRules([SimplifyIfReturnBoolRector::class])->build();

        self::assertContains(SimplifyIfReturnBoolRector::class, self::readRectorState($rectorConfigBuilder, 'skip'));
        self::assertNotContains(SimplifyIfReturnBoolRector::class, self::readRectorState($rectorConfigBuilder, 'rules'));
    }

    public function testPathsAreReplacedAppendedAndDropped(): void
    {
        $rectorConfigBuilder = Rector::getBuilder()
            ->setPaths(['src', 'tests'])
            ->addPaths(['stubs', 'src'])
            ->removePaths(['tests'])
            ->build()
        ;

        self::assertSame(['src', 'stubs'], self::readRectorState($rectorConfigBuilder, 'paths'));
    }

    public function testTheFinderArgumentNarrowsThePaths(): void
    {
        $rectorConfigBuilder = Rector::getConfig(new Finder()->in(__DIR__ . '/../../src/php/Composer'));

        $paths = self::readRectorState($rectorConfigBuilder, 'paths');

        self::assertNotEmpty($paths);

        foreach ($paths as $path) {
            self::assertIsString($path);
            self::assertStringContainsString('/src/php/Composer/', $path);
        }
    }

    public function testFromAddsToAConfigAnotherFileBuilt(): void
    {
        $baseline = Rector::getConfig();
        $paths    = self::readRectorState($baseline, 'paths');

        $rectorConfigBuilder = Rector::from($baseline)->addSkips(['src/legacy'])->build();

        self::assertContains('src/legacy', self::readRectorState($rectorConfigBuilder, 'skip'));
        self::assertSame($paths, self::readRectorState($rectorConfigBuilder, 'paths'));
    }

    public function testBuildHandsOverOnlyOnce(): void
    {
        $rector = Rector::getBuilder()->addRules([SimplifyIfReturnBoolRector::class]);

        $rector->build();

        self::assertCount(1, self::readRectorState($rector->build(), 'rules'));
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function readRectorState(RectorConfigBuilder $rectorConfigBuilder, string $name): array
    {
        $reflectionProperty = new ReflectionProperty(RectorConfigBuilder::class, $name);

        return (array) $reflectionProperty->getValue($rectorConfigBuilder);
    }
}
