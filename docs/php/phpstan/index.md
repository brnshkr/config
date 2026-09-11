# PHPStan [🔍](../../../src/php/PhpStan.php 'Go to source')

`Brnshkr\Config\PhpStan` is the @brnshkr analysis configuration, ready to return from a config file.

## Usage

```php
// ./conf/phpstan.dist.php
use Brnshkr\Config\PhpStan;

return PhpStan::getConfig();
```

Pass a [Finder](https://symfony.com/doc/current/components/finder.html) to narrow the scope
— [`FileFinder`](../FileFinder.md) applies the shared exclusions either way.

## Customizing

`getBuilder()` gives the same configuration as a builder, and `build()` finishes it.

```php
return PhpStan::getBuilder()
    ->setLevel(10)
    ->addIgnoredErrors(['ternary.shortNotAllowed'])
    ->build()
;
```

A private `./conf/phpstan.php` starts from the tracked config rather than from the defaults,
so what the repository configured survives. `from()` takes what that file returns:

```php
// ./conf/phpstan.php
$config = include __DIR__ . '/phpstan.dist.php';

return PhpStan::from($config)
    ->addIgnoredErrors(['ternary.shortNotAllowed'])
    ->build()
;
```

A repository that tracks no config of its own has nothing to include; `getBuilder()` above
starts from the same defaults that file would have carried.

A method's verb says what it does to what is already there: `set*` replaces, `add*` merges, `remove*`
drops by name. A `set*` taking an option map changes only the keys you pass. Anything without a named setter goes
through `setParameter()`, in the shape PHPStan's own [config reference](https://phpstan.org/config-reference) documents.

## Ignoring an error

Name the identifier rather than the message: it survives a rewording, and PHPStan prints it beside every error.
Map one to `false` to also silence the report when it never matches.

```php
return PhpStan::getBuilder()
    ->addIgnoredErrors([
        'ternary.shortNotAllowed',
        'missingType.checkedException' => false,
        [
            'identifier' => 'brnshkr.internalUsage',
            'paths'      => [__DIR__ . '/../src/Legacy.php'],
        ],
    ])
    ->build()
;
```

## Unchecked exceptions

Analysis requires `@throws` for checked exceptions.
Declare the ones callers should not have to catch in `./conf/phpstan/unchecked-exceptions.php`,
returning a list of class names, and read a dependency's declaration rather than restating it:

```php
return PhpStan::getBuilder()
    ->addUncheckedExceptionsFrom('brnshkr/doxter')
    ->build()
;
```

## Symfony and Doctrine

Both extensions are configured from the project's kernel, so a project writes no loader scripts.
Name a kernel other than `App\Kernel` with `PHPSTAN_KERNEL_CLASS`, and replace either loader by putting
your own script at the same path under `./conf/phpstan`.

## Rules

- [Custom rules](./rules/index.md) — what this package checks beyond PHPStan's own
- [Architecture presets](./rules/architecture/index.md) — opt-in bundles, passed to `addArchitecture()`

`replaceRule()` re-registers a rule this package ships under your own arguments;
`configureRule()` builds a tagged definition for one of your own.
