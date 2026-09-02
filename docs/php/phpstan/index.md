# PHPStan [🔍](../../../src/php/PhpStan.php 'Go to source')

`Brnshkr\Config\PhpStan::getConfig()` builds the @brnshkr PHPStan configuration.
Unlike the formatter builders it can return either a finalized config array (the default) or a fluent builder instance
— pass `true` as the second argument when you need to chain further setters before producing the final array.

## Usage

The simplest form returns the array directly. Use it when the @brnshkr defaults are already exactly what the project needs.

```php
use Brnshkr\Config\PhpStan;

return PhpStan::getConfig();
```

If the defaults are all you need, you can skip the wrapper entirely:

```php
// ./conf/phpstan.dist.php

return [
    'includes' => [
        __DIR__ . '/../vendor/brnshkr/config/src/php/PhpStan.php',
    ],
];
```

To extend the defaults, opt into the builder and finish with `toArray()`:

```php
use Brnshkr\Config\PhpStan;

return PhpStan::getConfig(null, true)
    ->setLevel(10)
    ->setPaths(['src'], ['src/Excluded'])
    ->toArray()
;
```

## Scoping the run

Scope the run with a [`FileFinder`](../FileFinder.md) argument, or override the analyzed paths outright with `setPaths()`
— pick whichever feels closer to intent.

## The fluent builder

Many setters are thin pass-throughs that drop their argument straight into a section of PHPStan's own config
— usually a key under `parameters`. For those, the accepted shape and meaning of the value is PHPStan's own,
documented in the [PHPStan config reference](https://phpstan.org/config-reference), and this package does not restate it.
Others do more than pass through — they merge and dedup, route the value across sections, or transform it outright.
The "Transform" column flags which is which.

| Builder method | Writes to | Transform |
| --- | --- | --- |
| `setLevel()` | `parameters.level` | — |
| `setPaths()` | `parameters.paths` (+ `parameters.excludePaths`) | also forwards exclusions to `setExcludedPaths()` |
| `setExcludedPaths()` | `parameters.excludePaths` | a flat list is wrapped as `{analyseAndScan: …}` |
| `setBootstrapFiles()` | `parameters.bootstrapFiles` | — |
| `setTemporaryDirectory()` | `parameters.tmpDir` | — |
| `setIgnoredErrors()` | `parameters.ignoreErrors` | — |
| `setFeatureToggles()` | `parameters.featureToggles` | — |
| `setExceptions()` | `parameters.exceptions` | — |
| `setParameter()` / `setParameters()` | `parameters.*` (any key) | — |
| `setIncludes()` | `includes` | merged + deduped, order preserved |
| `setStrictRules()` | `parameters.strictRules` | — |
| `setTypePerfect()` | `parameters.type_perfect` | — |
| `setEditor()` | `parameters.editorUrl` | builds the OSC-8 URL template from the editor id |
| `setSymfony()` | `parameters.symfony` | — |
| `setDoctrine()` | `parameters.doctrine` | — |
| `setRules()` / `removeRules()` | `rules` + `services` | class-strings → `rules`, configured services → `services` |
| `setServices()` / `removeServices()` | `services` | deduped by class + arguments |
| `setArchitecture()` / `removeArchitecture()` | `services` | flattens nested preset lists |

The setters that back an optional extension
(`setStrictRules()`, `setTypePerfect()`, `setSymfony()`, `setDoctrine()`, `setArchitecture(`) throw a `RuntimeException`
when the corresponding package is not installed — install it before calling.

## Registering rules, services & architecture

What this package adds on top of PHPStan are its own custom rules and architecture presets,
plus the helpers to register them. Rules, services, and architecture rules go in through
`setRules()` / `setServices()` / `setArchitecture()` and come out through their `remove*()` counterparts.

```php
use Acme\PhpStan\Rule\NoStaticFactoryRule;
use Brnshkr\Config\PhpStan;
use Brnshkr\Config\PhpStan\Rule\BoolishPrefixRule;

return PhpStan::getConfig(null, true)
    ->removeRules([BoolishPrefixRule::class])
    ->setRules([
        PhpStan::configureRule(NoStaticFactoryRule::class, [
            'allowedFactoryNames' => ['fromArray', 'fromString'],
        ]),
    ])
    ->toArray()
;
```

Architecture presets are passed to `setArchitecture()` — see [Architecture presets](./rules/architecture/index.md)
for the factory and composition shapes.

## Static helpers

The `configure*()` helpers build correctly-tagged service definitions
so callers never hand-write PHPStan's or PHPat's tag strings:

- `PhpStan::configureRule()` → a definition for `setRules()` (tag `phpstan.rules.rule`)
- `PhpStan::configureStaticThrowTypeExtension()` → a definition for `setServices()` (tag `phpstan.dynamicStaticMethodThrowTypeExtension`)
- `PhpStan::configurePhpAtTest()` → a definition for `setArchitecture()` (tag `phpat.test`)

`PhpStan::getPreferredClassesMap()` builds the project-wide preferred-class replacement map
(e.g. `DateTime` → `DateTimeImmutable`) for Symplify's `PreferredClassRule`,
extended automatically for the optional packages it detects.

## Custom Rules

See [Custom PHPStan Rules](./rules/index.md).
