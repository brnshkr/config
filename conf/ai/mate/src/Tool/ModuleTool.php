<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
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
     * @throws \RuntimeException when the project's `composer.json` cannot be read
     */
    #[McpTool(
        name: 'project-modules-list',
        description: 'Lists all brnshkr/config modules (phpcsfixer, phpstan, rector, twigcsfixer) with their default config file and the required/optional packages including installation status.',
    )]
    public function listModules(): string
    {
        $modules = [];

        foreach (Module::MAP as $name => $info) {
            $packages = [];

            foreach ($info['packages']['requiredAll'] as $package) {
                $packages[$package->value] = [
                    'isRequired'  => true,
                    'isInstalled' => $package->isInstalled(),
                ];
            }

            foreach ($info['packages']['optional'] ?? [] as $package) {
                $packages[$package->value] = [
                    'isRequired'  => false,
                    'isInstalled' => $package->isInstalled(),
                ];
            }

            $modules[$name] = [
                'configFile' => sprintf('conf/%s.dist.php', $name),
                'packages'   => $packages,
            ];
        }

        return Project::encode($modules);
    }

    /**
     * @param string $module the module to resolve (one of the names returned by project-modules-list)
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-module-config',
        description: 'Prints the fully resolved configuration of a brnshkr/config module as JSON via the composer plugin command "brnshkr:config:print-module-config".',
    )]
    public function printModuleConfig(string $module): string
    {
        if (!in_array($module, array_keys(Module::MAP), true)) {
            return Project::encode([
                'exitCode' => 1,
                'output'   => sprintf(
                    'Unknown module "%s". Valid modules: %s.',
                    $module,
                    Str::joinAsQuotedList(array_keys(Module::MAP)),
                ),
            ]);
        }

        return Project::encode(Project::run(['php', 'scripts/composer.php', 'brnshkr:config:print-module-config', $module]));
    }
}
