<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Json;
use Brnshkr\Config\Mate\Support\Project;
use JsonException;
use RuntimeException;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function array_diff;
use function array_filter;
use function array_values;
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
        'NamedArgumentsTagRule',
        'TagDescriptionRule',
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
    #[MateTool(
        name: 'project-version-sync-check',
        description: 'Compares the version declared in package.json and composer.json, which must stay in sync (CI validates this).',
    )]
    public function checkVersionSync(): string
    {
        $packageVersion  = $this->getVersionOf('package.json');
        $composerVersion = $this->getVersionOf('composer.json');

        return Project::encode([
            'packageJson'  => $packageVersion,
            'composerJson' => $composerVersion,
            'isInSync'     => $packageVersion === $composerVersion,
        ]);
    }

    #[MateTool(
        name: 'project-rule-docs-audit',
        description: 'Cross-checks the custom PHPStan and ESLint rules against their doc pages and against each other, reporting rules without docs, docs without rules and rules that exist on only one of the two stacks.',
    )]
    public function auditRuleDocs(): string
    {
        $rootDirectory = Project::getRootDirectory();
        $phpRules      = $this->getRuleNames($rootDirectory . '/src/php/PhpStan/Rule', '*Rule.php', '.php');
        $phpDocs       = $this->getRuleNames($rootDirectory . '/docs/php/phpstan/rules', '*Rule.md', '.md');
        $jsRules       = $this->getRuleNames($rootDirectory . '/src/js/eslint/configs/builtin', '*.ts', '.ts');
        $jsDocs        = $this->getRuleNames($rootDirectory . '/docs/js/eslint/rules', '*.md', '.md');

        return Project::encode([
            'php'    => $this->buildDocsReport($phpRules, $phpDocs),
            'js'     => $this->buildDocsReport($jsRules, $jsDocs),
            'parity' => $this->buildParityReport($phpRules, $jsRules),
        ]);
    }

    /**
     * @param list<non-empty-string> $rules
     * @param list<non-empty-string> $docs
     *
     * @return array{
     *     implementedRules: list<non-empty-string>,
     *     documentedRules: list<non-empty-string>,
     *     rulesMissingDocs: list<non-empty-string>,
     *     docsMissingRules: list<non-empty-string>,
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
     * @param list<non-empty-string> $phpRules
     * @param list<non-empty-string> $jsRules
     *
     * @return array{
     *     pairedRules: list<non-empty-string>,
     *     phpRulesMissingJsCounterpart: list<non-empty-string>,
     *     jsRulesMissingPhpCounterpart: list<non-empty-string>,
     *     intentionallyPhpOnly: list<non-empty-string>,
     *     intentionallyJsOnly: list<non-empty-string>,
     * }
     */
    private function buildParityReport(array $phpRules, array $jsRules): array
    {
        $pairedRules  = [];
        $missingJsFor = [];

        foreach ($phpRules as $phpRule) {
            $jsRule = $this->toJsRuleName($phpRule);

            if ($jsRule !== '' && in_array($jsRule, $jsRules, true)) {
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
     * @param non-empty-string $directory
     * @param non-empty-string $namePattern
     * @param non-empty-string $extension
     *
     * @return list<non-empty-string>
     */
    private function getRuleNames(string $directory, string $namePattern, string $extension): array
    {
        return array_values(array_filter(
            $this->getBasenames($directory, $namePattern, $extension),
            static fn (string $name): bool => $name !== '' && $name !== 'index',
        ));
    }

    /**
     * @param non-empty-string $file
     *
     * @return non-empty-string
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

        if (!is_string($version) || $version === '') {
            throw new RuntimeException(sprintf('Failed reading the version from "%s".', $file));
        }

        return $version;
    }

    /**
     * @param non-empty-string $directory
     * @param non-empty-string $namePattern
     * @param non-empty-string $extension
     *
     * @return list<string>
     */
    private function getBasenames(string $directory, string $namePattern, string $extension): array
    {
        $names = [];

        $finder = new Finder()
            ->files()
            ->in($directory)
            ->depth(0)
            ->name($namePattern)
        ;

        foreach ($finder as $file) {
            $names[] = $file->getBasename($extension);
        }

        sort($names);

        return $names;
    }
}
