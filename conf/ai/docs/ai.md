# AI setup — agent notes

How the AI/MCP system of this repository works. Source of truth for changing it.

## Layout + hard constraints

- `conf/ai/` — everything real: `mate/` (MCP server source), `claude/` (Claude Code settings,
  symlinked from root `.claude`), `bin/codex` (+ `.bat`), `docs/` (these files).
- Root `mcp.json` + `.mcp.json` symlink — must stay at root: mate writes them,
  Claude Code only discovers `.mcp.json` there.
- Root `AGENTS.md` — must stay at root: read natively by Codex/agents; `mate discover` rewrites only its
  `AI_MATE_INSTRUCTIONS` managed block, custom content around it survives.
- Root `CLAUDE.md` — one line (`@AGENTS.md` import); Claude Code follows the import.
- `.claude` → `conf/ai/claude` and `.mcp.json` → `mcp.json` are committed symlinks
  (assumes a symlink-capable checkout — Linux/macOS/WSL; native-Windows clones need `core.symlinks=true` + Developer Mode).
- Root `mate/` (ai-mate hardcodes `$rootDir/mate/`) holds only generated artifacts:
  - `extensions.php` — committed, and **in the linted set** (not excluded). `make discover` rewrites it deterministically
    and post-processes it lint-clean (php-cs-fixer formatting plus `declare`/`@internal`, with mate's managed-by
    comment stripped), so there is no churn. Committed on purpose: the composer plugin is disabled, so a fresh clone
    needs this file present for `make discover` to regenerate the rest of `mate/` (see Lifecycle).
  - `AGENT_INSTRUCTIONS.md` — gitignored; regenerated every `composer install`. `mate serve` aggregates instructions live,
    so the file is only a materialized copy for humans / non-MCP agents.
- `composer.json` `extra.ai-mate` wires `scan-dirs`/`includes`/`instructions` to `conf/ai/mate/*`;
  autoload-dev maps `Brnshkr\Config\Mate\` there. `extra.ai-mate.extension: false` — this package is not
  itself a Mate extension, so consuming projects get none of this; it is purely for developing this repo.

## Lifecycle

On `composer install`/`update` the `post-install-cmd`/`post-update-cmd` scripts run `make discover`.
It calls `mate discover` (regenerating `extensions.php` + `mate/AGENT_INSTRUCTIONS.md` + the AGENTS.md managed block),
then post-processes `extensions.php` — php-cs-fixer formats it and an awk pass strips mate's managed-by comment
and inserts `@internal` — so the committed file stays lint-clean and in the linted set.
`symfony/ai-mate-composer-plugin` is disabled (`allow-plugins: false`) so its bare `mate discover --composer` can't
clobber that post-processing. `extensions.php` is committed so a fresh clone self-generates `mate/`;
to enable/disable an extension, edit it and commit.

## Instruction flow

`conf/ai/mate/INSTRUCTIONS.md` (hand-written source) → `mate discover` merges it with extension instructions into
`mate/AGENT_INSTRUCTIONS.md` → injected as MCP server instructions into every client session. Non-MCP agents reach the
same content via the AGENTS.md managed block pointer. Keep INSTRUCTIONS.md token-lean — it loads in every session.

## Clients

- Claude Code: `.mcp.json` (resolves `./vendor/bin/mate` against project root) + `conf/ai/claude/settings.json`
  (permissions, caveman plugin via `extraKnownMarketplaces`/`enabledPlugins`).
- Codex CLI: launch `./conf/ai/bin/codex` — registers the server via `-c mcp_servers.symfony_ai_mate.*`
  and cd's to the repo root first.
- VS Code-native MCP consumers: `.vscode/mcp.json` with `${workspaceFolder}` absolute command
  (relative commands fail there — spawn does not cd to the workspace).

## Adding an MCP tool

1. Class under `conf/ai/mate/src/Tool/`, namespace `Brnshkr\Config\Mate\Tool` — sits below the declaring
   `Brnshkr\Config` namespace so `@internal` symbols (e.g. `Module::MAP`) stay usable; cross-subnamespace helpers like
   `Support\Project` carry an explicit `@internal Brnshkr\Config\Mate` target.
2. `#[McpTool(name: '...', description: '...')]` on a public method; params/docblock become the input schema; return
   scalars or arrays only.
3. Discovery is automatic via `scan-dirs`; constructor DI works for container services. Use `Project::run()` for shell
   work (root-aware, output-truncating, stdin support).
4. Verify: `vendor/bin/mate mcp:tools:list`, `mcp:tools:call <name> '<json>'`.
   The code is linted by `make check` — full style rules apply.

## Debug CLI

`vendor/bin/mate` — `mcp:tools:list`, `mcp:tools:call`, `mcp:tools:inspect`, `debug:extensions`, `discover`.

## Known issues

- `symfony/ai-mate` is aliased `0.9.0 as 0.8.99` in composer.json — matesofmate extensions 0.3.0 require `^0.8`;
  drop the alias once 0.9-compatible releases exist.
- Cosmetic upstream bugs: composer-extension `ConfigResource` attribute fails against mcp/sdk 0.5 (stderr noise only);
  `mate mcp:tools:call` crashes TOON-decoding plain-string tool returns — the tool still executes,
  real MCP clients are unaffected.
