<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Trait;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Str;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use RuntimeException;

use function lcfirst;
use function preg_replace;
use function sprintf;

/**
 * @internal
 */
trait RuleTrait
{
    /**
     * @throws RuntimeException
     */
    private static function buildRuleError(string $message, int $line, bool $isIgnorable = true): IdentifierRuleError
    {
        $className = Str::getClassShortName(self::class);

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        $ruleName = lcfirst(preg_replace('/Rule$/', '', $className) ?: 'unknown');

        $identifier = sprintf(
            '%s.%s',
            ComposerJson::forThisLibrary()->getPackageOrganization(),
            $ruleName,
        );

        $ruleErrorBuilder = RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->line($line)
        ;

        if (!$isIgnorable) {
            $ruleErrorBuilder->nonIgnorable();
        }

        return $ruleErrorBuilder->build();
    }

    private static function getClassLikeName(ClassLike $classLike): string
    {
        return $classLike->name?->toString() ?: '<unknown>';
    }
}
