# @brnshkr/config

Dual-stack (JS + PHP) config package: centralized opinionated tooling
(ESLint, markdownlint, Stylelint, PHPStan, Rector, PHP-CS-Fixer, Twig-CS-Fixer)
for all @brnshkr projects. This repo lints itself with its own configs.

## Working here

- **Rules** (code style, tooling, commits) are injected every session as MCP server instructions.
  Without MCP, read [`conf/ai/mate/INSTRUCTIONS.md`](conf/ai/mate/INSTRUCTIONS.md) before writing code.
- **Prefer the `project-*` MCP tools** over raw CLI (quality, tests, version sync, commit lint).
  They return compact, structured output.
- **Commands, setup, tool/rule reference** live in [`docs/`](docs/)
  — start at [`docs/development.md`](docs/development.md). Never duplicate that here.
- **Deep agent references** live in [`conf/ai/docs/`](conf/ai/docs/): `ai` (this AI/MCP setup), `commit`, `php`, `js`, `make`.
- **Codex**: launch `./conf/ai/bin/codex` — it registers the MCP server and runs from the repo root.

## Map

- `src/js/eslint/` — flat-config builder; rule groups in `configs/` (incl. custom rules `configs/builtin/`)
  lazy plugin loading `utils/module.ts`, typegen output `types/`
- `src/js/stylelint/` — config builder, named modes in `configs/`
- `src/php/` — builders `PhpStan.php`, `Rector.php`, `PhpCsFixer.php`,
  `TwigCsFixer.php`, `FileFinder.php`; Composer plugin `Composer/`; custom rules `PhpStan/Rule/`
- `conf/` — this repo's own tool configs; `conf/Makefile` = reusable foundation (colorized help system)
- `tests/{js,php}/` — Vitest + Pest, snapshots in `__snapshots__/`
- `docs/` — user-facing reference; PHP pages PascalCase per class
- `conf/ai/` — all AI tooling: `mate/` (MCP server source; root `mate/` = generated, gitignored),
  `claude/` (symlinked from `.claude`), `bin/codex` (Codex launcher), `docs/` (deep agent refs)

## Response style

Respond terse like smart caveman: drop articles/filler/pleasantries/hedging, fragments OK, short synonyms.
Technical terms exact, code blocks + commit messages + docs normal prose, errors quoted exact.
Off only on "stop caveman"/"normal mode".

<!-- markdownlint-disable -->

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: matesofmate/composer-extension, matesofmate/phpstan-extension, matesofmate/phpunit-extension, symfony/ai-mate.
<!-- END AI_MATE_INSTRUCTIONS -->
