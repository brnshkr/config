# Makefile [🔍](../../conf/Makefile 'Go to source')

The PHP half of the shared [`Makefile`](../Makefile.md): one target per tool,
present once the repository has a `composer.json` and that tool is installed.
Each reads `./conf/<tool>.php` when it is there and the tracked `./conf/<tool>.dist.php` otherwise, so a
developer overrides without touching the repository — [Makefile](../Makefile.md) has the full search.
The shipped example for the local file includes the tracked one, which is the shape to keep.

## Targets

| Target | Variants | Description |
| --- | --- | --- |
| `composer` | `-list`, `-pack`, `-print` | Runs `composer`. `-pack` writes the package into `./.local`. |
| `php-cs-fixer` | `-dry-run`, `-group`, `-list`, `-print` | Fixes coding style. |
| `phpstan` | `-debug`, `-group`, `-list`, `-print`, `-raw` | Runs `phpstan analyze`. `-raw` drops the framing. |
| `phpunit` | `-coverage`, `-list`, `-update` | Runs the PHP tests through whichever runner is installed. |
| `pest` | `-coverage`, `-debug`, `-list`, `-update` | The same through Pest, and says so when Pest is missing. |
| `rector` | `-debug`, `-dry-run`, `-group`, `-list`, `-print` | Applies the automated refactorings. |
| `twig-cs-fixer` | `-debug`, `-dry-run`, `-group` | Fixes Twig coding style. |

`-list` prints what the tool would read, which is the quickest way to see what [FileFinder](./FileFinder.md) resolved.
The other suffixes are the shared ones, described in [Makefile](../Makefile.md).
A tool appears once the repository tracks a file it reads — `PHP_EXTENSIONS` for the four PHP tools,
`TWIG_CS_FIXER_EXTENSIONS` for templates.

The runner is Pest when it is installed and PHPUnit otherwise.
`PHP_UNIT` pins it, which is all a `pest` target does, and the snapshot and coverage flags follow whichever
command that variable names, since PHPUnit rejects the options Pest adds.

Its config follows the same search, `./conf/phpunit.xml` before `./conf/phpunit.dist.xml`, with one caveat:
PHPUnit merges nothing, so a local file has to be a whole configuration rather than an `include` of the
tracked one.
