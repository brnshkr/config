<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture;

use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\ApplicationNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoApplicationTest;
use Brnshkr\Config\PhpStan\Rule\Architecture\Layered\DomainNoInfrastructureTest;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use Brnshkr\Config\Str;
use InvalidArgumentException;

use function sprintf;

/**
 * @api
 *
 * @phpstan-import-type PhpAtService from PhpStan
 *
 * @phpstan-ignore brnshkr.noNamedArgumentsTag (Explicitly allow named arguments here)
 */
final class Architecture
{
    use ArchitectureRuleTrait;

    public const string DEFAULT_ROOT = 'App';

    private function __construct() {}

    /**
     * Build layered architecture rules enforcing dependency direction constraints.
     *
     * Creates PHPStan architecture tests that prevent:
     *   - Domain depending on Application
     *   - Domain depending on Infrastructure
     *   - Application depending on Infrastructure
     *
     * Intended for classic layered architectures with strict inward dependency flow.
     *
     * @example
     * ```php
     * $layered = Architecture::layered(
     *     domain: 'Acme\Domain',
     *     application: 'Acme\Application',
     *     infrastructure: 'Acme\Infrastructure',
     * );
     * ```
     *
     * @param non-empty-string $domain Domain layer namespace
     * @param non-empty-string $application Application layer namespace
     * @param non-empty-string $infrastructure Infrastructure layer namespace
     *
     * @return non-empty-list<PhpAtService> Configured architecture rule services
     *
     * @throws InvalidArgumentException When a namespace is empty after normalization
     */
    public static function layered(
        string $domain = self::DEFAULT_ROOT . '\Domain',
        string $application = self::DEFAULT_ROOT . '\Application',
        string $infrastructure = self::DEFAULT_ROOT . '\Infrastructure',
    ): array {
        $domain         = self::normalizeNonEmptyNamespace($domain, 'domain');
        $application    = self::normalizeNonEmptyNamespace($application, 'application');
        $infrastructure = self::normalizeNonEmptyNamespace($infrastructure, 'infrastructure');

        return [
            PhpStan::configurePhpAtTest(DomainNoApplicationTest::class, [
                'domain'      => $domain,
                'application' => $application,
            ]),
            PhpStan::configurePhpAtTest(DomainNoInfrastructureTest::class, [
                'domain'         => $domain,
                'infrastructure' => $infrastructure,
            ]),
            PhpStan::configurePhpAtTest(ApplicationNoInfrastructureTest::class, [
                'application'    => $application,
                'infrastructure' => $infrastructure,
            ]),
        ];
    }

    private static function normalizeNamespace(string $namespace): string
    {
        $normalized = Str::trim($namespace);
        $normalized = Str::replace($normalized, '/', '\\');

        do {
            $previous   = $normalized;
            $normalized = Str::replace($normalized, '\\\\', '\\');
        } while ($normalized !== $previous);

        return Str::trim($normalized, '\\');
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeNonEmptyNamespace(string $namespace, string $paramName): string
    {
        $normalized = self::normalizeNamespace($namespace);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf(
                '"%s" namespace must not be empty.',
                $paramName,
            ));
        }

        return $normalized;
    }
}
