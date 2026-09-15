# Commits — agent notes

The enforced commitlint rules are in `conf/ai/mate/INSTRUCTIONS.md`.

## Protocol

1. Show the diff.
2. Stage, then offer four or five subjects through `AskUserQuestion`, checked with MCP `project-commitlint-check`.
   Each option's label carries the whole subject, scope included — the scope is itself a judgment call.
3. Commit only on the literal word "commit", which authorizes exactly one. A subject the user edited wins.

- Ask on every judgment call: how to split, the scope, the type, a breaking marker.
- A change to `docs/` or the AI docs goes in the commit whose behavior it documents.
- No body unless asked, and no destructive git operation without confirmation.
- Match the tone of recent `git log`.

## Scope

Required, lowercase, one to three `/` segments, and the narrowest that covers the change; reuse one from `git log`.
The segments are area, tool, then rule group: `php/phpstan/rule`, `js/eslint/unicorn`, `ai/mate`.
`general` is for a change spanning both stacks or the whole tree.

## Git

- A fix to an unpushed commit is a fixup, never a new commit:

  ```sh
  git commit --fixup <sha>
  GIT_SEQUENCE_EDITOR=true git rebase -i --autosquash --autostash <sha>~1
  ```

- To split one file across commits, save `git diff --cached <file>` and `git diff <file>` as patches,
  restore the file, then `git apply --cached` the part that lands first. Hunks whose context overlaps go in one commit.
