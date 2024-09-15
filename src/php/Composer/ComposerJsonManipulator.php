<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\ComposerJson;
use Closure;
use Composer\Json\JsonManipulator;
use RuntimeException;

/**
 * @internal Brnshkr\Config\Composer
 */
final readonly class ComposerJsonManipulator
{
    private function __construct(
        private ComposerJson $composerJson,
    ) {}

    public static function forComposerJson(ComposerJson $composerJson): self
    {
        return new self($composerJson);
    }

    /**
     * @param Closure(JsonManipulator $manipulatorCallback): void $manipulatorCallback
     *
     * @throws RuntimeException
     */
    public function manipulate(Closure $manipulatorCallback): void
    {
        $jsonManipulator = new JsonManipulator($this->composerJson->getContent());

        $manipulatorCallback($jsonManipulator);

        $this->composerJson->setContent($jsonManipulator->getContents());
    }
}
