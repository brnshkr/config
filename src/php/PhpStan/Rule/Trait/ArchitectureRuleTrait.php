<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Trait;

use Brnshkr\Config\Str;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function array_map;
use function implode;
use function sprintf;

/**
 * @internal
 */
trait ArchitectureRuleTrait
{
    /**
     * @param list<non-empty-string> $namespaces
     *
     * @return list<SelectorInterface>
     */
    private static function buildNamespaceSelectors(array $namespaces): array
    {
        return array_map(static fn (string $namespace): SelectorInterface => Selector::inNamespace($namespace), $namespaces);
    }

    private static function regexEscapeNamespace(string $namespace): string
    {
        return Str::replace($namespace, '\\', '\\\\');
    }

    /**
     * @param non-empty-string $suffix
     */
    private static function selectByClassnameSuffix(string $suffix): SelectorInterface
    {
        return Selector::classname(sprintf('/.*%s$/', $suffix), true);
    }

    /**
     * @param non-empty-string $root
     * @param non-empty-list<SelectorInterface> $detection
     * @param non-empty-string $targetSegment
     * @param non-empty-string $noun
     */
    private static function buildPlacementRule(string $root, array $detection, string $targetSegment, string $noun): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(Selector::inNamespace($root), ...$detection))
            ->should()
            ->beNamed(sprintf(
                '/^%s\\\%s\\\.+$/',
                self::regexEscapeNamespace($root),
                self::regexEscapeNamespace($targetSegment),
            ), regex: true)
            ->because(sprintf('%s must reside in %s\%s\*.', $noun, $root, $targetSegment))
        ;
    }

    /**
     * @param non-empty-string $baseClass
     * @param non-empty-string $because
     */
    private static function buildMustExtendRule(SelectorInterface $selector, string $baseClass, string $because): BuildStep
    {
        return PHPat::rule()
            ->classes($selector)
            ->should()
            ->extend()
            ->classes(Selector::classname($baseClass))
            ->because($because)
        ;
    }

    /**
     * @param non-empty-string $interface
     * @param non-empty-string $because
     */
    private static function buildMustImplementRule(SelectorInterface $selector, string $interface, string $because): BuildStep
    {
        return PHPat::rule()
            ->classes($selector)
            ->should()
            ->implement()
            ->classes(Selector::classname($interface))
            ->because($because)
        ;
    }

    /**
     * @param non-empty-string $sourceNamespace
     * @param non-empty-string $forbiddenNamespace
     * @param non-empty-string $because
     */
    private static function buildNamespaceIsolationRule(string $sourceNamespace, string $forbiddenNamespace, string $because): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace($sourceNamespace))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace($forbiddenNamespace))
            ->because($because)
        ;
    }

    /**
     * @param non-empty-string $sourceNamespace
     * @param non-empty-string $forbiddenClass
     * @param non-empty-string $because
     */
    private static function buildClassIsolationRule(string $sourceNamespace, string $forbiddenClass, string $because): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace($sourceNamespace))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname($forbiddenClass))
            ->because($because)
        ;
    }

    /**
     * @param non-empty-string $root
     * @param non-empty-list<non-empty-string> $allowedFolders
     * @param non-empty-string $presetLabel
     */
    private static function buildRoleFoldersExhaustiveRule(string $root, array $allowedFolders, string $presetLabel): BuildStep
    {
        $foldersAlternation = implode('|', array_map(self::regexEscapeNamespace(...), $allowedFolders));

        return PHPat::rule()
            ->classes(Selector::inNamespace($root))
            ->should()
            ->beNamed(sprintf(
                '/^%s\\\(?:%s)\\\.+$/',
                self::regexEscapeNamespace($root),
                $foldersAlternation,
            ), regex: true)
            ->because(sprintf(
                '%s classes inside %s must reside in one of the allowed role folders: %s.',
                $presetLabel,
                $root,
                Str::joinAsQuotedList($allowedFolders, 'disjunction'),
            ))
        ;
    }
}
