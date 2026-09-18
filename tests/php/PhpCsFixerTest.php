<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\Module;
use Brnshkr\Config\Package;
use Brnshkr\Config\PhpCsFixer;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

use function array_key_first;
use function count;

/**
 * @internal
 */
#[CoversClass(PhpCsFixer::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(Module::class)]
#[UsesClass(Package::class)]
#[UsesClass(Str::class)]
final class PhpCsFixerTest extends TestCase
{
    public function testRulesAreMergedIntoTheBaseline(): void
    {
        $baseline = PhpCsFixer::getConfig()->getRules();

        $rules = PhpCsFixer::getBuilder()
            ->addRules(['simplified_null_return' => true])
            ->build()
            ->getRules()
        ;

        self::assertCount(count($baseline) + 1, $rules);
        self::assertTrue($rules['simplified_null_return'] ?? null);
    }

    public function testARuleGivenAgainOverridesTheBaselineValue(): void
    {
        $baseline = PhpCsFixer::getConfig()->getRules();
        $name     = array_key_first($baseline);

        self::assertIsString($name);
        self::assertNotSame('', $name);

        $rules = PhpCsFixer::getBuilder()
            ->addRules([$name => false])
            ->build()
            ->getRules()
        ;

        self::assertCount(count($baseline), $rules);
        self::assertFalse($rules[$name] ?? null);
    }

    public function testSetRulesReplacesTheBaseline(): void
    {
        $rules = PhpCsFixer::getBuilder()
            ->setRules(['simplified_null_return' => true])
            ->build()
            ->getRules()
        ;

        self::assertSame(['simplified_null_return' => true], $rules);
    }

    public function testRulesAreRemovedByName(): void
    {
        $baseline = PhpCsFixer::getConfig()->getRules();
        $name     = array_key_first($baseline);

        self::assertIsString($name);
        self::assertNotSame('', $name);

        $rules = PhpCsFixer::getBuilder()
            ->removeRules([$name])
            ->build()
            ->getRules()
        ;

        self::assertCount(count($baseline) - 1, $rules);
        self::assertArrayNotHasKey($name, $rules);
    }

    public function testFromAddsToAConfigAnotherFileBuilt(): void
    {
        $config = PhpCsFixer::getConfig();

        $rules = PhpCsFixer::from($config)
            ->addRules(['simplified_null_return' => true])
            ->build()
            ->getRules()
        ;

        self::assertCount(count($config->getRules()), $rules);
        self::assertTrue($rules['simplified_null_return'] ?? null);
    }

    public function testTheFinderArgumentNarrowsTheFiles(): void
    {
        $finder = PhpCsFixer::getConfig(new Finder()->in(__DIR__ . '/../../src/php/Composer'))->getFinder();

        $paths = [];

        foreach ($finder as $file) {
            $paths[] = $file->getPathname();
        }

        self::assertNotEmpty($paths);

        foreach ($paths as $path) {
            self::assertStringContainsString('/src/php/Composer/', $path);
        }
    }
}
