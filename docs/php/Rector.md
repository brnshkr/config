# Rector [🔍](../../src/php/Rector.php 'Go to source')

`Brnshkr\Config\Rector::getConfig()` builds a ready-to-use Rector config that captures the @brnshkr refactoring
decisions. Among them, the `#[\SensitiveParameter]` attribute rule comes pre-wired to a list of parameter names commonly
associated with secrets (`password`, `apiToken`, `clientSecret`, and many others), with plural variants generated
at runtime, so newly introduced sensitive parameters automatically get the attribute added.

## Usage

```php
use Brnshkr\Config\Rector;

return Rector::getConfig();
```

Scope the run with a [`FileFinder`](./FileFinder.md) argument.
`getConfig()` returns a `RectorConfigBuilder`; customizing it further is Rector's own API.
