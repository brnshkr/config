# Makefile [🔍](../../conf/Makefile 'Go to source')

The JavaScript targets of the shared [`Makefile`](../Makefile.md).
Each one shows up once your project has a `package.json` and the tool is installed,
the linters only when there are files for them to read.

| Target | Description |
| --- | --- |
| `bun` | Runs `bun`. `bun-pack` packs your package into `./.local`. |
| `commitlint` | Lints a commit message. |
| `eslint` | Lints and fixes the code. |
| `markdownlint` | Lints and fixes the Markdown. |
| `stylelint` | Lints and fixes the styles. |
| `typescript` | Checks the types. |
| `vitest` | Runs the JavaScript tests. |

`make help vvv` lists each target's variants. `-list` shows what a tool would read,
e.g. the tests `vitest` finds or the files `bun` would pack.

Each tool reads its config from `./conf/`, e.g. `./conf/eslint.dist.mjs`, and the package's default if you have none.
ESLint and Vitest read `./conf/eslint.ts` and `./conf/vitest.ts` first. `typescript` uses your `tsconfig.json`.

Without arguments `commitlint` lints `.git/COMMIT_EDITMSG`, the message you are committing,
or the last commit on a fresh clone. In GitHub and GitLab CI it lints every commit the push or pull request brings.
To lint a range of your own, pass it:

```sh
make commitlint COMMITLINT_RANGE=origin/master..HEAD
```
