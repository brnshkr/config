<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Library;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Forbid a package's model from depending on the libraries that populate it.
 *
 * The model is what a consumer receives and what the serialized form is derived from, so a parser
 * or framework type reaching it becomes part of both.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(ModelNoFrameworkTest::class, [
 *     'model'        => 'Acme\Model',
 *     'isolatedFrom' => ['PhpParser', 'Symfony'],
 * ]);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class ModelNoFrameworkTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $model model namespace
     * @param non-empty-list<non-empty-string> $isolatedFrom namespaces the model must not reach
     */
    public function __construct(
        private string $model,
        private array $isolatedFrom,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return self::buildNamespacesIsolationRule(
            $this->model,
            $this->isolatedFrom,
            'The model must not depend on the libraries that populate it.',
        );
    }
}
