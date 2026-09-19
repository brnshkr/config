<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use Brnshkr\Config\Tests\RectorTest;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Contract\Rector\RectorInterface;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_filter;
use function array_unique;
use function array_values;
use function in_array;
use function is_int;

// @codeCoverageIgnoreStart
// NOTICE: Ignored so a random test is not charged with this file-level statement
Module::warnMissingPackages(Module::MODULE_RECTOR);
// @codeCoverageIgnoreEnd

/**
 * Builds a ready-to-use Rector config that captures the @brnshkr refactoring decisions.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/Rector.md
 *
 * @no-named-arguments
 *
 * @see RectorTest
 */
final class Rector
{
    private function __construct(
        private readonly RectorConfigBuilder $rectorConfigBuilder,
        /**
         * @var list<non-empty-string>
         */
        private array $paths = [],
        /**
         * @var array<array-key, mixed>
         */
        private array $skips = [],
        /**
         * @var list<class-string<RectorInterface>>
         */
        private array $rules = [],
    ) {}

    /**
     * Build a fully configured RectorConfigBuilder.
     *
     * Caller may pass a Finder to narrow paths under analysis; otherwise the project-wide
     * {@see FileFinder} defaults apply.
     *
     * @example
     * ```php
     * // conf/rector.php
     * return Rector::getConfig();
     * ```
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return RectorConfigBuilder configured builder ready for Rector
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws RuntimeException when required Rector dependencies are missing
     */
    public static function getConfig(?Finder $finder = null): RectorConfigBuilder
    {
        return self::getBuilder($finder)->build();
    }

    /**
     * A builder another file already built, to add to.
     *
     * This is what a private `conf/rector.php` reaches for: the tracked config it includes stays the
     * baseline, and every verb here adds to it rather than replacing what that file configured.
     * The paths that config resolved stay as they are, so {@see self::setPaths()} is the only way to
     * change them from here.
     *
     * @example
     * ```php
     * // conf/rector.php
     * $config = include __DIR__ . '/rector.dist.php';
     *
     * return Rector::from($config)
     *     ->addSkips([SomeRector::class])
     *     ->build()
     * ;
     * ```
     *
     * @param RectorConfigBuilder $rectorConfigBuilder config to extend
     *
     * @return self the builder, wrapping that config
     */
    public static function from(RectorConfigBuilder $rectorConfigBuilder): self
    {
        return new self($rectorConfigBuilder);
    }

