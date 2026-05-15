<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Tempest;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function array_map;
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
     * @param non-empty-string $root
     * @param non-empty-string $module
     * @param list<non-empty-string> $siblings
     */
    public function __construct(
        private string $root,
        private string $module,
        private array $siblings,
    ) {}

    /**
     * @internal
     */
    #[TestRule]
    public function getRule(): BuildStep
    {
        $siblings = array_map(fn (string $sibling): string => $this->root . '\\' . $sibling, $this->siblings);

        return PHPat::rule()
            ->classes(Selector::inNamespace($this->root . '\\' . $this->module))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::buildNamespaceSelectors($siblings))
            ->because(sprintf('%s must not depend on sibling modules.', $this->module))
        ;
    }
}
