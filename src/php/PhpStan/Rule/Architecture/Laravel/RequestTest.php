<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Laravel;

use Brnshkr\Config\PhpStan\Rule\Architecture\Architecture;
use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;

/**
 * Enforce Laravel form request placement and base class.
 *
 * Classes named `*Request` or extending `Illuminate\Foundation\Http\FormRequest` must live
 * under `<root>\Http\Requests`, and every `*Request` in that folder must extend FormRequest.
 *
 * @example
 * ```php
 * PhpStan::configurePhpAtTest(RequestTest::class, ['root' => 'Acme']);
 * ```
 *
 * @api
 *
 * @no-named-arguments
 */
final readonly class RequestTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-string $root Root application namespace
     */
    public function __construct(
        private string $root = Architecture::DEFAULT_ROOT,
    ) {}

    /**
     * @internal
     *
     * @return iterable<BuildStep>
     */
    #[TestRule]
    public function getRules(): iterable
    {
        yield self::buildPlacementRule(
            $this->root,
            [self::selectByClassnameSuffix('Request'), Selector::extends('Illuminate\Foundation\Http\FormRequest')],
            'Http\Requests',
            'Form requests',
        );

        yield self::buildMustExtendRule(
            Selector::AllOf(
                Selector::inNamespace($this->root . '\Http\Requests'),
                self::selectByClassnameSuffix('Request'),
            ),
            'Illuminate\Foundation\Http\FormRequest',
            'Form requests must extend Illuminate\Foundation\Http\FormRequest.',
        );
    }
}
