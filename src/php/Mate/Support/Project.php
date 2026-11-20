<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Support;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use Composer\InstalledVersions;
use RuntimeException;
use Symfony\AI\Mate\Encoding\ResponseEncoder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

use function is_file;
use function is_readable;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal Brnshkr\Config
 */
final class Project
{
    public const int MAX_OUTPUT_LENGTH = 20_000;

    /**
     * @phpstan-var array<non-empty-string, false>
     */
    private const array INHERITED_MAKE_VARIABLES = [
        'MAKEFLAGS'      => false,
        'MAKELEVEL'      => false,
        'MFLAGS'         => false,
        '_IS_ANNOUNCING' => false,
    ];

    private function __construct() {}

    /**
     * @throws IOException
     * @throws RuntimeException
     */
    public static function isMakefileMissing(string $rootPackageName, string $rootDirectory): bool
    {
        $packageFullName = self::getPackageFullName();
        $makefilePath    = $rootDirectory . '/Makefile';

        if ($rootPackageName === $packageFullName) {
            return false;
        }

        return !is_file($makefilePath)
            || !is_readable($makefilePath)
            || !Str::contains(new Filesystem()->readFile($makefilePath), '###> ' . $packageFullName . ' ###');
    }

    /**
     * @throws IOException
     * @throws RuntimeException
     */
    public static function getRootDirectory(): string
    {
        $packageFullName = self::getPackageFullName();

        foreach (InstalledVersions::getAllRawData() as $installedPackages) {
            if (isset($installedPackages['versions'][$packageFullName])) {
                return Path::canonicalize($installedPackages['root']['install_path']);
            }
        }

        throw new RuntimeException(sprintf(
            'Package "%s" is not installed.',
            $packageFullName,
        ));
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function encode(array $payload): string
    {
        return ResponseEncoder::encode($payload);
    }

    /**
     * @param array{
     *     exitCode: int,
     *     output: string,
     * } $result
     * @param array<non-empty-string, mixed> $extraFields
     */
    public static function encodeResult(array $result, array $extraFields = []): string
    {
        $output = s($result['output']);

        if ($output->length() > self::MAX_OUTPUT_LENGTH) {
            $output = s(sprintf(
                "[... %d characters truncated ...]\n%s",
                $output->length() - self::MAX_OUTPUT_LENGTH,
                $output->slice(-self::MAX_OUTPUT_LENGTH)->toString(),
            ));
        }

        return self::encode([
            'exitCode' => $result['exitCode'],
            'output'   => $output->toString(),
            ...$extraFields,
        ]);
    }

    /**
     * @param non-empty-string $target
     * @param array<non-empty-string, string> $variables
     * @param ?non-empty-string $input
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    public static function runTarget(string $target, array $variables = [], ?string $input = null): array
    {
        $targetPrefix = Str::fromEnvironment('BRNSHKR_TARGET_PREFIX');

        $command = [
            'make',
            '--no-print-directory',
            'NO_COLOR=1',
            Str::startsWith($target, '_') ? '_' . $targetPrefix . Str::slice($target, 1) : $targetPrefix . $target,
        ];

        foreach ($variables as $name => $value) {
            $command[] = $name . '=' . Str::replace($value, '$', '$$');
        }

        $result         = self::run($command, input: $input);
        $recipeExitCode = Str::match($result['output'], '/(?:^|\n)make: \*\*\* \[[^\n\]]*\] Error (?<exitCode>\d+)\z/')['exitCode'] ?? null;

        if ($recipeExitCode === null) {
            return $result;
        }

        return [
            'exitCode' => (int) $recipeExitCode,
            'output'   => Str::trim(Str::beforeLast($result['output'], 'make: *** [')),
        ];
    }

    public static function createUnexpectedOutputException(string $source): RuntimeException
    {
        return new RuntimeException(sprintf(
            'Unexpected output from "%s".',
            $source,
        ));
    }

    /**
     * @param non-empty-list<non-empty-string> $command
     * @param positive-int $timeoutSeconds
     * @param ?non-empty-string $input
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    public static function run(array $command, int $timeoutSeconds = 600, ?string $input = null): array
    {
        $process = new Process($command, self::getRootDirectory(), self::INHERITED_MAKE_VARIABLES, $input, (float) $timeoutSeconds);

        $process->run();

        $output = s($process->getOutput() . "\n" . $process->getErrorOutput())
            // eslint-disable no-control-regex, regexp/no-control-character -- Stripping control characters is what this pattern is for
            ->replaceMatches('/\x1B\[[\x30-\x3F]*[\x20-\x2F]*[\x40-\x7E]/', '')
            ->replaceMatches('/\x1B\][^\x07\x1B]*(?:\x07|\x1B\\\)/', '')
            ->replaceMatches('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '')
            // eslint-enable no-control-regex, regexp/no-control-character
            ->trim()
        ;

        return [
            'exitCode' => $process->getExitCode() ?? -1,
            'output'   => $output->toString(),
        ];
    }

    /**
     * @param non-empty-string $noun
     * @param non-empty-list<string> $validValues
     */
    public static function encodeUnknownValue(string $noun, string $value, array $validValues): string
    {
        return self::encode([
            'exitCode' => 1,
            'output'   => sprintf('Unknown %s "%s". Allowed %ss are: %s.', $noun, $value, $noun, Str::joinAsQuotedList($validValues)),
        ]);
    }

    /**
     * @throws IOException
     * @throws RuntimeException
     */
    private static function getPackageFullName(): string
    {
        return ComposerJson::forThisLibrary()->getPackageFullName()
            ?? throw new RuntimeException('Package name not found in "composer.json".');
    }
}
