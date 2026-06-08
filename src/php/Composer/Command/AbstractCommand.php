<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer\Command;

use Brnshkr\Config\Composer\Console;
use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use Composer\Command\BaseCommand;
use Composer\Composer;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\AbstractString;
use Throwable;

use function array_map;
use function get_debug_type;
use function getcwd;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
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

        $kebabName = s($this->getName() ?: Str::getClassShortName($this))
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
                        ->replace('{{ package_full_name }}', $packageOrganization . '/' . $packageName)
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
     * @return self::SUCCESS|self::FAILURE|self::INVALID
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
            } catch (RuntimeException $runtimeException) {
                throw new LogicException(
                    message: $runtimeException->getMessage(),
                    code: (int) $runtimeException->getCode(),
                    previous: $throwable,
                );
            }

            return self::FAILURE;
        }
    }

    protected function wrappedConfigure(): void {}

    protected function wrappedInitialize(): void {}

    /**
     * @return self::SUCCESS|self::FAILURE|self::INVALID
     */
    abstract protected function wrappedExecute(): int;

    abstract protected function getDescriptionTemplate(): string;

    /**
     * @return non-empty-string
     */
    protected function getCwd(): string
    {
        return getcwd() ?: '.';
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    protected function getStringArgument(string $name): string
    {
        return self::assertNonEmptyString($this->input->getArgument($name), 'argument', $name);
    }

    /**
     * @return ?non-empty-string
     *
     * @throws InvalidArgumentException
     */
    protected function getOptionalStringArgument(string $name): ?string
    {
        return self::toOptionalNonEmptyString($this->input->getArgument($name), 'argument', $name);
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    protected function getStringListArgument(string $name): array
    {
        return self::toStringList($this->input->getArgument($name), 'argument', $name);
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    protected function getStringOption(string $name): string
    {
        return self::assertNonEmptyString($this->input->getOption($name), 'option', $name);
    }

    /**
     * @return ?non-empty-string
     *
     * @throws InvalidArgumentException
     */
    protected function getOptionalStringOption(string $name): ?string
    {
        return self::toOptionalNonEmptyString($this->input->getOption($name), 'option', $name);
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    protected function getStringListOption(string $name): array
    {
        return self::toStringList($this->input->getOption($name), 'option', $name);
    }

    protected function isBoolOptionEnabled(string $name): bool
    {
        return $this->input->getOption($name) === true;
    }

    /**
     * @param 'argument'|'option' $kind
     *
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function assertNonEmptyString(mixed $value, string $kind, string $name): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for %s "%s", expected string but got %s.',
                $kind,
                $name,
                get_debug_type($value),
            ));
        }

        if (Str::isEmpty($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for %s "%s", expected non-empty string but got an empty one.',
                $kind,
                $name,
            ));
        }

        return $value;
    }

    /**
     * @param 'argument'|'option' $kind
     *
     * @return ?non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function toOptionalNonEmptyString(mixed $value, string $kind, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for %s "%s", expected string but got %s.',
                $kind,
                $name,
                get_debug_type($value),
            ));
        }

        return Str::isEmpty($value) ? null : $value;
    }

    /**
     * @param 'argument'|'option' $kind
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    private static function toStringList(mixed $value, string $kind, string $name): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for %s "%s", expected array but got %s.',
                $kind,
                $name,
                get_debug_type($value),
            ));
        }

        $strings = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value for %s "%s", expected array of strings but got %s as an element.',
                    $kind,
                    $name,
                    get_debug_type($item),
                ));
            }

            $strings[] = $item;
        }

        return $strings;
    }
}
