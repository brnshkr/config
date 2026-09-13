# AI setup — agent notes

## Layout

- `conf/ai/` holds the MCP server source in `mate/`, Claude Code settings in `claude/`, `bin/codex` and these docs.
- `AGENTS.md`, `CLAUDE.md`, `mcp.json` and the `.mcp.json` and `.claude` symlinks stay at the root, where clients look.
- Root `mate/` is generated. Only `mate/extensions.php` is committed; enable or disable an extension there.

## Changing it

- `make discover` regenerates `mate/` and the managed block of `AGENTS.md`, and runs on every `composer install`.
- `conf/ai/mate/INSTRUCTIONS.md` loads into every session, so keep it lean.
- An MCP tool is a `#[McpTool]` method on a class in `conf/ai/mate/src/Tool/`, returning scalars or arrays.
  Shell work goes through `Project::run()`.
- Try a tool with `vendor/bin/mate mcp:tools:list` and `vendor/bin/mate mcp:tools:call <name> '<json>'`.
