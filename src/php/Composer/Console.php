<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\ComposerJson;
use Composer\IO\IOInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Throwable;

use function array_any;
use function array_map;
use function array_merge;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function max;
use function sprintf;
use function Symfony\Component\String\s;

use const PHP_EOL;

/**
 * @internal Brnshkr\Config\Composer
 */
final readonly class Console
{
    public function __construct(
        public IOInterface $io,
        private ComposerJson $libraryComposerJson,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function writeDebug(string $message): void
    {
        if ($this->io->isVeryVerbose()) {
            $this->write($message, 'fg=cyan');
        }
    }

    /**
     * @throws RuntimeException
     */
    public function writeInfo(string $message): void
    {
        if ($this->io->isVerbose()) {
            $this->write($message, 'info');
        }
    }

    /**
     * @throws RuntimeException
     */
    public function writeNotice(string $message): void
    {
        $this->write($message, 'fg=magenta');
    }

    /**
     * @throws RuntimeException
     */
    public function writeWarning(string $message): void
    {
        $this->write($message, 'fg=yellow');
    }

    /**
     * @throws RuntimeException
     */
    public function writeError(string|Throwable $messageOrThrowable): void
    {
        $message = match (true) {
            is_string($messageOrThrowable) => $messageOrThrowable,
            $this->io->isVerbose()         => sprintf(
                '<comment>%s</comment> (Code: %s): %s%sThrown at %s%s',
                $messageOrThrowable::class,
                $messageOrThrowable->getCode(),
                $messageOrThrowable->getMessage(),
                PHP_EOL,
                $messageOrThrowable->getFile() . ':' . $messageOrThrowable->getLine(),
                $this->io->isVeryVerbose() ? (PHP_EOL . $messageOrThrowable->getTraceAsString()) : '',
            ),
            default => $messageOrThrowable->getMessage() . ' <comment>(Retry with -v or -vv for more details)</comment>',
        };

        $this->io->writeError($this->getFormattedMessage($message, 'fg=red'));
    }

    /**
     * @throws RuntimeException
     */
    public function writeLogo(): void
    {
        $packageNameAndVersion = s($this->libraryComposerJson->getPackageName())
            ->camel()
            ->title()
            ->append(' (')
            ->append($this->libraryComposerJson->getPackageVersion())
            ->append(')')
        ;

        $this->writeRaw(sprintf(
            '<comment>%s%s%s%s</comment><info>%s%s</info>',
            '   ___               __   __' . PHP_EOL,
            '  / _ )_______  ____/ /  / /__ ____' . PHP_EOL,
            ' / _  / __/ _ \(_--/ _ \/  ´_// __/' . PHP_EOL,
            '/____/_/ /_//_/___/_//_/_/\_\/_/' . PHP_EOL . PHP_EOL,
            $packageNameAndVersion->toString() . PHP_EOL,
            s('‾')->repeat($packageNameAndVersion->length()),
        ));
    }

    /**
     * @param list<Command> $commands
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    public function writeHelp(array $commands): void
    {
        $this->writeRaw('<comment>Available commands:</comment>');

        $width = self::getColumnWidth($commands);

        foreach ($commands as $command) {
            $name         = $command->getName();
            $spacingWidth = $width - Helper::width($name);
            $aliases      = $command->getAliases();

            $this->writeRaw(sprintf(
                '  <info>%s</info>%s%s%s',
                $name,
                s(' ')->repeat($spacingWidth)->toString(),
                $aliases !== [] ? '[' . implode('|', $aliases) . '] ' : '',
                $command->getDescription(),
            ));
        }
    }

    /**
     * @throws RuntimeException
     */
    public function isConfirmed(string $question, bool $isDefaultAnswerYes = true): bool
    {
        $question = $this->getFormattedMessage(
            message: sprintf(
                '%s [%s/%s] ',
                $question,
                $isDefaultAnswerYes ? 'Y' : 'y',
                $isDefaultAnswerYes ? 'n' : 'N',
            ),
            tag: 'info',
        );

        return $this->io->askConfirmation(
            question: $question,
            default: $isDefaultAnswerYes,
        );
    }

    /**
     * @template T of array<int|string, int|string>
     * @template U of bool
     *
     * @param mixed $default
     * @phpstan-param T $choices
     * @phpstan-param key-of<T>|value-of<T>|list<value-of<T>>|list<key-of<T>>|array<key-of<T>, value-of<T>> $default
     * @phpstan-param U $isMultiselect
     *
     * @phpstan-return (U is false ? value-of<T> : list<value-of<T>>)
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function select(
        string $question,
        array $choices,
        $default,
        bool $isMultiselect = false,
    ) {
        $default = is_array($default)
            ? implode(',', array_map(strval(...), $default))
            : (string) $default;

        $question = $this->getFormattedMessage(
            message: sprintf(
                '%s <comment>(%s choice)</comment> [%s]',
                $question,
                $isMultiselect ? 'Multiple' : 'Single',
                $default,
            ),
            tag: 'info',
        );

        $answerIndex = $this->io->select(
            question: $question,
            choices: array_map(strval(...), $choices),
            default: $default,
            multiselect: (bool) $isMultiselect,
        );

        if (is_array($answerIndex)) {
            $answer = [];

            foreach ($answerIndex as $singleAnswerIndex) {
                if (isset($choices[$singleAnswerIndex])) {
                    $answer[] = $choices[$singleAnswerIndex];
                }
            }

            return $answer;
        }

        if (is_bool($answerIndex)) {
            $answerIndex = $answerIndex ? '1' : '0';
        }

        return $choices[$answerIndex] ?? '';
    }

    /**
     * @throws RuntimeException
     */
    public function write(string $message, string $tag): void
    {
        $this->io->write($this->getFormattedMessage($message, $tag));
    }

    /**
     * @throws RuntimeException
     */
    public function writeRaw(string $message): void
    {
        $this->io->write($message);
    }

    /**
     * @param list<Command> $commands
     *
     * @throws RuntimeException
     */
    private static function getColumnWidth(array $commands): int
    {
        $widths = array_map(
            static function (Command $command): array {
                $aliases = $command->getAliases();

                if (array_any($aliases, static fn (mixed $aliases): bool => !is_string($aliases))) {
                    throw new RuntimeException('Expected alias values to be an array of strings.');
                }

                /**
                 * @var list<?string> $aliasesCasted
                 */
                $aliasesCasted = $aliases;

                return array_map(Helper::width(...), [$command->getName(), ...$aliasesCasted]);
            },
            $commands,
        );

        return $widths !== []
            ? max(array_merge(...$widths)) + 2
            : 0;
    }

    /**
     * @throws RuntimeException
     */
    private function getFormattedMessage(string $message, string $tag): string
    {
        return sprintf(
            '<%s>[%s]</%s> %s',
            $tag,
            $this->libraryComposerJson->getPackageFullName(),
            $tag,
            $message,
        );
    }
}
