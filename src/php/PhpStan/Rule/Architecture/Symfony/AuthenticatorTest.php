<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Symfony Security authenticator placement and contract.
 *
 * Classes named `*Authenticator` must live under `<root>\Security\Authenticator`, and every
 * class in that folder must implement `Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface`.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(AuthenticatorTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class AuthenticatorTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<non-empty-string> $roots root namespaces, one per module
     */
    public function __construct(
        private array $roots,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        foreach ($this->roots as $root) {
            yield self::buildPlacementRule(
                $root,
                [self::selectByClassnameSuffix('Authenticator')],
                'Security\Authenticator',
                'Authenticators',
            );

            yield self::buildMustImplementRule(
                Selector::inNamespace($root . '\Security\Authenticator'),
                'Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface',
                'Authenticators must implement Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface.',
            );
        }
    }
}
