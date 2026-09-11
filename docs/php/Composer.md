# Composer plugin [🔍](../../src/php/Composer/Command/ 'Go to source')

The package doubles as a Composer plugin. Once you allow it to run — Composer prompts on first install —
it registers a few helper commands under the `brnshkr:config` namespace.

For full usage of any command run `composer help <command>`, `composer <command> --help`, or `composer <command> -h`.

## Commands

| Command | Alias | Description |
| --- | --- | --- |
| `brnshkr:config` | `b:c` | Displays the plugin overview and a list of available commands. |
| `brnshkr:config:update-php-extensions` | `b:c:upe` | Scans installed packages and updates `composer.json` with the required `ext-*` platform packages. |
| `brnshkr:config:extract-phar <package>` | `b:c:ep` | Extracts a `.phar` file from a given vendor package. |
| `brnshkr:config:print-module-config <module>` | `b:c:pmc` | Prints a module's resolved configuration as JSON. |
| `brnshkr:config:setup [<modules>...]` | `b:c:s` | Installs the packages the modules you pick need. |

`setup` installs packages and nothing else. Files are `make` work:
copy `./conf/Makefile.example` to `./Makefile`, then `make startup` — see [Makefile](./Makefile.md).
