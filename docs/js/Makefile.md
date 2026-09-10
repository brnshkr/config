# Makefile [🔍](../../conf/Makefile 'Go to source')

The JavaScript half of the shared [`Makefile`](../Makefile.md): one target per tool,
present once the repository has a `package.json` and that tool is installed.
Each reads a config under `./conf/`, overridable through `<TOOL>_CONFIG`;
`typescript` reads the repository's own `tsconfig.json`.
`eslint` and `vitest` take a TypeScript config where there is one, `conf/<tool>.config.ts` before the
`.mjs`, since both load it natively.

## Targets

| Target | Variants | Description |
| --- | --- | --- |
| `bun` | `-list`, `-pack` | Runs `bun`. `-pack` writes the package into `./.local`, `-list` names its files. |
| `commitlint` | `-print` | Lints a commit message rather than the tree. |
| `eslint` | `-dry-run`, `-group`, `-print` | Lints and writes its fixes. |
| `markdownlint` | `-dry-run`, `-group` | Lints and writes its fixes. |
| `stylelint` | `-dry-run`, `-group`, `-print` | Lints and writes its fixes. |
| `typescript` | `-group`, `-list`, `-print` | Runs the `tsc` type check. |
| `vitest` | `-coverage`, `-list`, `-update` | Runs the tests. |

`-list` prints what the tool would read: the files `bun` would pack, the tests `vitest` collects, the files
the `tsc` project includes.
The other suffixes are the shared ones, described in [Makefile](../Makefile.md).

`markdownlint` and `stylelint` are handed a glob built from `<TOOL>_EXTENSIONS`,
and `eslint` uses its own to decide which files a config is worth writing.
Each follows the packages that are installed, so a repository without `stylelint-config-html` gets no `.svelte` or `.vue`.

Given no arguments `commitlint` reads `.git/COMMIT_EDITMSG`, where the `commit-msg` hook leaves the message being written;
a fresh checkout has none, so `HEAD`'s message is linted instead.
That is what lets one target serve `check` and `ci` alike.
A range is passed as arguments.
