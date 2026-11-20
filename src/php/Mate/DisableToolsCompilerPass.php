<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Tests\Mate\DisableToolsCompilerPassTest;
use Composer\InstalledVersions;
use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

use function is_array;
use function sprintf;

/**
 * Disables this extension's tools in a project without the shared Makefile,
 * on top of the features the project disables itself.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see DisableToolsCompilerPassTest
 */
final readonly class DisableToolsCompilerPass implements CompilerPassInterface
{
    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    public const array TOOL_NAMES = [
        'brnshkr-commitlint-check',
        'brnshkr-module-config',
        'brnshkr-module-list',
        'brnshkr-quality-check',
        'brnshkr-test-run',
    ];

    private const string PARAMETER_DISABLED_FEATURES = 'mate.disabled_features';

    /**
     * Returns whether the project runs without the shared Makefile.
     *
     * @return bool true for a project other than this library without the `###> brnshkr/config ###` block in its `Makefile`
     *
     * @throws IOException when the project's `Makefile` cannot be read
     * @throws RuntimeException when the package name is not found in this library's `composer.json`
     */
    public static function isNeeded(): bool
    {
        return Project::isMakefileMissing(InstalledVersions::getRootPackage()['name'], Project::getRootDirectory());
    }

    /**
     * @throws ParameterNotFoundException when the `mate.disabled_features` parameter is not defined
     * @throws RuntimeException when the `mate.disabled_features` parameter is not an array of arrays
     * @throws RuntimeException when the package name is not found in this library's `composer.json`
     */
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $packageFullName  = self::getPackageFullName();
        $disabledFeatures = $container->getParameter(self::PARAMETER_DISABLED_FEATURES);
        $packageFeatures  = is_array($disabledFeatures) ? $disabledFeatures[$packageFullName] ?? [] : null;

        if (!is_array($disabledFeatures) || !is_array($packageFeatures)) {
            throw new RuntimeException(sprintf(
                'The "%s" parameter must be an array of arrays.',
                self::PARAMETER_DISABLED_FEATURES,
            ));
        }

        foreach (self::TOOL_NAMES as $toolName) {
            $packageFeatures[$toolName] = [
                'enabled' => false,
            ];
        }

        $disabledFeatures[$packageFullName] = $packageFeatures;

        $container->setParameter(self::PARAMETER_DISABLED_FEATURES, $disabledFeatures);
    }

    /**
     * @throws RuntimeException
     */
    private static function getPackageFullName(): string
    {
        return ComposerJson::forThisLibrary()->getPackageFullName()
            ?? throw new RuntimeException('Package name not found in "composer.json".');
    }
}
