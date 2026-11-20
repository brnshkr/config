---
name: mate-brnshkr-commit-message
description: Check a commit message draft against the project's commitlint rules through Mate before committing. Not for checking code (`mate-brnshkr-code-quality`).
---

# Commit messages

Lints a draft against the project's commitlint config without creating a commit:

- `brnshkr-commitlint-check` (req `message`): returns `exitCode` and `output`.

## Workflow

1. Draft the subject, and a body only when one is asked for.
2. Check it, with the draft in `ARGS`:
   `make mate ARGS='tools:call brnshkr-commitlint-check "--message=feat(user): add login"'`.
3. Fix every rule it reports and check again until `exitCode` is `0`.
4. Show the final message before committing.

## Reading

- `exitCode` `0` means the draft passes. `output` lists each failed rule as `✖ <message> [<rule>]`.
