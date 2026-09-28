# AI setup — agent notes

## Layout

- `conf/ai/` holds the mate tool source in `mate/`, Claude Code settings in `claude/` and these docs.
- `AGENTS.md`, `CLAUDE.md` and the `.claude` symlink stay at the root, where clients look.
  `CLAUDE.md` only imports `AGENTS.md`, which imports the rules in `conf/ai/mate/INSTRUCTIONS.md`.
- Root `mate/` is generated. Only `mate/extensions.php` is committed; enable or disable an extension there.

## Changing it

- `make discover` regenerates `mate/`, the skills and the managed block of `AGENTS.md`, and runs on every `composer install`.
- `conf/ai/mate/INSTRUCTIONS.md` loads into every session, so keep it lean.
- A tool is a `#[MateTool]` method on a class in `conf/ai/mate/src/Tool/`, returning scalars or arrays.
  Shell work goes through `Project::run()`, a make target through `Project::runTarget()`.
- Try a tool with `./scripts/mate.php tools:inspect <name>` and `./scripts/mate.php tools:call <name> --<param>=<value>`;
  `./scripts/mate.php` runs mate where the tools run, arguments untouched, and answers in TOON.
