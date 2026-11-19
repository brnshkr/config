<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Process;
use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * @internal
 */
#[CoversClass(Process::class)]
#[UsesClass(Str::class)]
final class ProcessTest extends TestCase
{
    private const int PIPE_OVERFLOW_BYTES = 200_000;

    public function testRunReportsTheExitCodeAndBothOutputsApart(): void
    {
        $processResult = Process::run([
            'sh',
            '-c',
            'printf acme-output; printf acme-error >&2; exit 3',
        ], __DIR__);

        self::assertSame([
            'exitCode'    => 3,
            'output'      => 'acme-output',
            'errorOutput' => 'acme-error',
        ], $processResult);
    }

    public function testRunDrainsBothOutputsPastThePipeBuffer(): void
    {
        $processResult = Process::run([
            'sh',
            '-c',
            sprintf('head -c %1$d /dev/zero >&2; head -c %1$d /dev/zero', self::PIPE_OVERFLOW_BYTES),
        ], __DIR__);

        self::assertSame(self::PIPE_OVERFLOW_BYTES, Str::length($processResult['output']));
        self::assertSame(self::PIPE_OVERFLOW_BYTES, Str::length($processResult['errorOutput']));
    }
}
