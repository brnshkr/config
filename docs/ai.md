# 🤖 AI setup [🔍](../conf/ai 'Go to source')

`make configs ai` gives the agents in your project the commands that run its checks and Claude Code's permissions.
In @brnshkr projects, they also get the @brnshkr conventions.

## Usage

```sh
make configs ai
```

| File | Holds |
| --- | --- |
| `./AGENTS.md` | a block importing the commands and the conventions, above your own text |
| `./CLAUDE.md` | `@AGENTS.md`, inside mate's import block once mate is installed |
| `./conf/ai/claude/settings.json` | the shared permissions for the targets your project has, next to your own |
| `./.claude/` | links to everything in `./conf/ai/claude/` |

`make configs` keeps them up to date from then on.

With [Symfony AI Mate](https://github.com/symfony/ai-mate) installed,
`make mate -- tools:list` lists the tools that run your checks, and `make discover` refreshes what mate generates.

## Customizing

Put your own Claude Code agents, commands, and skills into `./conf/ai/claude/`:

```text
./conf/ai/claude/agents/reviewer.md
./conf/ai/claude/skills/release/SKILL.md
```

Add your own permissions to `./conf/ai/claude/settings.json`.
An entry the shared permissions name keeps their list.

Import the @brnshkr conventions outside the `brnshkr` vendor:

```Makefile
# ./Makefile

BRNSHKR_CONVENTIONS := 1

include ./vendor/brnshkr/config/conf/Makefile
```
