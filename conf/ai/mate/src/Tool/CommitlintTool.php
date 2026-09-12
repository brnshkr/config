<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Lints commit messages against the Commitlint rules of this repository.
 *
 * @internal
 */
final class CommitlintTool
{
    /**
     * @param string $message the commit message draft to lint (subject line plus optional body)
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    #[McpTool(
        name: 'project-commitlint-check',
        description: 'Lints a commit message draft against the Commitlint rules of this repository without creating a commit. Use before committing to validate type, scope and body formatting.',
    )]
    public function checkMessage(string $message): string
    {
        return Project::encode(Project::run(
            ['bun', '--bun', 'x', 'commitlint', '--config', './conf/commitlint.mjs'],
            input: $message,
        ));
    }
}
