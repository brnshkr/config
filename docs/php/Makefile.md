# Makefile [🔍](../../conf/Makefile 'Go to source')

The PHP targets of the shared [`Makefile`](../Makefile.md).
Each one shows up once your project has a `composer.json` and the tool is installed,
the linters only when there are files for them to read.

| Target | Description |
| --- | --- |
| `composer` | Runs `composer`. `composer-pack` packs your package into `./.local`. |
| `php-cs-fixer` | Fixes the PHP coding style. |
| `phpstan` | Analyzes the PHP code. |
| `phpunit` | Runs the tests with Pest if it is installed, PHPUnit otherwise. |
| `pest` | Runs the tests with Pest. |
| `rector` | Refactors the PHP code. |
| `twig-cs-fixer` | Fixes the Twig coding style. |

`make help vvv` lists each target's variants. `-list` shows which files a tool would read,
which is the quickest way to check what [FileFinder](./FileFinder.md) picked up.

Each tool reads `./conf/<tool>.dist.php` if you have one, and the package's default otherwise.
For settings only you want, `make configs local` writes a `./conf/<tool>.php` that includes the tracked one,
so you only add what differs. PHPUnit can't include another config, so a private `./conf/phpunit.xml`
has to be a complete one.
