#!/usr/bin/env php
<?php

/**
 * Runs Symfony AI Mate where this project's tools run, handing every argument over untouched.
 * `tools:*` and `resources:read` answer in TOON unless a `--format` is given.
 *
 * @internal Brnshkr\Config\Mate
 */

declare(strict_types=1);

use Brnshkr\Config\Str;
use Symfony\Component\Process\Process;

require __DIR__ . '/../vendor/autoload.php';

$toonCommands = [
    'tools:call',
    'tools:inspect',
    'tools:list',
    'resources:read',
];

$commandLine = $_SERVER['argv'] ?? [];
$arguments   = \is_array($commandLine) ? \array_values(\array_filter(\array_slice($commandLine, 1), is_string(...))) : [];

if ($arguments === []) {
    $arguments = ['list'];
}

$hasFormat = \array_any(
    $arguments,
    static fn (string $argument): bool => $argument === '--format' || Str::startsWith($argument, '--format='),
);

if (!$hasFormat && \in_array($arguments[0], $toonCommands, true)) {
    $arguments[] = '--format=toon';
}

$process = new Process(
    command: ['make', '--no-print-directory', '--silent', '_mate-from-stdin'],
    cwd: \dirname(__DIR__),
    input: \implode("\0", $arguments) . "\0",
    timeout: null,
);

exit($process->run(static function (string $type, string $buffer): void {
    \fwrite($type === Process::ERR ? \STDERR : \STDOUT, $buffer);
}));
