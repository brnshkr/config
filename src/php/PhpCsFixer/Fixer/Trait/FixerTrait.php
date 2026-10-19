<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpCsFixer\Fixer\Trait;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\Str;
use Override;
use RuntimeException;

use function sprintf;

/**
 * @internal
 */
trait FixerTrait
{
    /**
     * Return the name the fixer is configured under, built from the organization and the class name.
     *
     * @return non-empty-string the fixer name
     *
     * @example
     * ```php
     * use Brnshkr\Config\PhpCsFixer;
     * use Brnshkr\Config\PhpCsFixer\Fixer\FencedCodeFixer;
     *
     * return PhpCsFixer::getBuilder()
     *     ->removeRules([FencedCodeFixer::name()])
     *     ->build()
     * ;
     * ```
     */
    public static function name(): string
    {
        $snakeCaseName = Str::replaceMatches(
            Str::trimSuffix(Str::getClassShortName(self::class), 'Fixer'),
            '/(?<!^)[A-Z]/',
            static fn (array $matches): string => '_' . ($matches[0] ?? ''),
        );

        return self::getFixerName(Str::toLowerCase($snakeCaseName));
    }

    /**
     * @internal invoked by php-cs-fixer
     */
    #[Override]
    public function getName(): string
    {
        return self::name();
    }

    /**
     * @return non-empty-string
     */
    private static function getFixerName(string $name): string
    {
        try {
            $organization = ComposerJson::forThisLibrary()->getPackageOrganization();
        } catch (RuntimeException $runtimeException) {
            throw UnreachableException::wrap($runtimeException);
        }

        return sprintf('%s/%s', Str::upperFirst($organization), $name);
    }
}
