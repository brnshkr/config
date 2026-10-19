<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\FencedCode;
use Brnshkr\Config\Process;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\FencedCodeRuleTest;
use RuntimeException;

use function array_first;

/**
 * @internal
 *
 * @phpstan-import-type ExampleError from FencedCodeRule
 *
 * @see FencedCodeRuleTest
 */
final class MakefileExampleAnalyzer
{
    private const string MAKEFILE_NAME = 'Makefile';

    private const string PARSE_GOAL = '_fenced-code-parse';

    private ?bool $isMakeInstalled = null;

    /**
     * @param non-empty-string $temporaryDirectory
     * @param list<string> $sharedMakefileSuffixes
     */
    public function __construct(
        private readonly string $temporaryDirectory,
        private readonly string $sharedMakefilePath,
        private readonly array $sharedMakefileSuffixes,
    ) {}

    /**
     * @return list<ExampleError>
     */
    public function analyze(string $code): array
    {
        if (!$this->isMakeInstalled()) {
            return [];
        }

        $isSharedMakefileIncluded = false;
        $makefileLines            = [];

        foreach (Str::split($code, "\n") as $line) {
            $includeGroups = Str::match($line, '/^(?<indent>\s*)include\s+(?<path>\S.*)$/D');

            if (!isset($includeGroups['indent'], $includeGroups['path'])) {
                $makefileLines[] = $line;
            } elseif (Str::endsWithAny(Str::trim($includeGroups['path']), $this->sharedMakefileSuffixes)) {
                $isSharedMakefileIncluded = true;
                $makefileLines[]          = $includeGroups['indent'] . 'include ' . $this->sharedMakefilePath;
            } else {
                $makefileLines[] = $includeGroups['indent'] . '-include ' . $includeGroups['path'];
            }
        }

        $makefile = Str::join([
            ...$makefileLines,
            '',
            self::PARSE_GOAL . ': ; @:',
            '',
        ], "\n");

        return FencedCode::runInScratchDirectory(
            $this->temporaryDirectory,
            self::MAKEFILE_NAME,
            $makefile,
            function (string $scratchDirectory) use ($isSharedMakefileIncluded): array {
                $exampleError = $this->runMake($scratchDirectory, [
                    '-n',
                    self::PARSE_GOAL,
                ], FencedCodeRule::PROBLEM_PARSE);

                if ($exampleError === null && $isSharedMakefileIncluded) {
                    $exampleError = $this->runMake($scratchDirectory, ['help'], 'Code block fails `make help`');
                }

                return $exampleError === null ? [] : [$exampleError];
            },
        ) ?? [];
    }

    /**
     * @param non-empty-string $workingDirectory
     * @param list<string> $arguments
     *
     * @return ?ExampleError
     */
    private function runMake(string $workingDirectory, array $arguments, string $problem): ?array
    {
        try {
            $processResult = Process::run([
                'env',
                '-u',
                'MAKEFLAGS',
                '-u',
                'MAKELEVEL',
                'NO_COLOR=1',
                'make',
                '--no-print-directory',
                ...$arguments,
            ], $workingDirectory);
        } catch (RuntimeException) {
            return null;
        }

        if ($processResult['exitCode'] === 0) {
            return null;
        }

        $outputLines = Str::split(Str::trim($processResult['errorOutput'] . "\n" . $processResult['output']), "\n");
        $errorGroups = [];

        foreach ($outputLines as $outputLine) {
            $errorGroups = Str::match(
                Str::trimSuffix($outputLine, '  Stop.'),
                '/^(?<file>[^\s:]+):(?<line>\d+): \*\*\* (?<message>.+)$/D',
            ) ?: Str::match($outputLine, '/^(?:\[[^\]]*\] )?(?<message>.+?) at \.\/Makefile:(?<line>\d+)\.$/D');

            if ($errorGroups !== []) {
                break;
            }
        }

        $isBlockLine = ($errorGroups['file'] ?? self::MAKEFILE_NAME) === self::MAKEFILE_NAME
            && isset($errorGroups['line']);

        return FencedCodeRule::buildExampleError(
            $problem,
            $errorGroups['message'] ?? array_first($outputLines),
            $isBlockLine ? (int) $errorGroups['line'] : 1,
        );
    }

    private function isMakeInstalled(): bool
    {
        if ($this->isMakeInstalled !== null) {
            return $this->isMakeInstalled;
        }

        try {
            $processResult = Process::run([
                'make',
                '--version',
            ], $this->temporaryDirectory);

            $this->isMakeInstalled = $processResult['exitCode'] === 0;
        } catch (RuntimeException) {
            $this->isMakeInstalled = false;
        }

        return $this->isMakeInstalled;
    }
}
