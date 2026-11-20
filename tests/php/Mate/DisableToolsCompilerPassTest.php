<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Mate;

use Brnshkr\Config\Mate\DisableToolsCompilerPass;
use Brnshkr\Config\Tests\Trait\PackageNameTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

use function array_keys;
use function class_exists;
use function sort;

/**
 * @internal
 */
#[CoversClass(DisableToolsCompilerPass::class)]
final class DisableToolsCompilerPassTest extends TestCase
{
    use PackageNameTrait;

    public function testTheToolsJoinTheFeaturesTheProjectDisables(): void
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('mate.disabled_features', [
            'acme/user' => [
                'acme-user-list' => [
                    'enabled' => false,
                ],
            ],
        ]);

        new DisableToolsCompilerPass()->process($containerBuilder);

        $disabledFeatures = $containerBuilder->getParameter('mate.disabled_features');
        $packageFullName  = self::getPackageFullName();

        self::assertIsArray($disabledFeatures);
        self::assertSame(['acme/user', $packageFullName], array_keys($disabledFeatures));
        self::assertArrayHasKey($packageFullName, $disabledFeatures);
        self::assertIsArray($disabledFeatures[$packageFullName]);
        self::assertSame(DisableToolsCompilerPass::TOOL_NAMES, array_keys($disabledFeatures[$packageFullName]));
    }

    public function testItNamesEveryToolOfTheExtension(): void
    {
        $toolNames = [];

        foreach (new Finder()->files()->in(__DIR__ . '/../../../src/php/Mate/Tool')->name('*.php') as $file) {
            $className = 'Brnshkr\Config\Mate\Tool\\' . $file->getFilenameWithoutExtension();

            self::assertTrue(class_exists($className));

            foreach (new ReflectionClass($className)->getMethods() as $reflectionMethod) {
                foreach ($reflectionMethod->getAttributes(MateTool::class) as $reflectionAttribute) {
                    $toolNames[] = $reflectionAttribute->newInstance()->name;
                }
            }
        }

        sort($toolNames);

        self::assertSame(DisableToolsCompilerPass::TOOL_NAMES, $toolNames);
    }
}
