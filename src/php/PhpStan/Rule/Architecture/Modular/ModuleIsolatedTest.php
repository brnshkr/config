<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Modular;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function sprintf;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class ModuleIsolatedTest
{
    use ArchitectureRuleTrait;

    /**
     * @param non-empty-string $module
     * @param non-empty-string $label
     * @param list<non-empty-string> $siblings
     */
    public function __construct(
        private string $module,
        private string $label,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace($this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($this->siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->label))
        ;
    }
}
