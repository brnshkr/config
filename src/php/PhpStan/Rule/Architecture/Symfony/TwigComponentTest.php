<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Architecture\Symfony;

use Brnshkr\Config\PhpStan\Rule\Trait\ArchitectureRuleTrait;
use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;

use function sprintf;

/**
 * Enforce Symfony UX Twig component placement and attribute.
 *
 * Classes annotated with `#[AsTwigComponent]` or `#[AsLiveComponent]` must live under
 * `<root>\Twig\Components`, and every concrete class in that folder must carry one of them.
 *
 * @api
 *
 * @no-named-arguments
 *
 * @example
 * ```php
 * use Brnshkr\Config\PhpStan;
 *
 * PhpStan::configurePhpAtTest(TwigComponentTest::class, ['root' => 'Acme']);
 * ```
 */
final readonly class TwigComponentTest
{
    use ArchitectureRuleTrait;

    /**
     * @internal invoked by PHPat
     *
     * @param non-empty-list<non-empty-string> $roots - root namespaces, one per module
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
        $componentSelector = Selector::AnyOf(
            Selector::appliesAttribute('Symfony\UX\TwigComponent\Attribute\AsTwigComponent'),
            Selector::appliesAttribute('Symfony\UX\LiveComponent\Attribute\AsLiveComponent'),
        );

        foreach ($this->roots as $root) {
            yield self::buildPlacementRule($root, [$componentSelector], 'Twig\Components', 'Twig components');

            yield PHPat::rule()
                ->classes(Selector::AllOf(
                    self::selectInstantiableClasses(Selector::inNamespace($root . '\Twig\Components')),
                    Selector::Not($componentSelector),
                ))
                ->shouldNot()
                ->exist()
                ->because(sprintf(
                    'Classes under %s\Twig\Components must carry #[AsTwigComponent] or #[AsLiveComponent].',
                    $root,
                ))
            ;
        }
    }
}
