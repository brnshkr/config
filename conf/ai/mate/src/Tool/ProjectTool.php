<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use Brnshkr\Config\Str;
use JsonException;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function basename;
use function glob;
use function in_array;
use function is_string;
use function sort;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * Project-level consistency checks for this repository.
 *
 * @internal
 */
final class ProjectTool
{
    /**
     * PHPStan rules that have no ESLint counterpart by design.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array PHP_ONLY_RULES = [
        'NoNamedArgumentsTagRule',
    ];

    /**
     * ESLint rules that have no PHPStan counterpart by design.
     *
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array JS_ONLY_RULES = [
        'require-import-alias',
        'require-import-attributes',
    ];

    /**
     * @throws IOException
     * @throws JsonException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-version-sync-check',
        description: 'Compares the version declared in package.json, composer.json and conf/Makefile (VERSION), which must stay in sync (CI validates this).',
    )]
    public function checkVersionSync(): string
    {
        $packageVersion  = $this->getVersionOf('package.json');
        $composerVersion = $this->getVersionOf('composer.json');
        $makefileVersion = $this->getMakefileVersion();

        return Project::encode([
            'packageJson'  => $packageVersion,
            'composerJson' => $composerVersion,
            'makefile'     => $makefileVersion,
            'isInSync'     => $packageVersion === $composerVersion && $composerVersion === $makefileVersion,
        ]);
    }

    #[McpTool(
        name: 'project-rule-docs-audit',
        description: 'Cross-checks the custom PHPStan and ESLint rules against their doc pages and against each other, reporting rules without docs, docs without rules and rules that exist on only one of the two stacks.',
    )]
    public function auditRuleDocs(): string
    {
        $rootDirectory = Project::getRootDirectory();
        $phpRules      = $this->getRuleNames(sprintf('%s/src/php/PhpStan/Rule/*Rule.php', $rootDirectory), '.php');
        $phpDocs       = $this->getRuleNames(sprintf('%s/docs/php/phpstan/rules/*Rule.md', $rootDirectory), '.md');
        $jsRules       = $this->getRuleNames(sprintf('%s/src/js/eslint/configs/builtin/*.ts', $rootDirectory), '.ts');
        $jsDocs        = $this->getRuleNames(sprintf('%s/docs/js/eslint/rules/*.md', $rootDirectory), '.md');

        return Project::encode([
            'php'    => $this->buildDocsReport($phpRules, $phpDocs),
            'js'     => $this->buildDocsReport($jsRules, $jsDocs),
            'parity' => $this->buildParityReport($phpRules, $jsRules),
        ]);
    }

    /**
     * @param list<string> $rules
     * @param list<string> $docs
     *
     * @return array{
     *     implementedRules: list<string>,
     *     documentedRules: list<string>,
     *     rulesMissingDocs: list<string>,
     *     docsMissingRules: list<string>,
     * }
     */
    private function buildDocsReport(array $rules, array $docs): array
    {
        return [
            'implementedRules' => $rules,
            'documentedRules'  => $docs,
            'rulesMissingDocs' => array_values(array_diff($rules, $docs)),
            'docsMissingRules' => array_values(array_diff($docs, $rules)),
        ];
    }

    /**
     * @param list<string> $phpRules
     * @param list<string> $jsRules
     *
     * @return array{
     *     pairedRules: list<string>,
     *     phpRulesMissingJsCounterpart: list<string>,
     *     jsRulesMissingPhpCounterpart: list<string>,
     *     intentionallyPhpOnly: list<string>,
     *     intentionallyJsOnly: list<string>,
     * }
     */
    private function buildParityReport(array $phpRules, array $jsRules): array
    {
        $pairedRules  = [];
        $missingJsFor = [];

        foreach ($phpRules as $phpRule) {
            $jsRule = $this->toJsRuleName($phpRule);

            if (in_array($jsRule, $jsRules, true)) {
                $pairedRules[] = $jsRule;

                continue;
            }

            if (!in_array($phpRule, self::PHP_ONLY_RULES, true)) {
                $missingJsFor[] = $phpRule;
            }
        }

        return [
            'pairedRules'                  => $pairedRules,
            'phpRulesMissingJsCounterpart' => $missingJsFor,
            'jsRulesMissingPhpCounterpart' => array_values(array_diff(
                $jsRules,
                [...$pairedRules, ...self::JS_ONLY_RULES],
            )),
            'intentionallyPhpOnly' => self::PHP_ONLY_RULES,
            'intentionallyJsOnly'  => self::JS_ONLY_RULES,
        ];
    }

    private function toJsRuleName(string $phpRule): string
    {
        return s($phpRule)->trimSuffix('Rule')->kebab()->toString();
    }

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string $extension
     *
     * @return list<string>
     */
    private function getRuleNames(string $pattern, string $extension): array
    {
        return array_values(array_filter(
            $this->getBasenamesByGlob($pattern, $extension),
            static fn (string $name): bool => $name !== 'index',
        ));
    }

    /**
     * @param non-empty-string $file
     *
     * @throws IOException
     * @throws JsonException
     * @throws RuntimeException
     */
    private function getVersionOf(string $file): string
    {
        $contents = new Filesystem()->readFile(sprintf('%s/%s', Project::getRootDirectory(), $file));
        $manifest = Json::decode($contents);
        $version  = $manifest['version'] ?? null;

        if (!is_string($version)) {
            throw new RuntimeException(sprintf('Failed reading the version from "%s".', $file));
        }

        return $version;
    }

    /**
     * @throws IOException
     * @throws RuntimeException
     */
    private function getMakefileVersion(): string
    {
        $contents = new Filesystem()->readFile(sprintf('%s/conf/Makefile', Project::getRootDirectory()));
        $matches  = Str::match($contents, '/^VERSION\s*[!+:?]*=\s*(?<version>[^\s#]+)/m');

        if (!isset($matches['version']) || $matches['version'] === '') {
            throw new RuntimeException('Failed reading the VERSION from "conf/Makefile".');
        }

        return $matches['version'];
    }

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string $extension
     *
     * @return list<string>
     */
    private function getBasenamesByGlob(string $pattern, string $extension): array
    {
        $names = array_map(
            static fn (string $path): string => basename($path, $extension),
            glob($pattern) ?: [],
        );

        sort($names);

        return $names;
    }
}
