<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Examples;

use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\{Preset as GroupedPreset};
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder;
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Magic;
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Preset;
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Preset as AliasedPreset;

/**
 * Resolves through the file's imports, the block's own import and the placeholder `Acme` code.
 *
 * ```php
 * use Acme\Email\Mailer;
 *
 * $builder = Builder::create();
 * $name    = Builder::NAME;
 * $class   = Builder::class;
 * $preset  = AliasedPreset::forRoot(root: 'Acme', modules: ['User']);
 * $grouped = GroupedPreset::forRoot(root: 'Acme');
 * $user    = new \Acme\User\User();
 *
 * $builder->addPath('src')->addPath('tests', false)->build();
 * Mailer::send($user);
 * ```
 */
function resolves(): void {}

/**
 * Trusts the methods a class only provides through `@method` or `__call`.
 *
 * ```php
 * $magic = Magic::tagged();
 *
 * Magic::untagged(1, 2);
 * $magic->untagged(1, 2);
 * ```
 */
function trustsMagic(): void {}

/**
 * Reports every kind of mistake.
 *
 * ```php
 * Missing::create(); // ERROR Class `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Examples\Missing` does not exist.
 * Builder::missing(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::missing()` does not exist.
 * Builder::create()->missing(); // ERROR Method `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::missing()` does not exist.
 * $value = Builder::MISSING; // ERROR Constant `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::MISSING` does not exist.
 * Builder::create()->addPath(path: 'src'); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::addPath()` must not name its arguments, because it is tagged `@no-named-arguments`.
 * Preset::forRoot('Acme'); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Preset::forRoot()` must name its arguments, because it is tagged `@named-arguments`.
 * Preset::forRoot(root: 'Acme', unknown: 1); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Preset::forRoot()` names unknown parameter `$unknown`.
 * Builder::create()->addPath('src', true, false); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::addPath()` passes 3 arguments, but it takes 1 to 2.
 * Builder::create()->addPath(); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Builder::addPath()` passes 0 arguments, but it takes 1 to 2.
 * ```
 */
function reports(): void {}

/**
 * Knows the classes it declares itself.
 *
 * ```php
 * final class Registration
 * {
 *     public static function create(): self
 *     {
 *         return new self();
 *     }
 * }
 *
 * Registration::create();
 *
 * $registration = new Registration();
 * ```
 */
function declares(): void {}

/**
 * <!-- brnshkr-skip -->
 * ```php
 * Missing::create();
 * ```
 */
function skips(): void {}

/**
 * ```php
 * Builder::create( // ERROR Code block does not parse: Syntax error, unexpected EOF.
 * ```
 */
function breaks(): void {}

final class Documented
{
    /**
     * ```php
     * $documented->addPath('src');
     * $documented->addPath(); // ERROR Call to `Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\FencedCode\Examples\Documented::addPath()` passes 0 arguments, but it takes 1 to 2.
     * ```
     */
    public function addPath(string $path, bool $isRecursive = true): self
    {
        return $this;
    }
}
