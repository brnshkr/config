# Rigor

## Tests

- A test pins behavior a later edit could silently break: a layout, a parse, a container path.
  One asserting only that a file exists or a target is unknown restates the change. Show a probe's output instead.
- Prove a guard is live by breaking it and watching the named test fail.
- Pin what a tool resolves with one snapshot per tool, scenarios inside it, not with string assertions over output.
- A fixture holds only what a test reads. Otherwise it is empty:
  JSON is `{}`, a stub that has to run `#!/bin/sh` and `exit 0`.
- A version or date a fixture must carry but no test reads is `1.0.0` or the Unix epoch (`1970-01-01T00:00:00.000Z`).
- Prefer tightening an existing test's assertions over adding a new one.

## Verification

- Run it and print the output before asserting anything. A passing test proves only the claim it makes.
- Measure the thing, not a proxy. A probe proves nothing until its real output is checked.
- A verdict (ready, verified, passes) lists every step, each run for real with the tool versions it ships with.
  A step that was not run makes the verdict "not proven".
- `eslint --fix`, Rector and snapshot updates are not reviews. Read every diff they write.
- For a bump, read what changed: the changelog of every supported peer, and the diff where it is vague.

## Decisions

- Mark every symbol a consumer imports `@api`, and keep hand-authored content possible beside anything generated.
- An `@internal` tag stays bare where its own namespace covers every caller.
  Otherwise it names the narrowest namespace that does: `@internal Brnshkr\Config\Mate`.
