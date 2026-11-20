## Brnshkr Extension

These tools run the targets of the @brnshkr/config Makefile. Without that Makefile they are hidden.

Prefer them over raw tool commands when the user is checking, fixing or testing the code.

| User intent | Prefer |
| --- | --- |
| Check the code with one tool | `brnshkr-quality-check --tool=<tool>` |
| Count a tool's findings by rule | `brnshkr-quality-check --tool=<tool> --isGrouped` |
| Apply a fixer's changes | `brnshkr-quality-check --tool=<tool> --isDryRun=false` |
| Run the JS tests | `brnshkr-test-run --suite=js` |
| Update the test snapshots | `brnshkr-test-run --suite=<php or js> --doUpdateSnapshots` |
| Check a commit message draft | `brnshkr-commitlint-check --message=<draft>` |
| List the PHP config modules | `brnshkr-module-list` |
| Read a PHP module's resolved config | `brnshkr-module-config --module=<module>` |

### Guidance

- `--arguments` passes files or flags on to the tool: `--tool=eslint --arguments='./src/user.ts'`.
- A boolean is a bare flag. A value with spaces or newlines goes in `ARGS`:
  `make mate ARGS='tools:call brnshkr-commitlint-check "--message=feat(user): add login"'`.
- `brnshkr-quality-check` rejects an unknown `--tool` and lists this project's tools.
- `rector-preview` and `rector-apply` take `--configuration` with `rector`'s `configFile` from `brnshkr-module-list`.
- With a `TARGET_PREFIX`, `make mate` reads `make <prefix>-mate`.
- The `mate-brnshkr-code-quality` and `mate-brnshkr-test-run` skills cover the fix loop and snapshot updates.
  Consult them before improvising either.
