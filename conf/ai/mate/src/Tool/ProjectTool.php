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
use function array_map;
use function array_values;
use function basename;
use function glob;
use function is_string;
use function sort;
use function sprintf;

/**
 * Project-level consistency checks for this repository.
 *
 * @internal
 */
final class ProjectTool
{
    /**
     * @return array{
     *     packageJson: string,
     *     composerJson: string,
     *     makefile: string,
     *     isInSync: bool,
     * }
     *
     * @throws IOException
     * @throws JsonException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-version-sync-check',
        description: 'Compares the version declared in package.json, composer.json and conf/Makefile (VERSION), which must stay in sync (CI validates this).',
    )]
    public function checkVersionSync(): array
    {
        $packageVersion  = $this->getVersionOf('package.json');
        $composerVersion = $this->getVersionOf('composer.json');
        $makefileVersion = $this->getMakefileVersion();

        return [
            'packageJson'  => $packageVersion,
            'composerJson' => $composerVersion,
            'makefile'     => $makefileVersion,
            'isInSync'     => $packageVersion === $composerVersion && $composerVersion === $makefileVersion,
        ];
    }

    /**
     * @return array{
     *     implementedRules: list<string>,
     *     documentedRules: list<string>,
     *     rulesMissingDocs: list<string>,
     *     docsMissingRules: list<string>,
     * }
     */
    #[McpTool(
        name: 'project-rule-docs-audit',
        description: 'Cross-checks the custom PHPStan rules in src/php/PhpStan/Rule against their documentation in docs/php/phpstan/rules and reports rules without docs and docs without rules.',
    )]
    public function auditRuleDocs(): array
    {
        $rootDirectory = Project::getRootDirectory();
        $rules         = $this->getBasenamesByGlob(sprintf('%s/src/php/PhpStan/Rule/*Rule.php', $rootDirectory), '.php');
        $docs          = $this->getBasenamesByGlob(sprintf('%s/docs/php/phpstan/rules/*Rule.md', $rootDirectory), '.md');

        return [
            'implementedRules' => $rules,
            'documentedRules'  => $docs,
            'rulesMissingDocs' => array_values(array_diff($rules, $docs)),
            'docsMissingRules' => array_values(array_diff($docs, $rules)),
        ];
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
        $matches  = Str::match($contents, '/^VERSION\s*[?:+!]*=\s*([^#\s]+)/m');

        if (!isset($matches[1]) || $matches[1] === '') {
            throw new RuntimeException('Failed reading the VERSION from "conf/Makefile".');
        }

        return $matches[1];
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
