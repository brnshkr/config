<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Trait;

use Brnshkr\Config\ComposerJson;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use RuntimeException;

use function lcfirst;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal Brnshkr\Config\PhpStan\Rule
 */
trait RuleTrait
{
    /**
     * @throws RuntimeException
     */
    private static function buildRuleError(string $message): IdentifierRuleError
    {
        $identifier = sprintf(
            '%s.%s',
            ComposerJson::forThisLibrary()->getPackageOrganization(),
            lcfirst(s(self::class)->afterLast('\\')->beforeLast('Rule')->toString()),
        );

        return RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->build()
        ;
    }
}
