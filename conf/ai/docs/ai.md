# AI setup — agent notes

| Path | Holds |
| --- | --- |
| `./conf/ai/conventions/` | @brnshkr conventions, imported by every @brnshkr project's `AGENTS.md` |
| `./conf/ai/make.md` | make rules, imported by every project's `AGENTS.md` that uses the shared Makefile |
| `./conf/ai/INSTRUCTIONS.md` | this repository's rules |
| `./conf/ai/claude/` | Claude Code files, linked entry by entry into `./.claude/` |
| `./conf/ai/mate/config.php` | mate settings for every project with the extension |
| `./conf/ai/mate/INSTRUCTIONS.md` | the extension's section of `./mate/AGENT_INSTRUCTIONS.md` |
| `./conf/ai/mate/skills/` | the extension's skills, installed as `mate-<name>` |
| `./src/php/Mate/` | mate tools, shipped |
| `./mate/` | mate's generated files, except `extensions.php` |

- `make configs ai` writes the `AGENTS.md` import block, `./CLAUDE.md`,
  `./conf/ai/claude/settings.json` and the `./.claude/` links. `make configs` updates them.
- Permissions come from `./conf/claude-settings.dist.json`, minus the `make` targets the project lacks.
  The template's list for an entry wins over the project's.
- `make discover` regenerates `./mate/`, the skills and mate's `AGENTS.md` block.
  Never run `mate discover` or `mate skills:install` directly.
- Extensions are toggled in `./mate/extensions.php`.
- A tool is a `#[MateTool]` method in `./src/php/Mate/Tool/`, listed in `DisableToolsCompilerPass::TOOL_NAMES`.
  Make targets go through `Project::runTarget()`, other commands through `Project::run()`.
- Without the shared Makefile, `config.php` disables the tools and the Composer plugin turns the skills off.
- `make discover` turns `brnshkr-commit-message` off without commitlint,
  and back on whatever it or the plugin turned off once the reason is gone.
  Both note what they turned off in `./.cache/brnshkr.cache/mate-disabled-skills`.
- Every file `AGENTS.md` imports stays short.
