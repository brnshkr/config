# AI setup — agent notes

## Layout

- `conf/ai/` holds the mate extension's settings, instructions and skills in `mate/`,
  Claude Code settings in `claude/` and these docs. The mate tools are in `src/php/Mate/`.
- `AGENTS.md`, `CLAUDE.md`, and the `.claude` symlink stay at the root, where clients look.
  `CLAUDE.md` only imports `AGENTS.md`, which imports the rules in `conf/ai/INSTRUCTIONS.md`.
- Root `mate/` is generated. Only `mate/extensions.php` is committed; enable or disable an extension there.

## Changing it

- `make discover` regenerates `mate/`, the skills and the managed block of `AGENTS.md`, and runs on every `composer install`.
- `conf/ai/INSTRUCTIONS.md` loads into every session, so keep it lean.
- A tool is a `#[MateTool]` method on a class in `src/php/Mate/Tool/`, returning scalars or arrays,
  and named in `DisableToolsCompilerPass::TOOL_NAMES`.
  Shell work goes through `Project::run()`, a make target through `Project::runTarget()`.
- Without the shared Makefile marker, the Composer plugin turns this extension's skills off.
- `make discover` turns `brnshkr-commit-message` off without commitlint,
  and back on whatever it or the plugin turned off once the reason is gone.
  Both note what they turned off in `./.cache/brnshkr.cache/mate-disabled-skills`.
- Try a tool with `make mate -- tools:inspect <name>` and `make mate -- tools:call <name> --<param>=<value>`;
  `make mate` runs mate where the tools run and answers in TOON; `ARGS` carries a value with spaces or newlines intact.
