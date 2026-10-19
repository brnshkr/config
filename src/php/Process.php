<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\ProcessTest;
use RuntimeException;

use function fclose;
use function feof;
use function fread;
use function function_exists;
use function microtime;
use function proc_close;
use function proc_open;
use function proc_terminate;
use function sprintf;
use function stream_select;
use function stream_set_blocking;

/**
 * @internal
 *
 * @phpstan-type ProcessResult array{
 *     exitCode: int,
 *     output: string,
 *     errorOutput: string,
 * }
 *
 * @see ProcessTest
 */
final readonly class Process
{
    private const int TIMEOUT_SECONDS = 60;

    private const int READ_CHUNK_BYTES = 8_192;

    private const int MICROSECONDS_PER_SECOND = 1_000_000;

    private function __construct() {}

    /**
     * @param non-empty-list<string> $command
     * @param non-empty-string $workingDirectory
     *
     * @return ProcessResult
     *
     * @throws RuntimeException
     */
    public static function run(array $command, string $workingDirectory): array
    {
        if (!function_exists('proc_open')) {
            throw new RuntimeException(sprintf(
                'Running "%s" needs proc_open(), which this PHP installation disabled.',
                Str::join($command, ' '),
            ));
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/process here to keep package as lightweight as possible)
        $process = proc_open($command, $descriptors, $processPipes, $workingDirectory);

        if ($process === false) {
            throw new RuntimeException(sprintf(
                'Unable to run "%s".',
                Str::join($command, ' '),
            ));
        }

        if (!isset($processPipes[0], $processPipes[1], $processPipes[2])) {
            // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/process here to keep package as lightweight as possible)
            proc_close($process);

            throw new RuntimeException(sprintf(
                '"%s" opened no pipes.',
                Str::join($command, ' '),
            ));
        }

        fclose($processPipes[0]);

        [$output, $errorOutput] = self::readUntilEndOfFile($process, [$processPipes[1], $processPipes[2]], $command);

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/process here to keep package as lightweight as possible)
        $exitCode = proc_close($process);

        return [
            'exitCode'    => $exitCode,
            'output'      => $output,
            'errorOutput' => $errorOutput,
        ];
    }

    /**
     * @param resource $process
     * @param array{resource, resource} $pipes
     * @param list<string> $command
     *
     * @return array{string, string}
     *
     * @throws RuntimeException
     */
    private static function readUntilEndOfFile(mixed $process, array $pipes, array $command): array
    {
        $outputs   = ['', ''];
        $openPipes = $pipes;
        $deadline  = microtime(true) + self::TIMEOUT_SECONDS;

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        while ($openPipes !== []) {
            $remainingSeconds = $deadline - microtime(true);

            if ($remainingSeconds <= 0) {
                // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/process here to keep package as lightweight as possible)
                proc_terminate($process);

                foreach ($openPipes as $openPipe) {
                    fclose($openPipe);
                }

                // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/process here to keep package as lightweight as possible)
                proc_close($process);

                throw new RuntimeException(sprintf(
                    '"%s" did not finish within %d seconds.',
                    Str::join($command, ' '),
                    self::TIMEOUT_SECONDS,
                ));
            }

            $readable     = $openPipes;
            $writable     = [];
            $errored      = [];
            $wholeSeconds = (int) $remainingSeconds;
            $microseconds = (int) (($remainingSeconds - $wholeSeconds) * self::MICROSECONDS_PER_SECOND);

            stream_select($readable, $writable, $errored, $wholeSeconds, $microseconds);

            foreach ($openPipes as $index => $openPipe) {
                $chunk = fread($openPipe, self::READ_CHUNK_BYTES);

                if ($chunk === false || ($chunk === '' && feof($openPipe))) {
                    fclose($openPipe);
                    unset($openPipes[$index]);

                    continue;
                }

                $outputs[$index] .= $chunk;
            }
        }

        return $outputs;
    }
}
