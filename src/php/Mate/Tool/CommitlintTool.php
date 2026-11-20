<?php

declare(strict_types=1);

namespace Brnshkr\Config\Mate\Tool;

use Brnshkr\Config\Mate\Support\Project;
use RuntimeException;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

/**
 * @internal
 */
final class CommitlintTool
{
    /**
     * @param string $message - the commit message draft to lint (subject line plus optional body)
     *
     * @throws LogicException
     * @throws ProcessRuntimeException
     * @throws RuntimeException
     */
    #[MateTool(
        name: 'brnshkr-commitlint-check',
        description: 'Lints a commit message draft against the commitlint rules of this repository without creating a commit. Use before committing to validate type, scope and body formatting.',
    )]
    public function checkMessage(string $message): string
    {
        if ($message === '') {
            return Project::encode([
                'exitCode' => 1,
                'output'   => 'The message is empty.',
            ]);
        }

        return Project::encodeResult(Project::runTarget('commitlint', ['COMMITLINT_SOURCE' => ''], $message));
    }
}
