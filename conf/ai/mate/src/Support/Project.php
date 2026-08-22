<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Support;

use Symfony\AI\Mate\Encoding\ResponseEncoder;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

use function dirname;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * Locates the repository root and runs commands inside it.
 *
 * @internal Brnshkr\Config\Mate
 */
final class Project
{
    private const int MAX_OUTPUT_LENGTH = 20_000;

    private function __construct() {}

    public static function getRootDirectory(): string
    {
        return dirname(__DIR__, 5);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function encode(array $payload): string
    {
        return ResponseEncoder::encode($payload);
    }

    /**
     * @param non-empty-list<non-empty-string> $command
     * @param positive-int $timeoutSeconds
     * @param string|null $input data to pass to the process via stdin
     *
     * @return array{
     *     exitCode: int,
     *     output: string,
     * }
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    public static function run(array $command, int $timeoutSeconds = 600, ?string $input = null): array
    {
        $process = new Process($command, self::getRootDirectory(), null, $input, (float) $timeoutSeconds);

        $process->run();

        $output = s($process->getOutput() . "\n" . $process->getErrorOutput())
            ->replaceMatches('/\x1B\[[\x30-\x3F]*[\x20-\x2F]*[\x40-\x7E]/', '')
            ->replaceMatches('/\x1B\][^\x07\x1B]*(?:\x07|\x1B\\\)/', '')
            ->replaceMatches('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '')
            ->trim()
        ;

        if ($output->length() > self::MAX_OUTPUT_LENGTH) {
            $output = s(sprintf(
                "[... %d characters truncated ...]\n%s",
                $output->length() - self::MAX_OUTPUT_LENGTH,
                $output->slice(-self::MAX_OUTPUT_LENGTH)->toString(),
            ));
        }

        return [
            'exitCode' => $process->getExitCode() ?? -1,
            'output'   => $output->toString(),
        ];
    }
}
