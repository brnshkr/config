# `DevFunctionUsageRule` [🔍](../../../../src/php/PhpStan/Rule/DevFunctionUsageRule.php 'Go to source')

Production code must not call a function that only a development-only package declares.
A package autoloading functions through `autoload.files` puts them in the global namespace,
where the architecture presets' namespace rules cannot reach them, so this rule compares
where the called function is declared instead.

```php
namespace Acme\User;

// ❌ Bad — `dd()` comes from `symfony/var-dumper`, which production installs without
dd($user);

// ✅ Good — the tests may call it, since `autoload-dev` maps them
namespace Acme\User\Tests;

dd($user);
```

The configuration builder enables it with both lists derived from `composer.json`:
every package Composer marks development-only, minus `suggest`, and the `autoload-dev` directories.

## Options

| Option | Description |
| --- | --- |
| `developmentPackageDirectories` | where each development-only package lies, keyed by its name |
| `developmentDirectories` | directories whose code may call those functions |
