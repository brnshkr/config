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
copy `./conf/Makefile.dist` to `./Makefile`, then `make startup` — see [Makefile](./Makefile.md).

## Minimum release age

The plugin holds back versions released less than 7 days ago, as the shipped `bunfig.toml` does for Bun.
Set under `extra.brnshkr.config` in `composer.json`:

| Key | Default | Meaning |
| --- | --- | --- |
| `minimum-release-age` | `604800` | Seconds a version must be out; `0` turns the check off. |
| `minimum-release-age-excludes` | `[]` | Excluded packages; `*` matches any run of characters. |

Never held back: the root package, platform packages (`php`, `ext-*`),
dev versions such as `dev-main` and versions without a release time.
`-v` lists the versions held back and those taken without a release time.
