<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use JsonException;
use RuntimeException;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

use function array_column;
use function array_diff_key;
use function array_filter;
use function array_map;
use function count;
use function is_string;

/**
 * @internal
 */
final class QualityTool
{
    /**
     * @param string $tool - the tool to run, e.g. "eslint" or "phpstan"
     * @param bool $isDryRun - when true (default), a fixer only reports
     * @param bool $isGrouped - when true, counts the findings by rule instead of listing them
     * @param string $arguments - passed on to the tool, e.g. file paths or flags
     *
     * @throws JsonException
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    #[MateTool(
        name: 'brnshkr-quality-check',
        description: 'Runs one quality tool of this project through its make target. A dry run, the default, answers each finding with its file, line, rule and message, a fixer\'s with the diff; pass isDryRun=false to apply fixes, isGrouped=true to count the findings by rule, and arguments to narrow it, e.g. to files.',
    )]
    public function runQualityTool(string $tool, bool $isDryRun = true, bool $isGrouped = false, string $arguments = ''): string
    {
        if (($isDryRun || $isGrouped) && Str::match($arguments, '/(?<![^\s"\'])--fix(?![^\s"\'=])/') !== []) {
            return Project::encode([
                'exitCode' => 1,
                'output'   => 'The "--fix" option cannot be used in a dry run. Try "isDryRun=false".',
            ]);
        }

        $isListed = $isDryRun && !$isGrouped;

        $result = Project::runTarget('_quality', [
            '_QUALITY_TOOL'       => $tool,
            '_IS_QUALITY_DRY_RUN' => $isDryRun ? '1' : '',
            '_IS_QUALITY_GROUPED' => $isGrouped || $isListed ? '1' : '',
            '_GROUP_FORMAT'       => $isListed ? 'tsv' : '',
            'ARGS'                => $arguments,
        ]);

        $findings = $isListed ? self::getFindings($result['output']) : null;

        if ($findings === null) {
            return Project::encodeResult($result);
        }

        if ($findings === []) {
            return Project::encode(['exitCode' => $result['exitCode']]);
        }

        return Project::encode([
            'exitCode' => $result['exitCode'],
            'findings' => $findings,
        ]);
    }

    /**
     * @return ?list<array<'diff'|'file'|'line'|'message'|'rule', int|string|null>>
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    private static function getFindings(string $output): ?array
    {
        if ($output === '') {
            return [];
        }

        $findings = [];

        foreach (Str::split($output, "\n") as $findingRow) {
            $findingFields = Str::split($findingRow, "\t");

            if (count($findingFields) !== 5) {
                return null;
            }

            [$file, $line, $message, $diff, $rule] = array_map(self::decodeField(...), $findingFields);

            $findings[] = [
                'file'    => $file,
                'line'    => $line === '' ? null : (int) $line,
                'rule'    => $rule,
                'message' => $message,
                'diff'    => $diff,
            ];
        }

        foreach (['line', 'message', 'diff'] as $optionalColumn) {
            $columnValues = array_column($findings, $optionalColumn);

            if (array_filter($columnValues, static fn (mixed $columnValue): bool => $columnValue !== null && $columnValue !== '') === []) {
                $findings = array_map(static fn (array $finding): array => array_diff_key($finding, [$optionalColumn => true]), $findings);
            }
        }

        return $findings;
    }

    /**
     * @throws JsonException
     * @throws RuntimeException
     */
    private static function decodeField(string $field): string
    {
        $decodedField = Json::decode('"' . $field . '"', isAssociative: false);

        return is_string($decodedField) ? $decodedField : throw Project::createUnexpectedOutputException('_quality');
    }
}
