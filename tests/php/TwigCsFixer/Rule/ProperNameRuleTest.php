<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\TwigCsFixer\Rule;

use Brnshkr\Config\ProperNames;
use Brnshkr\Config\Str;
use Brnshkr\Config\TwigCsFixer\Rule\ProperNameRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Runner\Fixer;
use TwigCsFixer\Token\Tokenizer;

/**
 * @internal
 */
#[CoversClass(ProperNameRule::class)]
#[UsesClass(ProperNames::class)]
#[UsesClass(Str::class)]
final class ProperNameRuleTest extends TestCase
{
    public function testCommentsSpellEachNameTheWayItsProjectSpellsIt(): void
    {
        $ruleset = new Ruleset();

        $ruleset->addRule(new ProperNameRule());

        $fixedTemplate = new Fixer(new Tokenizer(new StubbedEnvironment()))->fixFile(
            "{# Built on Symfony and Github, served by caddy. #}\n<p>Github</p>\n",
            $ruleset,
        );

        self::assertSame("{# Built on Symfony and GitHub, served by caddy. #}\n<p>Github</p>\n", $fixedTemplate);
    }
}
