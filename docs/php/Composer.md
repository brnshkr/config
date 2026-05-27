# Composer Plugin [🔍](../../src/php/Composer/Command/ 'Go to source')

The package doubles as a Composer plugin. Once you allow it to run (Composer prompts on first install), it registers a set of helper commands under the `brnshkr:config` namespace. The most important of them, `setup`, installs packages for the modules you pick, copies example config files into your repository, and can optionally create a `Makefile` and/or a `.gitignore`.

For full usage of any command run `composer help <command>`, `composer <command> --help`, or `composer <command> -h`.

## Commands

| Command | Alias | Description |
| --- | --- | --- |
| `brnshkr:config` | `b:c` | Displays the plugin overview and a list of available commands. |
| `brnshkr:config:setup [<modules>...]` | `b:c:s` | Interactive setup helper: installs suggested packages for the chosen modules, copies example config files, and can create a `Makefile` and/or `.gitignore`. |
| `brnshkr:config:update-php-extensions` | `b:c:upe` | Scans installed packages and updates `composer.json` with the required `ext-*` platform packages. |
| `brnshkr:config:extract-phar <package>` | `b:c:ep` | Extracts a `.phar` file from a given vendor package. |

## `setup` flags

`brnshkr:config:setup` runs interactively by default; the flags below skip the prompts. They can be combined into a single bundle, e.g. `composer brnshkr:config:setup -gofacme`.

| Flag | Short | Effect |
| --- | --- | --- |
| `--all` | `-a` | Install all modules. |
| `--optional` | `-o` | Automatically include all optional packages. |
| `--copy` | `-c` | Automatically copy config files for the selected modules. |
| `--make` | `-m` | Automatically create a `Makefile`. |
| `--gitignore` | `-g` | Automatically create a `.gitignore`. |
| `--force` | `-f` | Force an update to the latest package versions from the library. |
| `--exact` | `-e` | Install exact dependency versions. |
