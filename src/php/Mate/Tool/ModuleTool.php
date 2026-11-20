<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Composer\Command\PrintModuleConfigCommand;
use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Module;
use Brnshkr\Config\Str;
use JsonException;
use RuntimeException;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function arsort;
use function escapeshellarg;
use function is_array;
use function sprintf;

/**
 * @internal
 */
final class ModuleTool
{
    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    #[MateTool(
        name: 'brnshkr-module-list',
        description: 'Lists the brnshkr/config PHP modules with the config file make runs each one with and its required and optional packages, installed or not.',
    )]
    public function listModules(): string
    {
        $configPaths = self::getConfigPaths();
        $modules     = [];

        foreach (Module::cases() as $module) {
            $packages = [];

            foreach ($module->getRequiredPackages() as $package) {
                $packages[$package->value] = [
                    'isRequired'  => true,
                    'isInstalled' => $package->isInstalled(),
                ];
            }

            foreach ($module->getOptionalPackages() as $package) {
                $packages[$package->value] = [
                    'isRequired'  => false,
                    'isInstalled' => $package->isInstalled(),
                ];
            }

            $modules[$module->value] = [
                'configFile' => $configPaths[$module->value] ?? null,
                'packages'   => $packages,
            ];
        }

        return Project::encode($modules);
    }

    /**
     * @param string $module - the module to resolve, e.g. "phpstan"
     * @param string $section - a dotted path into the config, e.g. "parameters" or "services.6"; empty prints the whole config
     *
     * @throws ConsoleLogicException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    #[MateTool(
        name: 'brnshkr-module-config',
        description: 'Prints the resolved config of a brnshkr/config PHP module, or one part of it through a dotted section path. A part too long to print is replaced by the call that prints it.',
    )]
    public function printModuleConfig(string $module, string $section = ''): string
    {
        $resolvedModule = Module::tryFrom($module);

        if ($resolvedModule === null) {
            return Project::encodeUnknownValue('module', $module, Module::values());
        }

        $configPath = self::getConfigPaths()[$resolvedModule->value] ?? null;

        if ($configPath === null) {
            return Project::encode([
                'exitCode' => 1,
                'output'   => sprintf('The "%s" module is not installed.', $resolvedModule->value),
            ]);
        }

        $result = Project::runTarget('composer', [
            'ARGS' => new PrintModuleConfigCommand()->getName() . ' ' . $resolvedModule->value . ' --path=' . escapeshellarg($configPath),
        ]);

        if ($result['exitCode'] !== 0) {
            return Project::encodeResult($result);
        }

        $configPart    = Json::decode($result['output']);
        $walkedSection = '';

        foreach ($section === '' ? [] : Str::split($section, '.') as $sectionKey) {
            if (!is_array($configPart) || !array_key_exists($sectionKey, $configPart)) {
                return Project::encodeUnknownValue(
                    'section',
                    $section,
                    is_array($configPart) && $configPart !== []
                        ? array_map(static fn (int|string $key): string => self::getSectionPath($walkedSection, $key), array_keys($configPart))
                        : [$walkedSection],
                );
            }

            $configPart    = $configPart[$sectionKey];
            $walkedSection = self::getSectionPath($walkedSection, $sectionKey);
        }

        if (!is_array($configPart)) {
            return Project::encode([$section => $configPart]);
        }

        return Project::encode(self::getFittingPart($configPart, $resolvedModule, $section));
    }

    /**
     * @return array<value-of<Module>, ?string>
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    private static function getConfigPaths(): array
    {
        $result = Project::runTarget('_print-variables', [
            '_PRINTED_VARIABLES' => Str::join(array_map(static fn (Module $module): string => $module->getConfigVariableName(), Module::cases()), ' '),
        ]);

        $printedValues = [];

        foreach (Str::matchAll($result['output'], '/^(?<name>\w+)=(?<value>.*)$/m') as $printedVariable) {
            $printedValues[$printedVariable['name'] ?? ''] = $printedVariable['value'] ?? '';
        }

        $configPaths = [];

        foreach (Module::cases() as $module) {
            $configPath = $printedValues[$module->getConfigVariableName()] ?? '';

            $configPaths[$module->value] = $configPath === '' ? null : Path::makeRelative($configPath, Project::getRootDirectory());
        }

        return $configPaths;
    }

    /**
     * @param array<array-key, mixed> $configPart
     *
     * @return array<array-key, mixed>
     */
    private static function getFittingPart(array $configPart, Module $module, string $section): array
    {
        $encodedLengths = array_map(static fn (mixed $value): int => Str::length(Project::encode([$value])), $configPart);

        arsort($encodedLengths);

        foreach ($encodedLengths as $key => $encodedLength) {
            if (Str::length(Project::encode($configPart)) <= Project::MAX_OUTPUT_LENGTH) {
                break;
            }

            $configPart[$key] = sprintf(
                'Omitted %d characters. Run `brnshkr-module-config --module=%s --section=%s`.',
                $encodedLength,
                $module->value,
                self::getSectionPath($section, $key),
            );
        }

        return $configPart;
    }

    private static function getSectionPath(string $section, int|string $key): string
    {
        return Str::trim($section . '.' . $key, '.');
    }
}
