<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpCsFixer\Fixer;

use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\FileFinder;
use Brnshkr\Config\PhpCsFixer\Fixer\Trait\FixerTrait;
use Brnshkr\Config\Tests\PhpCsFixer\Fixer\MarkdownSafeFixerTest;
use Override;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

/**
 * @internal Brnshkr\Config
 *
 * @see MarkdownSafeFixerTest
 */
// @phpstan-ignore brnshkr.interfaceSuffix (php-cs-fixer names every fixer *Fixer)
final readonly class MarkdownSafeFixer implements WhitespacesAwareFixerInterface
{
    use FixerTrait;

    public function __construct(
        private FixerInterface $fixer,
    ) {}

    public static function name(): never
    {
        throw new UnreachableException('A markdown safe fixer is named after the fixer it wraps.');
    }

    #[Override]
    public function getName(): string
    {
        return self::getFixerName($this->fixer->getName());
    }

    public function getWrappedName(): string
    {
        return $this->fixer->getName();
    }

    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function supports(SplFileInfo $file): bool
    {
        return !FileFinder::isMarkdown($file->getFilename()) && $this->fixer->supports($file);
    }

    #[Override]
    public function isCandidate(Tokens $tokens): bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    #[Override]
    public function isRisky(): bool
    {
        return $this->fixer->isRisky();
    }

    #[Override]
    // @phpstan-ignore symplify.preferredClass (Required by php-cs-fixer's FixerInterface)
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixer->fix($file, $tokens);
    }

    #[Override]
    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    #[Override]
    public function getPriority(): int
    {
        return $this->fixer->getPriority();
    }

    #[Override]
    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        if ($this->fixer instanceof WhitespacesAwareFixerInterface) {
            $this->fixer->setWhitespacesConfig($config);
        }
    }
}
