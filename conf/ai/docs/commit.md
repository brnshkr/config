# Commits — agent notes

Conventional-Commits basics: `README.md`. Enforced commitlint rules: `conf/ai/mate/INSTRUCTIONS.md`.
This file is the interactive workflow + git mechanics. No autopilot.

## Protocol

1. Show diff first (`git diff --stat` + substantive excerpts) before staging anything non-trivial.
2. Stage. One step per turn — never stage + ask in same turn.
3. Ask subject via `AskUserQuestion` (2–3 phrasings + Other).
4. Commit only after confirmation. User sometimes edits the proposed message before approving — the edited wording wins.
   Verify result: `git log -1 --format=%s`.
5. Validate drafts with MCP tool `project-commitlint-check` before committing.
6. Before staging, check whether the change touches anything covered by `docs/` or the AI files
   (`AGENTS.md`, `conf/ai/mate/INSTRUCTIONS.md`, `conf/ai/docs/*`) — updating them is a MUST,
   in the same commit when they document the changed behavior.

Pause + ask on every judgment call: hunk splits, scope token, `feat` vs `refactor` vs `chore`, breaking marker.
User redirects are authoritative ("skip group", "merge groups", "split further") — restructure immediately.
User splits aggressively (docs vs behavior = two commits). No destructive git ops (`reset --hard`, forced checkout, force-push)
without explicit confirmation. Substantial untracked code gets a style/review pass before staging.
Match tone of recent `git log` — short bodies, one short paragraph max.

Verification after each commit: `make check` clean;
JS side `bun check` (heavy — prefer filtered `bun run test <filter>`).

## Scope

Required, lower-case, `/`-delimited, one to three segments — the narrowest that still covers the change.
Read `git log` for an existing token before inventing one.

- segment 1 — stack or area (e.g. `php`, `js`, `make`, `composer`, `ci`, `ai`),
  or a bucket for work with no path of its own (e.g. `hygiene`, `release`, `dependencies`, `readme`)
- segment 2 — the tool or module (e.g. `php/phpstan`, `php/rector`, `php/string`, `js/eslint`, `js/stylelint`,
  `composer/commands`, `ai/mate`)
- segment 3 — a rule group or sub-area (e.g. `php/phpstan/rule`, `js/eslint/unicorn`, `js/stylelint/defensive`,
  `composer/setup`)

`general` is for a change spanning both stacks or the whole tree, not for one that is merely awkward to place.

New rule → `php/phpstan` / `js/eslint`; changing one → `.../rule`.  
`Str` → `php/string`. Symbols in backticks, methods without `()`.

## Fixup over fix-commits

Correcting landed commit: fixup + autosquash onto ORIGINAL, never new "fix" commit.

```sh
git stash push -- <unrelated dirty tracked files>   # untracked don't block rebase
git commit --fixup <sha>
git rebase --autosquash -i <sha>~1
git stash pop
```

Fix invalidates later-committed snapshot → create both fixups first, one rebase folds both.

## Hunk-splitting across commits

```sh
git diff --cached <file> > /tmp/staged.patch
git diff <file> > /tmp/unstaged.patch
git restore --staged <file> && git checkout -- <file>
# re-apply selectively with hand-edited patches:
git apply --cached <patch>
```

Context lines drift across hunks — patch made against state-with-hunk-A won't apply to fresh HEAD.
Context-entangled patches → bundle into one commit instead.