    /**
     * The same configuration as {@see self::getConfig()}, as a builder to extend before
     * {@see self::build()} finalizes it.
     *
     * @example
     * ```php
     * // conf/rector.php
     * return Rector::getBuilder()
     *     ->addSkips([SomeRector::class])
     *     ->build()
     * ;
     * ```
     *
     * @param ?Finder $finder pre-configured Finder to extend, or null for project defaults
     *
     * @return self the builder, pre-configured with the baseline
     *
     * @throws DirectoryNotFoundException when FileFinder cannot resolve the source directory
     * @throws RuntimeException when required Rector dependencies are missing
     */
    public static function getBuilder(?Finder $finder = null): self
    {
        $rectorConfigBuilder = RectorConfig::configure()
            ->withCache('.cache/rector.cache')
            ->withRootFiles()
            ->withPhpSets()
            ->withAttributesSets()
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                codingStyle: true,
                typeDeclarations: true,
                privatization: true,
                naming: true,
                rectorPreset: true,
                phpunitCodeQuality: true,
                phpunitNarrowAsserts: true,
                phpunitMockToStub: true,
                doctrineCodeQuality: true,
                symfonyCodeQuality: true,
                symfonyConfigs: true,
            )
            ->withComposerBased(
                twig: true,
                doctrine: true,
                phpunit: true,
                symfony: true,
            )
            ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
                AddOverrideAttributeToOverriddenMethodsRector::ADD_TO_INTERFACE_METHODS    => true,
                AddOverrideAttributeToOverriddenMethodsRector::ALLOW_OVERRIDE_EMPTY_METHOD => true,
            ])
        ;

        $editorUrl = EditorUrl::forRector();

        if ($editorUrl !== null) {
            $rectorConfigBuilder->withEditorUrl($editorUrl);
        }

        /** @disregard P1009 Some skipped classes come bundled with rector and are not picked up by intelephense */
        return new self($rectorConfigBuilder, FileFinder::getFilePaths($finder), [
            LocallyCalledStaticMethodToNonStaticRector::class,
            NewlineBeforeNewAssignSetRector::class,
            NewlineBetweenClassLikeStmtsRector::class,
            PreferPHPUnitThisCallRector::class,
            SimplifyQuoteEscapeRector::class,
        ]);
    }

    /**
     * Add rules, keeping the ones already configured.
     *
     * @example
     * ```php
     * $builder->addRules([SomeRector::class]);
     * ```
     *
     * @param list<class-string<RectorInterface>> $rules rule classes to add
     */
    public function addRules(array $rules): self
    {
        $this->rules = [...$this->rules, ...$rules]
            |> array_unique(...)
            |> array_values(...);

        return $this;
    }

    /**
     * Replace the rules this builder registers, leaving the prepared sets alone.
     *
     * Only rules added through {@see self::addRules()} are replaced. The baseline's rules arrive
     * through `withPreparedSets()`, which this cannot reach — dropping one of those is
     * {@see self::removeRules()}.
     *
     * @example
     * ```php
     * $builder->setRules([SomeRector::class]);
     * ```
     *
     * @param list<class-string<RectorInterface>> $rules rule classes to run, replacing every one added here
     */
    public function setRules(array $rules): self
    {
        $this->rules = [];

        return $this->addRules($rules);
    }

    /**
     * Add skip entries, keeping the ones already configured.
     *
     * @example
     * ```php
     * $builder->addSkips([SomeRector::class => ['src/legacy']]);
     * ```
     *
     * @param array<array-key, mixed> $skips rule classes, paths, or `[rule => paths]` entries
     */
    public function addSkips(array $skips): self
    {
        $this->skips = [...$this->skips, ...$skips];

        return $this;
    }

    /**
     * Drop rules from the run, including ones a prepared set brought in.
     *
     * Rector has no list to subtract from — the baseline's rules arrive through `withPreparedSets()`,
     * so removing one means skipping it. That reaches set-provided rules, which a subtractive list
     * could not.
     *
     * @example
     * ```php
     * Rector::getBuilder()->removeRules([AddOverrideAttributeToOverriddenMethodsRector::class]);
     * ```
     *
     * @param list<class-string<RectorInterface>> $rules rule classes to stop running
     */
    public function removeRules(array $rules): self
    {
        return $this->addSkips($rules);
    }

    /**
     * Set the paths Rector processes, replacing the ones already there.
     *
     * @example
     * ```php
     * $builder->setPaths(['src']);
     * ```
     *
     * @param list<non-empty-string> $paths paths to process
     */
    public function setPaths(array $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Add paths to process, keeping the ones already configured.
     *
     * @example
     * ```php
     * $builder->addPaths(['stubs']);
     * ```
     *
     * @param list<non-empty-string> $paths paths to append
     */
    public function addPaths(array $paths): self
    {
        $this->paths = [...$this->paths, ...$paths]
            |> array_unique(...)
            |> array_values(...);

        return $this;
    }

    /**
     * Drop paths from the run, leaving the rest processed.
     *
     * @example
     * ```php
     * $builder->removePaths(['tests']);
     * ```
     *
     * @param list<non-empty-string> $paths paths to stop processing
     */
    public function removePaths(array $paths): self
    {
        $this->paths = array_values(array_filter(
            $this->paths,
            static fn (string $path): bool => !in_array($path, $paths, true),
        ));

        return $this;
    }

    /**
     * Replace the skip list outright.
     *
     * Drops the baseline's skips, so the run skips exactly what is passed here.
     *
     * @example
     * ```php
     * $builder->setSkips([NewlineBetweenClassLikeStmtsRector::class]);
     * ```
     *
     * @param array<array-key, mixed> $skips skip entries, replacing every one already configured
     */
    public function setSkips(array $skips): self
    {
        $this->skips = $skips;

        return $this;
    }

    /**
     * Drop skip entries, leaving the rest skipped.
     *
     * Matches an entry as it was given: a rule class drops that rule's skip, a path drops that path's.
     *
     * @example
     * ```php
     * $builder->removeSkips([PreferPHPUnitThisCallRector::class]);
     * ```
     *
     * @param array<array-key, mixed> $skips skip entries to stop skipping
     */
    public function removeSkips(array $skips): self
    {
        foreach ($this->skips as $key => $value) {
            if (in_array(is_int($key) ? $value : $key, $skips, true)) {
                unset($this->skips[$key]);
            }
        }

        return $this;
    }

    /**
     * Finalize the builder into the config Rector consumes.
     *
     * Hands over the paths, rules and skips collected here and forgets them, so calling this twice
     * does not register them twice.
     *
     * @return RectorConfigBuilder configured builder ready for Rector
     */
    public function build(): RectorConfigBuilder
    {
        if ($this->paths !== []) {
            $this->rectorConfigBuilder->withPaths($this->paths);
        }

        if ($this->rules !== []) {
            $this->rectorConfigBuilder->withRules($this->rules);

            $this->rules = [];
        }

        if ($this->skips !== []) {
            $this->rectorConfigBuilder->withSkip($this->skips);

            $this->skips = [];
        }

        return $this->rectorConfigBuilder;
    }
}

// @codeCoverageIgnoreStart
return Rector::getConfig();
// @codeCoverageIgnoreEnd
