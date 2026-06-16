<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

use function array_keys;
use function in_array;
use function sprintf;

/**
 * Exposes the module registry and resolved module configurations.
 *
 * @internal
 */
final class ModuleTool
{
    /**
     * @return array<string, array{
     *     configFile: non-empty-string,
     *     packages: array<string, array{
     *         isRequired: bool,
     *         isInstalled: bool,
     *     }>,
     * }>
     */
    #[McpTool(
        name: 'project-modules-list',
        description: 'Lists all brnshkr/config modules (phpcsfixer, phpstan, rector, twigcsfixer) with their default config file and the required/optional packages including installation status.',
    )]
    public function listModules(): array
    {
        $modules = [];

        foreach (Module::MAP as $name => $info) {
            $packages = [];

            foreach ($info['packages']['requiredAll'] as $package) {
                $packages[$package] = [
                    'isRequired'  => true,
                    'isInstalled' => InstalledVersions::isInstalled($package),
                ];
            }

            foreach ($info['packages']['optional'] ?? [] as $package) {
                $packages[$package] = [
                    'isRequired'  => false,
                    'isInstalled' => InstalledVersions::isInstalled($package),
                ];
            }

            $modules[$name] = [
                'configFile' => sprintf('conf/%s.dist.php', $name),
                'packages'   => $packages,
            ];
        }

        return $modules;
    }

    /**
     * @param string $module the module to resolve (one of the names returned by project-modules-list)
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-module-config',
        description: 'Prints the fully resolved configuration of a brnshkr/config module as JSON via the composer plugin command "brnshkr:config:print-module-config".',
    )]
    public function printModuleConfig(string $module): array
    {
        if (!in_array($module, array_keys(Module::MAP), true)) {
            return [
                'exitCode' => 1,
                'output'   => sprintf(
                    'Unknown module "%s". Valid modules: %s.',
                    $module,
                    Str::joinAsQuotedList(array_keys(Module::MAP)),
                ),
            ];
        }

        return Project::run(['php', 'scripts/composer.php', 'brnshkr:config:print-module-config', $module]);
    }
}
