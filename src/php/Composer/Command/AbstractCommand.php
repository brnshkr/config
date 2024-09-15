<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\Console;
use Brnshkr\Config\ComposerJson;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Override;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\AbstractString;
use Throwable;

use function array_map;
use function implode;
use function Symfony\Component\String\s;

/**
 * @internal Brnshkr\Config\Composer
 */
abstract class AbstractCommand extends BaseCommand
{
    protected private(set) InputInterface $input;

    protected private(set) OutputInterface $output;

    protected private(set) Composer $composer;

    protected private(set) Console $console;

    protected private(set) ComposerJson $libraryComposerJson;

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->composer = $this->requireComposer();
        $this->console  = new Console($this->getIO(), $this->libraryComposerJson);

        try {
            $this->wrappedInitialize();
        } catch (Throwable $throwable) {
            $this->console->writeError($throwable);
        }
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function configure(): void
    {
        $this->libraryComposerJson = ComposerJson::forThisLibrary();
        $packageOrganization       = $this->libraryComposerJson->getPackageOrganization();
        $packageName               = $this->libraryComposerJson->getPackageName();

        $commandNamePrefix = implode(':', array_map(
            static fn (string $string): string => s($string)->slice(length: 1)->toString(),
            [$packageOrganization, $packageName],
        ));

        $kebabName = s($this->getName() ?: new ReflectionClass($this)->getShortName())
            ->beforeLast('Command')
            ->snake()
            ->replace('_', '-')
        ;

        $mainCommandName = $packageOrganization . ':' . $packageName;
        $isMainCommand   = $kebabName->replace('-', ':')->equalsTo($mainCommandName);

        $alias = $isMainCommand
            ? $commandNamePrefix
            : $commandNamePrefix . ':' . implode('', array_map(
                static fn (AbstractString $string): string => $string->slice(length: 1)->toString(),
                $kebabName->split('-'),
            ));

        try {
            $this
                ->setName($isMainCommand ? $mainCommandName : ($mainCommandName . ':' . $kebabName->toString()))
                ->setDescription(
                    s($this->getDescriptionTemplate())
                        ->replace('{{ package_full_name }}', $this->libraryComposerJson->getPackageFullName())
                        ->toString(),
                )
                ->setAliases([$alias])
            ;

            $this->wrappedConfigure();
        } catch (Throwable $throwable) {
            $this->console->writeError($throwable);
        }
    }

    /**
     * @phpstan-return self::SUCCESS|self::FAILURE|self::INVALID
     *
     * @throws LogicException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->wrappedExecute();
        } catch (Throwable $throwable) {
            try {
                $this->console->writeError($throwable);
            } catch (RuntimeException $exception) {
                throw new LogicException($exception->getMessage(), previous: $exception);
            }

            return self::FAILURE;
        }
    }

    protected function wrappedConfigure(): void {}

    protected function wrappedInitialize(): void {}

    /**
     * @phpstan-return self::SUCCESS|self::FAILURE|self::INVALID
     */
    abstract protected function wrappedExecute(): int;

    abstract protected function getDescriptionTemplate(): string;
}
